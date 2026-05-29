<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendSlackErrorNotification;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ErrorIngestionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if (empty($token)) {
            throw new UnauthorizedHttpException('Bearer', 'Missing API key.');
        }

        $project = Project::where('api_key', $token)->first();

        if (! $project) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid API key.');
        }

        $data = $request->validate([
            'exception_class' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'file' => ['required', 'string', 'max:1024'],
            'line' => ['required', 'integer', 'min:1'],
            'url' => ['nullable', 'string', 'max:2048'],
            'environment' => ['nullable', 'string', 'max:64'],
            'level' => ['nullable', 'string', 'max:32'],
            'release' => ['nullable', 'string', 'max:255'],
            'stack_trace' => ['required', 'array'],
            'request_payload' => ['nullable', 'array'],
            'breadcrumbs' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'context' => ['nullable', 'array'],
        ]);

        $fingerprint = md5($data['exception_class'].$data['file'].$data['line']);

        $errorLog = $project->errorLogs()->where('fingerprint', $fingerprint)->first();

        $isNewIssue = false;
        $isRegression = false;

        if ($errorLog) {
            $isRegression = $errorLog->resolved_at !== null;

            $errorLog->increment('occurrences');
            $errorLog->forceFill([
                'last_seen_at' => now(),
                'resolved_at' => null,
                'message' => $data['message'],
                'url' => $data['url'] ?? $errorLog->url,
                'environment' => $data['environment'] ?? $errorLog->environment,
                'level' => $data['level'] ?? $errorLog->level,
                'release' => $data['release'] ?? $errorLog->release,
                'stack_trace' => $data['stack_trace'],
                'request_payload' => $data['request_payload'] ?? $errorLog->request_payload,
                'breadcrumbs' => $data['breadcrumbs'] ?? $errorLog->breadcrumbs,
                'tags' => $data['tags'] ?? $errorLog->tags,
                'context' => $data['context'] ?? $errorLog->context,
            ])->save();
        } else {
            $errorLog = $project->errorLogs()->create([
                'exception_class' => $data['exception_class'],
                'message' => $data['message'],
                'file' => $data['file'],
                'line' => $data['line'],
                'url' => $data['url'] ?? null,
                'environment' => $data['environment'] ?? null,
                'level' => $data['level'] ?? 'error',
                'release' => $data['release'] ?? null,
                'stack_trace' => $data['stack_trace'],
                'request_payload' => $data['request_payload'] ?? null,
                'breadcrumbs' => $data['breadcrumbs'] ?? null,
                'tags' => $data['tags'] ?? null,
                'context' => $data['context'] ?? null,
                'fingerprint' => $fingerprint,
                'occurrences' => 1,
                'last_seen_at' => now(),
            ]);
            $isNewIssue = true;
        }

        if ($isNewIssue || $isRegression) {
            SendSlackErrorNotification::dispatch($errorLog->id, $isRegression);
        }

        return response()->json([
            'id' => $errorLog->id,
            'occurrences' => $errorLog->occurrences,
        ], Response::HTTP_ACCEPTED);
    }
}
