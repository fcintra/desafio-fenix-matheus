<?php

use App\Models\Alternative;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication to list exams', function (): void {
    $this->getJson('/api/exams')
        ->assertUnauthorized();
});

it('returns a list of exams with question count', function (): void {
    $user = User::factory()->create();
    $exam = Exam::factory()->create(['title' => 'Exame de PHP']);
    Question::factory()->count(3)->create(['exam_id' => $exam->id]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/exams')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'title', 'question_count', 'has_attempted', 'created_at']],
        ])
        ->assertJsonPath('data.0.question_count', 3)
        ->assertJsonPath('data.0.has_attempted', false);
});

it('marks exam as attempted when user has a previous attempt', function (): void {
    $user = User::factory()->create();
    $exam = Exam::factory()->create();

    Attempt::factory()->create(['user_id' => $user->id, 'exam_id' => $exam->id]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/exams')
        ->assertOk()
        ->assertJsonPath('data.0.has_attempted', true);
});

it('requires authentication to show an exam', function (): void {
    $exam = Exam::factory()->create();

    $this->getJson("/api/exams/{$exam->id}")
        ->assertUnauthorized();
});

it('returns exam details with questions and alternatives', function (): void {
    $user  = User::factory()->create();
    $exam  = Exam::factory()->create(['title' => 'Exame de Laravel']);
    $q     = Question::factory()->create(['exam_id' => $exam->id, 'text' => 'O que é Eloquent?']);
    Alternative::factory()->create(['question_id' => $q->id, 'text' => 'Um ORM']);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/exams/{$exam->id}")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id', 'title', 'created_at',
                'questions' => [['id', 'text', 'alternatives' => [['id', 'text']]]],
            ],
        ])
        ->assertJsonPath('data.title', 'Exame de Laravel');
});

it('returns 404 for a non-existent exam', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/exams/99999')
        ->assertNotFound();
});
