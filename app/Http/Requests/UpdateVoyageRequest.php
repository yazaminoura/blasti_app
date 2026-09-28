<?php

namespace App\Http\Requests;

/**
 * Same rules as creation (heure_depart / heure_arrivee were missing here before,
 * so edited hours were silently dropped by validated()).
 */
class UpdateVoyageRequest extends StoreVoyageRequest
{
}
