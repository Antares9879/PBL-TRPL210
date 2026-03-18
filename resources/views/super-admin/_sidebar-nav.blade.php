{{--
    resources/views/super-admin/_sidebar-nav.blade.php
    Partial reusable — di-include oleh semua halaman Super Admin.
    Ini menghindari duplikasi nav di setiap blade file.
--}}

<div class="nav-section-label">Beranda</div>
<a href="{{ url('/super-admin/dashboard') }}"
   class="nav-item {{ request()->is('super-admin/dashboard') ? 'nav-item--active' : '' }}">
    <span class="nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 0 0 1 1h3m10-11l2 2m-2-2v10a1 1 0 0 1-1 1h-3m-6 0a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1m-6 0h6"/>
        </svg>
    </span>
    <span class="nav-item-label">Dashboard</span>
</a>

<div class="nav-section-label">Manajemen Akun</div>
<a href="{{ url('/super-admin/akun') }}"
   class="nav-item {{ request()->is('super-admin/akun*') ? 'nav-item--active' : '' }}">
    <span class="nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
        </svg>
    </span>
    <span class="nav-item-label">Pengguna</span>
</a>

<div class="nav-section-label">Master Data</div>
<a href="{{ url('/super-admin/master-data/perusahaan') }}"
   class="nav-item {{ request()->is('super-admin/master-data/perusahaan*') ? 'nav-item--active' : '' }}">
    <span class="nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4"/>
        </svg>
    </span>
    <span class="nav-item-label">Perusahaan Outsourcing</span>
</a>
<a href="{{ url('/super-admin/master-data/departemen') }}"
   class="nav-item {{ request()->is('super-admin/master-data/departemen*') ? 'nav-item--active' : '' }}">
    <span class="nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7zm0 8a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2z"/>
        </svg>
    </span>
    <span class="nav-item-label">Departemen</span>
</a>
<a href="{{ url('/super-admin/master-data/shift') }}"
   class="nav-item {{ request()->is('super-admin/master-data/shift*') ? 'nav-item--active' : '' }}">
    <span class="nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
        </svg>
    </span>
    <span class="nav-item-label">Shift & Waktu</span>
</a>

<div class="nav-section-label">Konfigurasi</div>
<a href="{{ url('/super-admin/konfigurasi-area') }}"
   class="nav-item {{ request()->is('super-admin/konfigurasi-area*') ? 'nav-item--active' : '' }}">
    <span class="nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 20l-5.447-2.724A1 1 0 0 1 3 16.382V5.618a1 1 0 0 1 1.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0 0 21 18.382V7.618a1 1 0 0 0-.553-.894L15 4m0 13V4m0 0L9 7"/>
        </svg>
    </span>
    <span class="nav-item-label">Konfigurasi Area</span>
</a>

<div class="nav-section-label">Sistem</div>
<a href="{{ url('/super-admin/audit-log') }}"
   class="nav-item {{ request()->is('super-admin/audit-log*') ? 'nav-item--active' : '' }}">
    <span class="nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9l2 2 4-4"/>
        </svg>
    </span>
    <span class="nav-item-label">Audit Log</span>
</a>
