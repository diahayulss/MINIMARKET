<?php
include 'header_kasir.php';
include 'koneksi.php';

// Proses Tambah Data Pelanggan
if (isset($_POST['tambah'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['NamaPelanggan']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['Alamat']);
    $telepon = mysqli_real_escape_string($koneksi, $_POST['NomotTelepon']);

    $query = mysqli_query($koneksi, "INSERT INTO pelanggan (NamaPelanggan, Alamat, NomotTelepon) VALUES ('$nama', '$alamat', '$telepon')");
    if ($query) {
        echo "<script>alert('Data pelanggan berhasil ditambahkan!'); window.location='pelanggan_kasir.php';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan data!');</script>";
    }
}

// Proses Edit Data Pelanggan
if (isset($_POST['edit'])) {
    $id = $_POST['PelangganID'];
    $nama = mysqli_real_escape_string($koneksi, $_POST['NamaPelanggan']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['Alamat']);
    $telepon = mysqli_real_escape_string($koneksi, $_POST['NomotTelepon']);

    $query = mysqli_query($koneksi, "UPDATE pelanggan SET NamaPelanggan='$nama', Alamat='$alamat', NomotTelepon='$telepon' WHERE PelangganID='$id'");
    if ($query) {
        echo "<script>alert('Data pelanggan berhasil diubah!'); window.location='pelanggan_kasir.php';</script>";
    } else {
        echo "<script>alert('Gagal mengubah data!');</script>";
    }
}

// Proses Hapus Data Pelanggan (Menggunakan try-catch untuk menangkap Foreign Key Error)
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    try {
        $query = mysqli_query($koneksi, "DELETE FROM pelanggan WHERE PelangganID='$id'");
        if ($query) {
            echo "<script>alert('Data pelanggan berhasil dihapus!'); window.location='pelanggan_kasir.php';</script>";
        }
    } catch (mysqli_sql_exception $e) {
        echo "<script>alert('Gagal menghapus data! Pelanggan ini masih memiliki riwayat transaksi di tabel penjualan.'); window.location='pelanggan_kasir.php';</script>";
    }
}

// Logika Pencarian
$search = '';
if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($koneksi, $_GET['search']);
    $query_pelanggan = mysqli_query($koneksi, "SELECT * FROM pelanggan WHERE NamaPelanggan LIKE '%$search%' OR Alamat LIKE '%$search%' OR NomotTelepon LIKE '%$search%' ORDER BY PelangganID DESC");
} else {
    $query_pelanggan = mysqli_query($koneksi, "SELECT * FROM pelanggan ORDER BY PelangganID DESC");
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
    .form-control:focus {
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
        <h4 class="fw-bold mb-1 text-dark">Data Pelanggan</h4>
        <p class="text-muted mb-0 small">Kelola informasi data pelanggan toko Anda di sini.</p>
    </div>
    <button type="button" class="btn btn-pastel-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-lg me-1"></i> Tambah Pelanggan
    </button>
</div>

<!-- Card Tabel & Search Bar -->
<div class="card card-custom">
    <div class="row align-items-center justify-content-between mb-4 g-3">
        <div class="col-auto">
            <h5 class="fw-bold mb-0 text-dark d-flex align-items-center">
                <i class="bi bi-people me-2 text-muted"></i> Daftar Pelanggan
            </h5>
        </div>
        <div class="col-md-4">
            <!-- Form Search -->
            <form method="GET" action="" class="d-flex">
                <input type="text" name="search" class="form-control form-control-sm me-2" placeholder="Cari nama, alamat, no telp..." value="<?= htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-outline-secondary btn-sm rounded-10 px-3"><i class="bi bi-search"></i></button>
                <?php if (!empty($search)): ?>
                    <a href="pelanggan_kasir.php" class="btn btn-outline-danger btn-sm ms-2 rounded-10 d-flex align-items-center" title="Reset Pencarian"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Nama Pelanggan</th>
                    <th>Alamat</th>
                    <th>Nomor Telepon</th>
                    <th class="text-center" width="15%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (mysqli_num_rows($query_pelanggan) > 0) {
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($query_pelanggan)) {
                ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($row['NamaPelanggan']); ?></td>
                    <td><?= htmlspecialchars($row['Alamat']); ?></td>
                    <td><?= htmlspecialchars($row['NomotTelepon']); ?></td>
                    <td class="text-center">
                        <!-- Tombol Trigger Modal Edit -->
                        <button type="button" class="btn btn-action-edit btn-sm shadow-sm me-1" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['PelangganID']; ?>" title="Edit">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                        <!-- Tombol Hapus -->
                        <a href="pelanggan_kasir.php?hapus=<?= $row['PelangganID']; ?>" class="btn btn-action-delete btn-sm shadow-sm" onclick="return confirm('Yakin ingin menghapus data pelanggan ini?')" title="Hapus">
                            <i class="bi bi-trash-fill"></i>
                        </a>
                    </td>
                </tr>

                <!-- Modal Edit Pelanggan -->
                <div class="modal fade" id="modalEdit<?= $row['PelangganID']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="fw-bold text-dark">Edit Data Pelanggan</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST" action="">
                                <div class="modal-body text-start">
                                    <input type="hidden" name="PelangganID" value="<?= $row['PelangganID']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-muted">Nama Pelanggan</label>
                                        <input type="text" name="NamaPelanggan" class="form-control" value="<?= htmlspecialchars($row['NamaPelanggan']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-muted">Alamat</label>
                                        <textarea name="Alamat" class="form-control" rows="3" required><?= htmlspecialchars($row['Alamat']); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-muted">Nomor Telepon</label>
                                        <input type="text" name="NomotTelepon" class="form-control" value="<?= htmlspecialchars($row['NomotTelepon']); ?>" required>
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
                    echo '<tr><td colspan="5" class="text-center py-4 text-muted">Belum ada data pelanggan atau data tidak ditemukan.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Pelanggan -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="fw-bold text-dark">Tambah Pelanggan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Nama Pelanggan</label>
                        <input type="text" name="NamaPelanggan" class="form-control" placeholder="Masukkan nama lengkap" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Alamat</label>
                        <textarea name="Alamat" class="form-control" rows="3" placeholder="Masukkan alamat lengkap" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Nomor Telepon</label>
                        <input type="text" name="NomotTelepon" class="form-control" placeholder="Contoh: 081234567890" required autocomplete="off">
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