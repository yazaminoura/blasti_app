{{-- Page title banner. Params: $title, $subtitle (optional), $crumb (label of the current page) --}}
<div class="breadcrumb-bar breadcrumb-bg-04 text-center">
    <div class="container">
        <div class="row">
            <div class="col-md-12 col-12">
                <h2 class="breadcrumb-title mb-2">{{ $title }}</h2>
                @isset($subtitle)
                    <p class="mz-banner-sub mb-2">{{ $subtitle }}</p>
                @endisset
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb justify-content-center mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Accueil') }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $crumb ?? $title }}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>
