<x-app-layout>
    <!-- Breadcrumb -->
    <div class="breadcrumb-bar breadcrumb-bg-04 text-center">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-12">
                    <h2 class="breadcrumb-title mb-2">{{ __('Contactez-nous') }}</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Accueil') }}</a></li>
                            <li class="breadcrumb-item active text-primary" aria-current="page">{{ __('Contact') }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- /Breadcrumb -->

    <!-- Contact Us -->
    <section class="contact-section py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5 mb-4 mb-lg-0">
                    <div class="contact-info-wrap p-4 bg-light rounded-4 shadow-sm">
                        <div class="section-header mb-4">
                            <h2 class="fw-bold">{{ __('Prendre contact avec nous') }}</h2>
                            <p class="text-muted">{{ __('Nous sommes là pour répondre à toutes vos questions et vous aider à organiser votre prochain voyage avec BLASTI.') }}</p>
                        </div>
                        <div class="contact-info">
                            <!-- Phone Item -->
                            <div class="contact-info-item d-flex align-items-center mb-4">
                                <!-- Removed fs-4 and added a larger inline font-size -->
                                <span
                                    class="avatar avatar-lg bg-primary rounded-circle d-flex align-items-center justify-content-center me-3"
                                    style="width: 60px; height: 60px; flex-shrink: 0;">
                                    <i class="fa-solid fa-phone text-white" style="font-size: 24px;"></i>
                                </span>
                                <div class="contact-info-content">
                                    <p class="mb-0 text-muted small">{{ __('Soutien Client') }}</p>
                                    <h6 class="fw-bold mb-0"><a href="tel:{{ preg_replace('/\s+/', '', config('safar.contact.telephone')) }}">{{ config('safar.contact.telephone') }}</a></h6>
                                </div>
                            </div>

                            <!-- Email Item -->
                            <div class="contact-info-item d-flex align-items-center mb-4">
                                <span
                                    class="avatar avatar-lg bg-secondary rounded-circle d-flex align-items-center justify-content-center me-3"
                                    style="width: 60px; height: 60px; flex-shrink: 0;">
                                    <i class="fa-solid fa-envelope text-white" style="font-size: 24px;"></i>
                                </span>
                                <div class="contact-info-content">
                                    <p class="mb-0 text-muted small">{{ __('Envoyez-nous un Email') }}</p>
                                    <h6 class="fw-bold mb-0"><a href="mailto:{{ config('safar.contact.email') }}">{{ config('safar.contact.email') }}</a></h6>
                                </div>
                            </div>

                            <!-- Location Item -->
                            <div class="contact-info-item d-flex align-items-center">
                                <span
                                    class="avatar avatar-lg bg-success rounded-circle d-flex align-items-center justify-content-center me-3"
                                    style="width: 60px; height: 60px; flex-shrink: 0;">
                                    <i class="fa-solid fa-location-dot text-white" style="font-size: 24px;"></i>
                                </span>
                                <div class="contact-info-content">
                                    <p class="mb-0 text-muted small">{{ __('Notre Emplacement') }}</p>
                                    <h6 class="fw-bold mb-0">{{ __(config('safar.contact.adresse')) }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="card contact-card border-0 shadow-lg rounded-4 overflow-hidden">
                        <div class="card-body p-4 p-md-5">
                            <h4 class="fw-bold mb-4">{{ __('Envoyez-nous un message') }}</h4>

                            @if(session('success'))
                                <div class="alert alert-success border-0 shadow-sm mb-4">
                                    {{ session('success') }}
                                </div>
                            @endif

                            <form action="{{ route('contact.store') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-medium">{{ __('Nom Complet') }}</label>
                                            <input type="text" name="name" class="form-control form-control-lg bg-light border-0 @error('name') is-invalid @enderror" value="{{ old('name') }}"
                                                placeholder="{{ __('Entrez votre nom') }}" required>
                                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-medium">{{ __('Adresse Email') }}</label>
                                            <input type="email" name="email" class="form-control form-control-lg bg-light border-0 @error('email') is-invalid @enderror" value="{{ old('email') }}"
                                                placeholder="{{ __('Entrez votre email') }}" required>
                                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label fw-medium">{{ __('Sujet') }}</label>
                                            <input type="text" name="subject" class="form-control form-control-lg bg-light border-0 @error('subject') is-invalid @enderror" value="{{ old('subject') }}"
                                                placeholder="{{ __('Sujet de votre message') }}" required>
                                            @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-4">
                                            <label class="form-label fw-medium">{{ __('Message') }}</label>
                                            <textarea name="message" class="form-control form-control-lg bg-light border-0 @error('message') is-invalid @enderror" rows="5" maxlength="5000"
                                                placeholder="{{ __('Votre message ici...') }}" required>{{ old('message') }}</textarea>
                                                @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <button type="submit"
                                            class="btn btn-primary btn-lg w-100 py-3 shadow-sm rounded-3">{{ __('Envoyer le Message') }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /Contact Us -->

    <!-- FAQ -->
    <section class="section pt-0" id="faq">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-8 col-lg-10">
                    <div class="section-header section-header-four text-center mb-4">
                        <h2 class="mb-2">{!! __('<span>Questions</span> fréquentes') !!}</h2>
                        <p class="sub-title">{{ __('Les réponses aux questions que l\'on nous pose le plus souvent.') }}</p>
                    </div>
                    @include('partials.faq')
                </div>
            </div>
        </div>
    </section>
    <!-- /FAQ -->

    <!-- Map Section -->
    <div class="map-section mt-5 overflow-hidden shadow-sm" style="height: 450px;">
        <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3323.846357247714!2d-7.589843384797!3d33.573110380738!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xda7cd4778daa113%3A0x10c067d5ff5fc32!2sCasablanca!5e0!3m2!1sen!2sma!4v1620000000000!5m2!1sen!2sma"
            width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
    </div>
</x-app-layout>