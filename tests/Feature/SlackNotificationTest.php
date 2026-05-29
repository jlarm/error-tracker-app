<?php

use App\Jobs\SendSlackErrorNotification;
use App\Models\ErrorLog;
use App\Models\Project;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'error-tracking.slack.enabled' => true,
        'error-tracking.slack.webhook_url' => 'https://hooks.slack.com/services/T0/B0/secret',
    ]);
});

function slackPayload(): array
{
    return [
        'exception_class' => 'RuntimeException',
        'message' => 'It broke',
        'file' => '/app/Service.php',
        'line' => 42,
        'environment' => 'production',
        'release' => 'v1.0.0',
        'url' => 'https://crm.armp.com/orders',
        'stack_trace' => [['file' => '/app/Service.php', 'line' => 42]],
    ];
}

test('a new issue dispatches a slack job', function () {
    Bus::fake();

    $project = Project::factory()->create();

    $this->withToken($project->api_key)
        ->postJson('/api/errors', slackPayload())
        ->assertAccepted();

    Bus::assertDispatched(
        SendSlackErrorNotification::class,
        fn (SendSlackErrorNotification $job) => $job->isRegression === false,
    );
});

test('a recurring (non-regressed) issue does not dispatch a job', function () {
    $project = Project::factory()->create();

    Bus::fake();

    $this->withToken($project->api_key)
        ->postJson('/api/errors', slackPayload())
        ->assertAccepted();
    Bus::assertDispatchedTimes(SendSlackErrorNotification::class, 1);

    $this->withToken($project->api_key)
        ->postJson('/api/errors', slackPayload())
        ->assertAccepted();
    Bus::assertDispatchedTimes(SendSlackErrorNotification::class, 1);
});

test('a regression dispatches a slack job marked as regression', function () {
    Bus::fake();

    $project = Project::factory()->create();
    ErrorLog::factory()->for($project)->create([
        'exception_class' => 'RuntimeException',
        'file' => '/app/Service.php',
        'line' => 42,
        'fingerprint' => md5('RuntimeException/app/Service.php42'),
        'resolved_at' => now()->subDay(),
    ]);

    $this->withToken($project->api_key)
        ->postJson('/api/errors', slackPayload())
        ->assertAccepted();

    Bus::assertDispatched(
        SendSlackErrorNotification::class,
        fn (SendSlackErrorNotification $job) => $job->isRegression === true,
    );
});

test('the slack toggle suppresses the job', function () {
    config(['error-tracking.slack.enabled' => false]);
    Http::fake();

    $project = Project::factory()->create();
    $errorLog = ErrorLog::factory()->for($project)->create();

    (new SendSlackErrorNotification($errorLog->id))->handle();

    Http::assertNothingSent();
});

test('the job posts a block kit payload to the configured webhook', function () {
    Http::fake([
        'hooks.slack.com/*' => Http::response('ok', 200),
    ]);

    $project = Project::factory()->create(['name' => 'CRM']);
    $errorLog = ErrorLog::factory()->for($project)->create([
        'exception_class' => 'Illuminate\\Database\\QueryException',
        'message' => 'Cannot connect to db',
        'file' => '/app/Foo.php',
        'line' => 99,
        'environment' => 'production',
        'release' => 'v1.0.0',
        'url' => 'https://crm.armp.com/orders',
    ]);

    (new SendSlackErrorNotification($errorLog->id))->handle();

    Http::assertSent(function ($request) {
        $body = $request->data();

        return $request->url() === 'https://hooks.slack.com/services/T0/B0/secret'
            && str_contains($body['text'], 'New error in CRM')
            && collect($body['blocks'])->contains(fn ($b) => $b['type'] === 'header')
            && collect($body['blocks'])->contains(fn ($b) => $b['type'] === 'actions');
    });
});

test('the job is a no-op when no webhook url is configured', function () {
    config(['error-tracking.slack.webhook_url' => null]);
    Http::fake();

    $project = Project::factory()->create();
    $errorLog = ErrorLog::factory()->for($project)->create();

    (new SendSlackErrorNotification($errorLog->id))->handle();

    Http::assertNothingSent();
});
