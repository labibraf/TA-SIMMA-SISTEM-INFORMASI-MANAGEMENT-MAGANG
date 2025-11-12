<!DOCTYPE html>
<html lang="en">
<!-- [Head] start -->

<head>
  <title>Login | Manajemen Magang APPS </title>
  <!-- [Meta] -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">


  <!-- [Favicon] icon -->
  <link rel="icon" href="{{ asset('template/dist') }}/assets/images/simma-w-xl.png" type="image/x-icon"> <!-- [Google Font] Family -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" id="main-font-link">
<!-- [Tabler Icons] https://tablericons.com -->
<link rel="stylesheet" href="{{ asset('template/dist') }}/assets/fonts/tabler-icons.min.css" >
<!-- [Feather Icons] https://feathericons.com -->
<link rel="stylesheet" href="{{ asset('template/dist') }}/assets/fonts/feather.css" >
<!-- [Font Awesome Icons] https://fontawesome.com/icons -->
<link rel="stylesheet" href="{{ asset('template/dist') }}/assets/fonts/fontawesome.css" >
<!-- [Material Icons] https://fonts.google.com/icons -->
<link rel="stylesheet" href="{{ asset('template/dist') }}/assets/fonts/material.css" >
<!-- [Template CSS Files] -->
<link rel="stylesheet" href="{{ asset('template/dist') }}/assets/css/style.css" id="main-style-link" >
<link rel="stylesheet" href="{{ asset('template/dist') }}/assets/css/style-preset.css" >
@vite(['resources/js/app.js'])
</head>
<!-- [Head] end -->
<!-- [Body] Start -->

<body>
  <!-- [ Pre-loader ] start -->
  <div class="loader-bg">
    <div class="loader-track">
      <div class="loader-fill"></div>
    </div>
  </div>
  <!-- [ Pre-loader ] End -->

  <div class="auth-main">
    <div class="auth-wrapper v3">
      <div class="auth-form">
        <div class="auth-header justify-content-center mt-5">
        <a href="#"><img src="{{ asset('template/dist') }}/assets/images/simma-w-xl.png"
            style="height: auto; max-height: 200px; width: auto; max-width: 400px; display: block;"
            alt="img"></a>
        </div>
        <div class="card my-2">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-end mb-4">
              <h3 class="mb-0"><b>Login</b></h3>
              <a href="{{ route('register') }}" class="link-primary">Don't have an account?</a>
            </div>
            <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="form-group mb-3">
              <label class="form-label">Email Address</label>
              <input type="email" class="form-control" placeholder="Email Address" class="form-control @error('email') is-invalid @enderror" name="email"  required autocomplete="off" autofocus>
              @error('email')
                  <small class="text-danger">{{ $message }}></small>
              @enderror
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Password</label>
              <input type="password" class="form-control" placeholder="Password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="off">
             @error('password')
                  <small class="text-danger">{{ $message }}></small>
             @enderror
            </div>
            <div class="d-grid mt-4">
              <button type="submit" class="btn btn-primary">Login</button>
            </div>
            <div class="saprator mt-3">
              <span>Login with</span>
            </div>
          </div>
        </div>
        </form>
        <div class="auth-footer row">
          <!-- <div class=""> -->
          <!-- </div> -->
        </div>
      </div>
    </div>
  </div>
  <!-- [ Main Content ] end -->
  <!-- Required Js -->
  <script src="{{ asset('template/dist') }}/assets/js/plugins/popper.min.js"></script>
  <script src="{{ asset('template/dist') }}/assets/js/plugins/simplebar.min.js"></script>
  <script src="{{ asset('template/dist') }}/assets/js/plugins/bootstrap.min.js"></script>
  <script src="{{ asset('template/dist') }}/assets/js/fonts/custom-font.js"></script>
  <script src="{{ asset('template/dist') }}/assets/js/pcoded.js"></script>
  <script src="{{ asset('template/dist') }}/assets/js/plugins/feather.min.js"></script>





  <script>layout_change('light');</script>




  <script>change_box_container('false');</script>



  <script>layout_rtl_change('false');</script>


  <script>preset_change("preset-1");</script>


  <script>font_change("poppins");</script>



</body>
<!-- [Body] end -->

</html>
