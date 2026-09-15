<!DOCTYPE html>
<html>

<head>
    <title>SerbaBisaToHelp - Solusi Permasalahanmu</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="author" content="templatesjungle">
    <meta name="keywords" content="website template">
    @php
        $faviconSrc = asset('images/logo-tohelp-kecil.png');
        $favPaths = [
            public_path('images/logo-tohelp-kecil.png'),
            public_path('logo-tohelp-kecil.png'),
            base_path('public/images/logo-tohelp-kecil.png'),
            base_path('public/logo-tohelp-kecil.png'),
        ];
        foreach ($favPaths as $fp) {
            if (file_exists($fp)) {
                $faviconSrc = 'data:image/png;base64,' . base64_encode(file_get_contents($fp));
                break;
            }
        }
    @endphp
    <link rel="icon" type="image/png" href="{{ $faviconSrc }}">

    <!--Bootstrap ================================================== -->
    <link rel="stylesheet" type="text/css" href="{{ asset('css/bootstrap.min.css') }}">

    <!--vendor css ================================================== -->
    <link rel="stylesheet" type="text/css" href="{{ asset('css/vendor.css') }}">


    <!--Link Swiper's CSS ================================================== -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" />

    <!-- Style Sheet ================================================== -->
    <link rel="stylesheet" type="text/css" href="{{ asset('css/style.css') }}">

    <!-- Google Fonts ================================================== -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    @stack('styles')

</head>

<body>
    @include('components.symbol')

    @include('components.header')

    @yield('content')

    @include('components.footer')

    <script src="{{ asset('js/jquery-1.11.0.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/plugins.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js"></script>
    <script type="text/javascript" src="{{ asset('js/script.js') }}"></script>
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('toastr/build/toastr.css') }}">
    <script src="{{ asset('toastr/build/toastr.min.js') }}"></script>
    <script src="{{ asset('sweetalert2/dist/sweetalert2.all.min.js') }}"></script>
    @stack('scripts')

</body>

</html>
