@extends('layouts.mantis')

@section('content')
<div class="container-fluid">
    <!-- Header dengan tombol navigasi -->
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="mb-0">Detail Laporan Akhir</h2>
            <div class="d-flex gap-2">
                <a href="{{ route('laporan-akhir.index') }}" class="btn btn-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Kembali
                </a>

                {{-- Tombol Edit untuk Peserta (draft & review) --}}
                @if(Auth::user()->isPeserta())
                    @if(in_array($laporanAkhir->status, ['draft', 'review']))
                        <a href="{{ route('laporan-akhir.edit', $laporanAkhir->id) }}" class="btn btn-warning">
                            <i class="ti ti-edit me-1"></i> Edit Laporan
                        </a>
                    @elseif($laporanAkhir->status == 'terima')
                        <span class="btn btn-success disabled" title="Laporan sudah diterima">
                            <i class="ti ti-lock me-1"></i> Sudah Diterima
                        </span>
                    @else
                        <span class="btn btn-secondary disabled" title="Laporan ditolak, tidak dapat diedit">
                            <i class="ti ti-lock me-1"></i> Tidak Dapat Diedit
                        </span>
                    @endif
                @endif

                {{-- Tombol Edit untuk Mentor/Admin --}}
                @unless(Auth::user()->isPeserta())
                    @if($laporanAkhir->status == 'terima')
                        <span class="btn btn-secondary disabled" title="Laporan sudah diterima, tidak dapat diedit">
                            <i class="ti ti-lock me-1"></i> Edit (Terkunci)
                        </span>
                    @else
                        <a href="{{ route('laporan-akhir.edit', $laporanAkhir->id) }}" class="btn btn-warning">
                            <i class="ti ti-edit me-1"></i> Edit
                        </a>
                    @endif
                @endunless
            </div>
        </div>
    </div>

    <!-- Informasi Peserta -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Informasi Peserta</h5>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="row g-3">
                        <div class="col-md-4 fw-bold">Nama Peserta</div>
                        <div class="col-md-8">: {{ $laporanAkhir->peserta->nama_lengkap ?? '-' }}</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4 fw-bold">Nomor Identitas</div>
                        <div class="col-md-8">: {{ $laporanAkhir->peserta->nomor_identitas ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row g-3">
                        <div class="col-md-4 fw-bold">Departemen</div>
                        <div class="col-md-8">: {{ $laporanAkhir->peserta->bagian->nama_bagian ?? '-' }}</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4 fw-bold">Tahun Magang</div>
                        <div class="col-md-8">: {{ $laporanAkhir->peserta->tanggal_mulai_magang ? \Carbon\Carbon::parse($laporanAkhir->peserta->tanggal_mulai_magang)->format('Y') : '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Informasi Laporan Akhir -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="ti ti-file-text me-2"></i>Informasi Laporan Akhir</h5>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <!-- Kolom Kiri - Informasi Dasar -->
                <div class="col-md-6">
                    <div class="row g-3">
                        <div class="col-md-4 fw-bold">Tanggal Dibuat</div>
                        <div class="col-md-8">: {{ $laporanAkhir->created_at->format('d F Y H:i') }}</div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-4 fw-bold">Judul Laporan</div>
                        <div class="col-md-8">: {{ $laporanAkhir->judul_laporan }}</div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-4 fw-bold">Status</div>
                        <div class="col-md-8">
                            : <span class="badge bg-{{ $laporanAkhir->status === 'terima' ? 'success' : ($laporanAkhir->status === 'tolak' ? 'danger' : ($laporanAkhir->status === 'draft' ? 'secondary' : 'warning')) }}">
                                {{ ucfirst($laporanAkhir->status) }}
                            </span>
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        @if($laporanAkhir->deskripsi_repository)
                        <div class="">
                            <div class="col-12">
                                <label class="fw-bold d-block mb-2"><i class=""></i>Deskripsi Singkat Repository:</label>
                                <div class="border rounded p-3" style="background-color: #f8f9fa;">
                                    {!! nl2br(e($laporanAkhir->deskripsi_repository)) !!}
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    <!-- Deskripsi Laporan -->
                    <div class="row g-3 mt-3">
                        <div class="col-12">
                            <label class="fw-bold d-block mb-2"><i class="ti ti-file-description me-1"></i>Deskripsi Laporan:</label>
                            <div class="border rounded p-3" style="background-color: #f8f9fa;">
                                {!! nl2br(e($laporanAkhir->deskripsi_laporan)) !!}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan - File, Approve, dan Feedback -->
                <div class="col-md-6">
                    <!-- File Lampiran -->
                    <div class="mb-3">
                        <label class="fw-bold d-block"><i class="ti ti-file me-1"></i>File Lampiran:</label>
                        <div class="mt-2">
                            @if($laporanAkhir->file_path)
                                <a href="{{ Storage::url($laporanAkhir->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="ti ti-file-pdf me-1"></i> Lihat File
                                </a>
                            @else
                                <span class="badge bg-secondary">Tidak ada file</span>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        @if($laporanAkhir->kategori_repository)
                        <div class="row g-3 mb-3">
                            <div class="col-md-3 fw-bold">Kategori Repository</div>
                            <div class="col-md-9">: <span class="badge bg-primary">{{ $laporanAkhir->kategori_repository }}</span></div>
                        </div>
                        @endif
                    </div>

                    <!-- Ubah Status -->
                    <div class="mb-3">
                        <label class="fw-bold d-block"><i class="ti ti-edit me-1"></i>Status Laporan:</label>
                        <div class="mt-2">
                            @if(Auth::user()->isPeserta())
                                {{-- Peserta: Hanya tampilkan badge status --}}
                                <span class="badge bg-{{ $laporanAkhir->status === 'terima' ? 'success' : ($laporanAkhir->status === 'tolak' ? 'danger' : ($laporanAkhir->status === 'draft' ? 'secondary' : 'warning')) }} fs-6">
                                    <i class="ti ti-{{ $laporanAkhir->status === 'terima' ? 'check' : ($laporanAkhir->status === 'tolak' ? 'x' : 'clock') }} me-1"></i>
                                    {{ ucfirst($laporanAkhir->status) }}
                                </span>

                                {{-- Badge Repository jika ada --}}
                                @if($laporanAkhir->status === 'terima' && $laporanAkhir->repository)
                                    <span class="badge bg-{{ $laporanAkhir->repository->is_published ? 'primary' : 'secondary' }} fs-6 ms-2">
                                        <i class="ti ti-{{ $laporanAkhir->repository->is_published ? 'world' : 'file' }} me-1"></i>
                                        Repository: {{ $laporanAkhir->repository->is_published ? 'Published' : 'Draft' }}
                                    </span>
                                @endif

                                @if($laporanAkhir->status === 'terima')
                                    <div class="alert alert-success mt-2 mb-0">
                                        <i class="ti ti-info-circle me-2"></i>
                                        <strong>Laporan sudah diterima dan di-publish ke repository.</strong> Status tidak dapat diubah lagi.
                                    </div>
                                @elseif($laporanAkhir->status === 'review')
                                    <div class="alert alert-info mt-2 mb-0">
                                        <i class="ti ti-info-circle me-2"></i>
                                        <strong>Laporan sedang direview oleh mentor.</strong> Anda masih dapat mengedit laporan jika ada catatan dari mentor.
                                    </div>
                                @elseif($laporanAkhir->status === 'draft')
                                    <div class="alert alert-warning mt-2 mb-0">
                                        <i class="ti ti-info-circle me-2"></i>
                                        <strong>Laporan masih dalam bentuk draft.</strong> Silakan lengkapi dan submit untuk direview.
                                    </div>
                                @elseif($laporanAkhir->status === 'tolak')
                                    <div class="alert alert-danger mt-2 mb-0">
                                        <i class="ti ti-alert-triangle me-2"></i>
                                        <strong>Laporan ditolak.</strong> Silakan buat laporan baru sesuai catatan mentor.
                                    </div>
                                @endif
                            @else
                                {{-- Admin/Mentor: Bisa ubah status --}}
                                @php
                                    // Cek apakah repository sudah published
                                    $isPublished = $laporanAkhir->status === 'terima' && $laporanAkhir->repository && $laporanAkhir->repository->is_published;
                                    // Cek apakah mentor mencoba ubah status terima
                                    $isMentorTerima = Auth::user()->isMentor() && $laporanAkhir->status === 'terima';
                                @endphp

                                <span class="badge bg-{{ $laporanAkhir->status === 'terima' ? 'success' : ($laporanAkhir->status === 'tolak' ? 'danger' : ($laporanAkhir->status === 'draft' ? 'secondary' : 'warning')) }} fs-6">
                                    <i class="ti ti-{{ $laporanAkhir->status === 'terima' ? 'check' : ($laporanAkhir->status === 'tolak' ? 'x' : 'clock') }} me-1"></i>
                                    {{ ucfirst($laporanAkhir->status) }}
                                </span>

                                {{-- Badge Repository jika ada --}}
                                @if($laporanAkhir->status === 'terima' && $laporanAkhir->repository)
                                    <span class="badge bg-{{ $laporanAkhir->repository->is_published ? 'primary' : 'secondary' }} fs-6 ms-2">
                                        <i class="ti ti-{{ $laporanAkhir->repository->is_published ? 'world' : 'file' }} me-1"></i>
                                        Repository: {{ $laporanAkhir->repository->is_published ? 'Published' : 'Draft' }}
                                    </span>
                                @endif

                                @if($isMentorTerima)
                                    {{-- Mentor tidak bisa ubah status yang sudah terima --}}
                                    <div class="alert alert-info mt-2 mb-0">
                                        <i class="ti ti-info-circle me-2"></i>
                                        <strong>Laporan sudah diterima.</strong> Hanya Admin yang dapat mengubah status laporan yang sudah diterima.
                                    </div>
                                @elseif($isPublished)
                                    {{-- Repository Published: Tidak bisa ubah status (untuk Admin) --}}
                                    <div class="alert alert-warning mt-2 mb-0">
                                        <i class="ti ti-alert-triangle me-2"></i>
                                        <strong>Repository sudah dipublikasikan.</strong> Untuk mengubah status, unpublish repository terlebih dahulu di halaman Repository.
                                    </div>
                                @else
                                    {{-- Repository Draft atau belum ada: Bisa ubah status --}}
                                    <form action="{{ route('laporan-akhir.updateStatus', $laporanAkhir->id) }}" method="POST" class="mt-2">
                                        @csrf
                                        @method('PATCH')
                                        <div class="d-flex align-items-center gap-2">
                                            <select name="status" class="form-select form-select-sm" style="width: 200px;" onchange="this.form.submit()">
                                                <option value="draft" {{ $laporanAkhir->status == 'draft' ? 'selected' : '' }}>Draft</option>
                                                <option value="review" {{ $laporanAkhir->status == 'review' ? 'selected' : '' }}>Review</option>
                                                <option value="terima" {{ $laporanAkhir->status == 'terima' ? 'selected' : '' }}>Terima</option>
                                                <option value="tolak" {{ $laporanAkhir->status == 'tolak' ? 'selected' : '' }}>Tolak</option>
                                            </select>
                                            <small class="text-muted">Pilih status untuk mengubah</small>
                                        </div>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>

                    <!-- Catatan Mentor/Admin -->
                    @if(!Auth::user()->isPeserta())
                        <!-- Catatan yang sudah ada -->
                        @if($laporanAkhir->catatan_mentor)
                            <div class="mb-3">
                                <label class="fw-bold d-block"><i class="ti ti-message-circle me-1"></i>Catatan dari Mentor:</label>
                                <div class="alert alert-{{ $laporanAkhir->status === 'tolak' ? 'warning' : 'info' }} mt-2 mb-0">
                                    @if($laporanAkhir->status === 'tolak')
                                        <i class="ti ti-alert-triangle me-2"></i>
                                    @else
                                        <i class="ti ti-info-circle me-2"></i>
                                    @endif
                                    {{ $laporanAkhir->catatan_mentor }}
                                </div>
                            </div>
                        @endif

                        <!-- Form Edit Catatan -->
                        @if($laporanAkhir->status !== 'terima')
                        <div class="mb-3">
                            <label class="fw-bold d-block">
                                <i class="ti ti-message-plus me-1"></i>
                                @if($laporanAkhir->catatan_mentor)
                                    Edit Catatan:
                                @else
                                    Beri Catatan:
                                @endif
                            </label>
                            <form action="{{ route('laporan-akhir.updateStatus', $laporanAkhir->id) }}" method="POST" class="mt-2">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $laporanAkhir->status }}">
                                <div class="mb-2">
                                    <textarea name="catatan_mentor" class="form-control" rows="3" placeholder="Masukkan catatan untuk peserta...">{{ $laporanAkhir->catatan_mentor }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-{{ $laporanAkhir->status === 'tolak' ? 'warning' : 'info' }} btn-sm">
                                    <i class="ti ti-device-floppy me-1"></i>
                                    @if($laporanAkhir->catatan_mentor)
                                        Update Catatan
                                    @else
                                        Simpan Catatan
                                    @endif
                                </button>
                            </form>
                        </div>
                        @endif
                    @else
                        <!-- Untuk Peserta - Tampilkan catatan read-only -->
                        @if($laporanAkhir->catatan_mentor)
                            <div class="mb-3">
                                <label class="fw-bold d-block"><i class="ti ti-message-circle me-1"></i>Catatan dari Mentor:</label>
                                <div class="alert alert-{{ in_array($laporanAkhir->status, ['review', 'tolak']) ? 'warning' : 'info' }} mt-2 mb-0">
                                    @if(in_array($laporanAkhir->status, ['review', 'tolak']))
                                        <i class="ti ti-alert-triangle me-2"></i>
                                    @else
                                        <i class="ti ti-info-circle me-2"></i>
                                    @endif
                                    <strong class="d-block mb-2">{{ $laporanAkhir->catatan_mentor }}</strong>

                                    @if(in_array($laporanAkhir->status, ['draft', 'review']))
                                        <hr class="my-2">
                                        <small class="d-block">
                                            <i class="ti ti-edit me-1"></i>
                                            Anda dapat mengedit laporan untuk memperbaiki sesuai catatan di atas.
                                        </small>
                                    @elseif($laporanAkhir->status === 'tolak')
                                        <hr class="my-2">
                                        <small class="d-block">
                                            <i class="ti ti-info-circle me-1"></i>
                                            Silakan buat laporan baru dengan perbaikan sesuai catatan di atas.
                                        </small>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Error Messages -->
    @if ($errors->any())
        <div class="alert alert-danger">
            <h6><i class="ti ti-alert-triangle me-2"></i>Terjadi kesalahan:</h6>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection


