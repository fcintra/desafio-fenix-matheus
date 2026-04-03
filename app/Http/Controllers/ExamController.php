<?php

namespace App\Http\Controllers;

use App\DTOs\CreateExamDTO;
use App\DTOs\SubmitExamDTO;
use App\Http\Requests\StoreExamRequest;
use App\Http\Requests\SubmitExamRequest;
use App\Http\Resources\AttemptResource;
use App\Http\Resources\ExamResource;
use App\Models\Attempt;
use App\Models\Exam;
use App\Services\ExamScoringService;
use App\Services\ExamService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class ExamController extends Controller
{
    public function __construct(
        private readonly ExamScoringService $scoringService,
        private readonly ExamService $examService,
    ) {}

    public function index(Request $request): JsonResource
    {
        $userId = $request->user()->id;

        $attemptedIds = Attempt::where('user_id', $userId)->pluck('exam_id');

        $exams = Exam::withCount('questions')->latest()->get()
            ->each(function (Exam $exam) use ($attemptedIds): void {
                $exam->has_attempted = $attemptedIds->contains($exam->id);
            });

        return ExamResource::collection($exams);
    }

    public function show(Exam $exam): ExamResource
    {
        $exam->load('questions.alternatives');

        return new ExamResource($exam);
    }

    public function store(StoreExamRequest $request): ExamResource
    {
        $exam = $this->examService->create(CreateExamDTO::fromArray($request->validated()));

        return new ExamResource($exam);
    }

    public function submit(SubmitExamRequest $request, Exam $exam): AttemptResource
    {
        $attempt = $this->scoringService->process(new SubmitExamDTO(
            user:    $request->user(),
            exam:    $exam,
            answers: $request->validated()['answers'],
        ));

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
