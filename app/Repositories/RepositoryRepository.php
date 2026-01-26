<?php

namespace App\Repositories;

use App\Models\Repository;
use App\Models\LaporanAkhir;
use App\Repositories\Interfaces\RepositoryRepositoryInterface;
use Illuminate\Support\Facades\DB;

class RepositoryRepository implements RepositoryRepositoryInterface
{
    protected $model;

    public function __construct(Repository $model)
    {
        $this->model = $model;
    }

    /**
     * Ambil semua repository yang sudah dipublish dengan relasi
     */
    public function getAllPublished()
    {
        return $this->model
            ->published()
            ->with(['laporanAkhir', 'peserta.user', 'peserta.bagian'])
            ->orderBy('published_at', 'desc')
            ->get();
    }

    /**
     * Ambil semua repository (termasuk draft) - untuk admin
     */
    public function getAll()
    {
        return $this->model
            ->with(['laporanAkhir', 'peserta.user', 'peserta.bagian'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Ambil repository berdasarkan ID dengan relasi lengkap
     */
    public function findById($id)
    {
        return $this->model
            ->with(['laporanAkhir.mentor', 'peserta.user', 'peserta.bagian'])
            ->findOrFail($id);
    }

    /**
     * Ambil repository berdasarkan tahun
     */
    public function getByYear($year)
    {
        return $this->model
            ->published()
            ->byYear($year)
            ->with(['laporanAkhir', 'peserta.user', 'peserta.bagian'])
            ->orderBy('published_at', 'desc')
            ->get();
    }

    /**
     * Ambil repository berdasarkan kategori
     */
    public function getByCategory($category)
    {
        return $this->model
            ->published()
            ->byCategory($category)
            ->with(['laporanAkhir', 'peserta.user', 'peserta.bagian'])
            ->orderBy('published_at', 'desc')
            ->get();
    }

    /**
     * Ambil repository berdasarkan bagian
     */
    public function getByBagian($bagian)
    {
        return $this->model
            ->published()
            ->byBagian($bagian)
            ->with(['laporanAkhir', 'peserta.user', 'peserta.bagian'])
            ->orderBy('published_at', 'desc')
            ->get();
    }

    /**
     * Ambil repository berdasarkan peserta
     */
    public function getByPeserta($pesertaId)
    {
        return $this->model
            ->where('peserta_id', $pesertaId)
            ->with(['laporanAkhir', 'peserta.user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Buat repository baru dari laporan akhir yang sudah di-ACC
     */
    public function createFromLaporanAkhir($laporanAkhirId, array $data)
    {
        // Cek apakah laporan akhir sudah di-ACC
        $laporanAkhir = LaporanAkhir::with('peserta')->findOrFail($laporanAkhirId);

        if ($laporanAkhir->status !== 'terima') {
            throw new \Exception('Hanya laporan akhir yang sudah diterima yang bisa dipublikasikan ke repository.');
        }

        // Cek apakah laporan ini sudah ada di repository
        $existingRepo = $this->model->where('laporan_akhir_id', $laporanAkhirId)->first();
        if ($existingRepo) {
            throw new \Exception('Laporan akhir ini sudah ada di repository.');
        }

        // Buat repository baru
        return $this->model->create([
            'laporan_akhir_id' => $laporanAkhirId,
            'peserta_id' => $laporanAkhir->peserta_id,
            'judul' => $data['judul'] ?? $laporanAkhir->judul_laporan,
            'deskripsi' => $data['deskripsi'],
            'deskripsi_lengkap' => $data['deskripsi_lengkap'] ?? null,
            'tahun_magang' => $data['tahun_magang'],
            'bagian' => $data['bagian'] ?? null,
            'kategori' => $data['kategori'] ?? null,
            'file_path' => $laporanAkhir->file_path, // Simpan file path dari laporan akhir
            'is_published' => $data['is_published'] ?? false,
            'published_at' => isset($data['is_published']) && $data['is_published'] ? now() : null,
        ]);
    }

    /**
     * Buat repository baru dari upload manual (tanpa laporan akhir)
     * Untuk mengarsipkan laporan dari tahun-tahun sebelumnya atau dokumen eksternal
     */
    public function createManual(array $data)
    {
        // Cek apakah repository dengan judul yang sama sudah ada (baik dari laporan akhir maupun manual)
        $existingRepo = $this->model
            ->where('judul', $data['judul'])
            ->where('tahun_magang', $data['tahun_magang'])
            ->first();

        if ($existingRepo) {
            throw new \Exception('Repository dengan judul dan tahun magang yang sama sudah ada.');
        }

        // Buat repository baru tanpa relasi ke laporan_akhir
        return $this->model->create([
            'laporan_akhir_id' => null,
            'peserta_id' => null,
            'judul' => $data['judul'],
            'deskripsi' => $data['deskripsi'],
            'deskripsi_lengkap' => $data['deskripsi_lengkap'] ?? null,
            'tahun_magang' => $data['tahun_magang'],
            'bagian' => $data['bagian'] ?? null,
            'kategori' => $data['kategori'] ?? null,
            'is_published' => $data['is_published'] ?? false,
            'published_at' => isset($data['is_published']) && $data['is_published'] ? now() : null,
            'file_path' => $data['file_path'],
            'nama_peserta' => $data['nama_peserta'], // Nama peserta disimpan sebagai string
        ]);
    }

    /**
     * Update repository
     */
    public function update($id, array $data)
    {
        $repository = $this->model->findOrFail($id);

        // Jika status publish berubah, update published_at
        if (isset($data['is_published'])) {
            if ($data['is_published'] && !$repository->is_published) {
                $data['published_at'] = now();
            } elseif (!$data['is_published']) {
                $data['published_at'] = null;
            }
        }

        $repository->update($data);
        return $repository->fresh();
    }

    /**
     * Hapus repository
     */
    public function delete($id)
    {
        $repository = $this->model->findOrFail($id);
        return $repository->delete();
    }

    /**
     * Hapus repository berdasarkan laporan akhir ID
     * Digunakan ketika status laporan akhir berubah dari 'diterima' menjadi status lain
     * atau ketika laporan akhir ditolak/diubah
     *
     * @param int $laporanAkhirId
     * @return bool
     */
    public function deleteByLaporanAkhir($laporanAkhirId)
    {
        $repository = $this->model->where('laporan_akhir_id', $laporanAkhirId)->first();

        if ($repository) {
            return $repository->delete();
        }

        return false;
    }

    /**
     * Publish repository
     */
    public function publish($id)
    {
        $repository = $this->model->findOrFail($id);
        $repository->publish();
        return $repository->fresh();
    }

    /**
     * Unpublish repository
     */
    public function unpublish($id)
    {
        $repository = $this->model->findOrFail($id);
        $repository->unpublish();
        return $repository->fresh();
    }

    /**
     * Search repository berdasarkan keyword
     */
    public function search($keyword)
    {
        return $this->model
            ->published()
            ->where(function($query) use ($keyword) {
                $query->where('judul', 'like', "%{$keyword}%")
                      ->orWhere('deskripsi', 'like', "%{$keyword}%")
                      ->orWhere('deskripsi_lengkap', 'like', "%{$keyword}%")
                      ->orWhere('kategori', 'like', "%{$keyword}%");
            })
            ->with(['laporanAkhir', 'peserta.user', 'peserta.bagian'])
            ->orderBy('published_at', 'desc')
            ->get();
    }

    /**
     * Ambil statistik repository
     */
    public function getStatistics()
    {
        return [
            'total_repositories' => $this->model->count(),
            'total_published' => $this->model->where('is_published', true)->count(),
            'total_draft' => $this->model->where('is_published', false)->count(),
            'by_year' => $this->model
                ->select('tahun_magang', DB::raw('count(*) as total'))
                ->where('is_published', true)
                ->groupBy('tahun_magang')
                ->orderBy('tahun_magang', 'desc')
                ->get(),
            'by_category' => $this->model
                ->select('kategori', DB::raw('count(*) as total'))
                ->where('is_published', true)
                ->whereNotNull('kategori')
                ->groupBy('kategori')
                ->orderBy('total', 'desc')
                ->get(),
        ];
    }
}
