<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TypeVoyageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_voyage' => [
                'required', 'min:3', 'max:255',
                // letters (accents included), spaces, apostrophes and dashes
                "regex:/^[\pL\s'-]+$/u",
                Rule::unique('type_voyages', 'type_voyage')->ignore($this->route('type_voyage')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'type_voyage.required' => 'Le champ Type Voyage est obligatoire.',
            'type_voyage.min'      => 'Le champ Type Voyage doit contenir au moins 3 caractères.',
            'type_voyage.regex'    => 'Le champ Type Voyage ne doit contenir que des lettres.',
            'type_voyage.max'      => 'Le champ Type Voyage ne doit pas dépasser 255 caractères.',
            'type_voyage.unique'   => 'Ce type de voyage existe déjà.',
        ];
    }
}
