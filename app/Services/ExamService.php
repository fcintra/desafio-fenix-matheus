<?php

namespace App\Services;

use App\Models\Exam;
use Illuminate\Support\Facades\DB;

class ExamService
{
    public function create(array $data): Exam
    {
        return DB::transaction(function () use ($data): Exam {
            $exam = Exam::create([
                'title'       => $data['title'],
                'description' => $data['description'] ?? null,
            ]);

            foreach ($data['questions'] as $questionData) {
                $question = $exam->questions()->create(['text' => $questionData['text']]);

                foreach ($questionData['alternatives'] as $altData) {
                    $question->alternatives()->create([
                        'text'       => $altData['text'],
                        'is_correct' => $altData['is_correct'],
                    ]);
                }
            }

            return $exam->load('questions.alternatives');
        });
    }
}
