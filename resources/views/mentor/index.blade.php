@extends('layouts.mantis')
@section('content')
<div class="">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="text-center">Daftar Mentor</h2>
            <a href="{{ route('mentor.create') }}" class="btn btn-primary">
                <i class="ti ti-plus"></i> Tambah Mentor
            </a>
        </div>
        <div class="card-body">
            @if($mentors->isEmpty())
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle"></i> Belum ada data mentor.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="tabel">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>Nama Mentor</th>
                                <th>Email</th>
                                <th>No. Identitas</th>
                                <th>Bagian</th>
                                <th>Keahlian</th>
                                <th>Foto</th>
                                <th>Opsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mentors as $index => $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->nama_mentor }}</td>
                                    <td>{{ $item->email }}</td>
                                    <td>{{ $item->nomor_identitas }}</td>
                                    <td>{{ $item->bagian?->nama_bagian ?? '-' }}</td>
                                    <td>{{ $item->keahlian }}</td>
                                    <td class="text-center">
                                        @if($item->foto)
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                                data-bs-target="#modalFoto{{ $item->id }}">
                                                <i class="fas fa-image"></i> Lihat
                                            </button>
                                        @else
                                            <span class="badge bg-secondary">Tidak ada</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('mentor.show', $item->id) }}"
                                               class="btn btn-info btn-sm" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('mentor.edit', $item->id) }}"
                                               class="btn btn-warning btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#confirmDeleteModal{{ $item->id }}"
                                                    title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Modal Konfirmasi Hapus --}}
                @foreach($mentors as $item)
                    <div class="modal fade" id="confirmDeleteModal{{ $item->id }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title" id="exampleModalLabel">Konfirmasi Penghapusan</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Apakah Anda yakin ingin menghapus data mentor <strong>{{ $item->nama_mentor }}</strong>?</p>
                                    <p class="text-muted">Data akan terhapus secara permanen dan tidak dapat dikembalikan.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <form action="{{ route('mentor.destroy', $item->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Modal Foto --}}
                @foreach ($mentors as $item)
                    @if($item->foto)
                        <div class="modal fade" id="modalFoto{{ $item->id }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="exampleModalLabel">Foto {{ $item->nama_mentor }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-center">
                                        <img src="{{ asset('storage/foto_mentor/'.$item->foto) }}" alt="{{ $item->nama_mentor }}" class="img-fluid rounded">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
    </div>
</div>
@endsection

