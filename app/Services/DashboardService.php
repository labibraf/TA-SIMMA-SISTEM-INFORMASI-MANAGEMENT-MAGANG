<?php

namespace App\Services;

use App\Models\Peserta;
use App\Models\Mentor;
use App\Models\Bagian;
use App\Models\User;
use App\Models\Penugasan;
use App\Models\LaporanAkhir;
use App\Models\LaporanHarian;
use App\Models\Repository;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DashboardService
{
    /**
     * Get Admin Dashboard Data
     */
    public function getAdminDashboardData($filters = [])
    {
        // Update waktu_tugas_tercapai for all peserta to include division tasks
        $this->updateAllPesertaWaktuTugas();

        // Extract filters
        $tahun = $filters['tahun'] ?? null;
        $bulan = $filters['bulan'] ?? null;
        $bagian = $filters['bagian'] ?? null;
        $trendPeriod = $filters['trend_period'] ?? 6;

        // Build base query with filters
        $pesertaQuery = $this->buildPesertaQuery($tahun, $bulan, $bagian);

        // Get peserta IDs for task filtering
        $pesertaIds = (clone $pesertaQuery)->pluck('id')->toArray();

        return [
            // Basic Statistics
            'totalPeserta' => (clone $pesertaQuery)->count(),
            'totalMentor' => Mentor::count(),
            'totalBagian' => Bagian::count(),
            'totalUsers' => User::count(),

            // Peserta Status
            'pesertaSelesai' => $this->getPesertaSelesai($pesertaQuery),
            'pesertaAktif' => $this->getPesertaAktif($pesertaQuery),
            'pesertaHampirSelesai' => $this->getPesertaHampirSelesai($pesertaQuery),

            // Recent Data
            'recentPeserta' => $this->getRecentPeserta($pesertaQuery),

            // Department & Trends
            'bagianDistribution' => $this->getBagianDistribution($tahun, $bulan),
            'monthlyTrend' => $this->getMonthlyTrend($tahun, $bulan, $bagian, $trendPeriod),

            // Working Hours
            'totalJamMagang' => (clone $pesertaQuery)->sum('waktu_tugas_tercapai') ?? 0,

            // Laporan Statistics
            'totalLaporanAkhir' => (clone $pesertaQuery)->has('laporanAkhir')->count(),
            'laporanAkhirSelesai' => $this->getLaporanAkhirSelesai($pesertaQuery),
            'laporanAkhirTolak' => $this->getLaporanAkhirTolak($pesertaQuery),
            'laporanAkhirBelum' => $this->getLaporanAkhirBelum($pesertaQuery),
            'repositoryDraft' => Repository::where('is_published', false)->count(),

            // Task Statistics
            'totalTugas' => $this->getTotalTugas($pesertaIds, $bagian, $tahun, $bulan),
            'tugasSelesai' => $this->getTugasSelesai($pesertaIds, $bagian, $tahun, $bulan),
            'tugasBerjalan' => $this->getTugasBerjalan($pesertaIds, $bagian, $tahun, $bulan),
            'tugasTerlambat' => $this->getTugasTerlambat($pesertaIds, $bagian, $tahun, $bulan),
            'tugasApproved' => $this->getTugasApproved($pesertaIds, $bagian, $tahun, $bulan),
            'tugasPendingApproval' => $this->getTugasPendingApproval($pesertaIds, $bagian, $tahun, $bulan),

            // Progress Statistics
            'pesertaTargetTercapai' => (clone $pesertaQuery)->whereRaw('waktu_tugas_tercapai >= target_waktu_tugas')->count(),
            'pesertaTargetBelum' => (clone $pesertaQuery)->count() - (clone $pesertaQuery)->whereRaw('waktu_tugas_tercapai >= target_waktu_tugas')->count(),

            // Gender Distribution
            'pesertaLakiLaki' => (clone $pesertaQuery)->where('jenis_kelamin', 'Laki-laki')->count(),
            'pesertaPerempuan' => (clone $pesertaQuery)->where('jenis_kelamin', 'Perempuan')->count(),

            // Internship Type
            'magangKP' => (clone $pesertaQuery)->where('tipe_magang', 'Kerja Praktik')->count(),
            'magangNasional' => (clone $pesertaQuery)->where('tipe_magang', 'Magang Nasional')->count(),
            'magangPenelitian' => (clone $pesertaQuery)->where('tipe_magang', 'Penelitian')->count(),

            // Mentor Statistics
            'mentorTertinggi' => $this->getMentorTertinggi($tahun, $bulan, $bagian),
            'rataRataPesertaPerMentor' => $this->getRataRataPesertaPerMentor($pesertaQuery),

            // Progress Distribution
            'pesertaBaru' => (clone $pesertaQuery)->whereRaw('waktu_tugas_tercapai < (target_waktu_tugas * 0.25)')->count(),
            'pesertaMenungah' => (clone $pesertaQuery)->whereRaw('waktu_tugas_tercapai >= (target_waktu_tugas * 0.25) AND waktu_tugas_tercapai < (target_waktu_tugas * 0.75)')->count(),
            'pesertaMahir' => (clone $pesertaQuery)->whereRaw('waktu_tugas_tercapai >= (target_waktu_tugas * 0.75)')->count(),

            // Monthly Performance
            'monthlyCompletions' => $this->getMonthlyCompletions($pesertaQuery),

            // Institution Distribution
            'topInstitutions' => $this->getTopInstitutions($pesertaQuery),

            // Activity Trends
            'dailyActivityTrend' => $this->getDailyActivityTrend($tahun, $bulan, $bagian),

            // Mentor Performance
            'mentorPerformance' => $this->getMentorPerformance($tahun, $bulan, $bagian),

            // Task Categories
            'taskIndividu' => Penugasan::whereIn('peserta_id', $pesertaIds)->where('kategori', 'Individu')->count(),
            'taskDivisi' => $this->getTaskDivisi($bagian, $tahun, $bulan),
            'taskIndividuSelesai' => Penugasan::whereIn('peserta_id', $pesertaIds)->where('kategori', 'Individu')->where('status_tugas', 'Selesai')->count(),
            'taskDivisiSelesai' => $this->getTaskDivisiSelesai($bagian, $tahun, $bulan),

            // Task Approval
            'tugasPending' => $this->getTugasPending($pesertaIds, $bagian, $tahun, $bulan),

            // Target Method
            'targetMethodSKS' => (clone $pesertaQuery)->where('target_method', 'sks')->count(),
            'targetMethodManual' => (clone $pesertaQuery)->count() - (clone $pesertaQuery)->where('target_method', 'sks')->count(),

            // Recent Reports & Approvals
            'recentDailyReports' => $this->getRecentDailyReports(),
            'pendingApprovals' => $this->getPendingApprovals(),

            // Filter Lists
            'tahunList' => $this->getTahunList(),

            // Other data
            'trendPeriod' => $trendPeriod,
        ];
    }

    /**
     * Update waktu_tugas_tercapai for all peserta
     */
    private function updateAllPesertaWaktuTugas()
    {
        $allPeserta = Peserta::all();
        foreach ($allPeserta as $peserta) {
            $peserta->updateWaktuTugasTercapai();
        }
    }

    /**
     * Build base peserta query with filters
     */
    private function buildPesertaQuery($tahun = null, $bulan = null, $bagian = null)
    {
        $query = Peserta::query();

        if ($tahun) {
            $query->whereYear('tanggal_mulai_magang', $tahun);
            if ($bulan) {
                $query->whereMonth('tanggal_mulai_magang', $bulan);
            }
        }

        if ($bagian) {
            $query->whereHas('bagian', function($q) use ($bagian) {
                $q->where('nama_bagian', $bagian);
            });
        }

        return $query;
    }

    /**
     * Get peserta yang sudah selesai
     */
    private function getPesertaSelesai($query)
    {
        return (clone $query)->whereHas('laporanAkhir', function($q) {
            $q->where('status', 'terima');
        })->count();
    }

    /**
     * Get peserta yang masih aktif
     */
    private function getPesertaAktif($query)
    {
        return (clone $query)->whereDoesntHave('laporanAkhir', function($q) {
            $q->where('status', 'terima');
        })->count();
    }

    /**
     * Get peserta hampir selesai (progress < 50%)
     */
    private function getPesertaHampirSelesai($query)
    {
        return (clone $query)->get()->filter(function($peserta) {
            $target = $peserta->target_method === 'sks'
                ? ($peserta->sks * 45)
                : $peserta->target_waktu_tugas;

            if ($target == 0) return false;

            $progress = ($peserta->waktu_tugas_tercapai / $target) * 100;
            return $progress < 50;
        })->count();
    }

    /**
     * Get recent peserta yang selesai
     */
    private function getRecentPeserta($query)
    {
        return (clone $query)
            ->with(['bagian', 'mentor', 'laporanAkhir' => function($query) {
                $query->where('status', 'terima')->latest();
            }])
            ->whereHas('laporanAkhir', function($q) {
                $q->where('status', 'terima');
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($peserta) {
                $target = $peserta->target_method === 'sks'
                    ? ($peserta->sks * 45)
                    : $peserta->target_waktu_tugas;

                $completedHours = $peserta->waktu_tugas_tercapai ?? 0;
                $peserta->progress_percentage = $target > 0 ? round(($completedHours / $target) * 100, 1) : 0;

                $peserta->tahun_magang = $peserta->tanggal_mulai_magang
                    ? Carbon::parse($peserta->tanggal_mulai_magang)->format('Y')
                    : '-';

                $laporanAkhirDiterima = $peserta->laporanAkhir->where('status', 'terima')->first();
                $peserta->tanggal_selesai = $laporanAkhirDiterima
                    ? Carbon::parse($laporanAkhirDiterima->updated_at)->format('d M Y')
                    : '-';

                return $peserta;
            });
    }

    /**
     * Get department distribution
     */
    private function getBagianDistribution($tahun = null, $bulan = null)
    {
        return Bagian::withCount(['pesertas' => function($query) use ($tahun, $bulan) {
            if ($tahun) {
                $query->whereYear('tanggal_mulai_magang', $tahun);
                if ($bulan) {
                    $query->whereMonth('tanggal_mulai_magang', $bulan);
                }
            }
        }])->get();
    }

    /**
     * Get monthly registration trend
     */
    private function getMonthlyTrend($tahun = null, $bulan = null, $bagian = null, $trendPeriod = 6)
    {
        $monthlyTrend = [];

        if ($tahun && $bulan) {
            $endMonth = Carbon::create($tahun, $bulan, 1);
        } elseif ($tahun) {
            $endMonth = Carbon::create($tahun, 12, 1);
            if ($endMonth->isFuture()) {
                $endMonth = now()->startOfMonth();
            }
        } else {
            $endMonth = now()->startOfMonth();
        }

        for ($i = $trendPeriod - 1; $i >= 0; $i--) {
            $month = $endMonth->copy()->subMonths($i);
            $query = Peserta::whereYear('tanggal_mulai_magang', $month->year)
                           ->whereMonth('tanggal_mulai_magang', $month->month);

            if ($bagian) {
                $query->whereHas('bagian', function($q) use ($bagian) {
                    $q->where('nama_bagian', $bagian);
                });
            }

            $count = $query->count();
            $monthlyTrend[] = [
                'month' => $month->format('M Y'),
                'count' => $count
            ];
        }

        return $monthlyTrend;
    }

    /**
     * Get laporan akhir selesai count
     */
    private function getLaporanAkhirSelesai($query)
    {
        return (clone $query)->whereHas('laporanAkhir', function($q) {
            $q->where('status', 'terima');
        })->count();
    }

    /**
     * Get laporan akhir ditolak count
     */
    private function getLaporanAkhirTolak($query)
    {
        return (clone $query)->whereHas('laporanAkhir', function($q) {
            $q->where('status', 'tolak');
        })->count();
    }

    /**
     * Get laporan akhir belum count
     */
    private function getLaporanAkhirBelum($query)
    {
        $totalPeserta = (clone $query)->count();
        $selesai = $this->getLaporanAkhirSelesai($query);
        $tolak = $this->getLaporanAkhirTolak($query);
        return $totalPeserta - $selesai - $tolak;
    }

    /**
     * Build task query base with filters
     */
    private function buildTaskQuery($pesertaIds, $bagian = null, $tahun = null, $bulan = null)
    {
        return Penugasan::where(function($query) use ($pesertaIds, $bagian, $tahun, $bulan) {
            $query->whereIn('peserta_id', $pesertaIds)
                  ->orWhere(function($q) use ($bagian, $tahun, $bulan) {
                      $q->where('kategori', 'Divisi');
                      if ($bagian) {
                          $q->whereHas('bagian', function($subQ) use ($bagian) {
                              $subQ->where('nama_bagian', $bagian);
                          });
                      }
                      if ($tahun) {
                          $q->whereYear('created_at', $tahun);
                          if ($bulan) {
                              $q->whereMonth('created_at', $bulan);
                          }
                      }
                  });
        });
    }

    /**
     * Get total tugas (exclude gugur)
     */
    private function getTotalTugas($pesertaIds, $bagian = null, $tahun = null, $bulan = null)
    {
        return $this->buildTaskQuery($pesertaIds, $bagian, $tahun, $bulan)
            ->where(function($query) {
                $query->whereNull('deadline')
                      ->orWhere('deadline', '>=', now()->startOfDay())
                      ->orWhere('is_approved', 1);
            })
            ->count();
    }

    /**
     * Get tugas selesai
     */
    private function getTugasSelesai($pesertaIds, $bagian = null, $tahun = null, $bulan = null)
    {
        return $this->buildTaskQuery($pesertaIds, $bagian, $tahun, $bulan)
            ->where('status_tugas', 'Selesai')
            ->where('is_approved', 1)
            ->count();
    }

    /**
     * Get tugas berjalan
     */
    private function getTugasBerjalan($pesertaIds, $bagian = null, $tahun = null, $bulan = null)
    {
        return $this->buildTaskQuery($pesertaIds, $bagian, $tahun, $bulan)
            ->where('status_tugas', 'Dikerjakan')
            ->where(function($query) {
                $query->whereNull('deadline')
                      ->orWhere('deadline', '>=', now()->startOfDay())
                      ->orWhere('is_approved', 1);
            })
            ->count();
    }

    /**
     * Get tugas terlambat (gugur) - deadline lewat dan belum di-approve
     */
    private function getTugasTerlambat($pesertaIds, $bagian = null, $tahun = null, $bulan = null)
    {
        return $this->buildTaskQuery($pesertaIds, $bagian, $tahun, $bulan)
            ->where('deadline', '<', now()->startOfDay())
            ->where('is_approved', '!=', 1)
            ->count();
    }

    /**
     * Get tugas approved
     */
    private function getTugasApproved($pesertaIds, $bagian = null, $tahun = null, $bulan = null)
    {
        return $this->buildTaskQuery($pesertaIds, $bagian, $tahun, $bulan)
            ->where('is_approved', 1)
            ->count();
    }

    /**
     * Get tugas pending approval (exclude gugur)
     */
    private function getTugasPendingApproval($pesertaIds, $bagian = null, $tahun = null, $bulan = null)
    {
        return $this->buildTaskQuery($pesertaIds, $bagian, $tahun, $bulan)
            ->where('is_approved', 0)
            ->where('status_tugas', 'Selesai')
            ->where(function($query) {
                $query->whereNull('deadline')
                      ->orWhere('deadline', '>=', now()->startOfDay());
            })
            ->count();
    }

    /**
     * Get mentor with highest peserta count
     */
    private function getMentorTertinggi($tahun = null, $bulan = null, $bagian = null)
    {
        return Mentor::withCount(['pesertas' => function($query) use ($tahun, $bulan, $bagian) {
            if ($tahun) {
                $query->whereYear('tanggal_mulai_magang', $tahun);
                if ($bulan) {
                    $query->whereMonth('tanggal_mulai_magang', $bulan);
                }
            }
            if ($bagian) {
                $query->whereHas('bagian', function($q) use ($bagian) {
                    $q->where('nama_bagian', $bagian);
                });
            }
        }])
        ->having('pesertas_count', '>', 0)
        ->orderBy('pesertas_count', 'desc')
        ->first();
    }

    /**
     * Get rata-rata peserta per mentor
     */
    private function getRataRataPesertaPerMentor($pesertaQuery)
    {
        $totalPeserta = (clone $pesertaQuery)->count();
        $totalMentor = Mentor::count();
        return $totalMentor > 0 ? round($totalPeserta / $totalMentor, 1) : 0;
    }

    /**
     * Get monthly completions trend
     */
    private function getMonthlyCompletions($pesertaQuery)
    {
        $monthlyCompletions = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $query = (clone $pesertaQuery)->whereYear('tanggal_selesai_magang', $month->year)
                           ->whereMonth('tanggal_selesai_magang', $month->month)
                           ->where('tanggal_selesai_magang', '<', now());

            $count = $query->count();
            $monthlyCompletions[] = [
                'month' => $month->format('M Y'),
                'count' => $count
            ];
        }
        return $monthlyCompletions;
    }

    /**
     * Get top institutions
     */
    private function getTopInstitutions($pesertaQuery)
    {
        return (clone $pesertaQuery)->selectRaw('asal_instansi, COUNT(*) as count')
            ->groupBy('asal_instansi')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Get daily activity trend
     */
    private function getDailyActivityTrend($tahun = null, $bulan = null, $bagian = null)
    {
        $dailyActivityTrend = [];

        if ($tahun && $bulan) {
            $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
            $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth();
        } else {
            $startDate = now()->subDays(29);
            $endDate = now();
        }

        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $count = LaporanHarian::whereHas('peserta', function($q) use ($tahun, $bulan, $bagian) {
                if ($tahun) {
                    $q->whereYear('tanggal_mulai_magang', $tahun);
                    if ($bulan) {
                        $q->whereMonth('tanggal_mulai_magang', $bulan);
                    }
                }
                if ($bagian) {
                    $q->whereHas('bagian', function($subQ) use ($bagian) {
                        $subQ->where('nama_bagian', $bagian);
                    });
                }
            })->whereDate('created_at', $currentDate->format('Y-m-d'))->count();

            $dailyActivityTrend[] = [
                'date' => $currentDate->format('d M'),
                'count' => $count
            ];

            $currentDate->addDay();
        }

        return $dailyActivityTrend;
    }

    /**
     * Get attendance heatmap data
     */
    /**
     * Get mentor performance data
     */
    private function getMentorPerformance($tahun = null, $bulan = null, $bagian = null)
    {
        return Mentor::withCount(['pesertas' => function($query) use ($tahun, $bulan, $bagian) {
            if ($tahun) {
                $query->whereYear('tanggal_mulai_magang', $tahun);
                if ($bulan) {
                    $query->whereMonth('tanggal_mulai_magang', $bulan);
                }
            }
            if ($bagian) {
                $query->whereHas('bagian', function($q) use ($bagian) {
                    $q->where('nama_bagian', $bagian);
                });
            }
            $query->where('tanggal_selesai_magang', '<', now());
        }])
        ->with(['pesertas' => function($query) use ($tahun, $bulan, $bagian) {
            if ($tahun) {
                $query->whereYear('tanggal_mulai_magang', $tahun);
                if ($bulan) {
                    $query->whereMonth('tanggal_mulai_magang', $bulan);
                }
            }
            if ($bagian) {
                $query->whereHas('bagian', function($q) use ($bagian) {
                    $q->where('nama_bagian', $bagian);
                });
            }
            $query->select('id', 'mentor_id', 'waktu_tugas_tercapai', 'target_waktu_tugas', 'target_method', 'sks', 'bagian_id');
        }])
        ->having('pesertas_count', '>', 0)
        ->get()
        ->map(function($mentor) {
            $totalPeserta = $mentor->pesertas->count();
            $completedTasks = 0;

            foreach ($mentor->pesertas as $peserta) {
                $target = $peserta->target_method === 'sks' ? ($peserta->sks * 45) : $peserta->target_waktu_tugas;
                if ($peserta->waktu_tugas_tercapai >= $target) {
                    $completedTasks++;
                }
            }

            return [
                'nama' => $mentor->nama_mentor,
                'total_peserta' => $totalPeserta,
                'completed' => $completedTasks,
                'completion_rate' => $totalPeserta > 0 ? round(($completedTasks / $totalPeserta) * 100, 1) : 0
            ];
        })
        ->sortByDesc('total_peserta')
        ->take(10)
        ->values();
    }

    /**
     * Get task divisi count
     */
    private function getTaskDivisi($bagian = null, $tahun = null, $bulan = null)
    {
        $query = Penugasan::where('kategori', 'Divisi');

        if ($bagian) {
            $query->whereHas('bagian', function($subQ) use ($bagian) {
                $subQ->where('nama_bagian', $bagian);
            });
        }
        if ($tahun) {
            $query->whereYear('created_at', $tahun);
            if ($bulan) {
                $query->whereMonth('created_at', $bulan);
            }
        }

        return $query->count();
    }

    /**
     * Get task divisi selesai count
     */
    private function getTaskDivisiSelesai($bagian = null, $tahun = null, $bulan = null)
    {
        $query = Penugasan::where('kategori', 'Divisi')->where('status_tugas', 'Selesai');

        if ($bagian) {
            $query->whereHas('bagian', function($subQ) use ($bagian) {
                $subQ->where('nama_bagian', $bagian);
            });
        }
        if ($tahun) {
            $query->whereYear('created_at', $tahun);
            if ($bulan) {
                $query->whereMonth('created_at', $bulan);
            }
        }

        return $query->count();
    }

    /**
     * Get tugas pending count
     */
    private function getTugasPending($pesertaIds, $bagian = null, $tahun = null, $bulan = null)
    {
        return $this->buildTaskQuery($pesertaIds, $bagian, $tahun, $bulan)
            ->where('is_approved', 0)
            ->where('status_tugas', 'Selesai')
            ->where(function($query) {
                $query->whereNull('deadline')
                      ->orWhere('deadline', '>=', now()->startOfDay());
            })
            ->count();
    }

    /**
     * Get recent daily reports
     */
    private function getRecentDailyReports()
    {
        return LaporanHarian::with(['peserta.user', 'penugasan'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($laporan) {
                return [
                    'peserta_nama' => $laporan->peserta->nama_lengkap ?? 'N/A',
                    'tugas' => Str::limit($laporan->penugasan->judul_tugas ?? 'N/A', 30),
                    'progres' => $laporan->progres_tugas,
                    'tanggal' => $laporan->created_at->format('d M Y H:i'),
                    'status' => $laporan->status_tugas
                ];
            });
    }

    /**
     * Get pending approvals
     */
    private function getPendingApprovals()
    {
        return Penugasan::with(['peserta.user', 'bagian'])
            ->where('status_tugas', 'Selesai')
            ->where('is_approved', 0)
            ->where(function($query) {
                $query->whereNull('deadline')
                      ->orWhere('deadline', '>=', now()->startOfDay());
            })
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($tugas) {
                return [
                    'id' => $tugas->id,
                    'judul' => $tugas->judul_tugas,
                    'peserta' => $tugas->kategori === 'Individu'
                        ? ($tugas->peserta->nama_lengkap ?? 'N/A')
                        : 'Divisi ' . ($tugas->bagian->nama_bagian ?? 'N/A'),
                    'kategori' => $tugas->kategori,
                    'beban_waktu' => $tugas->beban_waktu,
                    'updated_at' => $tugas->updated_at->diffForHumans()
                ];
            });
    }

    /**
     * Get overdue tasks
     */
    /**
     * Get tahun list for filter
     */
    private function getTahunList()
    {
        return Peserta::selectRaw('YEAR(tanggal_mulai_magang) as tahun')
            ->whereNotNull('tanggal_mulai_magang')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun');
    }

    /**
     * ========================================
     * MENTOR DASHBOARD METHODS
     * ========================================
     */

    /**
     * Get Mentor Dashboard Data
     */
    public function getMentorDashboardData($mentorId, $filters = [])
    {
        // Extract filters
        $tahun = $filters['tahun'] ?? null;
        $bulan = $filters['bulan'] ?? null;

        // Build base query for filtered peserta
        $pesertaQuery = Peserta::where('mentor_id', $mentorId);

        if ($tahun) {
            $pesertaQuery->whereYear('tanggal_mulai_magang', $tahun);
            if ($bulan) {
                $pesertaQuery->whereMonth('tanggal_mulai_magang', $bulan);
            }
        }

        // Get filtered peserta IDs
        $pesertaIds = (clone $pesertaQuery)->pluck('id')->toArray();

        // Update waktu_tugas_tercapai for all peserta under this mentor
        $pesertaBimbingan = Peserta::where('mentor_id', $mentorId)->get();
        foreach ($pesertaBimbingan as $peserta) {
            $peserta->updateWaktuTugasTercapai();
        }

        // Generate tahunList for filter dropdown
        $tahunList = Peserta::where('mentor_id', $mentorId)
            ->selectRaw('DISTINCT YEAR(tanggal_mulai_magang) as tahun')
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->filter()
            ->values();

        return [
            // Basic Statistics
            'totalPesertaBimbingan' => (clone $pesertaQuery)->count(),
            'pesertaLulus' => $this->getMentorPesertaLulus($pesertaQuery),
            'pesertaAktif' => $this->getMentorPesertaAktif($pesertaQuery),
            'pesertaPerformaRendah' => $this->getMentorPesertaPerformaRendah($pesertaQuery),

            // Task Statistics
            'totalTugas' => $this->getMentorTotalTugas($pesertaIds, $mentorId, $tahun, $bulan),
            'tugasSelesai' => $this->getMentorTugasSelesai($pesertaIds, $mentorId, $tahun, $bulan),
            'tugasAktif' => $this->getMentorTugasAktif($pesertaIds, $mentorId, $tahun, $bulan),
            'tugasPerluReview' => $this->getMentorTugasPerluReview($pesertaIds, $mentorId, $tahun, $bulan),
            'tugasTerlambat' => $this->getMentorTugasTerlambat($pesertaIds, $mentorId, $tahun, $bulan),

            // Laporan Statistics
            'reviewLaporanAkhir' => LaporanAkhir::whereIn('peserta_id', $pesertaIds)->where('status', 'draft')->count(),
            'totalLaporanAkhir' => LaporanAkhir::whereIn('peserta_id', $pesertaIds)->count(),

            // Tables Data
            'tugasMenungguApproval' => $this->getMentorTugasMenungguApproval($pesertaIds, $mentorId, $tahun, $bulan),
            'pesertaBimbingan' => $this->getMentorPesertaBimbinganList($pesertaQuery),

            // Chart Data
            'pesertaPemula' => $this->getMentorPesertaPemula($pesertaQuery),
            'pesertaMenengah' => $this->getMentorPesertaMenengah($pesertaQuery),
            'pesertaMahir' => $this->getMentorPesertaMahir($pesertaQuery),
            'tugasDikerjakan' => $this->getMentorTugasDikerjakan($pesertaIds, $mentorId, $tahun, $bulan),
            'tugasTerlambatGugur' => $this->getMentorTugasTerlambatGugur($pesertaIds, $mentorId, $tahun, $bulan),

            // Recent Reports
            'laporanHarianTerbaru' => $this->getMentorLaporanHarianTerbaru($pesertaIds),

            // Filter data
            'tahunList' => $tahunList,
            'tahun' => $tahun,
            'bulan' => $bulan,
        ];
    }

    /**
     * Build mentor task query
     */
    private function buildMentorTaskQuery($pesertaIds, $mentorId, $tahun = null, $bulan = null)
    {
        return Penugasan::where(function($query) use ($pesertaIds, $mentorId, $tahun, $bulan) {
            $query->where(function($q) use ($pesertaIds) {
                // Tugas individu untuk peserta yang dibimbing mentor dan sesuai filter
                $q->where('kategori', 'Individu')
                  ->whereIn('peserta_id', $pesertaIds);
            })->orWhere(function($q) use ($mentorId, $tahun, $bulan) {
                // Tugas divisi yang dibuat oleh mentor
                $q->where('kategori', 'Divisi')
                  ->where('mentor_id', $mentorId);
                if ($tahun) {
                    $q->whereYear('created_at', $tahun);
                    if ($bulan) {
                        $q->whereMonth('created_at', $bulan);
                    }
                }
            });
        });
    }

    private function getMentorPesertaLulus($query)
    {
        return (clone $query)->whereHas('laporanAkhir', function($q) {
            $q->where('status', 'terima');
        })->count();
    }

    private function getMentorPesertaAktif($query)
    {
        return (clone $query)->whereDoesntHave('laporanAkhir', function($q) {
            $q->where('status', 'terima');
        })->count();
    }

    private function getMentorPesertaPerformaRendah($query)
    {
        return (clone $query)
            ->whereDoesntHave('laporanAkhir', function($q) {
                $q->where('status', 'terima');
            })
            ->whereRaw('(
                CASE
                    WHEN target_method = "sks" THEN
                        CASE WHEN target_waktu_tugas > 0 THEN (sks / target_waktu_tugas * 100) ELSE 0 END
                    ELSE
                        CASE WHEN target_waktu_tugas > 0 THEN (waktu_tugas_tercapai / target_waktu_tugas * 100) ELSE 0 END
                END
            ) < 25')
            ->count();
    }

    private function getMentorTotalTugas($pesertaIds, $mentorId, $tahun = null, $bulan = null)
    {
        return $this->buildMentorTaskQuery($pesertaIds, $mentorId, $tahun, $bulan)->count();
    }

    private function getMentorTugasSelesai($pesertaIds, $mentorId, $tahun = null, $bulan = null)
    {
        return $this->buildMentorTaskQuery($pesertaIds, $mentorId, $tahun, $bulan)
            ->where('status_tugas', 'Selesai')
            ->count();
    }

    private function getMentorTugasAktif($pesertaIds, $mentorId, $tahun = null, $bulan = null)
    {
        return $this->buildMentorTaskQuery($pesertaIds, $mentorId, $tahun, $bulan)
            ->where('status_tugas', 'Dikerjakan')
            ->count();
    }

    private function getMentorTugasPerluReview($pesertaIds, $mentorId, $tahun = null, $bulan = null)
    {
        return $this->buildMentorTaskQuery($pesertaIds, $mentorId, $tahun, $bulan)
            ->where('is_approved', 0)
            ->where('status_tugas', 'Selesai')
            ->count();
    }

    private function getMentorTugasTerlambat($pesertaIds, $mentorId, $tahun = null, $bulan = null)
    {
        return $this->buildMentorTaskQuery($pesertaIds, $mentorId, $tahun, $bulan)
            ->where('deadline', '<', now())
            ->where('status_tugas', '!=', 'Selesai')
            ->count();
    }

    private function getMentorTugasMenungguApproval($pesertaIds, $mentorId, $tahun = null, $bulan = null)
    {
        $tugas = $this->buildMentorTaskQuery($pesertaIds, $mentorId, $tahun, $bulan)
            ->with(['peserta', 'bagian', 'laporanHarian'])
            ->where('is_approved', 0)
            ->where('status_tugas', 'Selesai')
            ->orderBy('updated_at', 'desc')
            ->get();

        // Filter tugas: hanya tampilkan yang benar-benar siap direview
        return $tugas->filter(function($task) use ($pesertaIds) {
            if ($task->kategori === 'Divisi') {
                // Untuk tugas Divisi: cek apakah SEMUA peserta sudah 100%
                $pesertaDivisi = \App\Models\Peserta::whereIn('id', $pesertaIds)
                    ->where('bagian_id', $task->bagian_id)
                    ->get();

                if ($pesertaDivisi->isEmpty()) {
                    return false;
                }

                // Cek apakah semua peserta sudah mencapai 100%
                $semuaSelesai = $pesertaDivisi->every(function($peserta) use ($task) {
                    $maxProgress = \App\Models\LaporanHarian::where('penugasan_id', $task->id)
                        ->where('peserta_id', $peserta->id)
                        ->max('progres_tugas') ?? 0;

                    return $maxProgress == 100;
                });

                return $semuaSelesai;
            } else {
                // Untuk tugas Individu: langsung tampilkan jika status_tugas = 'Selesai'
                return true;
            }
        })->take(5);
    }

    private function getMentorPesertaBimbinganList($query)
    {
        return (clone $query)
            ->whereDoesntHave('laporanAkhir', function($q) {
                $q->where('status', 'terima');
            })
            ->withCount(['penugasan'])
            ->get()
            ->map(function($peserta) {
                // Hitung progress percentage
                if ($peserta->target_method === 'sks') {
                    $peserta->progress_percentage = $peserta->target_waktu_tugas > 0
                        ? ($peserta->sks / $peserta->target_waktu_tugas * 100)
                        : 0;
                } else {
                    $peserta->progress_percentage = $peserta->target_waktu_tugas > 0
                        ? ($peserta->waktu_tugas_tercapai / $peserta->target_waktu_tugas * 100)
                        : 0;
                }
                return $peserta;
            })
            ->sortByDesc('progress_percentage');
    }

    private function getMentorPesertaPemula($query)
    {
        return (clone $query)
            ->whereDoesntHave('laporanAkhir', function($q) {
                $q->where('status', 'terima');
            })
            ->whereRaw('(
                CASE
                    WHEN target_method = "sks" THEN
                        CASE WHEN target_waktu_tugas > 0 THEN (sks / target_waktu_tugas * 100) ELSE 0 END
                    ELSE
                        CASE WHEN target_waktu_tugas > 0 THEN (waktu_tugas_tercapai / target_waktu_tugas * 100) ELSE 0 END
                END
            ) < 25')
            ->count();
    }

    private function getMentorPesertaMenengah($query)
    {
        return (clone $query)
            ->whereDoesntHave('laporanAkhir', function($q) {
                $q->where('status', 'terima');
            })
            ->whereRaw('(
                CASE
                    WHEN target_method = "sks" THEN
                        CASE WHEN target_waktu_tugas > 0 THEN (sks / target_waktu_tugas * 100) ELSE 0 END
                    ELSE
                        CASE WHEN target_waktu_tugas > 0 THEN (waktu_tugas_tercapai / target_waktu_tugas * 100) ELSE 0 END
                END
            ) BETWEEN 25 AND 75')
            ->count();
    }

    private function getMentorPesertaMahir($query)
    {
        return (clone $query)
            ->whereDoesntHave('laporanAkhir', function($q) {
                $q->where('status', 'terima');
            })
            ->whereRaw('(
                CASE
                    WHEN target_method = "sks" THEN
                        CASE WHEN target_waktu_tugas > 0 THEN (sks / target_waktu_tugas * 100) ELSE 0 END
                    ELSE
                        CASE WHEN target_waktu_tugas > 0 THEN (waktu_tugas_tercapai / target_waktu_tugas * 100) ELSE 0 END
                END
            ) > 75')
            ->count();
    }

    private function getMentorTugasDikerjakan($pesertaIds, $mentorId, $tahun = null, $bulan = null)
    {
        return $this->buildMentorTaskQuery($pesertaIds, $mentorId, $tahun, $bulan)
            ->where('status_tugas', 'Dikerjakan')
            ->count();
    }

    private function getMentorTugasTerlambatGugur($pesertaIds, $mentorId, $tahun = null, $bulan = null)
    {
        return $this->buildMentorTaskQuery($pesertaIds, $mentorId, $tahun, $bulan)
            ->where('deadline', '<', now()->startOfDay())
            ->where('is_approved', '!=', 1)
            ->count();
    }

    private function getMentorLaporanHarianTerbaru($pesertaIds)
    {
        return LaporanHarian::with(['peserta', 'penugasan'])
            ->whereIn('peserta_id', $pesertaIds)
            ->whereNotNull('penugasan_id')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * ========================================
     * PESERTA/INTERN DASHBOARD METHODS
     * ========================================
     */

    /**
     * Get Peserta Dashboard Data
     */
    public function getPesertaDashboardData($peserta)
    {
        // Update waktu tugas tercapai
        $totalJamTercapai = $peserta->updateWaktuTugasTercapai();

        // Calculate progress
        $progressPercentage = $peserta->progress_percentage;
        $targetWaktu = $peserta->target_method === 'sks' ? $peserta->target_bobot_tugas : $peserta->target_waktu_tugas;
        $targetJam = $targetWaktu;

        // Calculate remaining time
        $sisaWaktu = $this->calculateSisaWaktu($peserta);

        // Calculate active tasks (exclude gugur)
        $tugasAktif = $this->getPesertaTugasAktif($peserta);

        // Get laporan akhir status
        $laporanAkhirData = $this->getPesertaLaporanAkhirStatus($peserta);

        // Get tugas list
        $tugasSaya = $this->getPesertaTugasList($peserta);

        // Get mentor & bagian data
        $mentor = $peserta->mentor;
        $bagian = $peserta->bagian;
        $tanggalMulai = Carbon::parse($peserta->tanggal_mulai_magang);
        $tanggalSelesai = Carbon::parse($peserta->tanggal_selesai_magang);
        $tanggalSelesaiFormatted = $tanggalSelesai->format('d M Y');
        $tanggalMulaiFormatted = $tanggalMulai->format('d M Y');

        // Get chart data
        $chartData = $this->getPesertaChartData($peserta);

        // Get recent reports
        $laporanHarianTerbaru = $this->getPesertaLaporanHarianTerbaru($peserta);

        // Get riwayat tugas
        $riwayatTugasSelesai = $this->getPesertaRiwayatTugas($peserta);

        // Get notifications
        $notifikasi = $this->getPesertaNotifikasi($peserta, $progressPercentage);

        return array_merge([
            // Progress Utama
            'progressPercentage' => $progressPercentage,
            'totalJamTercapai' => $totalJamTercapai,
            'targetJam' => $targetJam,
            'tugasAktif' => $tugasAktif,
            'sisaWaktu' => $sisaWaktu,

            // Tabel Tugas
            'tugasSaya' => $tugasSaya,

            // Info Mentor & Magang
            'mentor' => $mentor,
            'bagian' => $bagian,
            'tanggalMulaiFormatted' => $tanggalMulaiFormatted,
            'tanggalSelesaiFormatted' => $tanggalSelesaiFormatted,
            'peserta' => $peserta,

            // Laporan Harian Terbaru
            'laporanHarianTerbaru' => $laporanHarianTerbaru,

            // Riwayat & Notifikasi
            'riwayatTugasSelesai' => $riwayatTugasSelesai,
            'notifikasi' => $notifikasi,
        ], $laporanAkhirData, $chartData);
    }

    /**
     * Calculate sisa waktu magang
     */
    private function calculateSisaWaktu($peserta)
    {
        $tanggalSelesai = Carbon::parse($peserta->tanggal_selesai_magang);

        if ($tanggalSelesai->isFuture()) {
            $interval = now()->diff($tanggalSelesai);
            $bulan = $interval->m + ($interval->y * 12);
            $hari = $interval->d;

            if ($bulan > 0 && $hari > 0) {
                return "$bulan Bulan $hari Hari";
            } elseif ($bulan > 0) {
                return "$bulan Bulan";
            } else {
                return "$hari Hari";
            }
        } else {
            return "Selesai";
        }
    }

    /**
     * Get peserta active tasks count
     */
    private function getPesertaTugasAktif($peserta)
    {
        // Tugas individu aktif (belum selesai DAN tidak gugur)
        $tugasIndividuAktif = Penugasan::where('peserta_id', $peserta->id)
            ->where('is_approved', '!=', 1)
            ->where(function($query) {
                $query->whereNull('deadline')
                      ->orWhere('deadline', '>=', now()->startOfDay())
                      ->orWhere('is_approved', 1);
            })
            ->count();

        // Tugas divisi aktif
        $tugasDivisiAktif = Penugasan::where('kategori', 'Divisi')
            ->where('bagian_id', $peserta->bagian_id)
            ->whereHas('pesertas', function($query) use ($peserta) {
                $query->where('peserta_id', $peserta->id);
            })
            ->where('is_approved', '!=', 1)
            ->where(function($query) {
                $query->whereNull('deadline')
                      ->orWhere('deadline', '>=', now()->startOfDay());
            })
            ->count();

        return $tugasIndividuAktif + $tugasDivisiAktif;
    }

    /**
     * Get laporan akhir status
     */
    private function getPesertaLaporanAkhirStatus($peserta)
    {
        $laporanAkhir = $peserta->laporanAkhir()->latest()->first();

        if ($laporanAkhir) {
            $statusLaporanAkhir = match($laporanAkhir->status) {
                'terima' => 'Disetujui',
                'tolak' => 'Perlu Revisi',
                'pending' => 'Menunggu Review',
                default => 'Belum Mengajukan'
            };
            $badgeClass = match($laporanAkhir->status) {
                'terima' => 'bg-success',
                'tolak' => 'bg-danger',
                'pending' => 'bg-warning',
                default => 'bg-secondary'
            };
        } else {
            $statusLaporanAkhir = 'Belum Mengajukan';
            $badgeClass = 'bg-secondary';
        }

        return [
            'statusLaporanAkhir' => $statusLaporanAkhir,
            'badgeClass' => $badgeClass,
        ];
    }

    /**
     * Get peserta tugas list with isGugur flag
     */
    private function getPesertaTugasList($peserta)
    {
        $tugasSaya = Penugasan::where(function($query) use ($peserta) {
                $query->where('peserta_id', $peserta->id);
                $query->orWhere(function($q) use ($peserta) {
                    $q->where('kategori', 'Divisi')
                      ->where('bagian_id', $peserta->bagian_id)
                      ->whereHas('pesertas', function($pivot) use ($peserta) {
                          $pivot->where('peserta_id', $peserta->id);
                      });
                });
            })
            ->with(['mentor', 'bagian', 'laporanHarian'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Add isGugur property
        $tugasSaya->each(function($tugas) use ($peserta) {
            $isOverdue = $tugas->deadline && now()->greaterThan($tugas->deadline->endOfDay());

            if ($tugas->kategori === 'Divisi') {
                $isSelesaiBetulan = ($tugas->is_approved == 1);
            } else {
                $latestLaporan = $tugas->laporanHarian()->where('peserta_id', $peserta->id)->latest('created_at')->first();
                $progress = $latestLaporan ? $latestLaporan->progres_tugas : 0;
                $isSelesaiBetulan = ($tugas->is_approved == 1 || $progress == 100);
            }

            $tugas->isGugur = $isOverdue && !$isSelesaiBetulan;
        });

        return $tugasSaya;
    }

    /**
     * Get chart data for peserta
     */
    private function getPesertaChartData($peserta)
    {
        // Base query builder
        $baseQuery = function($query) use ($peserta) {
            $query->where('peserta_id', $peserta->id)
                ->orWhere(function($q) use ($peserta) {
                    $q->where('kategori', 'Divisi')
                      ->where('bagian_id', $peserta->bagian_id)
                      ->whereHas('pesertas', function($pivot) use ($peserta) {
                          $pivot->where('peserta_id', $peserta->id);
                      });
                });
        };

        // Tugas Selesai
        $tugasSelesai = Penugasan::where($baseQuery)
            ->where('status_tugas', 'Selesai')
            ->where('is_approved', 1)
            ->count();

        // Tugas Dikerjakan
        $tugasDikerjakan = Penugasan::where($baseQuery)
            ->where('status_tugas', 'Dikerjakan')
            ->count();

        // Tugas Terlambat/Gugur
        $tugasTerlambat = Penugasan::where($baseQuery)
            ->where('deadline', '<', now()->startOfDay())
            ->where('is_approved', '!=', 1)
            ->count();

        // Tugas Belum Dimulai (exclude gugur)
        $tugasBelumDimulai = Penugasan::where($baseQuery)
            ->where('status_tugas', 'Belum Dimulai')
            ->where(function($query) {
                $query->whereNull('deadline')
                      ->orWhere('deadline', '>=', now()->startOfDay())
                      ->orWhere('is_approved', 1);
            })
            ->count();

        // Trend Aktivitas (14 hari terakhir)
        $trendAktivitas = [];
        $trendLabels = [];

        for ($i = 13; $i >= 0; $i--) {
            $tanggal = now()->subDays($i);
            $trendLabels[] = $tanggal->format('d M');

            $jumlahLaporan = LaporanHarian::where('peserta_id', $peserta->id)
                ->whereDate('created_at', $tanggal->format('Y-m-d'))
                ->count();

            $trendAktivitas[] = $jumlahLaporan;
        }

        return [
            'tugasSelesai' => $tugasSelesai,
            'tugasDikerjakan' => $tugasDikerjakan,
            'tugasTerlambat' => $tugasTerlambat,
            'tugasBelumDimulai' => $tugasBelumDimulai,
            'trendAktivitas' => $trendAktivitas,
            'trendLabels' => $trendLabels,
        ];
    }

    /**
     * Get peserta recent laporan harian
     */
    private function getPesertaLaporanHarianTerbaru($peserta)
    {
        return LaporanHarian::where('peserta_id', $peserta->id)
            ->with(['penugasan'])
            ->orderBy('created_at', 'desc')
            ->take(7)
            ->get();
    }

    /**
     * Get peserta riwayat tugas selesai
     */
    private function getPesertaRiwayatTugas($peserta)
    {
        return Penugasan::where(function($query) use ($peserta) {
                $query->where('peserta_id', $peserta->id)
                    ->orWhere(function($q) use ($peserta) {
                        $q->where('kategori', 'Divisi')
                          ->where('bagian_id', $peserta->bagian_id)
                          ->whereNull('peserta_id');
                    });
            })
            ->where('status_tugas', 'Selesai')
            ->where('is_approved', 1)
            ->with(['mentor', 'bagian'])
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get();
    }

    /**
     * Get peserta notifications
     */
    private function getPesertaNotifikasi($peserta, $progressPercentage)
    {
        $notifikasi = [];

        // Notifikasi dari Laporan Akhir yang ditolak
        $laporanAkhirTolak = $peserta->laporanAkhir()
            ->where('status', 'tolak')
            ->latest()
            ->first();

        if ($laporanAkhirTolak && $laporanAkhirTolak->catatan) {
            $notifikasi[] = [
                'type' => 'danger',
                'icon' => 'ti-alert-circle',
                'title' => 'Laporan Akhir Perlu Revisi',
                'message' => $laporanAkhirTolak->catatan,
                'date' => $laporanAkhirTolak->updated_at,
                'action_url' => route('laporan-akhir.index'),
                'action_text' => 'Lihat & Revisi'
            ];
        }

        // Notifikasi dari tugas dengan feedback
        $tugasDenganFeedback = Penugasan::where('peserta_id', $peserta->id)
            ->where('is_approved', 1)
            ->whereNotNull('feedback')
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($tugasDenganFeedback) {
            $notifikasi[] = [
                'type' => 'info',
                'icon' => 'ti-message-circle',
                'title' => 'Feedback dari Mentor',
                'message' => 'Mentor memberikan feedback untuk tugas "' . Str::limit($tugasDenganFeedback->judul_tugas, 40) . '"',
                'date' => $tugasDenganFeedback->updated_at,
                'action_url' => route('penugasans.show', $tugasDenganFeedback->id),
                'action_text' => 'Lihat Feedback'
            ];
        }

        // Notifikasi progres
        if ($progressPercentage >= 75 && $progressPercentage < 100) {
            $notifikasi[] = [
                'type' => 'success',
                'icon' => 'ti-trophy',
                'title' => 'Hampir Selesai!',
                'message' => 'Progres Anda sudah mencapai ' . number_format($progressPercentage, 1) . '%! Pertahankan semangat Anda!',
                'date' => now(),
                'action_url' => null,
                'action_text' => null
            ];
        } elseif ($progressPercentage >= 50 && $progressPercentage < 75) {
            $notifikasi[] = [
                'type' => 'warning',
                'icon' => 'ti-star',
                'title' => 'Setengah Perjalanan',
                'message' => 'Anda sudah menyelesaikan lebih dari setengah target. Terus semangat!',
                'date' => now(),
                'action_url' => null,
                'action_text' => null
            ];
        }

        // Notifikasi deadline mendekat
        $tugasDeadlineMendekat = Penugasan::where(function($query) use ($peserta) {
                $query->where('peserta_id', $peserta->id)
                    ->orWhere(function($q) use ($peserta) {
                        $q->where('kategori', 'Divisi')
                          ->where('bagian_id', $peserta->bagian_id)
                          ->whereNull('peserta_id');
                    });
            })
            ->where('status_tugas', '!=', 'Selesai')
            ->whereBetween('deadline', [now(), now()->addDays(3)])
            ->count();

        if ($tugasDeadlineMendekat > 0) {
            $notifikasi[] = [
                'type' => 'warning',
                'icon' => 'ti-clock-alert',
                'title' => 'Deadline Mendekat',
                'message' => 'Anda memiliki ' . $tugasDeadlineMendekat . ' tugas dengan deadline dalam 3 hari ke depan.',
                'date' => now(),
                'action_url' => route('penugasans.index'),
                'action_text' => 'Lihat Tugas'
            ];
        }

        return $notifikasi;
    }
}
