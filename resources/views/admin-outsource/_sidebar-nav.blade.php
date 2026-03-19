{{--
    resources/views/admin-outsource/_sidebar-nav.blade.php
    Partial reusable — di-include seluruh halaman Admin Outsource.

    Menu scope:
      F07        — Data Karyawan (CRUD + akun + reset password)
      F08–F09    — Planning Kerja
      F10        — Validasi Absensi (badge pending)
      F11        — Riwayat Absensi
      F04–F05 ↔  — Kelola Izin + Dokumen (badge izin pending)
--}}

{{-- BERANDA --}}
<div class="nav-section-label">Beranda</div>
<a href="{{ url('/admin/dashboard') }}"
   class="nav-item {{ request()->is('admin/dashboard') ? 'nav-item--active' : '' }}">
    <span class="nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 0 0 1 1h3m10-11l2 2m-2-2v10a1 1 0 0 1-1 1h-3m-6 0a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1m-6 0h6"/>
        </svg>
    </span>
    <span class="nav-item-label">Dashboard</span>
</a>

{{-- KARYAWAN --}}
<div class="nav-section-label">Karyawan</div>

{{-- F07 — Kelola karyawan: CRUD + aktif/nonaktif + reset password --}}
<a href="{{ url('/admin/karyawan') }}"
   class="nav-item {{ request()->is('admin/karyawan*') ? 'nav-item--active' : '' }}">
    <span class="nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
        </svg>
    </span>
    <span class="nav-item-label">Data Karyawan</span>
</a>

{{-- PLANNING --}}
<div class="nav-section-label">Jadwal Kerja</div>

{{-- F08–F09 — Planning kerja bulanan & upload jadwal --}}
<a href="{{ url('/admin/planning') }}"
   class="nav-item {{ request()->is('admin/planning*') ? 'nav-item--active' : '' }}">
    <span class="nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/>
        </svg>
    </span>
    <span class="nav-item-label">Planning Kerja</span>
</a>

{{-- ABSENSI --}}
<div class="nav-section-label">Absensi</div>

{{-- F10 — Validasi absensi harian — badge diisi JS saat ada pending --}}
<a href="{{ url('/admin/validasi-absensi') }}"
   class="nav-item {{ request()->is('admin/validasi-absensi*') ? 'nav-item--active' : '' }}">
    <span class="nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
        </svg>
    </span>
    <span class="nav-item-label">Validasi Absensi</span>
    <span class="nav-badge" id="badge-validasi-absensi" style="display:none;">0</span>
</a>

{{-- F11 — Riwayat & rekap absensi --}}
<a href="{{ url('/admin/riwayat-absensi') }}"
   class="nav-item {{ request()->is('admin/riwayat-absensi*') ? 'nav-item--active' : '' }}">
    <span class="nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
        </svg>
    </span>
    <span class="nav-item-label">Riwayat Absensi</span>
</a>

{{-- IZIN --}}
<div class="nav-section-label">Pengajuan Izin</div>

{{-- F04–F05 ↔ — Kelola izin + verifikasi dokumen — badge rose jika ada pending --}}
<a href="{{ url('/admin/kelola-izin') }}"
   class="nav-item {{ request()->is('admin/kelola-izin*') ? 'nav-item--active' : '' }}">
    <span class="nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
        </svg>
    </span>
    <span class="nav-item-label">Kelola Izin</span>
    <span class="nav-badge nav-badge--rose" id="badge-izin" style="display:none;">0</span>
</a>