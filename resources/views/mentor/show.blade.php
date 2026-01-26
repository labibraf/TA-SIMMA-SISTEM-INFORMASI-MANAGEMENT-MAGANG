@extends('layouts.mantis')
@section('content')
<div class="">
    <div class="mb-3">
        <a href="{{ route('mentor.index') }}" class="btn btn-secondary">
            < Kembali
        </a>
    </div>

    <div class="row">
        {{-- Informasi Utama --}}
        <div class="col-xl-8 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Detail Mentor</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="mb-3">
                                <label class="text-muted">Nama Mentor</label>
                                <p class="mb-0">{{ $mentor->nama_mentor }}</p>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="mb-3">
                                <label class="text-muted">Nomor Identitas</label>
                                <p class="mb-0">{{ $mentor->nomor_identitas }}</p>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="mb-3">
                                <label class="text-muted">Email</label>
                                <p class="mb-0">{{ $mentor->email }}</p>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="mb-3">
                                <label class="text-muted">No. Telepon</label>
                                <p class="mb-0">{{ $mentor->no_telepon }}</p>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="mb-3">
                                <label class="text-muted">Jenis Kelamin</label>
                                <p class="mb-0">{{ $mentor->jenis_kelamin }}</p>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="mb-3">
                                <label class="text-muted">Bagian</label>
                                <p class="mb-0">{{ $mentor->bagian?->nama_bagian ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="text-muted">Alamat</label>
                                <p class="mb-0">{{ $mentor->alamat }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Informasi Profesional --}}
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informasi Profesional</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted">Keahlian</label>
                                <p class="mb-0">{{ $mentor->keahlian }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted">Status Mentor</label>
                                <p class="mb-0">
                                    @if($mentor->user && $mentor->user->role_id == 2)
                                        <span class="badge bg-success rounded-pill px-3 py-2 fs-6">
                                            <i class="fas fa-check-circle"></i> Mentor Aktif
                                        </span>
                                    @elseif($mentor->user)
                                        <span class="badge bg-warning rounded-pill px-3 py-2 fs-6">
                                            <i class="fas fa-clock"></i> Belum Diassign sebagai Mentor
                                        </span>
                                    @else
                                        <span class="badge bg-secondary rounded-pill px-3 py-2 fs-6">
                                            <i class="fas fa-user-times"></i> Belum Memiliki Akun
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Daftar Peserta Bimbingan --}}
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Peserta Bimbingan</h5>
                </div>
                <div class="card-body">
                    @if($mentor->pesertas->isEmpty())
                        <div class="alert alert-info text-center">
                            <i class="ti ti-info-circle"></i> Belum ada peserta yang dibimbing.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Peserta</th>
                                        <th>Instansi</th>
                                        <th>Periode Magang</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($mentor->pesertas as $peserta)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                {{ $peserta->nama_lengkap }}
                                                <br><small class="text-muted">{{ $peserta->jurusan ?? '-' }}</small>
                                            </td>
                                            <td>{{ $peserta->asal_instansi }}</td>
                                            <td>
                                                {{ \Carbon\Carbon::parse($peserta->tanggal_mulai_magang)->format('d M Y') }} -
                                                {{ \Carbon\Carbon::parse($peserta->tanggal_selesai_magang)->format('d M Y') }}
                                                <br><small class="text-muted">
                                                    ({{ \Carbon\Carbon::parse($peserta->tanggal_mulai_magang)->diffInDays($peserta->tanggal_selesai_magang) + 1 }} hari)
                                                </small>
                                            </td>
                                            <td class="text-orange-900">
                                                @php
                                                    $progressPercentage = 0;
                                                    $bisaLaporanAkhir = false;

                                                    if($peserta->target_waktu_tugas > 0) {
                                                        $progressPercentage = round(($peserta->waktu_tugas_tercapai / $peserta->target_waktu_tugas) * 100, 2);
                                                        $bisaLaporanAkhir = $peserta->waktu_tugas_tercapai >= $peserta->target_waktu_tugas;
                                                    }
                                                @endphp

                                                @if($bisaLaporanAkhir)
                                                    <span class="bg-green-400 text-white p-2 rounded-3">Siap Laporan Akhir</span>
                                                @else
                                                    <span class="bg-yellow-400 p-2 rounded-3">Proses ({{ $progressPercentage }}%)</span>
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

        {{-- Foto & Aksi --}}
        <div class="col-xl-4 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Foto Mentor</h5>
                </div>
                <div class="card-body text-center">
                    @if($mentor->foto)
                        <img src="{{ asset('storage/foto_mentor/'.$mentor->foto) }}"
                             alt="Foto {{ $mentor->nama_mentor }}"
                             class="img-fluid rounded mb-3"
                             style="max-height: 300px;">
                    @else
                        <div class="mb-3">
                            <i class="ti ti-user text-muted" style="font-size: 5rem;"></i>
                        </div>
                        <p class="text-muted">Foto tidak tersedia</p>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Aksi</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('mentor.edit', $mentor->id) }}" class="btn btn-warning">
                            <i class="ti ti-edit"></i> Edit Mentor
                        </a>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">
                            <i class="ti ti-trash"></i> Hapus Mentor
                        </button>
                    </div>
                </div>
            </div>

            {{-- Statistik Singkat --}}
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Statistik</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <h4 class="mb-1">{{ $mentor->pesertas->count() }}</h4>
                            <p class="text-muted mb-0">Total Peserta</p>
                        </div>
                        <div class="col-6">
                            @php
                                $selesaiCount = 0;
                                foreach($mentor->pesertas as $peserta) {
                                    if($peserta->target_waktu_tugas > 0 && $peserta->waktu_tugas_tercapai >= $peserta->target_waktu_tugas) {
                                        $selesaiCount++;
                                    }
                                }
                            @endphp
                            <h4 class="mb-1">{{ $selesaiCount }}</h4>
                            <p class="text-muted mb-0">Selesai</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Konfirmasi Penghapusan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus data mentor <strong>{{ $mentor->nama_mentor }}</strong>?</p>
                <p class="text-muted">Data akan terhapus secara permanen dan tidak dapat dikembalikan.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form action="{{ route('mentor.destroy', $mentor->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
