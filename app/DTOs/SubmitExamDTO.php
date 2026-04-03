<?php

namespace App\DTOs;

use App\Models\Exam;
use App\Models\User;

readonly class SubmitExamDTO
{
    public function __construct(
        public User $user,
        public Exam $exam,
        public array $answers,
    ) {}
}
