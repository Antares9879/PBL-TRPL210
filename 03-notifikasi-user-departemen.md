# Prompt: Fitur Notifikasi In-App — Role User Departemen

## Konteks Proyek

Proyek ini adalah aplikasi **E-Outsourcing PT Ecogreen Oleochemicals** berbasis Laravel 12
dengan arsitektur **Blade sebagai HTML shell + JS/AJAX murni** (tidak ada logika data di Blade).
Semua data dimuat via AJAX setelah halaman terbuka. Baca `STRUKTUR-FOLDER.md` sebelum menulis
kode apapun.

---

## Tujuan Task

Implementasikan fitur **notifikasi in-app** untuk role **User Departemen** yang terdiri dari:

1. **Panel overlay notifikasi** — muncul saat tombol lonceng di topbar diklik
2. **Halaman "Lihat Semua" notifikasi** — halaman penuh dengan paginasi 25 per halaman
3. **Badge count** di ikon lonceng — diperbarui via polling setiap 30 detik

> **Catatan penting:** Halaman `resources/views/user-departemen/notifikasi.blade.php` sudah
> ada sebagai placeholder kosong. File ini akan **diganti sepenuhnya** dengan implementasi
> lengkap. File JS `resources/js/user-departemen/validasi-lembur.js` sudah ada dan **tidak
> boleh diubah**.

---

## File yang Harus Dibuat / Dimodifikasi

### File Baru

| File | Keterangan |
|---|---|
| `resources/js/user-departemen/notifikasi.js` | Logic JS untuk panel overlay + halaman penuh |

### File yang Dimodifikasi / Diganti

| File | Perubahan |
|---|---|
| `resources/views/user-departemen/notifikasi.blade.php` | **Ganti seluruh isi** dengan implementasi lengkap |
| `resources/views/layouts/app.blade.php` | Tambah markup panel overlay notifikasi |
| `resources/css/departemen.css` | Tambah styling panel overlay + halaman notifikasi |
| `vite.config.js` | Daftarkan `resources/js/user-departemen/notifikasi.js` sebagai entry point |

> Route `GET /departemen/notifikasi` dan method `PageController::notifikasi()` **sudah ada**,
> tidak perlu ditambahkan lagi.

---

## Perhatian: Layout yang Digunakan

Role User Departemen menggunakan `layouts/app.blade.php` (bukan `layouts/admin.blade.php`).
Layout ini **juga digunakan oleh HR dan Super Admin**. Oleh karena itu:

- Markup panel overlay yang ditambahkan ke `layouts/app.blade.php` **harus kondisional** —
  hanya render jika role pengguna yang login adalah `user_departemen`.
- Gunakan Blade directive:

```blade
@if(auth()->check() && auth()->user()->role === 'user_departemen')
    <!-- markup panel overlay notifikasi -->
@endif
```

- CSS untuk User Departemen ada di `resources/css/departemen.css`, bukan `app.css`.
  CSS ini hanya dimuat di halaman User Departemen via `@push('styles') @vite('resources/css/departemen.css') @endpush`.

---

## Spesifikasi API yang Digunakan

Semua endpoint sudah tersedia. Semua request wajib menyertakan header `X-CSRF-TOKEN`.

### 1. Ambil daftar notifikasi (panel overlay — 4 terbaru)
```
GET /api/notifikasi?per_page=4
```
Response:
```json
{
  "status": true,
  "message": "Notifikasi berhasil dimuat.",
  "data": {
    "current_page": 1,
    "last_page": 4,
    "per_page": 4,
    "total": 14,
    "data": [
      {
        "id_notifikasi": 1,
        "judul": "Budi Santoso mengajukan lembur 12 Jun",
        "isi": "Pengajuan lembur pada 12 Jun 2025. Menunggu persetujuan Anda.",
        "jenis": "lembur",
        "id_referensi": 7,
        "is_dibaca": false,
        "dibaca_pada": null,
        "created_at": "2025-06-12T14:00:00.000000Z"
      }
    ]
  }
}
```

### 2. Ambil jumlah belum dibaca (badge)
```
GET /api/notifikasi/jumlah-baru
```

### 3. Tandai satu notifikasi dibaca
```
PATCH /api/notifikasi/{id}/baca
```

### 4. Tandai semua dibaca
```
PATCH /api/notifikasi/baca-semua
```

### 5. Semua notifikasi dengan paginasi (halaman penuh)
```
GET /api/notifikasi?per_page=25&page={n}
```

---

## Spesifikasi Fitur Detail

### A. Panel Overlay Notifikasi

**Trigger:** Klik tombol lonceng di topbar. Di `layouts/app.blade.php` elemen lonceng
tidak memiliki `id` spesifik. JS harus menggunakan selector yang menyesuaikan:

```javascript
// Cari tombol lonceng di topbar — selector fallback bertingkat
const btnNotif = document.querySelector('.topbar-icon-btn[aria-label="Notifikasi"]')
              || document.querySelector('.topbar-icon-btn');
```

**Posisi & tampilan:**
- Dropdown panel overlay di bawah ikon lonceng, pojok kanan atas
- Lebar: `360px` di desktop, `calc(100vw - 16px)` di mobile (≤640px)
- Backdrop transparan, klik di luar area panel menutup panel
- Tampilkan **4 notifikasi terbaru**
- Setiap item: ikon jenis, judul, waktu relatif, dot merah jika belum dibaca,
  background lebih terang untuk notif belum dibaca

**Aksi panel:**
- Tombol **"Tandai semua dibaca"** → `PATCH /api/notifikasi/baca-semua` → refresh
- Klik item → tandai dibaca (fire-and-forget) → redirect ke URL tujuan
- Tombol / link **"Lihat semua"** → navigasi ke `/departemen/notifikasi`

**Loading state:** Skeleton loader 3 baris saat fetch.

**Empty state:** Teks "Tidak ada notifikasi" + ikon lonceng kecil.

### B. Badge Count

- Elemen badge: `<span class="topbar-notif-dot" id="notif-dot">` — sudah ada di
  `layouts/app.blade.php` (tampak dari kode topbar yang ada)
- Juga perbarui badge sidebar: `<span class="nav-badge nav-badge--rose" id="badge-notif-unread">`
  yang sudah ada di `user-departemen/_sidebar-nav.blade.php`
- `jumlah_belum_dibaca > 0` → tampilkan kedua badge, isi dengan angka count
- `jumlah_belum_dibaca === 0` → sembunyikan keduanya
- **Polling** setiap 30 detik

### C. Halaman Penuh (`/departemen/notifikasi`)

**Layout:** `@extends('layouts.app')` dengan sidebar User Departemen.

**Fitur:**
- Paginasi 25 per halaman
- Filter tab: **"Semua"** | **"Belum Dibaca"** | **"Sudah Dibaca"**
- Tombol "Tandai semua dibaca"
- Klik item → tandai dibaca → redirect ke URL tujuan
- Paginasi: Prev / Next + "Halaman X dari Y"

---

## Mapping Jenis Notifikasi → URL Tujuan (Role User Departemen)

```javascript
function getNotifikasiUrl(jenis, id_referensi) {
  const map = {
    'lembur'  : '/departemen/validasi-lembur',
    'absensi' : '/departemen/monitoring-absensi',
    'izin'    : '/departemen/monitoring-absensi',
    'planning': '/departemen/dashboard',
    'sistem'  : '/departemen/dashboard',
  };
  return map[jenis] || '/departemen/dashboard';
}
```

---

## Mapping Jenis Notifikasi → Ikon

| `jenis` | Ikon (Heroicons outline) | Warna aksen |
|---|---|---|
| `lembur` | `clock` | `var(--teal-600, #0d9488)` |
| `absensi` | `check-circle` | `var(--teal-500, #14b8a6)` |
| `izin` | `document-text` | `var(--teal-400, #2dd4bf)` |
| `planning` | `calendar` | `var(--teal-700, #0f766e)` |
| `sistem` | `information-circle` | `var(--text-muted)` |
| *(default)* | `bell` | `var(--text-muted)` |

> Warna aksen teal karena color scheme User Departemen menggunakan teal/indigo
> (sesuai `departemen.css`).

---

## Spesifikasi Markup Panel Overlay

Tambahkan ke `layouts/app.blade.php` tepat sebelum `</body>`, dibungkus kondisi Blade:

```html
@if(auth()->check() && auth()->user()->role === 'user_departemen')
<!-- Panel Overlay Notifikasi — User Departemen -->
<div id="notif-panel-overlay" aria-hidden="true">
  <div id="notif-backdrop"></div>
  <div id="notif-panel" role="dialog" aria-label="Notifikasi" aria-modal="false">

    <div class="d-notif-panel-header">
      <span class="d-notif-panel-title">Notifikasi</span>
      <div class="d-notif-panel-actions">
        <button id="btn-tandai-semua-baca" type="button">
          Tandai semua dibaca
        </button>
        <button id="btn-tutup-notif-panel" type="button" aria-label="Tutup panel notifikasi">
          <!-- icon X SVG -->
        </button>
      </div>
    </div>

    <div id="notif-panel-list" role="list">
      <!-- diisi JS -->
    </div>

    <div class="d-notif-panel-footer">
      <a href="/departemen/notifikasi" class="d-notif-panel-see-all">
        Lihat semua notifikasi
      </a>
    </div>

  </div>
</div>
@endif
```

> Prefix class `d-notif-*` untuk menghindari konflik dengan CSS role lain yang
> juga menggunakan `layouts/app.blade.php`.

---

## Spesifikasi CSS (`resources/css/departemen.css`)

Tambahkan di bagian paling bawah. **Jangan ubah CSS yang sudah ada.**

```
#notif-panel-overlay            → position: fixed; inset: 0; z-index: 9500; pointer-events: none
#notif-panel-overlay.d-notif--open → pointer-events: auto
#notif-backdrop                 → position: absolute; inset: 0; background: rgba(0,0,0,0.3)
                                    opacity: 0; transition: opacity 0.2s
#notif-panel-overlay.d-notif--open #notif-backdrop → opacity: 1
#notif-panel                    → position: absolute; top: calc(topbar height + 8px); right: 16px
                                    width: 360px; max-height: 520px
                                    background: var(--surface-card); border-radius: var(--radius-xl)
                                    box-shadow: 0 8px 32px rgba(0,0,0,0.12)
                                    transform: translateY(-8px) scale(0.97); opacity: 0
                                    transition: transform 0.25s, opacity 0.2s
                                    display: flex; flex-direction: column; overflow: hidden
#notif-panel-overlay.d-notif--open #notif-panel → transform: translateY(0) scale(1); opacity: 1

.d-notif-item                   → display: flex; gap: var(--space-3)
                                    padding: var(--space-3) var(--space-4)
                                    cursor: pointer; transition: background 0.15s
                                    border-bottom: 1px solid var(--surface-border)
.d-notif-item:hover             → background: var(--surface-bg)
.d-notif-item--unread           → background: color-mix(in srgb, #f0fdfa 60%, white)
.d-notif-item--unread:hover     → background: #ccfbf1

.d-notif-page-tabs              → display: flex; gap: 4px
                                    padding: var(--space-3) var(--space-4)
                                    border-bottom: 1px solid var(--surface-border)
.d-notif-page-tab               → padding: 6px 16px; border-radius: var(--radius-md)
                                    font-size: 13px; font-weight: 500; cursor: pointer
                                    border: none; background: none; color: var(--text-secondary)
.d-notif-page-tab--active       → background: #f0fdfa; color: #0f766e

@media (max-width: 640px)
  #notif-panel                  → width: calc(100vw - 16px); right: 8px
```

---

## Spesifikasi JS (`resources/js/user-departemen/notifikasi.js`)

### Struktur modul

```javascript
// 1. State
// 2. init()
// 3. fetchBadgeCount()
// 4. fetchPanelNotifikasi()
// 5. renderPanelItems(items)
// 6. openPanel() / closePanel()
// 7. tandaiBaca(id)
// 8. tandaiSemuaBaca()
// 9. getNotifikasiUrl(jenis, id_referensi)
// 10. getIkonSvg(jenis)
// 11. formatWaktuRelatif(isoString)
// 12. startPolling()
// -- Jika halaman ini adalah /departemen/notifikasi --
// 13. initHalamanPenuh()
// 14. fetchHalamanPenuh(page, filter)
// 15. renderHalamanPenuh(data)
// 16. renderPaginasiHalaman(meta)
```

### Pola AJAX

```javascript
$.ajaxSetup({
  headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});
```

### Selector menyesuaikan `layouts/app.blade.php`

```javascript
function init() {
  // Selector bertingkat karena tombol lonceng di layouts/app.blade.php
  // tidak memiliki id khusus
  const btnNotif = document.querySelector('.topbar-icon-btn[aria-label="Notifikasi"]')
                || document.querySelector('.topbar-right .topbar-icon-btn');

  if (!btnNotif) return; // guard: jangan jalankan jika elemen tidak ada

  btnNotif.addEventListener('click', openPanel);
  // ... dst
}
```

### Logika `formatWaktuRelatif(isoString)`

Implementasikan sendiri tanpa library eksternal:
- < 1 menit → "Baru saja"
- < 60 menit → "X menit yang lalu"
- < 24 jam → "X jam yang lalu"
- < 7 hari → "X hari yang lalu"
- >= 7 hari → "DD MMM YYYY" Bahasa Indonesia

### Badge update

```javascript
function updateBadge(jumlah) {
  // Update #notif-dot di topbar (layouts/app.blade.php)
  const dot = document.getElementById('notif-dot');
  // Update #badge-notif-unread di sidebar (_sidebar-nav.blade.php)
  const sidebarBadge = document.getElementById('badge-notif-unread');

  if (jumlah > 0) {
    if (dot) { dot.style.display = 'block'; dot.dataset.count = jumlah; }
    if (sidebarBadge) { sidebarBadge.style.display = 'inline-flex'; sidebarBadge.textContent = jumlah; }
  } else {
    if (dot) dot.style.display = 'none';
    if (sidebarBadge) sidebarBadge.style.display = 'none';
  }
}
```

---

## Spesifikasi `user-departemen/notifikasi.blade.php` (Ganti Seluruh Isi)

```blade
@extends('layouts.app')

@section('title', 'Notifikasi')
@section('breadcrumb-parent', 'User Departemen')
@section('breadcrumb-current', 'Notifikasi')
@section('sidebar-role', 'User Departemen')

@section('sidebar-nav')
    @include('user-departemen._sidebar-nav')
@endsection

@push('styles')
    @vite('resources/css/departemen.css')
@endpush

@section('content')
<div class="dashboard-wrap">

    <div class="page-header">
        <div>
            <h1 class="page-title">Notifikasi</h1>
            <p class="page-subtitle">
                Semua notifikasi dan pembaruan aktivitas Anda.
            </p>
        </div>
        <button id="btn-tandai-semua-halaman" class="btn-secondary">
            Tandai semua dibaca
        </button>
    </div>

    <!-- Tab Filter -->
    <div class="d-notif-page-tabs" role="tablist">
        <button class="d-notif-page-tab d-notif-page-tab--active"
                data-filter="" role="tab">Semua</button>
        <button class="d-notif-page-tab"
                data-filter="false" role="tab">Belum Dibaca</button>
        <button class="d-notif-page-tab"
                data-filter="true" role="tab">Sudah Dibaca</button>
    </div>

    <!-- List -->
    <div class="dash-panel dash-panel--full">
        <div id="notif-halaman-list" class="dash-panel-body">
            @for ($i = 0; $i < 5; $i++)
                <div class="d-notif-item" style="display:flex;gap:12px;padding:12px 20px;">
                    <div class="skeleton-line"
                         style="width:36px;height:36px;flex-shrink:0;border-radius:8px;">
                    </div>
                    <div style="flex:1;">
                        <div class="skeleton-line"></div>
                        <div class="skeleton-line skeleton-line--medium"></div>
                    </div>
                </div>
            @endfor
        </div>
        <div id="paginasi-notif-halaman" style="padding:12px 20px;"></div>
    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/user-departemen/notifikasi.js'])
@endpush
```

---

## Modifikasi `vite.config.js`

Tambahkan ke array `input` di bagian JS User Departemen:

```javascript
'resources/js/user-departemen/notifikasi.js',
```

---

## Aturan Wajib

1. **Jangan ubah** CSS yang sudah ada di `departemen.css` — hanya tambah di bagian bawah
2. **Jangan ubah** `_sidebar-nav.blade.php` User Departemen — badge `#badge-notif-unread` sudah ada, cukup update via JS
3. Markup panel di `layouts/app.blade.php` **wajib dibungkus kondisi** `@if(auth()->user()->role === 'user_departemen')` agar tidak muncul di halaman HR dan Super Admin
4. Gunakan prefix class `d-notif-*` untuk semua class CSS baru agar tidak konflik
5. Semua AJAX wajib menyertakan `X-CSRF-TOKEN` via `$.ajaxSetup`
6. Gunakan **jQuery (`$`)** — sudah tersedia via `bootstrap.js`
7. Panel harus bisa ditutup: klik backdrop, klik tombol tutup, tekan `Escape`
8. Saat redirect setelah klik notif — fire-and-forget `tandaiBaca()`, langsung redirect
9. JS harus defensif terhadap kemungkinan elemen tidak ditemukan (gunakan optional chaining atau null check)
10. Seluruh teks UI dalam **Bahasa Indonesia**
