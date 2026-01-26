{{-- resources/views/repository/create.blade.php --}}
@extends('layouts.mantis')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            {{-- Header Card --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body bg-blue-200  p-4">
                    <h3 class="mb-2">
                        <i class="fas fa-plus-circle me-2"></i>Tambah Repository Baru
                    </h3>
                    <p class="mb-0 opacity-75">Publikasikan laporan dari sistem atau upload manual dari penyimpanan lokal</p>
                </div>
            </div>

            {{-- Form Card --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('repository.store') }}" enctype="multipart/form-data">
                        @csrf

                        {{-- Mode Selection --}}
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-cog me-1"></i>Pilih Metode Input <span class="text-danger">*</span>
                            </label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="input_mode" id="mode_from_system" value="system" checked onchange="toggleInputMode()">
                                <label class="btn btn-outline-primary" for="mode_from_system">
                                    <i class="fas fa-database me-2"></i>Dari Laporan Akhir di Sistem
                                </label>

                                <input type="radio" class="btn-check" name="input_mode" id="mode_manual" value="manual" onchange="toggleInputMode()">
                                <label class="btn btn-outline-success" for="mode_manual">
                                    <i class="fas fa-upload me-2"></i>Upload Manual dari Lokal
                                </label>
                            </div>
                            <p class="form-text text-muted d-block mt-2">
                                <strong>Dari Sistem:</strong> Pilih laporan yang sudah ada di database.
                                <strong>Upload Manual:</strong> Untuk arsip laporan lama atau dokumen eksternal.
                            </p>
                        </div>
                        <hr class="my-4">

                        {{-- FROM SYSTEM MODE --}}
                        <div id="systemModeSection">
                            @if($laporanAkhirs->isEmpty())
                            <div class="alert alert-warning" role="alert">
                                <h6 class="alert-heading">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Tidak Ada Laporan Akhir yang Tersedia
                                </h6>
                                <p class="mb-0 medium">
                                    Semua laporan akhir yang sudah di-ACC telah dipublikasikan ke repository.
                                    Gunakan mode "Upload Manual" untuk menambahkan laporan dari luar sistem.
                                </p>
                            </div>
                            @else
                            {{-- Pilih Laporan Akhir --}}
                            <div class="mb-4">
                                <label for="laporan_akhir_id" class="form-label">
                                    <i class="fas fa-file-alt me-1"></i>Pilih Laporan Akhir <span class="text-danger">*</span>
                                </label>
                                <select name="laporan_akhir_id"
                                        id="laporan_akhir_id"
                                        class="form-select @error('laporan_akhir_id') is-invalid @enderror"
                                        onchange="updateFormFromLaporan()">
                                    <option value="">-- Pilih Laporan Akhir --</option>
                                    @foreach($laporanAkhirs as $laporan)
                                        <option value="{{ $laporan->id }}"
                                                data-judul="{{ $laporan->judul_laporan }}"
                                                data-deskripsi="{{ $laporan->deskripsi_laporan }}"
                                                data-peserta="{{ $laporan->peserta->nama_lengkap ?? ($laporan->peserta->user->name ?? 'N/A') }}"
                                                data-bagian="{{ $laporan->peserta->bagian->nama_bagian ?? '' }}"
                                                {{ old('laporan_akhir_id', $selectedLaporanId) == $laporan->id ? 'selected' : '' }}>
                                            {{ $laporan->judul_laporan }} - {{ $laporan->peserta->nama_lengkap ?? ($laporan->peserta->user->name ?? 'N/A') }} ({{ $laporan->created_at->format('Y') }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('laporan_akhir_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Pilih laporan akhir yang sudah di-ACC untuk dipublikasikan ke repository
                                </small>
                            </div>

                            {{-- Preview Laporan --}}
                            <div id="laporanPreview" class="alert alert-info border-start border-info border-4 mb-4" style="display: none;">
                                <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Preview Laporan</h6>
                                <div id="previewContent"></div>
                            </div>
                            @endif
                        </div>

                        {{-- MANUAL MODE --}}
                        <div id="manualModeSection" style="display: none;">
                            <div class="alert alert-success border-start border-success border-4 mb-4">
                                <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Mode Upload Manual</h6>
                                <p class="mb-0 small">
                                    Isi semua informasi di bawah ini dan upload file PDF laporan dari penyimpanan lokal Anda.
                                    Mode ini cocok untuk mengarsipkan laporan dari tahun-tahun sebelumnya.
                                </p>
                            </div>

                            {{-- Upload File PDF --}}
                            <div class="mb-4">
                                <label for="file_laporan_manual" class="form-label">
                                    <i class="fas fa-file-pdf me-1"></i>File Laporan (PDF) <span class="text-danger">*</span>
                                </label>
                                <input type="file"
                                       name="file_laporan_manual"
                                       id="file_laporan_manual"
                                       class="form-control @error('file_laporan_manual') is-invalid @enderror"
                                       accept=".pdf">
                                @error('file_laporan_manual')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Upload file PDF laporan akhir (Maksimal 10MB)
                                </small>
                            </div>

                            {{-- Nama Peserta Manual --}}
                            <div class="mb-4">
                                <label for="nama_peserta_manual" class="form-label">
                                    <i class="fas fa-user me-1"></i>Nama Peserta <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       name="nama_peserta_manual"
                                       id="nama_peserta_manual"
                                       class="form-control @error('nama_peserta_manual') is-invalid @enderror"
                                       value="{{ old('nama_peserta_manual') }}"
                                       placeholder="Masukkan nama lengkap peserta magang">
                                @error('nama_peserta_manual')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Judul Repository --}}
                        <div class="mb-4">
                            <label for="judul" class="form-label">
                                <i class="fas fa-heading me-1"></i>Judul Repository <span class="text-danger" id="judulRequired">*</span>
                            </label>
                            <input type="text"
                                   name="judul"
                                   id="judul"
                                   class="form-control @error('judul') is-invalid @enderror"
                                   value="{{ old('judul') }}"
                                   placeholder="Masukkan judul repository">
                            @error('judul')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted" id="judulHelp">
                                Jika mode sistem: Kosongkan untuk menggunakan judul dari laporan akhir
                            </small>
                        </div>

                        {{-- Deskripsi Singkat --}}
                        <div class="mb-4">
                            <label for="deskripsi" class="form-label">
                                <i class="fas fa-align-left me-1"></i>Deskripsi Singkat <span class="text-danger">*</span>
                            </label>
                            <textarea name="deskripsi"
                                      id="deskripsi"
                                      rows="3"
                                      class="form-control @error('deskripsi') is-invalid @enderror"
                                      required
                                      placeholder="Masukkan deskripsi singkat yang akan ditampilkan di halaman utama (diisi manual oleh admin)">{{ old('deskripsi') }}</textarea>
                            @error('deskripsi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Deskripsi singkat ini diisi manual oleh admin untuk tampilan di halaman utama
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
                                      class="form-control @error('deskripsi_lengkap') is-invalid @enderror"
                                      placeholder="Otomatis terisi dari deskripsi laporan akhir (bisa diedit)">{{ old('deskripsi_lengkap') }}</textarea>
                            @error('deskripsi_lengkap')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Deskripsi detail yang otomatis terisi dari laporan akhir, dapat diedit jika diperlukan
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
                                       value="{{ old('tahun_magang', date('Y')) }}"
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
                                        <option value="{{ $bagian->nama_bagian }}" {{ old('bagian') == $bagian->nama_bagian ? 'selected' : '' }}>
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
                                        <option value="{{ $category }}" {{ old('kategori') == $category ? 'selected' : '' }}>
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
                            <div class="form-check form-switch">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="is_published"
                                       id="is_published"
                                       value="1"
                                       {{ old('is_published', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_published">
                                    <i class="fas fa-eye me-1"></i>Publikasikan langsung ke repository
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Jika tidak dicentang, repository akan disimpan sebagai draft
                            </small>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="d-flex gap-2 pt-3 border-top">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Simpan Repository
                            </button>
                            <a href="{{ route('repository.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
</style>

<script>
    // Toggle between system and manual mode
    function toggleInputMode() {
        const mode = document.querySelector('input[name="input_mode"]:checked').value;
        const systemSection = document.getElementById('systemModeSection');
        const manualSection = document.getElementById('manualModeSection');
        const laporanSelect = document.getElementById('laporan_akhir_id');
        const fileInput = document.getElementById('file_laporan_manual');
        const namaPesertaInput = document.getElementById('nama_peserta_manual');
        const judulInput = document.getElementById('judul');
        const judulRequired = document.getElementById('judulRequired');
        const judulHelp = document.getElementById('judulHelp');

        if (mode === 'system') {
            systemSection.style.display = 'block';
            manualSection.style.display = 'none';

            // Set required attributes
            if (laporanSelect) laporanSelect.setAttribute('required', 'required');
            fileInput.removeAttribute('required');
            namaPesertaInput.removeAttribute('required');
            judulInput.removeAttribute('required');

            // Update judul label
            judulRequired.style.display = 'none';
            judulHelp.textContent = 'Jika mode sistem: Kosongkan untuk menggunakan judul dari laporan akhir';
            judulInput.placeholder = 'Kosongkan untuk menggunakan judul dari laporan akhir';
        } else {
            systemSection.style.display = 'none';
            manualSection.style.display = 'block';

            // Set required attributes
            if (laporanSelect) laporanSelect.removeAttribute('required');
            fileInput.setAttribute('required', 'required');
            namaPesertaInput.setAttribute('required', 'required');
            judulInput.setAttribute('required', 'required');

            // Update judul label
            judulRequired.style.display = 'inline';
            judulHelp.textContent = 'Masukkan judul untuk repository yang akan ditampilkan';
            judulInput.placeholder = 'Masukkan judul repository';
        }
    }

    // Auto-fill form ketika laporan dipilih
    function updateFormFromLaporan() {
        const select = document.getElementById('laporan_akhir_id');
        const selectedOption = select.options[select.selectedIndex];
        const preview = document.getElementById('laporanPreview');
        const previewContent = document.getElementById('previewContent');

        if (selectedOption.value) {
            // Update form fields - deskripsi_laporan dipindah ke deskripsi_lengkap
            document.getElementById('deskripsi_lengkap').value = selectedOption.dataset.deskripsi;
            document.getElementById('bagian').value = selectedOption.dataset.bagian;

            // Kosongkan deskripsi singkat agar admin mengisi manual
            document.getElementById('deskripsi').value = '';

            // Show preview
            previewContent.innerHTML = `
                <p class="mb-1"><strong>Judul:</strong> ${selectedOption.dataset.judul}</p>
                <p class="mb-1"><strong>Peserta:</strong> ${selectedOption.dataset.peserta}</p>
                <p class="mb-0"><strong>Bagian:</strong> ${selectedOption.dataset.bagian || '-'}</p>
            `;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }
    }

    // Auto-trigger jika ada selected laporan
    document.addEventListener('DOMContentLoaded', function() {
        const select = document.getElementById('laporan_akhir_id');
        if (select && select.value) {
            updateFormFromLaporan();
        }

        // Initialize mode on page load
        toggleInputMode();
    });
</script>
@endsection
