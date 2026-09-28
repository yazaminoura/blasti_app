<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
@include('admin.Layout.head')
<body class="sa-body">
    <div class="sa-shell">
        @include('admin.Layout.sidebar')
        <div class="sa-backdrop" data-sa-sidebar-close></div>

        <div class="sa-main">
            @include('admin.Layout.header')

            <main class="sa-content">
                @yield('content')
            </main>

            @include('admin.Layout.footer')
        </div>
    </div>

    @include('admin.Layout.script')
    @stack('scripts')
</body>
</html>
