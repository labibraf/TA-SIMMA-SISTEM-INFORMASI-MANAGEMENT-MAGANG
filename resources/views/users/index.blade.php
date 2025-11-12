@extends('layouts.mantis')

@section('content')
<style>
    .user-badge {
        font-size: 0.75rem !important;
        font-weight: 500 !important;
        letter-spacing: 0.5px;
        padding: 8px 12px !important;
        border-radius: 8px !important;
    }

    .dept-badge {
        font-size: 0.8rem !important;
        font-weight: 500 !important;
        letter-spacing: 0.3px;
        padding: 6px 10px !important;
        border-radius: 6px !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
</style>

<div class="">
    <div class="card">
        <div class="card-header">
            <h2 class="">Data User</h2>
        </div>
        <div class="card-body">
            @if($users->isEmpty())
                <div class="alert alert-info text-center">
                    <i class="ti ti-info-circle"></i> Belum ada data user.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="tabel">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Departemen</th>
                                <th>Tanggal Dibuat</th>
                                <th>Opsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $user->actual_name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @if($user->role && $user->isMentor())
                                            <span class="badge bg-danger user-badge">
                                                <i class="ti ti-user-star me-1"></i>{{ $user->role->role_name }}
                                            </span>
                                        @elseif($user->role && $user->isPeserta())
                                            <span class="badge bg-primary user-badge">
                                                <i class="ti ti-user me-1"></i>{{ $user->role->role_name }}
                                            </span>
                                        @elseif($user->role && $user->isAdmin())
                                            <span class="badge bg-success user-badge">
                                                <i class="ti ti-shield-check me-1"></i>{{ $user->role->role_name }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary user-badge">
                                                <i class="ti ti-alert-circle me-1"></i>Belum ada role
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php $deptInfo = $user->departemen_info; @endphp
                                        <span class="badge bg-{{ $deptInfo['color'] }} dept-badge text-white">
                                            <i class="{{ $deptInfo['icon'] }} me-1"></i>{{ $deptInfo['bagian'] }}
                                        </span>
                                    </td>
                                    <td>{{ $user->created_at->format('d M Y') }}</td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('users.edit', $user->id) }}"
                                               class="btn btn-warning btn-sm" title="Edit">
                                                <i class="ti ti-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-success btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#roleModal{{ $user->id }}"
                                                    title="Ganti Role">
                                                <i class="ti ti-exchange"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Modal Ganti Role --}}
                @foreach ($users as $user)
                    <div class="modal fade" id="roleModal{{ $user->id }}" tabindex="-1" aria-labelledby="roleModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-success text-white">
                                    <h5 class="modal-title" id="roleModalLabel">
                                        <i class="ti ti-user-cog"></i> Ganti Role User
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="text-center mb-3">
                                        <h6 class="text-muted">Ubah role untuk:</h6>
                                        <strong class="fs-5">{{ $user->name }}</strong>
                                        <p class="text-muted mb-0">{{ $user->email }}</p>
                                        @if($user->role)
                                            <p class="text-info">Role saat ini: <strong>{{ $user->role->role_name }}</strong></p>
                                        @endif
                                    </div>

                                    <form action="{{ route('users.update-role') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $user->id }}">
                                        <div class="mb-3">
                                            <label for="role_id_{{ $user->id }}" class="form-label">Pilih Role Baru</label>
                                            <select name="role_id" id="role_id_{{ $user->id }}" class="form-select" required>
                                                <option value="">-- Pilih Role --</option>
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role->id }}"
                                                            @if($user->role_id == $role->id) selected @endif>
                                                        {{ $role->role_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-success">
                                                <i class="ti ti-device-floppy"></i> Simpan Perubahan
                                            </button>
                                        </div>
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
