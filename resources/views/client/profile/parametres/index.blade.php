<x-app-layout>
    <x-profile-layout>

                <!-- Profile Settings -->
                <div class="col-xl-9 col-lg-8">
                    <div class="card shadow-none mb-0">
                        <form method="POST" action="{{ route('client.profile.parametres.update') }}" enctype="multipart/form-data">
                                @csrf
                                @method('PUT') 

                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="isax isax-user-edit text-primary me-1"></i> {{ __('Mes informations') }}</h6>
                                        </div>
                                        <div class="card-body pb-3">
                                            
                                            @if ($errors->any())
                                                <div class="alert alert-danger">
                                                    <ul>
                                                        @foreach ($errors->all() as $error)
                                                            <li>{{ $error }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif

                                            <!-- Profile Image -->
                                            <div class="settings-content mb-3">
                                                <h6 class="fs-16 mb-3">{{ __('Informations de base') }}</h6>
                                                <div class="row gy-2">
                                                    <div class="col-lg-12">
                                                        <div class="d-flex align-items-center">
                                                        <img src="{{ auth()->user()->profile_image_path }}"
                                                        alt="{{ __('image') }}" class="img-fluid avatar avatar-xxl br-10 flex-shrink-0 me-3">
                                                            <div>
                                                                <p class="fs-14 text-gray-6 fw-normal mb-2">{{ __('JPG, PNG ou WEBP, 2 Mo maximum. Taille conseillée : 400 x 400 px.') }}</p>
                                                                <div class="d-flex align-items-center">
                                                                    <label for="fileUpload" class="btn btn-primary me-2"><i class="isax isax-gallery-add me-1"></i>{{ __('Changer la photo') }}</label>
                                                                    <input type="file" id="fileUpload" name="image" accept="image/png,image/jpeg,image/webp,image/gif" hidden>
                                                                    @if (auth()->user()->image && ! str_starts_with(auth()->user()->image, 'assets/') && auth()->user()->image !== 'default.jpg')
                                                                        <button type="button" class="btn btn-light" onclick="confirmDeleteImage(event)">{{ __('Supprimer') }}</button>
                                                                    @endif

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Name -->
                                                    <div class="col-lg-12">
                                                        <div>
                                                            <label class="form-label">{{ __('Nom et Prénom') }}</label>
                                                            <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" class="form-control">
                                                        </div>
                                                    </div>

                                                    <!-- Email -->
                                                    <div class="col-lg-6">
                                                        <div>
                                                            <label class="form-label">{{ __('Email') }}</label>
                                                            <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" class="form-control">
                                                        </div>
                                                    </div>

                                                    <!-- Téléphone -->
                                                    <div class="col-lg-6">
                                                        <div>
                                                            <label class="form-label">{{ __('Téléphone') }}</label>
                                                            <input type="text" name="telephone" value="{{ old('telephone', auth()->user()->telephone) }}" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Adresse Information -->
                                            <div class="settings-content">
                                                <h6 class="fs-16 mb-3">{{ __('Informations d\'Adresse') }}</h6>
                                                <div class="row gy-2">
                                                    <div class="col-lg-12">
                                                        <div>
                                                            <label class="form-label">{{ __('Adresse') }}</label>
                                                            <input type="text" name="adresse" value="{{ old('adresse', auth()->user()->adresse) }}" class="form-control">
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-6">
                                                        <div>
                                                            <label class="form-label">{{ __('Pays') }}</label>
                                                            <input type="text" name="pays" value="{{ old('pays', auth()->user()->pays) }}" class="form-control">
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-6">
                                                        <div>
                                                            <label class="form-label">{{ __('Région') }}</label>
                                                            <input type="text" name="region" value="{{ old('region', auth()->user()->region) }}" class="form-control">
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-6">
                                                        <div>
                                                            <label class="form-label">{{ __('Ville') }}</label>
                                                            <input type="text" name="ville" value="{{ old('ville', auth()->user()->ville) }}" class="form-control">
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-6">
                                                        <div>
                                                            <label class="form-label">{{ __('Code Postal') }}</label>
                                                            <input type="text" name="code_postal" value="{{ old('code_postal', auth()->user()->code_postal) }}" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Buttons -->
                                        <div class="card-footer">
                                            <div class="d-flex align-items-center justify-content-end">
                                                <a href="{{ route('client.profile.monprofile.index') }}"  class="btn btn-light me-2">{{ __('Annuler') }}</a>
                                                <button type="submit" class="btn btn-primary">{{ __('Enregistrer') }}</button>
                                            </div>
                                        </div>
                        </form>
                    </div>

                    {{-- Security: change password (Breeze route password.update, errors in the "updatePassword" bag) --}}
                    @php $pwd = $errors->updatePassword; @endphp
                    <div class="card mt-4" id="securite">
                        <div class="card-header"><h6 class="mb-0"><i class="isax isax-lock-1 text-primary me-1"></i> {{ __('Mot de passe') }}</h6></div>
                        <form method="POST" action="{{ route('password.update') }}">
                            @csrf
                            @method('PUT')
                            <div class="card-body">
                                @if (session('status') === 'password-updated')
                                    <div class="alert alert-success py-2"><i class="isax isax-tick-circle me-1"></i> {{ __('Votre mot de passe a été modifié.') }}</div>
                                @endif
                                <div class="row g-3">
                                    <div class="col-lg-12">
                                        <label class="form-label" for="current_password">{{ __('Mot de passe actuel') }}</label>
                                        <input type="password" name="current_password" id="current_password" autocomplete="current-password" class="form-control {{ $pwd->has('current_password') ? 'is-invalid' : '' }}">
                                        @if ($pwd->has('current_password'))<div class="invalid-feedback">{{ $pwd->first('current_password') }}</div>@endif
                                    </div>
                                    <div class="col-lg-6">
                                        <label class="form-label" for="new_password">{{ __('Nouveau mot de passe') }}</label>
                                        <input type="password" name="password" id="new_password" autocomplete="new-password" class="form-control {{ $pwd->has('password') ? 'is-invalid' : '' }}">
                                        @if ($pwd->has('password'))<div class="invalid-feedback">{{ $pwd->first('password') }}</div>@endif
                                        <div class="form-text">{{ __('8 caractères minimum.') }}</div>
                                    </div>
                                    <div class="col-lg-6">
                                        <label class="form-label" for="new_password_confirmation">{{ __('Confirmation') }}</label>
                                        <input type="password" name="password_confirmation" id="new_password_confirmation" autocomplete="new-password" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">{{ __('Changer le mot de passe') }}</button>
                            </div>
                        </form>
                    </div>

                    {{-- Delete account (refused while bookings exist, see ProfileController::destroy) --}}
                    <div class="mz-danger-zone mt-4">
                        <form method="POST" action="{{ route('profile.destroy') }}" data-bl-confirm="{{ __('Supprimer définitivement votre compte ?') }}" data-bl-confirm-text="{{ __('Vos informations sont effacées. Cette action est définitive.') }}" data-bl-confirm-button="{{ __('Oui, supprimer') }}" data-bl-danger>
                            @csrf
                            @method('DELETE')
                            <h6 class="text-danger mb-1"><i class="isax isax-warning-2 me-1"></i> {{ __('Supprimer mon compte') }}</h6>
                            <p class="fs-14 text-muted mb-3">{{ __('La suppression est définitive. Si vous avez des réservations, contactez-nous plutôt : vos billets doivent rester valables.') }}</p>
                            <div class="d-flex flex-wrap gap-2 align-items-start">
                                <div class="flex-grow-1" style="max-width: 320px;">
                                    <input type="password" name="password" placeholder="{{ __('Votre mot de passe') }}" autocomplete="current-password"
                                           class="form-control {{ $errors->userDeletion->has('password') ? 'is-invalid' : '' }}">
                                    @if ($errors->userDeletion->has('password'))<div class="invalid-feedback">{{ $errors->userDeletion->first('password') }}</div>@endif
                                </div>
                                <button type="submit" class="btn btn-outline-danger">{{ __('Supprimer le compte') }}</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- /Profile Settings -->
    </x-profile-layout>
</x-app-layout>
<script>
    // profile photo removal: same popups as the rest of the site (assets/js/blasti-alert.js)
    function confirmDeleteImage(event) {
        event.preventDefault();

        BlastiAlert.fire({
            type: "warning",
            title: @json(__('Supprimer votre photo ?')),
            text: @json(__('Votre photo de profil sera supprimée tout de suite. Vous pourrez en ajouter une autre quand vous voulez.')),
            confirmText: @json(__('Supprimer')),
            cancelText: @json(__('Annuler')),
            danger: true,
        }).then((result) => {
            if (!result.isConfirmed) return;
            fetch("{{ route('profile.delete-image') }}", {
                method: "DELETE",
                headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}", "Content-Type": "application/json", "Accept": "application/json" },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => BlastiAlert.fire({ type: "success", title: @json(__('Photo supprimée')), text: data.message }).then(() => location.reload()))
            .catch(() => BlastiAlert.fire({ type: "error", title: @json(__('Suppression impossible')), text: @json(__('La photo n\'a pas pu être supprimée. Réessayez dans un instant.')) }));
        });
    }
</script>
