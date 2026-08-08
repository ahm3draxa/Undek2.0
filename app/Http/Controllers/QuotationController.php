<?php

namespace App\Http\Controllers;

use App\Models\QuoteRequest;
use App\Models\Quotation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    /**
     * Specialist sends a quotation for an assigned request.
     */
    public function store(Request $request, QuoteRequest $quoteRequest): RedirectResponse
    {
        $user = $request->user();
        abort_unless(
            $user->hasAnyRole(['ceo', 'admin']) || $quoteRequest->assigned_to === $user->id,
            403
        );
        abort_if($quoteRequest->quotation()->exists(), 422, 'A quotation has already been sent for this request.');

        $data = $request->validate([
            'budget' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'estimated_days' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        Quotation::create([
            'quote_request_id' => $quoteRequest->id,
            'specialist_id' => $user->id,
            'budget' => $data['budget'],
            'currency' => $data['currency'] ?? 'USD',
            'estimated_days' => $data['estimated_days'],
            'notes' => $data['notes'] ?? null,
            'status' => 'sent',
        ]);

        $quoteRequest->update(['status' => 'quoted']);

        return redirect()
            ->route('quote-requests.show', $quoteRequest)
            ->with('status', 'Quotation sent to the client.');
    }

    /**
     * Client accepts or rejects the quotation they received.
     */
    public function respond(Request $request, Quotation $quotation): RedirectResponse
    {
        $quoteRequest = $quotation->quoteRequest;
        $user = $request->user();

        abort_unless(
            $user && ($quoteRequest->client_id === $user->id || $user->hasAnyRole(['ceo', 'admin'])),
            403
        );

        $data = $request->validate([
            'decision' => ['required', 'in:accepted,rejected'],
        ]);

        $quotation->update([
            'status' => $data['decision'],
            'responded_at' => now(),
        ]);

        $quoteRequest->update([
            'status' => $data['decision'] === 'accepted' ? 'accepted' : 'rejected',
        ]);

        return redirect()
            ->route('quote-requests.show', $quoteRequest)
            ->with('status', $data['decision'] === 'accepted'
                ? 'Quotation accepted. Our team will finalize your project shortly.'
                : 'Quotation declined.');
    }
}
