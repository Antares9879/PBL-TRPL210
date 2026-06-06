# Prompt: Fitur Notifikasi In-App — Role Karyawan

## Konteks Proyek

Proyek ini adalah aplikasi **E-Outsourcing PT Ecogreen Oleochemicals** berbasis Laravel 12
dengan arsitektur **Blade sebagai HTML shell + JS/AJAX murni** (tidak ada logika data di Blade).
Semua data dimuat via AJAX setelah halaman terbuka. Baca `STRUKTUR-FOLDER.md` sebelum menulis
kode apapun.

---

## Tujuan Task

Implementasikan fitur **notifikasi in-app** untuk role **Karyawan** yang terdiri dari:

1. **Panel overlay notifikasi** — muncul saat tombol lonceng di topbar diklik
2. **Halaman "Lihat Semua" notifikasi** — halaman penuh dengan paginasi 25 per halaman
3. **Badge count** di ikon lonceng — diperbarui via polling setiap 30 detik

---

## File yang Harus Dibuat / Dimodifikasi

### File Baru

| File | Keterangan |
|---|---|
| `resources/views/karyawan/notifikasi.blade.php` | Halaman penuh "Lihat Semua" notifikasi |
| `resources/js/karyawan/notifikasi.js` | Logic JS untuk panel overlay + halaman penuh |

### File yang Dimodifikasi

| File | Perubahan |
|---|---|
| `resources/views/layouts/karyawan.blade.php` | Tambah markup panel overlay notifikasi + panggil `notifikasi.js` |
| `resources/css/karyawan.css` | Tambah styling panel overlay + halaman notifikasi |
| `resources/views/karyawan/_sidebar-nav.blade.php` | Tambah link menu "Notifikasi" |
| `app/Http/Controllers/Karyawan/PageController.php` | Tambah method `notifikasi()` |
| `routes/web.php` | Tambah route `GET /karyawan/notifikasi` |
| `vite.config.js` | Daftarkan `resources/js/karyawan/notifikasi.js` sebagai entry point |

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
        "judul": "Absensi 12 Jun Anda disetujui",
        "isi": "Absensi Anda pada 12 Jun 2025 telah disetujui.",
        "jenis": "absensi",
        "id_referensi": 42,
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
  "data": { "jumlah_belum_dibaca": 3 }
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

**Trigger:** Klik tombol lonceng `#btn-notif` di `layouts/karyawan.blade.php`.

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
- Tombol **"Lihat semua"** di footer panel → navigasi ke `/karyawan/notifikasi`

**Loading state:** Tampilkan skeleton loader (3 baris) saat data sedang di-fetch.

**Empty state:** Tampilkan ilustrasi sederhana + teks "Tidak ada notifikasi" jika data kosong.

### B. Badge Count di Ikon Lonceng

- Elemen badge sudah ada di layout: `<span class="k-topbar-notif-dot" id="notif-dot">`
- Saat `jumlah_belum_dibaca > 0`: tampilkan dot merah (`style="display:block"`) dan tambahkan atribut `data-count="{jumlah}"` untuk aksesibilitas
- Saat `jumlah_belum_dibaca === 0`: sembunyikan dot (`style="display:none"`)
- **Polling:** Jalankan `setInterval` setiap **30.000ms** (30 detik) untuk hit `GET /api/notifikasi/jumlah-baru` dan perbarui badge
- Polling hanya aktif jika user sedang di halaman dengan layout karyawan (cek keberadaan `#btn-notif`)

### C. Halaman Penuh "Lihat Semua" (`/karyawan/notifikasi`)

**Layout:** Gunakan `@extends('layouts.karyawan')` — sama seperti halaman karyawan lainnya.

**Fitur:**
- Tampilkan semua notifikasi dengan paginasi **25 per halaman**
- Filter tab: **"Semua"** | **"Belum Dibaca"** | **"Sudah Dibaca"**
  - Filter "Belum Dibaca" → tambahkan query param `?is_dibaca=false`
  - Filter "Sudah Dibaca" → tambahkan query param `?is_dibaca=true`
- Tombol **"Tandai semua dibaca"** di header halaman
- Setiap item: klik → tandai dibaca → redirect ke URL tujuan
- Paginasi: render tombol Prev / Next + info "Halaman X dari Y"

---

## Mapping Jenis Notifikasi → URL Tujuan (Role Karyawan)

JS harus mengimplementasikan fungsi `getNotifikasiUrl(jenis, id_referensi)` yang mengembalikan URL berdasarkan tabel berikut:

| `jenis` | URL Tujuan |
|---|---|
| `absensi` | `/karyawan/absensi` |
| `lembur` | `/karyawan/lembur` |
| `izin` | `/karyawan/izin` |
| `planning` | `/karyawan/jadwal` |
| `sistem` | `/karyawan/dashboard` |
| *(default / tidak dikenal)* | `/karyawan/dashboard` |

> `id_referensi` untuk saat ini tidak digunakan sebagai query param, cukup redirect ke halaman
> fitur terkait. Jika di kemudian hari perlu deep-link, bisa ditambahkan sebagai `?ref={id}`.

---

## Mapping Jenis Notifikasi → Ikon

Gunakan inline SVG. Setiap `jenis` mendapat ikon dan warna aksen berbeda:

| `jenis` | Ikon (Heroicons outline) | Warna aksen CSS var |
|---|---|---|
| `absensi` | `map-pin` / lokasi GPS | `var(--eco-600)` (hijau) |
| `lembur` | `clock` | `var(--status-lembur, #7c3aed)` (violet) |
| `izin` | `document-text` | `var(--eco-500)` (biru-hijau) |
| `planning` | `calendar` | `var(--eco-700)` (hijau tua) |
| `sistem` | `information-circle` | `var(--text-muted)` (abu) |
| *(default)* | `bell` | `var(--text-muted)` |

---

## Spesifikasi Markup Panel Overlay

Tambahkan markup berikut ke `layouts/karyawan.blade.php`, tepat sebelum tag `</body>`.
Panel ini **selalu ada di DOM** tapi tersembunyi secara default:

```html
<!-- Panel Overlay Notifikasi -->
<div id="notif-panel-overlay" aria-hidden="true">
  <!-- backdrop -->
  <div id="notif-backdrop"></div>

  <!-- panel -->
  <div id="notif-panel" role="dialog" aria-label="Notifikasi" aria-modal="false">

    <!-- header -->
    <div class="k-notif-panel-header">
      <span class="k-notif-panel-title">Notifikasi</span>
      <div class="k-notif-panel-actions">
        <button id="btn-tandai-semua-baca" type="button">Tandai semua dibaca</button>
        <button id="btn-tutup-notif-panel" type="button" aria-label="Tutup panel notifikasi">
          <!-- icon X -->
        </button>
      </div>
    </div>

    <!-- list -->
    <div id="notif-panel-list" role="list">
      <!-- diisi JS -->
    </div>

    <!-- footer -->
    <div class="k-notif-panel-footer">
      <a href="/karyawan/notifikasi" class="k-notif-panel-see-all">
        Lihat semua notifikasi
      </a>
    </div>

  </div>
</div>
```

---

## Spesifikasi CSS (`resources/css/karyawan.css`)

Tambahkan CSS baru di bagian paling bawah file. Gunakan CSS custom properties yang sudah ada
(`--eco-*`, `--surface-*`, `--text-*`, `--radius-*`, `--space-*`). **Jangan ubah CSS yang sudah ada.**

### Panel overlay

```
#notif-panel-overlay           → position: fixed; inset: 0; z-index: 9500; pointer-events: none
#notif-panel-overlay.k-notif--open → pointer-events: auto
#notif-backdrop                → position: absolute; inset: 0; background: rgba(0,0,0,0.3)
                                   opacity: 0; transition: opacity 0.2s
#notif-panel-overlay.k-notif--open #notif-backdrop → opacity: 1
#notif-panel                   → position: absolute; top: calc(topbar height + 8px); right: 16px
                                   width: 360px; max-height: 520px
                                   background: var(--surface-card); border-radius: var(--radius-xl)
                                   box-shadow: 0 8px 32px rgba(0,0,0,0.12)
                                   transform: translateY(-8px) scale(0.97); opacity: 0
                                   transition: transform 0.25s, opacity 0.2s
                                   display: flex; flex-direction: column; overflow: hidden
#notif-panel-overlay.k-notif--open #notif-panel → transform: translateY(0) scale(1); opacity: 1
```

### Item notifikasi

```
.k-notif-item                  → display: flex; gap: var(--space-3); padding: var(--space-3) var(--space-4)
                                   cursor: pointer; transition: background 0.15s
                                   border-bottom: 1px solid var(--surface-border)
.k-notif-item:hover            → background: var(--surface-bg)
.k-notif-item--unread          → background: color-mix(in srgb, var(--eco-50) 60%, white)
.k-notif-item--unread:hover    → background: var(--eco-50)
.k-notif-icon-wrap             → width: 36px; height: 36px; border-radius: var(--radius-md)
                                   display: flex; align-items: center; justify-content: center
                                   flex-shrink: 0; background: var(--surface-bg)
.k-notif-unread-dot            → width: 8px; height: 8px; border-radius: 50%
                                   background: var(--eco-500); flex-shrink: 0; margin-top: 6px
```

### Mobile responsive

```
@media (max-width: 640px)
  #notif-panel                 → width: calc(100vw - 16px); right: 8px
```

### Halaman penuh

```
.k-notif-page-tabs             → display: flex; gap: 4px; padding: var(--space-3) var(--space-4)
                                   border-bottom: 1px solid var(--surface-border)
.k-notif-page-tab              → padding: 6px 16px; border-radius: var(--radius-md)
                                   font-size: 13px; font-weight: 500; cursor: pointer
                                   border: none; background: none; color: var(--text-secondary)
.k-notif-page-tab--active      → background: var(--eco-50); color: var(--eco-700)
```

---

## Spesifikasi JS (`resources/js/karyawan/notifikasi.js`)

### Struktur modul

```javascript
// 1. State
// 2. Init — dipanggil saat DOMContentLoaded
// 3. fetchBadgeCount()       — GET /api/notifikasi/jumlah-baru
// 4. fetchPanelNotifikasi()  — GET /api/notifikasi?per_page=4
// 5. renderPanelItems(items) — render item di panel overlay
// 6. openPanel() / closePanel()
// 7. tandaiBaca(id)          — PATCH /api/notifikasi/{id}/baca
// 8. tandaiSemuaBaca()       — PATCH /api/notifikasi/baca-semua
// 9. getNotifikasiUrl(jenis, id_referensi) — mapping URL
// 10. getIkonSvg(jenis)      — mapping SVG string
// 11. formatWaktuRelatif(isoString) — "5 menit yang lalu"
// 12. startPolling()         — setInterval 30 detik
// -- Jika halaman ini adalah /karyawan/notifikasi --
// 13. initHalamanPenuh()
// 14. fetchHalamanPenuh(page, filter)
// 15. renderHalamanPenuh(data)
// 16. renderPaginasiHalaman(meta)
```

### Pola AJAX yang wajib diikuti

```javascript
// Setup CSRF sekali di atas file
$.ajaxSetup({
  headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});

// Contoh fetch
$.get('/api/notifikasi/jumlah-baru', function(res) {
  if (res.status) updateBadge(res.data.jumlah_belum_dibaca);
});
```

### Logika `init()`

```javascript
function init() {
  // 1. Attach event listener ke #btn-notif (panel toggle)
  // 2. Attach event listener ke #btn-tutup-notif-panel
  // 3. Attach event listener ke #notif-backdrop (klik backdrop = tutup)
  // 4. Attach event listener ke #btn-tandai-semua-baca
  // 5. fetchBadgeCount() — langsung saat halaman load
  // 6. startPolling()
  // 7. Cek apakah URL saat ini adalah /karyawan/notifikasi
  //    Jika ya → initHalamanPenuh()
}
```

### Logika `renderPanelItems(items)`

```javascript
// Jika items kosong → tampilkan empty state
// Untuk setiap item:
//   - Buat elemen .k-notif-item (tambah .k-notif-item--unread jika !is_dibaca)
//   - Isi dengan ikon, judul, waktu relatif, dan unread dot jika perlu
//   - Bind event click:
//       1. tandaiBaca(item.id_notifikasi)
//       2. window.location.href = getNotifikasiUrl(item.jenis, item.id_referensi)
```

### Logika `formatWaktuRelatif(isoString)`

Implementasikan sendiri tanpa library eksternal:
- < 1 menit → "Baru saja"
- < 60 menit → "X menit yang lalu"
- < 24 jam → "X jam yang lalu"
- < 7 hari → "X hari yang lalu"
- >= 7 hari → format tanggal "DD MMM YYYY" dalam Bahasa Indonesia

### Logika `initHalamanPenuh()`

```javascript
// Hanya dijalankan jika window.location.pathname === '/karyawan/notifikasi'
// 1. Bind tab filter (Semua / Belum Dibaca / Sudah Dibaca)
// 2. Bind tombol "Tandai semua dibaca" di halaman
// 3. fetchHalamanPenuh(page=1, filter=null)
```

---

## Spesifikasi `karyawan/notifikasi.blade.php`

```blade
@extends('layouts.karyawan')

@section('title', 'Notifikasi')
@section('breadcrumb-parent', 'Karyawan')
@section('breadcrumb-current', 'Notifikasi')

@section('content')
<div class="k-wrap">

  <!-- Page Header -->
  <div class="k-page-header k-anim-up">
    <div>
      <h1 class="k-page-title">Notifikasi</h1>
      <p class="k-page-subtitle">Semua notifikasi dan pembaruan aktivitas Anda.</p>
    </div>
    <button id="btn-tandai-semua-halaman" class="k-btn k-btn--outline k-btn--sm">
      Tandai semua dibaca
    </button>
  </div>

  <!-- Tab Filter -->
  <div class="k-notif-page-tabs" role="tablist">
    <button class="k-notif-page-tab k-notif-page-tab--active"
            data-filter="" role="tab">Semua</button>
    <button class="k-notif-page-tab"
            data-filter="false" role="tab">Belum Dibaca</button>
    <button class="k-notif-page-tab"
            data-filter="true" role="tab">Sudah Dibaca</button>
  </div>

  <!-- List Notifikasi -->
  <div class="k-card k-anim-up k-anim-up-d1">
    <div id="notif-halaman-list">
      <!-- Skeleton placeholder -->
      @for ($i = 0; $i < 5; $i++)
        <div class="k-notif-item">
          <div class="k-skel k-skel--block"
               style="width:36px;height:36px;flex-shrink:0;border-radius:var(--radius-md);">
          </div>
          <div style="flex:1;display:flex;flex-direction:column;gap:6px;">
            <div class="k-skel k-skel--text" style="width:70%;"></div>
            <div class="k-skel k-skel--text" style="width:40%;"></div>
          </div>
        </div>
      @endfor
    </div>
    <!-- Paginasi -->
    <div id="paginasi-notif-halaman"
         style="padding: var(--space-3) var(--space-4);"></div>
  </div>

</div>
@endsection

@push('scripts')
  @vite(['resources/js/karyawan/notifikasi.js'])
@endpush
```

---

## Modifikasi `routes/web.php`

Tambahkan route berikut di dalam grup `role:karyawan`:

```php
Route::get('notifikasi', [KaryawanPageController::class, 'notifikasi'])->name('notifikasi');
```

---

## Modifikasi `app/Http/Controllers/Karyawan/PageController.php`

Tambahkan method:

```php
/** Halaman semua notifikasi karyawan */
public function notifikasi()
{
    return view('karyawan.notifikasi');
}
```

---

## Modifikasi `resources/views/karyawan/_sidebar-nav.blade.php`

Tambahkan item menu di bagian paling bawah sidebar (setelah "Riwayat Absensi"):

```blade
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
```

---

## Modifikasi `vite.config.js`

Tambahkan ke array `input` di bagian JS Karyawan:

```javascript
'resources/js/karyawan/notifikasi.js',
```

---

## Aturan Wajib

1. **Jangan ubah** struktur, class, atau id elemen yang sudah ada di `layouts/karyawan.blade.php` kecuali menambahkan markup panel overlay sebelum `</body>`
2. **Jangan ubah** CSS yang sudah ada di `karyawan.css` — hanya tambah di bagian bawah
3. Semua request AJAX wajib menyertakan `X-CSRF-TOKEN` via `$.ajaxSetup`
4. Gunakan **jQuery (`$`)** karena sudah di-setup di `bootstrap.js` proyek ini
5. Format response JSON selalu `{ status, message, data }` — cek `res.status` sebelum proses data
6. Panel overlay harus bisa ditutup dengan: klik backdrop, klik tombol tutup, atau tekan `Escape`
7. Saat `tandaiBaca()` dipanggil, jangan tunggu response sebelum redirect — fire-and-forget, langsung redirect
8. Badge di sidebar (`#sidebar-notif-badge`) dan badge di topbar (`#notif-dot`) harus diperbarui **bersamaan** setiap kali `fetchBadgeCount()` dipanggil
9. Seluruh teks UI dalam **Bahasa Indonesia**
