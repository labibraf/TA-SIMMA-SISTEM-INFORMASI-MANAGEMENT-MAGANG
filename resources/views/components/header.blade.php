<div>
   <header class="pc-header">
  <div class="header-wrapper"> <!-- [Mobile Media Block] start -->
<div class="me-auto pc-mob-drp">
  <ul class="list-unstyled">
    <!-- ======= Menu collapse Icon ===== -->
    <li class="pc-h-item pc-sidebar-collapse">
      <a href="#" class="pc-head-link ms-0" id="sidebar-hide">
        <i class="ti ti-menu-2"></i>
      </a>
    </li>
    <li class="pc-h-item pc-sidebar-popup">
      <a href="#" class="pc-head-link ms-0" id="mobile-collapse">
        <i class="ti ti-menu-2"></i>
      </a>
    </li>
  </ul>
</div>
<!-- [Mobile Media Block end] -->
<div class="ms-auto">
  <ul class="list-unstyled">
    <li class="dropdown pc-h-item header-user-profile">
        <a
            class="pc-head-link dropdown-toggle arrow-none me-0"
            data-bs-toggle="dropdown"
            href="#"
            role="button"
            aria-haspopup="false"
            data-bs-auto-close="outside"
            aria-expanded="false"
        >
            @if(auth()->user()->peserta && auth()->user()->peserta->foto)
                <img src="{{ asset('storage/foto_peserta/' . auth()->user()->peserta->foto) }}" alt="user-image" class="user-avtar">
            @elseif(auth()->user()->mentor && auth()->user()->mentor->foto)
                <img src="{{ asset('storage/foto_mentor/' . auth()->user()->mentor->foto) }}" alt="user-image" class="user-avtar">
            @else
                <img src="{{ asset('template/dist/assets/images/user/avatar-2.jpg') }}" alt="user-image" class="user-avtar">
            @endif
            <span>{{ auth()->user()->name }}</span>
        </a>
        <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown">
            <!-- Header: Avatar + Nama + Role + Logout -->
            <div class="dropdown-header">
                <div class="d-flex mb-1">
                    <div class="rounded-circle me-1" width="50" height="50">
                        @if(auth()->user()->peserta && auth()->user()->peserta->foto)
                            <img src="{{ asset('storage/foto_peserta/' . auth()->user()->peserta->foto) }}" alt="user-image" class="user-avtar">
                        @elseif(auth()->user()->mentor && auth()->user()->mentor->foto)
                            <img src="{{ asset('storage/foto_mentor/' . auth()->user()->mentor->foto) }}" alt="user-image" class="user-avtar">
                        @else
                            <img src="{{ asset('template/dist/assets/images/user/avatar-2.jpg') }}" alt="user-image" class="user-avtar">
                        @endif
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <h6 class="mb-1">{{ auth()->user()->name }}</h6>
                        <span>UI/UX Designer</span> <!-- ganti dengan peran user jika ada -->
                    </div>
                    <!-- Tombol Logout -->
                    <a href="{{ route('logout') }}" 
                      class="pc-head-link bg-transparent" 
                      onclick="event.preventDefault(); if (confirm('Apakah Anda yakin ingin logout?')) { document.getElementById('logout-form').submit(); }">
                        <i class="ti ti-power text-danger"></i>
                    </a>
                </div>
            </div>

            <!-- Form Logout ( disembunyikan ) -->
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </div>
    </li>
  </ul>
</div>
 </div>
</header>
</div>