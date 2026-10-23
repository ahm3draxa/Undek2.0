<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasPermissions;

/**
 * A team. Its permissions are the team's tools: everyone holding a position
 * in this department (primary or secondary) receives them.
 */
class Department extends Model
{
    use HasPermissions;

    protected $fillable = ['name', 'slug'];

    public function employeeProfiles(): HasMany
    {
        return $this->hasMany(EmployeeProfile::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class)->orderByDesc('rank');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Everyone in the team: primary position here, or a secondary one.
     */
    public function members()
    {
        return User::whereHas('employeeProfile.position', fn ($q) => $q->where('department_id', $this->id))
            ->orWhereHas('secondaryPositions', fn ($q) => $q->where('department_id', $this->id));
    }

    /**
     * Department isn't an authenticatable model, so Spatie can't infer a guard
     * from it — pin it to 'web' to match every permission in this app.
     */
    public function getDefaultGuardName(): string
    {
        return 'web';
    }

    protected function getGuardNames(): \Illuminate\Support\Collection
    {
        return collect(['web']);
    }
}
