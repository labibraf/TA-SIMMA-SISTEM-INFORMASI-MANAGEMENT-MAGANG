@extends('layouts.mantis')

@section('content')
<div class="card">
    <div>
        <a href="{{ route('laporan-akhir.index') }}" class="btn btn-secondary float-start mt-3 mr-2 text-center">
            <i class="ti ti-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-header">
        <h2 class="text-left">Edit Laporan Akhir</h2>
    </div>
    <div class="card-body">
        <form action="{{ route('laporan-akhir.update', $laporanAkhir->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-group mb-3">
                <label for="judul_laporan">Judul Laporan</label>
                <input type="text" name="judul_laporan" id="judul_laporan"
                    class="form-control @error('judul_laporan') is-invalid @enderror"
                    value="{{ old('judul_laporan', $laporanAkhir->judul_laporan) }}" required autofocus autocomplete="off">
                @error('judul_laporan')
                    <small class="text-danger">
                        {{ $message }}
                    </small>
                @enderror
            </div>

            <div class="form-group mb-3">
                <label for="deskripsi_repository">Deskripsi Singkat Repository</label>
                <small class="text-muted d-block mb-1">Ringkasan singkat (maksimal 1000 karakter) yang akan ditampilkan di halaman repository</small>
                <textarea name="deskripsi_repository" id="deskripsi_repository" cols="30" rows="3"
                    class="form-control @error('deskripsi_repository') is-invalid @enderror"
                    maxlength="1000"
                    placeholder="Contoh: Laporan ini membahas tentang implementasi sistem informasi...">{{ old('deskripsi_repository', $laporanAkhir->deskripsi_repository) }}</textarea>
                @error('deskripsi_repository')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
                <div class="form-text text-muted">
                    <span id="char_count">0</span>/1000 karakter
                </div>
            </div>
            <div class="form-group mb-3">
                <label for="deskripsi_laporan">Deskripsi Laporan <span class="text-danger">*</span></label>
                <small class="text-muted d-block mb-1">Deskripsi lengkap mengenai laporan akhir Anda</small>
                <textarea name="deskripsi_laporan" id="deskripsi_laporan" cols="30" rows="5"
                    class="form-control @error('deskripsi_laporan') is-invalid @enderror" required
                    autofocus autocomplete="off">{{ old('deskripsi_laporan', $laporanAkhir->deskripsi_laporan) }}</textarea>
                @error('deskripsi_laporan')
                    <small class="text-danger">
                        {{ $message }}
                    </small>
                @enderror
            </div>

            <div class="form-group mb-3">
                <label for="file_path">Upload File</label>
                <input type="file" name="file_path" id="file_path"
                    class="form-control @error('file_path') is-invalid @enderror" autofocus>
                @error('file_path')
                    <small class="text-danger">
                        {{ $message }}
                    </small>
                @enderror
                @if($laporanAkhir->file_path)
                    <div class="mt-2">
                        <a href="{{ Storage::url($laporanAkhir->file_path) }}" target="_blank" class="text-info">
                            <i class="ti ti-file-pdf"></i> File saat ini
                        </a>
                    </div>
                @endif
                <div class="form-text text-muted">
                    Format: PDF, DOC, DOCX. Maksimal 2MB. Kosongkan jika tidak ingin mengganti file.
                </div>
            </div>

            <hr class="my-4">
            <h5 class="mb-3">
                <i class="ti ti-database me-2"></i>Informasi untuk Repository
            </h5>
            <div class="alert alert-info">
                <i class="ti ti-info-circle me-2"></i>
                <small>Field di bawah ini akan digunakan saat laporan akhir Anda disetujui dan masuk ke repository sistem. Isi dengan lengkap untuk memudahkan proses publikasi.</small>
            </div>

            <div class="form-group mb-3">
                <label for="kategori_repository">Kategori Repository</label>
                <select name="kategori_repository" id="kategori_repository"
                    class="form-control @error('kategori_repository') is-invalid @enderror">
                    <option value="">-- Pilih Kategori (Opsional) --</option>
                    <option value="Teknologi Informasi" {{ old('kategori_repository', $laporanAkhir->kategori_repository) == 'Teknologi Informasi' ? 'selected' : '' }}>Teknologi Informasi</option>
                    <option value="Sistem & Infrastruktur" {{ old('kategori_repository', $laporanAkhir->kategori_repository) == 'Sistem & Infrastruktur' ? 'selected' : '' }}>Sistem & Infrastruktur</option>
                    <option value="Keamanan & Privasi" {{ old('kategori_repository', $laporanAkhir->kategori_repository) == 'Keamanan & Privasi' ? 'selected' : '' }}>Keamanan & Privasi</option>
                    <option value="Inovasi & Riset" {{ old('kategori_repository', $laporanAkhir->kategori_repository) == 'Inovasi & Riset' ? 'selected' : '' }}>Inovasi & Riset</option>
                    <option value="Lainnya" {{ old('kategori_repository', $laporanAkhir->kategori_repository) == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                </select>
                @error('kategori_repository')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
                <div class="form-text text-muted">
                    Kategori untuk memudahkan pencarian di repository
                </div>
            </div>
            <hr class="my-4">

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('laporan-akhir.index') }}" class="btn btn-secondary">
                    <i class="ti ti-x me-1"></i> Batal
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i> Perbarui Laporan Akhir
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Counter karakter untuk deskripsi repository
    document.getElementById('deskripsi_repository').addEventListener('input', function() {
        const charCount = this.value.length;
        document.getElementById('char_count').textContent = charCount;
    });

    // Update counter on page load
    window.addEventListener('DOMContentLoaded', function() {
        const textarea = document.getElementById('deskripsi_repository');
        if (textarea.value) {
            document.getElementById('char_count').textContent = textarea.value.length;
        }
    });
</script>
@endsection
