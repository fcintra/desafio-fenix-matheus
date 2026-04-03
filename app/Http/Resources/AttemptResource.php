<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'score'        => $this->score,
            'percentage'   => (float) $this->percentage,
            'passed'       => (float) $this->percentage >= 60.0,
            'exam'         => [
                'id'    => $this->exam->id,
                'title' => $this->exam->title,
            ],
            'student'      => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ],
            'submitted_at' => $this->created_at->toIso8601String(),
        ];
    }

    public function withResponse(Request $request, \Illuminate\Http\JsonResponse $response): void
    {
        $response->setStatusCode(201);
    }
}
