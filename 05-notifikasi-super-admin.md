# Prompt: Fitur Notifikasi In-App — Role Super Admin

## Konteks Proyek

Proyek ini adalah aplikasi **E-Outsourcing PT Ecogreen Oleochemicals** berbasis Laravel 12
dengan arsitektur **Blade sebagai HTML shell + JS/AJAX murni** (tidak ada logika data di Blade).
Semua data dimuat via AJAX setelah halaman terbuka. Baca `STRUKTUR-FOLDER.md` sebelum menulis
kode apapun.

---

## Tujuan Task

Implementasikan fitur **notifikasi in-app** untuk role **Super Admin** yang terdiri dari:

1. **Panel overlay notifikasi** — muncul saat tombol lonceng di topbar diklik
2. **Halaman "Lihat Semua" notifikasi** — halaman penuh dengan paginasi 25 per halaman
3. **Badge count** di ikon lonceng — diperbarui via polling setiap 30 detik

---

## File yang Harus Dibuat / Dimodifikasi

### File Baru

| File | Keterangan |
|---|---|
| `resources/views/super-admin/notifikasi.blade.php` | Halaman penuh "Lihat Semua" notifikasi |
| `resources/js/super-admin/notifikasi.js` | Logic JS untuk panel overlay + halaman penuh |

### File yang Dimodifikasi

| File | Perubahan |
|---|---|
| `resources/views/layouts/app.blade.php` | Perluas kondisi markup panel overlay (tambah `super_admin`) |
| `resources/css/super-admin.css` | Tambah styling panel overlay + halaman notifikasi |
| `resources/views/super-admin/_sidebar-nav.blade.php` | Tambah link menu "Notifikasi" |
| `app/Http/Controllers/SuperAdmin/PageController.php` | Tambah method `notifikasi()` |
| `routes/web.php` | Tambah route `GET /super-admin/notifikasi` |
| `vite.config.js` | Daftarkan `resources/js/super-admin/notifikasi.js` sebagai entry point |

---

## Perhatian: Layout yang Digunakan

Role Super Admin menggunakan `layouts/app.blade.php` — **file yang sama** dengan HR dan
User Departemen. Kondisi Blade yang membungkus markup panel overlay harus **diperluas**
untuk mencakup semua tiga role:

```blade
@if(auth()->check() && in_array(auth()->user()->role, ['user_departemen', 'hr', 'super_admin']))
    <!-- markup panel overlay -->
@endif
```

> **Penting:** Jangan duplikasi markup. Jika prompt User Departemen atau HR sudah
> menambahkan blok kondisional ini, cukup tambahkan `'super_admin'` ke dalam array.
> Markup HTML panel tetap satu, yang berbeda hanya file JS dan CSS per role.

Link "Lihat semua" di dalam panel harus diisi secara dinamis oleh JS:

```javascript
const seeAllLink = document.getElementById('notif-see-all-link');
if (seeAllLink) {
  // Deteksi role dari path URL
  const path = window.location.pathname;
  if (path.startsWith('/super-admin')) seeAllLink.href = '/super-admin/notifikasi';
  else if (path.startsWith('/hr'))     seeAllLink.href = '/hr/notifikasi';
  else                                 seeAllLink.href = '/departemen/notifikasi';
}
```

---

## Spesifikasi API yang Digunakan

Semua endpoint sudah tersedia. Semua request wajib menyertakan `X-CSRF-TOKEN`.

### 1. Ambil daftar notifikasi (panel — 4 terbaru)
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
    "last_page": 2,
    "per_page": 4,
    "total": 6,
    "data": [
      {
        "id_notifikasi": 1,
        "judul": "Login baru terdeteksi",
        "isi": "Aktivitas login dari IP 192.168.1.10 pada 13 Jun 2025.",
        "jenis": "sistem",
        "id_referensi": null,
        "is_dibaca": false,
        "dibaca_pada": null,
        "created_at": "2025-06-13T10:00:00.000000Z"
      }
    ]
  }
}
```

### 2. Jumlah belum dibaca (badge)
```
GET /api/notifikasi/jumlah-baru
```

### 3. Tandai satu dibaca
```
PATCH /api/notifikasi/{id}/baca
```

### 4. Tandai semua dibaca
```
PATCH /api/notifikasi/baca-semua
```

### 5. Semua notifikasi (halaman penuh)
```
GET /api/notifikasi?per_page=25&page={n}
```

---

## Spesifikasi Fitur Detail

### A. Panel Overlay Notifikasi

**Trigger:** Klik tombol lonceng di topbar. Di `layouts/app.blade.php` tombol lonceng
tidak memiliki `id` khusus. Gunakan selector menyesuaikan:

```javascript
const btnNotif = document.querySelector('.topbar-icon-btn[aria-label="Notifikasi"]')
              || document.querySelector('.topbar-right .topbar-icon-btn');
```

**Posisi & tampilan:**
- Dropdown panel overlay di bawah ikon lonceng, pojok kanan atas
- Lebar: `360px` di desktop, `calc(100vw - 16px)` di mobile (≤640px)
- Backdrop transparan, klik di luar menutup panel
- Tampilkan **4 notifikasi terbaru**
- Setiap item: ikon jenis, judul, waktu relatif, dot merah jika belum dibaca,
  background lebih terang untuk notif belum dibaca

**Aksi:**
- Tombol **"Tandai semua dibaca"** → `PATCH /api/notifikasi/baca-semua` → refresh
- Klik item → tandai dibaca (fire-and-forget) → redirect ke URL tujuan
- Link **"Lihat semua"** → navigasi ke `/super-admin/notifikasi` (diset via JS)

**Loading state:** Skeleton loader 3 baris.

**Empty state:** Teks "Tidak ada notifikasi" + ikon lonceng kecil.

### B. Badge Count

- Gunakan class selector `.topbar-notif-dot` — tidak bergantung pada id untuk
  menghindari konflik dengan role lain yang menggunakan layout yang sama
- `jumlah_belum_dibaca > 0` → tampilkan badge, tambahkan `data-count`
- `jumlah_belum_dibaca === 0` → sembunyikan
- **Polling** setiap 30 detik

### C. Halaman Penuh (`/super-admin/notifikasi`)

**Layout:** `@extends('layouts.app')` dengan sidebar Super Admin.

**Fitur:**
- Paginasi 25 per halaman
- Filter tab: **"Semua"** | **"Belum Dibaca"** | **"Sudah Dibaca"**
- Tombol "Tandai semua dibaca"
- Klik item → tandai dibaca → redirect
- Paginasi: Prev / Next + "Halaman X dari Y"

---

## Mapping Jenis Notifikasi → URL Tujuan (Role Super Admin)

```javascript
function getNotifikasiUrl(jenis, id_referensi) {
  const map = {
    'akun'        : '/super-admin/akun',
    'konfigurasi' : '/super-admin/konfigurasi-area',
    'master_data' : '/super-admin/master-data/perusahaan',
    'auth'        : '/super-admin/audit-log',
    'sistem'      : '/super-admin/audit-log',
    'absensi'     : '/super-admin/audit-log',
    'lembur'      : '/super-admin/audit-log',
    'izin'        : '/super-admin/audit-log',
    'planning'    : '/super-admin/audit-log',
  };
  return map[jenis] || '/super-admin/dashboard';
}
```

---

## Mapping Jenis Notifikasi → Ikon

| `jenis` | Ikon (Heroicons outline) | Warna aksen |
|---|---|---|
| `akun` | `user-circle` | `var(--blue-600, #2563eb)` |
| `konfigurasi` | `cog` | `var(--violet-600, #7c3aed)` |
| `master_data` | `database` / `collection` | `var(--green-600, #16a34a)` |
| `auth` | `shield-check` | `var(--blue-500, #3b82f6)` |
| `sistem` | `information-circle` | `var(--text-muted)` |
| *(default)* | `bell` | `var(--text-muted)` |

---

## Spesifikasi Markup Panel Overlay

**Jika prompt-prompt sebelumnya (User Departemen, HR) sudah dijalankan**, kondisi yang ada:
```blade
@if(auth()->check() && in_array(auth()->user()->role, ['user_departemen', 'hr']))
```
harus diperluas menjadi:
```blade
@if(auth()->check() && in_array(auth()->user()->role, ['user_departemen', 'hr', 'super_admin']))
```
Markup panel **tidak berubah**, hanya kondisi yang diperluas.

**Jika belum ada markup panel sama sekali**, tambahkan seluruh blok:

```html
@if(auth()->check() && in_array(auth()->user()->role, ['user_departemen', 'hr', 'super_admin']))
<div id="notif-panel-overlay" aria-hidden="true">
  <div id="notif-backdrop"></div>
  <div id="notif-panel" role="dialog" aria-label="Notifikasi" aria-modal="false">

    <div class="app-notif-panel-header">
      <span class="app-notif-panel-title">Notifikasi</span>
      <div class="app-notif-panel-actions">
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

    <div class="app-notif-panel-footer">
      <a href="#" id="notif-see-all-link" class="app-notif-panel-see-all">
        Lihat semua notifikasi
      </a>
    </div>

  </div>
</div>
@endif
```

---

## Spesifikasi CSS (`resources/css/super-admin.css`)

Tambahkan di bagian paling bawah. **Jangan ubah CSS yang sudah ada.**

Jika CSS `app-notif-*` sudah ditambahkan oleh prompt HR di `hr.css`, Super Admin hanya
perlu menambahkan **override warna** yang sesuai dengan color scheme biru/indigo Super Admin:

```css
/* Override warna aksen notifikasi untuk Super Admin (blue/indigo scheme) */
.app-notif-item--unread {
  background: color-mix(in srgb, #eff6ff 60%, white);
}
.app-notif-item--unread:hover {
  background: #dbeafe;
}
.app-notif-page-tab--active {
  background: #eff6ff;
  color: #1d4ed8;
}
```

Jika CSS lengkap `app-notif-*` **belum ada** di file CSS manapun, tambahkan CSS lengkap:

```
#notif-panel-overlay             → position: fixed; inset: 0; z-index: 9500; pointer-events: none
#notif-panel-overlay.app-notif--open → pointer-events: auto
#notif-backdrop                  → position: absolute; inset: 0; background: rgba(0,0,0,0.3)
                                     opacity: 0; transition: opacity 0.2s
#notif-panel-overlay.app-notif--open #notif-backdrop → opacity: 1
#notif-panel                     → position: absolute; top: calc(topbar height + 8px); right: 16px
                                     width: 360px; max-height: 520px
                                     background: var(--surface-card); border-radius: var(--radius-xl)
                                     box-shadow: 0 8px 32px rgba(0,0,0,0.12)
                                     transform: translateY(-8px) scale(0.97); opacity: 0
                                     transition: transform 0.25s, opacity 0.2s
                                     display: flex; flex-direction: column; overflow: hidden
#notif-panel-overlay.app-notif--open #notif-panel → transform: translateY(0) scale(1); opacity: 1

.app-notif-item                  → display: flex; gap: var(--space-3)
                                     padding: var(--space-3) var(--space-4)
                                     cursor: pointer; transition: background 0.15s
                                     border-bottom: 1px solid var(--surface-border)
.app-notif-item:hover            → background: var(--surface-bg)
.app-notif-item--unread          → background: color-mix(in srgb, #eff6ff 60%, white)
.app-notif-item--unread:hover    → background: #dbeafe

.app-notif-page-tabs             → display: flex; gap: 4px
                                     padding: var(--space-3) var(--space-4)
                                     border-bottom: 1px solid var(--surface-border)
.app-notif-page-tab              → padding: 6px 16px; border-radius: var(--radius-md)
                                     font-size: 13px; font-weight: 500; cursor: pointer
                                     border: none; background: none; color: var(--text-secondary)
.app-notif-page-tab--active      → background: #eff6ff; color: #1d4ed8

@media (max-width: 640px)
  #notif-panel                   → width: calc(100vw - 16px); right: 8px
```

---

## Spesifikasi JS (`resources/js/super-admin/notifikasi.js`)

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
// -- Jika halaman ini adalah /super-admin/notifikasi --
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

### Logika `init()`

```javascript
function init() {
  const btnNotif = document.querySelector('.topbar-icon-btn[aria-label="Notifikasi"]')
                || document.querySelector('.topbar-right .topbar-icon-btn');

  if (!btnNotif) return;

  // Set URL "Lihat semua" untuk role ini
  const seeAllLink = document.getElementById('notif-see-all-link');
  if (seeAllLink) seeAllLink.href = '/super-admin/notifikasi';

  btnNotif.addEventListener('click', openPanel);
  document.getElementById('btn-tutup-notif-panel')?.addEventListener('click', closePanel);
  document.getElementById('notif-backdrop')?.addEventListener('click', closePanel);
  document.getElementById('btn-tandai-semua-baca')?.addEventListener('click', tandaiSemuaBaca);
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePanel(); });

  fetchBadgeCount();
  startPolling();

  if (window.location.pathname === '/super-admin/notifikasi') initHalamanPenuh();
}
```

### Badge update

```javascript
function updateBadge(jumlah) {
  // Gunakan class selector — lebih aman karena layout dibagi antar role
  const dot = document.querySelector('.topbar-notif-dot');
  const sidebarBadge = document.getElementById('sidebar-notif-badge-sa');

  if (jumlah > 0) {
    if (dot) { dot.style.display = 'block'; dot.dataset.count = jumlah; }
    if (sidebarBadge) {
      sidebarBadge.style.display = 'inline-flex';
      sidebarBadge.textContent = jumlah;
    }
  } else {
    if (dot) dot.style.display = 'none';
    if (sidebarBadge) sidebarBadge.style.display = 'none';
  }
}
```

### `formatWaktuRelatif(isoString)`

Implementasikan tanpa library:
- < 1 menit → "Baru saja"
- < 60 menit → "X menit yang lalu"
- < 24 jam → "X jam yang lalu"
- < 7 hari → "X hari yang lalu"
- >= 7 hari → "DD MMM YYYY" Bahasa Indonesia

### Class panel open state

```javascript
// Gunakan class yang konsisten dengan file JS role lain
const PANEL_OPEN_CLASS = 'app-notif--open';

function openPanel() {
  const overlay = document.getElementById('notif-panel-overlay');
  if (!overlay) return;
  overlay.classList.add(PANEL_OPEN_CLASS);
  overlay.setAttribute('aria-hidden', 'false');
  fetchPanelNotifikasi();
}

function closePanel() {
  const overlay = document.getElementById('notif-panel-overlay');
  if (!overlay) return;
  overlay.classList.remove(PANEL_OPEN_CLASS);
  overlay.setAttribute('aria-hidden', 'true');
}
```

---

## Spesifikasi `super-admin/notifikasi.blade.php`

```blade
@extends('layouts.app')

@section('title', 'Notifikasi')
@section('breadcrumb-parent', 'Super Admin')
@section('breadcrumb-current', 'Notifikasi')
@section('sidebar-role', 'Super Administrator')

@section('sidebar-nav')
    @include('super-admin._sidebar-nav')
@endsection

@section('content')
<div class="dashboard-wrap">

    <div class="page-header">
        <div>
            <h1 class="page-title">Notifikasi</h1>
            <p class="page-subtitle">Semua notifikasi dan pembaruan aktivitas sistem.</p>
        </div>
        <button id="btn-tandai-semua-halaman" class="btn-secondary">
            Tandai semua dibaca
        </button>
    </div>

    <!-- Tab Filter -->
    <div class="app-notif-page-tabs" role="tablist">
        <button class="app-notif-page-tab app-notif-page-tab--active"
                data-filter="" role="tab">Semua</button>
        <button class="app-notif-page-tab"
                data-filter="false" role="tab">Belum Dibaca</button>
        <button class="app-notif-page-tab"
                data-filter="true" role="tab">Sudah Dibaca</button>
    </div>

    <!-- List -->
    <div class="dash-panel dash-panel--full">
        <div id="notif-halaman-list" class="dash-panel-body">
            @for ($i = 0; $i < 5; $i++)
                <div style="display:flex;gap:12px;padding:12px 20px;">
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
    @vite(['resources/js/super-admin/notifikasi.js'])
@endpush
```

---

## Modifikasi `resources/views/super-admin/_sidebar-nav.blade.php`

Tambahkan di bagian paling bawah sidebar (setelah item "Audit Log"):

```blade
{{-- Notifikasi --}}
<a href="{{ url('/super-admin/notifikasi') }}"
   class="nav-item {{ request()->is('super-admin/notifikasi*') ? 'nav-item--active' : '' }}">
    <span class="nav-item-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/>
        </svg>
    </span>
    <span class="nav-item-label">Notifikasi</span>
    <span class="nav-badge" id="sidebar-notif-badge-sa" style="display:none;">0</span>
</a>
```

---

## Modifikasi `routes/web.php`

Tambahkan di dalam grup `role:super_admin`:

```php
Route::get('notifikasi', [SuperAdminPageController::class, 'notifikasi'])->name('notifikasi');
```

---

## Modifikasi `app/Http/Controllers/SuperAdmin/PageController.php`

Tambahkan method:

```php
/** Halaman semua notifikasi Super Admin */
public function notifikasi()
{
    return view('super-admin.notifikasi');
}
```

---

## Modifikasi `vite.config.js`

Tambahkan ke array `input` di bagian JS Super Admin:

```javascript
'resources/js/super-admin/notifikasi.js',
```

---

## Urutan Pengerjaan yang Disarankan

Karena prompt ini adalah yang terakhir dari seri 5 role, dan `layouts/app.blade.php`
sudah dimodifikasi oleh prompt sebelumnya (User Departemen dan HR), lakukan langkah
berikut secara berurutan:

1. Cek kondisi di `layouts/app.blade.php` — perluas array role jika sudah ada
2. Cek class CSS `app-notif-*` di `hr.css` — jika sudah ada, hanya tambahkan override warna di `super-admin.css`
3. Buat `super-admin/notifikasi.blade.php`
4. Buat `super-admin/notifikasi.js`
5. Update `_sidebar-nav.blade.php`
6. Update `PageController.php`, `routes/web.php`, `vite.config.js`

---

## Aturan Wajib

1. **Jangan ubah** CSS yang sudah ada di `super-admin.css` — hanya tambah di bagian bawah
2. **Jangan duplikasi** markup panel di `layouts/app.blade.php` — cukup perluas kondisi
3. Gunakan class `app-notif-*` yang konsisten dengan HR dan User Departemen
4. JS harus **defensif** — semua query selector dicek null sebelum digunakan
5. Semua AJAX wajib `X-CSRF-TOKEN` via `$.ajaxSetup`
6. Gunakan **jQuery (`$`)** — tersedia via `bootstrap.js`
7. Panel harus bisa ditutup: klik backdrop, klik tombol tutup, tekan `Escape`
8. Saat redirect — fire-and-forget `tandaiBaca()`, langsung redirect
9. Badge sidebar menggunakan id `sidebar-notif-badge-sa` (unik, tidak konflik dengan role lain)
10. Seluruh teks UI dalam **Bahasa Indonesia**
