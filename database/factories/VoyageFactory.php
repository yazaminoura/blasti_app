<?php

namespace Database\Factories;

use App\Models\Ville;
use App\Models\Autocar;
use App\Models\TypeVoyage;
use Illuminate\Database\Eloquent\Factories\Factory;

class VoyageFactory extends Factory
{
    public function definition(): array
    {
        $dateDepart = $this->faker->dateTimeBetween('now', '+1 month');
        // Arrivee is usually same day or next day
        $dateArrivee = (clone $dateDepart)->modify('+' . rand(1, 10) . ' hours');

        return [
            'date_depart' => $dateDepart->format('Y-m-d'),
            'date_arrivee' => $dateArrivee->format('Y-m-d'),
            'heure_depart' => $dateDepart->format('H:i:s'),
            'heure_arrivee' => $dateArrivee->format('H:i:s'),
            // two different cities (a trip from a city to itself makes no sense)
            'ville_depart_id' => $depart = Ville::inRandomOrder()->value('id') ?? Ville::factory()->create()->id,
            'ville_arrivee_id' => Ville::whereKeyNot($depart)->inRandomOrder()->value('id') ?? Ville::factory()->create()->id,
            'autocar_id' => Autocar::inRandomOrder()->value('id') ?? Autocar::factory(),
            'type_voyage_id' => TypeVoyage::inRandomOrder()->value('id') ?? TypeVoyage::factory(),
            'prix' => $this->faker->randomFloat(2, 50, 500),
        ];
    }
}
