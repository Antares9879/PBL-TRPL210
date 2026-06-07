# E-Outsourcing PT Ecogreen

<p align="center">
  <!-- Tambahkan logo aplikasi di sini jika ada -->
  <img src="path/to/logo.png" alt="E-Outsourcing Logo" width="200">
</p>

<p align="center">
  <b>Sistem Manajemen Karyawan Outsource untuk PT Ecogreen</b>
</p>

---

## 📋 Daftar Isi

- [Tentang Aplikasi](#-tentang-aplikasi)
- [Latar Belakang](#-latar-belakang)
- [Fitur Utama](#-fitur-utama)
- [Teknologi yang Digunakan](#-teknologi-yang-digunakan)
- [Requirement Sistem](#-requirement-sistem)
- [Instalasi](#-instalasi)
- [Konfigurasi](#-konfigurasi)
- [Cara Menggunakan](#-cara-menggunakan)
- [Role & Hak Akses](#-role--hak-akses)
- [Screenshot](#-screenshot)
- [Tim Pengembang](#-tim-pengembang)
- [Lisensi](#-lisensi)

---

## 📖 Tentang Aplikasi

**E-Outsourcing PT Ecogreen** adalah aplikasi web internal untuk mengelola karyawan outsource di PT Ecogreen. Aplikasi ini dirancang khusus untuk mengakomodasi kebutuhan perusahaan dalam mengelola proses absensi, pengajuan lembur, dan pengajuan izin karyawan outsource secara digital dan terintegrasi.

Aplikasi ini dikembangkan menggunakan framework Laravel 12 dengan arsitektur modern yang memisahkan antara tampilan (Blade) dan logika bisnis (REST API), sehingga mudah dikembangkan dan dipelihara.

---

## 🎯 Latar Belakang

PT Ecogreen memiliki sejumlah karyawan outsource yang tersebar di berbagai departemen dan lokasi kerja. Sebelum aplikasi ini, proses manajemen karyawan seperti:

- **Absensi** dilakukan secara manual atau dengan sistem yang belum terintegrasi
- **Pengajuan lembur dan izin** memerlukan proses yang panjang dan tidak efisien
- **Monitoring dan validasi** absensi serta pengajuan memakan waktu
- **Pelaporan dan rekap** data absensi sulit dilakukan secara real-time

Dengan adanya **E-Outsourcing**, semua proses tersebut dapat dilakukan secara digital, transparan, dan real-time, sehingga meningkatkan efisiensi operasional perusahaan.

---

## ✨ Fitur Utama

### 🎯 Fitur Unggulan

#### **Manajemen Izin dan Lembur Retroaktif**
- ✅ Pengajuan izin dan lembur dapat dilakukan secara **retroaktif** (mundur dari tanggal sekarang)
- ✅ Validasi otomatis untuk mencegah duplikasi pengajuan
- ✅ Sistem notifikasi real-time untuk persetujuan/penolakan
- ✅ History lengkap pengajuan dengan status tracking

### 📱 Fitur untuk Karyawan
- ✅ **Absensi Online** dengan validasi GPS (check-in/check-out)
- ✅ **Lihat Jadwal Kerja** yang telah ditetapkan
- ✅ **Pengajuan Lembur** dengan upload dokumen pendukung
- ✅ **Pengajuan Izin** (sakit, cuti, dll) dengan upload surat/dokumen
- ✅ **Riwayat Absensi** dengan filter berdasarkan periode
- ✅ **Dashboard Personal** dengan ringkasan kehadiran

### 🏢 Fitur untuk Admin Outsource
- ✅ **Manajemen Data Karyawan** (CRUD karyawan outsource)
- ✅ **Planning Kerja** - assign karyawan ke departemen dan shift
- ✅ **Validasi Absensi** - approve/reject absensi yang diajukan
- ✅ **Kelola Izin** - approve/reject pengajuan izin retroaktif
- ✅ **Riwayat Absensi Karyawan** dengan export ke Excel
- ✅ **Dashboard Monitoring** absensi real-time

### 👨‍💼 Fitur untuk User Departemen
- ✅ **Validasi Lembur** - approve/reject pengajuan lembur karyawan
- ✅ **Monitoring Absensi** karyawan di departemen terkait
- ✅ **Dashboard Departemen** dengan statistik kehadiran

### 👔 Fitur untuk HR
- ✅ **Verifikasi Dokumen Izin** yang diupload karyawan
- ✅ **Generate Rekap Bulanan** absensi, lembur, dan izin
- ✅ **Export Rekap ke Excel** (PhpSpreadsheet)
- ✅ **Audit Trail** untuk tracking perubahan data
- ✅ **Dashboard HR** dengan overview perusahaan

### 🔐 Fitur untuk Super Admin
- ✅ **Manajemen Akun** semua pengguna (HR, Admin Outsource, User Departemen)
- ✅ **Master Data Perusahaan** Outsource
- ✅ **Master Data Departemen** dan struktur organisasi
- ✅ **Master Data Shift** kerja
- ✅ **Konfigurasi Area Absensi** (Geofencing)
- ✅ **Audit Log** untuk semua aktivitas sistem
- ✅ **Dashboard Super Admin** dengan overview penuh sistem

### 🔔 Fitur Umum
- ✅ **Sistem Notifikasi Real-time** untuk setiap approval/rejection
- ✅ **Multi-role Authentication** dengan middleware berbasis role
- ✅ **Responsive Design** menggunakan Tailwind CSS v4
- ✅ **RESTful API** untuk semua operasi data
- ✅ **Audit Trail** untuk tracking semua perubahan data penting

---

## 🛠️ Teknologi yang Digunakan

### Backend
- **Laravel 12** - PHP Framework
- **Laravel Sanctum** - API Authentication
- **PHPSpreadsheet** - Export Excel
- **MySQL** - Database (Production)
- **SQLite** - Database (Development/Testing)

### Frontend
- **Blade Templates** - Server-side rendering
- **Tailwind CSS v4** - Utility-first CSS framework
- **Axios** - HTTP Client untuk AJAX
- **Vanilla JavaScript** - Interactivity

### Development Tools
- **Vite** - Frontend build tool
- **Laravel Pint** - Code style fixer
- **Laravel Pail** - Log viewer
- **Composer** - PHP dependency manager
- **NPM** - JavaScript package manager

---

## 💻 Requirement Sistem

### Minimum Requirements
- **PHP**: >= 8.2
- **Composer**: >= 2.0
- **Node.js**: >= 18.x
- **NPM/Yarn**: Latest version
- **MySQL**: >= 8.0 (untuk production)
- **Web Server**: Apache/Nginx
- **Extensions PHP**:
  - OpenSSL
  - PDO
  - Mbstring
  - Tokenizer
  - XML
  - Ctype
  - JSON
  - BCMath
  - Fileinfo
  - GD (untuk manipulasi gambar)
  - Zip

### Recommended
- **RAM**: Minimum 2GB
- **Storage**: Minimum 1GB free space
- **Browser**: Chrome, Firefox, Safari, Edge (versi terbaru)

---

## 📦 Instalasi

### 1. Clone Repository

```bash
git clone <repository-url>
cd PBL-TRPL210
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### 3. Setup Environment

```bash
# Copy file .env.example menjadi .env
copy .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Konfigurasi Database

Edit file `.env` dan sesuaikan konfigurasi database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecogreen_outsourcing
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 5. Migrasi Database & Seeding

```bash
# Jalankan migrasi
php artisan migrate

# Jalankan seeder (untuk data dummy)
php artisan db:seed
```

### 6. Build Assets

```bash
# Development
npm run dev

# Production
npm run build
```

### 7. Jalankan Aplikasi

```bash
# Jalankan server development
php artisan serve

# Atau gunakan script composer untuk development lengkap (server + queue + logs + vite)
composer dev
```

Aplikasi akan berjalan di `http://localhost:8000`

---

## ⚙️ Konfigurasi

### Queue Configuration

Aplikasi ini menggunakan queue untuk mengirim notifikasi. Pastikan queue worker berjalan:

```bash
php artisan queue:work --tries=1
```

Atau untuk development, gunakan:

```bash
php artisan queue:listen
```

### Storage Link

Jika menggunakan penyimpanan file (upload dokumen), buat symbolic link:

```bash
php artisan storage:link
```

### Konfigurasi Geofencing

Untuk konfigurasi area absensi (geofencing), login sebagai **Super Admin** dan akses menu **Konfigurasi Area** untuk mengatur:
- Nama lokasi
- Latitude & Longitude
- Radius area (dalam meter)

---

## 📘 Cara Menggunakan

### Alur Kerja Umum

1. **Super Admin** membuat akun untuk HR, Admin Outsource, dan User Departemen
2. **Super Admin** mengatur master data (perusahaan, departemen, shift, area)
3. **Admin Outsource** membuat data karyawan dan mengatur planning kerja
4. **Karyawan** melakukan check-in/check-out melalui halaman absensi
5. **Karyawan** mengajukan izin/lembur (termasuk retroaktif)
6. **Admin Outsource** memvalidasi absensi dan izin
7. **User Departemen** memvalidasi pengajuan lembur
8. **HR** memverifikasi dokumen dan generate rekap bulanan

### Login ke Aplikasi

Akses aplikasi melalui `http://localhost:8000` atau `https://pbl-trpl210.test:8443` dan login menggunakan kredensial berikut:

#### Kredensial Default

> **Note**: Silakan ubah kredensial ini setelah instalasi pertama untuk keamanan

| Role | Username/Email | Password |
|------|---------------|----------|
| **Super Admin** | `[ISI_DISINI]` | `[ISI_DISINI]` |
| **HR** | `[ISI_DISINI]` | `[ISI_DISINI]` |
| **Admin Outsource** | `[ISI_DISINI]` | `[ISI_DISINI]` |
| **User Departemen** | `[ISI_DISINI]` | `[ISI_DISINI]` |
| **Karyawan** | `[ISI_DISINI]` | `[ISI_DISINI]` |

---

## 👥 Role & Hak Akses

### 1. Super Admin
**Fungsi**: Mengelola sistem secara keseluruhan

**Hak Akses**:
- Manajemen akun semua pengguna (kecuali karyawan)
- Master data perusahaan outsource
- Master data departemen
- Master data shift kerja
- Konfigurasi area absensi (geofencing)
- Audit log semua aktivitas
- Dashboard overview sistem

**URL**: `/super-admin/*`

---

### 2. HR (Human Resources)
**Fungsi**: Verifikasi dokumen dan pelaporan

**Hak Akses**:
- Verifikasi dokumen izin yang diupload karyawan
- Generate rekap bulanan (absensi, lembur, izin)
- Export rekap ke Excel
- Lihat audit trail
- Dashboard HR

**URL**: `/hr/*`

---

### 3. Admin Outsource
**Fungsi**: Mengelola karyawan outsource dan validasi kehadiran

**Hak Akses**:
- CRUD data karyawan
- Planning kerja (assign karyawan ke departemen & shift)
- Validasi absensi karyawan
- Approve/reject pengajuan izin
- Lihat riwayat absensi karyawan
- Dashboard admin

**URL**: `/admin/*`

---

### 4. User Departemen
**Fungsi**: Validasi lembur dan monitoring karyawan di departemen

**Hak Akses**:
- Approve/reject pengajuan lembur
- Monitoring absensi karyawan di departemen
- Dashboard departemen

**URL**: `/departemen/*`

---

### 5. Karyawan
**Fungsi**: Melakukan absensi dan pengajuan

**Hak Akses**:
- Check-in/check-out dengan GPS
- Lihat jadwal kerja
- Ajukan lembur dengan upload dokumen
- Ajukan izin (retroaktif) dengan upload dokumen
- Lihat riwayat absensi
- Dashboard personal

**URL**: `/karyawan/*`

---

## 📸 Screenshot

### Dashboard Super Admin
![Dashboard Super Admin](path/to/screenshot/super-admin-dashboard.png)

### Dashboard HR
![Dashboard HR](path/to/screenshot/hr-dashboard.png)

### Dashboard Admin Outsource
![Dashboard Admin](path/to/screenshot/admin-dashboard.png)

### Dashboard User Departemen
![Dashboard Departemen](path/to/screenshot/departemen-dashboard.png)

### Dashboard Karyawan
![Dashboard Karyawan](path/to/screenshot/karyawan-dashboard.png)

### Absensi Check-in/Check-out
![Absensi](path/to/screenshot/absensi.png)

### Pengajuan Izin Retroaktif
![Pengajuan Izin](path/to/screenshot/pengajuan-izin.png)

### Pengajuan Lembur
![Pengajuan Lembur](path/to/screenshot/pengajuan-lembur.png)

### Rekap Bulanan & Export Excel
![Rekap](path/to/screenshot/rekap-excel.png)

---

## 🧪 Testing

Untuk menjalankan test suite:

```bash
# Jalankan semua test
php artisan test

# Atau gunakan composer script
composer test
```

---

## 📚 API Documentation

Aplikasi ini menggunakan RESTful API untuk semua operasi data. Semua endpoint API berada di `/api/*`.

### Authentication
```http
POST /api/auth/login
POST /api/auth/logout
GET  /api/auth/me
```

### Karyawan Endpoints
```http
POST /api/karyawan/absensi/check-in
POST /api/karyawan/absensi/check-out
GET  /api/karyawan/jadwal
POST /api/karyawan/lembur
POST /api/karyawan/izin
GET  /api/karyawan/riwayat-absensi
```

### Admin Endpoints
```http
GET    /api/admin/karyawan
POST   /api/admin/karyawan
PUT    /api/admin/karyawan/{id}
DELETE /api/admin/karyawan/{id}
POST   /api/admin/planning
POST   /api/admin/validasi-absensi/{id}
POST   /api/admin/validasi-izin/{id}
```

*Untuk dokumentasi lengkap, silakan lihat file `routes/api.php`*

---

## 🐛 Troubleshooting

### Error: "No application encryption key has been specified"
```bash
php artisan key:generate
```

### Error: Permission denied pada storage/logs
```bash
# Windows
icacls storage /grant Users:F /T
icacls bootstrap/cache /grant Users:F /T

# Linux/Mac
chmod -R 777 storage bootstrap/cache
```

### Assets tidak muncul
```bash
npm run build
php artisan config:clear
php artisan cache:clear
```

### Queue tidak berjalan
```bash
php artisan queue:restart
php artisan queue:work --tries=1
```

---

## 👨‍💻 Tim Pengembang

Aplikasi ini dikembangkan oleh:

**Tim PBL-TRPL210**  
Semester Genap 2025/2026  
Politeknik Negeri Batam

### Anggota Tim
- **[William Fitzgerald Valentino Djo]** - [4342511028] - [Business & System Analyst]
- **[Muhammad Rafly Rizky Wahyudi]** - [4342511002] - [Full-Stack Developer]
- **[Rivaldi Amara]** - [4342511010] - [Frontend & UI/UX Designer]
- **[Kelvin Pramana Putra]** - [4342511011] - [Quality Assurance (QA)]
- **[Akut Wibowo Tri Kristi]** - [4342511014] - [Database Designer]

### Manajer Proyek
- **[Alena Uperiati, S.T, M.Cs]** - [NIDN]

### Mitra Industri
- **PT Ecogreen Oleochemicals Batam Plantn**

---

## 📄 Lisensi

Aplikasi ini dikembangkan untuk keperluan internal **PT Ecogreen Oleochemicals Batam Plant** sebagai bagian dari Project Based Learning (PBL) Politeknik Negeri Batam.

**Copyright © 2026 Tim PBL-TRPL210 Politeknik Negeri Batam**

---

## 📞 Kontak & Support

Untuk pertanyaan, bug report, atau kontribusi, silakan hubungi:

- **Email**: [email-tim@polibatam.ac.id]
- **Repository**: [link-repository-github]
- **Documentation**: [link-dokumentasi-tambahan]

---

## 🙏 Acknowledgments

Terima kasih kepada:
- **Politeknik Negeri Batam** - untuk program PBL dan fasilitasnya
- **PT Ecogreen Oleochemicals Batam Plant** - sebagai mitra industri yang memberikan kesempatan pengembangan aplikasi
- **Laravel Community** - untuk framework yang luar biasa
- **Open Source Contributors** - untuk library dan tools yang digunakan

---

<p align="center">
  Made with ❤️ by Tim PBL-TRPL210 Politeknik Negeri Batam
</p>
