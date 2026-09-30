@props(['type', 'message', 'title' => null])

{{-- Flash message (session success / error / warning / info) shown as a toast, see assets/js/blasti-alert.js --}}
@php
    $type = in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info';
    $title ??= [
        'success' => __('C\'est fait'),
        'error' => __('Impossible de continuer'),
        'warning' => __('Attention'),
        'info' => __('Pour information'),
    ][$type];
@endphp
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.BlastiAlert) {
            BlastiAlert.toast(@json($type), @json((string) $message), @json($title));
        }
    });
</script>
