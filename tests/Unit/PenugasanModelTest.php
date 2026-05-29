<?php

namespace Tests\Unit;

use App\Models\Bagian;
use App\Models\Penugasan;
use App\Models\Peserta;
use App\Models\User;
use Tests\TestCase;

class PenugasanModelTest extends TestCase
{
    public function test_ditugaskan_returns_user_name_for_individu_task(): void
    {
        $user = new User(['name' => 'Budi']);
        $peserta = new Peserta();
        $peserta->setRelation('user', $user);

        $penugasan = new Penugasan(['kategori' => 'Individu']);
        $penugasan->setRelation('peserta', $peserta);

        $this->assertSame('Budi', $penugasan->ditugaskan);
    }

    public function test_ditugaskan_returns_divisi_name_for_divisi_task(): void
    {
        $bagian = new Bagian(['nama_bagian' => 'Pengembangan']);

        $penugasan = new Penugasan(['kategori' => 'Divisi']);
        $penugasan->setRelation('bagian', $bagian);

        $this->assertSame('Divisi Pengembangan', $penugasan->ditugaskan);
    }

    public function test_ditugaskan_returns_fallback_when_task_has_no_assignee(): void
    {
        $penugasan = new Penugasan(['kategori' => 'Individu']);

        $this->assertSame('Tidak ada peserta', $penugasan->ditugaskan);
    }

    public function test_get_all_pesertas_wraps_individu_assignee_in_collection(): void
    {
        $peserta = new Peserta(['nama_lengkap' => 'Siti']);

        $penugasan = new Penugasan(['kategori' => 'Individu']);
        $penugasan->setRelation('peserta', $peserta);

        $allPesertas = $penugasan->getAllPesertas();

        $this->assertCount(1, $allPesertas);
        $this->assertSame('Siti', $allPesertas->first()->nama_lengkap);
    }

    public function test_get_all_pesertas_returns_empty_collection_when_no_rule_matches(): void
    {
        $penugasan = new Penugasan(['kategori' => 'TidakDikenal']);

        $this->assertTrue($penugasan->getAllPesertas()->isEmpty());
    }
}
