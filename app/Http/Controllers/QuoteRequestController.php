<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersBySkill;
use App\Http\Controllers\Concerns\StreamsStoredFiles;
use App\Models\QuoteRequest;
use App\Models\QuoteRequestAttachment;
use App\Models\Service;
use App\Models\Skill;
use App\Models\User;
use App\Rules\SafeUpload;
use App\Services\Uploads\SecureUploader;
use App\Support\PublicCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuoteRequestController extends Controller
{
    use FiltersBySkill, StreamsStoredFiles;

    /**
     * Admin/specialist queue of incoming quote requests.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = QuoteRequest::with(['service', 'client', 'assignedSpecialist'])->latest();

        if ($user->can('manage all work')) {
            // Full visibility — no filter.
        } elseif ($user->can('send quotation')) {
            $query->where('assigned_to', $user->id);
        } else {
            $query->where('client_id', $user->id);
        }

        $stats = [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'quoted' => (clone $query)->where('status', 'quoted')->count(),
            'converted' => (clone $query)->where('status', 'converted')->count(),
        ];

        $quoteRequests = $query->paginate(15);

        return view('quote-requests.index', compact('quoteRequests', 'stats'));
    }

    /**
     * Public intake form — works for guests and logged-in clients.
     */
    public function create(Request $request): View
    {
        // Public page — cached so visitors don't spend the hourly MySQL
        // connection allowance (see PublicCache).
        ['services' => $services, 'skillsByService' => $skillsByService] = PublicCache::remember(PublicCache::QUOTE_FORM, function () {
            $services = Service::where('is_active', true)->with('skills.category')->orderBy('name')->get();

            $allSkillsByCategory = Skill::with('category')->orderBy('name')->get()
                ->groupBy(fn ($skill) => $skill->category->name ?? 'Other')
                ->map(fn ($group) => $group->map(fn ($skill) => ['id' => $skill->id, 'name' => $skill->name])->values());

            // Skills scoped to each service, falling back to the full list when a service has none defined yet.
            $skillsByService = $services->mapWithKeys(function ($service) use ($allSkillsByCategory) {
                $grouped = $service->skills->isEmpty()
                    ? $allSkillsByCategory
                    : $service->skills->groupBy(fn ($skill) => $skill->category->name ?? 'Other')
                        ->map(fn ($group) => $group->map(fn ($skill) => ['id' => $skill->id, 'name' => $skill->name])->sortBy('name')->values());

                return [$service->id => $grouped];
            });

            return compact('services', 'skillsByService');
        });

        $selectedServiceId = $services->firstWhere('slug', $request->query('service'))?->id;

        $uploadRules = app(SecureUploader::class)->profile('quote_reference');
        $uploadAccept = app(SecureUploader::class)->acceptAttribute('quote_reference');

        return view('quote-requests.create', compact('services', 'selectedServiceId', 'skillsByService', 'uploadRules', 'uploadAccept'));
    }

    public function store(Request $request, SecureUploader $uploader): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'guest_name' => [$user ? 'nullable' : 'required', 'string', 'max:255'],
            'guest_email' => [$user ? 'nullable' : 'required', 'email:rfc,dns', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:50'],
            'skills' => ['array'],
            'skills.*' => ['exists:skills,id'],
            ...SafeUpload::rulesFor('attachments', 'quote_reference'),
            'website' => ['prohibited'], // honeypot, see components/honeypot
        ]);

        $quoteRequest = QuoteRequest::create([
            'service_id' => $data['service_id'],
            'client_id' => $user?->id,
            'guest_name' => $user ? null : $data['guest_name'],
            'guest_email' => $user ? null : $data['guest_email'],
            'guest_phone' => $data['guest_phone'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => 'pending',
        ]);

        if (! empty($data['skills'])) {
            $quoteRequest->skills()->sync($data['skills']);
        }

        foreach ($request->file('attachments', []) as $file) {
            $quoteRequest->attachments()->create($uploader->store($file, 'quote-references'));
        }

        // So that if a guest registers/logs in from the thank-you page, they land
        // straight back on this request instead of the generic dashboard.
        session(['url.intended' => route('quote-requests.show', $quoteRequest)]);

        return redirect()->route('quote-requests.thanks')->with([
            'quote_title' => $quoteRequest->title,
            'quote_id' => $quoteRequest->id,
            'quote_email' => $user->email ?? $data['guest_email'] ?? null,
            'is_guest' => ! $user,
        ]);
    }

    public function thanks(): View
    {
        return view('quote-requests.thanks');
    }

    public function show(QuoteRequest $quoteRequest): View
    {
        $this->authorizeView($quoteRequest);

        $quoteRequest->load(['service.skills', 'client', 'assignedSpecialist', 'attachments', 'quotations.proposedBy', 'quotations.specialist', 'quotations.project', 'project', 'skills']);

        [$candidateSpecialists, $skillMatched, $specialistEscalatedTo] = $this->candidatesForQuoteRequest('quote', $quoteRequest);
        [$candidateExperts, $expertSkillMatched, $expertEscalatedTo] = $this->candidatesForQuoteRequest('lead', $quoteRequest);

        return view('quote-requests.show', compact(
            'quoteRequest', 'candidateSpecialists', 'skillMatched', 'specialistEscalatedTo',
            'candidateExperts', 'expertSkillMatched', 'expertEscalatedTo'
        ));
    }

    /**
     * Attachments are private files — only people who can see the request
     * itself can open them.
     */
    public function attachment(QuoteRequest $quoteRequest, QuoteRequestAttachment $attachment): StreamedResponse
    {
        abort_unless($attachment->quote_request_id === $quoteRequest->id, 404);
        $this->authorizeView($quoteRequest);

        return $this->streamStoredFile($attachment->disk, $attachment->path, $attachment->original_name, $attachment->mime_type);
    }

    /**
     * CEO/admin assigns a specialist to review + quote the request.
     */
    public function assign(Request $request, QuoteRequest $quoteRequest): RedirectResponse
    {
        $data = $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
        ]);

        $specialist = User::findOrFail($data['assigned_to']);
        abort_unless($specialist->is_active && ($specialist->can('send quotation') || $specialist->can('manage all work')), 422, "That person can't price quote requests.");

        $quoteRequest->update(['assigned_to' => $specialist->id]);

        return back()->with('status', "Assigned to {$specialist->name}.");
    }

    private function authorizeView(QuoteRequest $quoteRequest): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        if ($user->can('manage all work')) {
            return;
        }

        if ($quoteRequest->client_id === $user->id || $quoteRequest->assigned_to === $user->id) {
            return;
        }

        abort(403);
    }
}
