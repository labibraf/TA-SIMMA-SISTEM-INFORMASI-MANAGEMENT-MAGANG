@extends('layouts.mantis')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Edit Penugasan</h2>
        <a href="{{ route('penugasans.index') }}" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Kembali
        </a>
    </div>

    @if($penugasan->status_tugas === 'Selesai' && $penugasan->is_approved == 1)
        <div class="alert alert-danger">
            <i class="fas fa-lock"></i>
            <strong>Tugas Telah Di-Approve</strong><br>
            Tugas ini sudah selesai dan telah mendapat approval. Tidak dapat diedit lagi.
            <br><a href="{{ route('penugasans.show', $penugasan->id) }}" class="btn btn-secondary btn-sm mt-2">
                <i class="fas fa-arrow-left"></i> Kembali ke Detail Tugas
            </a>
        </div>
    @elseif($isGugur)
        <div class="alert alert-danger">
            <i class="fas fa-info-circle"></i>
            <strong>Info</strong><br>
            Tugas ini Terlambat. Pastikan untuk cek kembali sebelum melakukan perubahan.
        </div>
    @elseif($penugasan->status_tugas === 'Selesai')
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Perhatian</strong><br>
            Tugas ini sudah selesai tapi belum di-approve. Edit dengan hati-hati karena dapat mempengaruhi laporan harian yang sudah ada.
        </div>
    @endif

    <form action="{{ route('penugasans.update', $penugasan->id) }}" method="POST" enctype="multipart/form-data"
          @if($penugasan->status_tugas === 'Selesai' && $penugasan->is_approved == 1) style="display: none;" @endif>
        @csrf
        @method('PUT')

        <!-- Detail Tugas Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Detail Tugas</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="judul_tugas" class="form-label fw-bold">Judul Tugas</label>
                            <input type="text" name="judul_tugas" id="judul_tugas"
                                class="form-control @error('judul_tugas') is-invalid @enderror"
                                value="{{ old('judul_tugas', $penugasan->judul_tugas) }}" required autofocus>
                            @error('judul_tugas')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="kategori" class="form-label fw-bold">Ditugaskan</label>
                            <select name="kategori" id="kategori"
                                class="form-select @error('kategori') is-invalid @enderror" required>
                                <option value="">-- Pilih Kategori --</option>
                                <option value="Individu" {{ old('kategori', $penugasan->kategori) == 'Individu' ? 'selected' : '' }}>Individu</option>
                                <option value="Divisi" {{ old('kategori', $penugasan->kategori) == 'Divisi' ? 'selected' : '' }}>Divisi</option>
                            </select>
                            @error('kategori')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label for="deskripsi_tugas" class="form-label fw-bold">Deskripsi Tugas</label>
                    <textarea name="deskripsi_tugas" id="deskripsi_tugas" rows="4"
                        class="form-control @error('deskripsi_tugas') is-invalid @enderror" required>{{ old('deskripsi_tugas', $penugasan->deskripsi_tugas) }}</textarea>
                    @error('deskripsi_tugas')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="deadline" class="form-label fw-bold">Deadline</label>
                            <input type="date" name="deadline" id="deadline"
                                class="form-control @error('deadline') is-invalid @enderror"
                                value="{{ old('deadline', $penugasan->deadline ? \Carbon\Carbon::parse($penugasan->deadline)->format('Y-m-d') : '') }}" required>
                            @error('deadline')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="beban_waktu" class="form-label fw-bold">Beban Waktu (Jam)</label>
                            <input type="number" name="beban_waktu" id="beban_waktu"
                                class="form-control @error('beban_waktu') is-invalid @enderror"
                                value="{{ old('beban_waktu', $penugasan->beban_waktu) }}"
                                min="1" required>
                            @error('beban_waktu')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <div class="form-text text-muted mt-1" id="sisaTargetInfo">
                                Pilih peserta untuk melihat sisa jam kerja
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="file" class="form-label fw-bold">File</label>
                            <input type="file" name="file" id="file"
                                class="form-control @error('file') is-invalid @enderror">
                            @if($penugasan->file)
                                <div class="form-text text-muted mt-1">
                                    File saat ini: <a href="{{ asset('storage/' . $penugasan->file) }}" target="_blank">{{ basename($penugasan->file) }}</a>
                                </div>
                            @endif
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Field untuk pemilihan peserta -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">Pemilihan Peserta</h5>
            </div>
            <div class="card-body">
                <!-- Field untuk Individu -->
                <div class="form-group mb-3" id="peserta-individu-field" style="display:none;">
                    <label for="peserta_id" class="form-label">Nama Peserta</label>
                    <select name="peserta_id" id="peserta_id" class="form-select">
                        <option value="">-- Pilih Peserta --</option>
                        @foreach($pesertas as $item)
                            @php
                                $sisaHari = (int)$item->sisa_hari_kerja;
                                $sisaJam = (int)$item->sisa_jam_kerja;
                                $statusInfo = $sisaHari > 0
                                    ? "Sisa: {$sisaJam} jam / {$sisaHari} hari kerja"
                                    : "Kurang: {$sisaJam} jam (masa berakhir)";
                            @endphp
                            <option value="{{ $item->id }}"
                                    data-target-waktu="{{ $sisaJam }}"
                                    data-sisa-hari="{{ $sisaHari }}"
                                    {{ old('peserta_id', $penugasan->peserta_id) == $item->id ? 'selected' : '' }}>
                                {{ $item->user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Field untuk Divisi -->
                <div class="form-group mb-3" id="peserta-divisi-field" style="display:none;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">Pilih Peserta</label>
                        <div class="form-check">
                            @php
                                $assignedPesertaIds = $penugasan->pesertas->pluck('id')->toArray();
                            @endphp
                            <input class="form-check-input" type="checkbox" id="selectAll" name="select_all" value="1"
                                {{ (old('select_all', (count($assignedPesertaIds) == $pesertas->count() && $penugasan->kategori == 'Divisi') ? '1' : '0')) == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="selectAll">
                                Pilih Semua
                            </label>
                        </div>
                    </div>

                    <div class="row">
                        @foreach($pesertas as $item)
                            @php
                                $sisaHari = (int)$item->sisa_hari_kerja;
                                $sisaJam = (int)$item->sisa_jam_kerja;
                                $statusInfo = $sisaHari > 0
                                    ? "Sisa: {$sisaJam} jam / {$sisaHari} hari kerja"
                                    : "Kurang: {$sisaJam} jam (masa berakhir)";
                                $badgeClass = $sisaHari > 0 ? 'bg-success' : 'bg-warning';
                            @endphp
                            <div class="col-md-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                        name="peserta_ids[]"
                                        value="{{ $item->id }}"
                                        id="peserta_{{ $item->id }}"
                                        data-target-waktu="{{ $sisaJam }}"
                                        data-sisa-hari="{{ $sisaHari }}"
                                        data-nama="{{ $item->user->name }}"
                                        {{ in_array($item->id, old('peserta_ids', $assignedPesertaIds)) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="peserta_{{ $item->id }}">
                                        {{ $item->user->name }}
                                        <span class="badge {{ $badgeClass }} badge-sm">{{ $statusInfo }}</span>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Display Target Waktu untuk peserta yang dipilih -->
                    <div class="card mt-3" id="target-waktu-display" style="display: none;">
                        <div class="card-header">
                            <h6 class="mb-0">Sisa Waktu Peserta yang Dipilih</h6>
                        </div>
                        <div class="card-body p-2">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Nama Peserta</th>
                                            <th>Sisa Jam Kerja</th>
                                            <th>Sisa Hari Kerja</th>
                                        </tr>
                                    </thead>
                                    <tbody id="target-waktu-body">
                                        <!-- Content will be populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="{{ route('penugasans.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Update Penugasan</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ambil elemen DOM
    const kategoriSelect = document.getElementById('kategori');
    const pesertaIndividuField = document.getElementById('peserta-individu-field');
    const pesertaDivisiField = document.getElementById('peserta-divisi-field');
    const pesertaSelect = document.getElementById('peserta_id');
    const selectAllCheckbox = document.getElementById('selectAll');
    const pesertaCheckboxes = document.querySelectorAll('input[name="peserta_ids[]"]');
    const bebanWaktuInput = document.getElementById('beban_waktu');
    const sisaTargetInfo = document.getElementById('sisaTargetInfo');
    const targetWaktuDisplay = document.getElementById('target-waktu-display');
    const targetWaktuBody = document.getElementById('target-waktu-body');

    // Fungsi untuk menampilkan field sesuai kategori
    function togglePesertaFields() {
        if (kategoriSelect.value === 'Individu') {
            pesertaIndividuField.style.display = 'block';
            pesertaDivisiField.style.display = 'none';
            updateBatasWaktuValidation();
        } else if (kategoriSelect.value === 'Divisi') {
            pesertaIndividuField.style.display = 'none';
            pesertaDivisiField.style.display = 'block';
            updateTargetWaktuDisplay();
            updateBatasWaktuValidation();
        } else {
            pesertaIndividuField.style.display = 'none';
            pesertaDivisiField.style.display = 'none';
            clearBatasWaktuValidation();
        }
    }

    // Fungsi untuk clear validasi batas waktu
    function clearBatasWaktuValidation() {
        targetWaktuDisplay.style.display = 'none';
        targetWaktuBody.innerHTML = '';
        sisaTargetInfo.textContent = 'Pilih peserta untuk melihat sisa jam kerja';
        sisaTargetInfo.className = 'form-text text-muted mt-1';
        if (bebanWaktuInput) {
            bebanWaktuInput.removeAttribute('max');
        }
    }

    // Fungsi untuk update display target waktu (untuk divisi)
    function updateTargetWaktuDisplay() {
        targetWaktuBody.innerHTML = '';
        const checkedCheckboxes = document.querySelectorAll('input[name="peserta_ids[]"]:checked');

        if (checkedCheckboxes.length > 0) {
            checkedCheckboxes.forEach(checkbox => {
                // Ambil data langsung dari attribute
                const pesertaName = checkbox.dataset.nama || document.querySelector(`label[for="${checkbox.id}"]`).textContent.split(' (Sisa:')[0];
                const sisaJam = checkbox.dataset.targetWaktu || '0';
                const sisaHari = checkbox.dataset.sisaHari || '0';

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${pesertaName}</td>
                    <td>${sisaJam} jam</td>
                    <td>${sisaHari} hari</td>
                `;
                targetWaktuBody.appendChild(row);
            });
            targetWaktuDisplay.style.display = 'block';
        } else {
            targetWaktuDisplay.style.display = 'none';
        }
    }

    // Fungsi untuk apply batas waktu limit
    function applyBatasWaktuLimit(maxValue, message) {
        if (!bebanWaktuInput) return;

        bebanWaktuInput.setAttribute('max', maxValue);
        sisaTargetInfo.textContent = message;

        if (maxValue <= 0) {
            sisaTargetInfo.className = 'form-text text-danger mt-1';
        } else {
            sisaTargetInfo.className = 'form-text text-success mt-1';
        }
    }

    // Fungsi untuk validasi batas waktu
    function updateBatasWaktuValidation() {
        if (!bebanWaktuInput) return;

        // Reset info dan style
        sisaTargetInfo.textContent = 'Pilih peserta untuk melihat sisa jam kerja';
        sisaTargetInfo.className = 'form-text text-muted mt-1';
        bebanWaktuInput.removeAttribute('max');

        // Untuk penugasan individu
        if (kategoriSelect.value === 'Individu' && pesertaSelect.value) {
            const selectedOption = pesertaSelect.querySelector(`option[value="${pesertaSelect.value}"]`);
            if (selectedOption) {
                const sisaJamKerja = parseInt(selectedOption.dataset.targetWaktu) || 0;
                applyBatasWaktuLimit(sisaJamKerja, `Sisa jam kerja peserta: ${sisaJamKerja} jam`);
            }
        }
        // Untuk tugas divisi
        else if (kategoriSelect.value === 'Divisi') {
            const checkedCheckboxes = document.querySelectorAll('input[name="peserta_ids[]"]:checked');
            if (checkedCheckboxes.length > 0) {
                let maxSisaWaktu = 0;

                checkedCheckboxes.forEach(checkbox => {
                    const sisaWaktu = parseInt(checkbox.dataset.targetWaktu) || 0;
                    if (sisaWaktu > maxSisaWaktu) {
                        maxSisaWaktu = sisaWaktu;
                    }
                });

                if (maxSisaWaktu > 0) {
                    applyBatasWaktuLimit(maxSisaWaktu, `Sisa jam kerja terbesar dari peserta yang dipilih: ${maxSisaWaktu} jam`);
                } else {
                    sisaTargetInfo.textContent = 'Perhatian: Semua peserta tidak memiliki sisa jam kerja';
                    sisaTargetInfo.className = 'form-text text-danger mt-1';
                }
            }
        }
    }

    // Jalankan saat halaman load
    togglePesertaFields();

    // Event listeners
    kategoriSelect.addEventListener('change', togglePesertaFields);

    if (pesertaSelect) {
        pesertaSelect.addEventListener('change', updateBatasWaktuValidation);
    }

    // Fungsi untuk pilih semua
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            pesertaCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateTargetWaktuDisplay();
            updateBatasWaktuValidation();
        });
    }

    // Fungsi untuk uncheck select all jika ada yang di uncheck
    pesertaCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (!this.checked) {
                selectAllCheckbox.checked = false;
            } else {
                // Cek apakah semua checkbox sudah dicentang
                const allChecked = Array.from(pesertaCheckboxes).every(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
            }
            updateTargetWaktuDisplay();
            updateBatasWaktuValidation();
        });
    });
});
</script>

@endsection
