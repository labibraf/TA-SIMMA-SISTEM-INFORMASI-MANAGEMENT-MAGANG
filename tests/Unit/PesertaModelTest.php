<?php

namespace Tests\Unit;

use App\Models\Peserta;
use PHPUnit\Framework\TestCase;

class PesertaModelTest extends TestCase
{
    public function test_target_bobot_tugas_uses_sks_when_method_is_sks(): void
    {
        $peserta = new Peserta([
            'target_method' => 'sks',
            'sks' => 3,
            'target_waktu_tugas' => 120,
        ]);

        $this->assertSame(135.0, $peserta->target_bobot_tugas);
    }

    public function test_target_bobot_tugas_uses_manual_target_when_method_is_manual(): void
    {
        $peserta = new Peserta([
            'target_method' => 'manual',
            'sks' => 3,
            'target_waktu_tugas' => 120,
        ]);

        $this->assertSame(120, $peserta->target_bobot_tugas);
    }

    public function test_persentase_target_tugas_returns_zero_when_target_is_not_positive(): void
    {
        $peserta = new Peserta([
            'target_method' => 'manual',
            'target_waktu_tugas' => 0,
            'waktu_tugas_tercapai' => 20,
        ]);

        $this->assertSame(0, $peserta->persentase_target_tugas);
    }

    public function test_persentase_target_tugas_is_capped_at_100_percent(): void
    {
        $peserta = new Peserta([
            'target_method' => 'manual',
            'target_waktu_tugas' => 100,
            'waktu_tugas_tercapai' => 140,
        ]);

        $this->assertSame(100.0, $peserta->persentase_target_tugas);
    }

    public function test_warning_batas_maksimal_messages_follow_remaining_hours(): void
    {
        $pesertaMaks = new Peserta([
            'tanggal_mulai_magang' => '2026-03-02',
            'tanggal_selesai_magang' => '2026-03-20',
            'waktu_tugas_tercapai' => 120,
        ]);
        $this->assertSame(
            'Batas waktu maksimum tercapai. Tidak dapat menambah tugas lagi.',
            $pesertaMaks->warning_batas_maksimal
        );

        $pesertaHampirMaks = new Peserta([
            'tanggal_mulai_magang' => '2026-03-02',
            'tanggal_selesai_magang' => '2026-03-20',
            'waktu_tugas_tercapai' => 115,
        ]);
        $this->assertSame(
            'Perhatian: Sisa kuota waktu tinggal 5 jam sebelum mencapai batas maksimum.',
            $pesertaHampirMaks->warning_batas_maksimal
        );

        $pesertaAman = new Peserta([
            'tanggal_mulai_magang' => '2026-03-02',
            'tanggal_selesai_magang' => '2026-03-20',
            'waktu_tugas_tercapai' => 90,
        ]);
        $this->assertNull($pesertaAman->warning_batas_maksimal);
    }
}
