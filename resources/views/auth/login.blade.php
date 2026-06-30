@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')

<div class="login-wrapper">

    {{-- ══════════════════════════════════════════════════
         PANEL KIRI — Branding PT Ecogreen
    ═══════════════════════════════════════════════════ --}}
    <div class="login-panel-left">

        {{-- Dot pattern overlay --}}
        <div class="dot-pattern"></div>

        {{-- Bagian atas: logo --}}
        <div class="login-brand anim-fade-in">
            <div class="login-brand-icon">
                <img src="{{ asset('images/logo/logo-ecogreen.webp') }}"
                alt="Logo PT Ecogreen Oleochemicals"
                class="login-brand-logo">
            </div>
            <div>
                <p class="login-brand-name">E-Outsourcing</p>
                <p class="login-brand-sub">PT Ecogreen Oleochemicals</p>
            </div>
        </div>

        {{-- Bagian tengah: hero copy --}}
        <div class="login-hero">
            <div class="login-hero-accent anim-fade-up"></div>

            <h1 class="login-hero-title anim-fade-up-d1">
                Portal Manajemen<br>
                <span>Tenaga Kerja</span><br>
                Outsource
            </h1>

            <p class="login-hero-desc anim-fade-up-d2">
                Pantau kehadiran, jadwal kerja, lembur, dan izin karyawan
                outsource secara real-time berbasis lokasi GPS.
            </p>

            {{-- Feature pills --}}
            <div class="login-pills anim-fade-up-d3">
                <span class="login-pill">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                    </svg>
                    Absensi GPS
                </span>
                <span class="login-pill">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/>
                    </svg>
                    Jadwal Kerja
                </span>
                <span class="login-pill">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
                    </svg>
                    Rekap HR
                </span>
            </div>
        </div>

        {{-- Bagian bawah: copyright --}}
        <p class="login-copyright anim-fade-up-d4">
            © {{ date('Y') }} PT Ecogreen Oleochemicals Batam Plant
        </p>

    </div>{{-- /panel kiri --}}

    {{-- ══════════════════════════════════════════════════
         PANEL KANAN — Form Login
    ═══════════════════════════════════════════════════ --}}
    <div class="login-panel-right">

        {{-- Logo mobile (hanya tampil di bawah lg) --}}
        <div class="login-mobile-logo anim-fade-in">
            <div class="login-mobile-logo-icon">
                <img src="{{ asset('images/logo/logo-ecogreen.webp') }}"
                alt="Logo PT Ecogreen Oleochemicals"
                class="login-brand-logo">
            </div>
            <div>
                <p class="login-mobile-logo-name">E-Outsourcing</p>
                <p class="login-mobile-logo-sub">PT Ecogreen Oleochemicals Batam</p>
            </div>
        </div>

        {{-- Form card --}}
        <div class="login-form-card">

            {{-- Header --}}
            <div class="login-form-header anim-fade-up">
                <h2 class="login-form-title">Selamat Datang</h2>
                <p class="login-form-subtitle">Masukkan kredensial Anda untuk melanjutkan</p>
            </div>

            {{-- Alert (diisi oleh JS) --}}
            <div id="alert-container"
                 class="login-alert login-alert--hidden"
                 role="alert"
                 aria-live="polite">
                <svg id="alert-icon" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
                <p id="alert-message"></p>
            </div>

            {{-- Fields --}}
            <div class="login-fields anim-fade-up-d1">

                {{-- Email --}}
                <div class="login-field">
                    <label for="login-email" class="login-field-label">Email</label>
                    <div class="login-field-inner">
                        <span class="login-field-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/>
                            </svg>
                        </span>
                        <input id="login-email"
                               name="email"
                               type="email"
                               autocomplete="email"
                               placeholder="nama@email.com"
                               class="login-input"
                               aria-describedby="email-error-text">
                    </div>
                    <div id="email-error" class="login-field-error" role="alert">
                        <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd"
                                  d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-7 4a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-1-9a1 1 0 0 0-1 1v4a1 1 0 1 0 2 0V6a1 1 0 0 0-1-1z"
                                  clip-rule="evenodd"/>
                        </svg>
                        <span id="email-error-text"></span>
                    </div>
                </div>

                {{-- Password --}}
                <div class="login-field">
                    <label for="login-password" class="login-field-label">Password</label>
                    <div class="login-field-inner">
                        <span class="login-field-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2zm10-10V7a4 4 0 0 0-8 0v4h8z"/>
                            </svg>
                        </span>
                        <input id="login-password"
                               name="password"
                               type="password"
                               autocomplete="current-password"
                               placeholder="••••••••"
                               class="login-input login-input--password"
                               aria-describedby="password-error-text">

                        {{--
                            Toggle password — SATU SVG saja.
                            JS akan swap path-nya antara "eye" dan "eye-off".
                            data-state="hidden" = password tersembunyi (tampilkan eye icon)
                        --}}
                        <button type="button"
                                id="toggle-password"
                                class="login-toggle-password"
                                aria-label="Tampilkan password"
                                data-state="hidden">
                            <svg id="eye-icon"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2"
                                 viewBox="0 0 24 24"
                                 aria-hidden="true">
                                {{-- Path default: eye (tampilkan) --}}
                                <path id="eye-path-main" stroke-linecap="round" stroke-linejoin="round"
                                      d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                                <path id="eye-path-outer" stroke-linecap="round" stroke-linejoin="round"
                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                {{-- Path untuk eye-off (tersembunyi secara default) --}}
                                <path id="eye-path-slash" stroke-linecap="round" stroke-linejoin="round"
                                      class="eye-hidden"
                                      d="M3 3l18 18"/>
                            </svg>
                        </button>
                    </div>
                    <div id="password-error" class="login-field-error" role="alert">
                        <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd"
                                  d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-7 4a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-1-9a1 1 0 0 0-1 1v4a1 1 0 1 0 2 0V6a1 1 0 0 0-1-1z"
                                  clip-rule="evenodd"/>
                        </svg>
                        <span id="password-error-text"></span>
                    </div>
                </div>
                
                {{-- Submit --}}
                <button id="btn-login" type="button" class="login-btn" aria-live="polite">
                    <span id="btn-login-text">Masuk ke Sistem</span>
                    <div id="btn-login-spinner" class="login-spinner" aria-hidden="true"></div>
                </button>

            </div>{{-- /fields --}}

            {{-- Divider --}}
            <div class="login-divider anim-fade-up-d2">
                <div class="login-divider-line"></div>
                <span class="login-divider-text">Akses Role</span>
                <div class="login-divider-line"></div>
            </div>

            {{-- Role chips --}}
            <div class="login-roles anim-fade-up-d3">
                <div class="login-role-chip">Karyawan</div>
                <div class="login-role-chip">Admin</div>
                <div class="login-role-chip">HR</div>
                <div class="login-role-chip">Dept.</div>
                <div class="login-role-chip">Super Admin</div>
                <div></div>{{-- filler --}}
            </div>

            {{-- Footer note --}}
            <p class="login-footer-note anim-fade-up-d4">
                Butuh bantuan? Hubungi <span>IT PT Ecogreen</span>
            </p>

        </div>{{-- /form card --}}

    </div>{{-- /panel kanan --}}

</div>{{-- /login-wrapper --}}

@endsection

@push('scripts')
    {{--
        login.js sudah didaftarkan sebagai entry point di vite.config.js.
        @vite() akan otomatis inject script tag yang benar sesuai environment.
    --}}
    @vite(['resources/js/auth/login.js', 'resources/js/login-session-check.js'])
@endpush