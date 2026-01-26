@extends('layouts.mantis')
@section('content')
<div class="">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="text-center">Daftar Departemen</h2>
            <a href="{{ route('bagian.create') }}" class="btn btn-primary">
                <i class="ti ti-plus"></i> Tambah Departemen
            </a>
        </div>
        <div class="card-body">
            @if($bagians->isEmpty())
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle"></i> Belum ada data bagian.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="tabel">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>Nama Departemen</th>
                                <th>Jumlah Peserta</th>
                                <th>Jumlah Mentor</th>
                                <th>Opsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bagians as $bagian)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $bagian->nama_bagian }}</td>
                                    <td class="text-center">{{ $bagian->pesertas_count }}</td>
                                    <td class="text-center">{{ $bagian->mentors_count }}</td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('bagian.show', $bagian->id) }}"
                                               class="btn btn-info btn-sm" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('bagian.edit', $bagian->id) }}"
                                               class="btn btn-warning btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#confirmDeleteModal{{ $bagian->id }}"
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
                @foreach($bagians as $bagian)
                    <div class="modal fade" id="confirmDeleteModal{{ $bagian->id }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-red-500">
                                    <h5 class="modal-title text-white" id="exampleModalLabel">Konfirmasi Penghapusan</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Apakah Anda yakin ingin menghapus bagian <strong>{{ $bagian->nama_bagian }}</strong>?</p>
                                    <p class="text-muted">Data akan terhapus secara permanen dan tidak dapat dikembalikan.</p>
                                    @if($bagian->pesertas_count > 0 || $bagian->mentors_count > 0)
                                        <div class="alert alert-warning mb-2">
                                            <strong>Peringatan:</strong> Bagian ini masih memiliki {{ $bagian->pesertas_count }} peserta dan {{ $bagian->mentors_count }} mentor yang terkait.
                                        </div>
                                    @endif
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <form action="{{ route('bagian.destroy', $bagian->id) }}" method="POST" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
@endsection
