<?php

namespace App\DTOs;

readonly class QuestionDTO
{
    public function __construct(
        public string $text,
        public array $alternatives,
    ) {}
}
