{{-- resources/views/laporan_harian/index.blade.php --}}
@extends('layouts.mantis')
@section('content')
<div class="">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1"><i class="ti ti-file-text me-2"></i>Ringkasan Laporan Harian</h2>
                    <p class="text-muted small mb-0">Overview semua aktivitas harian Anda. Untuk mengelola laporan, kunjungi halaman tugas terkait.</p>
                </div>
            </div>
        </div>

        <div class="card-body">
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if($laporanHarian->isEmpty())
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle"></i> Belum ada data laporan harian.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="tabel">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                @if(Auth::user() && Auth::user()->isAdmin())
                                <th>Nama Peserta</th>
                                <th>Bagian</th>
                                @endif
                                <th>Tanggal Laporan</th>
                                <th>Judul Tugas</th>
                                <th>Deskripsi Kegiatan</th>
                                <th>Progress</th>
                                <th>File</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($laporanHarian as $index => $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    @if(Auth::user()->isAdmin())
                                        <td>{{ $item->peserta->user->name ?? '-' }}</td>
                                        <td>{{ $item->peserta->bagian->nama_bagian ?? '-' }}</td>
                                    @endif
                                    <td>{{ $item->created_at->format('d M Y') }}</td>
                                    <td>{{ $item->penugasan->judul_tugas ?? '-' }}</td>
                                    <td>{{ Str::limit($item->deskripsi_kegiatan, 50) }}</td>

                                    <!-- Perbaikan Progress Tugas -->
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 10px;">
                                                <div class="progress-bar bg-success"
                                                     role="progressbar"
                                                     style="width: {{ $item->progres_tugas }}%"></div>
                                            </div>
                                            <span class="small">{{ $item->progres_tugas }}%</span>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        @if($item->file)
                                            <a href="{{ asset('storage/' . $item->file) }}"
                                               target="_blank"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-file"></i> Lihat
                                            </a>
                                        @else
                                            <span class="badge bg-secondary">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($item->penugasan)
                                            <a href="{{ route('penugasans.show', $item->penugasan->id) }}"
                                               class="btn btn-primary btn-sm"
                                               title="Lihat detail tugas terkait">
                                                <i class="ti ti-arrow-right me-1"></i>Lihat Tugas
                                            </a>
                                        @else
                                            <span class="badge bg-secondary">Tugas tidak ditemukan</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
