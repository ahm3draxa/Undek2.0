<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class QuoteRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'client_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'title',
        'description',
        'requirements',
        'status',
        'source',
        'assigned_to',
    ];

    protected function casts(): array
    {
        return [
            'requirements' => 'array',
        ];
    }

    /**
     * Hands guest-submitted requests made with this user's email over to their
     * account — only once that email is verified as theirs.
     */
    public static function claimGuestRequestsFor(User $user): void
    {
        if (! $user->hasVerifiedEmail()) {
            return;
        }

        static::whereNull('client_id')
            ->whereRaw('LOWER(guest_email) = ?', [strtolower($user->email)])
            ->update(['client_id' => $user->id]);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function assignedSpecialist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(QuoteRequestAttachment::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'quote_request_skill');
    }

    /**
     * Full negotiation history — a specialist's initial quote, any client
     * counter-proposals, and any revised specialist offers, oldest first.
     */
    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class)->orderBy('id');
    }

    /**
     * The current/most recent proposal on the table — the one either side
     * can currently act on (accept, reject, or counter once it's settled).
     */
    public function latestQuotation(): HasOne
    {
        return $this->hasOne(Quotation::class)->latestOfMany();
    }

    public function messages(): MorphMany
    {
        return $this->morphMany(Message::class, 'messageable')->with('user')->latest('id');
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class);
    }
}
