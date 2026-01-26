<div>
    <nav class="pc-sidebar">
        <div class="navbar-wrapper">
            <div class="m-header mt-3 mb-3">
                <a href="{{ route('home') }}" class="b-brand  ">
                    <!-- Ganti dengan path logo Anda jika perlu -->
                    <img src="{{ asset('template/dist/assets/images/simma-w-xl.png') }}" alt="logo" class="img-fluid w-80 h-auto">
                </a>
            </div>
            <div class="navbar-content">
                <ul class="pc-navbar">
                    <x-sidebar.links title="Dashboard" route="dashboard" icon="ti ti-dashboard"></x-sidebar.links>

                    @if (Auth::check())
                        @if (Auth::user()->role_id == 1)
                            {{-- Menu untuk Admin --}}
                            <li class="pc-item pc-caption">
                                <label>Data Master</label>
                                <i class="ti ti-brand-chrome"></i>
                            </li>
                            <x-sidebar.links title="Data Peserta" route="peserta.index" icon="ti ti-plant"></x-sidebar.links>
                            <x-sidebar.links title="Data Mentor" route="mentor.index" icon="ti ti-user"></x-sidebar.links>
                            <x-sidebar.links title="Data Departemen" route="bagian.index" icon="ti ti-vector-triangle"></x-sidebar.links>
                            <x-sidebar.links title="Data User" route="users.index" icon="ti ti-users"></x-sidebar.links>
                            <li class="pc-item pc-caption">
                                <label>Data Tugas</label>
                                <i class="ti ti-brand-chrome"></i>
                            </li>
                            <x-sidebar.links title="Daftar Tugas" route="penugasans.index" icon="ti ti-file-text"></x-sidebar.links>
                            <x-sidebar.links title="Laporan Akhir" route="laporan-akhir.index" icon="ti ti-file-text"></x-sidebar.links>
                            <x-sidebar.links title="Repositori" route="repository.index" icon="ti ti-file-text"></x-sidebar.links>
                            @elseif (Auth::user()->isMentor())
                            {{-- Menu untuk Mentor --}}
                            <li class="pc-item pc-caption">
                                <label>Data Master</label>
                                <i class="ti ti-brand-chrome"></i>
                            </li>
                            <x-sidebar.links title="Data Peserta" route="peserta.index" icon="ti ti-plant"></x-sidebar.links>
                            <li class="pc-item pc-caption">
                                <label>Data Tugas</label>
                                <i class="ti ti-brand-chrome"></i>
                            </li>
                            <x-sidebar.links title="Daftar Tugas" route="penugasans.index" icon="ti ti-file-text"></x-sidebar.links>
                            <x-sidebar.links title="Laporan Akhir" route="laporan-akhir.index" icon="ti ti-file-text"></x-sidebar.links>
                            <x-sidebar.links title="Repository" route="repository.index" icon="ti ti-file-text"></x-sidebar.links>
                            @else
                            {{-- Menu default untuk Peserta atau role lainnya jika diperlukan --}}
                            <li class="pc-item pc-caption">
                                <label>Data Tugas</label>
                                <i class="ti ti-brand-chrome"></i>
                            </li>
                            <x-sidebar.links title="Daftar Tugas" route="penugasans.index" icon="ti ti-file-text"></x-sidebar.links>
                            <x-sidebar.links title="Laporan Akhir" route="laporan-akhir.index" icon="ti ti-file-text"></x-sidebar.links>
                            <x-sidebar.links title="Repository" route="repository.index" icon="ti ti-file-text"></x-sidebar.links>
                        @endif
                    @endif
                </ul>
            </div>
        </div>
    </nav>
</div>
