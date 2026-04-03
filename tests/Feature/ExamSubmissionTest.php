<?php

use App\Models\Alternative;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\Redis;

function makeExamWithQuestion(): array
{
    $exam    = Exam::factory()->create();
    $q       = Question::factory()->create(['exam_id' => $exam->id]);
    $correct = Alternative::factory()->correct()->create(['question_id' => $q->id]);
    $wrong   = Alternative::factory()->create(['question_id' => $q->id]);

    return [$exam, $q, $correct, $wrong];
}

it('requires authentication to submit an exam', function () {
    $exam = Exam::factory()->create();

    $this->postJson("/api/exams/{$exam->id}/submit", ['answers' => []])
        ->assertUnauthorized();
});

it('submits an exam and returns 201 with the attempt resource', function () {
    Redis::shouldReceive('zadd')->once()->andReturn(1);
    Redis::shouldReceive('zincrby')->once()->andReturn(1.0);

    [$exam, $q, $correct] = makeExamWithQuestion();
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/exams/{$exam->id}/submit", [
            'answers' => [$q->id => $correct->id],
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id', 'score', 'percentage', 'passed',
                'exam'      => ['id', 'title'],
                'student'   => ['id', 'name'],
                'submitted_at',
            ],
        ])
        ->assertJsonPath('data.score', 1)
        ->assertJson(fn ($json) => $json
            ->where('data.percentage', fn ($v) => (float) $v === 100.0)
            ->etc()
        )
        ->assertJsonPath('data.passed', true);
});

it('stores the attempt in the database after submission', function () {
    Redis::shouldReceive('zadd')->once()->andReturn(1);
    Redis::shouldReceive('zincrby')->once()->andReturn(1.0);

    [$exam, $q, $correct] = makeExamWithQuestion();
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/exams/{$exam->id}/submit", [
            'answers' => [$q->id => $correct->id],
        ]);

    expect(
        Attempt::where('user_id', $user->id)->where('exam_id', $exam->id)->exists()
    )->toBeTrue();
});

it('returns 422 when the user has already attempted the exam', function () {
    Redis::shouldReceive('zadd')->andReturn(1)->byDefault();
    Redis::shouldReceive('zincrby')->andReturn(1.0)->byDefault();

    [$exam, $q, $correct] = makeExamWithQuestion();
    $user = User::factory()->create();

    Attempt::factory()->create([
        'user_id'    => $user->id,
        'exam_id'    => $exam->id,
        'score'      => 1,
        'percentage' => 100.0,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/exams/{$exam->id}/submit", [
            'answers' => [$q->id => $correct->id],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('exam');
});

it('returns 422 when answers field is missing', function () {
    $exam = Exam::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/exams/{$exam->id}/submit", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('answers');
});

it('returns 422 when answers array is empty', function () {
    $exam = Exam::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/exams/{$exam->id}/submit", ['answers' => []])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('answers');
});

it('returns 422 when an alternative id does not exist', function () {
    $exam = Exam::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/exams/{$exam->id}/submit", ['answers' => [1 => 99999]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('answers.1');
});

it('requires authentication to view the exam ranking', function () {
    $exam = Exam::factory()->create();

    $this->getJson("/api/exams/{$exam->id}/ranking")
        ->assertUnauthorized();
});

it('returns the exam ranking leaderboard', function () {
    $exam = Exam::factory()->create();
    $user = User::factory()->create();

    Redis::shouldReceive('zrevrange')
        ->once()
        ->with("exam:{$exam->id}:ranking", 0, 9, ['withscores' => true])
        ->andReturn([(string) $user->id => '85.50']);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/exams/{$exam->id}/ranking")
        ->assertOk()
        ->assertJsonPath('data.exam.id', $exam->id)
        ->assertJsonPath('data.leaderboard.0.user_id', $user->id)
        ->assertJsonPath('data.leaderboard.0.percentage', 85.5);
});

it('returns an empty leaderboard when no attempts exist', function () {
    $exam = Exam::factory()->create();
    $user = User::factory()->create();

    Redis::shouldReceive('zrevrange')
        ->once()
        ->andReturn([]);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/exams/{$exam->id}/ranking")
        ->assertOk()
        ->assertJsonPath('data.leaderboard', []);
});

it('requires authentication to view the global ranking', function () {
    $this->getJson('/api/ranking')
        ->assertUnauthorized();
});

it('returns the global ranking leaderboard', function () {
    $user = User::factory()->create();

    Redis::shouldReceive('zrevrange')
        ->once()
        ->with('student_ranking', 0, 9, ['withscores' => true])
        ->andReturn([(string) $user->id => '250.00']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/ranking')
        ->assertOk()
        ->assertJsonPath('data.leaderboard.0.user_id', $user->id)
        ->assertJson(fn ($json) => $json
            ->where('data.leaderboard.0.total_score', fn ($v) => (float) $v === 250.0)
            ->etc()
        );
});
