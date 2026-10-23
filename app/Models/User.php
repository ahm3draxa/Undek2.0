<?php

namespace App\Models;

use App\Support\Organization;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
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
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function hasEnabledTwoFactorAuthentication(): bool
    {
        return ! is_null($this->two_factor_confirmed_at);
    }

    /**
     * Removes a used recovery code so it can't be replayed.
     */
    public function consumeRecoveryCode(string $code): void
    {
        $codes = array_values(array_diff($this->two_factor_recovery_codes ?? [], [$code]));

        $this->forceFill(['two_factor_recovery_codes' => $codes])->save();
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

    /**
     * Additional positions held alongside the primary one on the employee profile.
     */
    public function secondaryPositions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'employee_positions')->withPivot('assigned_at')->withTimestamps();
    }

    /**
     * Every position currently held — primary (from the employee profile) plus secondary.
     * Queries fresh rather than trusting any already-loaded relations, since callers often
     * update the primary position moments before calling this in the same request.
     */
    public function allPositions(): \Illuminate\Support\Collection
    {
        $primaryPositionId = $this->employeeProfile()->value('position_id');
        $primary = $primaryPositionId ? Position::find($primaryPositionId) : null;

        return collect([$primary])->filter()->concat($this->secondaryPositions()->get())->unique('id')->values();
    }

    /**
     * Every department this person works in — their primary position's, plus
     * any secondary positions'.
     */
    public function departments(): \Illuminate\Support\Collection
    {
        return Department::whereIn('id', $this->allPositions()->pluck('department_id')->filter())->get();
    }

    /**
     * Recompute this user's direct permissions: exactly the team tools of
     * every department they hold a position in. Role permissions are separate
     * and untouched, so this is a full resync, not an additive grant.
     */
    public function syncTeamPermissions(): void
    {
        $permissionNames = $this->departments()
            ->load('permissions')
            ->flatMap(fn (Department $department) => $department->permissions->pluck('name'))
            ->unique()
            ->values()
            ->all();

        $this->syncPermissions($permissionNames);
    }

    public function isOwner(): bool
    {
        return $this->hasRole(Organization::OWNER);
    }

    /**
     * Whether this person may change another person's account — role,
     * password, email, 2FA, active status. Owner and admin accounts can only
     * be changed by an owner, so 'manage users' can never be used to take
     * over a more powerful account or to promote oneself.
     */
    public function canManageAccountOf(self $target): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        return $this->can('manage users')
            && $this->id !== $target->id
            && ! $target->hasAnyRole([Organization::OWNER, Organization::ADMIN]);
    }

    /**
     * Roles this person may hand out: owners any, everyone else only staff
     * and client.
     *
     * @return array<int, string>
     */
    public function assignableRoles(): array
    {
        return $this->isOwner()
            ? array_keys(Organization::ROLES)
            : [Organization::STAFF, Organization::CLIENT];
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(Organization::STAFF_ROLES);
    }

    /**
     * Interns (employment type) work on what they're given: they can't lead
     * projects or manage a project's milestones beyond the ones assigned to
     * them, whatever their team.
     */
    public function isIntern(): bool
    {
        return $this->employeeProfile?->employment_type === 'intern';
    }

    /**
     * How this person is described to others: their job title if they have
     * one, otherwise their role.
     */
    public function title(): string
    {
        return $this->employeeProfile?->position?->name
            ?? Organization::roleLabel($this->roles->first()?->name);
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
