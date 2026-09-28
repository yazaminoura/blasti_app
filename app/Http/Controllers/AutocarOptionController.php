<?php

namespace App\Http\Controllers;

use App\Models\AutocarOption;
use App\Http\Requests\AutocarOptionRequest;
use App\Models\Autocar;
use App\Models\Option;

class AutocarOptionController extends Controller
{
    public function index()
    {
        $autocaroptions = AutocarOption::with(['autocar', 'option'])->paginate(10);
        return view('admin.autocaroptions.index', compact('autocaroptions'));
    }

    public function create()
    {
        $autocars = Autocar::all();
        $options = Option::all();
        return view('admin.autocaroptions.create', compact('autocars', 'options'));
    }

    public function store(AutocarOptionRequest $request)
    {
        AutocarOption::create($request->validated());

        return redirect()->route('autocaroptions.index')->with('success', "L'option a été associée à l'autocar avec succès.");
    }

    public function edit(AutocarOption $autocaroption)
    {
        $autocars = Autocar::all();
        $options = Option::all();
        return view('admin.autocaroptions.edit', compact('autocaroption', 'autocars', 'options'));
    }

    public function update(AutocarOptionRequest $request, AutocarOption $autocaroption)
    {
        $autocaroption->update($request->validated());

        return redirect()->route('autocaroptions.index')->with('success', 'Association modifiée avec succès.');
    }

    public function destroy(AutocarOption $autocaroption)
    {
        $autocaroption->delete();
        return redirect()->route('autocaroptions.index')->with('success', 'Association supprimée avec succès.');
    }
}
