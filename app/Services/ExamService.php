<?php

namespace App\Services;

use App\DTOs\CreateExamDTO;
use App\Models\Exam;
use Illuminate\Support\Facades\DB;

class ExamService
{
    public function create(CreateExamDTO $dto): Exam
    {
        return DB::transaction(function () use ($dto): Exam {
            $exam = Exam::create([
                'title'       => $dto->title,
                'description' => $dto->description,
            ]);

            foreach ($dto->questions as $questionDTO) {
                $question = $exam->questions()->create(['text' => $questionDTO->text]);

                foreach ($questionDTO->alternatives as $alternativeDTO) {
                    $question->alternatives()->create([
                        'text'       => $alternativeDTO->text,
                        'is_correct' => $alternativeDTO->isCorrect,
                    ]);
                }
            }

            return $exam->load('questions.alternatives');
        });
    }
}
