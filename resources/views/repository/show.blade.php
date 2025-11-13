{{-- resources/views/repository/show.blade.php --}}
@extends('layouts.mantis')

@section('content')
<div class="container-fluid">

    <div class="row">
        {{-- Main Content --}}
        <div class="col-lg-8">
            {{-- Repository Detail Card --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    {{-- Status Badge --}}
                    <div class="mb-3">
                        @if($repository->is_published)
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle me-1"></i>Published
                            </span>
                        @else
                            <span class="badge bg-warning">
                                <i class="fas fa-clock me-1"></i>Draft
                            </span>
                        @endif

                        @if($repository->kategori)
                            <span class="badge bg-primary">{{ $repository->kategori }}</span>
                        @endif

                        <span class="badge bg-info">
                            <i class="fas fa-calendar me-1"></i>{{ $repository->tahun_magang }}
                        </span>

                        <span class="badge bg-secondary">
                            <i class="fas fa-eye me-1"></i>{{ $repository->views }} views
                        </span>
                    </div>

                    {{-- Judul --}}
                    <h2 class="mb-4">{{ $repository->judul }}</h2>

                    {{-- Deskripsi Singkat --}}
                    <div class="alert alert-light border-start border-black border-1 mb-4">
                        <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Deskripsi Singkat</h6>
                        <p class="mb-0">{{ $repository->deskripsi }}</p>
                    </div>

                    {{-- Deskripsi Lengkap --}}
                    @if($repository->deskripsi_lengkap)
                    <div class="mb-4">
                        <h5 class="mb-3">
                            <i class="fas fa-file-alt me-2"></i>Deskripsi Lengkap
                        </h5>
                        <div class="bg-gray-200 p-4 rounded">
                            {!! nl2br(e($repository->deskripsi_lengkap)) !!}
                        </div>
                    </div>
                    @endif

                    {{-- Laporan Akhir Section --}}
                    <div class="card border-2 mb-4">
                        <div class="card-header bg-blue-100 text-blue-800">
                            <h5 class="mb-0 text-blue-800">
                                <i class="fas fa-file-pdf me-2"></i>Laporan Akhir
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($repository->laporanAkhir)
                                {{-- Dari Sistem --}}
                                <div class="row">
                                    <div class="col-md-8">
                                        <h6 class="mb-2">{{ $repository->laporanAkhir->judul_laporan }}</h6>
                                        <p class="text-muted mb-2">
                                            {{ $repository->laporanAkhir->deskripsi_laporan }}
                                        </p>
                                        <div class="text-muted">
                                            <i class="fas fa-user me-2"></i>
                                            <strong>Pembimbing:</strong> {{ $repository->laporanAkhir->mentor->nama_mentor ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-md-end">
                                        @if($repository->laporanAkhir->file_path)
                                            <a href="{{ Storage::url($repository->laporanAkhir->file_path) }}"
                                               target="_blank"
                                               class="btn btn-primary btn-lg">
                                                <i class="fas fa-download me-2"></i>Download PDF
                                            </a>
                                        @else
                                            <button class="btn btn-secondary btn-lg" disabled>
                                                <i class="fas fa-times-circle me-2"></i>File Tidak Tersedia
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @elseif($repository->file_path)
                                {{-- Dari Upload Manual --}}
                                <div class="alert alert-info mb-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <small>Laporan ini diarsipkan secara manual dari penyimpanan lokal</small>
                                </div>
                                <div class="row">
                                    <div class="col-md-8">
                                        <h6 class="mb-2">{{ $repository->judul }}</h6>
                                        <div class="text-muted small">
                                            <i class="fas fa-user me-2"></i>
                                            <strong>Peserta:</strong> {{ $repository->nama_peserta }}
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-md-end">
                                        <a href="{{ Storage::url($repository->file_path) }}"
                                           target="_blank"
                                           class="btn btn-primary btn-lg">
                                            <i class="fas fa-download me-2"></i>Download PDF
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-warning mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    File laporan tidak tersedia
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Admin Actions --}}
                    @if(Auth::user()->isAdmin())
                    <div class="card border-warning mb-4">
                        <div class="card-header bg-warning">
                            <h6 class="mb-0">
                                <i class="fas fa-tools me-2"></i>Admin Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="{{ route('repository.edit', $repository->id) }}" class="btn btn-warning">
                                    <i class="fas fa-edit me-1"></i>Edit Repository
                                </a>

                                @if($repository->is_published)
                                    <form method="POST" action="{{ route('repository.unpublish', $repository->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary" onclick="return confirm('Yakin ingin unpublish repository ini?')">
                                            <i class="fas fa-eye-slash me-1"></i>Unpublish
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('repository.publish', $repository->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success" onclick="return confirm('Yakin ingin publish repository ini?')">
                                            <i class="fas fa-check-circle me-1"></i>Publish
                                        </button>
                                    </form>
                                @endif

                                <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                                    <i class="fas fa-trash me-1"></i>Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                {{-- Author Information --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0">
                            <i class="fas fa-user-circle me-2"></i>Informasi Penulis
                        </h6>
                    </div>
                    <div class="card-body">
                        {{-- Author Info - Unified untuk sistem & manual --}}
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle {{ $repository->is_manual ? 'bg-success' : 'bg-primary' }} text-white me-3">
                                {{ strtoupper(substr($repository->nama_peserta_lengkap, 0, 1)) }}
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $repository->nama_peserta_lengkap }}</h6>
                                <small class="text-muted">
                                    Peserta Magang
                                    @if($repository->is_manual)
                                        <span class="badge bg-success ms-1">Arsip</span>
                                    @endif
                                </small>
                            </div>
                        </div>

                        <div class="border-top pt-3">
                            @if($repository->peserta && $repository->peserta->email)
                            <div class="mb-2">
                                <i class="fas fa-envelope text-muted me-2"></i>
                                <small>{{ $repository->peserta->email }}</small>
                            </div>
                            @endif


                            @if($repository->bagian)
                            <div class="mb-2">
                                <i class="fas fa-building text-muted me-2"></i>
                                <small>{{ $repository->bagian }}</small>
                            </div>
                            @endif

                            <div class="mb-2">
                                <i class="fas fa-calendar text-muted me-2"></i>
                                <small>Tahun Magang: {{ $repository->tahun_magang }}</small>
                            </div>

                            <div>
                                <i class="fas fa-clock text-muted me-2"></i>
                                <small>Dipublikasikan: {{ $repository->published_at ? $repository->published_at->format('d M Y') : '-' }}</small>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Related Repository --}}
                    @if($relatedRepositories->isNotEmpty())
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="mb-0">
                                <i class="fas fa-link me-2"></i>Repository Terkait
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                @foreach($relatedRepositories as $related)
                                <a href="{{ route('repository.show', $related->id) }}"
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 text-break">{{ $related->judul }}</h6>
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i>{{ $related->nama_peserta_lengkap }}
                                            </small>
                                        </div>
                                        <div class="ms-2 flex-shrink-0">
                                            <span class="badge bg-primary">{{ $related->tahun_magang }}</span>
                                            @if($related->is_manual)
                                                <span class="badge bg-success ms-1">
                                                    <i class="fas fa-archive"></i>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
            </div>
        </div>
    </div>
</div>

{{-- Modal Konfirmasi Hapus --}}
@if(Auth::user()->isAdmin())
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
                    Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" action="{{ route('repository.destroy', $repository->id) }}" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@push('styles')
<style>
    .avatar-circle {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: bold;
    }

    .list-group-item-action:hover {
        background-color: #f8f9fa;
    }

    .text-break {
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
</style>
@endpush

@push('scripts')
<script>
    function confirmDelete() {
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }
</script>
@endpush
@endsection
