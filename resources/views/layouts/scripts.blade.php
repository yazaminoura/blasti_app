<!-- Sweetalert JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.17.2/dist/sweetalert2.all.min.js"></script>
<!-- One look for every popup and toast: BlastiAlert.fire / BlastiAlert.toast -->
<script src="{{ asset('assets/js/blasti-alert.js') . '?v=' . @filemtime(public_path('assets/js/blasti-alert.js')) }}"></script>
<!-- Jquery JS -->
<script src="{{asset('assets/js/jquery-3.7.1.min.js')}}"></script>

<!-- Bootstrap JS -->
<script src="{{asset('assets/js/bootstrap.bundle.min.js')}}"></script>

<!-- Wow JS -->
<script src="{{asset('assets/js/wow.min.js')}}"></script>

<!-- MeanMenu Js -->
<script src="{{asset('assets/js/jquery.meanmenu.min.js')}}"></script>

<!-- Swiper Js -->
<script src="{{asset('assets/plugins/owlcarousel/owl.carousel.min.js')}}"></script>
@if (app()->getLocale() === 'ar')
<script>$.fn.owlCarousel.Constructor.Defaults.rtl = true;</script>
@endif

<!-- Fancybox JS -->

<!-- Counter JS -->
<script src="{{asset('assets/js/jquery.counterup.min.js')}}"></script>
<script src="{{asset('assets/js/jquery.waypoints.min.js')}}"></script>

<!-- Datepicker Core JS -->
<script src="{{asset('assets/plugins/moment/moment.js')}}"></script>
<script src="{{asset('assets/js/bootstrap-datetimepicker.min.js')}}"></script>

<!-- cursor JS -->
<script src="{{asset('assets/js/cursor.js')}}"></script>

<!-- Styled city pickers (select[data-bl-select]) -->
<script src="{{ asset('assets/js/blasti-select.js') . '?v=' . @filemtime(public_path('assets/js/blasti-select.js')) }}"></script>

<!-- Script JS -->
<script src="{{ asset('assets/js/script.js') . '?v=' . @filemtime(public_path('assets/js/script.js')) }}"></script>

@include('layouts.partials.wishlist-script')

{{-- Page @push scripts before reservation.js (reservation.js assumes reservation form nodes and throws on the home page, which would skip scripts below it) --}}
@stack('scripts')


<!-- Reservation script -->
<script src="{{asset('assets/js/reservation.js')}}"></script>
{{-- just signed up: the verification link is in the mailbox (RegisteredUserController) --}}
@if (session('inscription'))
    @php
        // built here: Blade's @json() splits its argument on commas
        $boiteMail = \App\Support\Mailbox::for(session('inscription'));
        $inscriptionPopup = [
            'type' => 'success',
            'icon' => 'isax-sms-tracking5',
            'title' => __('Compte créé !'),
            'html' => '<p class="bl-swal-text">' . __('Dernière étape : nous avons envoyé un lien à :email. Cliquez dessus pour activer votre compte et pouvoir réserver.', ['email' => '<strong>' . e(session('inscription')) . '</strong>'])
                . '</p><p class="bl-swal-text mt-2" style="font-size:.85rem">' . e(__('Rien reçu ? Regardez dans les courriers indésirables (spam).')) . '</p>',
            'links' => $boiteMail ? [['href' => $boiteMail['url'], 'label' => __('Ouvrir :boite', ['boite' => $boiteMail['nom']]), 'icon' => 'isax-sms', 'newTab' => true, 'primary' => true]] : [],
            'confirmText' => __('Compris'),
        ];
    @endphp
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.BlastiAlert) BlastiAlert.fire(@json($inscriptionPopup));
        });
    </script>
@endif

{{-- flash messages: redirect()->with('success' | 'error' | 'warning' | 'info', '...') --}}
@foreach (['error', 'warning', 'info', 'success'] as $flash)
    @if (session($flash))
        <x-alert :type="$flash" :message="session($flash)" />
    @endif
@endforeach
