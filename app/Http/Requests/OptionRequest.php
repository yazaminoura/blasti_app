<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'option' => [
                'required', 'string', 'min:3', 'max:255',
                // ignore the current row when editing, otherwise saving without renaming fails
                Rule::unique('options', 'option')->ignore($this->route('option')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'option.required' => "Le nom de l'option est obligatoire.",
            'option.min'      => "Le nom de l'option doit contenir au moins 3 caractères.",
            'option.unique'   => 'Cette option existe déjà.',
        ];
    }
}
