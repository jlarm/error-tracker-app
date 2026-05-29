<?php

use App\Models\ErrorLog;
use App\Models\Project;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('requires a sanctum token', function () {
    $this->getJson('/api/v1/errors')->assertUnauthorized();
});

test('lists unresolved errors by default with newest first', function () {
    Sanctum::actingAs(User::factory()->create());

    $project = Project::factory()->create();
    $old = ErrorLog::factory()->for($project)->create(['last_seen_at' => now()->subHour()]);
    $new = ErrorLog::factory()->for($project)->create(['last_seen_at' => now()]);
    $resolved = ErrorLog::factory()->for($project)->create(['resolved_at' => now(), 'last_seen_at' => now()]);

    $response = $this->getJson('/api/v1/errors')->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->all();

    expect($ids)->toBe([$new->id, $old->id]);
    expect($response->json('meta.unresolved_count'))->toBe(2);
    expect($response->json('meta.server_time'))->toBeString();
    expect($response->json('data.0'))
        ->toHaveKeys(['id', 'project_id', 'project_name', 'last_seen_at']);
});

test('filters by status=resolved', function () {
    Sanctum::actingAs(User::factory()->create());

    $project = Project::factory()->create();
    ErrorLog::factory()->for($project)->create();
    $resolved = ErrorLog::factory()->for($project)->create(['resolved_at' => now()]);

    $response = $this->getJson('/api/v1/errors?status=resolved')->assertOk();

    expect(collect($response->json('data'))->pluck('id')->all())->toBe([$resolved->id]);
});

test('filters by since', function () {
    Sanctum::actingAs(User::factory()->create());

    $project = Project::factory()->create();
    ErrorLog::factory()->for($project)->create(['last_seen_at' => now()->subHour()]);
    $fresh = ErrorLog::factory()->for($project)->create(['last_seen_at' => now()]);

    $since = now()->subMinutes(10)->toIso8601String();
    $response = $this->getJson('/api/v1/errors?since='.urlencode($since))->assertOk();

    expect(collect($response->json('data'))->pluck('id')->all())->toBe([$fresh->id]);
});

test('filters by project_id', function () {
    Sanctum::actingAs(User::factory()->create());

    $a = Project::factory()->create();
    $b = Project::factory()->create();
    $errorA = ErrorLog::factory()->for($a)->create();
    ErrorLog::factory()->for($b)->create();

    $response = $this->getJson('/api/v1/errors?project_id='.$a->id)->assertOk();

    expect(collect($response->json('data'))->pluck('id')->all())->toBe([$errorA->id]);
});

test('resolve marks the error resolved', function () {
    Sanctum::actingAs(User::factory()->create());

    $error = ErrorLog::factory()->for(Project::factory())->create(['resolved_at' => null]);

    $this->postJson("/api/v1/errors/{$error->id}/resolve")
        ->assertOk()
        ->assertJsonPath('data.id', $error->id)
        ->assertJsonPath('data.resolved_at', fn ($v) => $v !== null);

    expect($error->fresh()->resolved_at)->not->toBeNull();
});

test('unresolve clears resolved_at', function () {
    Sanctum::actingAs(User::factory()->create());

    $error = ErrorLog::factory()->for(Project::factory())->create(['resolved_at' => now()]);

    $this->postJson("/api/v1/errors/{$error->id}/unresolve")
        ->assertOk()
        ->assertJsonPath('data.resolved_at', null);

    expect($error->fresh()->resolved_at)->toBeNull();
});

test('per_page is capped at 100', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/errors?per_page=500')->assertUnprocessable();
});
