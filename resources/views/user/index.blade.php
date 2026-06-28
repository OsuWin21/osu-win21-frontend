<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>osu!win21 - Home</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="{{ asset('assets/user/vendors/mdi/css/materialdesignicons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/user/vendors/css/vendor.bundle.base.css') }}">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <!-- endinject -->
    <!-- Layout styles -->
    <link rel="stylesheet" href="{{ asset('assets/user/css/style.css') }}">
    <!-- End layout styles -->
    <link rel="shortcut icon"
        href="https://cdn.discordapp.com/emojis/1199139517198770206.png?size=48&quality=lossless" />
</head>

<body>
    @include('user.layouts.alerts')
    <div class="container-fluid p-0">
        @include('user.layouts.navbar')
        <div class="page-body-wrapper">
            @include('user.layouts.sidebar')
            <div class="main-panel">
                <div class="content-wrapper px-5 bg-hero">
                    <div class="row align-items-center position-relative justify-content-center px-5">
                        <div class="col-lg-6">
                            <div class="btn btn-yellow company-badge mb-4">
                                #1 osu! Private Server
                            </div>

                            <h1 class="mb-4 fw-1">
                                osu!win21 <br>
                                Invite Only Server <br>
                                <span class="text-primary typing-effect" id="typing"></span>
                            </h1>

                            <p class="mb-4 mb-md-5">
                                osu!win21 is an Invite Only osu! Private Server with a passionate community, custom features, and enhanced gameplay. Join to compete, connect, and experience osu! in a new way—exclusively for invited players.
                            </p>
                            <a href="{{ route('register') }}" class="btn btn-primary me-0 me-sm-2 mx-1">Get Started</a>
                            <a href="#"
                                class="btn btn-link link-primary mt-2 mt-sm-0 glightbox">
                                Player Rangkings
                                <i class="mdi mdi-arrow-top-right"></i>
                            </a>
                        </div>

                        <div class="col-lg-6 d-flex justify-content-center">
                            <img src="{{ asset('https://cdn.discordapp.com/emojis/1199139517198770206.png?size=1024&quality=lossless') }}"
                                alt="Hero Image" class="img-fluid rotating" width="150px">

                            <div class="customers-badge bg-light">
                                <div class="customer-avatars">
                                    @foreach ($user as $item)
                                        <img src="{{ Storage::disk('public')->exists('avatars/' . $item->id . '.png') ? asset('storage/avatars/' . $item->id . '.png') : asset('storage/avatars/default.png') }}"
                                            alt="Customer 1" class="avatar">
                                    @endforeach
                                    <span class="avatar more">{{ $user_count += 10 }}+</span>
                                </div>
                                <p class="mb-0 mt-2 text-muted text-center">{{ $user_count += 10 }}+ Players Registered, Competing and Satisfied With Our Services, Join Now!</p>
                            </div>
                        </div>
                    </div>
                    <div class="row stats-row mt-5 py-5 bg-white text-center mx-5 shadow-lg justify-content-evenly" style="border-radius: 20px">
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="bi bi-briefcase"></i>
                                </div>
                                <div class="stat-content">
                                    <h4>Standard Vanilla PP Record</h4>
                                    <p class="mb-0">{{ number_format($vn_record ? $vn_record->pp : 0, 0) }}pp by <a class="link link-primary" href="u/{{ $vn_record ? $vn_record->id : '#' }}">{{ $vn_record ? $vn_record->name : 'No Record' }}</a></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="bi bi-graph-up"></i>
                                </div>
                                <div class="stat-content">
                                    <h4>Standard Relax PP Record</h4>
                                    <p class="mb-0">{{ number_format($rx_record->pp, 0) }}pp by <a class="link link-primary" href="u/{{ $rx_record->id }}?mode=0&rx=4">{{ $rx_record->name }}</a></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="bi bi-award"></i>
                                </div>
                                <div class="stat-content">
                                    <h4>Standard AutoPilot PP Record</h4>
                                    <p class="mb-0">{{ number_format($ap_record->pp, 0) }}pp by <a class="link link-primary" href="u/{{ $ap_record->id }}?mode=0&rx=8">{{ $ap_record->name }}</a></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('user.layouts.footer')
    </div>

    {{-- Chart Data --}}
    {{-- <script>
        var areaData = {
            labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October',
                'November', 'December'
            ],
            datasets: [{
                label: 'Users',
                data: {!! json_encode($reg_data) !!},
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                    'rgba(255, 159, 64, 0.2)'
                ],
                borderColor: [
                    'rgba(255,99,132,1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1,
                fill: true, // 3: no fill
            }]
        };
        var areaData2 = {
            labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October',
                'November', 'December'
            ],
            datasets: [{
                label: 'Users',
                data: {!! json_encode($login_data) !!},
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                    'rgba(255, 159, 64, 0.2)'
                ],
                borderColor: [
                    'rgba(255,99,132,1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1,
                fill: true, // 3: no fill
            }]
        };
    </script> --}}

    {{-- Data Fetch --}}
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="{{ asset('assets/user/js/data-fetch.js') }}"></script>
    <!-- container-scroller -->
    <!-- plugins:js -->
    <script src="{{ asset('assets/user/vendors/js/vendor.bundle.base.js') }}"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <script src="{{ asset('assets/user/vendors/chart.js/Chart.min.js') }}"></script>
    <script src="{{ asset('assets/user/js/jquery.cookie.js') }}" type="text/javascript"></script>
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="{{ asset('assets/user/js/off-canvas.js') }}"></script>
    <script src="{{ asset('assets/user/js/hoverable-collapse.js') }}"></script>
    <script src="{{ asset('assets/user/js/misc.js') }}"></script>
    <!-- endinject -->
    <!-- Custom js for this page -->
    <script src="{{ asset('assets/user/js/chart.js') }}"></script>
    <script src="{{ asset('assets/user/js/dashboard.js') }}"></script>
    <script src="{{ asset('assets/user/js/todolist.js') }}"></script>
    <script src="{{ asset('assets/user/js/error.js') }}"></script>
    <script src="{{ asset('assets/user/js/typing-effect.js') }}"></script>
    <!-- End custom js for this page -->
</body>

</html>
