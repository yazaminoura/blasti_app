<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{


    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View|RedirectResponse
    {
        if ($request->user()->isadmin) {
            return view('admin.profile.edit', [
                'user' => $request->user(),
            ]);
        }
        // Clients manage their account in the client area (Paramètres), not on the raw Breeze page
        return redirect()->route('client.profile.parametres.index');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // The avatar upload is handled by the Paramètres page; never store the temporary file path here
        $request->user()->fill(collect($request->validated())->except('image')->all());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Their tickets must stay in the history (and the database refuses to orphan them)
        if ($user->reservations()->exists()) {
            return back()->withErrors(['password' => __('Ce compte a des réservations : contactez-nous pour le supprimer.')], 'userDeletion');
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
