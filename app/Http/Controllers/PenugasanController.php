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
            // Peserta: hanya lihat penugasan di bagian yang sama
            $bagianId = $user->peserta->bagian_id;

            $penugasans = Penugasan::where(function($q) use ($user, $bagianId) {
                    $q->where('peserta_id', $user->peserta->id)
                    ->orWhere(function($sub) use ($bagianId) {
                        $sub->where('kategori', 'Divisi')
                            ->where('bagian_id', $bagianId);
                    });
                })
                ->with(['peserta.user', 'peserta.bagian', 'mentor.user', 'bagian', 'laporanHarian'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('Penugasan.index', compact('penugasans'));
    }

    public function create()
    {
        $mentor = auth()->user(); // user login
        $peserta = Peserta::with('user')
            ->where('bagian_id', $mentor->mentor->bagian_id)
            ->aktifUntukForm()
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
                $sisaWaktuMaksimal = $peserta->getSisaWaktuMaksimalAttribute();
                if ($request->beban_waktu > $sisaWaktuMaksimal) {
                    return back()->withErrors([
                        'beban_waktu' => "Beban waktu tidak boleh melebihi sisa waktu maksimal peserta ({$sisaWaktuMaksimal} jam)."
                    ])->withInput();
                }
            }
        } elseif (strtolower($request->kategori) === 'divisi' && $request->peserta_ids) {
            // Untuk tugas divisi, cek sisa waktu maksimal terbesar (maksimal yang diizinkan)
            $pesertas = Peserta::whereIn('id', $request->peserta_ids)->get();
            $maxSisaWaktu = $pesertas->max(function($peserta) {
                return $peserta->getSisaWaktuMaksimalAttribute();
            });

            if ($request->beban_waktu > $maxSisaWaktu) {
                return back()->withErrors([
                    'beban_waktu' => "Beban waktu tidak boleh melebihi sisa waktu maksimal terbesar dari peserta yang dipilih ({$maxSisaWaktu} jam)."
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

        // Simpan penugasan
        $penugasan = Penugasan::create($validatedData);

        // Sync peserta ke pivot table untuk kategori Divisi
        if (strtolower($request->kategori) === 'divisi') {
            if ($request->has('select_all') && $request->select_all == '1') {
                $bagianId = optional(Auth::user()->mentor)->bagian_id;
                $pesertaIds = Peserta::where('bagian_id', $bagianId)->pluck('id')->toArray();
                $penugasan->pesertasRelation()->sync($pesertaIds);
            } else {
                $penugasan->pesertasRelation()->sync($request->peserta_ids);
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

        // Siapkan query default kosong
        $pesertas = collect();

        // Mentor hanya boleh edit jika bagian_id cocok
        if ($user->isMentor() && optional($user->mentor)->bagian_id == $penugasan->bagian_id) {
            $pesertas = Peserta::where('bagian_id', $user->mentor->bagian_id)
                ->aktifUntukForm()
                ->orderBy('id')
                ->get();
        }
        // Admin bebas akses semua
        elseif ($user->isAdmin()) {
            $pesertas = Peserta::aktifUntukForm()
                ->orderBy('id')
                ->get();
        }
        // Selain itu, tolak akses
        else {
            abort(403, 'AKSES DITOLAK: ANDA TIDAK MEMILIKI HAK AKSES.');
        }

        return view('Penugasan.edit', compact('penugasan', 'pesertas'));
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
                $penugasan->pesertasRelation()->sync($pesertaIds);
            } else {
                $penugasan->pesertasRelation()->sync($request->peserta_ids);
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
                $pesertaList = $penugasan->pesertas();
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
            $pesertasToUpdate = $penugasan->pesertas();
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
            ->orderBy('created_at', 'asc')
            ->get();

        // Inisialisasi variabel
        $currentProgress = 0;
        $rataRataProgressDivisi = 0;
        $pesertaList = collect();

        // Hitung progress berdasarkan kategori penugasan
        if ($penugasan->kategori === 'Divisi') {
            // Untuk penugasan Divisi: hitung rata-rata dari progress tertinggi setiap peserta
            $pesertaList = $penugasan->pesertas();
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

        return view('Penugasan.show', compact(
            'penugasan',
            'laporanHarians',
            'currentProgress',
            'latestLaporan',
            'pesertaList',
            'rataRataProgressDivisi'
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
