<?php

use App\Models\ErrorLog;
use App\Models\Project;
use App\Models\User;

test('dashboard lists recent errors across projects', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Acme']);
    ErrorLog::factory()->for($project)->create([
        'exception_class' => 'RuntimeException',
        'message' => 'Boom',
        'last_seen_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page->component('Dashboard')
                ->where('status', 'unresolved')
                ->has('recentErrors', 1)
                ->where('recentErrors.0.exception_class', 'RuntimeException')
                ->where('recentErrors.0.project_name', 'Acme')
                ->where('counts.unresolved', 1)
                ->where('counts.resolved', 0)
                ->where('counts.all', 1),
        );
});

test('dashboard filters by resolved status', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $open = ErrorLog::factory()->for($project)->create(['resolved_at' => null]);
    $closed = ErrorLog::factory()->for($project)->create(['resolved_at' => now()]);

    $this->actingAs($user)
        ->get(route('dashboard', ['status' => 'resolved']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page->component('Dashboard')
                ->where('status', 'resolved')
                ->has('recentErrors', 1)
                ->where('recentErrors.0.id', $closed->id),
        );

    $this->actingAs($user)
        ->get(route('dashboard', ['status' => 'all']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page->component('Dashboard')
                ->where('status', 'all')
                ->has('recentErrors', 2),
        );

    expect($open)->not->toBeNull();
});

test('project page paginates errors sorted by last seen', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $older = ErrorLog::factory()->for($project)->create([
        'last_seen_at' => now()->subDay(),
    ]);
    $newer = ErrorLog::factory()->for($project)->create([
        'last_seen_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page->component('Projects/Show')
                ->where('project.id', $project->id)
                ->where('errors.data.0.id', $newer->id)
                ->where('errors.data.1.id', $older->id),
        );
});

test('authenticated user can create a project and gets an api key', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('projects.store'), ['name' => 'My New Site']);

    $project = Project::firstWhere('name', 'My New Site');

    expect($project)->not->toBeNull()
        ->and($project->api_key)->toBeString()
        ->and(strlen($project->api_key))->toBe(40);

    $response->assertRedirect(route('projects.show', $project));
});

test('project name is required and unique', function () {
    $user = User::factory()->create();
    Project::factory()->create(['name' => 'Taken']);

    $this->actingAs($user)
        ->post(route('projects.store'), ['name' => ''])
        ->assertSessionHasErrors('name');

    $this->actingAs($user)
        ->post(route('projects.store'), ['name' => 'Taken'])
        ->assertSessionHasErrors('name');
});

test('guests cannot create a project', function () {
    $this->post(route('projects.store'), ['name' => 'Sneaky'])
        ->assertRedirect(route('login'));

    expect(Project::where('name', 'Sneaky')->exists())->toBeFalse();
});

test('project page filters by resolved status', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $open = ErrorLog::factory()->for($project)->create(['resolved_at' => null]);
    $closed = ErrorLog::factory()->for($project)->create(['resolved_at' => now()]);

    $this->actingAs($user)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page->component('Projects/Show')
                ->where('status', 'unresolved')
                ->has('errors.data', 1)
                ->where('errors.data.0.id', $open->id)
                ->where('counts.unresolved', 1)
                ->where('counts.resolved', 1)
                ->where('counts.all', 2),
        );

    $this->actingAs($user)
        ->get(route('projects.show', ['project' => $project, 'status' => 'resolved']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page->component('Projects/Show')
                ->where('status', 'resolved')
                ->has('errors.data', 1)
                ->where('errors.data.0.id', $closed->id),
        );

    $this->actingAs($user)
        ->get(route('projects.show', ['project' => $project, 'status' => 'all']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page->component('Projects/Show')
                ->where('status', 'all')
                ->has('errors.data', 2),
        );
});

test('authenticated user can resolve and reopen an issue', function () {
    $user = User::factory()->create();
    $errorLog = ErrorLog::factory()->create(['resolved_at' => null]);

    $this->actingAs($user)
        ->post(route('errors.resolve', $errorLog))
        ->assertRedirect();

    expect($errorLog->fresh()->resolved_at)->not->toBeNull();

    $this->actingAs($user)
        ->post(route('errors.unresolve', $errorLog))
        ->assertRedirect();

    expect($errorLog->fresh()->resolved_at)->toBeNull();
});

test('a recurring error reopens a resolved issue', function () {
    $project = Project::factory()->create();
    $errorLog = ErrorLog::factory()->for($project)->create([
        'exception_class' => 'RuntimeException',
        'file' => '/app/Foo.php',
        'line' => 10,
        'fingerprint' => md5('RuntimeException/app/Foo.php10'),
        'occurrences' => 1,
        'resolved_at' => now()->subDay(),
    ]);

    $this->withToken($project->api_key)
        ->postJson('/api/errors', [
            'exception_class' => 'RuntimeException',
            'message' => 'It came back',
            'file' => '/app/Foo.php',
            'line' => 10,
            'stack_trace' => [['file' => '/app/Foo.php', 'line' => 10]],
        ])
        ->assertAccepted();

    expect($errorLog->fresh())
        ->resolved_at->toBeNull()
        ->occurrences->toBe(2);
});

test('error log show page returns the parsed payload', function () {
    $user = User::factory()->create();
    $errorLog = ErrorLog::factory()->create();

    $this->actingAs($user)
        ->get(route('errors.show', $errorLog))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page->component('Errors/Show')
                ->where('errorLog.id', $errorLog->id)
                ->has('errorLog.stack_trace')
                ->has('errorLog.request_payload'),
        );
});
