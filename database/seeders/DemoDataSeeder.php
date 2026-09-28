<?php

namespace Database\Seeders;

use App\Models\Autocar;
use App\Models\AutocarEquipement;
use App\Models\AutocarOption;
use App\Models\Equipement;
use App\Models\ModeReglement;
use App\Models\Option;
use App\Models\Reservation;
use App\Models\Societe;
use App\Models\TypeVoyage;
use App\Models\User;
use App\Models\Ville;
use App\Models\Voyage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Realistic demo data (Morocco): cities, bus companies (invented names), buses, routes, departures around
 * today, clients and bookings. Runs automatically at the end of "php artisan migrate" on an empty database
 * (see the 2026_09_28_170000 migration), or by hand: php artisan db:seed --class=DemoDataSeeder
 *
 * Accounts (change the passwords before going live): admin@blasti.ma / password, clients *@example.com / password
 */
class DemoDataSeeder extends Seeder
{
    private const CITIES = ['Casablanca', 'Rabat', 'Marrakech', 'Fès', 'Tanger', 'Agadir', 'Meknès', 'Oujda', 'Tétouan', 'Kénitra', 'El Jadida', 'Essaouira', 'Laâyoune',
        'Imouzzer', 'Ifrane', 'Azrou', 'Khénifra', 'Béni Mellal', 'Khémisset', 'Settat', 'Asilah', 'Larache'];

    /**
     * Bus lines: [[city, minutes from the start, price from the start in DH], ...] — also run in the other direction.
     * Lines with more than two cities stop on the way: a client can ride any part of it.
     */
    private const LINES = [
        [['Fès', 0, 0], ['Imouzzer', 40, 20], ['Ifrane', 70, 30], ['Azrou', 95, 40], ['Khénifra', 170, 70], ['Béni Mellal', 290, 110], ['Marrakech', 480, 190]],
        [['Fès', 0, 0], ['Meknès', 60, 40], ['Khémisset', 140, 70], ['Rabat', 200, 100], ['Casablanca', 290, 140]],
        [['Tanger', 0, 0], ['Asilah', 45, 25], ['Larache', 80, 40], ['Kénitra', 190, 95], ['Rabat', 230, 120]],
        [['Casablanca', 0, 0], ['Settat', 60, 35], ['Marrakech', 210, 110], ['Agadir', 420, 220]],
        [['Casablanca', 0, 0], ['Rabat', 90, 60]], [['Casablanca', 0, 0], ['Tanger', 270, 150]], [['Casablanca', 0, 0], ['El Jadida', 90, 50]],
        [['Rabat', 0, 0], ['Kénitra', 45, 30]], [['Marrakech', 0, 0], ['Essaouira', 180, 90]], [['Marrakech', 0, 0], ['Agadir', 210, 110]],
        [['Fès', 0, 0], ['Oujda', 300, 150]], [['Tanger', 0, 0], ['Tétouan', 60, 35]], [['Agadir', 0, 0], ['Laâyoune', 540, 280]],
    ];

    private const COMPANIES = [
        ['Atlas Express', 'Casablanca', 'Boulevard Mohammed V'], ['Sahara Voyages', 'Marrakech', 'Avenue Mohammed VI'],
        ['Rif Lines', 'Tanger', 'Avenue des FAR'], ['Oasis Transport', 'Agadir', 'Avenue Hassan II'],
        ['Nour Bus', 'Fès', 'Route d\'Imouzzer'], ['Médina Travel', 'Rabat', 'Avenue Allal Ben Abdellah'],
    ];

    private const CLIENTS = ['Salma Bennani', 'Youssef El Amrani', 'Khadija Alaoui', 'Omar Tazi', 'Imane Chraibi', 'Hamza Idrissi',
        'Nadia Berrada', 'Mehdi Lahlou', 'Sara El Fassi', 'Anas Benjelloun', 'Fatima Zahra Ouazzani', 'Karim Sefrioui'];

    public function run(): void
    {
        // one transaction: much faster on SQLite, and all-or-nothing
        \Illuminate\Support\Facades\DB::transaction(fn () => $this->seed());
    }

    private function seed(): void
    {
        mt_srand(2026); // same data on every machine

        // ---- reference tables ----
        $villes = collect(self::CITIES)->mapWithKeys(fn ($v) => [$v => Ville::firstOrCreate(['ville' => $v])]);
        $types = collect(['Standard', 'Confort', 'Premium', 'Express'])->map(fn ($t) => TypeVoyage::firstOrCreate(['type_voyage' => $t]));
        $cash = ModeReglement::firstOrCreate(['mode_reglement' => 'Espèces']);
        ModeReglement::firstOrCreate(['mode_reglement' => 'Paiement en agence']);
        ModeReglement::firstOrCreate(['mode_reglement' => 'Carte bancaire'], ['en_ligne' => true]);
        $options = collect(['Wi-Fi à bord', 'Bagage supplémentaire', 'Boisson offerte', 'Collation', 'Assurance annulation'])
            ->map(fn ($o) => Option::firstOrCreate(['option' => $o]));
        $equipements = collect(['Climatisation', 'Toilettes', 'Sièges inclinables', 'Prises USB', 'Écrans individuels', 'Repose-jambes'])
            ->map(fn ($e) => Equipement::firstOrCreate(['equipement' => $e]));

        // ---- accounts (one password hash reused: hashing is slow on purpose) ----
        $password = Hash::make('password');
        User::firstOrCreate(['email' => 'admin@blasti.ma'], [
            'name' => 'Administrateur Blasti', 'password' => $password, 'isadmin' => 1, 'email_verified_at' => now(),
        ]);
        $clients = collect(self::CLIENTS)->map(function ($name, $i) use ($password) {
            $email = strtolower(str_replace(' ', '.', \Illuminate\Support\Str::ascii($name))) . '@example.com';

            return User::firstOrCreate(['email' => $email], [
                'name' => $name, 'password' => $password, 'isadmin' => 0, 'email_verified_at' => now(),
                'telephone' => '06' . str_pad((string) (10000000 + $i * 7919), 8, '0', STR_PAD_LEFT),
                'ville' => self::CITIES[$i % count(self::CITIES)], 'pays' => 'Maroc',
                'created_at' => now()->subDays(mt_rand(5, 90)),
            ]);
        });

        // ---- companies and fleet ----
        $buses = collect();
        foreach (self::COMPANIES as $i => [$name, $city, $street]) {
            $slug = \Illuminate\Support\Str::slug($name);
            $societe = Societe::firstOrCreate(['raison_social' => $name], [
                'adresse' => mt_rand(10, 250) . ', ' . $street, 'ville' => $city, 'tel' => '05' . mt_rand(22, 39) . mt_rand(100000, 999999),
                'nom_contact' => self::CLIENTS[($i + 5) % count(self::CLIENTS)], 'email' => "contact@{$slug}.example.com",
                'ice' => str_pad((string) (1500000000 + $i * 104729), 15, '0', STR_PAD_LEFT),
            ]);
            foreach (range(1, mt_rand(2, 3)) as $n) {
                $bus = Autocar::firstOrCreate(['matricule' => mt_rand(10000, 99999) . '-' . ['A', 'B', 'D', 'H', 'W'][mt_rand(0, 4)] . '-' . mt_rand(1, 80)], [
                    'societe_id' => $societe->id, 'nbr_siege' => [41, 45, 49, 53][mt_rand(0, 3)],
                ]);
                foreach ($equipements->random(mt_rand(2, 4)) as $e) AutocarEquipement::firstOrCreate(['autocar_id' => $bus->id, 'equipement_id' => $e->id]);
                foreach ($options->random(mt_rand(1, 3)) as $o) AutocarOption::firstOrCreate(['autocar_id' => $bus->id, 'option_id' => $o->id]);
                $buses->push($bus);
            }
        }

        // ---- departures: past month (history) + next 3 weeks ----
        $voyages = collect();
        foreach (self::LINES as $line) {
            [, $totalMinutes, $totalPrice] = end($line);
            $back = array_map(fn ($s) => [$s[0], $totalMinutes - $s[1], $totalPrice - $s[2]], array_reverse($line));
            foreach ([$line, $back] as $stops) {
                for ($day = -14; $day <= 21; $day += mt_rand(4, 6)) {
                    foreach ([['07:30', '18:00'], ['09:15', '21:30'], ['14:00', '23:00']][mt_rand(0, 2)] as $k => $hour) {
                        if ($k > 0 && mt_rand(0, 2) > 0) continue; // 1 or 2 departures that day
                        $depart = Carbon::parse(today()->addDays($day)->toDateString() . ' ' . $hour);
                        $arrivee = $depart->copy()->addMinutes($totalMinutes);
                        $type = $types[mt_rand(0, $types->count() - 1)];
                        $extra = ['Standard' => 0, 'Confort' => 20, 'Premium' => 45, 'Express' => 30][$type->type_voyage];
                        // the class supplement is spread along the route (a short hop pays a small part of it)
                        $prix = fn ($p) => round($p + $extra * $p / $totalPrice);
                        $voyage = Voyage::create([
                            'date_depart' => $depart->toDateString(), 'heure_depart' => $depart->format('H:i:s'),
                            'date_arrivee' => $arrivee->toDateString(), 'heure_arrivee' => $arrivee->format('H:i:s'),
                            'ville_depart_id' => $villes[$stops[0][0]]->id, 'ville_arrivee_id' => $villes[end($stops)[0]]->id,
                            'autocar_id' => $buses[mt_rand(0, $buses->count() - 1)]->id, 'type_voyage_id' => $type->id,
                            'prix' => $totalPrice + $extra,
                        ]);
                        $voyage->syncArrets(array_map(fn ($s) => [
                            'ville_id' => $villes[$s[0]]->id, 'heure' => $depart->copy()->addMinutes($s[1])->format('H:i'), 'prix' => $prix($s[2]),
                        ], array_slice($stops, 1, -1)));
                        $voyages->push($voyage->load('arrets'));
                    }
                }
            }
        }

        // ---- bookings: fuller buses in the past, some already sold for the coming days (batch insert: fast) ----
        $modes = ModeReglement::where('en_ligne', false)->pluck('id')->all() ?: [$cash->id];
        $rows = [];
        foreach ($voyages as $voyage) {
            $seats = $voyage->autocar->nbr_siege;
            $past = Carbon::parse($voyage->date_depart)->isPast();
            $count = $past ? mt_rand(6, (int) ($seats * 0.5)) : mt_rand(0, (int) ($seats * 0.25));
            $arrets = $voyage->arrets->values();
            $last = $arrets->count() - 1;
            $used = []; // seat => [[from, to], ...] segments already sold on it
            for ($try = 0, $sold = 0; $sold < $count && $try < $count * 4; $try++) {
                $seat = mt_rand(1, $seats);
                // most tickets cover the whole line; on lines with stops, some ride only part of it
                $i = $last > 1 && mt_rand(1, 100) <= 45 ? mt_rand(0, $last - 1) : 0;
                $j = $last > 1 && mt_rand(1, 100) <= 45 ? mt_rand($i + 1, $last) : $last;
                foreach ($used[$seat] ?? [] as [$a, $b]) {
                    if ($a < $j && $b > $i) {
                        continue 2; // overlaps a ticket already sold on this seat
                    }
                }
                $used[$seat][] = [$i, $j];
                $sold++;
                [$from, $to] = [$arrets[$i], $arrets[$j]];
                $booked = Carbon::parse($voyage->date_depart)->subDays(mt_rand(1, 20))->setTime(mt_rand(8, 22), mt_rand(0, 59));
                if ($booked->isFuture()) $booked = now()->subHours(mt_rand(1, 48));
                $paid = $past || mt_rand(1, 100) <= 35;
                $cancelled = mt_rand(1, 100) <= 3;
                $rows[] = [
                    'num_siege' => $seat,
                    'statut' => $cancelled ? Reservation::ANNULEE : Reservation::CONFIRMEE,
                    'siege_actif' => $cancelled ? null : $seat, // a cancelled booking frees its seat
                    'user_id' => $clients[mt_rand(0, $clients->count() - 1)]->id,
                    'mode_reglement_id' => $modes[mt_rand(0, count($modes) - 1)],
                    'date_reservation' => $booked->toDateString(),
                    'date_depart' => $from->passage_at->toDateString(), 'heure_depart' => $from->passage_at->format('H:i:s'),
                    'date_arrivee' => $to->passage_at->toDateString(), 'heure_arrivee' => $to->passage_at->format('H:i:s'),
                    'ville_depart_id' => $from->ville_id, 'ville_arrivee_id' => $to->ville_id,
                    'arret_depart_id' => $from->id, 'arret_arrivee_id' => $to->id,
                    'autocar_id' => $voyage->autocar_id, 'type_voyage_id' => $voyage->type_voyage_id,
                    'prix' => $voyage->segmentPrice($from, $to), 'frais' => 0, 'voyage_id' => $voyage->id,
                    'paye_le' => $paid ? $booked->copy()->addHours(mt_rand(0, 30)) : null,
                    'paiement_ref' => $paid ? 'guichet' : null,
                    'annulee_le' => $cancelled ? $booked->copy()->addDay() : null,
                    'annulee_par' => $cancelled ? (mt_rand(0, 1) ? 'client' : 'admin') : null,
                    // most cancelled paid tickets are already refunded; a few are left for the admin to handle
                    'rembourse_le' => $cancelled && $paid && mt_rand(1, 100) <= 85 ? $booked->copy()->addDays(3) : null,
                    'montant_rembourse' => $cancelled && $paid ? $voyage->segmentPrice($from, $to) : null,
                    'frais_annulation' => $cancelled && $paid ? 0 : null,
                    'created_at' => $booked, 'updated_at' => $booked,
                ];
            }
        }
        foreach (array_chunk($rows, 300) as $chunk) {
            \Illuminate\Support\Facades\DB::table('reservations')->insert($chunk);
        }
    }
}
