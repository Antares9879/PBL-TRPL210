# Prompt: Fitur Notifikasi In-App — Role Admin Outsource

## Konteks Proyek

Proyek ini adalah aplikasi **E-Outsourcing PT Ecogreen Oleochemicals** berbasis Laravel 12
dengan arsitektur **Blade sebagai HTML shell + JS/AJAX murni** (tidak ada logika data di Blade).
Semua data dimuat via AJAX setelah halaman terbuka. Baca `STRUKTUR-FOLDER.md` sebelum menulis
kode apapun.

---

## Tujuan Task

Implementasikan fitur **notifikasi in-app** untuk role **Admin Outsource** yang terdiri dari:

1. **Panel overlay notifikasi** — muncul saat tombol lonceng di topbar diklik
2. **Halaman "Lihat Semua" notifikasi** — halaman penuh dengan paginasi 25 per halaman
3. **Badge count** di ikon lonceng — diperbarui via polling setiap 30 detik

---

## File yang Harus Dibuat / Dimodifikasi

### File Baru

| File | Keterangan |
|---|---|
| `resources/views/admin-outsource/notifikasi.blade.php` | Halaman penuh "Lihat Semua" notifikasi |
| `resources/js/admin-outsource/notifikasi.js` | Logic JS untuk panel overlay + halaman penuh |

### File yang Dimodifikasi

| File | Perubahan |
|---|---|
| `resources/views/layouts/admin.blade.php` | Tambah markup panel overlay notifikasi + panggil `notifikasi.js` |
| `resources/css/admin.css` | Tambah styling panel overlay + halaman notifikasi |
| `resources/views/admin-outsource/_sidebar-nav.blade.php` | Tambah link menu "Notifikasi" |
| `app/Http/Controllers/AdminOutsource/PageController.php` | Tambah method `notifikasi()` |
| `routes/web.php` | Tambah route `GET /admin/notifikasi` |
| `vite.config.js` | Daftarkan `resources/js/admin-outsource/notifikasi.js` sebagai entry point |

---

## Spesifikasi API yang Digunakan

Semua endpoint sudah tersedia di `routes/api.php` dan `app/Http/Controllers/Api/NotifikasiApiController.php`.
Semua request wajib menyertakan header `X-CSRF-TOKEN`.

### 1. Ambil daftar notifikasi (untuk panel overlay — 4 terbaru)
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
    "last_page": 5,
    "per_page": 4,
    "total": 18,
    "data": [
      {
        "id_notifikasi": 1,
        "judul": "Ahmad Surya mengajukan izin sakit",
        "isi": "Pengajuan izin Sakit pada 12 Jun 2025. Menunggu validasi Anda.",
        "jenis": "izin",
        "id_referensi": 15,
        "is_dibaca": false,
        "dibaca_pada": null,
        "created_at": "2025-06-12T08:30:00.000000Z"
      }
    ]
  }
}
```

### 2. Ambil jumlah notifikasi belum dibaca (untuk badge)
```
GET /api/notifikasi/jumlah-baru
```
Response:
```json
{
  "status": true,
  "message": "OK",
  "data": { "jumlah_belum_dibaca": 5 }
}
```

### 3. Tandai satu notifikasi sebagai dibaca
```
PATCH /api/notifikasi/{id}/baca
```

### 4. Tandai semua notifikasi sebagai dibaca
```
PATCH /api/notifikasi/baca-semua
```

### 5. Ambil semua notifikasi dengan paginasi (untuk halaman penuh)
```
GET /api/notifikasi?per_page=25&page={n}
```

---

## Spesifikasi Fitur Detail

### A. Panel Overlay Notifikasi

**Trigger:** Klik tombol lonceng `#btn-notif` di `layouts/admin.blade.php`.

**Posisi & tampilan:**
- Muncul sebagai dropdown panel overlay di bawah ikon lonceng (pojok kanan atas)
- Lebar panel: `360px` di desktop, `100vw` di mobile
- Panel memiliki backdrop transparan yang menutup panel saat diklik di luar area panel
- Tampilkan **4 notifikasi terbaru** saja
- Setiap item notifikasi menampilkan:
  - Ikon sesuai `jenis` notifikasi (lihat mapping ikon di bawah)
  - Judul notifikasi (`judul`)
  - Waktu relatif (contoh: "5 menit yang lalu") — format dari field `created_at`
  - Dot indikator merah jika `is_dibaca: false`
  - Background lebih terang untuk notifikasi yang belum dibaca

**Tombol & aksi di dalam panel:**
- Tombol **"Tandai semua dibaca"** di header panel → hit `PATCH /api/notifikasi/baca-semua` → refresh daftar dan badge
- Klik salah satu item notifikasi → tandai sebagai dibaca via `PATCH /api/notifikasi/{id}/baca` → redirect ke URL tujuan (lihat mapping URL di bawah)
- Tombol **"Lihat semua"** di footer panel → navigasi ke `/admin/notifikasi`

**Loading state:** Tampilkan skeleton loader (3 baris) saat data sedang di-fetch.

**Empty state:** Tampilkan ilustrasi sederhana + teks "Tidak ada notifikasi" jika data kosong.

### B. Badge Count di Ikon Lonceng

- Elemen badge: `<span class="topbar-notif-dot" id="notif-dot">` di `layouts/admin.blade.php`
- Saat `jumlah_belum_dibaca > 0`: tampilkan dot (`style="display:block"`) dan tambahkan `data-count="{jumlah}"`
- Saat `jumlah_belum_dibaca === 0`: sembunyikan dot (`style="display:none"`)
- **Polling:** Jalankan `setInterval` setiap **30.000ms** (30 detik) untuk hit `GET /api/notifikasi/jumlah-baru` dan perbarui badge
- Polling hanya aktif jika elemen `#btn-notif` ada di DOM

### C. Halaman Penuh "Lihat Semua" (`/admin/notifikasi`)

**Layout:** Gunakan `@extends('layouts.admin')` dengan sidebar Admin Outsource.

**Fitur:**
- Tampilkan semua notifikasi dengan paginasi **25 per halaman**
- Filter tab: **"Semua"** | **"Belum Dibaca"** | **"Sudah Dibaca"**
  - Filter "Belum Dibaca" → tambahkan query param `?is_dibaca=false`
  - Filter "Sudah Dibaca" → tambahkan query param `?is_dibaca=true`
- Tombol **"Tandai semua dibaca"** di header halaman
- Setiap item: klik → tandai dibaca → redirect ke URL tujuan
- Paginasi: render tombol Prev / Next + info "Halaman X dari Y"

---

## Mapping Jenis Notifikasi → URL Tujuan (Role Admin Outsource)

JS harus mengimplementasikan fungsi `getNotifikasiUrl(jenis, id_referensi)`:

| `jenis` | URL Tujuan |
|---|---|
| `absensi` | `/admin/validasi-absensi` |
| `izin` | `/admin/kelola-izin` |
| `planning` | `/admin/planning` |
| `lembur` | `/admin/dashboard` |
| `sistem` | `/admin/dashboard` |
| *(default / tidak dikenal)* | `/admin/dashboard` |

---

## Mapping Jenis Notifikasi → Ikon

Gunakan inline SVG (Heroicons outline):

| `jenis` | Ikon | Warna aksen |
|---|---|---|
| `absensi` | `check-circle` | `var(--amber-600, #d97706)` |
| `izin` | `document-text` | `var(--amber-500, #f59e0b)` |
| `planning` | `calendar` | `var(--amber-700, #b45309)` |
| `lembur` | `clock` | `#7c3aed` |
| `sistem` | `information-circle` | `var(--text-muted)` |
| *(default)* | `bell` | `var(--text-muted)` |

> Warna aksen menggunakan amber tone karena color scheme Admin Outsource adalah amber/orange
> (sesuai `admin.css` yang sudah ada).

---

## Spesifikasi Markup Panel Overlay

Tambahkan markup berikut ke `layouts/admin.blade.php`, tepat sebelum tag `</body>`.

```html
<!-- Panel Overlay Notifikasi -->
<div id="notif-panel-overlay" aria-hidden="true">
  <div id="notif-backdrop"></div>
  <div id="notif-panel" role="dialog" aria-label="Notifikasi" aria-modal="false">

    <div class="a-notif-panel-header">
      <span class="a-notif-panel-title">Notifikasi</span>
      <div class="a-notif-panel-actions">
        <button id="btn-tandai-semua-baca" type="button">Tandai semua dibaca</button>
        <button id="btn-tutup-notif-panel" type="button" aria-label="Tutup">
          <!-- icon X -->
        </button>
      </div>
    </div>

    <div id="notif-panel-list" role="list">
      <!-- diisi JS -->
    </div>

    <div class="a-notif-panel-footer">
      <a href="/admin/notifikasi" class="a-notif-panel-see-all">
        Lihat semua notifikasi
      </a>
    </div>

  </div>
</div>
```

> Prefix class `a-notif-*` digunakan untuk menghindari konflik dengan CSS role lain.

---

## Spesifikasi CSS (`resources/css/admin.css`)

Tambahkan di bagian paling bawah file. Gunakan CSS custom properties yang sudah ada.
**Jangan ubah CSS yang sudah ada.**

Ikuti pola visual amber/orange yang sudah ada di `admin.css`:

```
#notif-panel-overlay           → position: fixed; inset: 0; z-index: 9500; pointer-events: none
#notif-panel-overlay.a-notif--open → pointer-events: auto
#notif-backdrop                → position: absolute; inset: 0; background: rgba(0,0,0,0.3)
                                   opacity: 0; transition: opacity 0.2s
#notif-panel-overlay.a-notif--open #notif-backdrop → opacity: 1
#notif-panel                   → position: absolute; top: calc(topbar height + 8px); right: 16px
                                   width: 360px; max-height: 520px
                                   background: var(--surface-card); border-radius: var(--radius-xl)
                                   box-shadow: 0 8px 32px rgba(0,0,0,0.12)
                                   transform: translateY(-8px) scale(0.97); opacity: 0
                                   transition: transform 0.25s, opacity 0.2s
                                   display: flex; flex-direction: column; overflow: hidden
#notif-panel-overlay.a-notif--open #notif-panel → transform: translateY(0) scale(1); opacity: 1

.a-notif-item                  → display: flex; gap: var(--space-3); padding: var(--space-3) var(--space-4)
                                   cursor: pointer; transition: background 0.15s
                                   border-bottom: 1px solid var(--surface-border)
.a-notif-item:hover            → background: var(--surface-bg)
.a-notif-item--unread          → background: color-mix(in srgb, #fffbeb 70%, white)
.a-notif-item--unread:hover    → background: #fef9c3

@media (max-width: 640px)
  #notif-panel                 → width: calc(100vw - 16px); right: 8px
```

---

## Spesifikasi JS (`resources/js/admin-outsource/notifikasi.js`)

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
// -- Jika halaman ini adalah /admin/notifikasi --
// 13. initHalamanPenuh()
// 14. fetchHalamanPenuh(page, filter)
// 15. renderHalamanPenuh(data)
// 16. renderPaginasiHalaman(meta)
```

### Pola AJAX yang wajib diikuti

```javascript
$.ajaxSetup({
  headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});
```

### Logika `init()`

```javascript
function init() {
  // Selector menyesuaikan layout admin: #btn-notif, #notif-dot
  // 1. Attach event listener ke #btn-notif
  // 2. Attach ke #btn-tutup-notif-panel
  // 3. Attach ke #notif-backdrop
  // 4. Attach ke #btn-tandai-semua-baca
  // 5. fetchBadgeCount()
  // 6. startPolling()
  // 7. Cek URL → jika /admin/notifikasi → initHalamanPenuh()
}
```

### Logika `formatWaktuRelatif(isoString)`

Implementasikan sendiri tanpa library eksternal:
- < 1 menit → "Baru saja"
- < 60 menit → "X menit yang lalu"
- < 24 jam → "X jam yang lalu"
- < 7 hari → "X hari yang lalu"
- >= 7 hari → format tanggal "DD MMM YYYY" Bahasa Indonesia

---

## Spesifikasi `admin-outsource/notifikasi.blade.php`

```blade
@extends('layouts.admin')

@section('title', 'Notifikasi')
@section('breadcrumb-parent', 'Admin Outsource')
@section('breadcrumb-current', 'Notifikasi')
@section('sidebar-role', 'Admin Outsource')

@section('sidebar-nav')
    @include('admin-outsource._sidebar-nav')
@endsection

@section('content')
<div class="dashboard-wrap">

    <div class="page-header">
        <div>
            <h1 class="page-title">Notifikasi</h1>
            <p class="page-subtitle">Semua notifikasi dan pembaruan aktivitas Anda.</p>
        </div>
        <button id="btn-tandai-semua-halaman" class="btn-secondary">
            Tandai semua dibaca
        </button>
    </div>

    <!-- Tab Filter -->
    <div class="a-notif-page-tabs" role="tablist">
        <button class="a-notif-page-tab a-notif-page-tab--active"
                data-filter="" role="tab">Semua</button>
        <button class="a-notif-page-tab"
                data-filter="false" role="tab">Belum Dibaca</button>
        <button class="a-notif-page-tab"
                data-filter="true" role="tab">Sudah Dibaca</button>
    </div>

    <!-- List -->
    <div class="dash-panel dash-panel--full">
        <div id="notif-halaman-list" class="dash-panel-body">
            <!-- Skeleton -->
            @for ($i = 0; $i < 5; $i++)
                <div class="a-notif-item">
                    <div class="skeleton-wrap" style="display:flex;gap:12px;width:100%;">
                        <div class="skeleton-line"
                             style="width:36px;height:36px;flex-shrink:0;border-radius:8px;">
                        </div>
                        <div style="flex:1;">
                            <div class="skeleton-line"></div>
                            <div class="skeleton-line skeleton-line--medium"></div>
                        </div>
                    </div>
                </div>
            @endfor
        </div>
        <div id="paginasi-notif-halaman"
             style="padding: 12px 20px;"></div>
    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin-outsource/notifikasi.js'])
@endpush
```

---

## Modifikasi `routes/web.php`

Tambahkan di dalam grup `role:admin_outsource`:

```php
Route::get('notifikasi', [AdminOutsourcePageController::class, 'notifikasi'])->name('notifikasi');
```

---

## Modifikasi `app/Http/Controllers/AdminOutsource/PageController.php`

Tambahkan method:

```php
/** Halaman semua notifikasi Admin Outsource */
public function notifikasi()
{
    return view('admin-outsource.notifikasi');
}
```

---

## Modifikasi `resources/views/admin-outsource/_sidebar-nav.blade.php`

Tambahkan di bagian paling bawah sidebar:

```blade
{{-- Notifikasi --}}
<div class="nav-section-label">Lainnya</div>
<a href="{{ url('/admin/notifikasi') }}"
   class="nav-item {{ request()->is('admin/notifikasi*') ? 'nav-item--active' : '' }}">
    <span class="nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/>
        </svg>
    </span>
    <span class="nav-item-label">Notifikasi</span>
    <span class="nav-badge" id="sidebar-notif-badge" style="display:none;">0</span>
</a>
```

---

## Modifikasi `vite.config.js`

Tambahkan ke array `input` di bagian JS Admin Outsource:

```javascript
'resources/js/admin-outsource/notifikasi.js',
```

---

## Aturan Wajib

1. **Jangan ubah** CSS yang sudah ada di `admin.css` — hanya tambah di bagian bawah
2. **Jangan ubah** elemen yang sudah ada di `layouts/admin.blade.php` kecuali menambahkan markup panel sebelum `</body>`
3. Semua request AJAX wajib menyertakan `X-CSRF-TOKEN` via `$.ajaxSetup`
4. Gunakan **jQuery (`$`)** — sudah tersedia via `bootstrap.js`
5. Format response selalu `{ status, message, data }` — cek `res.status` sebelum proses
6. Panel harus bisa ditutup dengan: klik backdrop, klik tombol tutup, tekan `Escape`
7. Saat redirect setelah klik notif — fire-and-forget `tandaiBaca()`, langsung redirect
8. Badge topbar (`#notif-dot`) dan badge sidebar (`#sidebar-notif-badge`) diperbarui bersamaan
9. Seluruh teks UI dalam **Bahasa Indonesia**
10. Warna aksen konsisten dengan amber/orange scheme yang sudah ada di `admin.css`
