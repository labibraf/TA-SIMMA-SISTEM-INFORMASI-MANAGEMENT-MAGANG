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
                    <a href="#"
                       class="pc-head-link bg-transparent"
                       data-bs-toggle="modal"
                       data-bs-target="#logoutModal">
                        <i class="ti ti-power text-danger"></i>
                    </a>
                </div>
            </div>

            <!-- Form Logout ( disembunyikan ) -->
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </div>
    </li>
  </ul>
</div>
 </div>
</header>

<!-- Modal Konfirmasi Logout -->
<div id="logoutModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="logoutModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalTitle">
                    <i class="ti ti-power me-2"></i>Konfirmasi Logout
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-3">
                    <i class="ti ti-logout text-danger" style="font-size: 4rem;"></i>
                    <p class="mt-3 mb-0">Apakah Anda yakin ingin keluar dari sistem?</p>
                    <p class="text-muted">Anda harus login kembali untuk mengakses sistem</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>Batal
                </button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('logout-form').submit();">
                    <i class="ti ti-logout me-1"></i>Ya, Logout
                </button>
            </div>
        </div>
    </div>
</div>
</div>
