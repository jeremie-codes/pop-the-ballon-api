<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnswerPopChoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'answer' => [
                'required',
                'string',
                Rule::in(['a', 'b']),
            ],

            'session_id' => [
                'nullable',
                'integer',
                'exists:pop_choice_sessions,id',
            ],
        ];
    }
}