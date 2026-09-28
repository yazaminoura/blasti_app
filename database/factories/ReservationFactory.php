<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Voyage;
use App\Models\ModeReglement;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationFactory extends Factory
{
    public function definition(): array
    {
        $voyage = Voyage::with('autocar')->inRandomOrder()->first() ?? Voyage::factory()->create();

        // A free seat of this bus (one seat = one reservation per voyage, enforced by a unique index)
        $taken = $voyage->reservations()->pluck('num_siege')->all();
        $free = array_values(array_diff(range(1, max(1, (int) $voyage->autocar?->nbr_siege)), $taken));
        $seat = $free ? $this->faker->randomElement($free) : max($taken ?: [0]) + 1;

        return [
            'date_reservation' => $this->faker->date(),
            'num_siege' => $seat,
            'prix' => $voyage->prix,
            'frais' => 0,
            'date_depart' => $voyage->date_depart,
            'heure_depart' => $voyage->heure_depart,
            'date_arrivee' => $voyage->date_arrivee,
            'heure_arrivee' => $voyage->heure_arrivee,
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'ville_depart_id' => $voyage->ville_depart_id,
            'ville_arrivee_id' => $voyage->ville_arrivee_id,
            'mode_reglement_id' => ModeReglement::inRandomOrder()->value('id') ?? ModeReglement::create(['mode_reglement' => 'Espèces'])->id,
            'autocar_id' => $voyage->autocar_id,
            'type_voyage_id' => $voyage->type_voyage_id,
            'voyage_id' => $voyage->id,
        ];
    }
}
