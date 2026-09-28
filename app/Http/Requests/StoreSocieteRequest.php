<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSocieteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'raison_social' => ['required', 'string', 'max:255'],
            'adresse' => ['required', 'string', 'max:255'],
            'ville' => ['required', 'string', 'max:100'],
            'tel' => ['required', 'string', 'max:20'], // Fixed this line
            'nom_contact' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:societes,email'],
            'ice' => ['required', 'string', 'max:15', 'unique:societes,ice'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ];
    }
    public function messages(): array
    {
        return [
            'raison_social.required' => 'Le champ Raison Sociale est obligatoire.',
            'adresse.required' => 'Le champ Adresse est obligatoire.',
            'ville.required' => 'Le champ Ville est obligatoire.',
            'tel.required' => 'Le champ Téléphone est obligatoire.',
            'nom_contact.required' => 'Le champ Nom du Contact est obligatoire.',
            'email.required' => 'Le champ Email est obligatoire.',
            'email.email' => 'L\'adresse email n\'est pas valide.',
            'ice.required' => 'Le champ ICE est obligatoire.',
            'logo.image' => 'Le logo doit être une image.',
            'logo.mimes' => 'Le logo doit être au format PNG, JPG ou WEBP.',
            'logo.max' => 'Le logo ne doit pas dépasser 2 Mo.',
            'email.unique' => 'Une autre société utilise déjà cet email.',
            'ice.unique' => 'Une autre société a déjà cet ICE.',
        ];
    }
}
