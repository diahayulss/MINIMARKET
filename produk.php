<?php
include 'header.php';
include 'koneksi.php';

// Proses Tambah Produk Baju
if (isset($_POST['tambah'])) {
    $namaproduk = mysqli_real_escape_string($koneksi, $_POST['NamaProduk']);
    $hargabeli  = mysqli_real_escape_string($koneksi, $_POST['HargaBeli']);
    $harga      = mysqli_real_escape_string($koneksi, $_POST['Harga']);
    $stok       = mysqli_real_escape_string($koneksi, $_POST['Stok']);
    
    // Upload Gambar Produk
    $nama_foto = $_FILES['foto']['name'] ?? '';
    $lokasi_foto = $_FILES['foto']['tmp_name'] ?? '';
    $folder = "assets/img/produk/";

    if (!is_dir('assets/img/produk')) {
        mkdir('assets/img/produk', 0777, true);
    }

    if (!empty($nama_foto)) {
        $ext = pathinfo($nama_foto, PATHINFO_EXTENSION);
        $foto_baru = uniqid() . '.' . $ext;
        move_uploaded_file($lokasi_foto, $folder . $foto_baru);

        $query = mysqli_query($koneksi, "INSERT INTO produk (NamaProduk, HargaBeli, Harga, Stok) VALUES ('$namaproduk', '$hargabeli', '$harga', '$stok')");
    } else {
        $query = mysqli_query($koneksi, "INSERT INTO produk (NamaProduk, HargaBeli, Harga, Stok) VALUES ('$namaproduk', '$hargabeli', '$harga', '$stok')");
    }

    if ($query) {
        echo "<script>alert('Data produk baju berhasil ditambahkan!'); window.location='produk.php';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan data produk!');</script>";
    }
}

// Proses Edit Produk
if (isset($_POST['edit'])) {
    $produkid   = $_POST['ProdukID'];
    $namaproduk = mysqli_real_escape_string($koneksi, $_POST['NamaProduk']);
    $hargabeli  = mysqli_real_escape_string($koneksi, $_POST['HargaBeli']);
    $harga      = mysqli_real_escape_string($koneksi, $_POST['Harga']);
    $stok       = mysqli_real_escape_string($koneksi, $_POST['Stok']);

    $query = mysqli_query($koneksi, "UPDATE produk SET NamaProduk='$namaproduk', HargaBeli='$hargabeli', Harga='$harga', Stok='$stok' WHERE ProdukID='$produkid'");

    if ($query) {
        echo "<script>alert('Data produk berhasil diubah!'); window.location='produk.php';</script>";
    } else {
        echo "<script>alert('Gagal mengubah data produk!');</script>";
    }
}

// Proses Hapus Produk (Dilengkapi Try-Catch untuk mencegah Fatal Error Foreign Key)
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    try {
        $query = mysqli_query($koneksi, "DELETE FROM produk WHERE ProdukID='$id'");
        if ($query) {
            echo "<script>alert('Produk berhasil dihapus!'); window.location='produk.php';</script>";
        }
    } catch (\mysqli_sql_exception $e) {
        // Menangkap error relasi foreign key dari tabel detailpenjualan[cite: 1]
        echo "<script>alert('Gagal menghapus! Produk ini masih tercatat dalam riwayat transaksi penjualan.'); window.location='produk.php';</script>";
    }
}

// Logika Pencarian (Search)
$search = '';
if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($koneksi, $_GET['search']);
    $query_produk = mysqli_query($koneksi, "SELECT * FROM produk WHERE NamaProduk LIKE '%$search%' ORDER BY ProdukID DESC");
} else {
    $query_produk = mysqli_query($koneksi, "SELECT * FROM produk ORDER BY ProdukID DESC");
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

<!-- Header Konten & Tombol Tambah -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Katalog Produk Baju</h4>
        <p class="text-muted mb-0 small">Kelola data pakaian, harga beli, harga jual, dan stok barang toko.</p>
    </div>
    <div>
        <button type="button" class="btn btn-pastel-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-lg me-1"></i> Tambah Produk Baru
        </button>
    </div>
</div>

<!-- Card Tabel & Search Bar -->
<div class="card card-custom">
    <div class="row align-items-center justify-content-between mb-4 g-3">
        <div class="col-auto">
            <h5 class="fw-bold mb-0 text-dark d-flex align-items-center">
                <i class="bi bi-bag-check me-2 text-muted"></i> Daftar Produk Pakaian
            </h5>
        </div>
        <div class="col-md-4">
            <!-- Form Search -->
            <form method="GET" action="" class="d-flex">
                <input type="text" name="search" class="form-control form-control-sm me-2" placeholder="Cari nama baju..." value="<?= htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-outline-secondary btn-sm rounded-10 px-3"><i class="bi bi-search"></i></button>
                <?php if (!empty($search)): ?>
                    <a href="produk.php" class="btn btn-outline-danger btn-sm ms-2 rounded-10 d-flex align-items-center" title="Reset Pencarian"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>ID Produk</th>
                    <th>Nama Produk Baju</th>
                    <th>Harga Beli</th>
                    <th>Harga Jual</th>
                    <th>Stok</th>
                    <th class="text-center" width="15%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (mysqli_num_rows($query_produk) > 0) {
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($query_produk)) {
                ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td class="text-muted">PRD-<?= sprintf("%03d", $row['ProdukID']); ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($row['NamaProduk']); ?></td>
                    <td class="text-secondary">Rp <?= number_format($row['HargaBeli'], 0, ',', '.'); ?></td>
                    <td class="text-success fw-bold">Rp <?= number_format($row['Harga'], 0, ',', '.'); ?></td>
                    <td>
                        <?php if ($row['Stok'] > 0): ?>
                            <span class="badge bg-light text-dark border px-2 py-1"><?= $row['Stok']; ?> Pcs</span>
                        <?php else: ?>
                            <span class="badge bg-danger px-2 py-1">Habis</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <!-- Tombol Edit -->
                        <button type="button" class="btn btn-action-edit btn-sm shadow-sm me-1" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['ProdukID']; ?>" title="Edit">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                        <!-- Tombol Hapus -->
                        <a href="produk.php?hapus=<?= $row['ProdukID']; ?>" class="btn btn-action-delete btn-sm shadow-sm" onclick="return confirm('Yakin ingin menghapus produk baju ini?')" title="Hapus">
                            <i class="bi bi-trash-fill"></i>
                        </a>
                    </td>
                </tr>

                <!-- Modal Edit Produk -->
                <div class="modal fade" id="modalEdit<?= $row['ProdukID']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="fw-bold text-dark">Edit Produk Baju</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST" action="">
                                <div class="modal-body text-start">
                                    <input type="hidden" name="ProdukID" value="<?= $row['ProdukID']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-muted">Nama Produk Baju</label>
                                        <input type="text" name="NamaProduk" class="form-control" value="<?= htmlspecialchars($row['NamaProduk']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-muted">Harga Beli (Rp)</label>
                                        <input type="number" name="HargaBeli" class="form-control" value="<?= htmlspecialchars($row['HargaBeli']); ?>" min="0" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-muted">Harga Jual (Rp)</label>
                                        <input type="number" name="Harga" class="form-control" value="<?= htmlspecialchars($row['Harga']); ?>" min="0" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-muted">Stok Barang</label>
                                        <input type="number" name="Stok" class="form-control" value="<?= htmlspecialchars($row['Stok']); ?>" min="0" required>
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
                    echo '<tr><td colspan="7" class="text-center py-4 text-muted">Belum ada data produk baju atau pencarian tidak ditemukan.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Produk -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="fw-bold text-dark">Tambah Produk Baju Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Nama Produk Baju</label>
                        <input type="text" name="NamaProduk" class="form-control" placeholder="Contoh: Kemeja Flanel Oversize" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Harga Beli (Rp)</label>
                        <input type="number" name="HargaBeli" class="form-control" placeholder="Contoh: 40000" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Harga Jual (Rp)</label>
                        <input type="number" name="Harga" class="form-control" placeholder="Contoh: 75000" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Stok Barang</label>
                        <input type="number" name="Stok" class="form-control" placeholder="Contoh: 20" min="0" required>
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