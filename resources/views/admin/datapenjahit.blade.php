<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Dashboard Admin | Penjahit</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="{{ asset('css/styles.css') }}" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body class="sb-nav-fixed">
            <!-- Navbar-->
            <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
            <!-- Navbar Brand-->
            <a class="navbar-brand ps-3" href="{{ route('admin.dashboard') }}">
                <i class="fas fa-scissors me-2"></i>
                Admin Panel
            </a>
            <!-- Sidebar Toggle-->
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!">
                <i class="fas fa-bars"></i>
            </button>
            <!-- Navbar Search
            <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
                <div class="input-group">
                    <input class="form-control" type="text" placeholder="Search for..." aria-label="Search for..." aria-describedby="btnNavbarSearch" />
                    <button class="btn btn-primary" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
                </div>
            </form> -->
            <!-- Navbar-->
            <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle fa-fw"></i>
                        @auth('admin')
                            {{ Auth::guard('admin')->user()->name }}
                        @endauth
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="#!"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><a class="dropdown-item" href="#!"><i class="fas fa-list me-2"></i>Activity Log</a></li>
                        <li><hr class="dropdown-divider" /></li>
                        <li>
                            <form action="{{ route('admin.logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
        <div id="layoutSidenav">
            <div id="layoutSidenav_nav">
                <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                    <div class="sb-sidenav-menu">
                        <div class="nav">
                            <div class="sb-sidenav-menu-heading">Core</div>
                            <a class="nav-link" href="{{ route('admin.dashboard') }}">
                                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                Dashboard
                            </a>
                            <div class="sb-sidenav-menu-heading">Management</div>
                            <a class="nav-link" href="{{ route('admin.users') }}">
                                <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                Data Users
                            </a>
                            <a class="nav-link" href="{{ route('datapenjahit') }}">
                                <div class="sb-nav-link-icon"><i class="fas fa-user-tie"></i></div>
                                Data Penjahit
                            </a>
                            <div class="sb-sidenav-menu-heading">Analytics</div>
                            <a class="nav-link" href="{{ route('rating_review') }}">
                                <div class="sb-nav-link-icon"><i class="fas fa-star"></i></div>
                                Rating dan Review
                            </a>
                        </div>
                    </div>
                    <div class="sb-sidenav-footer">
                        <div class="small">Logged in as:</div>
                        @auth('admin')
                            {{ Auth::guard('admin')->user()->name }}
                        @endauth
                    </div>
                </nav>
            </div>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Data Penjahit</h1>
                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-table me-1"></i> Tambah/Edit Data Penjahit</div>
                        <div class="card-body">
                            @if (session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                            @endif
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <form id="formPenjahit" action="{{ isset($location) ? route('penjahit.update', $location->id) : route('penjahit.store') }}" method="POST">
                                @csrf
                                @if (isset($location))
                                    @method('PUT')
                                    <input type="hidden" id="editIndex" name="id" value="{{ $location->id }}">
                                @endif
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nama Tempat Jahit</label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $location->name ?? '') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="address" class="form-label">Alamat</label>
                                    <input type="text" class="form-control" id="address" name="address" value="{{ old('address', $location->address ?? '') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="telepon" class="form-label">Nomor Telepon</label>
                                    <input type="text" class="form-control" id="telepon" name="telepon" value="{{ old('telepon', $location->telepon ?? '') }}">
                                </div>
                                <div class="mb-3">
                                    <label for="image_url" class="form-label">URL Gambar</label>
                                    <input type="text" class="form-control" id="image_url" name="image_url" value="{{ old('image_url', $location->image_url ?? '') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="lat" class="form-label">Latitude</label>
                                    <input type="text" class="form-control" id="lat" name="lat" value="{{ old('lat', $location->lat ?? '') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="lng" class="form-label">Longitude</label>
                                    <input type="text" class="form-control" id="lng" name="lng" value="{{ old('lng', $location->lng ?? '') }}" required>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="opening_hours_start" class="form-label">Jam Buka</label>
                                        <input type="text" class="form-control timepicker" id="opening_hours_start" name="opening_hours_start" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="opening_hours_end" class="form-label">Jam Tutup</label>
                                        <input type="text" class="form-control timepicker" id="opening_hours_end" name="opening_hours_end" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">{{ isset($location) ? 'Update' : 'Tambah' }}</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-table me-1"></i> Daftar Penjahit</div>
                        <div class="card-body">
                            <table class="table table-bordered" id="dataTable">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Rating</th>
                                        <th>Jumlah Review</th>
                                        <th>Alamat</th>
                                        <th>No telepon</th>
                                        <th>Status</th>
                                        <th>Latitude</th>
                                        <th>Longitude</th>
                                        <th>Jam Buka</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach ($locations as $location)
                                    <tr>
                                        <td>{{ $location->name }}</td>
                                        <td>{{ $location->rating ?? 'Belum ada rating' }}</td>
                                        <td>{{ $location->reviews }}</td>
                                        <td>{{ $location->address }}</td>
                                        <td>{{ $location->telepon }}</td>
                                        <td>
                                            <span class="{{ $location->dynamic_status == 'Buka' ? 'text-success' : 'text-danger' }}">
                                                {{ $location->dynamic_status }}
                                            </span>
                                        </td>
                                        <td>{{ $location->lat }}</td>
                                        <td>{{ $location->lng }}</td>
                                        <td>{{ $location->opening_hours }}</td>
                                        <td>
                                            <!-- Tombol Edit -->
                                            <form action="{{ route('penjahit.update', $location->id) }}" method="POST" style="display:inline-block;">
                                                @csrf
                                                @method('PUT')
                                                <button type="button" class="btn btn-warning btn-sm" onclick="editLocation({{ Js::from($location) }})">Edit</button>
                                            </form>

                                            <!-- Tombol Hapus -->
                                            <form action="{{ route('penjahit.destroy', $location->id) }}" method="POST" style="display:inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Hapus data penjahit ini beserta semua ulasannya?')">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="{{ asset('js/scripts.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script src="{{ asset('js/datatables-simple-demo.js') }}"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.13.18/jquery.timepicker.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.13.18/jquery.timepicker.min.css">

    <script>
        $(document).ready(function(){
            $('.timepicker').timepicker({
                timeFormat: 'H:i',
                interval: 30, // 30 minutes
                minTime: '00:00',
                maxTime: '23:30',
                defaultTime: '08:00',
                startTime: '00:00',
                dynamic: false,
                dropdown: true,
                scrollbar: true
            });
        });

        function editLocation(location) {
            document.getElementById('formPenjahit').action = @js(url('admin/penjahit')) + '/' + location.id;
            document.getElementById('formPenjahit').method = "POST";

            let existingMethodInput = document.querySelector("input[name='_method']");
            if (existingMethodInput) {
                existingMethodInput.remove();
            }

            let methodInput = document.createElement("input");
            methodInput.type = "hidden";
            methodInput.name = "_method";
            methodInput.value = "PUT";
            document.getElementById('formPenjahit').appendChild(methodInput);

            document.getElementById('name').value = location.name;
            document.getElementById('address').value = location.address;
            document.getElementById('telepon').value = location.telepon;

            document.getElementById('image_url').value = location.image_url;
            document.getElementById('lat').value = location.lat;
            document.getElementById('lng').value = location.lng;

            if (location.opening_hours && location.opening_hours.includes('-')) {
                const [start, end] = location.opening_hours.split('-').map(t => t.trim());
                $('#opening_hours_start').timepicker('setTime', start);
                $('#opening_hours_end').timepicker('setTime', end);
            } else {
                $('#opening_hours_start').timepicker('setTime', '08:00');
                $('#opening_hours_end').timepicker('setTime', '17:00');
            }

            window.scrollTo({
                top: document.getElementById('formPenjahit').offsetTop - 50, 
                behavior: 'smooth'
            });
        }
    </script>
</body>
