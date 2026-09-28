<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreautocarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'societe_id' => 'required|exists:societes,id',
            'nbr_siege' => 'required|integer|min:1|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'matricule' => 'required|string|max:50|unique:autocars,matricule',
        ];
    }

    public function messages(): array
    {
        return [
            'societe_id.required' => 'Choisissez une société.',
            'societe_id.exists' => 'La société sélectionnée n\'existe pas.',

            'nbr_siege.required' => 'Le nombre de sièges est obligatoire.',
            'nbr_siege.integer' => 'Le nombre de sièges doit être un nombre entier.',
            'nbr_siege.min' => 'Le nombre de sièges doit être d\'au moins :min.',
            'nbr_siege.max' => 'Le nombre de sièges ne peut pas dépasser :max.',

            'matricule.required' => 'Le matricule est obligatoire.',
            'matricule.max' => 'Le matricule ne peut pas dépasser :max caractères.',
            'matricule.unique' => 'Un autre autocar a déjà ce matricule.',

            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'L\'image doit être au format JPG, PNG, GIF ou WEBP.',
            'image.max' => 'L\'image ne doit pas dépasser 2 Mo.',
        ];
    }
}
