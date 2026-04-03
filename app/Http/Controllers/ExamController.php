<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitExamRequest;
use App\Http\Resources\AttemptResource;
use App\Models\Exam;
use App\Services\ExamScoringService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Redis;

class ExamController extends Controller
{
    public function __construct(private readonly ExamScoringService $scoringService) {}

    public function submit(SubmitExamRequest $request, Exam $exam): AttemptResource
    {
        $attempt = $this->scoringService->process(
            user:    $request->user(),
            exam:    $exam,
            answers: $request->validated()['answers'],
        );

        return new AttemptResource($attempt);
    }

    public function ranking(Exam $exam): JsonResource
    {
        $raw = Redis::zrevrange("exam:{$exam->id}:ranking", 0, 9, ['withscores' => true]);

        $leaderboard = collect($raw)
            ->map(fn ($percentage, $userId) => [
                'user_id'    => (int) $userId,
                'percentage' => (float) $percentage,
            ])
            ->values();

        return new JsonResource([
            'exam'        => ['id' => $exam->id, 'title' => $exam->title],
            'leaderboard' => $leaderboard,
        ]);
    }

    public function globalRanking(): JsonResource
    {
        $raw = Redis::zrevrange('student_ranking', 0, 9, ['withscores' => true]);

        $leaderboard = collect($raw)
            ->map(fn ($totalScore, $userId) => [
                'user_id'     => (int) $userId,
                'total_score' => (float) $totalScore,
            ])
            ->values();

        return new JsonResource(['leaderboard' => $leaderboard]);
    }
}
