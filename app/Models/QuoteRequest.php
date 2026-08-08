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
        'assigned_to',
    ];

    protected function casts(): array
    {
        return [
            'requirements' => 'array',
        ];
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

    public function quotation(): HasOne
    {
        return $this->hasOne(Quotation::class);
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
