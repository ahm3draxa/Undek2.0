<?php

namespace App\Models;

use App\Support\PublicCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'department_id',
        'intake_fields',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'intake_fields' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Public pages cache the service catalog — drop it so edits show immediately.
        // (Block bodies, not arrow functions: a listener returning false stops
        // the listeners after it.)
        static::saved(function () {
            PublicCache::flush();
        });
        static::deleted(function () {
            PublicCache::flush();
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The team that delivers this service.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function quoteRequests(): HasMany
    {
        return $this->hasMany(QuoteRequest::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'service_skill');
    }

    /**
     * Best-guess themed icon for a service name, so admins never have to pick one manually.
     */
    public static function suggestIcon(string $name): string
    {
        $name = strtolower($name);

        $map = [
            'code-bracket' => ['web', 'develop', 'app', 'site', 'software', 'engineer'],
            'magnifying-glass' => ['seo', 'search', 'rank', 'marketing'],
            'wrench-screwdriver' => ['support', 'it ', 'technical', 'maintenance', 'network', 'infrastructure'],
            'paint-brush' => ['design', 'graphic', 'brand', 'logo', 'ui', 'ux'],
            'film' => ['video', 'film', 'motion', 'animation', 'edit'],
            'currency-dollar' => ['finance', 'accounting', 'payroll'],
            'shield-check' => ['security', 'compliance', 'audit'],
            'users' => ['consult', 'hr', 'recruit', 'staffing'],
        ];

        foreach ($map as $icon => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($name, $keyword)) {
                    return $icon;
                }
            }
        }

        return 'sparkles';
    }
}
