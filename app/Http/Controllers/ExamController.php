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
use OpenApi\Attributes as OA;

class ExamController extends Controller
{
    public function __construct(
        private readonly ExamScoringService $scoringService,
        private readonly ExamService $examService,
    ) {}

    #[OA\Get(
        path: '/exams',
        summary: 'Listar todos os exames',
        tags: ['Exames'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de exames',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'title', type: 'string', example: 'Exame de PHP'),
                                    new OA\Property(property: 'description', type: 'string', example: 'Teste de conhecimentos em PHP'),
                                    new OA\Property(property: 'question_count', type: 'integer', example: 10),
                                    new OA\Property(property: 'has_attempted', type: 'boolean', example: false),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                ],
                            ),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ],
    )]
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

    #[OA\Get(
        path: '/exams/{exam}',
        summary: 'Exibir detalhes de um exame com questões e alternativas',
        tags: ['Exames'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'exam', in: 'path', required: true, description: 'ID do exame', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalhes do exame',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'title', type: 'string', example: 'Exame de PHP'),
                                new OA\Property(property: 'description', type: 'string'),
                                new OA\Property(
                                    property: 'questions',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                            new OA\Property(property: 'text', type: 'string', example: 'O que é PHP?'),
                                            new OA\Property(
                                                property: 'alternatives',
                                                type: 'array',
                                                items: new OA\Items(
                                                    properties: [
                                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                                        new OA\Property(property: 'text', type: 'string', example: 'Uma linguagem de programação'),
                                                    ],
                                                ),
                                            ),
                                        ],
                                    ),
                                ),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Exame não encontrado'),
        ],
    )]
    public function show(Exam $exam): ExamResource
    {
        $exam->load('questions.alternatives');

        return new ExamResource($exam);
    }

    #[OA\Post(
        path: '/exams',
        summary: 'Criar novo exame com questões e alternativas',
        tags: ['Exames'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'questions'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Exame de PHP'),
                    new OA\Property(property: 'description', type: 'string', example: 'Teste de conhecimentos em PHP'),
                    new OA\Property(
                        property: 'questions',
                        type: 'array',
                        items: new OA\Items(
                            required: ['text', 'alternatives'],
                            properties: [
                                new OA\Property(property: 'text', type: 'string', example: 'O que é PHP?'),
                                new OA\Property(
                                    property: 'alternatives',
                                    type: 'array',
                                    items: new OA\Items(
                                        required: ['text', 'is_correct'],
                                        properties: [
                                            new OA\Property(property: 'text', type: 'string', example: 'Uma linguagem de programação'),
                                            new OA\Property(property: 'is_correct', type: 'boolean', example: true),
                                        ],
                                    ),
                                ),
                            ],
                        ),
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Exame criado com sucesso'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
        ],
    )]
    public function store(StoreExamRequest $request): ExamResource
    {
        $exam = $this->examService->create(CreateExamDTO::fromArray($request->validated()));

        return new ExamResource($exam);
    }

    #[OA\Post(
        path: '/exams/{exam}/submit',
        summary: 'Submeter respostas de um exame',
        tags: ['Respostas'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'exam', in: 'path', required: true, description: 'ID do exame', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['answers'],
                properties: [
                    new OA\Property(
                        property: 'answers',
                        type: 'array',
                        items: new OA\Items(
                            required: ['question_id', 'alternative_id'],
                            properties: [
                                new OA\Property(property: 'question_id', type: 'integer', example: 1),
                                new OA\Property(property: 'alternative_id', type: 'integer', example: 3),
                            ],
                        ),
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Tentativa registrada com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'score', type: 'integer', example: 7),
                                new OA\Property(property: 'percentage', type: 'number', format: 'float', example: 70.0),
                                new OA\Property(property: 'passed', type: 'boolean', example: true),
                                new OA\Property(property: 'submitted_at', type: 'string', format: 'date-time'),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Exame não encontrado'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
        ],
    )]
    public function submit(SubmitExamRequest $request, Exam $exam): AttemptResource
    {
        $attempt = $this->scoringService->process(new SubmitExamDTO(
            user:    $request->user(),
            exam:    $exam,
            answers: $request->validated()['answers'],
        ));

        return new AttemptResource($attempt);
    }

    #[OA\Get(
        path: '/exams/{exam}/ranking',
        summary: 'Ranking de um exame específico (top 10)',
        tags: ['Dashboard'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'exam', in: 'path', required: true, description: 'ID do exame', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ranking do exame',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'exam',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'title', type: 'string', example: 'Exame de PHP'),
                            ],
                        ),
                        new OA\Property(
                            property: 'leaderboard',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'user_id', type: 'integer', example: 5),
                                    new OA\Property(property: 'percentage', type: 'number', format: 'float', example: 90.0),
                                ],
                            ),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Exame não encontrado'),
        ],
    )]
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

    #[OA\Get(
        path: '/ranking',
        summary: 'Ranking global de estudantes (top 10)',
        tags: ['Dashboard'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ranking global',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'leaderboard',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'user_id', type: 'integer', example: 5),
                                    new OA\Property(property: 'total_score', type: 'number', format: 'float', example: 250.0),
                                ],
                            ),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ],
    )]
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
