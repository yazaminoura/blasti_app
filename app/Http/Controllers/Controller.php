<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;

abstract class Controller
{
    /**
     * Delete a model and redirect to $route. When the row is still referenced by a
     * foreign key (MySQL error 23000) show a friendly message instead of a 500 page.
     */
    protected function deleteAndRedirect(Model $model, string $route, string $successMessage, string $inUseMessage): RedirectResponse
    {
        try {
            $model->delete();
        } catch (QueryException $e) {
            $message = $e->getCode() == '23000'
                ? $inUseMessage
                : 'Une erreur est survenue lors de la suppression.';

            return redirect()->route($route)->with('error', $message);
        }

        return redirect()->route($route)->with('success', $successMessage);
    }
}
