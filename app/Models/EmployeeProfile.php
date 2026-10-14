<?php

namespace App\Models;

use App\Http\Controllers\HomeController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class EmployeeProfile extends Model
{
    protected $fillable = [
        'user_id',
        'manager_id',
        'position_id',
        'department_id',
        'employment_type',
        'hire_date',
        'end_date',
        'base_salary',
        'compensation_type',
        'hourly_rate',
        'fixed_project_price',
        'project_percentage',
        'equity_shares',
        'bio',
        'is_public',
    ];

    public const COMPENSATION_TYPES = [
        'fixed_salary' => 'Fixed salary',
        'hourly' => 'Hourly rate',
        'fixed_project_price' => 'Fixed price per project',
        'project_percentage' => 'Percentage of project value',
    ];

    /**
     * The employee_profiles column that holds the rate for the active compensation type.
     */
    public function compensationField(): string
    {
        return match ($this->compensation_type) {
            'hourly' => 'hourly_rate',
            'fixed_project_price' => 'fixed_project_price',
            'project_percentage' => 'project_percentage',
            default => 'base_salary',
        };
    }

    public function compensationValue(): ?float
    {
        $value = $this->{$this->compensationField()};

        return $value !== null ? (float) $value : null;
    }

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'end_date' => 'date',
            'is_public' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    protected static function booted(): void
    {
        // department_id must never drift from the assigned position's department — the
        // position is the single source of truth once one is set. This runs at the model
        // layer (not just in controllers) so no future write path can reintroduce the split
        // that let department and position disagree with each other.
        static::saving(function (self $profile) {
            if ($profile->position_id && $profile->isDirty('position_id')) {
                $profile->department_id = Position::find($profile->position_id)?->department_id ?? $profile->department_id;
            }
        });

        // The landing page caches the public team tree — drop it so visibility,
        // position and manager changes show immediately.
        static::saved(fn () => Cache::forget(HomeController::CACHE_KEY));
        static::deleted(fn () => Cache::forget(HomeController::CACHE_KEY));
    }
}
