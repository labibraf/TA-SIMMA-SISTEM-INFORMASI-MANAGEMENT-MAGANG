<?php

namespace App\Repositories\Interfaces;

interface RepositoryRepositoryInterface
{
    /**
     * Ambil semua repository yang sudah dipublish
     */
    public function getAllPublished();

    /**
     * Ambil semua repository (termasuk draft) - untuk admin
     */
    public function getAll();

    /**
     * Ambil repository berdasarkan ID
     */
    public function findById($id);

    /**
     * Ambil repository berdasarkan tahun
     */
    public function getByYear($year);

    /**
     * Ambil repository berdasarkan kategori
     */
    public function getByCategory($category);

    /**
     * Ambil repository berdasarkan bagian
     */
    public function getByBagian($bagian);

    /**
     * Ambil repository berdasarkan peserta
     */
    public function getByPeserta($pesertaId);

    /**
     * Buat repository baru dari laporan akhir yang sudah di-ACC
     */
    public function createFromLaporanAkhir($laporanAkhirId, array $data);

    /**
     * Buat repository baru dari upload manual (tanpa laporan akhir)
     */
    public function createManual(array $data);

    /**
     * Update repository
     */
    public function update($id, array $data);

    /**
     * Hapus repository
     */
    public function delete($id);

    /**
     * Hapus repository berdasarkan laporan akhir ID
     * Digunakan ketika status laporan akhir berubah atau ditolak
     */
    public function deleteByLaporanAkhir($laporanAkhirId);

    /**
     * Publish repository
     */
    public function publish($id);

    /**
     * Unpublish repository
     */
    public function unpublish($id);



    /**
     * Search repository berdasarkan keyword
     */
    public function search($keyword);

    /**
     * Ambil statistik repository
     */
    public function getStatistics();
}
