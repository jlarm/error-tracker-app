<?php

namespace App\Http\Controllers;

use App\Models\ErrorLog;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:projects,name'],
        ]);

        $project = Project::create($data);

        return to_route('projects.show', $project)
            ->with('flash.success', "Project \"{$project->name}\" created.");
    }

    public function show(Request $request, Project $project): Response
    {
        $status = in_array($request->query('status'), ['resolved', 'all'], true)
            ? $request->query('status')
            : 'unresolved';

        $baseQuery = $project->errorLogs();

        $counts = [
            'unresolved' => (clone $baseQuery)->whereNull('resolved_at')->count(),
            'resolved' => (clone $baseQuery)->whereNotNull('resolved_at')->count(),
            'all' => (clone $baseQuery)->count(),
        ];

        $query = $baseQuery->orderByDesc('last_seen_at');
        if ($status === 'unresolved') {
            $query->whereNull('resolved_at');
        } elseif ($status === 'resolved') {
            $query->whereNotNull('resolved_at');
        }

        $errors = $query
            ->paginate(25)
            ->withQueryString()
            ->through(fn (ErrorLog $log) => [
                'id' => $log->id,
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

        return Inertia::render('Projects/Show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'api_key' => $project->api_key,
            ],
            'errors' => $errors,
            'status' => $status,
            'counts' => $counts,
        ]);
    }
}
