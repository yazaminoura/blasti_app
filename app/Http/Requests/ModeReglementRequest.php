<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModeReglementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** unchecked checkbox = not an online payment mode */
    protected function prepareForValidation(): void
    {
        $this->merge(['en_ligne' => $this->boolean('en_ligne')]);
    }

    public function rules(): array
    {
        return [
            'mode_reglement' => [
                'required', 'min:3', 'max:255',
                // letters (accents included), spaces, apostrophes and dashes: "Carte bancaire", "Espèces"
                "regex:/^[\pL\s'-]+$/u",
                Rule::unique('mode_reglements', 'mode_reglement')->ignore($this->route('modeReglement')),
            ],
            'en_ligne' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'mode_reglement.required' => 'Le mode de règlement est obligatoire.',
            'mode_reglement.min'      => 'Le mode de règlement doit contenir au moins 3 lettres.',
            'mode_reglement.max'      => 'Le mode de règlement ne doit pas dépasser 255 caractères.',
            'mode_reglement.regex'    => 'Le mode de règlement ne doit contenir que des lettres.',
            'mode_reglement.unique'   => 'Ce mode de règlement existe déjà.',
        ];
    }
}
