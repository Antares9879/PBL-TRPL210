# Prompt 4 — Halaman Audit Log HR (F16)

## Konteks Proyek

Kamu adalah Laravel 12 Fullstack Developer yang bekerja pada proyek **E-Outsourcing PT Ecogreen Oleochemicals Batam Plant** (PBL-TRPL210). Baca dan pahami file-file berikut sebelum mulai:

- `STRUKTUR-FOLDER.md` — panduan wajib struktur folder, konvensi penamaan, dan arsitektur proyek
- `resources/views/layouts/app.blade.php` — layout utama yang sudah ada
- `resources/views/hr/dashboard.blade.php` — halaman HR yang sudah dibuat (ambil pola sidebar-nya)
- `resources/css/hr.css` — CSS HR yang sudah ada (jangan buat ulang, hanya tambahkan class baru)
- `resources/views/super-admin/audit-log.blade.php` — referensi pola audit log dari Super Admin
- `resources/js/super-admin/` — referensi pola JS audit log Super Admin
- `app/Http/Controllers/Api/HR/AuditLogApiController.php` — controller API yang akan di-consume

Ikuti **semua** aturan di `STRUKTUR-FOLDER.md` tanpa pengecualian.

---

## Tugas

Buat halaman **Audit Log HR** (F16) dengan tiga file:

1. `resources/views/hr/audit.blade.php`
2. `resources/js/hr/audit.js`
3. CSS tambahan dimasukkan ke `resources/css/hr.css` yang sudah ada

Tambahkan route web di `routes/web.php`:
```php
Route::get('audit', [HR\PageController::class, 'audit'])->name('audit');
```

---

## Spesifikasi Blade (`hr/audit.blade.php`)

### Struktur Layout
```
+--------------------------------------------------+
| SIDEBAR (sama dengan halaman HR lain)            |
+--------+-----------------------------------------+
| SIDEBAR | KONTEN UTAMA                            |
|         | Header: "Audit Log Approval"            |
|         +-----------------------------------------+
|         | PANEL RINGKASAN (stat cards)            |
|         | [Total Approve] [Total Reject]           |
|         | [Per Absensi]   [Per Lembur] [Per Izin] |
|         +-----------------------------------------+
|         | PANEL FILTER                            |
|         | [Tanggal Dari] [Tanggal Sampai]          |
|         | [Aksi] [Jenis Data] [Role Pelaku]        |
|         | [Search nama pelaku / catatan]           |
|         | [Terapkan] [Reset]                      |
|         +-----------------------------------------+
|         | TABS CEPAT AKSI:                        |
|         | [Semua] [Approve] [Reject] [Lainnya]    |
|         +-----------------------------------------+
|         | TABEL AUDIT LOG (paginasi)              |
|         +-----------------------------------------+
|         | PAGINASI                                |
+--------------------------------------------------+
```

### Elemen HTML Shell yang Wajib Ada
```html
<!-- Panel ringkasan stat cards -->
<div id="panel-ringkasan-audit" style="display:none;">
  <!-- Diisi setelah filter bulan/tahun diterapkan -->
  <select id="filter-bulan-ringkasan"></select>
  <select id="filter-tahun-ringkasan"></select>
  <button id="btn-load-ringkasan">Muat Ringkasan</button>

  <div id="card-total-approve"></div>
  <div id="card-total-reject"></div>
  <div id="card-stat-absensi"></div>
  <div id="card-stat-lembur"></div>
  <div id="card-stat-izin"></div>
</div>

<!-- Filter tabel -->
<input type="date" id="filter-tanggal-dari-audit">
<input type="date" id="filter-tanggal-sampai-audit">
<select id="filter-aksi-audit">
  <option value="">Semua Aksi</option>
  <option value="approve">Approve</option>
  <option value="reject">Reject</option>
  <option value="create">Create</option>
  <option value="update">Update</option>
</select>
<select id="filter-jenis-data-audit">
  <option value="">Semua Jenis Data</option>
  <option value="absensi">Absensi</option>
  <option value="lembur">Lembur</option>
  <option value="izin">Izin</option>
</select>
<select id="filter-role-pelaku-audit">
  <option value="">Semua Role</option>
  <option value="admin_outsource">Admin Outsource</option>
  <option value="user_departemen">User Departemen</option>
  <option value="hr">HR</option>
</select>
<input type="text" id="filter-search-audit" placeholder="Cari nama pelaku atau catatan...">
<button id="btn-terapkan-filter-audit">Terapkan Filter</button>
<button id="btn-reset-filter-audit">Reset</button>

<!-- Tabs cepat aksi -->
<div id="tabs-aksi-audit">
  <button class="hr-tab aktif" data-aksi="">Semua</button>
  <button class="hr-tab" data-aksi="approve">Approve</button>
  <button class="hr-tab" data-aksi="reject">Reject</button>
  <button class="hr-tab" data-aksi="update">Lainnya</button>
</div>

<!-- Tabel audit log -->
<div id="tabel-audit-log"></div>
<div id="paginasi-audit"></div>

<!-- Modal detail audit log -->
<div id="modal-detail-audit" class="hr-modal" style="display:none;">
  <div class="hr-modal-content hr-modal-lg">
    <div class="hr-modal-header">
      <h3>Detail Audit Log</h3>
      <button id="btn-tutup-modal-audit">×</button>
    </div>
    <div id="modal-audit-body"></div>
  </div>
</div>
```

---

## Spesifikasi JS (`hr/audit.js`)

### Endpoint API yang Digunakan
```
GET /api/hr/audit?tanggal_dari=&tanggal_sampai=&aksi=&jenis_data=&role_pelaku=&search=&per_page=&page=
GET /api/hr/audit/{id}
GET /api/hr/audit/ringkasan?bulan=&tahun=
```

### Alur Kerja JS
1. Load → `loadAuditLog(params)` dengan parameter default (tanpa filter, load 25 entri terbaru)
2. Inisialisasi dropdown bulan & tahun untuk panel ringkasan (default bulan & tahun ini)
3. `loadRingkasan(bulan, tahun)` → render 5 stat cards di panel ringkasan
4. Event listener filter tabel & tabs aksi → re-load tabel
5. Klik baris tabel atau tombol "Lihat Detail" → fetch `GET /api/hr/audit/{id}` → render modal

### Render Tabel Audit Log
Kolom:
| Waktu | Pelaku | Role | Aksi | Jenis Data | Referensi ID | Catatan | Detail |

- Kolom **Waktu**: tampilkan format "dd MMM YYYY, HH:mm" dan di bawahnya teks relatif "X menit lalu"
- Kolom **Aksi**: badge berwarna sesuai jenis:
  - `approve` → badge hijau
  - `reject` → badge merah
  - `create` → badge biru
  - `update` → badge kuning/amber
- Kolom **Jenis Data**: badge abu dengan label Indonesia ("Absensi", "Lembur", "Izin")
- Kolom **Detail**: tombol ikon 🔍 "Lihat" yang membuka modal
- Baris dengan aksi `reject` diberi latar merah sangat muda
- Baris dengan aksi `approve` diberi latar hijau sangat muda

### Modal Detail Audit Log
Saat HR klik "Lihat Detail", modal menampilkan:

```
+------------------------------------------+
| HEADER: Aksi + Jenis Data + Waktu Lengkap|
+------------------------------------------+
| INFO PELAKU                              |
| Nama       : [nama pengguna]             |
| Role       : [badge role]               |
| Waktu Aksi : [tanggal lengkap + jam]    |
| IP Address : [ip address]               |
+------------------------------------------+
| CATATAN                                  |
| [isi catatan atau "—" jika kosong]       |
+------------------------------------------+
| DATA SEBELUM        | DATA SESUDAH       |
| [JSON pretty-print] | [JSON pretty-print]|
| (jika ada)          | (jika ada)         |
+------------------------------------------+
| [Tutup]                                  |
+------------------------------------------+
```

**Render Data Sebelum/Sesudah:**
- Tampilkan dalam `<pre>` dengan syntax formatting sederhana (key: value per baris)
- Highlight perubahan: jika key ada di keduanya tapi nilainya berbeda, beri warna kuning pada nilai yang berubah
- Jika tidak ada data sebelum/sesudah → tampilkan teks "Tidak ada data perubahan"

### Panel Ringkasan
Setelah HR pilih bulan & tahun lalu klik "Muat Ringkasan":
```javascript
function renderRingkasan(data) {
  // data.total.approve → card "Total Approve" dengan warna hijau
  // data.total.reject → card "Total Reject" dengan warna merah
  // data.per_jenis_data.absensi → card "Absensi" approve/reject
  // data.per_jenis_data.lembur → card "Lembur" approve/reject
  // data.per_jenis_data.izin → card "Izin" approve/reject

  // Setiap card mini menampilkan:
  // [Jenis] : [N] Approve / [M] Reject
}
```

### Render Ringkasan Per Role
Di bawah stat cards, tampilkan tabel mini breakdown per role:
```
| Role             | Approve | Reject | Total |
|------------------|---------|--------|-------|
| Admin Outsource  |   45    |   3    |  48   |
| User Departemen  |   32    |   8    |  40   |
```

### Fitur Tambahan
- **Auto-refresh**: checkbox "Auto refresh (30 detik)" — jika dicentang, tabel refresh otomatis setiap 30 detik untuk memantau aktivitas real-time
- **Per Page**: dropdown untuk memilih jumlah baris (10 | 25 | 50)
- **Export ringkasan**: tombol "Salin Ringkasan" yang menyalin teks ringkasan bulan ke clipboard dalam format yang mudah dibaca

---

## CSS Tambahan untuk `hr.css`

```css
/* Modal detail audit */
.hr-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 0.5px solid var(--hr-hijau-border);
  padding-bottom: 12px;
  margin-bottom: 16px;
}

/* Data perubahan sebelum/sesudah */
.hr-diff-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.hr-diff-block {
  background: var(--hr-hijau-bg);
  border-radius: 6px;
  padding: 12px;
  font-size: 12px;
  font-family: monospace;
  overflow-x: auto;
  max-height: 300px;
  overflow-y: auto;
}
.hr-diff-changed { background: #FEF9C3; }  /* nilai yang berubah */

/* Badge aksi audit */
.hr-badge-approve { background: #DCFCE7; color: #14532D; }
.hr-badge-reject  { background: #FEE2E2; color: #7F1D1D; }
.hr-badge-create  { background: #DBEAFE; color: #1E3A5F; }
.hr-badge-update  { background: #FEF3C7; color: #78350F; }

/* Baris tabel audit */
.hr-baris-approve { background: #F0FDF4; }
.hr-baris-reject  { background: #FEF2F2; }

/* Stat cards ringkasan audit */
.hr-ringkasan-card {
  border-left: 3px solid var(--hr-hijau-utama);
  padding: 12px 16px;
  background: var(--hr-hijau-bg);
  border-radius: 0 6px 6px 0;
}
.hr-ringkasan-card.reject { border-left-color: #DC2626; background: #FEF2F2; }

/* Waktu relatif di tabel */
.hr-waktu-relatif {
  font-size: 11px;
  color: #888;
  display: block;
}

/* Auto-refresh indicator */
.hr-refresh-indicator {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--hr-hijau-utama);
}
.hr-refresh-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--hr-hijau-utama);
  animation: pulse 1.5s infinite;
}
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.3; }
}
```

---

## Catatan Tambahan
- Halaman audit log adalah **read-only** — tidak ada tombol aksi selain "Lihat Detail"
- Default filter saat halaman pertama kali dibuka: **tanpa filter tanggal**, tampilkan 25 entri terbaru dari semua jenis data yang relevan (absensi, lembur, izin)
- Saat panel ringkasan pertama kali dibuka, auto-load ringkasan untuk bulan dan tahun berjalan
- Modal detail harus menutup saat user tekan Escape
- Tabel harus memiliki state kosong yang informatif: "Tidak ada aktivitas yang cocok dengan filter yang diterapkan"
- Fitur "Salin Ringkasan" menggunakan `navigator.clipboard.writeText()` dengan fallback `document.execCommand('copy')`
- Pastikan route `audit` sudah didaftarkan di `routes/web.php` dalam group `role:hr`
- Auto-refresh harus berhenti saat modal detail sedang terbuka untuk menghindari refresh yang mengganggu
