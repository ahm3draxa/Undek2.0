<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersBySkill;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuoteRequestController extends Controller
{
    use FiltersBySkill;

    /**
     * Admin/specialist queue of incoming quote requests.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = QuoteRequest::with(['service', 'client', 'assignedSpecialist'])->latest();

        if ($user->hasAnyRole(['ceo', 'admin'])) {
            // Full visibility — no filter.
        } elseif ($user->can('view quote requests')) {
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
        $services = Service::where('is_active', true)->with('skills.category')->orderBy('name')->get();
        $selectedServiceId = $services->firstWhere('slug', $request->query('service'))?->id;

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

        return view('quote-requests.create', compact('services', 'selectedServiceId', 'skillsByService'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'guest_name' => [$user ? 'nullable' : 'required', 'string', 'max:255'],
            'guest_email' => [$user ? 'nullable' : 'required', 'email', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:50'],
            'skills' => ['array'],
            'skills.*' => ['exists:skills,id'],
            'reference_images' => ['array', 'max:5'],
            'reference_images.*' => ['image', 'max:5120'],
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

        foreach ($request->file('reference_images', []) as $image) {
            $path = $image->store('quote-references', 'public');

            $quoteRequest->attachments()->create([
                'path' => $path,
                'original_name' => $image->getClientOriginalName(),
                'mime_type' => $image->getClientMimeType(),
                'size' => $image->getSize(),
            ]);
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

        [$candidateSpecialists, $skillMatched, $specialistEscalatedTo] = $this->candidatesForQuoteRequest(['specialist'], $quoteRequest);
        [$candidateExperts, $expertSkillMatched, $expertEscalatedTo] = $this->candidatesForQuoteRequest(['expert', 'employee'], $quoteRequest);

        return view('quote-requests.show', compact(
            'quoteRequest', 'candidateSpecialists', 'skillMatched', 'specialistEscalatedTo',
            'candidateExperts', 'expertSkillMatched', 'expertEscalatedTo'
        ));
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
        abort_unless($specialist->hasAnyRole(['specialist', 'ceo', 'admin']), 422, 'Selected user is not a specialist.');

        $quoteRequest->update(['assigned_to' => $specialist->id]);

        return back()->with('status', "Assigned to {$specialist->name}.");
    }

    private function authorizeView(QuoteRequest $quoteRequest): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        if ($user->hasAnyRole(['ceo', 'admin'])) {
            return;
        }

        if ($quoteRequest->client_id === $user->id || $quoteRequest->assigned_to === $user->id) {
            return;
        }

        abort(403);
    }
}
