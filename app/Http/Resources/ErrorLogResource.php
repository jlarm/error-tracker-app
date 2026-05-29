<?php

namespace App\Http\Resources;

use App\Models\ErrorLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ErrorLog
 */
class ErrorLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'project_name' => $this->whenLoaded('project', fn () => $this->project->name),
            'exception_class' => $this->exception_class,
            'message' => $this->message,
            'file' => $this->file,
            'line' => $this->line,
            'url' => $this->url,
            'environment' => $this->environment,
            'level' => $this->level,
            'release' => $this->release,
            'occurrences' => $this->occurrences,
            'last_seen_at' => $this->last_seen_at->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
        ];
    }
}
