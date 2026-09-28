<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Voyage;
use App\Models\User;
use App\Models\Autocar;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Export reservations as Excel-compatible CSV (same filters as the admin list).
     */
    public function reservations(Request $request): StreamedResponse
    {
        $filters = $request->only(Reservation::FILTERS);

        $filename = 'reservations_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($filters) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header row
            self::csvRow($handle, [
                'ID',
                'Client',
                'Email',
                'Téléphone',
                'Ville Départ',
                'Ville Arrivée',
                'Date Réservation',
                'Date Départ',
                'Heure Départ',
                'Date Arrivée',
                'Heure Arrivée',
                'N° Siège',
                'Prix (DH)',
                'Frais (DH)',
                'Mode Règlement',
                'Paiement',
                'Type Voyage',
                'Autocar',
                'Créé le',
            ], ';');

            // Data rows — chunked for memory efficiency
            Reservation::filter($filters)
                ->with(['user', 'villeDepart', 'villeArrivee', 'modeReglement', 'voyage.typeVoyage', 'autocar'])
                ->orderByDesc('id')
                ->chunk(200, function ($reservations) use ($handle) {
                    foreach ($reservations as $r) {
                        self::csvRow($handle, [
                            $r->id,
                            $r->user->name ?? '—',
                            $r->user->email ?? '—',
                            $r->user->telephone ?? '—',
                            $r->villeDepart->ville ?? '—',
                            $r->villeArrivee->ville ?? '—',
                            $r->date_reservation,
                            $r->date_depart,
                            $r->heure_depart,
                            $r->date_arrivee,
                            $r->heure_arrivee,
                            $r->num_siege,
                            number_format($r->prix, 2, ',', ' '),
                            number_format($r->frais, 2, ',', ' '),
                            $r->modeReglement->mode_reglement ?? '—',
                            $r->statusBadge()[0],
                            $r->voyage->typeVoyage->type_voyage ?? '—',
                            $r->autocar->matricule ?? '—',
                            $r->created_at?->format('d/m/Y H:i'),
                        ], ';');
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export all voyages data as Excel-compatible CSV.
     */
    public function voyages(): StreamedResponse
    {
        $filename = 'voyages_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            self::csvRow($handle, [
                'ID',
                'Ville Départ',
                'Ville Arrivée',
                'Date Départ',
                'Heure Départ',
                'Date Arrivée',
                'Heure Arrivée',
                'Prix (DH)',
                'Type Voyage',
                'Autocar',
                'Nb Réservations',
                'Créé le',
            ], ';');

            Voyage::with(['villeDepart', 'villeArrivee', 'typeVoyage', 'autocar'])
                ->withCount('reservations')
                ->orderByDesc('id')
                ->chunk(200, function ($voyages) use ($handle) {
                    foreach ($voyages as $v) {
                        self::csvRow($handle, [
                            $v->id,
                            $v->villeDepart->ville ?? '—',
                            $v->villeArrivee->ville ?? '—',
                            $v->date_depart,
                            $v->heure_depart,
                            $v->date_arrivee,
                            $v->heure_arrivee,
                            number_format($v->prix, 2, ',', ' '),
                            $v->typeVoyage->type_voyage ?? '—',
                            $v->autocar->matricule ?? '—',
                            $v->reservations_count,
                            $v->created_at?->format('d/m/Y H:i'),
                        ], ';');
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export all users data as Excel-compatible CSV.
     */
    public function users(): StreamedResponse
    {
        $filename = 'utilisateurs_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            self::csvRow($handle, [
                'ID',
                'Nom',
                'Email',
                'Téléphone',
                'Pays',
                'Région',
                'Ville',
                'Adresse',
                'Code Postal',
                'Admin',
                'Nb Réservations',
                'Inscrit le',
            ], ';');

            User::withCount('reservations')
                ->orderByDesc('id')
                ->chunk(200, function ($users) use ($handle) {
                    foreach ($users as $u) {
                        self::csvRow($handle, [
                            $u->id,
                            $u->name,
                            $u->email,
                            $u->telephone ?? '—',
                            $u->pays ?? '—',
                            $u->region ?? '—',
                            $u->ville ?? '—',
                            $u->adresse ?? '—',
                            $u->code_postal ?? '—',
                            $u->isadmin ? 'Oui' : 'Non',
                            $u->reservations_count,
                            $u->created_at?->format('d/m/Y H:i'),
                        ], ';');
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export all autocars data as Excel-compatible CSV.
     */
    public function autocars(): StreamedResponse
    {
        $filename = 'autocars_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            self::csvRow($handle, [
                'ID',
                'Matricule',
                'Nb Sièges',
                'Société',
                'Nb Voyages',
                'Créé le',
            ], ';');

            Autocar::with('societe')
                ->withCount('voyages')
                ->orderByDesc('id')
                ->chunk(200, function ($autocars) use ($handle) {
                    foreach ($autocars as $a) {
                        self::csvRow($handle, [
                            $a->id,
                            $a->matricule ?? '—',
                            $a->nbr_siege ?? '—',
                            $a->societe->raison_social ?? '—',
                            $a->voyages_count,
                            $a->created_at?->format('d/m/Y H:i'),
                        ], ';');
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Writes one CSV line. Text cells starting with = + - @ (or a tab / CR) are prefixed with a quote,
     * otherwise Excel would run them as formulas (a client could name himself =HYPERLINK(...)).
     */
    private static function csvRow($handle, array $cells, string $separator = ';'): void
    {
        $cells = array_map(function ($cell) {
            if (is_string($cell) && $cell !== '' && ! is_numeric($cell) && in_array($cell[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                return "'" . $cell;
            }

            return $cell;
        }, $cells);

        fputcsv($handle, $cells, $separator);
    }
}
