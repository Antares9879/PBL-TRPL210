{{--
    resources/views/user-departemen/_sidebar-nav.blade.php
    Partial reusable — di-include seluruh halaman User Departemen.

    Menu scope:
      Dashboard          — Ringkasan kehadiran departemen
      F12                — Validasi pengajuan lembur karyawan
      Monitoring Absensi — Read-only monitoring kehadiran
      Notifikasi         — Notifikasi in-app
--}}

{{-- BERANDA --}}
<div class="nav-section-label">Beranda</div>
<a href="{{ url('/departemen/dashboard') }}"
   class="nav-item {{ request()->is('departemen/dashboard') ? 'nav-item--active' : '' }}">
    <span class="nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 0 0 1 1h3m10-11l2 2m-2-2v10a1 1 0 0 1-1 1h-3m-6 0a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1m-6 0h6"/>
        </svg>
    </span>
    <span class="nav-item-label">Dashboard</span>
</a>

{{-- LEMBUR --}}
<div class="nav-section-label">Validasi Lembur</div>

{{-- F12 — Validasi pengajuan lembur — badge diisi JS saat ada pending --}}
<a href="{{ url('/departemen/validasi-lembur') }}"
   class="nav-item {{ request()->is('departemen/validasi-lembur*') ? 'nav-item--active' : '' }}">
    <span class="nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
        </svg>
    </span>
    <span class="nav-item-label">Validasi Lembur</span>
    <span class="nav-badge" id="badge-lembur-pending" style="display:none;">0</span>
</a>

{{-- MONITORING --}}
<div class="nav-section-label">Monitoring</div>

{{-- Monitoring Absensi — read-only, scope departemen sendiri --}}
<a href="{{ url('/departemen/monitoring-absensi') }}"
   class="nav-item {{ request()->is('departemen/monitoring-absensi*') ? 'nav-item--active' : '' }}">
    <span class="nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"/>
        </svg>
    </span>
    <span class="nav-item-label">Monitoring Absensi</span>
</a>

{{-- NOTIFIKASI --}}
<div class="nav-section-label">Lainnya</div>

<a href="{{ url('/departemen/notifikasi') }}"
   class="nav-item {{ request()->is('departemen/notifikasi*') ? 'nav-item--active' : '' }}">
    <span class="nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/>
        </svg>
    </span>
    <span class="nav-item-label">Notifikasi</span>
    <span class="nav-badge nav-badge--rose" id="badge-notif-unread" style="display:none;">0</span>
</a>
