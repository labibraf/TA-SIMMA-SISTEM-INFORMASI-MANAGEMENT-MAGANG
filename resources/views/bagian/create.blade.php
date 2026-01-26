@extends('layouts.mantis')
@section('content')
<div class="">
    <div class="card">
        <div class="card-header">
            <h2 class="text-center">Tambah Departemen Baru</h2>
        </div>
        <div class="card-body">
            <form action="{{ route('bagian.store') }}" method="POST">
                @csrf
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="nama_bagian" class="form-label">Nama Departemen <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('nama_bagian') is-invalid @enderror"
                                   id="nama_bagian"
                                   name="nama_bagian"
                                   value="{{ old('nama_bagian') }}"
                                   placeholder="Masukkan nama bagian"
                                   required>
                            @error('nama_bagian')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('bagian.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
