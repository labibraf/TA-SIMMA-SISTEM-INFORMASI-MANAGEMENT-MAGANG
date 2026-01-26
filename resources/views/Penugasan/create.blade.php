@extends('layouts.mantis')

@section('content')
<!-- Header dengan tombol kembali -->
<div class="mb-4">
    <div class="d-flex flex-column">
        <div class="mb-2">
            <a href="{{ route('penugasans.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
        <div>
            <h2 class="mb-0 text-dark">Tambah Penugasan</h2>
            <p class="text-muted mb-0">Buat penugasan baru untuk peserta magang</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('penugasans.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="judul_tugas" class="form-label fw-bold">Judul Tugas</label>
                        <input type="text" name="judul_tugas" id="judul_tugas"
                            class="form-control @error('judul_tugas') is-invalid @enderror"
                            value="{{ old('judul_tugas') }}" required autocomplete="off">
                        @error('judul_tugas')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="deskripsi_tugas" class="form-label fw-bold">Deskripsi Tugas</label>
                        <textarea name="deskripsi_tugas" id="deskripsi_tugas" rows="4"
                            class="form-control @error('deskripsi_tugas') is-invalid @enderror"
                            required autocomplete="off">{{ old('deskripsi_tugas') }}</textarea>
                        @error('deskripsi_tugas')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="kategori" class="form-label fw-bold">Ditugaskan</label>
                        <select name="kategori" id="kategori"
                            class="form-select @error('kategori') is-invalid @enderror" required>
                            <option value="">-- Pilih Kategori --</option>
                            <option value="Individu" {{ old('kategori') == 'Individu' ? 'selected' : '' }}>Individu</option>
                            <option value="Divisi" {{ old('kategori') == 'Divisi' ? 'selected' : '' }}>Divisi</option>
                        </select>
                        @error('kategori')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="deadline" class="form-label fw-bold">Deadline</label>
                        <input type="date" name="deadline" id="deadline"
                            class="form-control @error('deadline') is-invalid @enderror"
                            value="{{ old('deadline') }}" required autocomplete="off">
                        @error('deadline')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="beban_waktu" class="form-label fw-bold">Beban Waktu</label>
                        <div class="input-group">
                            <input type="number" name="beban_waktu" id="beban_waktu"
                                class="form-control @error('beban_waktu') is-invalid @enderror"
                                value="{{ old('beban_waktu') }}" min="1" max="168"
                                required autocomplete="off">
                            <span class="input-group-text">jam</span>
                        </div>
                        @error('beban_waktu')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        <div class="form-text text-muted mt-1" id="sisaTargetInfo">
                            Pilih peserta untuk melihat sisa jam kerja
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="file" class="form-label fw-bold">File</label>
                        <input type="file" name="file" id="file"
                            class="form-control @error('file') is-invalid @enderror">
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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
                            @foreach($peserta as $item)
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
                                        {{ old('peserta_id') == $item->id ? 'selected' : '' }}>
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
                                <input class="form-check-input" type="checkbox" id="selectAll" name="select_all" value="1">
                                <label class="form-check-label" for="selectAll">
                                    Pilih Semua
                                </label>
                            </div>
                        </div>

                        <div class="row">
                            @foreach($peserta as $item)
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
                                            {{ in_array($item->id, old('peserta_ids', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="peserta_{{ $item->id }}">
                                            {{ $item->user->name }}
                                            <span class="badge {{ $badgeClass }} badge-sm">{{ $statusInfo }}</span>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Tabel untuk menampilkan peserta yang dipilih -->
                        <div id="target-waktu-display" class="mt-3" style="display:none;">
                            <h5 class="mb-2">Peserta yang Dipilih:</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama Peserta</th>
                                            <th>Sisa Jam Kerja</th>
                                            <th>Sisa Hari Kerja</th>
                                        </tr>
                                    </thead>
                                    <tbody id="target-waktu-body">
                                        <!-- Data akan ditambahkan secara dinamis -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save me-2"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elemen DOM yang sering digunakan
    const kategoriSelect = document.getElementById('kategori');
    const pesertaIndividuField = document.getElementById('peserta-individu-field');
    const pesertaDivisiField = document.getElementById('peserta-divisi-field');
    const selectAllCheckbox = document.getElementById('selectAll');
    const pesertaCheckboxes = document.querySelectorAll('input[name="peserta_ids[]"]');
    const targetWaktuDisplay = document.getElementById('target-waktu-display');
    const targetWaktuBody = document.getElementById('target-waktu-body');
    const bebanWaktuInput = document.getElementById('beban_waktu');
    const pesertaSelect = document.getElementById('peserta_id');
    const sisaTargetInfo = document.getElementById('sisaTargetInfo');
    const sisaWaktu = document.getElementsByClassName('form-check');

    // Fungsi untuk toggle field peserta berdasarkan kategori
    function togglePesertaFields() {
        if (kategoriSelect.value === 'Individu') {
            pesertaIndividuField.style.display = 'block';
            pesertaDivisiField.style.display = 'none';
            resetDivisiSelection();
        } else if (kategoriSelect.value === 'Divisi') {
            pesertaIndividuField.style.display = 'none';
            pesertaDivisiField.style.display = 'block';
            updateSelectAllCheckbox();
            updateTargetWaktuDisplay();
        } else {
            pesertaIndividuField.style.display = 'none';
            pesertaDivisiField.style.display = 'none';
            resetDivisiSelection();
        }
        updateBatasWaktuValidation();
    }

    // Fungsi untuk reset selection divisi
    function resetDivisiSelection() {
        targetWaktuDisplay.style.display = 'none';
        targetWaktuBody.innerHTML = '';
        selectAllCheckbox.checked = false;
        pesertaCheckboxes.forEach(checkbox => checkbox.checked = false);
    }

    // Fungsi untuk update status checkbox "Pilih Semua"
    function updateSelectAllCheckbox() {
        const allChecked = pesertaCheckboxes.length > 0 &&
                          Array.from(pesertaCheckboxes).every(checkbox => checkbox.checked);
        selectAllCheckbox.checked = allChecked;
    }

    // Fungsi untuk update tabel peserta yang dipilih
    function updateTargetWaktuDisplay() {
        targetWaktuBody.innerHTML = '';
        const checkedCheckboxes = document.querySelectorAll('input[name="peserta_ids[]"]:checked');

        if (checkedCheckboxes.length > 0) {
            checkedCheckboxes.forEach(checkbox => {
                // Ambil data langsung dari attribute
                const pesertaName = checkbox.dataset.nama || checkbox.nextElementSibling.textContent.split(' (Sisa:')[0];
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

    // Fungsi untuk menerapkan batas waktu
    function applyBatasWaktuLimit(maxValue, infoText) {
        if (maxValue > 0) {
            bebanWaktuInput.setAttribute('max', maxValue);
            sisaTargetInfo.textContent = `${infoText} (maksimal input)`;
            sisaTargetInfo.className = 'form-text text-muted mt-1';

            const inputValue = parseInt(bebanWaktuInput.value) || 0;
            if (inputValue > maxValue) {
                bebanWaktuInput.value = maxValue;
            }
        } else {
            sisaTargetInfo.textContent = 'Peserta tidak memiliki sisa jam kerja, tidak dapat menambah tugas';
            sisaTargetInfo.className = 'form-text text-danger mt-1';
            bebanWaktuInput.setAttribute('max', 0);
        }
    }

    // Event listeners
    kategoriSelect.addEventListener('change', togglePesertaFields);

    selectAllCheckbox.addEventListener('change', function() {
        pesertaCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateTargetWaktuDisplay();
        updateBatasWaktuValidation();
    });

    pesertaCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (!this.checked) {
                selectAllCheckbox.checked = false;
            } else {
                updateSelectAllCheckbox();
            }
            updateTargetWaktuDisplay();
            updateBatasWaktuValidation();
        });
    });

    pesertaSelect.addEventListener('change', updateBatasWaktuValidation);
    bebanWaktuInput.addEventListener('input', updateBatasWaktuValidation);

    // Inisialisasi
    togglePesertaFields();
});
</script>
@endsection
