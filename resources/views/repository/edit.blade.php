{{-- resources/views/repository/edit.blade.php --}}
@extends('layouts.mantis')

@section('content')
<div class="container-fluid">
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('repository.index') }}">
                    <i class="fas fa-book-open me-1"></i>Repository
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('repository.show', $repository->id) }}">
                    {{ Str::limit($repository->judul, 30) }}
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            {{-- Header Card --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body bg-gradient-warning text-white p-4">
                    <h3 class="mb-2">
                        <i class="fas fa-edit me-2"></i>Edit Repository
                    </h3>
                    <p class="mb-0 opacity-75">Update informasi repository yang sudah ada</p>
                </div>
            </div>

            {{-- Info Laporan Akhir --}}
            @if($repository->laporanAkhir)
            <div class="card border-primary mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Informasi Laporan Akhir Terkait
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>Judul Laporan:</strong><br>
                                {{ $repository->laporanAkhir->judul_laporan }}
                            </p>
                            <p class="mb-2">
                                <strong>Peserta:</strong><br>
                                {{ $repository->peserta->nama_lengkap ?? ($repository->peserta->user->name ?? 'N/A') }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>Status:</strong>
                                <span class="badge bg-success">{{ ucfirst($repository->laporanAkhir->status) }}</span>
                            </p>
                            <p class="mb-0">
                                <strong>Dibuat:</strong> {{ $repository->created_at->format('d M Y H:i') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="card border-success mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-upload me-2"></i>Repository dari Upload Manual
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Repository ini dibuat melalui upload manual (bukan dari laporan akhir di sistem).
                        <br><strong>Peserta:</strong> {{ $repository->nama_peserta ?? 'N/A' }}
                    </div>
                </div>
            </div>
            @endif

            {{-- Form Card --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('repository.update', $repository->id) }}">
                        @csrf
                        @method('PUT')

                        {{-- Judul Repository --}}
                        <div class="mb-4">
                            <label for="judul" class="form-label">
                                <i class="fas fa-heading me-1"></i>Judul Repository <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="judul"
                                   id="judul"
                                   class="form-control @error('judul') is-invalid @enderror"
                                   value="{{ old('judul', $repository->judul) }}"
                                   required>
                            @error('judul')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Nama Peserta (untuk repository manual) --}}
                        @if(!$repository->laporanAkhir)
                        <div class="mb-4">
                            <label for="nama_peserta" class="form-label">
                                <i class="fas fa-user me-1"></i>Nama Peserta
                            </label>
                            <input type="text"
                                   name="nama_peserta"
                                   id="nama_peserta"
                                   class="form-control @error('nama_peserta') is-invalid @enderror"
                                   value="{{ old('nama_peserta', $repository->nama_peserta) }}"
                                   readonly>
                            @error('nama_peserta')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Nama peserta untuk repository yang dibuat manual (tidak dapat diubah)
                            </small>
                        </div>
                        @endif

                        {{-- Deskripsi Singkat --}}
                        <div class="mb-4">
                            <label for="deskripsi" class="form-label">
                                <i class="fas fa-align-left me-1"></i>Deskripsi Singkat <span class="text-danger">*</span>
                            </label>
                            <textarea name="deskripsi"
                                      id="deskripsi"
                                      rows="3"
                                      class="form-control @error('deskripsi') is-invalid @enderror"
                                      required>{{ old('deskripsi', $repository->deskripsi) }}</textarea>
                            @error('deskripsi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Deskripsi singkat yang akan ditampilkan di halaman utama repository (diisi manual oleh admin)
                            </small>
                        </div>

                        {{-- Deskripsi Lengkap --}}
                        <div class="mb-4">
                            <label for="deskripsi_lengkap" class="form-label">
                                <i class="fas fa-file-alt me-1"></i>Deskripsi Lengkap
                            </label>
                            <textarea name="deskripsi_lengkap"
                                      id="deskripsi_lengkap"
                                      rows="6"
                                      class="form-control @error('deskripsi_lengkap') is-invalid @enderror">{{ old('deskripsi_lengkap', $repository->deskripsi_lengkap) }}</textarea>
                            @error('deskripsi_lengkap')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Deskripsi detail dari laporan akhir yang ditampilkan di halaman detail repository
                            </small>
                        </div>

                        <div class="row">
                            {{-- Tahun Magang --}}
                            <div class="col-md-4 mb-4">
                                <label for="tahun_magang" class="form-label">
                                    <i class="fas fa-calendar me-1"></i>Tahun Magang <span class="text-danger">*</span>
                                </label>
                                <input type="number"
                                       name="tahun_magang"
                                       id="tahun_magang"
                                       class="form-control @error('tahun_magang') is-invalid @enderror"
                                       value="{{ old('tahun_magang', $repository->tahun_magang) }}"
                                       min="2020"
                                       max="{{ date('Y') + 1 }}"
                                       required>
                                @error('tahun_magang')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Bagian --}}
                            <div class="col-md-4 mb-4">
                                <label for="bagian" class="form-label">
                                    <i class="fas fa-building me-1"></i>Bagian/Divisi
                                </label>
                                <select name="bagian"
                                        id="bagian"
                                        class="form-select @error('bagian') is-invalid @enderror">
                                    <option value="">-- Pilih Bagian --</option>
                                    @foreach($bagians as $bagian)
                                        <option value="{{ $bagian->nama_bagian }}"
                                                {{ old('bagian', $repository->bagian) == $bagian->nama_bagian ? 'selected' : '' }}>
                                            {{ $bagian->nama_bagian }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('bagian')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Kategori --}}
                            <div class="col-md-4 mb-4">
                                <label for="kategori" class="form-label">
                                    <i class="fas fa-tag me-1"></i>Kategori
                                </label>
                                <select name="kategori"
                                        id="kategori"
                                        class="form-select @error('kategori') is-invalid @enderror">
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category }}"
                                                {{ old('kategori', $repository->kategori) == $category ? 'selected' : '' }}>
                                            {{ $category }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('kategori')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Status Publikasi --}}
                        <div class="mb-4">
                            <div class="card border-{{ $repository->is_published ? 'success' : 'warning' }}">
                                <div class="card-body">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="is_published"
                                               id="is_published"
                                               value="1"
                                               {{ old('is_published', $repository->is_published) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_published">
                                            <i class="fas fa-eye me-1"></i>Publikasikan ke repository
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        @if($repository->is_published)
                                            Repository ini saat ini <strong>dipublikasikan</strong> sejak {{ $repository->published_at->format('d M Y H:i') }}
                                        @else
                                            Repository ini saat ini masih dalam status <strong>draft</strong>
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>

                        {{-- Statistik --}}
                        <div class="alert alert-info mb-4">
                            <h6 class="mb-2">
                                <i class="fas fa-chart-bar me-2"></i>Statistik Repository
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1">
                                        <i class="fas fa-eye me-1"></i>
                                        <strong>Total Views:</strong> {{ number_format($repository->views) }}
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-0">
                                        <i class="fas fa-calendar me-1"></i>
                                        <strong>Terakhir Update:</strong> {{ $repository->updated_at->format('d M Y H:i') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="d-flex gap-2 pt-3 border-top">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-1"></i>Update Repository
                            </button>
                            <a href="{{ route('repository.show', $repository->id) }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Batal
                            </a>
                            <div class="ms-auto">
                                <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                                    <i class="fas fa-trash me-1"></i>Hapus Repository
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Konfirmasi Hapus --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus repository ini?</p>
                <p class="text-danger small mb-0">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Tindakan ini tidak dapat dibatalkan. Data repository akan dihapus permanen.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" action="{{ route('repository.destroy', $repository->id) }}" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Hapus Permanen</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-gradient-warning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
</style>

<script>
    function confirmDelete() {
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }
</script>
@endsection
