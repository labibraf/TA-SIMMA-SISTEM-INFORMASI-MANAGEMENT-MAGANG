<?php

namespace App\Http\Controllers;

use App\Models\LaporanHarian;
use App\Models\Penugasan;
use App\Models\Peserta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Storage;

class LaporanHarianController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            // Tolak akses jika pengguna BUKAN Peserta, BUKAN Admin, DAN BUKAN Mentor.
            if (!$user->isPeserta() && !$user->isAdmin() && !$user->isMentor()) {
                abort(403, 'AKSES DITOLAK: ANDA TIDAK MEMILIKI HAK AKSES.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $laporanHarian = collect(); // Default collection kosong

        if ($user->isAdmin()) {
            // Admin: melihat semua laporan.
            $laporanHarian = LaporanHarian::with(['peserta.user', 'peserta.bagian', 'penugasan'])
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($user->isMentor()) {
            // Mentor: melihat laporan dari peserta di bagiannya.
            if ($user->mentor?->bagian_id) {
                // 1. Ambil ID semua peserta yang ada di bagian mentor tersebut.
                $pesertaIds = Peserta::where('bagian_id', $user->mentor->bagian_id)->pluck('id');
                // 2. Ambil laporan yang 'peserta_id'-nya ada di dalam daftar ID di atas.
                $laporanHarian = LaporanHarian::whereIn('peserta_id', $pesertaIds)
                    ->with(['peserta.user', 'peserta.bagian', 'penugasan'])
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
        } elseif ($user->isPeserta()) {
            // Peserta: hanya melihat laporannya sendiri.
            if ($user->peserta) {
                $laporanHarian = LaporanHarian::where('peserta_id', $user->peserta->id)
                    ->with(['peserta.user', 'peserta.bagian', 'penugasan'])
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
        }

        return view('laporan_harian.index', compact('laporanHarian'));
    }

    public function show($id)
    {
        // Cari laporan harian berdasarkan ID
        $laporanHarian = LaporanHarian::findOrFail($id);

        // Cek apakah user memiliki akses
        $user = Auth::user();

        if ($user->isPeserta()) {
            // Peserta hanya bisa melihat laporan miliknya sendiri
            if ($laporanHarian->peserta_id !== $user->peserta->id) {
                abort(403, 'AKSES DITOLAK: Anda tidak memiliki akses ke laporan ini.');
            }
        } elseif ($user->isMentor()) {
            // Mentor hanya bisa melihat laporan dari peserta bimbingannya
            if ($laporanHarian->peserta->mentor_id !== $user->mentor->id) {
                abort(403, 'AKSES DITOLAK: Anda tidak memiliki akses ke laporan ini.');
            }
        }

        // Redirect ke detail penugasan terkait jika ada
        if ($laporanHarian->penugasan_id) {
            return redirect()->route('penugasans.show', $laporanHarian->penugasan_id);
        }

        // Jika tidak ada penugasan terkait, redirect ke index dengan pesan
        Alert::info('Info', 'Laporan harian ini tidak terkait dengan penugasan tertentu.');
        return redirect()->route('laporan_harian.index');
    }

    public function create($penugasan_id = null)
    {
        if (!Auth::user()->isPeserta()) {
            abort(403, 'AKSES DITOLAK: HANYA PESERTA YANG BISA MEMBUAT LAPORAN.');
        }

        $user = Auth::user();
        $peserta = $user->peserta;

        // Cek apakah laporan akhir sudah diterima
        if ($peserta->is_laporan_akhir_selesai) {
            Alert::success('Magang Selesai', 'Laporan akhir Anda sudah diterima. Anda tidak dapat lagi membuat laporan harian.');
            return redirect()->route('laporan-harian.index');
        }

        $bagianId = $peserta->bagian_id;

        // Ambil semua penugasan yang bisa diakses peserta:
        // 1. Tugas individu yang ditugaskan ke peserta ini
        // 2. Tugas divisi untuk bagian yang sama
        $penugasans = Penugasan::where(function($query) use ($peserta, $bagianId) {
            // Tugas individu untuk peserta ini
            $query->where(function($q) use ($peserta) {
                $q->where('kategori', 'Individu')
                ->where('peserta_id', $peserta->id);
            })
            // ATAU tugas divisi untuk bagian yang sama
            ->orWhere(function($q) use ($bagianId) {
                $q->where('kategori', 'Divisi')
                ->where('bagian_id', $bagianId)
                ->whereNull('peserta_id');
            });
        })
        ->where(function ($query) {
            // Tugas yang belum selesai atau sudah selesai tapi belum di-approve
            $query->where('status_tugas', '!=', 'Selesai')
                ->orWhere(function($q) {
                    $q->where('status_tugas', 'Selesai')
                      ->where('is_approved', 0); // Belum di-approve
                });
        })
        // EXCLUDE tugas GUGUR: deadline lewat DAN (is_approved != 1 DAN progress < 100)
        ->where(function($query) {
            $query->whereNull('deadline') // Tidak ada deadline
                  ->orWhere('deadline', '>=', now()->startOfDay()) // Belum lewat deadline
                  ->orWhere('is_approved', 1); // ATAU sudah di-approve (dianggap selesai)
        })
        ->get();

        // Ambil progress terakhir untuk setiap penugasan
        $progressData = [];
        $approvalStatus = [];
        foreach ($penugasans as $penugasan) {
            $lastLaporan = LaporanHarian::where('penugasan_id', $penugasan->id)
                ->where('peserta_id', $peserta->id)
                ->latest()
                ->first();
            $progressData[$penugasan->id] = $lastLaporan ? $lastLaporan->progres_tugas : 0;

            // Cek status approval jika progres sudah 100%
            $approvalStatus[$penugasan->id] = [
                'is_complete' => $progressData[$penugasan->id] >= 100,
                'is_approved' => $penugasan->is_approved ?? false,
                'can_add_report' => $progressData[$penugasan->id] < 100 || !($penugasan->is_approved ?? false)
            ];
        }

        // Jika ada penugasan_id dari parameter, ambil data penugasan tersebut
        $selectedPenugasan = null;
        if ($penugasan_id) {
            $selectedPenugasan = Penugasan::where('id', $penugasan_id)
                ->where(function($query) use ($peserta, $bagianId) {
                    // Tugas individu untuk peserta ini
                    $query->where(function($q) use ($peserta) {
                        $q->where('kategori', 'Individu')
                        ->where('peserta_id', $peserta->id);
                    })
                    // ATAU tugas divisi untuk bagian yang sama
                    ->orWhere(function($q) use ($bagianId) {
                        $q->where('kategori', 'Divisi')
                        ->where('bagian_id', $bagianId)
                        ->whereNull('peserta_id');
                    });
                })
                ->first();

            if (!$selectedPenugasan) {
                abort(403, 'AKSES DITOLAK: Anda tidak memiliki akses ke penugasan ini.');
            }

            // Cek apakah tugas sudah di-approve
            if ($selectedPenugasan->is_approved == 1) {
                Alert::error('Akses Ditolak', 'Tugas ini sudah di-approve. Tidak dapat menambah laporan harian lagi.');
                return redirect()->route('penugasans.show', $selectedPenugasan->id);
            }
        }

        return view('laporan_harian.create', compact('penugasans', 'progressData', 'approvalStatus', 'selectedPenugasan'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $peserta = $user->peserta;

        // Cek apakah laporan akhir sudah diterima
        if ($peserta->is_laporan_akhir_selesai) {
            Alert::error('Tidak Dapat Membuat Laporan', 'Laporan akhir Anda sudah diterima. Anda tidak dapat lagi membuat laporan harian.');
            return redirect()->route('laporan-harian.index');
        }

        // Validasi input
        $validated = $request->validate([
            'penugasan_id' => 'required|exists:penugasans,id',
            'tanggal_laporan' => 'required|date',
            'deskripsi_kegiatan' => 'nullable|string',
            'progres_tugas' => 'required|integer|min:0|max:100',
            'file' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:2048',
        ]);

        // Pastikan penugasan bisa diakses oleh peserta
        $penugasan = Penugasan::where('id', $request->penugasan_id)
            ->where(function($query) use ($peserta) {
                // Tugas individu untuk peserta ini
                $query->where(function($q) use ($peserta) {
                    $q->where('kategori', 'Individu')
                    ->where('peserta_id', $peserta->id);
                })
                // ATAU tugas divisi untuk bagian yang sama
                ->orWhere(function($q) use ($peserta) {
                    $q->where('kategori', 'Divisi')
                    ->where('bagian_id', $peserta->bagian_id)
                    ->whereNull('peserta_id');
                });
            })
            ->where(function ($query) {
                // Tugas yang belum selesai atau sudah selesai tapi belum di-approve
                $query->where('status_tugas', '!=', 'Selesai')
                    ->orWhere(function($q) {
                        $q->where('status_tugas', 'Selesai')
                          ->where('is_approved', 0); // Belum di-approve
                    });
            })
            ->firstOrFail();

        // Cegah laporan jika tugas sudah di-approve (validasi tambahan)
        if ($penugasan->is_approved == 1) {
            Alert::error('Akses Ditolak', 'Tugas ini sudah di-approve. Tidak dapat menambah laporan harian lagi.');
            return redirect()->route('penugasans.show', $penugasan->id);
        }

        // Cegah laporan jika deadline sudah lewat (GUGUR)
        // KECUALI jika tugas sudah di-approve atau progress sudah 100%
        if ($penugasan->deadline && now()->greaterThan($penugasan->deadline->endOfDay())) {
            // Hitung progress terakhir
            $latestLaporan = $penugasan->laporanHarian()->latest('created_at')->first();
            $currentProgress = $latestLaporan ? $latestLaporan->progres_tugas : 0;

            // Gugur jika belum di-approve DAN progress belum 100%
            if ($penugasan->is_approved != 1 && $currentProgress < 100) {
                Alert::error('Tugas Gugur', 'Tugas ini sudah melewati deadline dan dianggap gugur. Tidak dapat menambah laporan harian.');
                return redirect()->route('penugasans.show', $penugasan->id);
            }
        }

        // Cegah laporan jika tugas sudah selesai dan di-approve
        if ($penugasan->status_tugas === 'Selesai' && ($penugasan->is_approved ?? false)) {
            return back()->withErrors([
                'penugasan_id' => 'Tugas ini sudah selesai dan telah di-approve. Tidak bisa menambah laporan lagi.'
            ]);
        }

        // Validasi progres
        $lastLaporan = LaporanHarian::where('penugasan_id', $request->penugasan_id)
            ->where('peserta_id', $peserta->id)
            ->latest()
            ->first();

        $lastProgress = $lastLaporan ? $lastLaporan->progres_tugas : 0;

        // Jika progres terakhir sudah 100%, set progres baru juga 100%
        if ($lastProgress >= 100) {
            $validated['progres_tugas'] = 100;
        } else {
            // Validasi progres tidak boleh kurang dari progres terakhir
            if ($request->progres_tugas < $lastProgress) {
                return back()->withErrors([
                    'progres_tugas' => 'Progress tidak boleh kurang dari progress terakhir (' . $lastProgress . '%)'
                ])->withInput();
            }
        }

        // Tambahkan data peserta
        $validated['peserta_id'] = $peserta->id;
        $validated['penugasan_id'] = $penugasan->id; // Pastikan ID yang benar

        // Simpan file jika ada
        if ($request->hasFile('file')) {
            $validated['file'] = $request->file('file')->store('laporan_harian', 'public');
        }

        // Tentukan status tugas
        $progress = (int) $validated['progres_tugas'];
        if ($progress >= 100) {
            $status = 'Selesai';
        } elseif ($progress > 0) {
            $status = 'Dikerjakan';
        } else {
            $status = 'Belum';
        }
        $validated['status_tugas'] = $status;

        // Simpan laporan harian
        $laporan = LaporanHarian::create($validated);

        // Update status penugasan
        $this->updateStatusPenugasan($laporan);

        Alert::success('Berhasil', 'Laporan harian berhasil ditambahkan.');
        return redirect()->route('penugasans.show', $penugasan->id);
    }


    public function edit(LaporanHarian $laporanHarian)
    {
        $user = Auth::user();

        // Validasi akses
        if (!$this->canAccessLaporan($user, $laporanHarian, 'edit')) {
            abort(403, 'AKSES DITOLAK.');
        }

        // Cek apakah ini laporan terbaru untuk penugasan dan peserta ini
        $latestLaporan = LaporanHarian::where('penugasan_id', $laporanHarian->penugasan_id)
            ->where('peserta_id', $laporanHarian->peserta_id)
            ->orderBy('created_at', 'desc')
            ->first();

        $isLatestReport = ($latestLaporan && $latestLaporan->id === $laporanHarian->id);

        return view('laporan_harian.edit', compact('laporanHarian', 'isLatestReport'));
    }


    public function update(Request $request, LaporanHarian $laporanHarian)
    {
        $user = Auth::user();

        // Validasi akses
        if (!$this->canAccessLaporan($user, $laporanHarian, 'update')) {
            abort(403, 'AKSES DITOLAK.');
        }

        // Cek apakah ini laporan terbaru
        $latestLaporan = LaporanHarian::where('penugasan_id', $laporanHarian->penugasan_id)
            ->where('peserta_id', $laporanHarian->peserta_id)
            ->orderBy('created_at', 'desc')
            ->first();

        $isLatestReport = ($latestLaporan && $latestLaporan->id === $laporanHarian->id);

        // Validasi input - jika bukan laporan terbaru, progress tidak wajib dan akan diabaikan
        $rules = [
            'tanggal_laporan' => 'required|date',
            'deskripsi_kegiatan' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:2048',
        ];

        // Hanya validasi progress jika ini laporan terbaru
        if ($isLatestReport) {
            $rules['progres_tugas'] = 'required|integer|min:0|max:100';
        }

        $validated = $request->validate($rules, [
            'tanggal_laporan.required' => 'Tanggal laporan wajib diisi.',
            'deskripsi_kegiatan.string' => 'Deskripsi kegiatan harus berupa teks.',
            'progres_tugas.required' => 'Persentase progres wajib diisi.',
            'progres_tugas.integer' => 'Persentase progres harus berupa angka.',
            'progres_tugas.min' => 'Persentase progres minimal 0.',
            'progres_tugas.max' => 'Persentase progres maksimal 100.',
            'file.file' => 'File harus berupa file.',
            'file.mimes' => 'File harus berformat PDF, DOC, DOCX, JPG, atau PNG.',
            'file.max' => 'File maksimal 2MB.',
        ]);

        // Jika ini laporan terbaru, validasi progres
        if ($isLatestReport) {
            $currentProgress = $laporanHarian->progres_tugas;
            if ($request->progres_tugas < $currentProgress) {
                return back()->withErrors([
                    'progres_tugas' => 'Progress tidak boleh kurang dari progress laporan ini (' . $currentProgress . '%)'
                ])->withInput();
            }

            // Tentukan status tugas
            $progress = (int) $validated['progres_tugas'];
            if ($progress >= 100) {
                $status = 'Selesai';
            } elseif ($progress > 0) {
                $status = 'Dikerjakan';
            } else {
                $status = 'Belum';
            }
            $validated['status_tugas'] = $status;
        } else {
            // Jika bukan laporan terbaru, progress tidak berubah
            unset($validated['progres_tugas']);
        }

        // Simpan file lama untuk dihapus jika ada file baru
        $oldFile = $laporanHarian->file;

        // Simpan file baru jika ada
        if ($request->hasFile('file')) {
            $validated['file'] = $request->file('file')->store('laporan_harian', 'public');
        }

        // Tentukan status tugas
        $progress = (int) $validated['progres_tugas'];
        if ($progress >= 100) {
            $status = 'Selesai';
        } elseif ($progress > 0) {
            $status = 'Dikerjakan';
        } else {
            $status = 'Belum';
        }
        $validated['status_tugas'] = $status;

        // Update laporan
        $laporanHarian->update($validated);

        // Hapus file lama jika ada file baru
        if ($request->hasFile('file') && $oldFile) {
            Storage::disk('public')->delete($oldFile);
        }

        // Update status penugasan
        $this->updateStatusPenugasan($laporanHarian);

        Alert::success('Berhasil', 'Laporan harian berhasil diperbarui.');
        return redirect()->route('penugasans.show', $laporanHarian->penugasan_id);
    }


    public function destroy($id)
    {
        $laporan = LaporanHarian::findOrFail($id);
        $user = Auth::user();

        // Validasi akses hapus
        if (!$this->canAccessLaporan($user, $laporan, 'delete')) {
            abort(403, 'AKSES DITOLAK.');
        }

        // Hapus file jika ada
        if ($laporan->file) {
            Storage::disk('public')->delete($laporan->file);
        }

        $laporan->delete();

        Alert::success('Berhasil', 'Laporan harian berhasil dihapus.');
        return redirect()->back()->with('success', 'Laporan harian berhasil dihapus');
    }


    private function updateStatusPenugasan(LaporanHarian $laporan)
    {
        $penugasan = $laporan->penugasan;
        if (!$penugasan) {
            return;
        }

        $progress = (int) $laporan->progres_tugas;
        $status = 'Belum';

        if ($progress >= 100) {
            $status = 'Selesai';
        } elseif ($progress > 0) {
            $status = 'Dikerjakan';
        }

        $penugasan->update(['status_tugas' => $status]);
    }


    private function canAccessLaporan($user, $laporan, $action = 'view')
    {
        // Admin bisa akses semua
        if ($user->isAdmin()) {
            return true;
        }

        // Mentor bisa akses laporan peserta di bagiannya
        if ($user->isMentor() && $laporan->peserta?->bagian_id === $user->mentor?->bagian_id) {
            return true;
        }

        // Peserta hanya bisa akses laporannya sendiri
        if ($user->isPeserta() && $laporan->peserta_id === $user->peserta?->id) {
            // Untuk edit/update, cek apakah tugas belum selesai
            if (in_array($action, ['edit', 'update']) && $laporan->penugasan?->status_tugas === 'Selesai') {
                return false;
            }
            return true;
        }

        return false;
    }
}
