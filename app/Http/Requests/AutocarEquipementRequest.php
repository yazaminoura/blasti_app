<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AutocarEquipementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'autocar_id' => 'required|exists:autocars,id',
            // The same équipement cannot be linked twice to the same autocar
            'equipement_id' => [
                'required',
                'exists:equipements,id',
                Rule::unique('autocar_equipements', 'equipement_id')
                    ->where('autocar_id', $this->input('autocar_id'))
                    ->ignore($this->route('autocarequipement')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'autocar_id.required' => 'Choisissez un autocar.',
            'equipement_id.required' => 'Choisissez un équipement.',
            'equipement_id.unique' => 'Cet équipement est déjà associé à cet autocar.',
        ];
    }
}
