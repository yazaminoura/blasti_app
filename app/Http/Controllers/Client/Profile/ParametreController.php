<?php

namespace App\Http\Controllers\Client\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class ParametreController extends Controller
{
    public function index()
    {
        return view('client.profile.parametres.index', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $this->deleteStoredImage($user->image);
            $data['image'] = $request->file('image')->store('profile_images', 'public');
        } else {
            unset($data['image']);
        }

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('client.profile.monprofile.index')->with('success', __('Profil mis à jour avec succès'));
    }

    public function deleteProfileImage(Request $request)
    {
        $user = Auth::user();

        $this->deleteStoredImage($user->image);

        // null => the User model falls back to the default avatar
        $user->image = null;
        $user->save();

        if ($request->expectsJson()) {
            return response()->json(['message' => __("L'image de profil a été supprimée avec succès")]);
        }

        return back()->with('success', __("L'image de profil a été supprimée avec succès"));
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

        Auth::logout();

        $this->deleteStoredImage($user->image);
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Remove an uploaded profile image from the public disk (never the bundled default avatars).
     */
    private function deleteStoredImage(?string $path): void
    {
        if ($path && str_starts_with($path, 'profile_images/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
