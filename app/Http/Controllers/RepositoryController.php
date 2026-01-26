<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Interfaces\RepositoryRepositoryInterface;
use App\Models\LaporanAkhir;
use App\Models\Bagian;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

class RepositoryController extends Controller
{
    protected $repositoryRepo;

    public function __construct(RepositoryRepositoryInterface $repositoryRepo)
    {
        $this->middleware('auth');
        $this->repositoryRepo = $repositoryRepo;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        // Ambil filter dari request
        $year = $request->get('year');
        $category = $request->get('category');
        $bagian = $request->get('bagian');
        $search = $request->get('search');
        $status = $request->get('status'); // draft atau published

        // Build query
        $query = \App\Models\Repository::with(['laporanAkhir', 'peserta.user', 'peserta.bagian']);

        // Filter berdasarkan status (Admin only)
        if ($user && $user->isAdmin() && $status) {
            if ($status === 'published') {
                $query->where('is_published', true);
            } elseif ($status === 'draft') {
                $query->where('is_published', false);
            }
        } elseif (!$user->isAdmin()) {
            // Non-admin hanya lihat published
            $query->where('is_published', true);
        }

        // Filter berdasarkan tahun
        if ($year) {
            $query->where('tahun_magang', $year);
        }

        // Filter berdasarkan kategori
        if ($category) {
            $query->where('kategori', $category);
        }

        // Filter berdasarkan bagian
        if ($bagian) {
            $query->where('bagian', $bagian);
        }

        // Filter berdasarkan search
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%")
                  ->orWhere('deskripsi_lengkap', 'like', "%{$search}%")
                  ->orWhere('kategori', 'like', "%{$search}%");
            });
        }

        // Order by
        if ($user && $user->isAdmin()) {
            $query->orderBy('is_published', 'asc')->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('published_at', 'desc');
        }

        $repositories = $query->get();

        // Ambil data untuk filter dropdown
        $years = DB::table('repositories')
            ->select('tahun_magang')
            ->distinct()
            ->where('is_published', true)
            ->orderBy('tahun_magang', 'desc')
            ->pluck('tahun_magang');

        $categories = DB::table('repositories')
            ->select('kategori')
            ->distinct()
            ->where('is_published', true)
            ->whereNotNull('kategori')
            ->orderBy('kategori', 'asc')
            ->pluck('kategori');

        $bagians = Bagian::orderBy('nama_bagian', 'asc')->get();

        // Ambil statistik
        $statistics = $this->repositoryRepo->getStatistics();

        return view('repository.index', compact(
            'repositories',
            'years',
            'categories',
            'bagians',
            'statistics'
        ));
    }

    /**
     * Form untuk membuat repository dari laporan akhir yang sudah di-ACC
     */
    public function create(Request $request)
    {
        // Hanya admin yang bisa membuat repository
        if (!Auth::user()->isAdmin()) {
            Alert::error('Error', 'Anda tidak memiliki akses untuk membuat repository.');
            return redirect()->route('repository.index');
        }

        // Ambil laporan akhir yang sudah di-ACC tapi belum ada di repository
        $laporanAkhirs = LaporanAkhir::where('status', 'terima')
            ->whereNotIn('id', function($query) {
                $query->select('laporan_akhir_id')
                    ->from('repositories');
            })
            ->with(['peserta.user', 'peserta.bagian'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Ambil data bagian dan kategori untuk dropdown
        $bagians = Bagian::orderBy('nama_bagian', 'asc')->get();
        $categories = ['Teknik', 'Non-Teknik', 'Manajemen', 'Penelitian', 'Lainnya'];

        // Ambil laporan_akhir_id dari query string jika ada
        $selectedLaporanId = $request->get('laporan_akhir_id');

        return view('repository.create', compact(
            'laporanAkhirs',
            'bagians',
            'categories',
            'selectedLaporanId'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Hanya admin yang bisa membuat repository
        if (!Auth::user()->isAdmin()) {
            Alert::error('Error', 'Anda tidak memiliki akses untuk membuat repository.');
            return redirect()->route('repository.index');
        }

        // Cek mode input
        $inputMode = $request->input('input_mode', 'system');

        if ($inputMode === 'manual') {
            // Validasi untuk mode manual
            $validated = $request->validate([
                'file_laporan_manual' => 'required|file|mimes:pdf|max:10240', // 10MB
                'nama_peserta_manual' => 'required|string|max:255',
                'judul' => 'required|string|max:255',
                'deskripsi' => 'required|string|max:1000',
                'deskripsi_lengkap' => 'nullable|string|max:65000',
                'tahun_magang' => 'required|digits:4',
                'bagian' => 'nullable|string|max:255',
                'kategori' => 'nullable|string|max:255',
                'is_published' => 'nullable|boolean',
            ], [
                'deskripsi.max' => 'Deskripsi singkat tidak boleh lebih dari 1000 karakter.',
                'deskripsi_lengkap.max' => 'Deskripsi lengkap tidak boleh lebih dari 65000 karakter.',
                'file_laporan_manual.required' => 'File laporan PDF wajib diupload.',
                'file_laporan_manual.mimes' => 'File harus berformat PDF.',
                'file_laporan_manual.max' => 'Ukuran file maksimal 10MB.',
            ]);

            try {
                // Upload file PDF
                $file = $request->file('file_laporan_manual');
                $fileName = 'repository_manual_' . time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('laporan_repository', $fileName, 'public');

                // Buat repository manual
                $repository = $this->repositoryRepo->createManual([
                    'judul' => $validated['judul'],
                    'deskripsi' => $validated['deskripsi'],
                    'deskripsi_lengkap' => $validated['deskripsi_lengkap'] ?? null,
                    'tahun_magang' => $validated['tahun_magang'],
                    'bagian' => $validated['bagian'] ?? null,
                    'kategori' => $validated['kategori'] ?? null,
                    'is_published' => $validated['is_published'] ?? false,
                    'file_path' => $filePath,
                    'nama_peserta' => $validated['nama_peserta_manual'],
                ]);

                Alert::success('Berhasil', 'Repository berhasil dibuat dari upload manual.');
                return redirect()->route('repository.show', $repository->id);
            } catch (\Exception $e) {
                Alert::error('Error', $e->getMessage());
                return redirect()->back()->withInput();
            }
        } else {
            // Validasi untuk mode dari sistem
            $validated = $request->validate([
                'laporan_akhir_id' => 'required|exists:laporan_akhirs,id',
                'judul' => 'nullable|string|max:255',
                'deskripsi' => 'required|string',
                'deskripsi_lengkap' => 'nullable|string',
                'tahun_magang' => 'required|digits:4',
                'bagian' => 'nullable|string|max:255',
                'kategori' => 'nullable|string|max:255',
                'is_published' => 'nullable|boolean',
            ]);

            try {
                $repository = $this->repositoryRepo->createFromLaporanAkhir(
                    $validated['laporan_akhir_id'],
                    $validated
                );

                Alert::success('Berhasil', 'Repository berhasil dibuat.');
                return redirect()->route('repository.show', $repository->id);
            } catch (\Exception $e) {
                Alert::error('Error', $e->getMessage());
                return redirect()->back()->withInput();
            }
        }
    }

    /**
     * Display the specified resource.
     * Halaman detail repository
     */
    public function show(string $id)
    {
        try {
            $repository = $this->repositoryRepo->findById($id);

            // Cek akses: jika repository belum published, hanya admin yang bisa lihat
            if (!$repository->is_published && !Auth::user()->isAdmin()) {
                Alert::error('Error', 'Repository ini belum dipublikasikan.');
                return redirect()->route('repository.index');
            }

            // Ambil repository terkait (same category or same year)
            $relatedRepositories = \App\Models\Repository::published()
                ->where('id', '!=', $id)
                ->where(function($query) use ($repository) {
                    $query->where('kategori', $repository->kategori)
                          ->orWhere('tahun_magang', $repository->tahun_magang);
                })
                ->limit(5)
                ->get();

            return view('repository.show', compact('repository', 'relatedRepositories'));
        } catch (\Exception $e) {
            Alert::error('Error', 'Repository tidak ditemukan.');
            return redirect()->route('repository.index');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Hanya admin yang bisa edit repository
        if (!Auth::user()->isAdmin()) {
            Alert::error('Error', 'Anda tidak memiliki akses untuk mengedit repository.');
            return redirect()->route('repository.index');
        }

        try {
            $repository = $this->repositoryRepo->findById($id);

            // Cek apakah repository sudah published
            if ($repository->is_published) {
                Alert::warning('Repository Terkunci', 'Repository yang sudah dipublikasikan tidak dapat diedit. Silakan unpublish terlebih dahulu.');
                return redirect()->route('repository.show', $id);
            }

            $bagians = Bagian::orderBy('nama_bagian', 'asc')->get();
            $categories = ['Teknik', 'Non-Teknik', 'Manajemen', 'Penelitian', 'Lainnya'];

            return view('repository.edit', compact('repository', 'bagians', 'categories'));
        } catch (\Exception $e) {
            Alert::error('Error', 'Repository tidak ditemukan.');
            return redirect()->route('repository.index');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Hanya admin yang bisa update repository
        if (!Auth::user()->isAdmin()) {
            Alert::error('Error', 'Anda tidak memiliki akses untuk mengupdate repository.');
            return redirect()->route('repository.index');
        }

        try {
            $repository = $this->repositoryRepo->findById($id);

            // Cek apakah repository sudah published
            if ($repository->is_published) {
                Alert::warning('Repository Terkunci', 'Repository yang sudah dipublikasikan tidak dapat diupdate. Silakan unpublish terlebih dahulu.');
                return redirect()->route('repository.show', $id);
            }

            $validated = $request->validate([
                'judul' => 'required|string|max:255',
                'nama_peserta' => 'nullable|string|max:255', // untuk repository manual
                'deskripsi' => 'required|string',
                'deskripsi_lengkap' => 'nullable|string',
                'tahun_magang' => 'required|digits:4',
                'bagian' => 'nullable|string|max:255',
                'kategori' => 'nullable|string|max:255',
                'is_published' => 'nullable|boolean',
            ]);

            $repository = $this->repositoryRepo->update($id, $validated);
            Alert::success('Berhasil', 'Repository berhasil diupdate.');
            return redirect()->route('repository.show', $id);
        } catch (\Exception $e) {
            Alert::error('Error', $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Hanya admin yang bisa hapus repository
        if (!Auth::user()->isAdmin()) {
            Alert::error('Error', 'Anda tidak memiliki akses untuk menghapus repository.');
            return redirect()->route('repository.index');
        }

        try {
            $repository = $this->repositoryRepo->findById($id);

            // Cek apakah repository sudah published
            if ($repository->is_published) {
                Alert::warning('Repository Terkunci', 'Repository yang sudah dipublikasikan tidak dapat dihapus. Silakan unpublish terlebih dahulu.');
                return redirect()->route('repository.show', $id);
            }

            $this->repositoryRepo->delete($id);
            Alert::success('Berhasil', 'Repository berhasil dihapus.');
            return redirect()->route('repository.index');
        } catch (\Exception $e) {
            Alert::error('Error', $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Publish repository
     */
    public function publish(string $id)
    {
        // Hanya admin yang bisa publish repository
        if (!Auth::user()->isAdmin()) {
            Alert::error('Error', 'Anda tidak memiliki akses.');
            return redirect()->route('repository.index');
        }

        try {
            $this->repositoryRepo->publish($id);
            Alert::success('Berhasil', 'Repository berhasil dipublikasikan.');
            return redirect()->back();
        } catch (\Exception $e) {
            Alert::error('Error', $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Unpublish repository
     */
    public function unpublish(string $id)
    {
        // Hanya admin yang bisa unpublish repository
        if (!Auth::user()->isAdmin()) {
            Alert::error('Error', 'Anda tidak memiliki akses.');
            return redirect()->route('repository.index');
        }

        try {
            $this->repositoryRepo->unpublish($id);
            Alert::success('Berhasil', 'Repository berhasil di-unpublish.');
            return redirect()->back();
        } catch (\Exception $e) {
            Alert::error('Error', $e->getMessage());
            return redirect()->back();
        }
    }
}
