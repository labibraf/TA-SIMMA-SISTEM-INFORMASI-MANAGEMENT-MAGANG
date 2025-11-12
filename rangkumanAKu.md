# MaMagang — Aplikasi Manajemen Magang

Tanggal: 27 Oktober 2025  
Lokasi proyek: d:\apk\laragon\www\MaMagang

Aplikasi web untuk mengelola program magang: penugasan (individu/divisi), persetujuan tugas, pelacakan progres jam, laporan harian, repository laporan akhir, dengan peran Admin, Mentor, dan Peserta.

## 🔄 Update Terbaru: Auto-Delete Repository (5 Nov 2025)

### ✅ Masalah Yang Diperbaiki

Repository tidak terhapus otomatis ketika status laporan akhir berubah dari "terima" ke status lain (tolak/draft/review). Penyebabnya adalah inkonsistensi nilai status: code menggunakan `'diterima'` tapi database menggunakan `'terima'`.

### 🛠️ Solusi Yang Diterapkan

1. **Perbaikan Nilai Status** - Ubah semua referensi dari `'diterima'` → `'terima'`
2. **Auto-Delete Logic** - Repository otomatis terhapus ketika laporan akhir diubah/ditolak
3. **Cleanup Data** - Hapus repository yang tidak valid (status bukan "terima")

### 📝 Status Laporan Akhir

- `draft` → Sedang dibuat/diedit
- `review` → Sedang direview mentor
- `terima` → Diterima (bisa ke repository)
- `tolak` → Ditolak (perlu revisi)

---

## 1) Arsitektur (MVC Laravel)

- Model (app/Models): User, Peserta, Mentor, Penugasan, LaporanHarian, LaporanAkhir, Bagian.
- View (resources/views): Blade templates (penugasan, dashboard, repository, laporan_akhir).
- Controller (app/Http/Controllers): PenugasanController, DashboardController, LaporanAkhirController, dsb.
- ORM: Eloquent (relasi, query berorientasi objek).

Lifecycle singkat:

1. public/index.php → 2) HTTP Kernel + Middleware (auth, role) → 3) Router (routes/web.php) → 4) Controller → 5) Model → 6) View (Blade) → 7) Response HTML.

---

## 2) Teknologi & Library

- Backend: PHP 8+, Laravel, Composer, Eloquent ORM.
- Akses/Otorisasi: spatie/laravel-permission (roles: Admin, Mentor, Peserta).
- Frontend: Bootstrap 5, jQuery, Font Awesome, Blade.
- Template UI: Mantis (aset di public/template/dist).
- Disarankan: Vite untuk bundling/minify/tree-shaking aset.

Keamanan bawaan:

- SQL Injection: parameter binding (Eloquent/Query Builder).
- XSS: escaping otomatis pada `{{ ... }}` di Blade.
- CSRF: token `@csrf` pada form.
- Password: hashing (bcrypt/argon) via `Hash::make()`.

---

## 3) Skema & Relasi Inti

- Migrations (database/migrations): versioning struktur DB.
- Seeders (database/seeders): data awal/dummy.

Relasi (contoh):

- Mentor hasMany Peserta
- Peserta hasMany Penugasan
- Penugasan belongsTo Peserta (kategori "Individu")
- Penugasan belongsTo Bagian (kategori "Divisi")
- Peserta hasMany LaporanHarian
- Repository/Entry terkait LaporanAkhir

---

## 4) Logika Bisnis Utama

Kategori penugasan:

- Individu → `peserta_id` terisi.
- Divisi → `bagian_id` terisi.

Status final penugasan:

- `is_approved = 1` menandakan tugas “Selesai/Disetujui” (gunakan ini sebagai sumber kebenaran).

Perhitungan progres peserta:

- Total Jam Tercapai
  = Σ(beban_waktu semua tugas Individu peserta yang `is_approved=1`)
    - Σ(beban_waktu semua tugas Divisi pada `bagian_id` peserta yang `is_approved=1`)
- Persentase Progress
  = `(Total Jam Tercapai / Target Jam Peserta) × 100%`
- Gate Laporan Akhir: peserta bisa membuat laporan akhir jika progress mencapai ambang kebijakan (mis. ≥ 100% target).

Contoh fungsi ilustratif (Model Peserta):

```php
public function updateWaktuTugasTercapai(): int
{
    $wIndividu = Penugasan::where('kategori', 'Individu')
        ->where('peserta_id', $this->id)
        ->where('is_approved', 1)
        ->sum('beban_waktu');

    $wDivisi = Penugasan::where('kategori', 'Divisi')
        ->where('bagian_id', $this->bagian_id)
        ->where('is_approved', 1)
        ->sum('beban_waktu');

    $total = $wIndividu + $wDivisi;
    $this->waktu_tugas_tercapai = $total;
    $this->save();

    return $total;
}
```

---

## 5) Komponen UI Penting

Badge status “Selesai” di bawah judul tugas:

```blade
<td>
  <a href="{{ route('penugasans.show', $item->id) }}" class="text-decoration-none judul-tugas">
    {{ $item->judul_tugas }}
  </a>
  @if($item->is_approved == 1)
    <br><span class="badge bg-success mt-1"><i class="fas fa-check me-1"></i> Selesai</span>
  @endif
</td>
```

Progress bar dengan outline:

```blade
@php($p = $alasanTidakBisa['progress'] ?? 0)
<div class="progress" style="height:20px; border:1px solid #ccc;">
  <div class="progress-bar bg-{{ $p >= 75 ? 'success' : ($p >= 50 ? 'warning' : 'danger') }}"
       style="width: {{ $p }}%"
       role="progressbar" aria-valuenow="{{ $p }}" aria-valuemin="0" aria-valuemax="100">
    {{ number_format($p, 1) }}%
  </div>
</div>
```

Warna teks/icon putih saat konflik spesifisitas:

```blade
<h5 class="mb-0 text-white"><i class="fas fa-file-pdf me-2"></i>Laporan Akhir</h5>
```

---

## 6) Optimasi Aset Frontend

Masalah: menyalin seluruh aset Mantis ke `public/template/dist` → bundle besar, halaman berat.

Rekomendasi:

- Cabut `<link>/<script>` yang tidak dipakai dari layout sebelum menghapus file terkait di public/.
- Audit dengan Chrome DevTools → More tools → Coverage (lihat CSS/JS tak terpakai).
- Pindah ke Vite:
    - Import hanya modul yang dipakai.
    - Dapat minify + tree-shaking otomatis.
- Kompres gambar & font; gunakan lazy-load bila relevan.

---

## 7) app/Console & app/Interfaces (Tujuan & Alasan)

`app/Console`:

- Tempat Artisan Commands (otomatisasi/scheduled task).
- Contoh: pengingat deadline, rekap progres harian, sinkronisasi.
- Dijadwalkan di `app/Console/Kernel.php`.
- Alasan: pisahkan proses berat/periodik dari alur HTTP; rapi dan efektif.

`app/Interfaces`:

- Menyimpan kontrak antarmuka (mis. `NotificationInterface`, `ReportExporterInterface`).
- Alasan: Dependency Inversion (SOLID), mudah ganti implementasi (Email → WhatsApp), mudah mocking saat testing.

Contoh ringkas:

```php
interface NotificationInterface { public function send(string $message); }
class EmailNotification implements NotificationInterface {
    public function send(string $message) { /* kirim email */ }
}
```

---

## 8) Best Practices & Performa

- Validasi via FormRequest (`app/Http/Requests`) agar controller tetap tipis.
- Authorization granular via Policy/Gate.
- Eager Loading (`with([...])`) untuk cegah N+1 query.
- Cache data agregat (statistik dashboard).
- Logging & audit trail aksi penting.
- Unit test untuk perhitungan jam dan layanan inti.

---

## 9) Setup Cepat (Windows / Laragon)

1. Duplikasi `.env`, set koneksi DB.
2. `composer install`
3. `php artisan key:generate`
4. `php artisan migrate --seed`
5. Jalankan: `php artisan serve` atau pakai Laragon.
6. (Disarankan) Vite: `npm install` → `npm run dev` (atau `npm run build` untuk produksi).

---

## 10) Istilah & Rumus Ringkas

- `is_approved`: 1 = tugas selesai/disetujui (dipakai untuk badge dan agregasi).
- Beban waktu (jam): durasi nilai per penugasan.
- Total Jam Tercapai = Jam Individu Disetujui + Jam Divisi Disetujui (berdasarkan bagian).
- Progress% = `(Total Jam Tercapai / Target Jam) × 100`.

---

## 11) Rencana Lanjutan

- Notifikasi real-time (Laravel Echo + Pusher/WebSocket).
- Ekspor laporan (PDF/Excel).
- SSO jika diperlukan.
- Monitoring performa (Laravel Telescope) dan error tracking (Sentry).

---

## 12) Tambahan di Luar MVC (yang ada di proyek)

Berikut komponen arsitektur tambahan selain MVC yang terdeteksi dari struktur dan dependensi:

- Providers (`app/Providers`)
    - Tempat binding ke Service Container, event, konfigurasi global.
    - Contoh binding Interface → Implementasi:

        ```php
        // app/Providers/AppServiceProvider.php
        use App\Repositories\Interfaces\PenugasanRepositoryInterface;
        use App\Repositories\PenugasanRepository;

        public function register(): void
        {
            $this->app->bind(PenugasanRepositoryInterface::class, PenugasanRepository::class);
        }
        ```

- Repositories + Interfaces (`app/Repositories`, `app/Repositories/Interfaces`)
    - Abstraksi akses data di atas Eloquent untuk menjaga controller/service tetap tipis serta memudahkan testing.
- Blade View Components (`app/View/Components/Sidebar`)
    - Komponen UI reusable (mis. `<x-sidebar />`) untuk memisahkan logic presentasi dari view biasa.
- Tooling/Bundling via Vite (package.json)
    - Vite, Sass, axios, Bootstrap, Popper, Tailwind, Prettier; pipeline aset modern di luar MVC.
    - Catatan: Bootstrap dan Tailwind sama‑sama terpasang. Jika tidak perlu keduanya, pilih salah satu untuk mengurangi ukuran aset.
- Queue readiness (composer.json script "dev")
    - Script dev menjalankan `php artisan queue:listen` → siap untuk Jobs/Queues (proses background).
    - Jika belum menggunakan antrian, nonaktifkan dari script dev agar lingkungan pengembangan lebih ringan.
- PDF generator
    - `barryvdh/laravel-dompdf` untuk ekspor/print PDF (mis. laporan).
- UI feedback
    - `realrashid/sweet-alert` untuk notifikasi/alert yang konsisten di UI.
- Auth scaffolding
    - `laravel/ui` (Bootstrap based) untuk autentikasi cepat.

Ringkasan alasan penggunaan:

- Console/Artisan + Scheduler: otomasi tugas periodik/berat tanpa mengganggu request web.
- Interfaces + DI/Service Container: longgar keterikatan (SOLID), mudah ganti implementasi dan mudah di‑mock saat testing.
- Repositories/Components/Providers/Vite: keterpisahan concern, reusability, performa aset, dan maintainability yang lebih baik.

---
