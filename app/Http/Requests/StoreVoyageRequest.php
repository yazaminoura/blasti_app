<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVoyageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_depart'      => 'required|date',
            'date_arrivee'     => 'required|date|after_or_equal:date_depart',
            'heure_depart'     => 'required|date_format:H:i,H:i:s',
            'heure_arrivee'    => 'required|date_format:H:i,H:i:s',
            'ville_depart_id'  => 'required|exists:villes,id',
            'ville_arrivee_id' => 'required|exists:villes,id|different:ville_depart_id',
            'autocar_id'       => 'required|exists:autocars,id',
            'type_voyage_id'   => 'required|exists:type_voyages,id',
            'prix'             => 'required|numeric|min:0',
            'image'            => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'arrets'           => 'nullable|array|max:15',
            'arrets.*.ville_id' => 'required|exists:villes,id',
            'arrets.*.heure'   => 'required|date_format:H:i,H:i:s',
            'arrets.*.prix'    => 'required|numeric|min:0',
        ];
    }

    /**
     * Checks that need several fields: arrival after departure (date + hour), no new departure in the past,
     * and on update, a replacement autocar must hold every seat already sold.
     */
    public function after(): array
    {
        return [function ($validator) {
            if ($validator->errors()->hasAny(['date_depart', 'date_arrivee', 'heure_depart', 'heure_arrivee', 'autocar_id'])) {
                return;
            }

            $depart = \Carbon\Carbon::parse($this->date_depart . ' ' . $this->heure_depart);
            $arrivee = \Carbon\Carbon::parse($this->date_arrivee . ' ' . $this->heure_arrivee);

            if ($arrivee->lte($depart)) {
                $validator->errors()->add('heure_arrivee', "L'arrivée doit être après le départ (date et heure).");
            }

            $voyage = $this->route('voyage');
            $departChanged = ! $voyage || $depart->ne(\Carbon\Carbon::parse($voyage->date_depart . ' ' . $voyage->heure_depart));
            if ($departChanged && $depart->isPast()) {
                $validator->errors()->add('date_depart', 'Le départ ne peut pas être dans le passé.');
            }

            $this->checkStops($validator, $depart, $arrivee);

            if ($voyage && (int) $this->autocar_id !== (int) $voyage->autocar_id) {
                $highestSoldSeat = (int) $voyage->reservations()->max('num_siege');
                $capacity = (int) \App\Models\Autocar::whereKey($this->autocar_id)->value('nbr_siege');
                if ($highestSoldSeat > $capacity) {
                    $validator->errors()->add('autocar_id', "Cet autocar n'a que {$capacity} sièges, mais le siège n° {$highestSoldSeat} est déjà vendu sur ce voyage.");
                }
            }
        }];
    }

    /**
     * Intermediate stops: other cities than the ends, each city once, times in route order between
     * departure and arrival, prices rising from 0 up to the full-trip price.
     */
    private function checkStops($validator, \Carbon\Carbon $depart, \Carbon\Carbon $arrivee): void
    {
        $stops = array_values((array) $this->input('arrets', []));
        if (! $stops || $validator->errors()->hasAny(['arrets', 'arrets.*', 'prix', 'ville_depart_id', 'ville_arrivee_id'])) {
            return;
        }

        $villes = [(int) $this->ville_depart_id, (int) $this->ville_arrivee_id];
        $previous = $depart->copy();
        $previousPrix = 0.0;
        foreach ($stops as $i => $stop) {
            $n = $i + 1;
            if (in_array((int) $stop['ville_id'], $villes, true)) {
                $validator->errors()->add("arrets.$i.ville_id", "Arrêt $n : cette ville est déjà sur le trajet.");
            }
            $villes[] = (int) $stop['ville_id'];

            $at = $previous->copy()->setTimeFromTimeString($stop['heure']);
            if ($at->lte($previous)) {
                $at->addDay();
            }
            if ($at->gte($arrivee)) {
                $validator->errors()->add("arrets.$i.heure", "Arrêt $n : l'heure de passage doit être avant l'arrivée (et après l'arrêt précédent).");
            }
            $previous = $at;

            $prix = (float) $stop['prix'];
            if ($prix <= $previousPrix || $prix >= (float) $this->prix) {
                $validator->errors()->add("arrets.$i.prix", "Arrêt $n : le prix depuis le départ doit être plus grand que celui de l'arrêt précédent et plus petit que le prix du trajet complet (" . $this->prix . " DH).");
            }
            $previousPrix = $prix;
        }
    }

    public function messages(): array
    {
        return [
            'date_depart.required'          => 'La date de départ est obligatoire.',
            'date_depart.date'              => "La date de départ n'est pas valide.",
            'date_arrivee.required'         => "La date d'arrivée est obligatoire.",
            'date_arrivee.date'             => "La date d'arrivée n'est pas valide.",
            'date_arrivee.after_or_equal'   => "La date d'arrivée doit être égale ou postérieure à la date de départ.",
            'heure_depart.required'         => "L'heure de départ est obligatoire.",
            'heure_depart.date_format'      => "L'heure de départ n'est pas valide.",
            'heure_arrivee.required'        => "L'heure d'arrivée est obligatoire.",
            'heure_arrivee.date_format'     => "L'heure d'arrivée n'est pas valide.",
            'ville_depart_id.required'      => 'La ville de départ est obligatoire.',
            'ville_depart_id.exists'        => "La ville de départ n'existe pas.",
            'ville_arrivee_id.required'     => "La ville d'arrivée est obligatoire.",
            'ville_arrivee_id.exists'       => "La ville d'arrivée n'existe pas.",
            'ville_arrivee_id.different'    => "La ville d'arrivée doit être différente de la ville de départ.",
            'autocar_id.required'           => "L'autocar est obligatoire.",
            'autocar_id.exists'             => "L'autocar sélectionné n'existe pas.",
            'type_voyage_id.required'       => 'Le type de voyage est obligatoire.',
            'type_voyage_id.exists'         => "Le type de voyage sélectionné n'existe pas.",
            'prix.required'                 => 'Le prix est obligatoire.',
            'prix.numeric'                  => 'Le prix doit être un nombre.',
            'prix.min'                      => 'Le prix doit être supérieur ou égal à 0.',
            'image.image'                   => "Le fichier doit être une image.",
            'image.max'                     => "L'image ne doit pas dépasser 2 Mo.",
            'arrets.max'                    => 'Un voyage peut avoir 15 arrêts intermédiaires au maximum.',
            'arrets.*.ville_id.required'    => "Choisissez la ville de l'arrêt.",
            'arrets.*.ville_id.exists'      => "La ville de l'arrêt n'existe pas.",
            'arrets.*.heure.required'       => "L'heure de passage est obligatoire.",
            'arrets.*.heure.date_format'    => "L'heure de passage n'est pas valide.",
            'arrets.*.prix.required'        => 'Le prix depuis le départ est obligatoire.',
            'arrets.*.prix.numeric'         => 'Le prix doit être un nombre.',
        ];
    }
}
