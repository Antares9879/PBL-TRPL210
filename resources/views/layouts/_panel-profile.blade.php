{{--
    resources/views/layouts/_profile-panel.blade.php
    Panel overlay profil pengguna — tombol logout.
    Di-include di: app.blade.php, admin.blade.php, karyawan.blade.php

    Cara include:
        @include('layouts._profile-panel')

    Letakkan di dalam <body>, setelah markup topbar, sebelum @stack('scripts').

    CSS: resources/css/app.css  (selector prefix: .profile-panel-*)
    JS:  resources/js/profile-panel.js
--}}

<div id="profile-panel-overlay" aria-hidden="true">
    <div id="profile-panel-backdrop"></div>

    <div id="profile-panel" role="dialog" aria-label="Menu profil" aria-modal="false">

        {{-- Header: info pengguna --}}
        <div class="profile-panel-header">
            <div class="profile-panel-avatar">
                {{ strtoupper(substr(auth()->user()->nama_lengkap ?? 'U', 0, 1)) }}
            </div>
            <div class="profile-panel-info">
                <span class="profile-panel-name">
                    {{ auth()->user()->nama_lengkap ?? 'Pengguna' }}
                </span>
                <span class="profile-panel-role">
                    {{ match(auth()->user()->role ?? '') {
                        'super_admin'      => 'Super Administrator',
                        'hr'               => 'HR Ecogreen',
                        'user_departemen'  => 'User Departemen',
                        'admin_outsource'  => 'Admin Outsource',
                        'karyawan'         => 'Karyawan Outsource',
                        default            => auth()->user()->role ?? '-',
                    } }}
                </span>
            </div>
        </div>

        {{-- Divider --}}
        <div class="profile-panel-divider"></div>

        {{-- Aksi --}}
        <div class="profile-panel-actions">
            <form id="profile-logout-form"
                  action="{{ route('logout') }}"
                  method="POST"
                  style="display:none;">
                @csrf
            </form>
            <button type="button"
                    class="profile-panel-btn profile-panel-btn--logout"
                    id="btn-profile-logout"
                    aria-label="Keluar dari sistem">
                <span class="profile-panel-btn-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1"/>
                    </svg>
                </span>
                <span class="profile-panel-btn-label">Keluar</span>
                <span class="profile-panel-btn-arrow">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </span>
            </button>
        </div>

    </div>
</div>