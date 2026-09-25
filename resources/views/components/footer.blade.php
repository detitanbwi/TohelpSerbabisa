<footer class="bg-light py-5">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-3 col-md-6">
                <h5 class="fw-bold mb-3">Tentang</h5>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-dark text-decoration-none">Beranda</a></li>
                    <li><a href="#" class="text-dark text-decoration-none">Tentang</a></li>
                    <li><a href="#" class="text-dark text-decoration-none">Pelayanan</a></li>
                    <li><a href="#" class="text-dark text-decoration-none">Kontak</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h5 class="fw-bold mb-3">Layanan</h5>
                <ul class="list-unstyled">
                    @php
                        $footerLayanans = \App\Models\Layanan::where('is_active', true)->orderBy('urutan')->take(5)->get();
                    @endphp
                    @forelse($footerLayanans as $fLayanan)
                        <li class="mb-1"><a href="{{ $fLayanan->route_url }}" class="text-dark text-decoration-none">{{ $fLayanan->nama }}</a></li>
                    @empty
                        <li><a href="{{ route('index') }}#services" class="text-dark text-decoration-none">Semua Layanan</a></li>
                    @endforelse
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h5 class="fw-bold mb-3">Kontak</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="bi bi-geo-alt me-2"></i>
                        RPHF+H99, Tegal Boto Kidul, Sumbersari, Kec. Sumbersari, Kabupaten Jember, Jawa Timur 68121
                    </li>
                    <li>
                        <i class="bi bi-envelope me-2"></i>
                        <a href="mailto:adatohelp@gmail"
                            class="text-dark
                            text-decoration-none">adatohelp@gmail.com</a>
                    </li>
                    <li>
                        <i class="bi bi-telephone me-2"></i>
                        <a href="tel:0856-9590-8981" class="text-dark text-decoration-none">0856-9590-8981</a>
                    </li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h5 class="fw-bold mb-3">Media Sosial</h5>
                <ul class="list-unstyled">
                    <li>
                        <i class="bi bi-instagram me-2"></i>
                        <a href="https://www.instagram.com/adatohelp"
                            class="text-dark text-decoration-none">@Adatohelp</a>
                    </li>
                    <li>
                        <i class="bi bi-tiktok me-2"></i>
                        <a href="https://www.tiktok.com/@adatohelp" class="text-dark text-decoration-none">To Help Serba
                            Bisa</a>
                    </li>
                </ul>
            </div>
        </div>

        <hr class="my-4">

        <div class="text-center">
            <p class="mb-0">&copy;
                <script>
                    document.write(new Date().getFullYear())
                </script> AdaToHelp. All rights reserved.
            </p>
        </div>
    </div>
</footer>
