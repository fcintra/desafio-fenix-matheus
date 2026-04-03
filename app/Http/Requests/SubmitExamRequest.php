<?php

namespace App\Http\Requests;

use App\Models\Attempt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers'   => ['required', 'array', 'min:1'],
            'answers.*' => ['required', 'integer', 'exists:alternatives,id'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $alreadyAttempted = Attempt::where('user_id', $this->user()->id)
                    ->where('exam_id', $this->route('exam')->id)
                    ->exists();

                if ($alreadyAttempted) {
                    $validator->errors()->add(
                        'exam',
                        'Você já realizou esta prova e não pode fazê-la novamente.'
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'answers.required'   => 'É obrigatório enviar as respostas.',
            'answers.array'      => 'O campo answers deve ser um objeto JSON.',
            'answers.min'        => 'É necessário responder ao menos uma questão.',
            'answers.*.required' => 'Cada questão deve ter uma alternativa selecionada.',
            'answers.*.integer'  => 'O ID de cada alternativa deve ser um número inteiro.',
            'answers.*.exists'   => 'Uma ou mais alternativas informadas não existem.',
        ];
    }
}
