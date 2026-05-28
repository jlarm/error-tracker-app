<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'api_key'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Project $project) {
            if (empty($project->api_key)) {
                $project->api_key = Str::random(40);
            }
        });
    }

    /**
     * @return HasMany<ErrorLog, $this>
     */
    public function errorLogs(): HasMany
    {
        return $this->hasMany(ErrorLog::class);
    }
}
