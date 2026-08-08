<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
