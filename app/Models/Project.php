<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

class Project extends Model
{
    protected $fillable = [
        'quote_request_id',
        'quotation_id',
        'client_id',
        'lead_expert_id',
        'title',
        'description',
        'status',
        'start_date',
        'due_date',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * Lead, team member, or anyone who manages all work — everyone who sees internal detail
     * (team channel, task assignments, submitted work). Never the client.
     */
    public function hasInternalAccess(User $user): bool
    {
        return $user->can('manage all work')
            || $this->lead_expert_id === $user->id
            || $this->teamMembers()->where('user_id', $user->id)->exists();
    }

    /**
     * People this user may hand a milestone to on this project. Owners/admins and
     * the lead can delegate to anyone on the project; anyone else on the
     * project only to the people they directly supervise (e.g. their own
     * interns), per the org chart's manager field.
     */
    public function assignableBy(User $user): Collection
    {
        $this->loadMissing('leadExpert', 'teamMembers.user.employeeProfile');

        $people = collect([$this->leadExpert])->filter()
            ->concat($this->teamMembers->pluck('user')->filter())
            ->unique('id')
            ->values();

        if ($user->can('manage all work') || $this->lead_expert_id === $user->id) {
            return $people;
        }

        if (! $people->contains('id', $user->id)) {
            return collect();
        }

        return $people->filter(fn (User $person) => $person->employeeProfile?->manager_id === $user->id)->values();
    }

    public function quoteRequest(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function leadExpert(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_expert_id');
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(ProjectTeamMember::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('sort_order');
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(EmployeeContribution::class);
    }

    public function messages(): MorphMany
    {
        return $this->morphMany(Message::class, 'messageable')->with('user')->latest('id');
    }

    /**
     * The client-visible thread — same audience as before the team channel existed.
     */
    public function clientMessages(): MorphMany
    {
        return $this->messages()->where('channel', 'client');
    }

    /**
     * Internal-only thread for staff to coordinate — never shown to the client.
     */
    public function teamMessages(): MorphMany
    {
        return $this->messages()->where('channel', 'team');
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(ProjectFeedback::class);
    }

    public function clientFeedback(): ?ProjectFeedback
    {
        return $this->feedbacks->firstWhere('audience', ProjectFeedback::CLIENT_TO_UNDEK);
    }

    public function undekFeedback(): ?ProjectFeedback
    {
        return $this->feedbacks->firstWhere('audience', ProjectFeedback::UNDEK_TO_CLIENT);
    }
}
