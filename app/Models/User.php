<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
        'avatar_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? \Illuminate\Support\Facades\Storage::url($this->avatar_path) : null;
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/', trim($this->name));
        $letters = array_map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)), array_slice($words, 0, 2));

        return implode('', $letters) ?: '?';
    }

    public function employeeProfile(): HasOne
    {
        return $this->hasOne(EmployeeProfile::class);
    }

    public function quoteRequests(): HasMany
    {
        return $this->hasMany(QuoteRequest::class, 'client_id');
    }

    public function quotationsGiven(): HasMany
    {
        return $this->hasMany(Quotation::class, 'specialist_id');
    }

    public function clientProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'client_id');
    }

    public function ledProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'lead_expert_id');
    }

    public function projectMemberships(): HasMany
    {
        return $this->hasMany(ProjectTeamMember::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(EmployeePromotion::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(EmployeeContribution::class);
    }

    public function payrollAdjustments(): HasMany
    {
        return $this->hasMany(PayrollAdjustment::class);
    }

    public function compensationIncrements(): HasMany
    {
        return $this->hasMany(CompensationIncrement::class)->latest('effective_date');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class)->latest();
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'employee_skills')->withPivot('level')->withTimestamps();
    }

    /**
     * The user this employee reports to, if any.
     */
    public function manager(): ?self
    {
        return $this->employeeProfile?->manager;
    }

    /**
     * Users who directly report to this one (one level of the org chart).
     */
    public function directReports()
    {
        return self::whereHas('employeeProfile', fn ($q) => $q->where('manager_id', $this->id));
    }
}
