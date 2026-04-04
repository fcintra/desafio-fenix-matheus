<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers a new user and returns a token', function (): void {
    $this->postJson('/api/register', [
        'name'                  => 'João Silva',
        'email'                 => 'joao@exemplo.com',
        'password'              => 'senha1234',
        'password_confirmation' => 'senha1234',
    ])
        ->assertStatus(201)
        ->assertJsonStructure(['user', 'token']);
});

it('returns 422 when registering with an existing email', function (): void {
    User::factory()->create(['email' => 'joao@exemplo.com']);

    $this->postJson('/api/register', [
        'name'                  => 'João Silva',
        'email'                 => 'joao@exemplo.com',
        'password'              => 'senha1234',
        'password_confirmation' => 'senha1234',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('logs in with valid credentials and returns a token', function (): void {
    User::factory()->create([
        'email'    => 'joao@exemplo.com',
        'password' => bcrypt('senha1234'),
    ]);

    $this->postJson('/api/login', [
        'email'    => 'joao@exemplo.com',
        'password' => 'senha1234',
    ])
        ->assertOk()
        ->assertJsonStructure(['user', 'token']);
});

it('returns 401 when login credentials are invalid', function (): void {
    $this->postJson('/api/login', [
        'email'    => 'naoexiste@exemplo.com',
        'password' => 'errada',
    ])
        ->assertUnauthorized();
});

it('logs out and invalidates the token', function (): void {
    $user  = User::factory()->create();
    $token = $user->createToken('spa')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Logout realizado com sucesso.');
});

it('requires authentication to logout', function (): void {
    $this->postJson('/api/logout')
        ->assertUnauthorized();
});

it('returns the authenticated user on /me', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('email', $user->email);
});

it('requires authentication to access /me', function (): void {
    $this->getJson('/api/me')
        ->assertUnauthorized();
});
