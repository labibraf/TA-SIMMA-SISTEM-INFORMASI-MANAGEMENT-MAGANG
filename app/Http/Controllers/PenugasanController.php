<?php

namespace App\Http\Controllers;

use App\Models\Penugasan;
use App\Models\Bagian;
use App\Models\Mentor;
use App\Models\Peserta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Models\LaporanHarian;
use RealRashid\SweetAlert\Facades\Alert;

class PenugasanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            // Tolak akses jika pengguna BUKAN Peserta, BUKAN Admin, DAN BUKAN Mentor.
            if (!$user->isPeserta() && !$user->isAdmin() && !$user->isMentor()) {
                abort(403, 'AKSES DITOLAK: ANDA TIDAK MEMILIKI HAK AKSES.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $user = Auth::user();
        $penugasans = collect();

        if ($user->isAdmin()) {
            // Admin: semua penugasan
            $penugasans = Penugasan::with(['peserta.user', 'peserta.bagian', 'mentor.user', 'bagian', 'laporanHarian'])
                ->orderBy('created_at', 'desc')
                ->get();
        }
        elseif ($user->isMentor() && $user->mentor?->bagian_id) {
            // Mentor: semua penugasan di bagian yang sama
            $bagianId = $user->mentor->bagian_id;

            $peserta = Peserta::with('user')
                ->where('bagian_id', $bagianId)
                ->orderBy('created_at', 'desc')
                ->get();

            $pesertaIds = $peserta->pluck('id');

            $penugasans = Penugasan::where(function($q) use ($pesertaIds, $bagianId) {
                    $q->whereIn('peserta_id', $pesertaIds)
                    ->orWhere(function($sub) use ($bagianId) {
                        $sub->where('kategori', 'Divisi')
                            ->where('bagian_id', $bagianId);
                    });
                })
                ->with(['peserta.user', 'peserta.bagian', 'mentor.user', 'bagian', 'laporanHarian'])
                ->orderBy('created_at', 'desc')
                ->get();
        }
        elseif ($user->isPeserta() && $user->peserta?->bagian_id) {
            // Peserta: tampilkan SEMUA tugas yang sudah di-assign (termasuk yang sudah dikerjakan)
            $pesertaId = $user->peserta->id;
            $bagianId = $user->peserta->bagian_id;

            $penugasans = Penugasan::where(function($q) use ($pesertaId, $bagianId) {
                    // Tugas individu untuk peserta ini
                    $q->where('peserta_id', $pesertaId);

                    // Tugas divisi: tampilkan jika peserta ada di pivot table
                    $q->orWhere(function($sub) use ($bagianId, $pesertaId) {
                        $sub->where('kategori', 'Divisi')
                            ->where('bagian_id', $bagianId)
                            // Pastikan peserta ini ada di pivot table
                            ->whereHas('pesertas', function($pivot) use ($pesertaId) {
                                $pivot->where('peserta_id', $pesertaId);
                            });
                    });
                })
                ->with(['peserta.user', 'peserta.bagian', 'mentor.user', 'bagian', 'laporanHarian'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Tambahkan property isGugur untuk setiap penugasan
        $penugasans->each(function($item) {
            $isOverdue = $item->deadline && now()->greaterThan($item->deadline->endOfDay());

            // Tugas benar-benar selesai
            if ($item->kategori === 'Divisi') {
                $isSelesaiBetulan = ($item->is_approved == 1);
            } else {
                $latestLaporan = $item->laporanHarian->last();
                $progress = $latestLaporan ? $latestLaporan->progres_tugas : 0;
                $isSelesaiBetulan = ($item->is_approved == 1 || $progress == 100);
            }

            // GUGUR: deadline lewat DAN belum selesai
            $item->isGugur = $isOverdue && !$isSelesaiBetulan;
        });

        return view('Penugasan.index', compact('penugasans'));
    }

    public function create()
    {
        $mentor = auth()->user(); // user login
        $peserta = Peserta::with('user')
            ->where('bagian_id', $mentor->mentor->bagian_id)
            ->aktifUntukForm() // Hanya peserta yang masih aktif (belum selesai magang)
            ->orderBy('id')
            ->get();

        return view('Penugasan.create', compact('peserta'));
    }

    public function store(Request $request)
    {
        $rules = [
            'judul_tugas' => 'required|max:255',
            'deskripsi_tugas' => 'required|max:255',
            'kategori' => ['required', Rule::in(['Individu', 'Divisi'])],
            'beban_waktu' => ['required', 'integer', 'min:1', 'max:168'],
            'deadline' => ['required', 'date', 'after_or_equal:today'],
            'nilai_kualitas' => 'numeric|min:0|max:10',
            'file' => 'sometimes|file|mimes:pdf,doc,docx|max:2048',
        ];

        $messages = [
            'judul_tugas.required' => 'Judul tugas harus diisi.',
            'judul_tugas.max' => 'Judul tugas tidak boleh lebih dari 255 karakter.',
            'deskripsi_tugas.required' => 'Deskripsi harus diisi.',
            'deskripsi_tugas.max' => 'Deskripsi tidak boleh lebih dari 255 karakter.',
            'kategori.required' => 'Kategori harus diisi.',
            'beban_waktu.required' => 'Beban waktu harus diisi.',
            'beban_waktu.integer' => 'Beban waktu harus berupa angka.',
            'beban_waktu.min' => 'Beban waktu minimal 1 jam.',
            'beban_waktu.max' => 'Beban waktu maksimal 168 jam (7 hari).',
            'deadline.required' => 'Deadline harus diisi.',
            'nilai_kualitas.required' => 'Nilai kualitas harus diisi.',
            'file.sometimes' => 'File tidak boleh kosong.',
        ];

        if (strtolower($request->kategori) === 'individu') {
            $rules['peserta_id'] = 'required|integer|exists:pesertas,id';
        } else {
            $rules['peserta_ids'] = 'required|array';
            $rules['peserta_ids.*'] = 'integer|exists:pesertas,id';
        }

        $validatedData = $request->validate($rules, $messages);

        // Custom validation: Cek apakah beban_waktu tidak melebihi sisa waktu maksimal peserta
        if (strtolower($request->kategori) === 'individu' && $request->peserta_id) {
            $peserta = Peserta::find($request->peserta_id);
            if ($peserta) {
                // Validasi 1: Cek sisa waktu maksimal
                $sisaWaktuMaksimal = $peserta->getSisaWaktuMaksimalAttribute();
                if ($request->beban_waktu > $sisaWaktuMaksimal) {
                    return back()->withErrors([
                        'beban_waktu' => "Beban waktu tidak boleh melebihi sisa waktu maksimal peserta ({$sisaWaktuMaksimal} jam)."
                    ])->withInput();
                }

                // Validasi 2: Cek sisa jam kerja berdasarkan sisa hari magang
                $sisaJamKerja = $peserta->sisa_jam_kerja;
                if ($request->beban_waktu > $sisaJamKerja) {
                    return back()->withErrors([
                        'beban_waktu' => "Beban waktu ({$request->beban_waktu} jam) melebihi sisa jam kerja peserta ({$sisaJamKerja} jam / {$peserta->sisa_hari_kerja} hari kerja tersisa)."
                    ])->withInput();
                }
            }

            // Validasi anti-duplikasi untuk tugas individu
            $existingTugas = Penugasan::where('judul_tugas', $request->judul_tugas)
                ->where('peserta_id', $request->peserta_id)
                ->where('mentor_id', optional(Auth::user()->mentor)->id)
                ->whereDate('deadline', $request->deadline)
                ->first();

            if ($existingTugas) {
                return back()->withErrors([
                    'judul_tugas' => 'Tugas dengan judul yang sama sudah ada untuk peserta ini dengan deadline yang sama. Silakan gunakan judul yang berbeda atau ubah deadline.'
                ])->withInput();
            }
        } elseif (strtolower($request->kategori) === 'divisi' && $request->peserta_ids) {
            // Untuk tugas divisi, validasi sisa waktu
            $pesertas = Peserta::whereIn('id', $request->peserta_ids)->get();

            // Validasi 1: Cek sisa waktu maksimal terbesar
            $maxSisaWaktu = $pesertas->max(function($peserta) {
                return $peserta->getSisaWaktuMaksimalAttribute();
            });

            if ($request->beban_waktu > $maxSisaWaktu) {
                return back()->withErrors([
                    'beban_waktu' => "Beban waktu tidak boleh melebihi sisa waktu maksimal terbesar dari peserta yang dipilih ({$maxSisaWaktu} jam)."
                ])->withInput();
            }

            // Validasi 2: Cek apakah ada peserta yang sisa jam kerjanya tidak cukup
            $pesertaTidakCukup = $pesertas->filter(function($peserta) use ($request) {
                return $peserta->sisa_jam_kerja < $request->beban_waktu;
            });

            if ($pesertaTidakCukup->count() > 0) {
                $namaPeserta = $pesertaTidakCukup->pluck('nama_lengkap')->implode(', ');
                return back()->withErrors([
                    'beban_waktu' => "Beban waktu ({$request->beban_waktu} jam) terlalu besar untuk peserta: {$namaPeserta}. Mereka tidak memiliki cukup sisa hari kerja."
                ])->withInput();
            }

            // Validasi anti-duplikasi untuk tugas divisi
            $bagianId = optional(Auth::user()->mentor)->bagian_id;
            $existingTugasDivisi = Penugasan::where('judul_tugas', $request->judul_tugas)
                ->where('kategori', 'Divisi')
                ->where('bagian_id', $bagianId)
                ->where('mentor_id', optional(Auth::user()->mentor)->id)
                ->whereDate('deadline', $request->deadline)
                ->first();

            if ($existingTugasDivisi) {
                return back()->withErrors([
                    'judul_tugas' => 'Tugas divisi dengan judul yang sama sudah ada untuk bagian ini dengan deadline yang sama. Silakan gunakan judul yang berbeda atau ubah deadline.'
                ])->withInput();
            }
        }

        $validatedData['mentor_id'] = optional(Auth::user()->mentor)->id;
        $validatedData['bagian_id'] = optional(Auth::user()->mentor)->bagian_id;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $validatedData['file'] = $file->storeAs(
                'penugasan_files',
                uniqid() . '_' . $file->getClientOriginalName(),
                'public'
            );
        } else {
            $validatedData['file'] = null;
        }

        // Handle multiple peserta
        if (strtolower($request->kategori) === 'divisi') {
            if ($request->has('select_all') && $request->select_all == '1') {
                // Jika pilih semua - hanya peserta yang masih aktif (belum selesai magang)
                $bagianId = optional(Auth::user()->mentor)->bagian_id;
                $pesertas = Peserta::where('bagian_id', $bagianId)
                    ->aktifUntukForm() // Filter peserta yang masih aktif
                    ->pluck('id')
                    ->toArray();
                $validatedData['multiple_peserta_ids'] = $pesertas;
                $validatedData['peserta_id'] = null; // Kosongkan untuk divisi
            } else {
                // Jika pilih beberapa
                $validatedData['multiple_peserta_ids'] = $request->peserta_ids;
                $validatedData['peserta_id'] = null; // Kosongkan untuk divisi
            }
        } else {
            // Untuk individu
            $validatedData['peserta_id'] = $request->peserta_id;
            $validatedData['multiple_peserta_ids'] = null; // Kosongkan untuk individu
        }

        // Simpan penugasan
        $penugasan = Penugasan::create($validatedData);

        // Sync peserta ke pivot table untuk kategori Divisi
        if (strtolower($request->kategori) === 'divisi') {
            if ($request->has('select_all') && $request->select_all == '1') {
                $bagianId = optional(Auth::user()->mentor)->bagian_id;
                // Hanya assign peserta yang masih aktif (menggunakan scope aktifUntukForm)
                $pesertaIds = Peserta::where('bagian_id', $bagianId)
                    ->aktifUntukForm() // Filter peserta yang masih bisa menerima tugas
                    ->pluck('id')
                    ->toArray();
                $penugasan->pesertas()->sync($pesertaIds);
            } else {
                // Filter peserta menggunakan scope aktifUntukForm
                $pesertaIds = $request->peserta_ids ?? [];
                $pesertaAktif = Peserta::whereIn('id', $pesertaIds)
                    ->aktifUntukForm() // Filter peserta yang masih bisa menerima tugas
                    ->pluck('id')
                    ->toArray();

                $penugasan->pesertas()->sync($pesertaAktif);
            }
        }

        Alert::success('Success', 'Penugasan berhasil ditambahkan.');
        return redirect()->route('penugasans.index');
    }

    public function edit(Penugasan $penugasan)
    {
        $user = Auth::user();

        // Tolak langsung jika user adalah Peserta
        if ($user->isPeserta()) {
            abort(403, 'AKSES DITOLAK: Peserta tidak diizinkan mengedit penugasan.');
        }

        // Cek apakah tugas sudah selesai dan di-approve
        if ($penugasan->status_tugas === 'Selesai' && $penugasan->is_approved == 1) {
            Alert::error('Akses Ditolak', 'Tugas ini sudah selesai dan telah di-approve. Tidak dapat diedit lagi.');
            return redirect()->route('penugasans.show', $penugasan->id);
        }

        // Load relasi pesertas untuk penugasan divisi
        $penugasan->load(['pesertas', 'peserta.user', 'bagian']);

        // Siapkan query default kosong
        $pesertas = collect();

        // Mentor hanya boleh edit jika bagian_id cocok
        if ($user->isMentor() && optional($user->mentor)->bagian_id == $penugasan->bagian_id) {
            $pesertas = Peserta::with('user')
                ->where('bagian_id', $user->mentor->bagian_id)
                ->aktifUntukForm()
                ->orderBy('id')
                ->get();
        }
        // Admin bebas akses semua
        elseif ($user->isAdmin()) {
            $pesertas = Peserta::with('user')
                ->aktifUntukForm()
                ->orderBy('id')
                ->get();
        }
        // Selain itu, tolak akses
        else {
            abort(403, 'AKSES DITOLAK: ANDA TIDAK MEMILIKI HAK AKSES.');
        }

        // Hitung apakah tugas gugur/telat
        $isOverdue = $penugasan->deadline && now()->greaterThan(\Carbon\Carbon::parse($penugasan->deadline)->endOfDay());

        // Cek apakah tugas benar-benar selesai
        if ($penugasan->kategori === 'Divisi') {
            $isSelesaiBetulan = ($penugasan->is_approved == 1);
        } else {
            // Untuk individu, cek progress dari laporan terakhir
            $latestLaporan = $penugasan->laporanHarian()->where('peserta_id', $penugasan->peserta_id)->latest('created_at')->first();
            $progress = $latestLaporan ? $latestLaporan->progres_tugas : 0;
            $isSelesaiBetulan = ($penugasan->is_approved == 1 || $progress == 100);
        }

        // GUGUR: deadline lewat DAN belum selesai
        $isGugur = $isOverdue && !$isSelesaiBetulan;

        return view('Penugasan.edit', compact('penugasan', 'pesertas', 'isGugur'));
    }

    public function update(Request $request, Penugasan $penugasan)
    {
        $user = Auth::user();

        // 1. Cek role & akses
        if ($user->isPeserta()) {
            abort(403, 'AKSES DITOLAK: Peserta tidak diizinkan mengedit penugasan.');
        }

        // Cek apakah tugas sudah selesai dan di-approve
        if ($penugasan->status_tugas === 'Selesai' && $penugasan->is_approved == 1) {
            Alert::error('Akses Ditolak', 'Tugas ini sudah selesai dan telah di-approve. Tidak dapat diedit lagi.');
            return redirect()->route('penugasans.show', $penugasan->id);
        }

        if ($user->isMentor() && optional($user->mentor)->bagian_id != $penugasan->bagian_id) {
            abort(403, 'AKSES DITOLAK: Anda tidak memiliki hak akses.');
        }

        // 2. Validasi input
        $rules = [
            'judul_tugas' => 'required|max:255',
            'deskripsi_tugas' => 'required|max:255',
            'kategori' => ['required', Rule::in(["Individu", "Divisi"])],
            'beban_waktu' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->kategori == 'Individu' && $request->peserta_id) {
                        $peserta = \App\Models\Peserta::find($request->peserta_id);
                        if ($peserta) {
                            $sisaWaktuMaksimal = $peserta->getSisaWaktuMaksimalAttribute();
                            if ($value > $sisaWaktuMaksimal) {
                                $fail("Beban waktu tidak boleh melebihi sisa waktu maksimal peserta ({$sisaWaktuMaksimal} jam).");
                            }
                        }
                    } elseif ($request->kategori == 'Divisi' && $request->peserta_ids) {
                        $maxSisaWaktu = 0;
                        foreach ($request->peserta_ids as $pesertaId) {
                            $peserta = \App\Models\Peserta::find($pesertaId);
                            if ($peserta) {
                                $sisaWaktu = $peserta->getSisaWaktuMaksimalAttribute();
                                if ($sisaWaktu > $maxSisaWaktu) {
                                    $maxSisaWaktu = $sisaWaktu;
                                }
                            }
                        }
                        if ($value > $maxSisaWaktu) {
                            $fail("Beban waktu tidak boleh melebihi sisa waktu maksimal terbesar dari peserta yang dipilih ({$maxSisaWaktu} jam).");
                        }
                    }
                }
            ],
            'deadline' => ['required', 'date', 'after_or_equal:today'],
            'feedback' => 'sometimes|string|max:500',
            'status_tugas' => ['sometimes', Rule::in(["Belum", "Dikerjakan", "Selesai"])],
            'nilai_kualitas' => 'numeric|min:0|max:10',
            'file' => 'file|mimes:pdf,doc,docx|max:2048',
        ];

        if ($request->kategori == 'Individu') {
            $rules['peserta_id'] = 'required|integer|exists:pesertas,id';
        } else {
            $rules['peserta_ids'] = 'required|array';
            $rules['peserta_ids.*'] = 'integer|exists:pesertas,id';
        }

        $messages = [
            'judul_tugas.required' => 'Judul tugas harus diisi.',
            'judul_tugas.max' => 'Judul tugas tidak boleh lebih dari 255 karakter.',
            'deskripsi_tugas.required' => 'Deskripsi harus diisi.',
            'deskripsi_tugas.max' => 'Deskripsi tidak boleh lebih dari 255 karakter.',
            'kategori.required' => 'Kategori harus diisi.',
            'beban_waktu.required' => 'Beban waktu harus diisi.',
            'beban_waktu.integer' => 'Beban waktu harus berupa angka.',
            'beban_waktu.min' => 'Beban waktu minimal 1 jam.',
            'beban_waktu.max' => 'Beban waktu maksimal 168 jam (7 hari).',
            'deadline.required' => 'Deadline harus diisi.',
            'feedback.sometimes' => 'Feedback harus diisi.',
            'status_tugas.sometimes' => 'Status tugas harus diisi.',
        ];

        $validatedData = $request->validate($rules, $messages);

        // Validasi anti-duplikasi saat update
        if ($request->kategori == 'Individu' && $request->peserta_id) {
            $existingTugas = Penugasan::where('judul_tugas', $request->judul_tugas)
                ->where('peserta_id', $request->peserta_id)
                ->where('mentor_id', optional(Auth::user()->mentor)->id)
                ->whereDate('deadline', $request->deadline)
                ->where('id', '!=', $penugasan->id) // Exclude current record
                ->first();

            if ($existingTugas) {
                return back()->withErrors([
                    'judul_tugas' => 'Tugas dengan judul yang sama sudah ada untuk peserta ini dengan deadline yang sama. Silakan gunakan judul yang berbeda atau ubah deadline.'
                ])->withInput();
            }
        } elseif ($request->kategori == 'Divisi') {
            $bagianId = optional(Auth::user()->mentor)->bagian_id;
            $existingTugasDivisi = Penugasan::where('judul_tugas', $request->judul_tugas)
                ->where('kategori', 'Divisi')
                ->where('bagian_id', $bagianId)
                ->where('mentor_id', optional(Auth::user()->mentor)->id)
                ->whereDate('deadline', $request->deadline)
                ->where('id', '!=', $penugasan->id) // Exclude current record
                ->first();

            if ($existingTugasDivisi) {
                return back()->withErrors([
                    'judul_tugas' => 'Tugas divisi dengan judul yang sama sudah ada untuk bagian ini dengan deadline yang sama. Silakan gunakan judul yang berbeda atau ubah deadline.'
                ])->withInput();
            }
        }

        // 3. Simpan file jika ada
        if ($request->hasFile('file')) {
            // Hapus file lama jika ada
            if ($penugasan->file) {
                Storage::delete($penugasan->file);
            }
            // Simpan file baru
            $validatedData['file'] = $request->file('file')->store('penugasan_files');
        }

        $bobotLama = $penugasan->beban_waktu;

        // Handle multiple peserta
        if ($request->kategori == 'Divisi') {
            if ($request->has('select_all') && $request->select_all == '1') {
                // Jika pilih semua
                $bagianId = optional(Auth::user()->mentor)->bagian_id;
                $pesertas = Peserta::where('bagian_id', $bagianId)->pluck('id')->toArray();
                $validatedData['multiple_peserta_ids'] = $pesertas;
                $validatedData['peserta_id'] = null; // Kosongkan untuk divisi
            } else {
                // Jika pilih beberapa
                $validatedData['multiple_peserta_ids'] = $request->peserta_ids;
                $validatedData['peserta_id'] = null; // Kosongkan untuk divisi
            }
        } else {
            // Untuk individu
            $validatedData['peserta_id'] = $request->peserta_id;
            $validatedData['multiple_peserta_ids'] = null; // Kosongkan untuk individu
        }

        // 4. Update penugasan
        $penugasan->update($validatedData);

        // Sync peserta ke pivot table untuk kategori Divisi
        if ($request->kategori == 'Divisi') {
            if ($request->has('select_all') && $request->select_all == '1') {
                $bagianId = optional(Auth::user()->mentor)->bagian_id;
                $pesertaIds = Peserta::where('bagian_id', $bagianId)->pluck('id')->toArray();
                $penugasan->pesertas()->sync($pesertaIds);
            } else {
                $penugasan->pesertas()->sync($request->peserta_ids);
            }
        }

        // 5. Update bobot_tercapai peserta jika bobot berubah
        if ($bobotLama != $penugasan->beban_waktu) {
            // Update untuk peserta utama
            if ($penugasan->peserta) {
                $penugasan->peserta->updateWaktuTugasTercapai();
            }
            // Update untuk semua peserta dalam kategori divisi
            if ($penugasan->kategori === 'Divisi' && $penugasan->bagian_id) {
                $pesertasInBagian = Peserta::where('bagian_id', $penugasan->bagian_id)->get();
                foreach ($pesertasInBagian as $peserta) {
                    $peserta->updateWaktuTugasTercapai();
                }
            }
        }

        Alert::success('Success', 'Penugasan berhasil diperbarui.');
        return redirect()->route('penugasans.index');
    }

    public function updateStatus(Request $request, $id)
    {
        $penugasan = Penugasan::findOrFail($id);

        // Validasi status
        $request->validate([
            'status_tugas' => 'required|in:Belum,Dikerjakan,Selesai'
        ]);

        $user = Auth::user();

        // Logic untuk pembatasan status
        if ($user->isPeserta()) {
            $currentProgress = 0;

            // Hitung progress berdasarkan kategori penugasan
            if ($penugasan->kategori === 'Divisi') {
                // Untuk tugas divisi: hitung rata-rata dari progress tertinggi setiap peserta
                $pesertaList = $penugasan->getAllPesertas();
                $totalProgress = 0;
                $jumlahPeserta = $pesertaList->count();

                if ($jumlahPeserta > 0) {
                    foreach ($pesertaList as $peserta) {
                        // Ambil progress tertinggi untuk setiap peserta
                        $maxProgress = LaporanHarian::where('penugasan_id', $id)
                            ->where('peserta_id', $peserta->id)
                            ->max('progres_tugas') ?? 0;
                        $totalProgress += $maxProgress;
                    }
                    // Rata-rata dari progress tertinggi setiap peserta
                    $currentProgress = $totalProgress / $jumlahPeserta;
                } else {
                    $currentProgress = 0;
                }
            } else {
                // Untuk tugas individu: ambil progress tertinggi dari laporan peserta yang bersangkutan
                $currentProgress = LaporanHarian::where('penugasan_id', $id)
                    ->where('peserta_id', $user->peserta->id)
                    ->max('progres_tugas') ?? 0;
            }

            // Peserta hanya bisa mengubah status saat progress 100%
            if ($currentProgress == 100) {
                if (in_array($request->status_tugas, ['Belum', 'Selesai'])) {
                    $penugasan->status_tugas = $request->status_tugas;
                    $penugasan->save();
                    return back()->with('success', 'Status tugas berhasil diperbarui');
                } else {
                    return back()->with('error', 'Peserta hanya bisa memilih Belum atau Selesai saat progress 100%');
                }
            } else {
                // Jangan izinkan perubahan status jika progress bukan 100%
                return back()->with('error', 'Status hanya bisa diubah saat progress mencapai 100%');
            }
        } else {
            // Mentor/Admin bisa mengubah status apapun
            $penugasan->status_tugas = $request->status_tugas;
            $penugasan->save();
            return back()->with('success', 'Status tugas berhasil diperbarui');
        }
    }

    public function updateFeedback(Request $request, $id)
    {
        $penugasan = Penugasan::findOrFail($id);

        // Hanya Mentor/Admin yang bisa memberi feedback
        if (Auth::user()->isPeserta()) {
            abort(403, 'AKSES DITOLAK');
        }

        $request->validate([
            'feedback' => 'nullable|string|max:500'
        ]);

        $penugasan->update([
            'feedback' => $request->feedback
        ]);

        return back()->with('success', 'Feedback berhasil diperbarui');
    }

    public function updateApprove(Request $request, $id)
    {
        $penugasan = Penugasan::findOrFail($id);

        // Hanya Mentor/Admin yang bisa approve
        if (Auth::user()->isPeserta()) {
            abort(403, 'AKSES DITOLAK');
        }

        $request->validate([
            'is_approved' => 'sometimes|in:0,1',
            'feedback' => 'nullable|string|max:500',
            'catatan' => 'nullable|string|max:500'
        ]);

        $approveChanged = false;

        // Update approve status jika ada
        if ($request->has('is_approved')) {
            $oldApproval = $penugasan->is_approved;
            $penugasan->update([
                'is_approved' => $request->is_approved
            ]);
            $approveChanged = ($oldApproval != $request->is_approved);
        }

        // Update feedback jika ada
        if ($request->has('feedback')) {
            $penugasan->update([
                'feedback' => $request->feedback
            ]);
        }

        // Update catatan jika ada
        if ($request->has('catatan')) {
            $penugasan->update([
                'catatan' => $request->catatan
            ]);
        }

        // Update bobot_tercapai peserta jika status approve berubah
        if ($approveChanged) {
            $pesertasToUpdate = $penugasan->getAllPesertas();
            foreach ($pesertasToUpdate as $peserta) {
                if ($peserta && method_exists($peserta, 'updateWaktuTugasTercapai')) {
                    $peserta->updateWaktuTugasTercapai();
                }
            }
        }

        // Buat pesan sesuai aksi
        $message = 'Status approve berhasil diperbarui';
        if ($request->has('feedback')) {
            $message = 'Feedback berhasil disimpan';
        }
        if ($request->has('catatan')) {
            $message = 'Catatan berhasil disimpan';
        }

        Alert::success('Success', $message);
        return back();
    }

    public function show($id)
    {
        $penugasan = Penugasan::with(['peserta.user', 'peserta.bagian', 'mentor.user', 'bagian'])->findOrFail($id);

        // Ambil laporan harian terkait dengan penugasan ini
        $laporanHarians = LaporanHarian::where('penugasan_id', $id)
            ->with(['peserta.user', 'penugasan'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Inisialisasi variabel
        $currentProgress = 0;
        $rataRataProgressDivisi = 0;
        $pesertaList = collect();

        // Hitung progress berdasarkan kategori penugasan
        if ($penugasan->kategori === 'Divisi') {
            // Untuk penugasan Divisi: hitung rata-rata dari progress tertinggi setiap peserta
            $pesertaList = $penugasan->pesertas()->get();
            $totalProgress = 0;
            $jumlahPeserta = $pesertaList->count();

            if ($jumlahPeserta > 0) {
                foreach ($pesertaList as $peserta) {
                    // Ambil progress tertinggi untuk setiap peserta
                    $maxProgress = LaporanHarian::where('penugasan_id', $id)
                        ->where('peserta_id', $peserta->id)
                        ->max('progres_tugas') ?? 0;

                    // Tambahkan property progress ke objek peserta
                    $peserta->progress = $maxProgress;
                    $totalProgress += $maxProgress;
                }
                // Rata-rata dari progress tertinggi setiap peserta
                $rataRataProgressDivisi = $totalProgress / $jumlahPeserta;
                $currentProgress = $rataRataProgressDivisi;
            } else {
                $currentProgress = 0;
                $rataRataProgressDivisi = 0;
            }

            // Ambil laporan terbaru sebagai referensi
            $latestLaporan = $laporanHarians->last();
        } else {
            // Untuk penugasan Individu: ambil progress tertinggi dari laporan
            $currentProgress = $laporanHarians->max('progres_tugas') ?? 0;
            $latestLaporan = $laporanHarians->where('progres_tugas', $currentProgress)->last();
        }

        // Hitung isGugur
        $isOverdue = $penugasan->deadline && now()->greaterThan($penugasan->deadline->endOfDay());

        if ($penugasan->kategori === 'Divisi') {
            $isSelesaiBetulan = ($penugasan->is_approved == 1);
        } else {
            $isSelesaiBetulan = ($penugasan->is_approved == 1 || $currentProgress == 100);
        }

        $isGugur = $isOverdue && !$isSelesaiBetulan;

        return view('Penugasan.show', compact(
            'penugasan',
            'laporanHarians',
            'currentProgress',
            'latestLaporan',
            'pesertaList',
            'rataRataProgressDivisi',
            'isGugur',
            'isSelesaiBetulan'
        ));
    }

    public function destroy(Penugasan $penugasan)
    {
        $user = Auth::user();
        if ($user->isPeserta()) {
            abort(403, 'AKSES DITOLAK: Peserta tidak diizinkan menghapus penugasan.');
        }
        if ($user->isMentor() && $user->mentor->bagian_id != $penugasan->bagian_id) {
            abort(403, 'AKSES DITOLAK: Anda tidak memiliki hak akses.');
        }
        if ($penugasan->file && Storage::exists($penugasan->file)) {
            Storage::delete($penugasan->file);
        }
        $penugasan->delete();
        Alert::success('Success', 'Penugasan berhasil dihapus.');
        return redirect()->route('penugasans.index');
    }
}
