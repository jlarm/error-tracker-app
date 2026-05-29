<?php

use App\Models\User;

test('user can create an api token and is shown the plain text once', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('api-tokens.store'), ['name' => 'MacBook menu bar']);

    $response->assertRedirect();

    expect($user->tokens()->count())->toBe(1);
    expect($user->tokens()->first()->name)->toBe('MacBook menu bar');
    expect($response->getSession()->get('flash.api_token'))->toBeString();
});

test('user can revoke an api token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->accessToken;

    $this->actingAs($user)
        ->delete(route('api-tokens.destroy', ['tokenId' => $token->id]))
        ->assertRedirect();

    expect($user->tokens()->count())->toBe(0);
});

test('cannot revoke another users token', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $token = $owner->createToken('owned')->accessToken;

    $this->actingAs($other)
        ->delete(route('api-tokens.destroy', ['tokenId' => $token->id]));

    expect($owner->tokens()->count())->toBe(1);
});

test('token name is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('api-tokens.store'), ['name' => ''])
        ->assertSessionHasErrors('name');
});
