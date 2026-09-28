<?php

namespace App\Http\Controllers;

use App\Models\Option;
use App\Http\Requests\OptionRequest;

class OptionController extends Controller
{
    public function index()
    {
        $options = Option::paginate(10);
        return view('admin.options.index', compact('options'));
    }

    public function create()
    {
        return view('admin.options.create');
    }

    public function store(OptionRequest $request)
    {
        Option::create($request->validated());

        return redirect()->route('options.index')->with('success', 'Votre option a été créée avec succès.');
    }

    public function edit(Option $option)
    {
        return view('admin.options.edit', compact('option'));
    }

    public function update(OptionRequest $request, Option $option)
    {
        $option->update($request->validated());

        return redirect()->route('options.index')->with('success', 'Votre option a été modifiée avec succès.');
    }

    public function destroy(Option $option)
    {
        return $this->deleteAndRedirect(
            $option,
            'options.index',
            'Votre option a été supprimée avec succès.',
            "Impossible de supprimer cette option car elle est liée à d'autres données."
        );
    }
}
