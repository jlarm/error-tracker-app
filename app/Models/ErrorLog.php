<?php

namespace App\Models;

use Database\Factories\ErrorLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'project_id',
    'exception_class',
    'message',
    'file',
    'line',
    'url',
    'environment',
    'level',
    'release',
    'stack_trace',
    'request_payload',
    'breadcrumbs',
    'tags',
    'context',
    'fingerprint',
    'occurrences',
    'last_seen_at',
    'resolved_at',
])]
class ErrorLog extends Model
{
    /** @use HasFactory<ErrorLogFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stack_trace' => 'array',
            'request_payload' => 'array',
            'breadcrumbs' => 'array',
            'tags' => 'array',
            'context' => 'array',
            'last_seen_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
