{{--
    resources/views/karyawan/_sidebar-nav.blade.php
    Partial reusable — di-include oleh layouts/karyawan.blade.php (sidebar desktop).

    Menu scope:
      F01  — Absensi GPS (check-in / check-out)
      F02  — Jadwal Kerja (kalender + list)
      F03  — Pengajuan Lembur
      F04  — Pengajuan Izin
      F05  — Upload Dokumen Izin (via halaman izin)
      F06  — Riwayat Absensi & Rekap
--}}

{{-- BERANDA --}}
<div class="k-sidebar-section">Beranda</div>

<a href="{{ url('/karyawan/dashboard') }}"
   class="k-nav-item {{ request()->is('karyawan/dashboard') ? 'k-nav-item--active' : '' }}"
   aria-current="{{ request()->is('karyawan/dashboard') ? 'page' : '' }}">
    <span class="k-nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 0 0 1 1h3m10-11l2 2m-2-2v10a1 1 0 0 1-1 1h-3m-6 0a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1m-6 0h6"/>
        </svg>
    </span>
    <span class="k-nav-item-label">Dashboard</span>
</a>

{{-- KEHADIRAN --}}
<div class="k-sidebar-section">Kehadiran</div>

{{-- F01 — Absensi GPS --}}
<a href="{{ url('/karyawan/absensi') }}"
   class="k-nav-item {{ request()->is('karyawan/absensi*') ? 'k-nav-item--active' : '' }}"
   aria-current="{{ request()->is('karyawan/absensi*') ? 'page' : '' }}">
    <span class="k-nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
        </svg>
    </span>
    <span class="k-nav-item-label">Absensi GPS</span>
</a>

{{-- F02 — Jadwal Kerja --}}
<a href="{{ url('/karyawan/jadwal') }}"
   class="k-nav-item {{ request()->is('karyawan/jadwal*') ? 'k-nav-item--active' : '' }}"
   aria-current="{{ request()->is('karyawan/jadwal*') ? 'page' : '' }}">
    <span class="k-nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/>
        </svg>
    </span>
    <span class="k-nav-item-label">Jadwal Kerja</span>
</a>

{{-- PENGAJUAN --}}
<div class="k-sidebar-section">Pengajuan</div>

{{-- F03 — Lembur --}}
<a href="{{ url('/karyawan/lembur') }}"
   class="k-nav-item {{ request()->is('karyawan/lembur*') ? 'k-nav-item--active' : '' }}"
   aria-current="{{ request()->is('karyawan/lembur*') ? 'page' : '' }}">
    <span class="k-nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
        </svg>
    </span>
    <span class="k-nav-item-label">Pengajuan Lembur</span>
</a>

{{-- F04 + F05 — Izin & Dokumen --}}
<a href="{{ url('/karyawan/izin') }}"
   class="k-nav-item {{ request()->is('karyawan/izin*') ? 'k-nav-item--active' : '' }}"
   aria-current="{{ request()->is('karyawan/izin*') ? 'page' : '' }}">
    <span class="k-nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
        </svg>
    </span>
    <span class="k-nav-item-label">Pengajuan Izin</span>
</a>

{{-- REKAP --}}
<div class="k-sidebar-section">Rekap</div>

{{-- F06 — Riwayat Absensi --}}
<a href="{{ url('/karyawan/riwayat') }}"
   class="k-nav-item {{ request()->is('karyawan/riwayat*') ? 'k-nav-item--active' : '' }}"
   aria-current="{{ request()->is('karyawan/riwayat*') ? 'page' : '' }}">
    <span class="k-nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
        </svg>
    </span>
    <span class="k-nav-item-label">Riwayat Absensi</span>
</a>

{{-- Notifikasi --}}
<a href="{{ url('/karyawan/notifikasi') }}"
   class="k-nav-item {{ request()->is('karyawan/notifikasi*') ? 'k-nav-item--active' : '' }}"
   aria-current="{{ request()->is('karyawan/notifikasi*') ? 'page' : '' }}">
    <span class="k-nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6 6 0 0 0-9.33-4.997M15 17v1a3 3 0 0 1-6 0v-1M6 11a6 6 0 0 1 6-6"/>
        </svg>
    </span>
    <span class="k-nav-item-label">Notifikasi</span>
    <span class="k-nav-badge" id="sidebar-notif-badge" style="display:none;">0</span>
</a>