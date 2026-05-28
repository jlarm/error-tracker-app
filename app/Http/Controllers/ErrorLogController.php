<?php

namespace App\Http\Controllers;

use App\Models\ErrorLog;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ErrorLogController extends Controller
{
    public function show(ErrorLog $errorLog): Response
    {
        $errorLog->load('project:id,name');

        return Inertia::render('Errors/Show', [
            'errorLog' => [
                'id' => $errorLog->id,
                'project' => [
                    'id' => $errorLog->project->id,
                    'name' => $errorLog->project->name,
                ],
                'exception_class' => $errorLog->exception_class,
                'message' => $errorLog->message,
                'file' => $errorLog->file,
                'line' => $errorLog->line,
                'url' => $errorLog->url,
                'environment' => $errorLog->environment,
                'level' => $errorLog->level,
                'release' => $errorLog->release,
                'stack_trace' => $errorLog->stack_trace,
                'request_payload' => $errorLog->request_payload,
                'breadcrumbs' => $errorLog->breadcrumbs,
                'tags' => $errorLog->tags,
                'context' => $errorLog->context,
                'occurrences' => $errorLog->occurrences,
                'fingerprint' => $errorLog->fingerprint,
                'last_seen_at' => $errorLog->last_seen_at->toIso8601String(),
                'created_at' => $errorLog->created_at->toIso8601String(),
                'resolved_at' => $errorLog->resolved_at?->toIso8601String(),
            ],
        ]);
    }

    public function resolve(ErrorLog $errorLog): RedirectResponse
    {
        $errorLog->forceFill(['resolved_at' => now()])->save();

        return back()->with('flash.success', 'Issue marked as resolved.');
    }

    public function unresolve(ErrorLog $errorLog): RedirectResponse
    {
        $errorLog->forceFill(['resolved_at' => null])->save();

        return back()->with('flash.success', 'Issue reopened.');
    }
}
