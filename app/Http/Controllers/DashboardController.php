<?php

namespace App\Http\Controllers;

use App\Models\ErrorLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $status = in_array($request->query('status'), ['resolved', 'all'], true)
            ? $request->query('status')
            : 'unresolved';

        $counts = [
            'unresolved' => ErrorLog::query()->whereNull('resolved_at')->count(),
            'resolved' => ErrorLog::query()->whereNotNull('resolved_at')->count(),
            'all' => ErrorLog::query()->count(),
        ];

        $query = ErrorLog::query()
            ->with('project:id,name')
            ->latest('last_seen_at');

        if ($status === 'unresolved') {
            $query->whereNull('resolved_at');
        } elseif ($status === 'resolved') {
            $query->whereNotNull('resolved_at');
        }

        $recentErrors = $query
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
            'status' => $status,
            'counts' => $counts,
        ]);
    }
}
