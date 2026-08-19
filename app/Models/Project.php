<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
