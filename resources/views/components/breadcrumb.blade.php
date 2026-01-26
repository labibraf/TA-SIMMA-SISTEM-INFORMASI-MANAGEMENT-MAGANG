@php
    $route = request()->route()->getName();
    $segments = request()->segments();

    // Konfigurasi breadcrumb berdasarkan route
    $breadcrumbs = [];

    // Dashboard
    if ($route === 'dashboard' || $route === 'home') {
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => null, 'active' => true]
        ];
    }
    // Mentor Routes
    elseif (str_contains($route, 'mentor')) {
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'active' => false],
            ['label' => 'Mentor', 'url' => $route === 'mentor.index' ? null : route('mentor.index'), 'active' => $route === 'mentor.index']
        ];
        if ($route === 'mentor.create') {
            $breadcrumbs[] = ['label' => 'Tambah', 'url' => null, 'active' => true];
        } elseif ($route === 'mentor.edit') {
            $breadcrumbs[] = ['label' => 'Edit', 'url' => null, 'active' => true];
        } elseif ($route === 'mentor.show') {
            $breadcrumbs[] = ['label' => 'Detail', 'url' => null, 'active' => true];
            $breadcrumbs[] = ['label' => 'Detail', 'url' => null, 'active' => true];
        }
    }
    // Peserta Routes
    elseif (str_contains($route, 'peserta')) {
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'active' => false],
            ['label' => 'Peserta', 'url' => $route === 'peserta.index' ? null : route('peserta.index'), 'active' => $route === 'peserta.index']
        ];
        if ($route === 'peserta.create') {
            $breadcrumbs[] = ['label' => 'Tambah', 'url' => null, 'active' => true];
        } elseif ($route === 'peserta.edit') {
            $breadcrumbs[] = ['label' => 'Edit', 'url' => null, 'active' => true];
        } elseif ($route === 'peserta.show') {
            $breadcrumbs[] = ['label' => 'Detail', 'url' => null, 'active' => true];
        }
    }
    // Laporan Harian Routes
    elseif (str_contains($route, 'laporan_harian')) {
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'active' => false],
            ['label' => 'Laporan Harian', 'url' => $route === 'laporan_harian.index' ? null : route('laporan_harian.index'), 'active' => $route === 'laporan_harian.index']
        ];
        if ($route === 'laporan_harian.create') {
            $breadcrumbs[] = ['label' => 'Buat Laporan', 'url' => null, 'active' => true];
        } elseif ($route === 'laporan_harian.edit') {
            $breadcrumbs[] = ['label' => 'Edit', 'url' => null, 'active' => true];
        } elseif ($route === 'laporan_harian.show') {
            $breadcrumbs[] = ['label' => 'Detail', 'url' => null, 'active' => true];
            $breadcrumbs[] = ['label' => 'Detail', 'url' => null, 'active' => true];
        }
    }
    // Penugasan Routes
    elseif (str_contains($route, 'penugasan')) {
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'active' => false],
            ['label' => 'Penugasan', 'url' => $route === 'penugasans.index' ? null : route('penugasans.index'), 'active' => $route === 'penugasans.index']
        ];
        if ($route === 'penugasans.create') {
            $breadcrumbs[] = ['label' => 'Buat Penugasan', 'url' => null, 'active' => true];
        } elseif ($route === 'penugasans.edit') {
            $breadcrumbs[] = ['label' => 'Edit', 'url' => null, 'active' => true];
        } elseif ($route === 'penugasans.show') {
            $breadcrumbs[] = ['label' => 'Detail', 'url' => null, 'active' => true];
            $breadcrumbs[] = ['label' => 'Detail', 'url' => null, 'active' => true];
        }
    }
    // Laporan Akhir Routes
    elseif (str_contains($route, 'laporan-akhir')) {
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'active' => false],
            ['label' => 'Laporan Akhir', 'url' => $route === 'laporan-akhir.index' ? null : route('laporan-akhir.index'), 'active' => $route === 'laporan-akhir.index']
        ];
        if ($route === 'laporan-akhir.create') {
            $breadcrumbs[] = ['label' => 'Buat Laporan', 'url' => null, 'active' => true];
        } elseif ($route === 'laporan-akhir.edit') {
            $breadcrumbs[] = ['label' => 'Edit', 'url' => null, 'active' => true];
        } elseif ($route === 'laporan-akhir.show') {
            $breadcrumbs[] = ['label' => 'Detail', 'url' => null, 'active' => true];
            $breadcrumbs[] = ['label' => 'Detail', 'url' => null, 'active' => true];
        }
    }
    // Repository Routes
    elseif (str_contains($route, 'repository')) {
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'active' => false],
            ['label' => 'Repository', 'url' => $route === 'repository.index' ? null : route('repository.index'), 'active' => $route === 'repository.index']
        ];
        if ($route === 'repository.create') {
            $breadcrumbs[] = ['label' => 'Tambah', 'url' => null, 'active' => true];
        } elseif ($route === 'repository.edit') {
            $breadcrumbs[] = ['label' => 'Edit', 'url' => null, 'active' => true];
        } elseif ($route === 'repository.show') {
            $breadcrumbs[] = ['label' => 'Detail', 'url' => null, 'active' => true];
            $breadcrumbs[] = ['label' => 'Detail', 'url' => null, 'active' => true];
        }
    }
    // Users Routes
    elseif (str_contains($route, 'users')) {
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'active' => false],
            ['label' => 'Pengguna', 'url' => $route === 'users.index' ? null : route('users.index'), 'active' => $route === 'users.index']
        ];
        if ($route === 'users.create') {
            $breadcrumbs[] = ['label' => 'Tambah', 'url' => null, 'active' => true];
        } elseif ($route === 'users.edit') {
            $breadcrumbs[] = ['label' => 'Edit', 'url' => null, 'active' => true];
        } elseif ($route === 'users.show') {
            $breadcrumbs[] = ['label' => 'Detail', 'url' => null, 'active' => true];
        }
    }
    // Bagian Routes
    elseif (str_contains($route, 'bagian')) {
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'active' => false],
            ['label' => 'Bagian', 'url' => $route === 'bagian.index' ? null : route('bagian.index'), 'active' => $route === 'bagian.index']
        ];
        if ($route === 'bagian.create') {
            $breadcrumbs[] = ['label' => 'Tambah', 'url' => null, 'active' => true];
        } elseif ($route === 'bagian.edit') {
            $breadcrumbs[] = ['label' => 'Edit', 'url' => null, 'active' => true];
        } elseif ($route === 'bagian.show') {
            $breadcrumbs[] = ['label' => 'Detail', 'url' => null, 'active' => true];
            $breadcrumbs[] = ['label' => 'Detail', 'url' => null, 'active' => true];
        }
    }
    // Default
    else {
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'active' => false],
            ['label' => $subtitle ?? 'Page', 'url' => null, 'active' => true]
        ];
    }
@endphp

<div class="page-header mb-0" style="padding: 5px 0; min-height: auto;">
    <div class="page-block">
        <div class="row align-items-center">
            <div class="col-md-12">
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
