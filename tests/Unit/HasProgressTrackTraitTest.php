<?php

namespace Tests\Unit;

use App\Models\Traits\HasProgressTrack;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class HasProgressTrackTraitTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_get_progress_pct_returns_zero_when_dates_are_missing(): void
    {
        $subject = new ProgressTrackSubject();

        $this->assertSame(0, $subject->getProgressPct());
    }

    public function test_get_progress_pct_returns_zero_when_internship_has_not_started(): void
    {
        Carbon::setTestNow('2026-03-01 09:00:00');

        $subject = new ProgressTrackSubject();
        $subject->tanggal_mulai_magang = '2026-03-02';
        $subject->tanggal_selesai_magang = '2026-03-13';

        $this->assertSame(0, $subject->getProgressPct());
    }

    public function test_get_progress_pct_returns_hundred_when_internship_has_ended(): void
    {
        Carbon::setTestNow('2026-03-20 10:00:00');

        $subject = new ProgressTrackSubject();
        $subject->tanggal_mulai_magang = '2026-03-02';
        $subject->tanggal_selesai_magang = '2026-03-13';

        $this->assertSame(100, $subject->getProgressPct());
    }

    public function test_progress_and_working_day_calculations_ignore_weekends(): void
    {
        Carbon::setTestNow('2026-03-11 00:00:00');

        $subject = new ProgressTrackSubject();
        $subject->tanggal_mulai_magang = '2026-03-02';
        $subject->tanggal_selesai_magang = '2026-03-13';

        $this->assertEqualsWithDelta(80.0, $subject->getProgressPct(), 0.01);
        $this->assertEquals(8, $subject->getHariKerjaTercapai());
        $this->assertEquals(3, $subject->getSisaHari());
        $this->assertEquals(24, $subject->getSisaJam());
    }

    public function test_get_sisa_jam_falls_back_to_target_gap_after_internship_period(): void
    {
        Carbon::setTestNow('2026-03-20 10:00:00');

        $subject = new ProgressTrackSubject();
        $subject->tanggal_mulai_magang = '2026-03-02';
        $subject->tanggal_selesai_magang = '2026-03-13';
        $subject->target_waktu_tugas = 100;
        $subject->waktu_tugas_tercapai = 70;

        $this->assertEquals(30, $subject->getSisaJam());
    }

    public function test_waktu_maks_and_durasi_hari_kerja_use_working_days_only(): void
    {
        $subject = new ProgressTrackSubject();
        $subject->tanggal_mulai_magang = '2026-03-02';
        $subject->tanggal_selesai_magang = '2026-03-13';

        $this->assertEquals(80, $subject->getWaktuMaks());
        $this->assertEquals(10, $subject->getDurasiHariKerja());
    }
}

class ProgressTrackSubject
{
    use HasProgressTrack;

    public ?string $tanggal_mulai_magang = null;
    public ?string $tanggal_selesai_magang = null;
    public float $target_waktu_tugas = 0;
    public float $waktu_tugas_tercapai = 0;
}
