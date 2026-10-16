<?php
include 'header.php';
include 'koneksi.php';

// Proses Tambah Data User
if (isset($_POST['tambah'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);
    $role     = mysqli_real_escape_string($koneksi, $_POST['role']);

    $query = mysqli_query($koneksi, "INSERT INTO user (username, password, role) VALUES ('$username', '$password', '$role')");
    if ($query) {
        echo "<script>alert('Data user berhasil ditambahkan!'); window.location='user.php';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan data user!');</script>";
    }
}

// Proses Edit Data User
if (isset($_POST['edit'])) {
    $user_id  = $_POST['user_id'];
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $role     = mysqli_real_escape_string($koneksi, $_POST['role']);
    
    // Cek apakah password diisi atau tidak
    if (!empty($_POST['password'])) {
        $password = mysqli_real_escape_string($koneksi, $_POST['password']);
        $query = mysqli_query($koneksi, "UPDATE user SET username='$username', password='$password', role='$role' WHERE user_id='$user_id'");
    } else {
        $query = mysqli_query($koneksi, "UPDATE user SET username='$username', role='$role' WHERE user_id='$user_id'");
    }

    if ($query) {
        echo "<script>alert('Data user berhasil diubah!'); window.location='user.php';</script>";
    } else {
        echo "<script>alert('Gagal mengubah data user!');</script>";
    }
}

// Proses Hapus Data User
if (isset($_GET['hapus'])) {
    $user_id = $_GET['hapus'];
    $query = mysqli_query($koneksi, "DELETE FROM user WHERE user_id='$user_id'");
    if ($query) {
        echo "<script>alert('Data user berhasil dihapus!'); window.location='user.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data user!'); window.location='user.php';</script>";
    }
}

// Logika Pencarian
$search = '';
if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($koneksi, $_GET['search']);
    $query_user = mysqli_query($koneksi, "SELECT * FROM user WHERE username LIKE '%$search%' OR role LIKE '%$search%' ORDER BY user_id DESC");
} else {
    $query_user = mysqli_query($koneksi, "SELECT * FROM user ORDER BY user_id DESC");
}
?>

<style>
    .card-custom {
        border: none;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        background: #ffffff;
        padding: 25px;
    }
    .table-custom th {
        color: #718096;
        font-weight: 600;
        border-bottom: 2px solid #edf2f7;
        background: transparent !important;
    }
    .table-custom td {
        color: #2d3748;
        vertical-align: middle;
        border-bottom: 1px solid #f7fafc;
        padding: 14px 10px;
    }
    .btn-pastel-primary {
        background-color: #b5ead7;
        border: none;
        color: #2d3748;
        font-weight: 500;
        border-radius: 10px;
        padding: 8px 16px;
        transition: 0.2s;
    }
    .btn-pastel-primary:hover {
        background-color: #97d4be;
        color: #000;
    }
    .btn-action-edit {
        background-color: #fff3cd;
        color: #856404;
        border: none;
        border-radius: 8px;
        padding: 5px 10px;
    }
    .btn-action-edit:hover {
        background-color: #ffeeba;
    }
    .btn-action-delete {
        background-color: #f8d7da;
        color: #721c24;
        border: none;
        border-radius: 8px;
        padding: 5px 10px;
    }
    .btn-action-delete:hover {
        background-color: #f5c6cb;
    }
    .form-control, .form-select {
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        padding: 10px 14px;
    }
    .form-control:focus, .form-select:focus {
        box-shadow: none;
        border-color: #b5ead7;
    }
    .modal-content {
        border-radius: 20px;
        border: none;
        padding: 10px;
    }
</style>

<!-- Header Bagian Konten & Tombol Tambah -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Data User</h4>
        <p class="text-muted mb-0 small">Kelola informasi akun pengguna sistem toko Anda di sini.</p>
    </div>
    <button type="button" class="btn btn-pastel-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-lg me-1"></i> Tambah User
    </button>
</div>

<!-- Card Tabel & Search Bar -->
<div class="card card-custom">
    <div class="row align-items-center justify-content-between mb-4 g-3">
        <div class="col-auto">
            <h5 class="fw-bold mb-0 text-dark d-flex align-items-center">
                <i class="bi bi-person-badge me-2 text-muted"></i> Daftar User
            </h5>
        </div>
        <div class="col-md-4">
            <!-- Form Search -->
            <form method="GET" action="" class="d-flex">
                <input type="text" name="search" class="form-control form-control-sm me-2" placeholder="Cari username atau role" value="<?= htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-outline-secondary btn-sm rounded-10 px-3"><i class="bi bi-search"></i></button>
                <?php if (!empty($search)): ?>
                    <a href="user.php" class="btn btn-outline-danger btn-sm ms-2 rounded-10 d-flex align-items-center" title="Reset Pencarian"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Tanggal Dibuat</th>
                    <th class="text-center" width="15%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (mysqli_num_rows($query_user) > 0) {
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($query_user)) {
                ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td class="fw-semibold"><i class="bi bi-person-circle me-2 text-secondary"></i><?= htmlspecialchars($row['username']); ?></td>
                    <td>
                        <span class="badge bg-<?= ($row['role'] == 'admin') ? 'success' : 'secondary'; ?> text-uppercase" style="font-size: 0.75rem;">
                            <?= htmlspecialchars($row['role']); ?>
                        </span>
                    </td>
                    <td class="text-muted small"><?= htmlspecialchars($row['created_at']); ?></td>
                    <td class="text-center">
                        <!-- Tombol Trigger Modal Edit -->
                        <button type="button" class="btn btn-action-edit btn-sm shadow-sm me-1" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['user_id']; ?>" title="Edit">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                        <!-- Tombol Hapus -->
                        <a href="user.php?hapus=<?= $row['user_id']; ?>" class="btn btn-action-delete btn-sm shadow-sm" onclick="return confirm('Yakin ingin menghapus data user ini?')" title="Hapus">
                            <i class="bi bi-trash-fill"></i>
                        </a>
                    </td>
                </tr>

                <!-- Modal Edit User -->
                <div class="modal fade" id="modalEdit<?= $row['user_id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="fw-bold text-dark">Edit Data User</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST" action="">
                                <div class="modal-body text-start">
                                    <input type="hidden" name="user_id" value="<?= $row['user_id']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-muted">Username</label>
                                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($row['username']); ?>" required autocomplete="off">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-muted">Password Baru <span class="text-danger fw-normal">(Kosongkan jika tidak diubah)</span></label>
                                        <input type="password" name="password" class="form-control" placeholder="••••••••">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-muted">Role</label>
                                        <select name="role" class="form-select" required>
                                            <option value="admin" <?= ($row['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                            <option value="kasir" <?= ($row['role'] == 'kasir') ? 'selected' : ''; ?>>Kasir</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer border-0">
                                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" name="edit" class="btn btn-pastel-primary rounded-pill px-4">Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php 
                    }
                } else {
                    echo '<tr><td colspan="5" class="text-center py-4 text-muted">Belum ada data user atau data tidak ditemukan.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah User -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="fw-bold text-dark">Tambah User Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="admin">Admin</option>
                            <option value="kasir" selected>Kasir</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-pastel-primary rounded-pill px-4">Tambah</button>
                </div>
            </form>
        </div>
    </div>
</div>

</div> <!-- Penutup div class="main-content" dari header.php -->

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>