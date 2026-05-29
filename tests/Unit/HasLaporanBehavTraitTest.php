<?php

namespace Tests\Unit;

use App\Models\Traits\HasLaporanBehav;
use PHPUnit\Framework\TestCase;

class HasLaporanBehavTraitTest extends TestCase
{
    public function test_get_is_lap_selesai_reflects_laporan_akhir_terima_state(): void
    {
        $subject = new LaporanBehavSubject();

        $subject->setLaporanDiterima(true);
        $this->assertTrue($subject->getIsLapSelesai());

        $subject->setLaporanDiterima(false);
        $this->assertFalse($subject->getIsLapSelesai());
    }

    public function test_get_bisa_laporan_requires_target_tercapai_and_all_tasks_done(): void
    {
        $subject = new LaporanBehavSubject();
        $subject->target_method = 'manual';
        $subject->target_waktu_tugas = 100;
        $subject->waktu_tugas_tercapai = 120;
        $subject->setSemuaTugasSelesai(true);
        $this->assertTrue($subject->getBisaLaporan());

        $subject->setSemuaTugasSelesai(false);
        $this->assertFalse($subject->getBisaLaporan());

        $subject->target_method = 'sks';
        $subject->target_bobot_tugas = 135;
        $subject->waktu_tugas_tercapai = 120;
        $subject->setSemuaTugasSelesai(true);
        $this->assertFalse($subject->getBisaLaporan());
    }

    public function test_get_is_aktif_form_depends_on_laporan_status_and_remaining_capacity(): void
    {
        $subject = new LaporanBehavSubject();
        $subject->target_method = 'manual';
        $subject->target_waktu_tugas = 100;
        $subject->waktu_maksimum = 140;
        $subject->setLaporanDiterima(false);
        $this->assertTrue($subject->getIsAktifForm());

        $subject->setLaporanDiterima(true);
        $this->assertFalse($subject->getIsAktifForm());

        $subject->setLaporanDiterima(false);
        $subject->waktu_maksimum = 100;
        $this->assertFalse($subject->getIsAktifForm());
    }

    public function test_edit_permissions_and_protected_fields_follow_laporan_completion(): void
    {
        $subject = new LaporanBehavSubject();

        $subject->setLaporanDiterima(false);
        $this->assertTrue($subject->getCanEditAkademis());
        $this->assertSame([], $subject->getProtectedFields());

        $subject->setLaporanDiterima(true);
        $this->assertFalse($subject->getCanEditAkademis());
        $this->assertSame([
            'sks',
            'tanggal_mulai_magang',
            'tanggal_selesai_magang',
            'target_method',
            'target_waktu_tugas',
            'tipe_magang',
        ], $subject->getProtectedFields());
    }
}

class LaporanBehavSubject
{
    use HasLaporanBehav;

    public string $target_method = 'manual';
    public float $target_bobot_tugas = 0;
    public float $target_waktu_tugas = 0;
    public float $waktu_tugas_tercapai = 0;
    public float $waktu_maksimum = 0;

    private bool $laporanDiterima = false;
    private bool $semuaTugasSelesai = true;

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

    public function isSemuaTugasSelesai(): bool
    {
        return $this->semuaTugasSelesai;
    }

    public function setLaporanDiterima(bool $state): void
    {
        $this->laporanDiterima = $state;
    }

    public function setSemuaTugasSelesai(bool $state): void
    {
        $this->semuaTugasSelesai = $state;
    }
}
