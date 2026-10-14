<?php

namespace App\Http\Controllers;

use App\Mail\MarketingCampaignMail;
use App\Models\MarketingCampaign;
use App\Models\MarketingCampaignRecipient;
use App\Models\MarketingUnsubscribe;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MarketingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:manage-marketing', except: ['unsubscribe', 'confirmUnsubscribe']),
        ];
    }

    public function index(): View
    {
        $campaigns = MarketingCampaign::with('sender')->withCount('recipients')->latest()->paginate(10);
        $clients = User::role('client')->orderBy('name')->get(['id', 'name', 'email']);

        $stats = [
            'campaigns' => MarketingCampaign::count(),
            'recipients' => MarketingCampaignRecipient::count(),
            'clients' => $clients->count(),
            'unsubscribed' => MarketingUnsubscribe::count(),
        ];

        $maxPerSend = config('mail.marketing.max_per_send');

        return view('marketing.index', compact('campaigns', 'clients', 'stats', 'maxPerSend'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'audience' => ['required', 'in:all_clients,selected,manual'],
            'client_ids' => ['required_if:audience,selected', 'array'],
            'client_ids.*' => ['exists:users,id'],
            'emails' => ['required_if:audience,manual', 'nullable', 'string', 'max:100000'],
        ], [
            'emails.required_if' => 'Paste at least one email address.',
        ]);

        $recipients = match ($data['audience']) {
            'all_clients' => $this->clientRecipients(User::role('client')),
            'selected' => $this->clientRecipients(User::role('client')->whereIn('id', $data['client_ids'] ?? [])),
            'manual' => $this->manualRecipients($data['emails'] ?? ''),
        };

        $unsubscribed = MarketingUnsubscribe::whereIn('email', $recipients->pluck('email'))->pluck('email')->flip();
        $skipped = $recipients->filter(fn ($r) => $unsubscribed->has($r['email']))->count();
        $recipients = $recipients->reject(fn ($r) => $unsubscribed->has($r['email']))->values();

        if ($recipients->isEmpty()) {
            throw ValidationException::withMessages([
                $data['audience'] === 'manual' ? 'emails' : 'audience' => $skipped
                    ? 'Everyone on this list has unsubscribed from marketing email.'
                    : 'No matching recipients to send to.',
            ]);
        }

        $max = config('mail.marketing.max_per_send');
        if ($recipients->count() > $max) {
            throw ValidationException::withMessages([
                $data['audience'] === 'manual' ? 'emails' : 'audience' => "That's {$recipients->count()} recipients — one campaign can go to at most $max. Split the list and send it in parts.",
            ]);
        }

        $campaign = MarketingCampaign::create([
            'user_id' => $request->user()->id,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'audience' => $data['audience'],
            'recipient_count' => $recipients->count(),
            'sent_at' => now(),
        ]);

        // Each email is queued, not sent inline: the scheduler works through
        // the queue a few per minute so a big list never trips the mail host's
        // rate limits or times out this request.
        foreach ($recipients as $recipient) {
            Mail::to($recipient['email'])->send(new MarketingCampaignMail($campaign, $recipient['email']));

            $campaign->recipients()->create([
                'user_id' => $recipient['user_id'],
                'email' => $recipient['email'],
                'sent_at' => now(),
            ]);
        }

        $message = "Campaign queued for {$recipients->count()} recipient(s).";
        if ($skipped) {
            $message .= " Skipped $skipped who unsubscribed.";
        }

        return redirect()->route('marketing.index')->with('status', $message);
    }

    /**
     * Landing page for the unsubscribe link in every campaign. GET only shows
     * a confirmation button — mail scanners pre-fetch links, and a GET that
     * unsubscribed would silently opt people out.
     */
    public function unsubscribe(Request $request, string $email): View
    {
        return view('marketing.unsubscribe', [
            'email' => $email,
            'done' => MarketingUnsubscribe::isUnsubscribed($email),
            'action' => $request->fullUrl(),
        ]);
    }

    /**
     * Also the target of the List-Unsubscribe-Post one-click header, which
     * mail clients POST to directly (hence no CSRF on this route).
     */
    public function confirmUnsubscribe(Request $request, string $email): View
    {
        MarketingUnsubscribe::firstOrCreate(['email' => strtolower($email)]);

        return view('marketing.unsubscribe', ['email' => $email, 'done' => true, 'action' => null]);
    }

    /**
     * @return Collection<int, array{email: string, user_id: int|null}>
     */
    private function clientRecipients($query): Collection
    {
        return $query->get(['id', 'email'])
            ->map(fn (User $user) => ['email' => strtolower($user->email), 'user_id' => $user->id]);
    }

    /**
     * One address per line (commas, semicolons and spaces also work), as
     * pasted from a spreadsheet or contact export. Duplicates are dropped;
     * any invalid address stops the send so it can be fixed first, rather
     * than silently going out to a partial list.
     *
     * @return Collection<int, array{email: string, user_id: int|null}>
     */
    private function manualRecipients(string $raw): Collection
    {
        $emails = collect(preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($email) => strtolower(trim($email, " \t\n\r\0\x0B<>\"'")))
            ->filter()
            ->unique()
            ->values();

        $invalid = $emails->reject(
            fn ($email) => Validator::make(['email' => $email], ['email' => 'email:rfc,strict'])->passes()
        );

        if ($invalid->isNotEmpty()) {
            throw ValidationException::withMessages([
                'emails' => sprintf(
                    '%d address%s %s not valid: %s%s',
                    $invalid->count(),
                    $invalid->count() === 1 ? '' : 'es',
                    $invalid->count() === 1 ? 'is' : 'are',
                    $invalid->take(10)->implode(', '),
                    $invalid->count() > 10 ? ' …' : '',
                ),
            ]);
        }

        $userIds = User::whereIn('email', $emails)->pluck('id', 'email');

        return $emails->map(fn ($email) => ['email' => $email, 'user_id' => $userIds[$email] ?? null]);
    }
}
