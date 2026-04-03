<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validExamPayload(array $overrides = []): array
{
    return array_merge([
        'title'       => 'Prova de Laravel',
        'description' => 'Conceitos básicos',
        'questions'   => [
            [
                'text'         => 'O que é Eloquent?',
                'alternatives' => [
                    ['text' => 'ORM do Laravel', 'is_correct' => true],
                    ['text' => 'Um framework JS', 'is_correct' => false],
                ],
            ],
        ],
    ], $overrides);
}

it('requires authentication to create an exam', function (): void {
    $this->postJson('/api/exams', validExamPayload())
        ->assertUnauthorized();
});

it('creates an exam and returns 201 with the exam resource', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/exams', validExamPayload())
        ->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id', 'title', 'description', 'created_at',
                'questions' => [
                    ['id', 'text', 'alternatives' => [['id', 'text']]],
                ],
            ],
        ])
        ->assertJsonPath('data.title', 'Prova de Laravel');
});

it('persists the exam in the database', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/exams', validExamPayload());

    expect(\App\Models\Exam::where('title', 'Prova de Laravel')->exists())->toBeTrue();
});

it('returns 422 when title is missing', function (): void {
    $user = User::factory()->create();

    $payload = validExamPayload();
    unset($payload['title']);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/exams', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('title');
});

it('returns 422 when questions array is empty', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/exams', validExamPayload(['questions' => []]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('questions');
});

it('returns 422 when a question has no correct alternative', function (): void {
    $user = User::factory()->create();

    $payload = validExamPayload([
        'questions' => [
            [
                'text'         => 'Sem correta',
                'alternatives' => [
                    ['text' => 'A', 'is_correct' => false],
                    ['text' => 'B', 'is_correct' => false],
                ],
            ],
        ],
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/exams', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('questions.0.alternatives');
});

it('returns 422 when a question has more than one correct alternative', function (): void {
    $user = User::factory()->create();

    $payload = validExamPayload([
        'questions' => [
            [
                'text'         => 'Duas corretas',
                'alternatives' => [
                    ['text' => 'A', 'is_correct' => true],
                    ['text' => 'B', 'is_correct' => true],
                ],
            ],
        ],
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/exams', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('questions.0.alternatives');
});
