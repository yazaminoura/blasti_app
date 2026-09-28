<?php

namespace App\Http\Requests;

use App\Models\Reservation;
use App\Models\Voyage;
use Illuminate\Validation\Rule;

class UpdateautocarRequest extends StoreautocarRequest
{
    /**
     * Same rules as creation, plus: the matricule stays unique ignoring this autocar, and the capacity
     * cannot go below the highest seat already sold on its upcoming voyages (those seats would disappear).
     */
    public function rules(): array
    {
        $autocar = $this->route('autocar');

        return array_merge(parent::rules(), [
            'nbr_siege' => ['required', 'integer', 'min:' . max(1, $this->highestSoldSeat()), 'max:100'],
            'matricule' => ['required', 'string', 'max:50', Rule::unique('autocars', 'matricule')->ignore($autocar)],
        ]);
    }

    public function messages(): array
    {
        $messages = parent::messages();
        if ($this->highestSoldSeat() > 1) {
            $messages['nbr_siege.min'] = 'Impossible de descendre sous :min sièges : le siège n° :min est déjà vendu sur un voyage à venir.';
        }

        return $messages;
    }

    private function highestSoldSeat(): int
    {
        return (int) Reservation::whereIn(
            'voyage_id',
            Voyage::where('autocar_id', $this->route('autocar')->id)->whereDate('date_depart', '>=', today())->select('id')
        )->max('num_siege');
    }
}
