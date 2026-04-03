<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'title'          => $this->title,
            'description'    => $this->description,
            'question_count' => $this->when(
                isset($this->questions_count),
                $this->questions_count
            ),
            'has_attempted'  => $this->when(
                array_key_exists('has_attempted', $this->resource->getAttributes() + ['has_attempted' => null]),
                fn () => (bool) $this->has_attempted
            ),
            'questions'      => $this->when(
                $this->relationLoaded('questions'),
                fn () => $this->questions->map(fn ($q) => [
                    'id'           => $q->id,
                    'text'         => $q->text,
                    'alternatives' => $q->alternatives->map(fn ($a) => [
                        'id'   => $a->id,
                        'text' => $a->text,
                    ]),
                ])
            ),
            'created_at'     => $this->created_at->toIso8601String(),
        ];
    }
}
