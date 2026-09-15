@extends('layouts.app')

@push('styles')
    <style>
        .hero-image-container {
            height: 680px;
            overflow: hidden;
        }

        .hero-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center bottom;
        }

        .leaflet-control-geosearch {
            width: 100%;
            margin: 0 !important;
        }

        .leaflet-control-geosearch form {
            background: #fff;
            padding: 10px;
        }

        .leaflet-control-geosearch form input {
            width: 100%;
            padding: 5px;
        }

        .custom-marker {
            display: flex;
            justify-content: center;
            align-items: center;
            border: 2px solid white;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.5);
        }
    </style>
@endpush

@section('content')
    <!-- Hero Section -->
    <section id="billboard">
        <div class="row align-items-center g-0 bg-secondary">
            <div class="col-lg-6">
                <div class="m-4 p-4 m-lg-5 p-lg-5">
                    <h6 class="text-white"><span class="text-primary">Transportasi</span>(Mobil)</h6>
                    <h2 class="display-4 fw-bold text-white my-4">Perjalanan Nyaman, Harga Bersahabat</h2>
                    <a href="#quote" class="btn btn-light btn-bg btn-slide hover-slide-right mt-4">
                        <span>Silahkan Pesan</span>
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image-container">
                    <img src="{{ asset('images/hero-ojek.jpg') }}" alt="img" class="img-fluid hero-image">
                </div>
            </div>
        </div>
    </section>

    <!-- Map/Order Section -->
    <section id="quote" class="padding-small">
        <div class="container text-center">
            <h3 class="display-6 fw-semibold mb-4">Tentukan Lokasi</h3>

            @if(isset($cabang) && $cabang->count() > 0)
                <div class="row justify-content-center mb-4">
                    <div class="col-md-8 col-lg-6">
                        <div class="card border-0 shadow-sm rounded-4 p-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div class="text-start">
                                    <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Wilayah Operasional</small>
                                    <span class="fw-bold text-dark fs-5">
                                        <i class="fas fa-map-marker-alt text-danger me-1"></i> <span class="active-cabang-name">Cabang {{ $activeCabang->nama ?? 'Pilih Wilayah' }}</span>
                                    </span>
                                </div>
                                <button type="button" class="btn btn-outline-primary rounded-pill px-3 py-1 btn-sm fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalGlobalPilihLokasi">
                                    <i class="fas fa-exchange-alt me-1"></i> Ganti Wilayah
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div id="map" style="height: 400px; width: 100%; margin-bottom: 20px;"></div>

            <button id="useMyLocation" class="btn btn-primary mb-3">
                <i class="fas fa-location-arrow"></i> Gunakan Lokasi Saya
            </button>

            <div id="routeInfo" class="alert alert-info mb-3" style="display: none;"></div>

            <form class="contact-form row mt-5" id="locationForm">
                <div class="col-lg-6 col-md-12 col-sm-12 mb-4">
                    <input type="text" name="lokasi_awal" id="lokasi_awal" placeholder="Mendapatkan lokasi Anda..."
                        class="form-control w-100 ps-3 py-2 rounded-0" required disabled>
                    <input type="hidden" id="lat_awal" name="lat_awal">
                    <input type="hidden" id="lng_awal" name="lng_awal">
                </div>
                <div class="col-lg-6 col-md-12 col-sm-12 mb-4">
                    <input type="text" name="lokasi_akhir" id="lokasi_akhir" placeholder="Masukkan Tujuan*"
                        class="form-control w-100 ps-3 py-2 rounded-0" required disabled>
                    <input type="hidden" id="lat_akhir" name="lat_akhir">
                    <input type="hidden" id="lng_akhir" name="lng_akhir">
                </div>
            </form>
            <!-- Voucher Section -->
            <div class="mt-4 mb-3">
                <div class="row justify-content-center">
                    <div class="col-lg-8 col-md-10">
                        <div class="input-group">
                            <input type="text" id="kode-voucher" class="form-control ps-3 py-2 rounded-0"
                                placeholder="Masukkan kode voucher" aria-label="Kode voucher">
                            <button class="btn btn-primary" type="button" id="apply-voucher">
                                <i class="fas fa-tag"></i> Apply Voucher
                            </button>
                            <button class="btn btn-outline-secondary" type="button" id="reset-voucher"
                                style="display: none;">
                                <i class="fas fa-times"></i> Reset
                            </button>
                        </div>
                        <div id="voucher-info" class="mt-2 text-start" style="display: none;"></div>
                    </div>
                </div>
            </div>

            <a id="order" class="btn btn-success mt-3">
                <i class="fas fa-location-arrow"></i> Pesan Via Whatsapp
            </a>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://maps.googleapis.com/maps/api/js?key={{ env('GMAP_API_KEY') }}&libraries=places"></script>

    <script>
        $(document).ready(function() {
            // Variables from dynamic Active Cabang / City Basecamp
            let BASECAMP_LAT = parseFloat("{{ $activeCabang->lat ?? config('services.location.basecamp_lat', -8.1711) }}");
            let BASECAMP_LNG = parseFloat("{{ $activeCabang->lng ?? $activeCabang->long ?? config('services.location.basecamp_long', 113.7233) }}");
            let CABANG_ID = {{ $activeCabang->id ?? 1 }};
            const BASE_FEE = parseFloat("{{ $tarifDasar->harga }}"); // Base fee for car

            // Listen for non-reload branch changes
            window.addEventListener('tohelp:cabang-changed', function(e) {
                if (e.detail && e.detail.lat && e.detail.lng) {
                    BASECAMP_LAT = parseFloat(e.detail.lat);
                    BASECAMP_LNG = parseFloat(e.detail.lng);
                    CABANG_ID = parseInt(e.detail.id);

                    if (typeof map !== 'undefined' && map) {
                        map.panTo({ lat: BASECAMP_LAT, lng: BASECAMP_LNG });
                    }

                    // If route is plotted, recalculate with new basecamp
                    if ($('#lat_awal').val() && $('#lat_akhir').val()) {
                        calculateDistances();
                    }
                }
            });

            // Tiered pricing for cars
            const TIER_1_MAX = 3; // 1-3 km
            const TIER_1_RATE = 18000; // 18rb/km
            const TIER_2_MAX = 10; // 4-10 km
            const TIER_2_RATE = 5000; // 5rb/km
            const TIER_3_RATE = 4000; // > 10 km rate (4rb/km)

            // Declare voucherDiscount at the script level so it's accessible in multiple functions
            let voucherDiscount = 0;
            let appliedVoucherCode = '';

            // Function to apply voucher
            function applyVoucher() {
                const voucherCode = $('#kode-voucher').val().trim();

                if (!voucherCode) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Silahkan masukkan kode voucher',
                        icon: 'error'
                    });
                    return;
                }

                // Check if same voucher is being applied again
                if (voucherCode === appliedVoucherCode) {
                    Swal.fire({
                        title: 'Info',
                        text: 'Voucher ini sudah diaplikasikan',
                        icon: 'info'
                    });
                    return;
                }

                // Show loading indicator
                Swal.fire({
                    title: 'Memproses',
                    text: 'Memeriksa kode voucher...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Send AJAX request to validate voucher
                $.ajax({
                    url: '{{ route('voucher.check') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        code: voucherCode
                    },
                    success: function(response) {
                        Swal.close();

                        if (response.status === 'success') {
                            $('#reset-voucher').show();
                            $('input#kode-voucher').prop('readonly', true);
                            // Save the applied voucher and discount percentage
                            voucherDiscount = response.data.persentase;
                            appliedVoucherCode = voucherCode;

                            // Display voucher info
                            $('#voucher-info').html(
                                `<div class="alert alert-success">
                        <strong>Voucher berhasil diaplikasikan!</strong><br>
                        Diskon ${voucherDiscount}% dari ${response.data.nama}
                    </div>`
                            ).show();

                            // Recalculate price with the discount
                            calculateRoute();

                            Swal.fire({
                                title: 'Berhasil',
                                text: `Voucher "${response.data.nama}" berhasil diaplikasikan! Diskon ${voucherDiscount}%`,
                                icon: 'success'
                            });
                        } else {
                            $('#voucher-info').html(
                                `<div class="alert alert-danger">
                        <strong>Voucher tidak valid!</strong><br>
                        ${response.message}
                    </div>`
                            ).show();

                            Swal.fire({
                                title: 'Gagal',
                                text: response.message || 'Voucher tidak valid',
                                icon: 'error'
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.close();

                        let errorMessage = 'Terjadi kesalahan saat memeriksa voucher';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        $('#voucher-info').html(
                            `<div class="alert alert-danger">
                    <strong>Error!</strong><br>
                    ${errorMessage}
                </div>`
                        ).show();

                        Swal.fire({
                            title: 'Error',
                            text: errorMessage,
                            icon: 'error'
                        });
                    }
                });
            }

            // Map variables
            let map;
            let markerAwal = null;
            let markerAkhir = null;
            let directionsService;
            let directionsRenderer;
            let distanceMatrixService;

            initMap();

            // Initialize Google Maps
            function initMap() {
                directionsService = new google.maps.DirectionsService();
                distanceMatrixService = new google.maps.DistanceMatrixService();
                directionsRenderer = new google.maps.DirectionsRenderer({
                    suppressMarkers: true,
                    polylineOptions: {
                        strokeColor: '#3388ff',
                        strokeWeight: 6
                    }
                });

                map = new google.maps.Map(document.getElementById('map'), {
                    center: {
                        lat: BASECAMP_LAT,
                        lng: BASECAMP_LNG
                    },
                    zoom: 13
                });

                directionsRenderer.setMap(map);

                // Enable location inputs after map is loaded
                $('#lokasi_awal, #lokasi_akhir').prop('disabled', false);

                // Add click listener to map for destination selection
                map.addListener('click', function(e) {
                    if (!markerAkhir) {
                        const lat = e.latLng.lat();
                        const lng = e.latLng.lng();
                        createMarker(lat, lng, false);
                        $('#lat_akhir').val(lat);
                        $('#lng_akhir').val(lng);
                        getAddressFromLatLng(lat, lng, 'lokasi_akhir');
                    }
                });

                // Setup autocomplete for inputs
                setupAutocomplete('lokasi_awal', true);
                setupAutocomplete('lokasi_akhir', false);
            }

            // Helper function: Calculate distance between two points using Google Distance Matrix API
            // Optimized for car (taxi) travel - uses DRIVING mode with highway access and traffic optimization
            function calculateDistanceMatrix(origins, destinations) {
                return new Promise((resolve, reject) => {
                    // travelMode "google.maps.TravelMode.DRIVING", or "google.maps.TravelMode.TWO_WHEELER"
                    // drivingOptions.trafficModel "google.maps.TrafficModel.BEST_GUESS", or "google.maps.TrafficModel.OPTIMISTIC"
                    const request = {
                        origins: origins,
                        destinations: destinations,
                        travelMode: google.maps.TravelMode.DRIVING, // Car travel mode
                        unitSystem: google.maps.UnitSystem.METRIC,
                        avoidHighways: false, // Cars can efficiently use highways
                        avoidTolls: false,
                        region: 'ID', // Indonesia region for better localization
                        language: 'id', // Indonesian language
                        drivingOptions: {
                            departureTime: new Date(), // Current time for real-time traffic
                            trafficModel: google.maps.TrafficModel.BEST_GUESS // Best traffic estimation
                        }
                    };

                    distanceMatrixService.getDistanceMatrix(request).then((response) => {
                        if (response.rows && response.rows.length > 0) {
                            const results = [];
                            for (let i = 0; i < response.rows.length; i++) {
                                const row = response.rows[i];
                                const rowResults = [];
                                for (let j = 0; j < row.elements.length; j++) {
                                    const element = row.elements[j];
                                    if (element.status === 'OK') {
                                        rowResults.push({
                                            distance: element.distance.value /
                                                1000, // Convert to km
                                            duration: element.duration.value /
                                                60, // Convert to minutes
                                            distanceText: element.distance.text,
                                            durationText: element.duration.text
                                        });
                                    } else {
                                        rowResults.push(null);
                                    }
                                }
                                results.push(rowResults);
                            }
                            resolve(results);
                        } else {
                            reject('No results from Distance Matrix API');
                        }
                    }).catch((error) => {
                        reject(error);
                    });
                });
            }

            // Calculate total price with the new tiered pricing model for cars
            function calculatePrice(routeDistance) {
                // Ceiling the distance to get proper tier calculation
                const roundedDistance = Math.ceil(routeDistance);
                let totalPrice = BASE_FEE; // Start with base fee

                // Apply tiered pricing
                if (roundedDistance <= TIER_1_MAX) {
                    // Tier 1: 1-3 km at 6000/km
                    totalPrice += roundedDistance * TIER_1_RATE;
                } else if (roundedDistance <= TIER_2_MAX) {
                    // Tier 2: 4-10 km at 5000/km
                    totalPrice += roundedDistance * TIER_2_RATE;
                } else {
                    // Tier 3: >10 km at 4000/km
                    totalPrice += roundedDistance * TIER_3_RATE;
                }

                return totalPrice;
            }

            // Get address from coordinates using Google Geocoder
            function getAddressFromLatLng(lat, lng, inputId) {
                const geocoder = new google.maps.Geocoder();
                const latlng = {
                    lat: parseFloat(lat),
                    lng: parseFloat(lng)
                };

                geocoder.geocode({
                    location: latlng
                }, (results, status) => {
                    if (status === "OK" && results[0]) {
                        $(`#${inputId}`).val(results[0].formatted_address);
                    } else {
                        $(`#${inputId}`).val(`${lat}, ${lng}`);
                    }
                    calculateRoute();
                });
            }

            // Setup autocomplete for location inputs
            function setupAutocomplete(inputId, isAwal) {
                const input = document.getElementById(inputId);
                const autocomplete = new google.maps.places.Autocomplete(input, {
                    types: ['geocode', 'establishment'],
                    fields: ['geometry', 'name', 'formatted_address', 'place_id'],
                    componentRestrictions: {
                        country: 'id'
                    }, // Restrict to Indonesia
                    bounds: new google.maps.LatLngBounds(
                        new google.maps.LatLng(BASECAMP_LAT - 0.1, BASECAMP_LNG - 0.1), // SW bounds
                        new google.maps.LatLng(BASECAMP_LAT + 0.1, BASECAMP_LNG + 0.1) // NE bounds
                    ),
                    strictBounds: false // Allow results outside bounds but prioritize within bounds
                });

                autocomplete.addListener('place_changed', function() {
                    const place = autocomplete.getPlace();

                    // Validate selection
                    if (!place.geometry || !place.formatted_address ||
                        place.formatted_address.toLowerCase().includes('undefined')) {
                        input.value = '';
                        alert('Mohon pilih lokasi yang valid dari daftar Google Maps');
                        return;
                    }

                    const lat = place.geometry.location.lat();
                    const lng = place.geometry.location.lng();

                    if (isAwal) {
                        $('#lat_awal').val(lat);
                        $('#lng_awal').val(lng);
                    } else {
                        $('#lat_akhir').val(lat);
                        $('#lng_akhir').val(lng);
                    }

                    createMarker(lat, lng, isAwal);
                    map.setCenter(place.geometry.location);
                    calculateRoute();
                });
            }

            function calculateRoute() {
                if (!markerAwal || !markerAkhir) return;

                const startPos = markerAwal.getPosition();
                const endPos = markerAkhir.getPosition();
                const basecampPos = new google.maps.LatLng(BASECAMP_LAT, BASECAMP_LNG);

                // Calculate distances using Distance Matrix API
                const origins = [basecampPos];
                const destinations = [startPos, endPos];

                calculateDistanceMatrix(origins, destinations).then((distanceResults) => {
                    const basecampToPickupDistance = distanceResults[0][0] ? distanceResults[0][0].distance
                        .toFixed(2) : '0';
                    const basecampToDestinationDistance = distanceResults[0][1] ? distanceResults[0][1]
                        .distance.toFixed(2) : '0';

                    const request = {
                        origin: startPos,
                        destination: endPos,
                        travelMode: google.maps.TravelMode.DRIVING, // Car travel mode
                        avoidHighways: false, // Cars can efficiently use highways
                        avoidTolls: false,
                        region: 'ID', // Indonesia region for better localization
                        language: 'id', // Indonesian language
                        provideRouteAlternatives: true, // Get alternative routes for better options
                        optimizeWaypoints: true, // Optimize route ordering
                        drivingOptions: {
                            departureTime: new Date(), // Current time for real-time traffic
                            trafficModel: google.maps.TrafficModel.BEST_GUESS // Best traffic estimation
                        }
                    };

                    directionsService.route(request, function(result, status) {
                        if (status === 'OK') {
                            directionsRenderer.setDirections(result);

                            const route = result.routes[0];
                            const routeDistance = (route.legs[0].distance.value / 1000).toFixed(2);
                            const duration = Math.round(route.legs[0].duration.value / 60);

                            // Calculate return trip distance (destination to pickup) in background
                            const returnRequest = {
                                origin: endPos,
                                destination: startPos,
                                travelMode: google.maps.TravelMode.DRIVING, // Car travel mode
                                avoidHighways: false, // Cars can efficiently use highways
                                avoidTolls: false,
                                region: 'ID', // Indonesia region for better localization
                                language: 'id', // Indonesian language
                                provideRouteAlternatives: true, // Get alternative routes for better options
                                optimizeWaypoints: true, // Optimize route ordering
                                drivingOptions: {
                                    departureTime: new Date(Date.now() + (30 * 60 *
                                        1000)), // Estimated departure time (30 min from now)
                                    trafficModel: google.maps.TrafficModel
                                        .BEST_GUESS // Best traffic estimation
                                }
                            };

                            directionsService.route(returnRequest, function(returnResult,
                                returnStatus) {
                                let returnDistance =
                                    routeDistance; // fallback to same distance

                                if (returnStatus === 'OK') {
                                    returnDistance = (returnResult.routes[0].legs[0]
                                        .distance.value / 1000).toFixed(2);
                                }

                                // Get rounded distance for display
                                const roundedDistance = Math.ceil(parseFloat(
                                    routeDistance));

                                // Calculate price using tiered pricing
                                let totalPrice = calculatePrice(parseFloat(routeDistance));

                                // Determine which rate is applied based on distance
                                let appliedRate;
                                if (roundedDistance <= TIER_1_MAX) {
                                    appliedRate = TIER_1_RATE;
                                } else if (roundedDistance <= TIER_2_MAX) {
                                    appliedRate = TIER_2_RATE;
                                } else {
                                    appliedRate = TIER_3_RATE;
                                }

                                // Voucher discount info for display (calculation will be done by backend)
                                let discountInfo = '';
                                if (voucherDiscount > 0) {
                                    discountInfo =
                                        `<br>Diskon Voucher: ${voucherDiscount}%`;
                                }

                                // Send both distances to the server including calculated return distance
                                $.ajax({
                                    url: `{{ route('taxi.show-pricing') }}`,
                                    method: 'POST',
                                    data: {
                                        _token: '{{ csrf_token() }}',
                                        jarakBaseCampKeTitikJemput: Math.ceil(
                                            basecampToPickupDistance),
                                        jarakTitikJemputKeTitikTujuan: Math.ceil(
                                            routeDistance),
                                        jarakBaseCampKeTitikTujuan: Math.ceil(
                                            basecampToDestinationDistance),
                                        jarakTitikTujuanKeTitikJemput: Math.ceil(
                                            returnDistance),
                                        discount: appliedVoucherCode || null,
                                    },
                                    success: function(response) {
                                        if (response.status === 'success') {
                                            // Backend already calculated final price including discount
                                            let finalPrice = response.harga;

                                            // Format price with thousand separators for Rupiah
                                            const formattedPrice = new Intl
                                                .NumberFormat('id-ID', {
                                                    style: 'currency',
                                                    currency: 'IDR',
                                                    minimumFractionDigits: 0,
                                                    maximumFractionDigits: 0
                                                }).format(finalPrice);
                                            // Update the route info with the response data
                                            $('#routeInfo').html(
                                                `Jarak Driver ke Titik Jemput: ${Math.ceil(
                                            basecampToPickupDistance)} km<br>
                            Jarak Perjalanan: <span id="distance">${roundedDistance}</span> km<br>
                            Estimasi waktu: <span id="duration">${duration}</span> menit<br>
                            ${discountInfo}<br>
                            Harga Total: <h1 id="totalPrice" class="text-success">${formattedPrice}</h1>`
                                            ).show();
                                        } else {
                                            // Handle error response
                                            $('#routeInfo').html(
                                                `<div class="alert alert-danger">
                                <strong>Error!</strong><br>
                                Maaf, terjadi kesalahan, silahkan coba lagi.
                            </div>`
                                            ).show();
                                        }
                                    },
                                    error: function(xhr) {
                                        let errorMessage =
                                            'Terjadi kesalahan saat menghitung harga';
                                        if (xhr.responseJSON && xhr.responseJSON
                                            .message) {
                                            errorMessage = xhr.responseJSON
                                                .message;
                                        }

                                        $('#routeInfo').html(
                                            `<div class="alert alert-danger">
                            <strong>Error!</strong><br>
                            ${errorMessage}
                        </div>`
                                        ).show();
                                    }
                                });
                            }); // Close return distance calculation callback
                        }
                    });
                }).catch((error) => {
                    console.error('Error calculating distances:', error);
                    $('#routeInfo').html(
                        `<div class="alert alert-danger">
                            <strong>Error!</strong><br>
                            Gagal menghitung jarak: ${error}
                        </div>`
                    ).show();
                });
            }

            // Add voucher reset functionality
            function resetVoucher() {
                voucherDiscount = 0;
                appliedVoucherCode = '';
                $('input#kode-voucher').prop('readonly', false);
                $('#kode-voucher').val('');
                $('#voucher-info').hide();
                calculateRoute();
            }

            // Add button event listeners
            $('#apply-voucher').click(applyVoucher);

            // Allow enter key to submit voucher
            $('#kode-voucher').keypress(function(e) {
                if (e.which === 13) {
                    applyVoucher();
                    e.preventDefault();
                }
            });

            // Create marker on the map
            function createMarker(lat, lng, isAwal) {
                const position = new google.maps.LatLng(lat, lng);
                const markerColor = isAwal ? '#00FF00' : '#FF0000';

                // SVG marker for better visibility
                const svgMarker = "data:image/svg+xml," + encodeURIComponent(`
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path fill="${markerColor}" 
                    d="M12 2a8 8 0 0 0-7.992 8A12.816 12.816 0 0 0 12 22a12.816 12.816 0 0 0 7.988-12A8 8 0 0 0 12 2zm0 11a3 3 0 1 1 3-3 3 3 0 0 1-3 3z"/>
            </svg>
        `);

                const markerOptions = {
                    position: position,
                    map: map,
                    draggable: true,
                    icon: {
                        url: svgMarker,
                        scaledSize: new google.maps.Size(40, 40),
                        anchor: new google.maps.Point(20, 20)
                    }
                };

                // Set marker and add drag event handler
                if (isAwal) {
                    if (markerAwal) markerAwal.setMap(null);
                    markerAwal = new google.maps.Marker(markerOptions);

                    markerAwal.addListener('dragend', function() {
                        const pos = markerAwal.getPosition();
                        $('#lat_awal').val(pos.lat());
                        $('#lng_awal').val(pos.lng());
                        getAddressFromLatLng(pos.lat(), pos.lng(), 'lokasi_awal');
                    });
                } else {
                    if (markerAkhir) markerAkhir.setMap(null);
                    markerAkhir = new google.maps.Marker(markerOptions);

                    markerAkhir.addListener('dragend', function() {
                        const pos = markerAkhir.getPosition();
                        $('#lat_akhir').val(pos.lat());
                        $('#lng_akhir').val(pos.lng());
                        getAddressFromLatLng(pos.lat(), pos.lng(), 'lokasi_akhir');
                    });
                }
            }

            // Get user's current location
            function getUserLocation() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            const lat = parseFloat(position.coords.latitude);
                            const lng = parseFloat(position.coords.longitude);

                            $('#lat_awal').val(lat);
                            $('#lng_awal').val(lng);

                            map.setCenter({
                                lat: lat,
                                lng: lng
                            });
                            map.setZoom(15);
                            createMarker(lat, lng, true);
                            getAddressFromLatLng(lat, lng, 'lokasi_awal');
                        },
                        function(error) {
                            alert("Error mendapatkan lokasi: " + error.message);
                        }
                    );
                } else {
                    alert("Geolocation tidak didukung oleh browser ini");
                }
            }

            // Validate location coordinates
            async function validateLocation(lat, lng) {
                if (!lat || !lng || isNaN(lat) || isNaN(lng)) return false;

                return new Promise((resolve) => {
                    const geocoder = new google.maps.Geocoder();
                    geocoder.geocode({
                        location: {
                            lat: parseFloat(lat),
                            lng: parseFloat(lng)
                        }
                    }, (results, status) => {
                        resolve(
                            status === google.maps.GeocoderStatus.OK &&
                            results &&
                            results.length > 0 &&
                            results[0].geometry &&
                            results[0].geometry.location_type !== 'APPROXIMATE' &&
                            results[0].formatted_address &&
                            !results[0].formatted_address.toLowerCase().includes(
                                'undefined')
                        );
                    });
                });
            }

            // Determine cabang based on location or active branch
            async function determineCabang(lat, lng) {
                return CABANG_ID;
            }

            // Modify the order click handler
            $('#order').click(async function() {
                const lat_awal = parseFloat($('#lat_awal').val());
                const lng_awal = parseFloat($('#lng_awal').val());
                const lat_akhir = parseFloat($('#lat_akhir').val());
                const lng_akhir = parseFloat($('#lng_akhir').val());

                // Calculate basecamp distances using Distance Matrix API
                const basecampPos = new google.maps.LatLng(BASECAMP_LAT, BASECAMP_LNG);
                const pickupPos = new google.maps.LatLng(lat_awal, lng_awal);
                const destinationPos = new google.maps.LatLng(lat_akhir, lng_akhir);

                const origins = [basecampPos];
                const destinations = [pickupPos, destinationPos];

                calculateDistanceMatrix(origins, destinations).then(async (distanceResults) => {
                    const basecampToPickupDistance = distanceResults[0][0] ?
                        distanceResults[0][0].distance.toFixed(2) : '0';
                    const basecampToDestinationDistance = distanceResults[0][1] ?
                        distanceResults[0][1].distance.toFixed(2) : '0';

                    // Validate both locations
                    const isStartValid = await validateLocation(lat_awal, lng_awal);
                    const isEndValid = await validateLocation(lat_akhir, lng_akhir);

                    if (!isStartValid || !isEndValid) {
                        alert(
                            'Lokasi tidak valid. Pastikan menggunakan lokasi tepat dari Google Maps.'
                        );
                        return;
                    }

                    // Determine cabang based on pickup location
                    const cabangId = await determineCabang(lat_awal, lng_awal);

                    // Calculate return distance for accurate pricing
                    const startPos = new google.maps.LatLng(lat_awal, lng_awal);
                    const endPos = new google.maps.LatLng(lat_akhir, lng_akhir);

                    const returnRequest = {
                        origin: endPos,
                        destination: startPos,
                        travelMode: google.maps.TravelMode.DRIVING, // Car travel mode
                        avoidHighways: false, // Cars can efficiently use highways
                        avoidTolls: false,
                        region: 'ID', // Indonesia region for better localization
                        language: 'id', // Indonesian language
                        provideRouteAlternatives: true, // Get alternative routes for better options
                        optimizeWaypoints: true, // Optimize route ordering
                        drivingOptions: {
                            departureTime: new Date(Date.now() + (30 * 60 *
                                1000)), // Estimated departure time (30 min from now)
                            trafficModel: google.maps.TrafficModel
                                .BEST_GUESS // Best traffic estimation
                        }
                    };

                    directionsService.route(returnRequest, function(returnResult,
                        returnStatus) {
                        let returnDistance = parseFloat($('#distance')
                            .text()); // fallback to forward distance

                        if (returnStatus === 'OK') {
                            returnDistance = (returnResult.routes[0].legs[0]
                                .distance.value / 1000).toFixed(2);
                        }

                        Swal.fire({
                            title: "Apakah anda yakin?",
                            text: "Apakah anda yakin ingin memesan jasa ini?",
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonText: "Ya, pesan!",
                            cancelButtonText: "Batal",
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $.ajax({
                                    url: `{{ route('taxi.pesan') }}`,
                                    method: 'POST',
                                    data: {
                                        _token: '{{ csrf_token() }}',
                                        jarak: parseFloat($(
                                                '#distance')
                                            .text()),
                                        jarakBaseCampKeTitikJemput: Math
                                            .ceil(
                                                basecampToPickupDistance
                                            ),
                                        jarakBaseCampKeTitikTujuan: Math
                                            .ceil(
                                                basecampToDestinationDistance
                                            ),
                                        jarakTitikTujuanKeTitikJemput: Math
                                            .ceil(returnDistance),
                                        voucher: appliedVoucherCode,
                                        titik_jemput: $(
                                                '#lokasi_awal')
                                            .val(),
                                        titik_tujuan: $(
                                                '#lokasi_akhir')
                                            .val(),
                                        cabang: cabangId
                                    },
                                    success: function(response) {
                                        if (response.status ===
                                            'success') {
                                            // console.log($('#totalPrice').text());

                                            Swal.fire({
                                                title: 'Berhasil',
                                                text: 'Pesanan berhasil dibuat, Anda akan diarahkan ke WhatsApp Admin',
                                                icon: 'success'
                                            }).then(() => {
                                                const
                                                    message =
                                                    `Hii, saya baru saja memesan To Help untuk meminta bantuan\n\n- Mobil\nID Order : ${response.order_id}\nTitik Penjemputan : ${$('#lokasi_awal').val()}\nTitik Pengantaran : ${$('#lokasi_akhir').val()}\nHarga : ${$('#totalPrice').text()}`;
                                                window
                                                    .open(
                                                        `https://api.whatsapp.com/send?phone=6285695908981&text=${encodeURIComponent(message)}`,
                                                        '_blank'
                                                    );
                                            });
                                        } else {
                                            Swal.fire({
                                                title: 'Gagal',
                                                text: 'Pesanan gagal dibuat, silahkan coba lagi',
                                                icon: 'error'
                                            });
                                        }
                                    },
                                    error: function() {
                                        Swal.fire({
                                            title: 'Gagal',
                                            text: 'Pesanan gagal dibuat, silahkan coba lagi',
                                            icon: 'error'
                                        });
                                    }
                                });
                            }
                        });
                    }); // Close return distance calculation callback
                }).catch((error) => {
                    console.error('Error calculating distances for order:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Gagal menghitung jarak. Silahkan coba lagi.',
                        icon: 'error'
                    });
                });
            });

            $('#reset-voucher').click(function() {
                resetVoucher();
                $(this).hide();
            });
        });
    </script>
@endpush
