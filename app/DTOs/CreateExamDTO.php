<?php

namespace App\DTOs;

readonly class CreateExamDTO
{
    public function __construct(
        public string $title,
        public ?string $description,
        public array $questions,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            description: $data['description'] ?? null,
            questions: array_map(
                fn (array $q) => new QuestionDTO(
                    text: $q['text'],
                    alternatives: array_map(
                        fn (array $a) => new AlternativeDTO(
                            text: $a['text'],
                            isCorrect: $a['is_correct'],
                        ),
                        $q['alternatives'],
                    ),
                ),
                $data['questions'],
            ),
        );
    }
}
