<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\NotifiesThreadParticipants;
use App\Models\QuoteRequest;
use App\Models\Quotation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class QuotationController extends Controller
{
    use NotifiesThreadParticipants;

    /**
     * Specialist (or CEO/admin) sends a quotation for an assigned request — the
     * opening offer, or a revised one at any point before terms are finalized.
     */
    public function store(Request $request, QuoteRequest $quoteRequest): RedirectResponse
    {
        $user = $request->user();
        abort_unless(
            $user->can('manage all work') || $quoteRequest->assigned_to === $user->id,
            403
        );
        $this->ensureNegotiationOpen($quoteRequest);

        $data = $request->validate([
            'budget' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'estimated_days' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        // Only now that the new terms are valid does the pending offer give way —
        // superseding first would leave the client with no live offer if this failed.
        $this->supersedePending($quoteRequest);

        $days = (int) $data['estimated_days'];
        $currency = $data['currency'] ?? 'USD';

        Quotation::create([
            'quote_request_id' => $quoteRequest->id,
            'specialist_id' => $quoteRequest->assigned_to ?? $user->id,
            'proposed_by_id' => $user->id,
            'budget' => $data['budget'],
            'currency' => $currency,
            'estimated_days' => $days,
            'delivery_date' => now()->addDays($days)->toDateString(),
            'notes' => $data['notes'] ?? null,
            'status' => 'sent',
        ]);

        $quoteRequest->update(['status' => 'quoted']);

        $this->postToThread($quoteRequest, $user, sprintf(
            '%s sent a quotation: %s %s — delivery in %d day%s.',
            $user->name, $currency, number_format((float) $data['budget'], 2), $days, $days === 1 ? '' : 's'
        ), 'event');

        return redirect()
            ->route('quote-requests.show', $quoteRequest)
            ->with('status', 'Quotation sent to the client.');
    }

    /**
     * Client proposes their own terms — a custom amount and a delivery date they
     * choose — at any point before terms are finalized, no need to formally
     * decline the current offer first.
     */
    public function proposeCounter(Request $request, QuoteRequest $quoteRequest): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $quoteRequest->client_id === $user->id, 403);
        abort_unless($quoteRequest->assigned_to, 422, 'A specialist needs to be assigned before you can propose terms.');
        $this->ensureNegotiationOpen($quoteRequest);

        $data = $request->validate([
            'budget' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'delivery_date' => ['required', 'date', 'after:today'],
            'notes' => ['nullable', 'string'],
        ]);

        // Only now that the new terms are valid does the pending offer give way —
        // superseding first would leave the client with no live offer if this failed.
        $this->supersedePending($quoteRequest);

        $deliveryDate = Carbon::parse($data['delivery_date']);
        $currency = $data['currency'] ?? 'USD';

        Quotation::create([
            'quote_request_id' => $quoteRequest->id,
            'specialist_id' => $quoteRequest->assigned_to,
            'proposed_by_id' => $user->id,
            'budget' => $data['budget'],
            'currency' => $currency,
            'estimated_days' => max(1, (int) now()->startOfDay()->diffInDays($deliveryDate->copy()->startOfDay())),
            'delivery_date' => $deliveryDate->toDateString(),
            'notes' => $data['notes'] ?? null,
            'status' => 'sent',
        ]);

        $quoteRequest->update(['status' => 'quoted']);

        $this->postToThread($quoteRequest, $user, sprintf(
            '%s proposed new terms: %s %s — delivery by %s.',
            $user->name, $currency, number_format((float) $data['budget'], 2), $deliveryDate->format('M j, Y')
        ), 'event');

        return redirect()
            ->route('quote-requests.show', $quoteRequest)
            ->with('status', 'Your proposed terms have been sent.');
    }

    /**
     * Whoever did NOT make the current proposal accepts or rejects it — the
     * client decides on a specialist's offer, the specialist/staff decide on a
     * client's counter-proposal. Rejecting doesn't end the request; it just
     * clears the way for the other side to propose new terms.
     */
    public function respond(Request $request, Quotation $quotation): RedirectResponse
    {
        $quoteRequest = $quotation->quoteRequest;
        $user = $request->user();

        abort_unless($quotation->status === 'sent', 422, 'This proposal has already been responded to.');
        abort_unless($quoteRequest->latestQuotation?->is($quotation), 422, 'A newer proposal exists — respond to that one instead.');

        $isManager = $user && $user->can('manage all work');
        $proposedByClient = $quotation->wasProposedByClient();

        $allowed = $isManager || ($proposedByClient
            ? $quoteRequest->assigned_to === $user?->id
            : $quoteRequest->client_id === $user?->id);

        abort_unless($allowed, 403);

        $data = $request->validate([
            'decision' => ['required', 'in:accepted,rejected'],
        ]);

        $quotation->update([
            'status' => $data['decision'],
            'responded_at' => now(),
        ]);

        // A rejection keeps the request in negotiation ('quoted') rather than
        // dead-ending it, so either side can come back with revised terms.
        $quoteRequest->update([
            'status' => $data['decision'] === 'accepted' ? 'accepted' : 'quoted',
        ]);

        $this->postToThread($quoteRequest, $user, sprintf(
            '%s %s the proposal: %s %s — delivery by %s.',
            $user->name,
            $data['decision'] === 'accepted' ? 'accepted' : 'declined',
            $quotation->currency, number_format((float) $quotation->budget, 2),
            $quotation->delivery_date?->format('M j, Y') ?? $quotation->estimated_days.' days out'
        ), 'event');

        return redirect()
            ->route('quote-requests.show', $quoteRequest)
            ->with('status', $data['decision'] === 'accepted'
                ? 'Terms accepted. Ready to finalize the project.'
                : 'Proposal declined — new terms can be proposed.');
    }

    /**
     * New proposals are blocked once terms have been accepted — that's the
     * signal to finalize, not to keep amending. Letting a proposal through
     * here would silently supersede the accepted deal and pull the rug out
     * from under an in-progress finalization.
     */
    private function ensureNegotiationOpen(QuoteRequest $quoteRequest): void
    {
        $latest = $quoteRequest->latestQuotation;

        abort_if($latest && $latest->status === 'accepted', 422, 'Terms have already been accepted — finalize the project instead of proposing new terms.');
    }

    /**
     * A new proposal automatically replaces whatever's still pending a decision,
     * so either side can jump straight to "here's what I actually want" instead
     * of having to formally decline first.
     */
    private function supersedePending(QuoteRequest $quoteRequest): void
    {
        $latest = $quoteRequest->latestQuotation;

        if ($latest && $latest->status === 'sent') {
            $latest->update(['status' => 'superseded', 'responded_at' => now()]);
        }
    }
}
