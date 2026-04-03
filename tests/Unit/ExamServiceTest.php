<?php

use App\Services\ExamService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(ExamService::class);
});

it('creates an exam with questions and alternatives', function (): void {
    $exam = $this->service->create([
        'title'       => 'Prova de PHP',
        'description' => 'Fundamentos',
        'questions'   => [
            [
                'text'         => 'O que é PHP?',
                'alternatives' => [
                    ['text' => 'Linguagem de programação', 'is_correct' => true],
                    ['text' => 'Banco de dados',           'is_correct' => false],
                ],
            ],
        ],
    ]);

    expect($exam->title)->toBe('Prova de PHP')
        ->and($exam->questions)->toHaveCount(1)
        ->and($exam->questions->first()->alternatives)->toHaveCount(2);
});

it('creates an exam without description', function (): void {
    $exam = $this->service->create([
        'title'     => 'Sem descrição',
        'questions' => [
            [
                'text'         => 'Questão única',
                'alternatives' => [
                    ['text' => 'Certa', 'is_correct' => true],
                    ['text' => 'Errada', 'is_correct' => false],
                ],
            ],
        ],
    ]);

    expect($exam->description)->toBeNull();
});

it('persists the exam and its relations in the database', function (): void {
    $this->service->create([
        'title'     => 'Prova persistida',
        'questions' => [
            [
                'text'         => 'Questão A',
                'alternatives' => [
                    ['text' => 'Alt 1', 'is_correct' => true],
                    ['text' => 'Alt 2', 'is_correct' => false],
                ],
            ],
        ],
    ]);

    expect(\App\Models\Exam::where('title', 'Prova persistida')->exists())->toBeTrue()
        ->and(\App\Models\Question::where('text', 'Questão A')->exists())->toBeTrue()
        ->and(\App\Models\Alternative::where('text', 'Alt 1')->exists())->toBeTrue();
});

it('creates multiple questions with their alternatives', function (): void {
    $exam = $this->service->create([
        'title'     => 'Multi questões',
        'questions' => [
            [
                'text'         => 'Q1',
                'alternatives' => [
                    ['text' => 'A', 'is_correct' => true],
                    ['text' => 'B', 'is_correct' => false],
                ],
            ],
            [
                'text'         => 'Q2',
                'alternatives' => [
                    ['text' => 'C', 'is_correct' => false],
                    ['text' => 'D', 'is_correct' => true],
                    ['text' => 'E', 'is_correct' => false],
                ],
            ],
        ],
    ]);

    expect($exam->questions)->toHaveCount(2)
        ->and($exam->questions->last()->alternatives)->toHaveCount(3);
});

it('loads questions and alternatives on the returned exam', function (): void {
    $exam = $this->service->create([
        'title'     => 'Com relações',
        'questions' => [
            [
                'text'         => 'Q',
                'alternatives' => [
                    ['text' => 'X', 'is_correct' => true],
                    ['text' => 'Y', 'is_correct' => false],
                ],
            ],
        ],
    ]);

    expect($exam->relationLoaded('questions'))->toBeTrue()
        ->and($exam->questions->first()->relationLoaded('alternatives'))->toBeTrue();
});
