{{-- Review of a finished trip (Client\AvisController) --}}
@php $r = $reservation; @endphp
<x-app-layout>
    <section class="section pt-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 p-md-5">
                            <div class="text-center mb-4">
                                <span class="avatar avatar-xl rounded-circle bg-primary-transparent text-primary mb-3"><i class="isax isax-star-1 fs-32"></i></span>
                                <h3 class="mb-1">{{ __('Comment s\'est passé votre voyage ?') }}</h3>
                                <p class="text-muted mb-0">
                                    {{ __($r->villeDepart?->ville) }} → {{ __($r->villeArrivee?->ville) }} · {{ $r->departAt()->translatedFormat('d M') }}
                                    · <strong>{{ $r->autocar?->societe?->raison_social }}</strong>
                                </p>
                            </div>

                            @if ($errors->any())
                                <div class="alert alert-danger">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
                            @endif

                            <form method="POST" action="{{ route('client.avis.store', $r) }}">
                                @csrf
                                {{-- 5 stars, right-to-left in the markup so CSS can light up the hovered star and the ones before it --}}
                                <div class="bl-stars mb-2" role="radiogroup" aria-label="{{ __('Note') }}">
                                    @for ($n = 5; $n >= 1; $n--)
                                        <input type="radio" name="note" id="note-{{ $n }}" value="{{ $n }}" @checked((int) old('note') === $n) required>
                                        <label for="note-{{ $n }}" title="{{ trans_choice('{1} :n étoile|[2,*] :n étoiles', $n, ['n' => $n]) }}"><i class="isax isax-star-15"></i></label>
                                    @endfor
                                </div>
                                <p class="text-center text-muted fs-13 mb-4">{{ __('1 = très mauvais · 5 = excellent') }}</p>

                                <label class="form-label" for="commentaire">{{ __('Votre commentaire (facultatif)') }}</label>
                                <textarea name="commentaire" id="commentaire" rows="4" maxlength="1000" class="form-control mb-4" placeholder="{{ __('Ponctualité, confort, propreté, accueil du personnel...') }}">{{ old('commentaire') }}</textarea>

                                <button class="btn-confirm"><i class="isax isax-send-2 me-1"></i>{{ __('Publier mon avis') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
