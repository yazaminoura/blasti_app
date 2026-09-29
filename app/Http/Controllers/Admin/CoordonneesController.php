<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Parametre;
use Illuminate\Http\Request;

/** Public contact details (footer, contact page, e-mails, tickets) and social links, in the single "parametres" row. */
class CoordonneesController extends Controller
{
    private const SOCIAL = ['facebook', 'instagram', 'tiktok', 'x', 'linkedin'];

    public function edit()
    {
        return view('admin.coordonnees.edit', ['parametre' => Parametre::actuel()]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'telephone' => ['required', 'string', 'max:40', 'regex:/^[0-9 +().-]+$/'],
            'email' => 'required|email|max:120',
            'adresse' => 'required|string|max:200',
        ] + array_fill_keys(self::SOCIAL, 'nullable|url|max:200'), [
            'telephone.regex' => 'Le téléphone ne peut contenir que des chiffres, espaces et + ( ) . -',
            '*.url' => 'Mettez l\'adresse complète de la page, en commençant par https://',
        ]);

        Parametre::actuel()->fill($validated)->save();

        return redirect()->route('admin.coordonnees.edit')->with('success', 'Coordonnées enregistrées : elles s\'affichent sur tout le site.');
    }

    /** Sends a test e-mail to the logged-in admin: checks the SMTP settings of .env (MAIL_*). */
    public function testMail(Request $request)
    {
        $mailer = config('mail.default');
        if ($mailer === 'log' || $mailer === 'array') {
            return back()->with('error', "Les e-mails ne partent pas : MAIL_MAILER={$mailer} dans .env (ils sont écrits dans storage/logs/laravel.log). Configurez un serveur SMTP.");
        }

        try {
            \Illuminate\Support\Facades\Mail::raw(
                'Ceci est un e-mail de test envoyé depuis l\'administration ' . config('safar.nom') . '. Si vous le lisez, les e-mails aux clients (billets, rappels) partent bien.',
                fn ($message) => $message->to($request->user()->email)->subject('E-mail de test | ' . config('safar.nom'))
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Échec de l\'envoi : ' . \Illuminate\Support\Str::limit($e->getMessage(), 200));
        }

        return back()->with('success', 'E-mail de test envoyé à ' . $request->user()->email . '. Vérifiez votre boîte (et les spams).');
    }
}
