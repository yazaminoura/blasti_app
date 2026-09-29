<!-- Sweetalert JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.17.2/dist/sweetalert2.all.min.js"></script>
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
<script src="{{asset('assets/plugins/fancybox/jquery.fancybox.min.js')}}"></script>

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
<script src="{{asset('assets/js/script.js')}}"></script>

@include('layouts.partials.wishlist-script')

{{-- Page @push scripts before reservation.js (reservation.js assumes reservation form nodes and throws on the home page, which would skip scripts below it) --}}
@stack('scripts')


<!-- Reservation script -->
<script src="{{asset('assets/js/reservation.js')}}"></script>
@if (session('error'))
<x-alert type="error" :message="session('error')" />
@endif


@if (session('success'))
<x-alert type="success" :message="session('success')" />
@endif
