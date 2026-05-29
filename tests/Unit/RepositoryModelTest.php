<?php

namespace Tests\Unit;

use App\Models\LaporanAkhir;
use App\Models\Peserta;
use App\Models\Repository;
use App\Models\User;
use Tests\TestCase;

class RepositoryModelTest extends TestCase
{
    public function test_nama_peserta_lengkap_prefers_related_peserta_name(): void
    {
        $repository = new Repository(['nama_peserta' => 'Manual Name']);

        $peserta = new Peserta();
        $peserta->setAttribute('nama_lengkap', 'Peserta Sistem');
        $repository->setRelation('peserta', $peserta);

        $this->assertSame('Peserta Sistem', $repository->nama_peserta_lengkap);
    }

    public function test_nama_peserta_lengkap_falls_back_to_related_user_name_then_manual_name(): void
    {
        $repository = new Repository(['nama_peserta' => 'Manual Name']);

        $peserta = new Peserta();
        $peserta->setAttribute('nama_lengkap', null);
        $user = new User(['name' => 'User Sistem']);
        $peserta->setRelation('user', $user);

        $repository->setRelation('peserta', $peserta);
        $this->assertSame('User Sistem', $repository->nama_peserta_lengkap);

        $repository->unsetRelation('peserta');
        $this->assertSame('Manual Name', $repository->nama_peserta_lengkap);
    }

    public function test_file_path_laporan_prefers_laporan_akhir_file_path(): void
    {
        $repository = new Repository(['file_path' => 'manual/file.pdf']);

        $laporanAkhir = new LaporanAkhir();
        $laporanAkhir->setAttribute('file_path', 'sistem/laporan.pdf');
        $repository->setRelation('laporanAkhir', $laporanAkhir);

        $this->assertSame('sistem/laporan.pdf', $repository->file_path_laporan);

        $repository->unsetRelation('laporanAkhir');
        $this->assertSame('manual/file.pdf', $repository->file_path_laporan);
    }

    public function test_is_manual_is_true_when_laporan_akhir_id_is_null(): void
    {
        $manual = new Repository(['laporan_akhir_id' => null]);
        $sistem = new Repository(['laporan_akhir_id' => 10]);

        $this->assertTrue($manual->is_manual);
        $this->assertFalse($sistem->is_manual);
    }
}
