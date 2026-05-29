<?php

namespace Tests\Unit;

use App\Models\Traits\HasPenugasanBehav;
use PHPUnit\Framework\TestCase;

class HasPenugasanBehavTraitTest extends TestCase
{
    public function test_can_add_jam_validates_against_waktu_maksimum(): void
    {
        $subject = new PenugasanBehavSubject();
        $subject->waktu_tugas_tercapai = 70;
        $subject->waktu_maksimum = 80;

        $this->assertTrue($subject->canAddJam(10));
        $this->assertFalse($subject->canAddJam(10.5));
    }

    public function test_bisa_menerima_tugas_baru_false_when_laporan_akhir_sudah_diterima(): void
    {
        $subject = new PenugasanBehavSubject();
        $subject->setLaporanDiterima(true);
        $subject->sisa_hari_kerja = 5;
        $subject->waktu_tugas_tercapai = 20;
        $subject->target_waktu_tugas = 100;

        $this->assertFalse($subject->bisaMenerimaTugasBaru());
    }

    public function test_bisa_menerima_tugas_baru_false_when_time_over_and_target_already_met(): void
    {
        $subject = new PenugasanBehavSubject();
        $subject->setLaporanDiterima(false);
        $subject->sisa_hari_kerja = 0;
        $subject->waktu_tugas_tercapai = 120;
        $subject->target_waktu_tugas = 100;

        $this->assertFalse($subject->bisaMenerimaTugasBaru());
    }

    public function test_bisa_menerima_tugas_baru_true_when_not_graduated_and_target_not_met(): void
    {
        $subject = new PenugasanBehavSubject();
        $subject->setLaporanDiterima(false);
        $subject->sisa_hari_kerja = 0;
        $subject->waktu_tugas_tercapai = 70;
        $subject->target_waktu_tugas = 100;

        $this->assertTrue($subject->bisaMenerimaTugasBaru());
    }
}

class PenugasanBehavSubject
{
    use HasPenugasanBehav;

    public int $id = 1;
    public int $bagian_id = 1;
    public float $waktu_tugas_tercapai = 0;
    public float $waktu_maksimum = 0;
    public int $sisa_hari_kerja = 0;
    public float $target_waktu_tugas = 0;

    private bool $laporanDiterima = false;

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

    public function penugasan()
    {
        return new class
        {
            public function count(): int
            {
                return 0;
            }

            public function where($column, $operator = null, $value = null)
            {
                return $this;
            }
        };
    }

    public function setLaporanDiterima(bool $state): void
    {
        $this->laporanDiterima = $state;
    }
}
