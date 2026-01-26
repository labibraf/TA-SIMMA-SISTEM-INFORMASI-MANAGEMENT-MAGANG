@extends('layouts.mantis')

@section('content')
<div class="card">
    <div>
        <a href="{{ route('penugasans.show', $laporanHarian->penugasan_id) }}" class="btn btn-secondary float-start mt-3 mr-2 text-center">
            <i class="ti ti-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-header">
        <h2 class="text-left">Edit Laporan Harian</h2>
    </div>
    <div class="card-body">
        <form action="{{ route('laporan_harian.update', $laporanHarian->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-group mb-3">
                <label for="tanggal_laporan">Tanggal Laporan</label>
                <input type="date" name="tanggal_laporan" id="tanggal_laporan"
                    class="form-control @error('tanggal_laporan') is-invalid @enderror"
                    value="{{ old('tanggal_laporan', $laporanHarian->tanggal_laporan) }}" required autofocus autocomplete="off">
                @error('tanggal_laporan')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
                @if($laporanHarian->tanggal_laporan)
                    <small class="text-muted">Tanggal laporan sebelumnya: {{ \Carbon\Carbon::parse($laporanHarian->tanggal_laporan)->format('d M Y') }}</small>
                @endif
            </div>

            <div class="form-group mb-3">
                <label for="penugasan_id">Judul Tugas</label>
                <div class="form-control-plaintext border p-2 bg-light">
                    <strong>{{ $laporanHarian->penugasan->judul_tugas }}</strong>
                    @if($laporanHarian->penugasan->kategori === 'Divisi')
                        <span class="badge bg-info ms-2">Divisi</span>
                    @else
                        <span class="badge bg-primary ms-2">Individu</span>
                    @endif
                </div>
                <small class="text-muted">Tugas tidak dapat diubah saat mengedit laporan</small>
            </div>

            <div class="form-group mb-3">
                <label for="deskripsi_kegiatan">Deskripsi Kegiatan</label>
                <textarea name="deskripsi_kegiatan" id="deskripsi_kegiatan" cols="30" rows="5"
                          class="form-control @error('deskripsi_kegiatan') is-invalid @enderror"
                          required autofocus autocomplete="off">{{ old('deskripsi_kegiatan', $laporanHarian->deskripsi_kegiatan) }}</textarea>
                @error('deskripsi_kegiatan')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group mb-3">
                <label for="progres_tugas">Progres Tugas</label>
                @if($isLatestReport)
                    {{-- Laporan terbaru: progress bisa diubah --}}
                    <div class="input-group">
                        <input type="number" name="progres_tugas" id="progres_tugas"
                            class="form-control @error('progres_tugas') is-invalid @enderror"
                            value="{{ old('progres_tugas', $laporanHarian->progres_tugas) }}"
                            required autofocus autocomplete="off" min="{{ $laporanHarian->progres_tugas }}" max="100">
                        <span class="input-group-text">%</span>
                    </div>
                    @error('progres_tugas')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                    <small class="text-muted">Progress saat ini: {{ $laporanHarian->progres_tugas }}% (Laporan terbaru, bisa diubah)</small>
                @else
                    {{-- Laporan lama: progress tidak bisa diubah, hanya tampil --}}
                    <div class="input-group">
                        <input type="number" class="form-control bg-light" value="{{ $laporanHarian->progres_tugas }}" disabled readonly>
                        <span class="input-group-text">%</span>
                    </div>
                    <small class="text-warning">
                        <i class="ti ti-lock me-1"></i>Progress tidak dapat diubah karena ini bukan laporan terbaru
                    </small>
                @endif
            </div>

            <div class="form-group mb-3">
                <label for="file">File</label>
                <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror" autofocus>
                @error('file')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
                @if($laporanHarian->file)
                    <div class="mt-2">
                        <a href="{{ asset('storage/' . $laporanHarian->file) }}" target="_blank" class="text-info">
                            <i class="ti ti-file"></i> File saat ini
                        </a>
                    </div>
                    <small class="text-muted">Kosongkan jika tidak ingin mengganti file</small>
                @endif
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('penugasans.show', $laporanHarian->penugasan_id) }}" class="btn btn-secondary">
                    <i class="ti ti-x me-1"></i> Batal
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i> Perbarui Laporan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
