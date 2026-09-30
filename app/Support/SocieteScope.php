<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Company space: a back-office account tied to a transport company (users.societe_id) only sees that
 * company's buses, trips, tickets and reviews. Switched on by AdminPermission for admin pages only, so the
 * public site is never filtered. Applied as global scopes (see the models' booted()), which also turns
 * another company's record opened by URL into a 404.
 */
class SocieteScope
{
    public static ?int $societeId = null;

    public static function activer(?int $societeId): void
    {
        static::$societeId = $societeId;
    }

    /** where the model's own societe_id column matches */
    public static function direct(string $column = 'societe_id'): \Closure
    {
        return function (Builder $query) use ($column) {
            if (static::$societeId) {
                $query->where($query->getModel()->qualifyColumn($column), static::$societeId);
            }
        };
    }

    /** through the model's autocar (voyages, reservations) */
    public static function viaAutocar(): \Closure
    {
        return function (Builder $query) {
            if (static::$societeId) {
                $query->whereHas('autocar', fn ($a) => $a->where('societe_id', static::$societeId));
            }
        };
    }
}
