<?php

use App\Models\ErrorLog;
use App\Models\Project;

test('rejects requests without an api key', function () {
    $response = $this->postJson('/api/errors', samplePayload());

    $response->assertUnauthorized();
});

test('rejects requests with an invalid api key', function () {
    $response = $this->withToken('not-a-real-key')
        ->postJson('/api/errors', samplePayload());

    $response->assertUnauthorized();
});

test('stores a new error log when the fingerprint is unseen', function () {
    $project = Project::factory()->create();

    $response = $this->withToken($project->api_key)
        ->postJson('/api/errors', samplePayload());

    $response->assertAccepted();

    expect($project->errorLogs()->count())->toBe(1);

    $log = $project->errorLogs()->first();
    expect($log->occurrences)->toBe(1)
        ->and($log->exception_class)->toBe('RuntimeException')
        ->and($log->fingerprint)->toBe(md5('RuntimeException/app/Http/Controllers/Foo.php42'));
});

test('increments occurrences when the fingerprint matches', function () {
    $project = Project::factory()->create();

    $this->withToken($project->api_key)
        ->postJson('/api/errors', samplePayload())
        ->assertAccepted();

    $this->withToken($project->api_key)
        ->postJson('/api/errors', samplePayload())
        ->assertAccepted();

    expect($project->errorLogs()->count())->toBe(1);
    expect($project->errorLogs()->first()->occurrences)->toBe(2);
});

test('validates required fields', function () {
    $project = Project::factory()->create();

    $response = $this->withToken($project->api_key)
        ->postJson('/api/errors', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['exception_class', 'message', 'file', 'line', 'stack_trace']);
});

test('stores rich event context when provided', function () {
    $project = Project::factory()->create();

    $payload = samplePayload();
    $payload['environment'] = 'production';
    $payload['level'] = 'error';
    $payload['release'] = 'v1.2.3';
    $payload['breadcrumbs'] = [
        ['category' => 'db.sql.query', 'level' => 'info', 'message' => 'select * from users'],
    ];
    $payload['tags'] = ['server_name' => 'web-01', 'runtime' => 'php'];
    $payload['context'] = [
        'runtime' => ['name' => 'php', 'version' => '8.5.0'],
        'user' => ['id' => 42, 'email' => 'test@example.com'],
    ];

    $this->withToken($project->api_key)
        ->postJson('/api/errors', $payload)
        ->assertAccepted();

    $log = $project->errorLogs()->first();
    expect($log->environment)->toBe('production')
        ->and($log->level)->toBe('error')
        ->and($log->release)->toBe('v1.2.3')
        ->and($log->breadcrumbs)->toHaveCount(1)
        ->and($log->tags['server_name'])->toBe('web-01')
        ->and($log->context['user']['email'])->toBe('test@example.com');
});

test('scopes fingerprints to the project', function () {
    $projectA = Project::factory()->create();
    $projectB = Project::factory()->create();

    $this->withToken($projectA->api_key)
        ->postJson('/api/errors', samplePayload())
        ->assertAccepted();

    $this->withToken($projectB->api_key)
        ->postJson('/api/errors', samplePayload())
        ->assertAccepted();

    expect(ErrorLog::count())->toBe(2);
});

function samplePayload(): array
{
    return [
        'exception_class' => 'RuntimeException',
        'message' => 'Something went wrong',
        'file' => '/app/Http/Controllers/Foo.php',
        'line' => 42,
        'url' => 'https://example.com/foo',
        'stack_trace' => [
            ['file' => '/app/Http/Controllers/Foo.php', 'line' => 42, 'function' => 'index'],
        ],
        'request_payload' => ['method' => 'GET', 'input' => []],
    ];
}
