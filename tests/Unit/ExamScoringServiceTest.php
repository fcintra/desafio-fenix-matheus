<?php

use App\DTOs\SubmitExamDTO;
use App\Models\Alternative;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use App\Services\ExamScoringService;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    $this->service = new ExamScoringService();
    Redis::shouldReceive('zadd')->andReturn(1)->byDefault();
    Redis::shouldReceive('zincrby')->andReturn(1.0)->byDefault();
});

it('returns zero score when exam has no questions', function () {
    $user = User::factory()->create();
    $exam = Exam::factory()->create();

    $attempt = $this->service->process(new SubmitExamDTO($user, $exam, []));

    expect($attempt->score)->toBe(0)
        ->and((float) $attempt->percentage)->toBe(0.0);
});

it('calculates 100% when all answers are correct', function () {
    $user = User::factory()->create();
    $exam = Exam::factory()->create();
    $q1   = Question::factory()->create(['exam_id' => $exam->id]);
    $q2   = Question::factory()->create(['exam_id' => $exam->id]);

    $correct1 = Alternative::factory()->correct()->create(['question_id' => $q1->id]);
    Alternative::factory()->create(['question_id' => $q1->id]);

    $correct2 = Alternative::factory()->correct()->create(['question_id' => $q2->id]);
    Alternative::factory()->create(['question_id' => $q2->id]);

    $attempt = $this->service->process(new SubmitExamDTO($user, $exam, [
        $q1->id => $correct1->id,
        $q2->id => $correct2->id,
    ]));

    expect($attempt->score)->toBe(2)
        ->and((float) $attempt->percentage)->toBe(100.0);
});

it('calculates 0% when no answers are correct', function () {
    $user = User::factory()->create();
    $exam = Exam::factory()->create();
    $q    = Question::factory()->create(['exam_id' => $exam->id]);

    Alternative::factory()->correct()->create(['question_id' => $q->id]);
    $wrong = Alternative::factory()->create(['question_id' => $q->id]);

    $attempt = $this->service->process(new SubmitExamDTO($user, $exam, [$q->id => $wrong->id]));

    expect($attempt->score)->toBe(0)
        ->and((float) $attempt->percentage)->toBe(0.0);
});

it('calculates partial score correctly', function () {
    $user = User::factory()->create();
    $exam = Exam::factory()->create();

    $q1       = Question::factory()->create(['exam_id' => $exam->id]);
    $correct1 = Alternative::factory()->correct()->create(['question_id' => $q1->id]);
    Alternative::factory()->create(['question_id' => $q1->id]);

    $q2     = Question::factory()->create(['exam_id' => $exam->id]);
    Alternative::factory()->correct()->create(['question_id' => $q2->id]);
    $wrong2 = Alternative::factory()->create(['question_id' => $q2->id]);

    $attempt = $this->service->process(new SubmitExamDTO($user, $exam, [
        $q1->id => $correct1->id,
        $q2->id => $wrong2->id,
    ]));

    expect($attempt->score)->toBe(1)
        ->and((float) $attempt->percentage)->toBe(50.0);
});

it('ignores answers for questions not in the exam', function () {
    $user = User::factory()->create();
    $exam = Exam::factory()->create();
    $q    = Question::factory()->create(['exam_id' => $exam->id]);

    $correct = Alternative::factory()->correct()->create(['question_id' => $q->id]);

    $attempt = $this->service->process(new SubmitExamDTO($user, $exam, [
        $q->id => $correct->id,
        9999   => 8888,
    ]));

    expect($attempt->score)->toBe(1)
        ->and((float) $attempt->percentage)->toBe(100.0);
});

it('persists the attempt to the database', function () {
    $user = User::factory()->create();
    $exam = Exam::factory()->create();

    $this->service->process(new SubmitExamDTO($user, $exam, []));

    expect(\App\Models\Attempt::query()
        ->where('user_id', $user->id)
        ->where('exam_id', $exam->id)
        ->exists()
    )->toBeTrue();
});

it('loads exam and user relations on the returned attempt', function () {
    $user = User::factory()->create();
    $exam = Exam::factory()->create();

    $attempt = $this->service->process(new SubmitExamDTO($user, $exam, []));

    expect($attempt->relationLoaded('exam'))->toBeTrue()
        ->and($attempt->relationLoaded('user'))->toBeTrue();
});

it('updates the exam-specific ranking in Redis', function () {
    $user = User::factory()->create();
    $exam = Exam::factory()->create();
    $q    = Question::factory()->create(['exam_id' => $exam->id]);

    $correct = Alternative::factory()->correct()->create(['question_id' => $q->id]);

    Redis::shouldReceive('zadd')
        ->once()
        ->with("exam:{$exam->id}:ranking", 100.0, $user->id)
        ->andReturn(1);

    Redis::shouldReceive('zincrby')
        ->once()
        ->with('student_ranking', 100.0, $user->id)
        ->andReturn(100.0);

    $this->service->process(new SubmitExamDTO($user, $exam, [$q->id => $correct->id]));
});
