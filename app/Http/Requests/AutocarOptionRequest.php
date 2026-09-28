<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AutocarOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'autocar_id' => 'required|exists:autocars,id',
            // The same option cannot be linked twice to the same autocar
            'option_id' => [
                'required',
                'exists:options,id',
                Rule::unique('autocar_options', 'option_id')
                    ->where('autocar_id', $this->input('autocar_id'))
                    ->ignore($this->route('autocaroption')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'autocar_id.required' => 'Choisissez un autocar.',
            'option_id.required' => 'Choisissez une option.',
            'option_id.unique' => 'Cette option est déjà associée à cet autocar.',
        ];
    }
}
