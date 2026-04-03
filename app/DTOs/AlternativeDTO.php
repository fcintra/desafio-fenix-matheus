<?php

namespace App\DTOs;

readonly class AlternativeDTO
{
    public function __construct(
        public string $text,
        public bool $isCorrect,
    ) {}
}
