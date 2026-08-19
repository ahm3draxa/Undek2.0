<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasPermissions;

class Position extends Model
{
    use HasPermissions;

    protected $fillable = ['name', 'slug', 'department_id', 'rank'];

    public function employeeProfiles(): HasMany
    {
        return $this->hasMany(EmployeeProfile::class);
    }

    /**
     * Position isn't an authenticatable model, so Spatie can't infer a guard from it by
     * checking auth.guards providers — pin it to 'web' to match every permission in this app.
     */
    public function getDefaultGuardName(): string
    {
        return 'web';
    }

    protected function getGuardNames(): \Illuminate\Support\Collection
    {
        return collect(['web']);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Users holding this as a secondary position (see User::secondaryPositions()).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'employee_positions')->withPivot('assigned_at')->withTimestamps();
    }

    /**
     * Skills expected of whoever holds this position — the link between the org chart
     * (department/position/rank) and the work-matching side (skills on quotes/services).
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'position_skill');
    }

    /**
     * Other positions in the same department's rank ladder, senior-to-junior.
     */
    public function departmentLadder(): Collection
    {
        return static::where('department_id', $this->department_id)->orderByDesc('rank')->get();
    }

    public function nextSeniorInDepartment(): ?self
    {
        return static::where('department_id', $this->department_id)
            ->where('rank', '>', $this->rank)
            ->orderBy('rank')
            ->first();
    }

    public function nextJuniorInDepartment(): ?self
    {
        return static::where('department_id', $this->department_id)
            ->where('rank', '<', $this->rank)
            ->orderByDesc('rank')
            ->first();
    }
}
