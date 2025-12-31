<?php

use App\Models\User;
use function Pest\Laravel\{postJson, actingAs};

test('user can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ])->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['id', 'email', 'token']
        ]);
});

test('user cannot login with invalid credentials', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(401);
});

test('user cannot login with non-existent email', function () {
    postJson('/api/v1/login', [
        'email' => 'nobody@example.com',
        'password' => 'password',
    ])->assertStatus(401);
});

test('authenticated user can access protected route', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);
});
