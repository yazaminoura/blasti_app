<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateSocieteRequest extends StoreSocieteRequest
{
    /**
     * Same rules as creation, but the uniqueness checks ignore the société being edited.
     */
    public function rules(): array
    {
        $societe = $this->route('societe');

        return array_merge(parent::rules(), [
            'email' => ['required', 'email', 'max:255', Rule::unique('societes', 'email')->ignore($societe)],
            'ice' => ['required', 'string', 'max:15', Rule::unique('societes', 'ice')->ignore($societe)],
        ]);
    }
}
