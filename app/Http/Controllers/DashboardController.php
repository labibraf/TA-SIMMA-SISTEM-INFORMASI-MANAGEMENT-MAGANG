<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Peserta;
use App\Models\Mentor;
use App\Models\Bagian;
use App\Models\User;
use App\Models\Penugasan;
use App\Models\Laporan;
use App\Models\LaporanAkhir;
use App\Models\LaporanHarian;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Redirect based on user role
        if ($user->role_id == 1) { // Admin
            return $this->adminDashboard($request);
        } elseif ($user->role_id == 2) { // Mentor
            return $this->mentorDashboard();
        } else { // Peserta
            return $this->pesertaDashboard();
        }
    }

    public function adminDashboard(Request $request)
    {
        // Update waktu_tugas_tercapai for all peserta to include division tasks
        $allPeserta = Peserta::all();
        foreach ($allPeserta as $peserta) {
            $peserta->updateWaktuTugasTercapai();
        }

        // Get filter parameters
        $tahun = $request->input('tahun');
        $bulan = $request->input('bulan');
        $bagian = $request->input('bagian');
        $trendPeriod = $request->input('trend_period', 6); // Default 6 bulan

        // Debug log untuk melihat filter yang diterima
        \Log::info('Dashboard Filter', [
            'tahun' => $tahun,
            'bulan' => $bulan,
            'bagian' => $bagian,
            'trend_period' => $trendPeriod
        ]);

        // Build query with filters
        $pesertaQuery = Peserta::query();

        // Filter berdasarkan tahun (menggunakan tanggal_mulai_magang)
        if ($tahun) {
            $pesertaQuery->whereYear('tanggal_mulai_magang', $tahun);

            // Filter bulan hanya jika tahun juga dipilih
            if ($bulan) {
                $pesertaQuery->whereMonth('tanggal_mulai_magang', $bulan);
            }
        }

        // Filter berdasarkan bagian/departemen
        if ($bagian) {
            $pesertaQuery->whereHas('bagian', function($q) use ($bagian) {
                $q->where('nama_bagian', $bagian);
            });
        }

        // Basic Statistics (with filters)
        $totalPeserta = (clone $pesertaQuery)->count();

        // Debug log untuk melihat total peserta yang difilter
        \Log::info('Total Peserta after filter', [
            'count' => $totalPeserta,
            'sql' => (clone $pesertaQuery)->toSql()
        ]);

        $totalMentor = Mentor::count();
        $totalBagian = Bagian::count();
        $totalUsers = User::count();

        // Peserta Status (with filters)
        $pesertaSelesai = (clone $pesertaQuery)->whereHas('laporanAkhir', function($q) {
            $q->where('status', 'terima');
        })->count();
        $pesertaAktif = (clone $pesertaQuery)->whereDoesntHave('laporanAkhir', function($q) {
            $q->where('status', 'terima');
        })->count();

        // Peserta dengan progress < 50%
        $pesertaHampirSelesai = (clone $pesertaQuery)->get()->filter(function($peserta) {
            // Hitung target berdasarkan metode
            $target = $peserta->target_method === 'sks'
                ? ($peserta->sks * 45)
                : $peserta->target_waktu_tugas;

            // Jika target 0, skip peserta ini
            if ($target == 0) return false;

            // Hitung progress percentage
            $progress = ($peserta->waktu_tugas_tercapai / $target) * 100;

            // Filter peserta dengan progress < 50%
            return $progress < 50;
        })->count();

        // Recent Peserta Selesai (last 5) - Peserta dengan laporan akhir diterima
        $recentPeserta = (clone $pesertaQuery)
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
                // Calculate target based on method
                $target = $peserta->target_method === 'sks'
                    ? ($peserta->sks * 45)
                    : $peserta->target_waktu_tugas;

                // Calculate progress percentage
                $completedHours = $peserta->waktu_tugas_tercapai ?? 0;
                $peserta->progress_percentage = $target > 0 ? round(($completedHours / $target) * 100, 1) : 0;

                // Get tahun masuk (year from tanggal_mulai_magang)
                $peserta->tahun_magang = $peserta->tanggal_mulai_magang
                    ? Carbon::parse($peserta->tanggal_mulai_magang)->format('Y')
                    : '-';

                // Get tanggal selesai (tanggal laporan akhir diterima)
                $laporanAkhirDiterima = $peserta->laporanAkhir->where('status', 'terima')->first();
                $peserta->tanggal_selesai = $laporanAkhirDiterima
                    ? Carbon::parse($laporanAkhirDiterima->updated_at)->format('d M Y')
                    : '-';

                return $peserta;
            });

        // Upcoming Completions (next 30 days) with filters
        // $upcomingCompletions = (clone $pesertaQuery)->with('bagian')
        //     ->whereBetween('tanggal_selesai_magang', [now(), now()->addDays(30)])
        //     ->orderBy('tanggal_selesai_magang', 'asc')
        //     ->limit(5)
        //     ->get();

        // Department Distribution (with filters)
        $bagianDistribution = Bagian::withCount(['peserta' => function($query) use ($tahun, $bulan) {
            if ($tahun) {
                $query->whereYear('tanggal_mulai_magang', $tahun);
                if ($bulan) {
                    $query->whereMonth('tanggal_mulai_magang', $bulan);
                }
            }
        }])->get();

        // Monthly Registration Trend - with dynamic period filter
        $monthlyTrend = [];
        for ($i = $trendPeriod - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $query = Peserta::whereYear('tanggal_mulai_magang', $month->year)
                           ->whereMonth('tanggal_mulai_magang', $month->month);

            // Apply bagian filter if exists
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

        // Total working hours across all peserta (with filters)
        $totalJamMagang = (clone $pesertaQuery)->sum('waktu_tugas_tercapai') ?? 0;

        // Laporan Akhir Statistics (with filters)
        $totalLaporanAkhir = (clone $pesertaQuery)->has('laporanAkhir')->count(); // Total peserta yang punya laporan akhir (filtered)
        $laporanAkhirSelesai = (clone $pesertaQuery)->whereHas('laporanAkhir', function($q) {
            $q->where('status', 'terima');
        })->count();
        $laporanAkhirTolak = (clone $pesertaQuery)->whereHas('laporanAkhir', function($q) {
            $q->where('status', 'tolak');
        })->count();
        $laporanAkhirBelum = $totalPeserta - $laporanAkhirSelesai - $laporanAkhirTolak; // Total peserta - yang sudah mengajukan

        // Repository Draft Statistics (untuk notifikasi admin)
        $repositoryDraft = \App\Models\Repository::where('is_published', false)->count();

        // Task Completion Statistics (with filters - hanya tugas dari peserta yang difilter)
        $pesertaIds = (clone $pesertaQuery)->pluck('id')->toArray();

        $totalTugas = Penugasan::where(function($query) use ($pesertaIds) {
            $query->whereIn('peserta_id', $pesertaIds)
                  ->orWhere('kategori', 'Divisi');
        })->count();

        $tugasSelesai = Penugasan::where(function($query) use ($pesertaIds) {
            $query->whereIn('peserta_id', $pesertaIds)
                  ->orWhere('kategori', 'Divisi');
        })->where('status_tugas', 'Selesai')->count();

        $tugasBerjalan = Penugasan::where(function($query) use ($pesertaIds) {
            $query->whereIn('peserta_id', $pesertaIds)
                  ->orWhere('kategori', 'Divisi');
        })->where('status_tugas', 'Dikerjakan')->count();

        $tugasBelumDimulai = Penugasan::where(function($query) use ($pesertaIds) {
            $query->whereIn('peserta_id', $pesertaIds)
                  ->orWhere('kategori', 'Divisi');
        })->where('status_tugas', 'Belum')->count();

        // Peserta Progress Statistics (with filters)
        $pesertaTargetTercapai = (clone $pesertaQuery)->whereRaw('waktu_tugas_tercapai >= target_waktu_tugas')->count();
        $pesertaTargetBelum = $totalPeserta - $pesertaTargetTercapai;

        // Gender Distribution (with filters)
        $pesertaLakiLaki = (clone $pesertaQuery)->where('jenis_kelamin', 'Laki-laki')->count();
        $pesertaPerempuan = (clone $pesertaQuery)->where('jenis_kelamin', 'Perempuan')->count();

        // Internship Type Distribution (with filters)
        $magangKP = (clone $pesertaQuery)->where('tipe_magang', 'Kerja Praktik')->count();
        $magangNasional = (clone $pesertaQuery)->where('tipe_magang', 'Magang Nasional')->count();
        $magangPenelitian = (clone $pesertaQuery)->where('tipe_magang', 'Penelitian')->count();

        // Task Approval Statistics (with filters)
        $tugasApproved = Penugasan::where(function($query) use ($pesertaIds) {
            $query->whereIn('peserta_id', $pesertaIds)
                  ->orWhere('kategori', 'Divisi');
        })->where('is_approved', 1)->count();

        // Pending Approval: tugas yang sudah selesai tapi belum di-approve
        $tugasPendingApproval = Penugasan::where(function($query) use ($pesertaIds) {
            $query->whereIn('peserta_id', $pesertaIds)
                  ->orWhere('kategori', 'Divisi');
        })->where('is_approved', 0)->where('status_tugas', 'Selesai')->count();

        // Mentor Workload Statistics (with filters)
        $mentorTertinggi = Mentor::withCount(['peserta' => function($query) use ($tahun, $bulan) {
            if ($tahun) {
                $query->whereYear('tanggal_mulai_magang', $tahun);
                if ($bulan) {
                    $query->whereMonth('tanggal_mulai_magang', $bulan);
                }
            }
        }])->orderBy('peserta_count', 'desc')->first();

        $rataRataPesertaPerMentor = $totalMentor > 0 ? round($totalPeserta / $totalMentor, 1) : 0;

        // Progress Distribution (untuk pie chart tambahan) - with filters
        $pesertaBaru = (clone $pesertaQuery)->whereRaw('waktu_tugas_tercapai < (target_waktu_tugas * 0.25)')->count(); // < 25%
        $pesertaMenungah = (clone $pesertaQuery)->whereRaw('waktu_tugas_tercapai >= (target_waktu_tugas * 0.25) AND waktu_tugas_tercapai < (target_waktu_tugas * 0.75)')->count(); // 25-75%
        $pesertaMahir = (clone $pesertaQuery)->whereRaw('waktu_tugas_tercapai >= (target_waktu_tugas * 0.75)')->count(); // >= 75%

        // Monthly Performance (peserta selesai per bulan dalam 6 bulan terakhir) - with filters
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

        // Institution Distribution (top 5) - with filters
        $topInstitutions = (clone $pesertaQuery)->selectRaw('asal_instansi, COUNT(*) as count')
            ->groupBy('asal_instansi')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        // Age Groups - REMOVED (tidak relevan dengan filter tahun/bulan/bagian)
        $pesertaMuda = 0;
        $pesertaDewasa = 0;
        $pesertaTua = 0;

        // ==================== SECTION A: LINE/AREA CHARTS ====================

        // Daily Activity Trend (last 30 days) - with filters
        $dailyActivityTrend = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = \App\Models\LaporanHarian::whereHas('peserta', function($q) use ($tahun, $bulan, $bagian) {
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
            })->whereDate('created_at', $date->format('Y-m-d'))->count();

            $dailyActivityTrend[] = [
                'date' => $date->format('d M'),
                'count' => $count
            ];
        }        // Attendance Heatmap (last 30 days - weekly view) - with filters
        $attendanceHeatmapData = [];
        $startDate = now()->subDays(34); // Start from 35 days ago to get 5 complete weeks

        // Mapping hari dalam bahasa Indonesia (singkatan)
        $dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

        // Generate 5 weeks of data
        for ($week = 0; $week < 5; $week++) {
            $weekData = [
                'name' => 'Week ' . ($week + 1),
                'data' => []
            ];

            // Generate 7 days for each week
            for ($day = 0; $day < 7; $day++) {
                $currentDate = $startDate->copy()->addDays($week * 7 + $day);

                // Count daily reports for this date - with filters
                $count = \App\Models\LaporanHarian::whereHas('peserta', function($q) use ($tahun, $bulan, $bagian) {
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

                $dayOfWeek = $currentDate->dayOfWeek; // 0 (Minggu) sampai 6 (Sabtu)

                $weekData['data'][] = [
                    'x' => $dayNames[$dayOfWeek],
                    'y' => $count
                ];
            }

            $attendanceHeatmapData[] = $weekData;
        }

        // ==================== SECTION B: BAR CHARTS ====================

        // Mentor Performance (jumlah peserta & completion rate per mentor) - with filters
        $mentorPerformance = Mentor::withCount(['peserta' => function($query) use ($tahun, $bulan) {
            if ($tahun) {
                $query->whereYear('tanggal_mulai_magang', $tahun);
                if ($bulan) {
                    $query->whereMonth('tanggal_mulai_magang', $bulan);
                }
            }
            $query->where('tanggal_selesai_magang', '<', now());
        }])
        ->with(['peserta' => function($query) use ($tahun, $bulan) {
            if ($tahun) {
                $query->whereYear('tanggal_mulai_magang', $tahun);
                if ($bulan) {
                    $query->whereMonth('tanggal_mulai_magang', $bulan);
                }
            }
            $query->select('id', 'mentor_id', 'waktu_tugas_tercapai', 'target_waktu_tugas', 'target_method', 'sks', 'bagian_id');
        }])
        ->get()
        ->map(function($mentor) {
            $totalPeserta = $mentor->peserta->count();
            $completedTasks = 0;

            foreach ($mentor->peserta as $peserta) {
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

        // Task Categories Distribution (with filters)
        $taskIndividu = Penugasan::where(function($query) use ($pesertaIds) {
            $query->whereIn('peserta_id', $pesertaIds);
        })->where('kategori', 'Individu')->count();

        $taskDivisi = Penugasan::where('kategori', 'Divisi')->count();

        $taskIndividuSelesai = Penugasan::where(function($query) use ($pesertaIds) {
            $query->whereIn('peserta_id', $pesertaIds);
        })->where('kategori', 'Individu')->where('status_tugas', 'Selesai')->count();

        $taskDivisiSelesai = Penugasan::where('kategori', 'Divisi')->where('status_tugas', 'Selesai')->count();

        // ==================== SECTION C: PIE/DONUT CHARTS (ADDITIONS) ====================

        // Task Approval Detailed (with filters)
        $tugasPending = Penugasan::where(function($query) use ($pesertaIds) {
            $query->whereIn('peserta_id', $pesertaIds)
                  ->orWhere('kategori', 'Divisi');
        })->where('is_approved', 0)->where('status_tugas', 'Selesai')->count();

        // Target Method Distribution (with filters)
        $targetMethodSKS = (clone $pesertaQuery)->where('target_method', 'sks')->count();
        // Manual = semua yang bukan SKS (termasuk NULL, empty, atau value lain)
        $targetMethodManual = $totalPeserta - $targetMethodSKS;

        // ==================== DATA TABLES & LISTS ====================

        // Recent Daily Reports (last 5) - with filters
        $recentDailyReports = \App\Models\LaporanHarian::with(['peserta.user', 'penugasan'])
            ->whereHas('peserta', function($q) use ($tahun, $bulan, $bagian) {
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
            })
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

        // Pending Approvals (tasks waiting for approval) - with filters
        $pendingApprovals = Penugasan::with(['peserta.user', 'bagian'])
            ->where('status_tugas', 'Selesai')
            ->where('is_approved', 0)
            ->where(function($query) use ($pesertaIds) {
                $query->whereIn('peserta_id', $pesertaIds)
                      ->orWhere('kategori', 'Divisi');
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

        // Overdue Tasks (tasks past deadline and not completed) - with filters
        $overdueTasks = Penugasan::with(['peserta.user', 'bagian'])
            ->where('deadline', '<', now())
            ->where('status_tugas', '!=', 'Selesai')
            ->where(function($query) use ($pesertaIds) {
                $query->whereIn('peserta_id', $pesertaIds)
                      ->orWhere('kategori', 'Divisi');
            })
            ->orderBy('deadline', 'asc')
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
                    'deadline' => $tugas->deadline->format('d M Y'),
                    'days_overdue' => now()->diffInDays($tugas->deadline),
                    'status' => $tugas->status_tugas
                ];
            });

        // Low Performance Alert (peserta with progress < 25%) - with filters
        $lowPerformanceAlert = (clone $pesertaQuery)->with(['bagian', 'mentor'])
            ->get()
            ->filter(function($peserta) {
                $target = $peserta->target_method === 'sks'
                    ? ($peserta->sks * 45)
                    : $peserta->target_waktu_tugas;

                if ($target == 0) return false;

                $progress = ($peserta->waktu_tugas_tercapai / $target) * 100;
                return $progress < 25 && $peserta->tanggal_selesai_magang > now();
            })
            ->map(function($peserta) {
                $target = $peserta->target_method === 'sks'
                    ? ($peserta->sks * 45)
                    : $peserta->target_waktu_tugas;
                $progress = $target > 0 ? round(($peserta->waktu_tugas_tercapai / $target) * 100, 1) : 0;

                return [
                    'id' => $peserta->id,
                    'nama' => $peserta->nama_lengkap,
                    'bagian' => $peserta->bagian->nama_bagian ?? 'N/A',
                    'mentor' => $peserta->mentor->nama_mentor ?? 'N/A',
                    'progress' => $progress,
                    'waktu_tercapai' => $peserta->waktu_tugas_tercapai,
                    'target' => $target,
                    'sisa_hari' => now()->diffInDays($peserta->tanggal_selesai_magang)
                ];
            })
            ->sortBy('progress')
            ->take(10)
            ->values();

        // Get list of years for filter dropdown (berdasarkan tahun magang)
        $tahunList = Peserta::selectRaw('YEAR(tanggal_mulai_magang) as tahun')
            ->whereNotNull('tanggal_mulai_magang')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun');

        return view('dashboard.admin', compact(
            'totalPeserta', 'totalMentor', 'totalBagian', 'totalUsers',
            'pesertaAktif', 'pesertaSelesai', 'pesertaHampirSelesai',
            'recentPeserta', 'bagianDistribution',
            'monthlyTrend', 'totalJamMagang', 'trendPeriod',
            // Pie Chart Data
            'totalLaporanAkhir', 'laporanAkhirSelesai','laporanAkhirTolak', 'laporanAkhirBelum', 'repositoryDraft',
            'totalTugas', 'tugasSelesai', 'tugasBerjalan', 'tugasBelumDimulai',
            'pesertaTargetTercapai', 'pesertaTargetBelum',
            'pesertaLakiLaki', 'pesertaPerempuan',
            'magangKP', 'magangNasional', 'magangPenelitian',
            'tugasApproved', 'tugasPendingApproval',
            'mentorTertinggi', 'rataRataPesertaPerMentor',
            // Additional Analytics
            'pesertaBaru', 'pesertaMenungah', 'pesertaMahir',
            'monthlyCompletions', 'topInstitutions',
            'pesertaMuda', 'pesertaDewasa', 'pesertaTua',
            // Filter data
            'tahunList',
            // NEW: Section A - Line/Area Charts
            'dailyActivityTrend', 'attendanceHeatmapData',
            // NEW: Section B - Bar Charts
            'mentorPerformance', 'taskIndividu', 'taskDivisi',
            'taskIndividuSelesai', 'taskDivisiSelesai',
            // NEW: Section C - Pie Charts Additions
            'tugasPending',
            'targetMethodSKS', 'targetMethodManual',
            // NEW: Data Tables & Lists
            'recentDailyReports', 'pendingApprovals', 'overdueTasks', 'lowPerformanceAlert'
        ));
    }

    public function mentorDashboard()
    {
        if (!Auth::user()->mentor) {
            return redirect()->route('home')->with('error', 'Data mentor tidak ditemukan.');
        }

        $mentorId = Auth::user()->mentor->id;

        // Update waktu_tugas_tercapai for all peserta under this mentor to include division tasks
        $pesertaBimbingan = Peserta::where('mentor_id', $mentorId)->get();
        foreach ($pesertaBimbingan as $peserta) {
            $peserta->updateWaktuTugasTercapai();
        }

        $totalPesertaBimbingan = Peserta::where('mentor_id', $mentorId)->count();
        $pesertaLulus = Peserta::where('mentor_id', $mentorId)
            ->whereHas('laporanAkhir', function($q) {
                $q->where('status', 'terima');
            })->count();
        $pesertaAktif = Peserta::where('mentor_id', $mentorId)
            ->whereDoesntHave('laporanAkhir', function($q) {
                $q->where('status', 'terima');
            })->count();

        $tugasPerluReview = Penugasan::where('is_approved', 0)
            ->where('status_tugas', 'Selesai')
            ->where(function($query) use ($mentorId) {
                $query->where(function($q) use ($mentorId) {
                    // Tugas individu untuk peserta yang dibimbing mentor
                    $q->where('kategori', 'Individu')
                      ->whereHas('peserta', function($subQ) use ($mentorId) {
                          $subQ->where('mentor_id', $mentorId);
                      });
                })->orWhere(function($q) use ($mentorId) {
                    // Tugas divisi yang dibuat oleh mentor
                    $q->where('kategori', 'Divisi')
                      ->where('mentor_id', $mentorId);
                });
            })->count();

        // Hitung total tugas (individu + divisi) untuk mentor
        $totalTugas = Penugasan::where(function($query) use ($mentorId) {
            $query->where(function($q) use ($mentorId) {
                // Tugas individu untuk peserta yang dibimbing mentor
                $q->where('kategori', 'Individu')
                  ->whereHas('peserta', function($subQ) use ($mentorId) {
                      $subQ->where('mentor_id', $mentorId);
                  });
            })->orWhere(function($q) use ($mentorId) {
                // Tugas divisi yang dibuat oleh mentor
                $q->where('kategori', 'Divisi')
                  ->where('mentor_id', $mentorId);
            });
        })->count();

        $tugasSelesai = Penugasan::where('status_tugas', 'Selesai')
            ->where(function($query) use ($mentorId) {
                $query->where(function($q) use ($mentorId) {
                    // Tugas individu untuk peserta yang dibimbing mentor
                    $q->where('kategori', 'Individu')
                      ->whereHas('peserta', function($subQ) use ($mentorId) {
                          $subQ->where('mentor_id', $mentorId);
                      });
                })->orWhere(function($q) use ($mentorId) {
                    // Tugas divisi yang dibuat oleh mentor
                    $q->where('kategori', 'Divisi')
                      ->where('mentor_id', $mentorId);
                });
            })->count();

        $tugasAktif = Penugasan::where('status_tugas', 'Dikerjakan')
            ->where(function($query) use ($mentorId) {
                $query->where(function($q) use ($mentorId) {
                    // Tugas individu untuk peserta yang dibimbing mentor
                    $q->where('kategori', 'Individu')
                      ->whereHas('peserta', function($subQ) use ($mentorId) {
                          $subQ->where('mentor_id', $mentorId);
                      });
                })->orWhere(function($q) use ($mentorId) {
                    // Tugas divisi yang dibuat oleh mentor
                    $q->where('kategori', 'Divisi')
                      ->where('mentor_id', $mentorId);
                });
            })->count();

        $reviewLaporanAkhir = LaporanAkhir::whereHas('peserta', function($q) use ($mentorId) {
            $q->where('mentor_id', $mentorId);
        })
        ->where('status', 'Selesai')
        ->count();
        $tugasAktif = Penugasan::whereHas('peserta', function($q) use ($mentorId) {
            $q->where('mentor_id', $mentorId);
        })
        ->where('status_tugas', 'Dikerjakan')
        ->count();

        $reviewLaporanAkhir = LaporanAkhir::whereHas('peserta', function($q) use ($mentorId) {
            $q->where('mentor_id', $mentorId);
        })
        ->where('status', 'draft')
        ->count();
        $totalLaporanAkhir = LaporanAkhir::whereHas('peserta', function($q) use ($mentorId) {
            $q->where('mentor_id', $mentorId);
        })->count();

        // Peserta Performa Rendah (progress < 25%) - Hanya Peserta Aktif
        // Note: target_method = 'sks' atau 'manual'
        // sks = SKS tercapai, target_waktu_tugas = target, waktu_tugas_tercapai = waktu tercapai
        $pesertaPerformaRendah = Peserta::where('mentor_id', $mentorId)
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

        // Tugas Terlambat (deadline sudah lewat tapi status bukan Selesai)
        $tugasTerlambat = Penugasan::where('deadline', '<', now())
            ->where('status_tugas', '!=', 'Selesai')
            ->where(function($query) use ($mentorId) {
                $query->where(function($q) use ($mentorId) {
                    // Tugas individu untuk peserta yang dibimbing mentor
                    $q->where('kategori', 'Individu')
                      ->whereHas('peserta', function($subQ) use ($mentorId) {
                          $subQ->where('mentor_id', $mentorId);
                      });
                })->orWhere(function($q) use ($mentorId) {
                    // Tugas divisi yang dibuat oleh mentor
                    $q->where('kategori', 'Divisi')
                      ->where('mentor_id', $mentorId);
                });
            })->count();

        // PRIORITAS 1: Data Tabel Menunggu Persetujuan
        $tugasMenungguApproval = Penugasan::with(['peserta', 'bagian'])
            ->where('is_approved', 0)
            ->where('status_tugas', 'Selesai')
            ->where(function($query) use ($mentorId) {
                $query->where(function($q) use ($mentorId) {
                    // Tugas individu untuk peserta yang dibimbing mentor
                    $q->where('kategori', 'Individu')
                      ->whereHas('peserta', function($subQ) use ($mentorId) {
                          $subQ->where('mentor_id', $mentorId);
                      });
                })->orWhere(function($q) use ($mentorId) {
                    // Tugas divisi yang dibuat oleh mentor
                    $q->where('kategori', 'Divisi')
                      ->where('mentor_id', $mentorId);
                });
            })
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        // PRIORITAS 1: Data Tabel Progres Peserta Bimbingan (Hanya Peserta Aktif)
        $pesertaBimbingan = Peserta::where('mentor_id', $mentorId)
            ->whereDoesntHave('laporanAkhir', function($q) {
                $q->where('status', 'terima');
            })
            ->withCount(['penugasan'])
            ->get()
            ->map(function($peserta) {
                // Hitung progress percentage
                // target_method = 'sks' atau 'manual'
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

        // PRIORITAS 2: Data untuk Chart Distribusi Progress Peserta (Hanya Peserta Aktif)
        $pesertaPemula = Peserta::where('mentor_id', $mentorId)
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

        $pesertaMenengah = Peserta::where('mentor_id', $mentorId)
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

        $pesertaMahir = Peserta::where('mentor_id', $mentorId)
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

        // PRIORITAS 2: Data untuk Chart Status Penugasan
        $tugasSelesai = Penugasan::where('status_tugas', 'Selesai')
            ->where(function($query) use ($mentorId) {
                $query->where(function($q) use ($mentorId) {
                    // Tugas individu untuk peserta yang dibimbing mentor
                    $q->where('kategori', 'Individu')
                      ->whereHas('peserta', function($subQ) use ($mentorId) {
                          $subQ->where('mentor_id', $mentorId);
                      });
                })->orWhere(function($q) use ($mentorId) {
                    // Tugas divisi yang dibuat oleh mentor
                    $q->where('kategori', 'Divisi')
                      ->where('mentor_id', $mentorId);
                });
            })->count();

        $tugasDikerjakan = Penugasan::where('status_tugas', 'Dikerjakan')
            ->where(function($query) use ($mentorId) {
                $query->where(function($q) use ($mentorId) {
                    // Tugas individu untuk peserta yang dibimbing mentor
                    $q->where('kategori', 'Individu')
                      ->whereHas('peserta', function($subQ) use ($mentorId) {
                          $subQ->where('mentor_id', $mentorId);
                      });
                })->orWhere(function($q) use ($mentorId) {
                    // Tugas divisi yang dibuat oleh mentor
                    $q->where('kategori', 'Divisi')
                      ->where('mentor_id', $mentorId);
                });
            })->count();

        $tugasBelumDimulai = Penugasan::where('status_tugas', 'Belum')
            ->where(function($query) use ($mentorId) {
                $query->where(function($q) use ($mentorId) {
                    // Tugas individu untuk peserta yang dibimbing mentor
                    $q->where('kategori', 'Individu')
                      ->whereHas('peserta', function($subQ) use ($mentorId) {
                          $subQ->where('mentor_id', $mentorId);
                      });
                })->orWhere(function($q) use ($mentorId) {
                    // Tugas divisi yang dibuat oleh mentor
                    $q->where('kategori', 'Divisi')
                      ->where('mentor_id', $mentorId);
                });
            })->count();

        // PRIORITAS 2: Data Tabel Log Laporan Harian Terbaru
        $laporanHarianTerbaru = LaporanHarian::with(['peserta', 'penugasan'])
            ->whereHas('peserta', function($q) use ($mentorId) {
                $q->where('mentor_id', $mentorId);
            })
            ->whereNotNull('penugasan_id') // Hanya ambil yang memiliki penugasan terkait
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard.mentor', compact(
            // Prioritas 1: Kartu Statistik
            'totalPesertaBimbingan',
            'tugasPerluReview',
            'pesertaLulus',
            'pesertaAktif',
            'reviewLaporanAkhir',
            'totalLaporanAkhir',
            'pesertaPerformaRendah',
            'tugasTerlambat',
            'tugasSelesai',
            'tugasAktif',
            'totalTugas',
            // Prioritas 1: Tabel
            'tugasMenungguApproval',
            'pesertaBimbingan',
            // Prioritas 2: Chart Data
            'pesertaPemula',
            'pesertaMenengah',
            'pesertaMahir',
            'tugasDikerjakan',
            'tugasBelumDimulai',
            // Prioritas 2: Tabel
            'laporanHarianTerbaru'
        ));
    }

    public function pesertaDashboard()
    {
        $user = auth()->user();
        $peserta = $user->peserta;

        // Cek jika peserta tidak ditemukan
        if (!$peserta) {
            return redirect()->back()->with('error', 'Data peserta tidak ditemukan');
        }

        // ========== PRIORITAS 1: KARTU PROGRES UTAMA ==========

        // 1. Progres Magang
        $progressPercentage = $peserta->progress_percentage;
        $targetWaktu = $peserta->target_method === 'sks' ? $peserta->target_bobot_tugas : $peserta->target_waktu_tugas;

        // 2. Total Jam Tercapai
        // Update waktu tugas tercapai terlebih dahulu untuk memastikan data terbaru
        $totalJamTercapai = $peserta->updateWaktuTugasTercapai();
        $targetJam = $targetWaktu;

        // 3. Sisa Hari Magang
        $tanggalSelesai = \Carbon\Carbon::parse($peserta->tanggal_selesai_magang);

        if ($tanggalSelesai->isFuture()) {
            $interval = now()->diff($tanggalSelesai);
            $bulan = $interval->m + ($interval->y * 12);
            $hari = $interval->d;

            if ($bulan > 0 && $hari > 0) {
                $sisaWaktu = "$bulan Bulan $hari Hari";
            } elseif ($bulan > 0) {
                $sisaWaktu = "$bulan Bulan";
            } else {
                $sisaWaktu = "$hari Hari";
            }
        } else {
            $sisaWaktu = "Selesai";
        }

        // 4. Tugas Aktif (belum selesai)
        $tugasAktif = Penugasan::where(function($query) use ($peserta) {
                $query->where('peserta_id', $peserta->id)
                    ->orWhere(function($q) use ($peserta) {
                        // Semua tugas divisi di bagian peserta
                        $q->where('kategori', 'Divisi')
                          ->where('bagian_id', $peserta->bagian_id)
                          ->whereNull('peserta_id');
                    });
            })
            ->whereIn('status_tugas', ['Belum Dimulai', 'Dikerjakan'])
            ->count();

        // 5. Status Laporan Akhir
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

        // ========== PRIORITAS 1: TABEL TUGAS ANDA ==========

        // Ambil semua tugas yang relevan dengan peserta (individu + divisi)
        $tugasSaya = Penugasan::where(function($query) use ($peserta) {
                $query->where('peserta_id', $peserta->id)
                    ->orWhere(function($q) use ($peserta) {
                        // Semua tugas divisi di bagian peserta
                        $q->where('kategori', 'Divisi')
                          ->where('bagian_id', $peserta->bagian_id)
                          ->whereNull('peserta_id');
                    });
            })
            ->with(['mentor', 'bagian'])
            ->orderByRaw("FIELD(status_tugas, 'Dikerjakan', 'Belum Dimulai', 'Selesai')")
            ->orderBy('deadline', 'asc')
            ->get();

        // ========== PRIORITAS 1: INFO MENTOR & MAGANG ==========

        $mentor = $peserta->mentor;
        $bagian = $peserta->bagian;
        $tanggalMulai = \Carbon\Carbon::parse($peserta->tanggal_mulai_magang);
        $tanggalSelesaiFormatted = $tanggalSelesai->format('d M Y');
        $tanggalMulaiFormatted = $tanggalMulai->format('d M Y');

        // ========== PRIORITAS 2: VISUALISASI DATA (CHARTS) ==========

        // 1. Chart: Distribusi Beban Kerja (Status Tugas)
        $tugasSelesai = Penugasan::where(function($query) use ($peserta) {
                $query->where('peserta_id', $peserta->id)
                    ->orWhere(function($q) use ($peserta) {
                        // Semua tugas divisi di bagian peserta
                        $q->where('kategori', 'Divisi')
                          ->where('bagian_id', $peserta->bagian_id)
                          ->whereNull('peserta_id');
                    });
            })
            ->where('status_tugas', 'Selesai')
            ->where('is_approved', 1)
            ->count();

        $tugasDikerjakan = Penugasan::where(function($query) use ($peserta) {
                $query->where('peserta_id', $peserta->id)
                    ->orWhere(function($q) use ($peserta) {
                        // Semua tugas divisi di bagian peserta
                        $q->where('kategori', 'Divisi')
                          ->where('bagian_id', $peserta->bagian_id)
                          ->whereNull('peserta_id');
                    });
            })
            ->where('status_tugas', 'Dikerjakan')
            ->count();

        $tugasBelumDimulai = Penugasan::where(function($query) use ($peserta) {
                $query->where('peserta_id', $peserta->id)
                    ->orWhere(function($q) use ($peserta) {
                        // Semua tugas divisi di bagian peserta
                        $q->where('kategori', 'Divisi')
                          ->where('bagian_id', $peserta->bagian_id)
                          ->whereNull('peserta_id');
                    });
            })
            ->where('status_tugas', 'Belum Dimulai')
            ->count();

        // 2. Chart: Tren Aktivitas Harian (14 hari terakhir)
        $trendAktivitas = [];
        $trendLabels = [];

        for ($i = 13; $i >= 0; $i--) {
            $tanggal = now()->subDays($i);
            $trendLabels[] = $tanggal->format('d M');

            // Hitung jumlah laporan harian pada tanggal tersebut
            $jumlahLaporan = \App\Models\LaporanHarian::where('peserta_id', $peserta->id)
                ->whereDate('created_at', $tanggal->format('Y-m-d'))
                ->count();

            $trendAktivitas[] = $jumlahLaporan;
        }

        // 3. Tabel: Log Laporan Harian Terbaru
        $laporanHarianTerbaru = \App\Models\LaporanHarian::where('peserta_id', $peserta->id)
            ->with(['penugasan'])
            ->orderBy('created_at', 'desc')
            ->take(7)
            ->get();

        // ========== PRIORITAS 3: RIWAYAT & INFORMASI TAMBAHAN ==========

        // 1. Tabel: Riwayat Tugas Selesai (dengan feedback/nilai)
        $riwayatTugasSelesai = Penugasan::where(function($query) use ($peserta) {
                $query->where('peserta_id', $peserta->id)
                    ->orWhere(function($q) use ($peserta) {
                        // Semua tugas divisi di bagian peserta
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

        // 2. Pengumuman/Notifikasi (simulasi - bisa diganti dengan tabel notification)
        // Untuk saat ini kita ambil dari laporan akhir yang perlu revisi atau tugas dengan catatan
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

        // Notifikasi progres (jika sudah mencapai milestone tertentu)
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
                        // Semua tugas divisi di bagian peserta
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

        return view('dashboard.peserta', compact(
            // Progres Utama
            'progressPercentage',
            'totalJamTercapai',
            'targetJam',
            'tugasAktif',
            'sisaWaktu',
            'statusLaporanAkhir',
            'badgeClass',

            // Tabel Tugas
            'tugasSaya',

            // Info Mentor & Magang
            'mentor',
            'bagian',
            'tanggalMulaiFormatted',
            'tanggalSelesaiFormatted',
            'peserta',

            // Charts (Prioritas 2)
            'tugasSelesai',
            'tugasDikerjakan',
            'tugasBelumDimulai',
            'trendAktivitas',
            'trendLabels',
            'laporanHarianTerbaru',

            // Prioritas 3
            'riwayatTugasSelesai',
            'notifikasi'
        ));
    }
}
