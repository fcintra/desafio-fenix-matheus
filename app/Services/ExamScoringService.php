<?php

namespace App\Services;

use App\Models\Alternative;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Support\Facades\Redis;

class ExamScoringService
{
    public function process(User $user, Exam $exam, array $answers): Attempt
    {
        [$score, $percentage] = $this->calculateScore($exam, $answers);

        $attempt = Attempt::create([
            'user_id'    => $user->id,
            'exam_id'    => $exam->id,
            'score'      => $score,
            'percentage' => $percentage,
        ]);

        $this->updateRanking($user, $exam, $percentage);

        return $attempt->load('exam', 'user');
    }

    private function calculateScore(Exam $exam, array $answers): array
    {
        $correctMap = Alternative::query()
            ->whereHas('question', fn ($q) => $q->where('exam_id', $exam->id))
            ->where('is_correct', true)
            ->pluck('id', 'question_id');

        $totalQuestions = $correctMap->count();

        if ($totalQuestions === 0) {
            return [0, 0.0];
        }

        $score = 0;
        foreach ($correctMap as $questionId => $correctAlternativeId) {
            $submitted = isset($answers[$questionId]) ? (int) $answers[$questionId] : null;
            if ($submitted === $correctAlternativeId) {
                $score++;
            }
        }

        $percentage = round(($score / $totalQuestions) * 100, 2);

        return [$score, $percentage];
    }

    private function updateRanking(User $user, Exam $exam, float $percentage): void
    {
        Redis::zadd("exam:{$exam->id}:ranking", $percentage, $user->id);
        Redis::zincrby('student_ranking', $percentage, $user->id);
    }
}
