<?php

namespace Database\Seeders;

use App\Models\Equipement;
use App\Models\ModeReglement;
use App\Models\Option;
use App\Models\TypeVoyage;
use App\Models\Ville;
use Illuminate\Database\Seeder;

/**
 * Base lists a real site needs before the first trip (no accounts, no fake trips):
 * main Moroccan cities, trip types, payment modes, bus options and equipment. Safe to run again.
 * php artisan db:seed --class=ReferenceSeeder (also run by db:seed when DEMO_DATA=false)
 */
class ReferenceSeeder extends Seeder
{
    public const CITIES = ['Casablanca', 'Rabat', 'Marrakech', 'Fès', 'Tanger', 'Agadir', 'Meknès', 'Oujda', 'Tétouan', 'Kénitra', 'El Jadida', 'Essaouira', 'Laâyoune',
        'Imouzzer', 'Ifrane', 'Azrou', 'Khénifra', 'Béni Mellal', 'Khémisset', 'Settat', 'Asilah', 'Larache'];

    public function run(): void
    {
        foreach (self::CITIES as $ville) {
            Ville::firstOrCreate(['ville' => $ville]);
        }
        foreach (['Standard', 'Confort', 'Premium', 'Express'] as $type) {
            TypeVoyage::firstOrCreate(['type_voyage' => $type]);
        }
        ModeReglement::firstOrCreate(['mode_reglement' => 'Espèces']);
        ModeReglement::firstOrCreate(['mode_reglement' => 'Paiement en agence'], ['en_agence' => true]);
        // offered to clients only once the CMI keys are set (Cmi::available)
        ModeReglement::firstOrCreate(['mode_reglement' => 'Carte bancaire'], ['en_ligne' => true]);
        foreach (['Wi-Fi à bord', 'Bagage supplémentaire', 'Boisson offerte', 'Collation', 'Assurance annulation'] as $option) {
            Option::firstOrCreate(['option' => $option]);
        }
        foreach (['Climatisation', 'Toilettes', 'Sièges inclinables', 'Prises USB', 'Écrans individuels', 'Repose-jambes'] as $equipement) {
            Equipement::firstOrCreate(['equipement' => $equipement]);
        }
    }
}
