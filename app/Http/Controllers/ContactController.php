<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Mail\ContactMail;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function index()
    {
        return view('contact');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ], [
            'name.required' => __('Indiquez votre nom.'),
            'email.required' => __('Indiquez votre adresse email.'),
            'email.email' => __("Cette adresse email n'est pas valide."),
            'subject.required' => __('Indiquez le sujet de votre message.'),
            'message.required' => __('Écrivez votre message.'),
            'message.max' => __('Le message ne doit pas dépasser 5000 caractères.'),
        ]);

        // Envoyer l'email à l'adresse de l'entreprise
        // Destination configured in .env (MAIL_CONTACT_ADDRESS)
        try {
            Mail::to(config('mail.contact_address'))->send(new ContactMail($validated));
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', __("Votre message n'a pas pu être envoyé. Réessayez dans quelques minutes ou appelez-nous."));
        }

        return back()->with('success', __('Merci ! Votre message a bien été envoyé, nous vous répondrons rapidement.'));
    }
}
