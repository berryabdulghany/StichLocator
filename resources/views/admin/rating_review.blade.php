<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Dashboard Admin | Rating</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="{{ asset('css/styles.css') }}" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .rating-stars {
            color: #ffc107;
            font-size: 1.2rem;
        }
        .card {
            border: none;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: none;
            padding: 1.5rem;
            font-weight: 600;
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2e59d9;
        }
        .table th {
            font-weight: 600;
            background-color: #f8f9fa;
        }
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78,115,223,0.25);
        }
        .alert {
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        .modal-content {
            border-radius: 10px;
            border: none;
        }
        .modal-header {
            background-color: #f8f9fa;
            border-bottom: none;
        }
        .btn-action {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 0.25rem;
            margin: 0 0.25rem;
        }
        .rating-input {
            display: flex;
            gap: 0.5rem;
        }
        .rating-star {
            cursor: pointer;
            font-size: 1.5rem;
            color: #ddd;
            transition: color 0.2s;
        }
        .rating-star.active {
            color: #ffc107;
        }
        .star-rating {
            display: inline-flex;
            gap: 0.25rem;
        }
        .star-rating .fas.fa-star {
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star-rating .fas.fa-star.active {
            color: #ffc107;
        }
    </style>
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
                        {{ Auth::guard('admin')->user()->name }}
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
                        {{ Auth::guard('admin')->user()->name }}
                    </div>
                </nav>
            </div>
        <!-- Main Content -->
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Data Rating</h1>
                        <button class="btn btn-primary" onclick="showAddForm()">
                            <i class="fas fa-plus me-2"></i>Tambah Rating
                        </button>
                    </div>

                    <!-- Alert Messages -->
                    <div id="alertContainer"></div>

                    <!-- Rating Form Card -->
                    <div class="card mb-4" id="formCard" style="display: none;">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-star me-2"></i>Tambah Rating Baru</span>
                            <button type="button" class="btn-close" onclick="hideAddForm()"></button>
                        </div>
                        <div class="card-body">
                            <form id="formRating" class="needs-validation" novalidate>
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="namaPenjahit" class="form-label">Nama Penjahit</label>
                                        <select class="form-select" id="namaPenjahit" name="location_id" required>
                                            <option value="">-- Pilih Penjahit --</option>
                                            @foreach($locations ?? [] as $location)
                                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback">
                                            Silakan pilih penjahit
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Rating</label>
                                        <div class="star-rating" id="ratingStars">
                                            <i class="fas fa-star" data-value="1"></i>
                                            <i class="fas fa-star" data-value="2"></i>
                                            <i class="fas fa-star" data-value="3"></i>
                                            <i class="fas fa-star" data-value="4"></i>
                                            <i class="fas fa-star" data-value="5"></i>
                                        </div>
                                        <input type="hidden" id="rating" name="rating" value="5" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="ulasan" class="form-label">Review</label>
                                    <textarea class="form-control" id="ulasan" name="review" rows="4" placeholder="Tulis review anda di sini..." required></textarea>
                                    <div class="invalid-feedback">
                                        Review tidak boleh kosong
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="button" class="btn btn-secondary me-2" onclick="hideAddForm()">Batal</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Simpan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Ratings Table Card -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-table me-2"></i>Daftar Rating
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nama Penjahit</th>
                                            <th>Rating</th>
                                            <th>Review</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabelRating"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="editId">
                        <div class="mb-3">
                            <label class="form-label">Nama Penjahit</label>
                            <input type="text" class="form-control" id="editNamaPenjahit" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rating</label>
                            <div class="star-rating" id="editRatingStars">
                                <i class="fas fa-star" data-value="1"></i>
                                <i class="fas fa-star" data-value="2"></i>
                                <i class="fas fa-star" data-value="3"></i>
                                <i class="fas fa-star" data-value="4"></i>
                                <i class="fas fa-star" data-value="5"></i>
                            </div>
                            <input type="hidden" id="editRating" name="rating" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Review</label>
                            <textarea class="form-control" id="editUlasan" name="review" rows="4" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="updateReview()">
                        <i class="fas fa-save me-2"></i>Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus review ini?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">
                        <i class="fas fa-trash-alt me-2"></i>Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    @vite(['resources/js/app.js'])
    <script>
        let editModal;
        let deleteModal;
        let currentDeleteId;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Escape teks sebelum dimasukkan ke innerHTML (mencegah XSS)
        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        };

        document.addEventListener("DOMContentLoaded", function() {
            fetchLocations();
            fetchReviews();
            editModal = new bootstrap.Modal(document.getElementById('editModal'));
            deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            setupStarRatings();
        });

        function setupStarRatings() {
            setupStarRating('ratingStars', 'rating');
            setupStarRating('editRatingStars', 'editRating');
        }

        function setupStarRating(containerId, inputId) {
            const container = document.getElementById(containerId);
            const stars = container.getElementsByClassName('fa-star');
            const input = document.getElementById(inputId);

            Array.from(stars).forEach(star => {
                star.addEventListener('mouseover', function() {
                    const value = this.dataset.value;
                    highlightStars(stars, value);
                });

                star.addEventListener('mouseout', function() {
                    const currentValue = input.value;
                    highlightStars(stars, currentValue);
                });

                star.addEventListener('click', function() {
                    const value = this.dataset.value;
                    input.value = value;
                    highlightStars(stars, value);
                });
            });

            highlightStars(stars, input.value);
        }

        function highlightStars(stars, value) {
            Array.from(stars).forEach(star => {
                star.classList.toggle('active', star.dataset.value <= value);
            });
        }

        function showAddForm() {
            document.getElementById('formCard').style.display = 'block';
            document.getElementById('rating').value = 5;
            highlightStars(document.querySelectorAll('#ratingStars .fa-star'), 5);
        }

        function hideAddForm() {
            document.getElementById('formCard').style.display = 'none';
            document.getElementById('formRating').reset();
        }

        function showAlert(message, type) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            document.getElementById('alertContainer').innerHTML = alertHtml;
            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) {
                    alert.remove();
                }
            }, 3000);
        }

        function fetchLocations() {
            fetch('/admin/api/locations', { 
                method: 'GET',
                headers: headers
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`); 
                }
                return response.json();
            })
            .then(data => {
                let select = document.getElementById("namaPenjahit");
                if (!select) return;
                let options = `<option value="">-- Pilih Penjahit --</option>`;
                if (Array.isArray(data)) {
                    data.forEach(location => {
                        options += `<option value="${Number(location.id)}">${escapeHtml(location.name)}</option>`;
                    });
                }
                select.innerHTML = options;
            })
            .catch(error => {
                console.error("Gagal memuat data lokasi:", error);
                showAlert("Gagal memuat data penjahit. Cek konsol untuk detail.", "danger");
            });
        }


        function fetchReviews() {
            fetch('/admin/api/reviews', {
                method: 'GET',
                headers: headers
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                let tabel = document.getElementById("tabelRating");
                if (!tabel) return;
                tabel.innerHTML = "";

                if (Array.isArray(data)) {
                    data.forEach(item => {
                        let stars = '';
                        for (let i = 1; i <= 5; i++) {
                            stars += `<i class="fas fa-star ${i <= item.rating ? 'rating-stars' : 'text-muted'}"></i>`;
                        }

                        let row = tabel.insertRow();
                        row.innerHTML = `
                            <td>${escapeHtml(item.nama_penjahit)}</td>
                            <td>${stars}</td>
                            <td>${escapeHtml(item.review)}</td>
                            <td>
                                <button class="btn btn-warning btn-action" onclick="showEditModal(${Number(item.id)})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-action" onclick="showDeleteModal(${Number(item.id)})">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        `;
                    });
                }
            })
            .catch(error => {
                console.error("Gagal memuat data review:", error);
                showAlert("Gagal memuat data review. Cek konsol untuk detail.", "danger");
            });
        }

        document.getElementById("formRating").addEventListener("submit", function(event) {
            event.preventDefault();
            if (!this.checkValidity()) {
                event.stopPropagation();
                this.classList.add('was-validated');
                return;
            }

            const formData = {
                location_id: document.getElementById("namaPenjahit").value,
                rating: document.getElementById("rating").value,
                review: document.getElementById("ulasan").value,
                _token: csrfToken
            };

            fetch('/admin/api/reviews', {
                method: 'POST',
                headers: headers,
                body: JSON.stringify(formData)
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                showAlert('Review berhasil disimpan!', 'success');
                this.reset();
                hideAddForm();
                fetchReviews();
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Gagal menyimpan review', 'danger');
            });
        });

        function showEditModal(id) {
            fetch(`/admin/api/reviews/${id}`, {
                method: 'GET',
                headers: headers
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                document.getElementById("editId").value = data.id;
                document.getElementById("editNamaPenjahit").value = data.nama_penjahit;
                document.getElementById("editRating").value = data.rating;
                document.getElementById("editUlasan").value = data.review;
                highlightStars(document.querySelectorAll('#editRatingStars .fa-star'), data.rating);
                editModal.show();
            })
            .catch(error => {
                console.error("Gagal mengambil data review:", error);
                showAlert("Gagal mengambil data review", "danger");
            });
        }

        function showDeleteModal(id) {
            currentDeleteId = id;
            deleteModal.show();
        }

        document.getElementById("confirmDelete").addEventListener("click", function() {
            deleteReview(currentDeleteId);
            deleteModal.hide();
        });

        function updateReview() {
            const id = document.getElementById("editId").value;
            const formData = {
                rating: document.getElementById("editRating").value,
                review: document.getElementById("editUlasan").value,
                _token: csrfToken,
                _method: 'PUT'
            };

            fetch(`/admin/api/reviews/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(formData)
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                showAlert('Review berhasil diupdate!', 'success');
                editModal.hide();
                fetchReviews();
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Gagal mengupdate review', 'danger');
            });
        }

        function deleteReview(id) {
            const formData = {
                _token: csrfToken,
                _method: 'DELETE'
            };

            fetch(`/admin/api/reviews/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(formData)
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                showAlert('Review berhasil dihapus!', 'success');
                fetchReviews();
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Gagal menghapus review', 'danger');
            });
        }
    </script>
</body>
</html>