<?php

namespace App\Models;

use App\Support\PublicCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'category_id',
    ];

    protected static function booted(): void
    {
        // The public quote form caches the skill list.
        static::saved(function () {
            PublicCache::flush();
        });
        static::deleted(function () {
            PublicCache::flush();
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SkillCategory::class, 'category_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'employee_skills')->withPivot('level')->withTimestamps();
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_skill');
    }
}
