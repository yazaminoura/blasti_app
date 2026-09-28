<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EquipementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'equipement' => [
                'required', 'string', 'min:3', 'max:255',
                // ignore the current row when editing, otherwise saving without renaming fails
                Rule::unique('equipements', 'equipement')->ignore($this->route('equipement')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'equipement.required' => "Le nom de l'équipement est obligatoire.",
            'equipement.min'      => "Le nom de l'équipement doit contenir au moins 3 caractères.",
            'equipement.unique'   => 'Cet équipement existe déjà.',
        ];
    }
}
