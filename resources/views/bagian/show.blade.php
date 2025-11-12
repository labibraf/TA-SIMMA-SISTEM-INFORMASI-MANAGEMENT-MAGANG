@extends('layouts.mantis')
@section('content')
<div class="">
    <div class="mb-3">
        <a href="{{ route('bagian.index') }}" class="btn btn-secondary">
            <i class="ti ti-arrow-left me-1"></i> Kembali
        </a>
    </div>

    {{-- Informasi Bagian --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-building me-2"></i>Informasi Departemen
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted">Nama Departemen</label>
                                <h5 class="mb-0">{{ $bagian->nama_bagian }}</h5>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="text-muted">Total Peserta</label>
                                <h5 class="mb-0">{{ $bagian->peserta->count() }} Orang</h5>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="text-muted">Total Mentor</label>
                                <h5 class="mb-0">{{ $bagian->mentor->count() }} Orang</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Daftar Peserta --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-users me-2"></i>Daftar Peserta Magang
                    </h5>
                </div>
                <div class="card-body">
                    @if($bagian->peserta->isEmpty())
                        <div class="text-center py-5">
                            <i class="ti ti-user-off text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">Belum ada peserta di departemen ini</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Peserta</th>
                                        <th>Email</th>
                                        <th>No. Telepon</th>
                                        <th>Asal Instansi</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($bagian->peserta as $peserta)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <strong>{{ $peserta->nama_lengkap }}</strong>
                                            @if($peserta->is_laporan_akhir_selesai)
                                                <br><span class="badge bg-success"><i class="ti ti-check"></i> Selesai</span>
                                            @endif
                                        </td>
                                        <td>{{ $peserta->email }}</td>
                                        <td>{{ $peserta->no_telepon ?? '-' }}</td>
                                        <td>{{ $peserta->asal_instansi ?? '-' }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('peserta.show', $peserta->id) }}" class="btn btn-sm btn-info" title="Detail">
                                                <i class="ti ti-eye"></i>
                                            </a>
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
    </div>

    {{-- Daftar Mentor --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-user-check me-2"></i>Daftar Mentor
                    </h5>
                </div>
                <div class="card-body">
                    @if($bagian->mentor->isEmpty())
                        <div class="text-center py-5">
                            <i class="ti ti-user-off text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">Belum ada mentor di departemen ini</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Mentor</th>
                                        <th>Email</th>
                                        <th>No. Telepon</th>
                                        <th>Nomor Identitas</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($bagian->mentor as $mentor)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><strong>{{ $mentor->nama_mentor }}</strong></td>
                                        <td>{{ $mentor->email }}</td>
                                        <td>{{ $mentor->no_telepon ?? '-' }}</td>
                                        <td>{{ $mentor->nomor_identitas ?? '-' }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('mentor.show', $mentor->id) }}" class="btn btn-sm btn-info" title="Detail">
                                                <i class="ti ti-eye"></i>
                                            </a>
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
    </div>
</div>
@endsection
