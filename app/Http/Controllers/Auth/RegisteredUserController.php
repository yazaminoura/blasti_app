<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\RetourUrl;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // dd($request->all());
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ],[
            'name.required' => __('Le nom est obligatoire.'),
            'name.string' => __('Le nom doit être une chaîne de caractères.'),
            'name.max' => __('Le nom ne peut pas dépasser 255 caractères.'),

            'email.required' => __('L\'adresse e-mail est obligatoire.'),
            'email.string' => __('L\'adresse e-mail doit être une chaîne de caractères.'),
            'email.lowercase' => __('L\'adresse e-mail doit être en minuscules.'),
            'email.email' => __('Veuillez entrer une adresse e-mail valide.'),
            'email.max' => __('L\'adresse e-mail ne peut pas dépasser 255 caractères.'),
            'email.unique' => __('Cette adresse e-mail est déjà utilisée.'),

            'password.required' => __('Le mot de passe est obligatoire.'),
            'password.confirmed' => __('La confirmation du mot de passe ne correspond pas.'),
            'password.min' => __('mot de passe comporter au moins 8 caractères.'),
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        // the Registered event has sent the verification link; clicking it brings the client back here
        $retour = RetourUrl::from($request) ?? route('home');
        $request->session()->put('url.intended', $retour);

        // popup "account created" with an "open my mailbox" button (layouts/scripts)
        return redirect()->to($retour)->with('inscription', $user->email);
    }
}
