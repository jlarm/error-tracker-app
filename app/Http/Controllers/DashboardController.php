<?php

namespace App\Http\Controllers;

use App\Models\ErrorLog;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $recentErrors = ErrorLog::query()
            ->with('project:id,name')
            ->latest('last_seen_at')
            ->limit(15)
            ->get()
            ->map(fn (ErrorLog $log) => [
                'id' => $log->id,
                'project_id' => $log->project_id,
                'project_name' => $log->project->name,
                'exception_class' => $log->exception_class,
                'message' => $log->message,
                'file' => $log->file,
                'line' => $log->line,
                'url' => $log->url,
                'occurrences' => $log->occurrences,
                'last_seen_at' => $log->last_seen_at->toIso8601String(),
                'created_at' => $log->created_at->toIso8601String(),
                'resolved_at' => $log->resolved_at?->toIso8601String(),
            ]);

        return Inertia::render('Dashboard', [
            'recentErrors' => $recentErrors,
        ]);
    }
}
