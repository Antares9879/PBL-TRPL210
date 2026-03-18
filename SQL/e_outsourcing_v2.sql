-- ============================================================
-- SKEMA BASIS DATA APLIKASI E-OUTSOURCING
-- PT Ecogreen Oleochemicals Batam Plant
-- Mata Kuliah: Pemrograman Basis Data
-- Versi 2.0 — Production-Ready (Table Per Type)
-- ============================================================
-- Perubahan dari v1.0:
--   - Tabel pengguna dibersihkan: tidak lagi menyimpan id_departemen
--     dan id_perusahaan (dihapus karena melanggar prinsip SRP dan
--     menyebabkan nullable column untuk sebagian besar role)
--   - Ditambahkan tabel admin_outsource_profile untuk menyimpan
--     data spesifik role Admin Outsource (id_perusahaan)
--   - Ditambahkan tabel user_departemen_profile untuk menyimpan
--     data spesifik role User Departemen (id_departemen)
--   - Tabel karyawan tetap sebagai extension table untuk role karyawan
--   - Data integrity kini dijaga di level database, bukan hanya
--     application layer
-- ============================================================

-- ============================================================
-- TABEL MASTER DATA
-- ============================================================

-- Tabel perusahaan_outsource
-- Menyimpan data perusahaan penyedia tenaga kerja pihak ketiga
CREATE TABLE perusahaan_outsource (
    id_perusahaan   INT             PRIMARY KEY AUTO_INCREMENT,
    nama_perusahaan VARCHAR(100)    NOT NULL,
    alamat          TEXT,
    no_telepon      VARCHAR(20),
    email           VARCHAR(100)    UNIQUE,
    status          ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel departemen
-- Menyimpan data departemen di PT Ecogreen sebagai area penugasan
CREATE TABLE departemen (
    id_departemen   INT             PRIMARY KEY AUTO_INCREMENT,
    nama_departemen VARCHAR(100)    NOT NULL,
    kode_departemen VARCHAR(20)     UNIQUE NOT NULL,
    status          ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel konfigurasi_area
-- Menyimpan konfigurasi radius GPS untuk validasi lokasi absensi
CREATE TABLE konfigurasi_area (
    id_konfigurasi  INT             PRIMARY KEY AUTO_INCREMENT,
    nama_area       VARCHAR(100)    NOT NULL,
    latitude_pusat  DECIMAL(10, 8)  NOT NULL,
    longitude_pusat DECIMAL(11, 8)  NOT NULL,
    radius_meter    INT             NOT NULL CHECK (radius_meter > 0),
    is_aktif        BOOLEAN         NOT NULL DEFAULT TRUE,
    diubah_oleh     INT             NOT NULL,  -- FK ke tabel pengguna (ditambahkan setelah tabel pengguna dibuat)
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel shift
-- Menyimpan definisi shift kerja (jam masuk dan jam pulang)
CREATE TABLE shift (
    id_shift        INT             PRIMARY KEY AUTO_INCREMENT,
    nama_shift      VARCHAR(50)     NOT NULL,
    jam_masuk       TIME            NOT NULL,
    jam_pulang      TIME            NOT NULL,
    durasi_normal_menit INT         NOT NULL DEFAULT 480,
    status          ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- TABEL PENGGUNA (AUTENTIKASI MURNI)
-- ============================================================

-- Tabel pengguna
-- Menyimpan data autentikasi dan identitas dasar seluruh pengguna sistem.
-- Tabel ini HANYA berisi data yang relevan untuk SEMUA role tanpa terkecuali.
-- Data spesifik per role disimpan di tabel profil terpisah (Table Per Type):
--   role karyawan          → tabel karyawan
--   role admin_outsource   → tabel admin_outsource_profile
--   role user_departemen   → tabel user_departemen_profile
--   role hr & super_admin  → tidak ada data tambahan (cukup tabel ini)
CREATE TABLE pengguna (
    id_pengguna     INT             PRIMARY KEY AUTO_INCREMENT,
    nama_lengkap    VARCHAR(100)    NOT NULL,
    email           VARCHAR(100)    UNIQUE NOT NULL,
    password_hash   VARCHAR(255)    NOT NULL,
    role            ENUM('super_admin', 'hr', 'user_departemen', 'admin_outsource', 'karyawan')
                                    NOT NULL,
    status          ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    last_login      TIMESTAMP       NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- TABEL PROFIL PER ROLE (TABLE PER TYPE / EXTENSION TABLES)
-- ============================================================

-- Tabel admin_outsource_profile
-- Extension table untuk role admin_outsource.
-- Relasi 1:1 dengan pengguna — satu admin hanya boleh mewakili satu perusahaan outsource.
-- Dipisah dari pengguna agar:
--   1. Tidak ada nullable column di tabel pengguna
--   2. Query per-perusahaan lebih efisien (tabel lebih kecil dan terfokus)
--   3. Mudah dikembangkan jika Admin Outsource butuh atribut tambahan ke depannya
CREATE TABLE admin_outsource_profile (
    id_profile      INT             PRIMARY KEY AUTO_INCREMENT,
    id_pengguna     INT             UNIQUE NOT NULL,   -- 1:1 dengan pengguna
    id_perusahaan   INT             NOT NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_aoprofile_pengguna    FOREIGN KEY (id_pengguna)
        REFERENCES pengguna(id_pengguna) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_aoprofile_perusahaan  FOREIGN KEY (id_perusahaan)
        REFERENCES perusahaan_outsource(id_perusahaan) ON UPDATE CASCADE ON DELETE RESTRICT
);

-- Tabel user_departemen_profile
-- Extension table untuk role user_departemen.
-- Relasi 1:1 dengan pengguna — satu User Departemen bertanggung jawab atas satu departemen.
-- Dipisah dari pengguna dengan alasan yang sama seperti admin_outsource_profile.
CREATE TABLE user_departemen_profile (
    id_profile      INT             PRIMARY KEY AUTO_INCREMENT,
    id_pengguna     INT             UNIQUE NOT NULL,   -- 1:1 dengan pengguna
    id_departemen   INT             NOT NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_udprofile_pengguna    FOREIGN KEY (id_pengguna)
        REFERENCES pengguna(id_pengguna) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_udprofile_departemen  FOREIGN KEY (id_departemen)
        REFERENCES departemen(id_departemen) ON UPDATE CASCADE ON DELETE RESTRICT
);

-- Tabel karyawan
-- Extension table untuk role karyawan sekaligus menyimpan data profil
-- lengkap karyawan outsource. Dikelola oleh Admin Outsource perusahaannya.
CREATE TABLE karyawan (
    id_karyawan         INT             PRIMARY KEY AUTO_INCREMENT,
    id_pengguna         INT             UNIQUE NOT NULL,   -- 1:1 dengan pengguna
    nik                 VARCHAR(30)     UNIQUE NOT NULL,
    nomor_karyawan      VARCHAR(30)     UNIQUE NOT NULL,
    nama_lengkap        VARCHAR(100)    NOT NULL,
    posisi              VARCHAR(100)    NOT NULL,
    id_perusahaan       INT             NOT NULL,
    id_departemen       INT             NOT NULL,          -- departemen penugasan
    tanggal_bergabung   DATE            NOT NULL,
    status              ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_by          INT             NOT NULL,          -- id_pengguna Admin Outsource
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_karyawan_pengguna     FOREIGN KEY (id_pengguna)
        REFERENCES pengguna(id_pengguna) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_karyawan_perusahaan   FOREIGN KEY (id_perusahaan)
        REFERENCES perusahaan_outsource(id_perusahaan) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_karyawan_departemen   FOREIGN KEY (id_departemen)
        REFERENCES departemen(id_departemen) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_karyawan_created_by   FOREIGN KEY (created_by)
        REFERENCES pengguna(id_pengguna) ON UPDATE CASCADE ON DELETE RESTRICT
);

-- ============================================================
-- TABEL PLANNING KERJA & JADWAL
-- ============================================================

-- Tabel planning_kerja
-- Menyimpan header planning kerja per periode yang dibuat Admin Outsource
CREATE TABLE planning_kerja (
    id_planning     INT             PRIMARY KEY AUTO_INCREMENT,
    id_perusahaan   INT             NOT NULL,
    periode_bulan   TINYINT         NOT NULL CHECK (periode_bulan BETWEEN 1 AND 12),
    periode_tahun   YEAR            NOT NULL,
    status          ENUM('draft', 'aktif', 'diperbarui') NOT NULL DEFAULT 'draft',
    versi           TINYINT         NOT NULL DEFAULT 1,
    dibuat_oleh     INT             NOT NULL,   -- id_pengguna Admin Outsource
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_planning_perusahaan   FOREIGN KEY (id_perusahaan)
        REFERENCES perusahaan_outsource(id_perusahaan) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_planning_dibuat_oleh  FOREIGN KEY (dibuat_oleh)
        REFERENCES pengguna(id_pengguna) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_planning_periode      UNIQUE (id_perusahaan, periode_bulan, periode_tahun, versi)
);

-- Tabel jadwal_kerja
-- Menyimpan detail jadwal kerja harian per karyawan per planning
CREATE TABLE jadwal_kerja (
    id_jadwal       INT             PRIMARY KEY AUTO_INCREMENT,
    id_planning     INT             NOT NULL,
    id_karyawan     INT             NOT NULL,
    id_shift        INT             NOT NULL,
    tanggal_kerja   DATE            NOT NULL,
    is_hari_libur   BOOLEAN         NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_jadwal_planning   FOREIGN KEY (id_planning)
        REFERENCES planning_kerja(id_planning) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_jadwal_karyawan   FOREIGN KEY (id_karyawan)
        REFERENCES karyawan(id_karyawan) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_jadwal_shift      FOREIGN KEY (id_shift)
        REFERENCES shift(id_shift) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_jadwal_karyawan_tanggal UNIQUE (id_karyawan, tanggal_kerja, id_planning)
);

-- ============================================================
-- TABEL ABSENSI
-- ============================================================

-- Tabel absensi
-- Menyimpan data check-in dan check-out karyawan harian beserta kalkulasi menit
CREATE TABLE absensi (
    id_absensi          INT             PRIMARY KEY AUTO_INCREMENT,
    id_karyawan         INT             NOT NULL,
    id_jadwal           INT             NOT NULL,
    tanggal_absensi     DATE            NOT NULL,

    -- Data Check-In
    waktu_check_in      DATETIME        NULL,
    latitude_check_in   DECIMAL(10, 8)  NULL,
    longitude_check_in  DECIMAL(11, 8)  NULL,
    is_lokasi_valid_in  BOOLEAN         NULL,

    -- Data Check-Out
    waktu_check_out     DATETIME        NULL,
    latitude_check_out  DECIMAL(10, 8)  NULL,
    longitude_check_out DECIMAL(11, 8)  NULL,
    is_lokasi_valid_out BOOLEAN         NULL,

    -- Kalkulasi Menit (dihitung otomatis oleh sistem)
    menit_kerja_normal  INT             NOT NULL DEFAULT 0,  -- maks. 480 menit
    menit_telat         INT             NOT NULL DEFAULT 0,
    menit_pulang_cepat  INT             NOT NULL DEFAULT 0,
    menit_kelebihan     INT             NOT NULL DEFAULT 0,  -- potensi lembur (sebelum disetujui)

    -- Status Absensi
    status_kehadiran    ENUM('hadir', 'izin', 'alpa', 'pending') NOT NULL DEFAULT 'pending',
    status_validasi     ENUM('menunggu', 'disetujui', 'ditolak') NOT NULL DEFAULT 'menunggu',
    catatan_penolakan   TEXT            NULL,
    divalidasi_oleh     INT             NULL,   -- id_pengguna Admin Outsource
    waktu_validasi      DATETIME        NULL,

    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_absensi_karyawan      FOREIGN KEY (id_karyawan)
        REFERENCES karyawan(id_karyawan) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_absensi_jadwal        FOREIGN KEY (id_jadwal)
        REFERENCES jadwal_kerja(id_jadwal) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_absensi_validator     FOREIGN KEY (divalidasi_oleh)
        REFERENCES pengguna(id_pengguna) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT uq_absensi_karyawan_tgl  UNIQUE (id_karyawan, tanggal_absensi)
);

-- ============================================================
-- TABEL PENGAJUAN LEMBUR
-- ============================================================

-- Tabel pengajuan_lembur
-- Menyimpan pengajuan lembur karyawan (bisa retroaktif maks. H+1)
CREATE TABLE pengajuan_lembur (
    id_lembur               INT             PRIMARY KEY AUTO_INCREMENT,
    id_karyawan             INT             NOT NULL,
    id_absensi              INT             NOT NULL,
    tanggal_lembur          DATE            NOT NULL,
    jam_mulai_estimasi      TIME            NOT NULL,
    jam_selesai_estimasi    TIME            NOT NULL,
    menit_lembur_diajukan   INT             NOT NULL DEFAULT 0,
    menit_lembur_resmi      INT             NOT NULL DEFAULT 0,
    alasan_lembur           TEXT            NOT NULL,
    status                  ENUM('menunggu', 'disetujui', 'ditolak', 'kadaluarsa')
                                            NOT NULL DEFAULT 'menunggu',
    catatan_penolakan       TEXT            NULL,
    batas_pengajuan         DATE            NOT NULL,
    diajukan_pada           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    diproses_oleh           INT             NULL,
    waktu_proses            DATETIME        NULL,
    created_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_lembur_karyawan   FOREIGN KEY (id_karyawan)
        REFERENCES karyawan(id_karyawan) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_lembur_absensi    FOREIGN KEY (id_absensi)
        REFERENCES absensi(id_absensi) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_lembur_prosesor   FOREIGN KEY (diproses_oleh)
        REFERENCES pengguna(id_pengguna) ON UPDATE CASCADE ON DELETE SET NULL
);

-- ============================================================
-- TABEL PENGAJUAN IZIN
-- ============================================================

-- Tabel jenis_izin
-- Lookup tabel untuk jenis izin tidak masuk
CREATE TABLE jenis_izin (
    id_jenis_izin   INT             PRIMARY KEY AUTO_INCREMENT,
    nama_jenis      VARCHAR(50)     NOT NULL,
    wajib_dokumen   BOOLEAN         NOT NULL DEFAULT FALSE,
    keterangan      VARCHAR(200)    NULL
);

-- Tabel pengajuan_izin
-- Menyimpan pengajuan izin tidak masuk dari karyawan
CREATE TABLE pengajuan_izin (
    id_izin                 INT             PRIMARY KEY AUTO_INCREMENT,
    id_karyawan             INT             NOT NULL,
    id_jenis_izin           INT             NOT NULL,
    tanggal_izin            DATE            NOT NULL,
    keterangan              TEXT            NULL,
    status                  ENUM('menunggu', 'disetujui', 'ditolak') NOT NULL DEFAULT 'menunggu',
    catatan_penolakan       TEXT            NULL,
    status_dokumen          ENUM('belum_upload', 'sudah_upload', 'lengkap', 'tidak_lengkap')
                                            NOT NULL DEFAULT 'belum_upload',
    catatan_dokumen         TEXT            NULL,
    diajukan_pada           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    divalidasi_admin        INT             NULL,
    waktu_validasi_admin    DATETIME        NULL,
    diverifikasi_hr         INT             NULL,
    waktu_verifikasi_hr     DATETIME        NULL,
    created_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_izin_karyawan     FOREIGN KEY (id_karyawan)
        REFERENCES karyawan(id_karyawan) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_izin_jenis        FOREIGN KEY (id_jenis_izin)
        REFERENCES jenis_izin(id_jenis_izin) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_izin_admin        FOREIGN KEY (divalidasi_admin)
        REFERENCES pengguna(id_pengguna) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_izin_hr           FOREIGN KEY (diverifikasi_hr)
        REFERENCES pengguna(id_pengguna) ON UPDATE CASCADE ON DELETE SET NULL
);

-- Tabel dokumen_izin
-- Menyimpan file dokumen pendukung untuk setiap pengajuan izin
CREATE TABLE dokumen_izin (
    id_dokumen      INT             PRIMARY KEY AUTO_INCREMENT,
    id_izin         INT             NOT NULL,
    nama_file       VARCHAR(255)    NOT NULL,
    path_file       VARCHAR(500)    NOT NULL,
    tipe_file       VARCHAR(50)     NOT NULL,
    ukuran_kb       INT             NOT NULL,
    diunggah_oleh   INT             NOT NULL,
    diunggah_pada   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_dokumen_izin      FOREIGN KEY (id_izin)
        REFERENCES pengajuan_izin(id_izin) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_dokumen_pengguna  FOREIGN KEY (diunggah_oleh)
        REFERENCES pengguna(id_pengguna) ON UPDATE CASCADE ON DELETE RESTRICT
);

-- ============================================================
-- TABEL AUDIT LOG
-- ============================================================

-- Tabel audit_log
-- Menyimpan jejak seluruh aksi approve/reject pada data absensi untuk transparansi HR
CREATE TABLE audit_log (
    id_log          INT             PRIMARY KEY AUTO_INCREMENT,
    id_pengguna     INT             NOT NULL,
    role_pelaku     ENUM('admin_outsource', 'user_departemen', 'hr', 'super_admin', 'sistem')
                                    NOT NULL,
    jenis_data      ENUM('absensi', 'lembur', 'izin', 'planning', 'akun', 'master_data', 'konfigurasi')
                                    NOT NULL,
    id_referensi    INT             NOT NULL,
    aksi            ENUM('approve', 'reject', 'create', 'update', 'deactivate', 'upload')
                                    NOT NULL,
    catatan         TEXT            NULL,
    data_sebelum    JSON            NULL,
    data_sesudah    JSON            NULL,
    ip_address      VARCHAR(45)     NULL,
    waktu_aksi      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_audit_pengguna    FOREIGN KEY (id_pengguna)
        REFERENCES pengguna(id_pengguna) ON UPDATE CASCADE ON DELETE RESTRICT
);

-- ============================================================
-- TABEL NOTIFIKASI
-- ============================================================

CREATE TABLE notifikasi (
    id_notifikasi   INT             PRIMARY KEY AUTO_INCREMENT,
    id_penerima     INT             NOT NULL,
    id_pengirim     INT             NULL,
    judul           VARCHAR(200)    NOT NULL,
    isi             TEXT            NOT NULL,
    jenis           ENUM('absensi', 'lembur', 'izin', 'planning', 'sistem') NOT NULL,
    id_referensi    INT             NULL,
    is_dibaca       BOOLEAN         NOT NULL DEFAULT FALSE,
    dibaca_pada     DATETIME        NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_notif_penerima    FOREIGN KEY (id_penerima)
        REFERENCES pengguna(id_pengguna) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_notif_pengirim    FOREIGN KEY (id_pengirim)
        REFERENCES pengguna(id_pengguna) ON UPDATE CASCADE ON DELETE SET NULL
);

-- ============================================================
-- TABEL REKAPITULASI BULANAN
-- ============================================================

CREATE TABLE rekap_bulanan (
    id_rekap                INT             PRIMARY KEY AUTO_INCREMENT,
    id_karyawan             INT             NOT NULL,
    periode_bulan           TINYINT         NOT NULL CHECK (periode_bulan BETWEEN 1 AND 12),
    periode_tahun           YEAR            NOT NULL,
    total_hari_kerja        INT             NOT NULL DEFAULT 0,
    total_hari_hadir        INT             NOT NULL DEFAULT 0,
    total_hari_izin         INT             NOT NULL DEFAULT 0,
    total_hari_alpa         INT             NOT NULL DEFAULT 0,
    total_menit_normal      INT             NOT NULL DEFAULT 0,
    total_menit_lembur      INT             NOT NULL DEFAULT 0,
    total_menit_telat       INT             NOT NULL DEFAULT 0,
    total_menit_pulang_cepat INT            NOT NULL DEFAULT 0,
    status_rekap            ENUM('draft', 'final') NOT NULL DEFAULT 'draft',
    dibuat_oleh             INT             NULL,
    ditetapkan_pada         DATETIME        NULL,
    created_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_rekap_karyawan    FOREIGN KEY (id_karyawan)
        REFERENCES karyawan(id_karyawan) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_rekap_hr          FOREIGN KEY (dibuat_oleh)
        REFERENCES pengguna(id_pengguna) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT uq_rekap_karyawan_periode UNIQUE (id_karyawan, periode_bulan, periode_tahun)
);

-- ============================================================
-- FOREIGN KEY TAMBAHAN (setelah semua tabel terbentuk)
-- ============================================================

ALTER TABLE konfigurasi_area
    ADD CONSTRAINT fk_konfigurasi_pengguna
        FOREIGN KEY (diubah_oleh)
        REFERENCES pengguna(id_pengguna) ON UPDATE CASCADE ON DELETE RESTRICT;

-- ============================================================
-- DATA AWAL (SEED DATA)
-- ============================================================

INSERT INTO jenis_izin (nama_jenis, wajib_dokumen, keterangan) VALUES
('Sakit',                    TRUE,  'Wajib melampirkan surat dokter'),
('Izin Keperluan Keluarga',  TRUE,  'Wajib melampirkan surat/bukti'),
('Izin Keperluan Lain',      FALSE, 'Tanpa dokumen wajib'),
('Cuti',                     FALSE, 'Cuti tahunan');

INSERT INTO shift (nama_shift, jam_masuk, jam_pulang, durasi_normal_menit) VALUES
('Shift Pagi',   '07:00:00', '15:00:00', 480),
('Shift Siang',  '15:00:00', '23:00:00', 480),
('Shift Malam',  '23:00:00', '07:00:00', 480),
('Shift Normal', '08:00:00', '17:00:00', 480);

-- ============================================================
-- INDEKS UNTUK PERFORMA
-- ============================================================

-- Indeks pada tabel profil (sering di-JOIN saat autentikasi & RBAC)
CREATE INDEX idx_aoprofile_perusahaan   ON admin_outsource_profile(id_perusahaan);
CREATE INDEX idx_udprofile_departemen   ON user_departemen_profile(id_departemen);

-- Indeks utama operasional
CREATE INDEX idx_absensi_karyawan_tgl   ON absensi(id_karyawan, tanggal_absensi);
CREATE INDEX idx_absensi_status         ON absensi(status_validasi, status_kehadiran);
CREATE INDEX idx_lembur_status          ON pengajuan_lembur(status, batas_pengajuan);
CREATE INDEX idx_lembur_karyawan        ON pengajuan_lembur(id_karyawan, tanggal_lembur);
CREATE INDEX idx_izin_karyawan          ON pengajuan_izin(id_karyawan, tanggal_izin);
CREATE INDEX idx_izin_status            ON pengajuan_izin(status, status_dokumen);
CREATE INDEX idx_jadwal_karyawan_tgl    ON jadwal_kerja(id_karyawan, tanggal_kerja);
CREATE INDEX idx_audit_waktu            ON audit_log(waktu_aksi, jenis_data);
CREATE INDEX idx_audit_pengguna         ON audit_log(id_pengguna, waktu_aksi);
CREATE INDEX idx_notif_penerima         ON notifikasi(id_penerima, is_dibaca);
CREATE INDEX idx_rekap_periode          ON rekap_bulanan(periode_tahun, periode_bulan);
CREATE INDEX idx_karyawan_perusahaan    ON karyawan(id_perusahaan);
CREATE INDEX idx_karyawan_departemen    ON karyawan(id_departemen);

-- ============================================================
-- VIEWS UNTUK KEMUDAHAN QUERY
-- ============================================================

-- View profil lengkap semua pengguna (termasuk data dari extension tables)
-- Berguna untuk dashboard Super Admin dan audit
CREATE VIEW v_pengguna_lengkap AS
SELECT
    p.id_pengguna,
    p.nama_lengkap,
    p.email,
    p.role,
    p.status,
    p.last_login,
    -- Data Admin Outsource (NULL untuk role lain)
    ao.id_perusahaan    AS ao_id_perusahaan,
    po.nama_perusahaan  AS ao_nama_perusahaan,
    -- Data User Departemen (NULL untuk role lain)
    ud.id_departemen    AS ud_id_departemen,
    d.nama_departemen   AS ud_nama_departemen,
    -- Data Karyawan (NULL untuk role lain)
    k.id_karyawan,
    k.nik,
    k.nomor_karyawan,
    k.posisi
FROM pengguna p
LEFT JOIN admin_outsource_profile ao    ON p.id_pengguna = ao.id_pengguna
LEFT JOIN perusahaan_outsource po       ON ao.id_perusahaan = po.id_perusahaan
LEFT JOIN user_departemen_profile ud    ON p.id_pengguna = ud.id_pengguna
LEFT JOIN departemen d                  ON ud.id_departemen = d.id_departemen
LEFT JOIN karyawan k                    ON p.id_pengguna = k.id_pengguna;

-- View rekap absensi harian lengkap
CREATE VIEW v_absensi_lengkap AS
SELECT
    a.id_absensi,
    k.id_karyawan,
    k.nama_lengkap      AS nama_karyawan,
    k.nomor_karyawan,
    po.nama_perusahaan,
    dep.nama_departemen,
    a.tanggal_absensi,
    s.nama_shift,
    s.jam_masuk         AS jadwal_masuk,
    s.jam_pulang        AS jadwal_pulang,
    a.waktu_check_in,
    a.waktu_check_out,
    a.menit_kerja_normal,
    a.menit_telat,
    a.menit_pulang_cepat,
    a.menit_kelebihan,
    a.status_kehadiran,
    a.status_validasi,
    pv.nama_lengkap     AS divalidasi_oleh
FROM absensi a
JOIN karyawan k                 ON a.id_karyawan = k.id_karyawan
JOIN jadwal_kerja jk            ON a.id_jadwal = jk.id_jadwal
JOIN shift s                    ON jk.id_shift = s.id_shift
JOIN perusahaan_outsource po    ON k.id_perusahaan = po.id_perusahaan
JOIN departemen dep             ON k.id_departemen = dep.id_departemen
LEFT JOIN pengguna pv           ON a.divalidasi_oleh = pv.id_pengguna;

-- View audit log lengkap
CREATE VIEW v_audit_lengkap AS
SELECT
    al.id_log,
    p.nama_lengkap  AS nama_pelaku,
    al.role_pelaku,
    al.jenis_data,
    al.id_referensi,
    al.aksi,
    al.catatan,
    al.waktu_aksi
FROM audit_log al
JOIN pengguna p ON al.id_pengguna = p.id_pengguna;
