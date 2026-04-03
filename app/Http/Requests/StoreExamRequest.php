<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'                                 => ['required', 'string', 'max:255'],
            'description'                           => ['nullable', 'string'],
            'questions'                             => ['required', 'array', 'min:1'],
            'questions.*.text'                      => ['required', 'string'],
            'questions.*.alternatives'              => ['required', 'array', 'min:2'],
            'questions.*.alternatives.*.text'       => ['required', 'string'],
            'questions.*.alternatives.*.is_correct' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach ($this->validated()['questions'] ?? [] as $i => $question) {
                    $correctCount = collect($question['alternatives'])
                        ->where('is_correct', true)
                        ->count();

                    if ($correctCount !== 1) {
                        $validator->errors()->add(
                            "questions.{$i}.alternatives",
                            'Cada questão deve ter exatamente uma alternativa correta.'
                        );
                    }
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'questions.required'                         => 'A prova deve ter ao menos uma questão.',
            'questions.*.text.required'                  => 'O enunciado da questão é obrigatório.',
            'questions.*.alternatives.min'               => 'Cada questão precisa de ao menos 2 alternativas.',
            'questions.*.alternatives.*.text.required'   => 'O texto de cada alternativa é obrigatório.',
            'questions.*.alternatives.*.is_correct.required' => 'Indique qual alternativa é correta.',
        ];
    }
}
