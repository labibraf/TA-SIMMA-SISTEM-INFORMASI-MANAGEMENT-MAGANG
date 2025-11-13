@php
    $route = request()->route()->getName();
    $segments = request()->segments();

    // Konfigurasi breadcrumb berdasarkan route
    $breadcrumbs = [];

    // Dashboard
    if ($route === 'dashboard' || $route === 'home') {
        $pageTitle = 'Dashboard ' . (auth()->check() && auth()->user()->role_id == 1 ? 'Admin' : (auth()->user()->role_id == 2 ? 'Mentor' : 'Peserta'));
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => null, 'active' => true]
        ];
    }
    // Mentor Routes
    elseif (str_contains($route, 'mentor')) {
        $pageTitle = 'Manajemen Mentor';
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'active' => false],
            ['label' => 'Mentor', 'url' => $route === 'mentor.index' ? null : route('mentor.index'), 'active' => $route === 'mentor.index']
        ];
        if ($route === 'mentor.create') {
            $pageTitle = 'Tambah Mentor';
            $breadcrumbs[] = ['label' => 'Tambah', 'url' => null, 'active' => true];
        } elseif ($route === 'mentor.edit') {
            $pageTitle = 'Edit Mentor';
            $breadcrumbs[] = ['label' => 'Edit', 'url' => null, 'active' => true];
        } elseif ($route === 'mentor.show') {
            $pageTitle = 'Detail Mentor';
            $breadcrumbs[] = ['label' => 'Detail', 'url' => null, 'active' => true];
        }
    }
    // Peserta Routes
    elseif (str_contains($route, 'peserta')) {
        $pageTitle = 'Manajemen Peserta';
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'active' => false],
            ['label' => 'Peserta', 'url' => $route === 'peserta.index' ? null : route('peserta.index'), 'active' => $route === 'peserta.index']
        ];
        if ($route === 'peserta.create') {
            $pageTitle = 'Tambah Peserta';
            $breadcrumbs[] = ['label' => 'Tambah', 'url' => null, 'active' => true];
        } elseif ($route === 'peserta.edit') {
            $pageTitle = 'Edit Peserta';
            $breadcrumbs[] = ['label' => 'Edit', 'url' => null, 'active' => true];
        } elseif ($route === 'peserta.show') {
            $pageTitle = 'Detail Peserta';
            $breadcrumbs[] = ['label' => 'Detail', 'url' => null, 'active' => true];
        }
    }
    // Laporan Harian Routes
    elseif (str_contains($route, 'laporan_harian')) {
        $pageTitle = 'Laporan Harian';
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'active' => false],
            ['label' => 'Laporan Harian', 'url' => $route === 'laporan_harian.index' ? null : route('laporan_harian.index'), 'active' => $route === 'laporan_harian.index']
        ];
        if ($route === 'laporan_harian.create') {
            $pageTitle = 'Buat Laporan Harian';
            $breadcrumbs[] = ['label' => 'Buat Laporan', 'url' => null, 'active' => true];
        } elseif ($route === 'laporan_harian.edit') {
            $pageTitle = 'Edit Laporan Harian';
            $breadcrumbs[] = ['label' => 'Edit', 'url' => null, 'active' => true];
        } elseif ($route === 'laporan_harian.show') {
            $pageTitle = 'Detail Laporan Harian';
            $breadcrumbs[] = ['label' => 'Detail', 'url' => null, 'active' => true];
        }
    }
    // Penugasan Routes
    elseif (str_contains($route, 'penugasan')) {
        $pageTitle = 'Penugasan';
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'active' => false],
            ['label' => 'Penugasan', 'url' => $route === 'penugasans.index' ? null : route('penugasans.index'), 'active' => $route === 'penugasans.index']
        ];
        if ($route === 'penugasans.create') {
            $pageTitle = 'Buat Penugasan';
            $breadcrumbs[] = ['label' => 'Buat Penugasan', 'url' => null, 'active' => true];
        } elseif ($route === 'penugasans.edit') {
            $pageTitle = 'Edit Penugasan';
            $breadcrumbs[] = ['label' => 'Edit', 'url' => null, 'active' => true];
        } elseif ($route === 'penugasans.show') {
            $pageTitle = 'Detail Penugasan';
            $breadcrumbs[] = ['label' => 'Detail', 'url' => null, 'active' => true];
        }
    }
    // Laporan Akhir Routes
    elseif (str_contains($route, 'laporan-akhir')) {
        $pageTitle = 'Laporan Akhir';
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'active' => false],
            ['label' => 'Laporan Akhir', 'url' => $route === 'laporan-akhir.index' ? null : route('laporan-akhir.index'), 'active' => $route === 'laporan-akhir.index']
        ];
        if ($route === 'laporan-akhir.create') {
            $pageTitle = 'Buat Laporan Akhir';
            $breadcrumbs[] = ['label' => 'Buat Laporan', 'url' => null, 'active' => true];
        } elseif ($route === 'laporan-akhir.edit') {
            $pageTitle = 'Edit Laporan Akhir';
            $breadcrumbs[] = ['label' => 'Edit', 'url' => null, 'active' => true];
        } elseif ($route === 'laporan-akhir.show') {
            $pageTitle = 'Detail Laporan Akhir';
            $breadcrumbs[] = ['label' => 'Detail', 'url' => null, 'active' => true];
        }
    }
    // Repository Routes
    elseif (str_contains($route, 'repository')) {
        $pageTitle = 'Repository';
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'active' => false],
            ['label' => 'Repository', 'url' => $route === 'repository.index' ? null : route('repository.index'), 'active' => $route === 'repository.index']
        ];
        if ($route === 'repository.create') {
            $pageTitle = 'Tambah Repository';
            $breadcrumbs[] = ['label' => 'Tambah', 'url' => null, 'active' => true];
        } elseif ($route === 'repository.edit') {
            $pageTitle = 'Edit Repository';
            $breadcrumbs[] = ['label' => 'Edit', 'url' => null, 'active' => true];
        } elseif ($route === 'repository.show') {
            $pageTitle = 'Detail Repository';
            $breadcrumbs[] = ['label' => 'Detail', 'url' => null, 'active' => true];
        }
    }
    // Users Routes
    elseif (str_contains($route, 'users')) {
        $pageTitle = 'Manajemen Pengguna';
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'active' => false],
            ['label' => 'Pengguna', 'url' => $route === 'users.index' ? null : route('users.index'), 'active' => $route === 'users.index']
        ];
        if ($route === 'users.create') {
            $pageTitle = 'Tambah Pengguna';
            $breadcrumbs[] = ['label' => 'Tambah', 'url' => null, 'active' => true];
        } elseif ($route === 'users.edit') {
            $pageTitle = 'Edit Pengguna';
            $breadcrumbs[] = ['label' => 'Edit', 'url' => null, 'active' => true];
        } elseif ($route === 'users.show') {
            $pageTitle = 'Detail Pengguna';
            $breadcrumbs[] = ['label' => 'Detail', 'url' => null, 'active' => true];
        }
    }
    // Bagian Routes
    elseif (str_contains($route, 'bagian')) {
        $pageTitle = 'Manajemen Bagian';
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'active' => false],
            ['label' => 'Bagian', 'url' => $route === 'bagian.index' ? null : route('bagian.index'), 'active' => $route === 'bagian.index']
        ];
        if ($route === 'bagian.create') {
            $pageTitle = 'Tambah Bagian';
            $breadcrumbs[] = ['label' => 'Tambah', 'url' => null, 'active' => true];
        } elseif ($route === 'bagian.edit') {
            $pageTitle = 'Edit Bagian';
            $breadcrumbs[] = ['label' => 'Edit', 'url' => null, 'active' => true];
        } elseif ($route === 'bagian.show') {
            $pageTitle = 'Detail Bagian';
            $breadcrumbs[] = ['label' => 'Detail', 'url' => null, 'active' => true];
        }
    }
    // Default
    else {
        $pageTitle = $title ?? 'Page';
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'active' => false],
            ['label' => $subtitle ?? 'Page', 'url' => null, 'active' => true]
        ];
    }
@endphp

<div class="page-header">
    <div class="page-block">
        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="page-header-title">
                    <h3 class="m-b-10">{{ $pageTitle }}</h3>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-default-icon">
                        @foreach($breadcrumbs as $index => $breadcrumb)
                            @if($index === 0)
                                {{-- First item dengan icon home --}}
                                <li class="breadcrumb-item {{ $breadcrumb['active'] ? 'active' : '' }}"
                                    @if($breadcrumb['active']) aria-current="page" @endif>
                                    @if($breadcrumb['url'])
                                        <a href="{{ $breadcrumb['url'] }}">
                                            <i class="ti ti-home-2"></i>
                                        </a>
                                    @else
                                        <i class="ti ti-home-2"></i>
                                    @endif
                                </li>
                            @else
                                {{-- Item selanjutnya --}}
                                <li class="breadcrumb-item {{ $breadcrumb['active'] ? 'active' : '' }}"
                                    @if($breadcrumb['active']) aria-current="page" @endif>
                                    @if($breadcrumb['url'])
                                        <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['label'] }}</a>
                                    @else
                                        {{ $breadcrumb['label'] }}
                                    @endif
                                </li>
                            @endif
                        @endforeach
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>
