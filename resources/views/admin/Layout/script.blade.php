<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.17.2/dist/sweetalert2.all.min.js"></script>
<script src="{{ asset('assets/js/blasti-alert.js') }}?v={{ @filemtime(public_path('assets/js/blasti-alert.js')) }}"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="{{ asset('assets/admin/safar-admin.js') }}?v={{ @filemtime(public_path('assets/admin/safar-admin.js')) }}"></script>

@if (session('error'))
    <x-alert type="error" :message="session('error')" />
@endif

@if (session('success'))
    <x-alert type="success" :message="session('success')" />
@endif
