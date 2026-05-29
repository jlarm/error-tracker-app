<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorLogResource;
use App\Models\ErrorLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class ErrorLogController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:unresolved,resolved,all'],
            'project_id' => ['nullable', 'integer'],
            'since' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $status = $data['status'] ?? 'unresolved';
        $perPage = $data['per_page'] ?? 50;

        $query = ErrorLog::query()
            ->with('project:id,name')
            ->orderByDesc('last_seen_at');

        if ($status === 'unresolved') {
            $query->whereNull('resolved_at');
        } elseif ($status === 'resolved') {
            $query->whereNotNull('resolved_at');
        }

        if (! empty($data['project_id'])) {
            $query->where('project_id', $data['project_id']);
        }

        if (! empty($data['since'])) {
            $query->where('last_seen_at', '>', Carbon::parse($data['since']));
        }

        $paginated = $query->paginate($perPage);

        return ErrorLogResource::collection($paginated)->additional([
            'meta' => [
                'unresolved_count' => ErrorLog::query()->whereNull('resolved_at')->count(),
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }

    public function resolve(ErrorLog $errorLog): JsonResponse
    {
        $errorLog->forceFill(['resolved_at' => now()])->save();

        return response()->json([
            'data' => new ErrorLogResource($errorLog->load('project:id,name')),
        ]);
    }

    public function unresolve(ErrorLog $errorLog): JsonResponse
    {
        $errorLog->forceFill(['resolved_at' => null])->save();

        return response()->json([
            'data' => new ErrorLogResource($errorLog->load('project:id,name')),
        ]);
    }
}
