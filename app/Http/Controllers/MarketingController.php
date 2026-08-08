<?php

namespace App\Http\Controllers;

use App\Mail\MarketingCampaignMail;
use App\Models\MarketingCampaign;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class MarketingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:manage-marketing'),
        ];
    }

    public function index(): View
    {
        $campaigns = MarketingCampaign::with('sender')->withCount('recipients')->latest()->paginate(10);
        $clients = User::role('client')->orderBy('name')->get(['id', 'name', 'email']);

        return view('marketing.index', compact('campaigns', 'clients'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'audience' => ['required', 'in:all_clients,selected'],
            'client_ids' => ['required_if:audience,selected', 'array'],
            'client_ids.*' => ['exists:users,id'],
        ]);

        $recipients = $data['audience'] === 'all_clients'
            ? User::role('client')->get(['id', 'name', 'email'])
            : User::role('client')->whereIn('id', $data['client_ids'] ?? [])->get(['id', 'name', 'email']);

        abort_if($recipients->isEmpty(), 422, 'No matching recipients to send to.');

        $campaign = MarketingCampaign::create([
            'user_id' => $request->user()->id,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'audience' => $data['audience'],
            'recipient_count' => $recipients->count(),
            'sent_at' => now(),
        ]);

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->send(new MarketingCampaignMail($campaign, $recipient->email));

            $campaign->recipients()->create([
                'user_id' => $recipient->id,
                'email' => $recipient->email,
                'sent_at' => now(),
            ]);
        }

        return redirect()->route('marketing.index')->with('status', "Campaign sent to {$recipients->count()} client(s).");
    }
}
