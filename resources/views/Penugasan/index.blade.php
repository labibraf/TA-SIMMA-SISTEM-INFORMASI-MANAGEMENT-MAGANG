{{-- resources/views/penugasan/index.blade.php --}}
@extends('layouts.mantis')

@section('content')
<div class="">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="text-center mb-0">Daftar Penugasan</h2>
            @if(Auth::user() && Auth::user()->isMentor())
                <a href="{{ route('penugasans.create') }}" class="btn btn-primary">
                    (+) Tambah Penugasan
                </a>
            @endif

            @if(session('success'))
                <div class="alert alert-success mt-2">
                    {{ session('success') }}
                </div>
            @endif
        </div>

        <div class="card-body">
            @if($penugasans->isEmpty())
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle"></i> Belum ada data penugasan.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="tabel">
                        <thead class="table-dark">
                            @if(Auth::user()->isPeserta())
                                {{-- Header untuk Peserta: nomor, judul, deadline, beban waktu, ditugaskan, status --}}
                                <tr>
                                    <th>No</th>
                                    <th>Judul</th>
                                    <th>Deadline</th>
                                    <th>Beban Waktu</th>
                                    <th>Ditugaskan</th>
                                    <th>Status</th>
                                </tr>
                            @else
                                {{-- Header untuk Mentor/Admin: no, nomor identitas, nama, judul, deadline, beban waktu, ditugaskan, opsi --}}
                                <tr>
                                    <th>No</th>
                                    <th>Nomor Identitas</th>
                                    <th>Nama</th>
                                    <th>Judul</th>
                                    <th>Deadline</th>
                                    <th>Beban Waktu</th>
                                    <th>Ditugaskan</th>
                                    <th>Opsi</th>
                                </tr>
                            @endif
                        </thead>
                        <tbody>
                            @foreach($penugasans as $index => $item)
                                @if(Auth::user()->isPeserta())
                                    {{-- Tampilan untuk Peserta: nomor, judul, deadline, beban waktu, ditugaskan, status --}}
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <a href="{{ route('penugasans.show', $item->id) }}" class="text-decoration-none judul-tugas">
                                                {{ $item->judul_tugas }}
                                            </a>

                                            @if($item->isGugur)
                                                <br><span class="badge bg-danger mt-1"><i class="ti ti-alarm me-1"></i> Terlambat</span>
                                            @elseif($item->is_approved == 1)
                                                <br><span class="badge bg-success mt-1"><i class="fas fa-check me-1"></i> Selesai</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->deadline ? $item->deadline->format('d M Y') : '-' }}</td>
                                        <td>{{ $item->beban_waktu ?? '-' }} Jam</td>
                                        <td>{{ $item->kategori }}</td>
                                        <td>
                                            @php
                                                // Hitung progress berdasarkan kategori penugasan
                                                if ($item->kategori === 'Divisi') {
                                                    $progress = $item->laporanHarian->max('progres_tugas') ?? 0;
                                                } else {
                                                    $latestLaporan = $item->laporanHarian->last();
                                                    $progress = $latestLaporan ? $latestLaporan->progres_tugas : 0;
                                                }
                                            @endphp

                                            @if($item->isGugur)
                                                <span class="badge bg-danger">Terlambat</span>
                                            @elseif($item->is_approved == 1 || $progress >= 100)
                                                <span class="badge bg-success">Selesai</span>
                                            @elseif($progress > 1 && $progress < 100)
                                                <span class="badge bg-gray-300 text-dark">Dikerjakan</span>
                                            @else
                                                <span class="badge text-blue-800">Belum Dimulai</span>
                                            @endif
                                        </td>
                                    </tr>
                                @else
                                    {{-- Tampilan untuk Mentor/Admin: no, nomor identitas, nama, judul, deadline, beban waktu, ditugaskan, opsi --}}
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            @if($item->peserta)
                                                {{ $item->peserta->nomor_identitas }}
                                            @elseif($item->kategori === 'Divisi' && $item->bagian)
                                                -
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->peserta)
                                                {{ $item->peserta->nama_lengkap }}
                                            @elseif($item->kategori === 'Divisi' && $item->bagian)
                                                Divisi {{ $item->bagian->nama_bagian }}
                                            @else
                                                -
                                            @endif

                                            @if($item->isGugur)
                                                <br><span class="badge bg-danger mt-1">
                                                    <i class="ti ti-alarm me-1"></i>Terlambat
                                                </span>
                                            @elseif($item->is_approved == 1)
                                                <br><span class="badge bg-success mt-1">
                                                    <i class="fas fa-check me-1"></i>Selesai
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('penugasans.show', $item->id) }}" class="text-decoration-none judul-tugas">
                                                {{ $item->judul_tugas }}
                                            </a>
                                        </td>
                                        <td>
                                            {{ $item->deadline ? $item->deadline->format('d M Y') : '-' }}
                                        </td>
                                        <td>{{ $item->beban_waktu ?? '-' }} Jam</td>
                                        <td>{{ $item->kategori }}</td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('penugasans.show', $item->id) }}" class="btn btn-info btn-sm" title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if($item->is_approved == 1)
                                                    {{-- Approved: Terkunci, tidak bisa edit/hapus --}}
                                                    <span class="btn btn-secondary btn-sm disabled" title="Tugas sudah di-approve, tidak dapat diedit">
                                                        <i class="fas fa-edit"></i>
                                                    </span>
                                                    <span class="btn btn-secondary btn-sm disabled" title="Tugas sudah di-approve, tidak dapat dihapus">
                                                        <i class="fas fa-trash"></i>
                                                    </span>
                                                @else
                                                    {{-- Belum Approved: Bisa edit dan hapus --}}
                                                    <a href="{{ route('penugasans.edit', $item->id) }}" class="btn btn-warning btn-sm" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-danger btn-sm"
                                                            data-bs-toggle="modal" data-bs-target="#confirmDeleteModal{{ $item->id }}"
                                                            title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endif

                                {{-- Modal Konfirmasi Hapus (hanya untuk mentor/admin) --}}
                                @if(!Auth::user()->isPeserta())
                                    <div class="modal fade" id="confirmDeleteModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-red-600">
                                                    <h5 class="modal-title text-white">Konfirmasi Penghapusan</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Apakah Anda yakin ingin menghapus penugasan <strong>{{ $item->judul_tugas }}</strong>?</p>
                                                    <p class="text-muted">Data akan terhapus secara permanen.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <form action="{{ route('penugasans.destroy', $item->id) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger">Hapus</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
<style>
    .judul-tugas {
        color: #240bc4 !important;
        transition: color 0.3s ease;
    }

    .judul-tugas:hover {
        color: #242628 !important;
        text-decoration: underline !important;
    }
</style>
@endsection
