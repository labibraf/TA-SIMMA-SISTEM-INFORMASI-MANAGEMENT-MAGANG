<?php

namespace Tests\Unit;

use App\Models\Traits\HasStatusMagang;
use PHPUnit\Framework\TestCase;

class HasStatusMagangTraitTest extends TestCase
{
    public function test_get_status_mag_returns_expected_priority_states(): void
    {
        $subject = new StatusMagangSubject();

        $subject->bisa_laporan_akhir = true;
        $subject->progress_percentage = 0;
        $this->assertSame('Siap Laporan Akhir', $subject->getStatusMag());

        $subject->bisa_laporan_akhir = false;
        $subject->progress_percentage = 100;
        $this->assertSame('Waktu Habis', $subject->getStatusMag());

        $subject->progress_percentage = 40;
        $this->assertSame('Berjalan', $subject->getStatusMag());

        $subject->progress_percentage = 0;
        $this->assertSame('Awal', $subject->getStatusMag());
    }

    public function test_get_status_text_handles_lulus_aktif_kurang_jam_dan_menunggu(): void
    {
        $subject = new StatusMagangSubject();

        $subject->setLaporanDiterima(true);
        $this->assertSame('Sudah Lulus', $subject->getStatusText());

        $subject->setLaporanDiterima(false);
        $subject->sisa_hari_kerja = 5;
        $this->assertSame('Aktif Magang', $subject->getStatusText());

        $subject->sisa_hari_kerja = 0;
        $subject->waktu_tugas_tercapai = 70;
        $subject->target_waktu_tugas = 100;
        $this->assertSame('Masa berakhir - Kurang 30 jam', $subject->getStatusText());

        $subject->waktu_tugas_tercapai = 100;
        $subject->target_waktu_tugas = 100;
        $this->assertSame('Menunggu Kelulusan', $subject->getStatusText());
    }

    public function test_get_is_aktif_proxies_bisa_menerima_tugas_baru(): void
    {
        $subject = new StatusMagangSubject();

        $subject->setCanReceiveTask(true);
        $this->assertTrue($subject->getIsAktif());

        $subject->setCanReceiveTask(false);
        $this->assertFalse($subject->getIsAktif());
    }
}

class StatusMagangSubject
{
    use HasStatusMagang;

    public bool $bisa_laporan_akhir = false;
    public float $progress_percentage = 0;
    public int $sisa_hari_kerja = 0;
    public float $waktu_tugas_tercapai = 0;
    public float $target_waktu_tugas = 0;

    private bool $laporanDiterima = false;
    private bool $canReceiveTask = true;

    public function laporanAkhir()
    {
        return new class($this->laporanDiterima)
        {
            public function __construct(private bool $existsResult)
            {
            }

            public function where($column, $operator = null, $value = null)
            {
                return $this;
            }

            public function exists(): bool
            {
                return $this->existsResult;
            }
        };
    }

    public function bisaMenerimaTugasBaru(): bool
    {
        return $this->canReceiveTask;
    }

    public function setLaporanDiterima(bool $state): void
    {
        $this->laporanDiterima = $state;
    }

    public function setCanReceiveTask(bool $state): void
    {
        $this->canReceiveTask = $state;
    }
}
