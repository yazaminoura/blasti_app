  <!-- Breadcrumb -->
  <div class="breadcrumb-bar breadcrumb-bg-04 text-center">
    <div class="container">
        <div class="row">
            <div class="col-md-12 col-12">
                <h2 class="breadcrumb-title mb-2">{{ __('Mon espace') }}</h2>
                <p class="mz-banner-sub">{{ __('Vos billets, vos voyages et vos informations au même endroit.') }}</p>
            </div>
        </div>
    </div>
</div>
<!-- /Breadcrumb -->

<!-- Page Wrapper -->
<div class="content">
    <div class="container">

        <div class="row">
        <x-profile-sidebar :name="auth()->user()->name"></x-profile-sidebar>
        {{ $slot }}
        </div>

    </div>
</div>
<!-- /Page Wrapper -->
