# 📋 SIMMA - Sistem Informasi Manajemen Magang

<p align="center">
  <img src="public/template/dist/assets/images/simma-w-xl.png" alt="SIMMA Logo" width="200"/>
</p>

<p align="center">
  <strong>Sistem Manajemen Magang Digital untuk Kapushansiber Kemhan</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11.x-red?style=flat-square&logo=laravel" alt="Laravel">
  <img src="https://img.shields.io/badge/PHP-8.2+-blue?style=flat-square&logo=php" alt="PHP">
  <img src="https://img.shields.io/badge/Bootstrap-5.x-purple?style=flat-square&logo=bootstrap" alt="Bootstrap">
  <img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="License">
</p>

---

## 📖 Tentang Proyek

**SIMMA (Sistem Informasi Manajemen Magang)** adalah aplikasi web yang dikembangkan sebagai bagian dari **Tugas Akhir** untuk mendigitalisasi dan mengoptimalkan proses manajemen magang di **Kapushansiber Kemhan, Pondok Labu, Jakarta Selatan**.

### 🎯 Latar Belakang

Sebelumnya, pengelolaan program magang di Kapushansiber Kemhan masih menggunakan sistem konvensional (manual) yang menyebabkan:
- Kesulitan dalam pelacakan dan monitoring peserta magang
- Proses administrasi yang memakan waktu
- Kurangnya transparansi dalam pelaporan dan evaluasi
- Sulitnya mengukur produktivitas dan pencapaian peserta

Dengan **SIMMA**, seluruh proses manajemen magang dapat dilakukan secara digital, mulai dari pendaftaran, penugasan, pelaporan harian, hingga penyimpanan repository laporan akhir.

---

## ✨ Fitur Utama

### 👨‍💼 Untuk Admin
- ✅ **Manajemen Data Master**
  - Kelola data peserta magang
  - Kelola data mentor/pembimbing
  - Kelola data bagian/divisi
  - Kelola akun pengguna dan role

- ✅ **Monitoring & Dashboard**
  - Dashboard statistik peserta per periode
  - Monitoring progres tugas peserta
  - Analisis waktu kerja dan produktivitas
  - Filter berdasarkan tahun, bulan, bagian

- ✅ **Manajemen Penugasan**
  - Buat penugasan individu atau divisi
  - Tentukan beban waktu dan deadline
  - Approval penugasan yang dibuat mentor

- ✅ **Repository Laporan Akhir**
  - Publikasi/unpublish laporan akhir
  - Filter berdasarkan kategori, tahun, bagian
  - Input manual untuk arsip lama
  - Tracking views dan popularitas

### 👨‍🏫 Untuk Mentor
- ✅ **Manajemen Bimbingan**
  - Lihat daftar peserta bimbingan
  - Buat dan assign penugasan
  - Review laporan harian peserta
  - Monitoring progres tugas

- ✅ **Evaluasi & Feedback**
  - Review dan beri feedback pada laporan akhir
  - Terima/Tolak/Revisi laporan akhir
  - Tracking pencapaian peserta

### 👨‍🎓 Untuk Peserta Magang
- ✅ **Manajemen Tugas**
  - Lihat penugasan individu dan divisi
  - Submit laporan harian per penugasan
  - Upload bukti pekerjaan (file)
  - Tracking progres tugas

- ✅ **Laporan Akhir**
  - Submit laporan akhir magang
  - Revisi berdasarkan feedback mentor
  - Lihat status approval (Draft/Review/Terima/Tolak)

- ✅ **Dashboard Pribadi**
  - Monitoring jam kerja vs target
  - Visualisasi progres magang
  - Kalender deadline tugas

---

## 🛠️ Teknologi yang Digunakan

### Backend
- **Framework**: Laravel 11.x
- **Database**: MySQL
- **ORM**: Eloquent
- **Authentication**: Laravel Breeze
- **Storage**: Local Storage

### Frontend
- **Template**: Mantis Bootstrap Admin
- **CSS Framework**: Bootstrap 5.3
- **JavaScript**: Vanilla JS + jQuery
- **DataTables**: v2.3.2
- **Charts**: ApexCharts
- **Icons**: Tabler Icons, Feather Icons
- **Build Tool**: Vite

### Libraries
- **SweetAlert2**: Notifikasi yang cantik
- **SimpleMDE**: Markdown editor
- **Carbon**: Manipulasi tanggal

---

## 📊 Skema Database & Relasi

### Entitas Utama
- **Users**: Pengguna sistem (Admin, Mentor, Peserta)
- **Roles**: Peran pengguna
- **Peserta**: Data peserta magang
- **Mentor**: Data mentor/pembimbing
- **Bagian**: Divisi/bagian di instansi
- **Penugasan**: Tugas yang diberikan (Individu/Divisi)
- **LaporanHarian**: Laporan harian dari peserta
- **LaporanAkhir**: Laporan akhir magang
- **Repository**: Repository publikasi laporan akhir

### Relasi Kunci
```
User -> Role (belongsTo)
User -> Peserta (belongsTo)
User -> Mentor (belongsTo)
Peserta -> Bagian (belongsTo)
Peserta -> Mentor (belongsTo)
Mentor -> Bagian (belongsTo)
Penugasan -> Peserta/Bagian (belongsTo)
LaporanHarian -> Peserta, Penugasan (belongsTo)
LaporanAkhir -> Peserta, Mentor (belongsTo)
Repository -> LaporanAkhir, Peserta (belongsTo)
```

---

## 🚀 Instalasi

### Prasyarat
- PHP >= 8.2
- Composer
- MySQL/MariaDB
- Node.js & NPM (untuk asset compilation)
- Git

### Langkah Instalasi

1. **Clone Repository**
   ```bash
   git clone https://github.com/labibraf/TA-SIMMA-SISTEM-INFORMASI-MANAGEMENT-MAGANG
   cd TA-SIMMA-SISTEM-INFORMASI-MANAGEMENT-MAGANG
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Konfigurasi Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Konfigurasi Database**
   
   Edit file `.env` dan sesuaikan dengan konfigurasi database Anda:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=simma_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Jalankan Migration & Seeder**
   ```bash
   php artisan migrate --seed
   ```

6. **Create Storage Link**
   ```bash
   php artisan storage:link
   ```

7. **Build Assets**
   ```bash
   # Development
   npm run dev
   
   # Production
   npm run build
   ```

8. **Jalankan Aplikasi**
   ```bash
   php artisan serve
   ```

9. **Akses Aplikasi**
   
   Buka browser dan akses: `http://localhost:8000`

---

## 👤 Akun Default

Setelah menjalankan seeder, gunakan akun berikut untuk login:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@gmail.com | password |
| Mentor | bukuran@gmail.com | password |
| Peserta | budi@gmail.com | password |

> ⚠️ **Penting**: Segera ubah password default setelah login pertama kali!

---

## 📁 Struktur Folder

```
project-ta3/
├── app/
│   ├── Console/              # Commands & Scheduling
│   ├── Http/
│   │   ├── Controllers/      # Controllers (MVC)
│   │   ├── Middleware/       # Custom Middleware
│   │   └── Requests/         # Form Requests
│   ├── Models/               # Eloquent Models
│   ├── Repositories/         # Repository Pattern
│   │   └── Interfaces/       # Repository Interfaces
│   └── ...
├── database/
│   ├── migrations/           # Database Migrations
│   └── seeders/              # Database Seeders
├── public/
│   ├── template/             # Mantis Admin Template
│   └── storage/              # Public Storage (Symlink)
├── resources/
│   ├── views/                # Blade Templates
│   │   ├── auth/
│   │   ├── components/
│   │   ├── dashboard/
│   │   ├── peserta/
│   │   ├── mentor/
│   │   ├── penugasan/
│   │   ├── laporan_harian/
│   │   ├── laporan_akhir/
│   │   └── repository/
│   ├── js/                   # JavaScript Files
│   └── css/                  # CSS Files
├── routes/
│   ├── web.php               # Web Routes
│   └── ...
└── ...
```
---

## 📝 Fitur Unggulan

### 1. **Auto-Calculate Target Waktu**
Sistem secara otomatis menghitung target waktu berdasarkan:
- **Metode SKS**: `SKS × 45 jam`
- **Metode Manual**: Input langsung oleh admin

### 2. **Real-time Progress Tracking**
- Monitoring jam kerja vs target
- Visualisasi dengan progress bar dan chart
- Alert jika mendekati deadline

### 3. **Multi-level Approval**
- Penugasan: Mentor → Admin
- Laporan Akhir: Mentor Review → Admin Approve

### 4. **Auto-Delete Repository**
Repository laporan akhir otomatis dihapus setelah 30 hari jika tidak dipublish.

### 5. **Role-Based Access Control**
Setiap role memiliki akses yang berbeda sesuai kebutuhan.

---

## 🔐 Keamanan

- ✅ CSRF Protection (Laravel)
- ✅ XSS Protection
- ✅ SQL Injection Protection (Eloquent ORM)
- ✅ Password Hashing (Bcrypt)
- ✅ Authentication & Authorization
- ✅ File Upload Validation
- ✅ Middleware Protection

---

## 📈 Performance Optimization

- ✅ Eager Loading untuk mencegah N+1 Query
- ✅ Database Indexing
- ✅ Query Optimization
- ✅ Asset Minification (Vite)
- ✅ Lazy Loading Components

---

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter=PesertaTest
```

---

## 🤝 Kontribusi

Kontribusi selalu diterima dengan senang hati! Jika Anda ingin berkontribusi:

1. Fork repository ini
2. Buat branch baru (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

---

## 👨‍💻 Developer

**M Labib Rafsanjani A**  
Mahasiswa Tugas Akhir  
Peserta Magang di Kapushansiber Kemhan, Jakarta

- GitHub: [@labibraf](https://github.com/labibraf)
- Email: [labibra@gmail.com]

---

## 🙏 Acknowledgments

- **Kapushansiber Kemhan** - Pondok Labu, Jakarta Selatan
- **Pembimbing Tugas Akhir** - [Ibu Intan S.kom, M.T]
- **Mentor Magang** - [Bpk Kol Rudi]
- **Laravel Community**
- **Mantis Bootstrap Admin Template**

---

<p align="center">
  Made with ❤️ for Kapushansiber Kemhan
</p>

<p align="center">
  <strong>Digitalisasi untuk Indonesia yang Lebih Maju</strong>
</p>
