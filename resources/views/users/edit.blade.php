@extends('layouts.mantis')
@section('content')
<div class="">
    <div class="card">
        <div class="card-header">
            <h2 class="text-center">
                <i class="fas fa-user-edit"></i> Edit Data User
            </h2>
        </div>
        <div class="card-body">
            <form action="{{ route('users.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <!-- Informasi User -->
                        <div class="card mb-4">
                            <div class="card-header bg-blue-200 text-white mr-3">
                                <h5 class="mb-0"><i class="fas fa-user"></i> Informasi Dasar</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text"
                                           class="form-control @error('name') is-invalid @enderror"
                                           id="name"
                                           name="name"
                                           value="{{ old('name', $user->name) }}"
                                           placeholder="Masukkan nama lengkap"
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email"
                                           class="form-control @error('email') is-invalid @enderror"
                                           id="email"
                                           name="email"
                                           value="{{ old('email', $user->email) }}"
                                           placeholder="Masukkan email"
                                           required>
                                    @error('email')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Role Saat Ini</label>
                                    <div class="form-control-plaintext">
                                        @if($user->role)
                                            <span class="badge bg-primary fs-6">{{ $user->role->role_name }}</span>
                                        @else
                                            <span class="badge bg-secondary fs-6">Belum ada role</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Profil -->
                        <div class="card mb-4">
                            <div class="card-header bg-blue-200 text-white">
                                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Status Profil</h5>
                            </div>
                            <div class="card-body">
                                @if($user->peserta)
                                    <div class="alert bg-green-100">
                                        <i class="fas fa-user-graduate mr-2"></i>
                                        <strong>Profil Peserta Lengkap</strong>
                                        <p class="mb-0 mt-2">Nama di profil peserta: <strong>{{ $user->peserta->nama_lengkap }}</strong></p>
                                        <p class="mb-0">Email di profil peserta: <strong>{{ $user->peserta->email }}</strong></p>
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle"></i>
                                            Perubahan nama dan email akan disinkronisasi dengan profil peserta
                                        </small>
                                    </div>
                                @elseif($user->mentor)
                                    <div class="alert alert-info">
                                        <i class="fas fa-user-tie"></i>
                                        <strong>Profil Mentor Lengkap</strong>
                                        <p class="mb-0 mt-2">Nama di profil mentor: <strong>{{ $user->mentor->nama_mentor }}</strong></p>
                                        <p class="mb-0">Email di profil mentor: <strong>{{ $user->mentor->email }}</strong></p>
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle"></i>
                                            Perubahan nama dan email akan disinkronisasi dengan profil mentor
                                        </small>
                                    </div>
                                @else
                                    <div class="alert alert-warning">
                                        <i class="fas fa-user"></i>
                                        <strong>Hanya Data User</strong>
                                        <p class="mb-0 mt-2">User ini belum memiliki profil peserta atau mentor yang lengkap.</p>
                                    </div>
                                @endif

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i>
                                            Dibuat: {{ $user->created_at->format('d/m/Y H:i') }}
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <i class="fas fa-edit"></i>
                                            Diupdate: {{ $user->updated_at->format('d/m/Y H:i') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Perbarui Data
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
