# Managemen Evaluasi

**Aplikasi Manajemen Tugas & Evaluasi Performa Karyawan** berbasis Laravel 12 + Filament 3, dilengkapi evaluasi dua arah (360-degree feedback) dan dashboard pemantauan beban kerja (*burnout risk*) menggunakan metode *Weighted Sum* prioritas tugas dan *Z-Score*.

## ✨ Fitur Utama

- **Manajemen Tugas** — pembuatan, delegasi berjenjang sesuai role, alur status To Do → In Progress → Done.
- **Evaluasi Atasan** — penilaian hasil kerja (1–5) oleh atasan langsung/HR, terkunci sampai tugas berstatus Done.
- **Evaluasi Penerima** — penilaian kejelasan instruksi & tingkat kesulitan oleh penerima tugas.
- **Feedback Karyawan** — karyawan melihat hasil evaluasi atasan dan memberi tanggapan balik yang dapat dibaca atasan/HR.
- **Dashboard Beban Kerja** — Weighted Sum + Z-Score per staff dengan indikator AMAN / PERLU PANTAUAN / BERISIKO dan badge CRITICAL BURNOUT RISK.
- **Hak Akses per Role** — HR (kelola user & pantau global), Manager (tim & evaluasi bawahan), Employee (tugas pribadi & evaluasi).
- **Registrasi Karyawan** — akun baru nonaktif hingga diaktivasi HR.

## 🛠 Teknologi

PHP ≥ 8.2 · Laravel 12 · Filament 3.3 · Livewire 3 · MySQL

## 🚀 Instalasi Lokal

1. Clone repositori:
   ```bash
   git clone https://github.com/athaillah22/managemen_evaluasi.git
   cd managemen_evaluasi
   ```
2. Install dependensi:
   ```bash
   composer install
   ```
3. Konfigurasi environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Lalu isi koneksi database di `.env` (DB_DATABASE, DB_USERNAME, DB_PASSWORD).
4. Siapkan database & data awal:
   ```bash
   php artisan migrate --seed
   ```
5. Jalankan:
   ```bash
   php artisan serve
   ```
   Buka `http://localhost:8000/admin`.

## 👤 Akun Default

| Role | Email | Password |
|---|---|---|
| HR | budi@gmail.com | budi123 |
| Manager | ibnu@gmail.com | ibnu123 |
| Employee | davez@gmail.com | davez123 |
| Employee | rifan@gmail.com | rifan123 |

## 🔐 Keamanan

- Rate limiting login: maksimal 5 percobaan/menit per IP (anti brute-force).
- Validasi backend pada aksi sensitif (delegasi, evaluasi, perubahan status).

## 📄 Lisensi

Proyek akademis — Kelompok 2.