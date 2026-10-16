<?php
include 'header.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$tanggal_hari_ini = date('Y-m-d');
mysqli_query($koneksi, "SET time_zone = '+07:00'");

$jml_pelanggan = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM pelanggan"));
$jml_produk    = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM produk"));
$jml_transaksi = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM penjualan"));

// Menghitung pendapatan hari ini
$q_pendapatan = mysqli_query($koneksi, "SELECT SUM(TotalHarga) AS total_pendapatan FROM penjualan WHERE DATE(TanggalPenjualan) = '$tanggal_hari_ini'");
$d_pendapatan = mysqli_fetch_assoc($q_pendapatan);
$pendapatan_hari_ini = $d_pendapatan['total_pendapatan'] ?? 0;

// Mengambil transaksi hari ini
$q_trx_hari_ini = mysqli_query($koneksi, "
    SELECT penjualan.*, pelanggan.NamaPelanggan, user.username, user.role 
    FROM penjualan 
    JOIN pelanggan ON penjualan.PelangganID = pelanggan.PelangganID 
    LEFT JOIN user ON penjualan.user_id = user.user_id 
    WHERE DATE(penjualan.TanggalPenjualan) = '$tanggal_hari_ini' 
    ORDER BY penjualan.PenjualanID DESC
");
?>

<style>
    .card-custom { border: none; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); background: #ffffff; padding: 25px; }
    .table-custom th { color: #718096; font-weight: 600; border-bottom: 2px solid #edf2f7; background: transparent !important; font-size: 0.9rem; }
    .table-custom td { color: #2d3748; vertical-align: middle; border-bottom: 1px solid #f7fafc; padding: 14px 10px; font-size: 0.9rem; }
    .badge-item { background-color: #f1f5f9; color: #475569; font-weight: 500; border: 1px solid #e2e8f0; font-size: 11px; padding: 4px 8px; border-radius: 6px; display: inline-block; margin: 2px; }
    .badge-role { display: inline-block; background-color: #f8f9fa; color: #212529; border: 1px solid #d9dee5; border-radius: 6px; padding: 3px 8px; font-size: 11px; font-weight: 600; line-height: 1.2; white-space: nowrap; }
    .stat-card { height: 100%; display: flex; flex-direction: column; justify-content: center; }
</style>

<div class="container-fluid px-0">
    <!-- Header / Welcome -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="p-4 rounded-4 shadow-sm" style="background: linear-gradient(135deg, #ffdac150, #b5ead750); padding-top: 30px !important; padding-bottom: 30px !important;">
                <h2 class="fw-bold mb-2" style="color: #2d3436; font-size: 1.9rem;">
                    Halo, <?= htmlspecialchars($_SESSION['username']); ?>! 👋
                </h2>
                <p class="text-muted mb-0" style="font-size: 1.05rem;">
                    Selamat datang kembali di panel administrasi Toko Baju Coer.
                </p>
            </div>
        </div>
    </div>

    <!-- Kotak Statistik -->
    <div class="row g-4 mb-4">
        <!-- Pendapatan -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 stat-card" style="background-color: #e2f0cb30;">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted fw-semibold mb-1" style="font-size: 0.9rem;">Pendapatan Hari Ini</h6>
                            <h4 class="fw-bold mb-0 text-success">Rp <?= number_format($pendapatan_hari_ini, 0, ',', '.'); ?></h4>
                        </div>
                        <div class="p-3 rounded-3" style="background-color: #e2f0cb;">
                            <i class="bi bi-wallet2 fs-4 text-dark"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Produk -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 stat-card" style="background-color: #ffb7b230;">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted fw-semibold mb-1" style="font-size: 0.9rem;">Total Produk</h6>
                            <h3 class="fw-bold mb-0 text-dark"><?= $jml_produk; ?></h3>
                        </div>
                        <div class="p-3 rounded-3" style="background-color: #ffb7b2;">
                            <i class="bi bi-box-seam fs-4 text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Pelanggan -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 stat-card" style="background-color: #b5ead730;">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted fw-semibold mb-1" style="font-size: 0.9rem;">Total Pelanggan</h6>
                            <h3 class="fw-bold mb-0 text-dark"><?= $jml_pelanggan; ?></h3>
                        </div>
                        <div class="p-3 rounded-3" style="background-color: #b5ead7;">
                            <i class="bi bi-people fs-4 text-dark"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Transaksi -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 stat-card" style="background-color: #c7ceea30;">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted fw-semibold mb-1" style="font-size: 0.9rem;">Total Transaksi</h6>
                            <h3 class="fw-bold mb-0 text-dark"><?= $jml_transaksi; ?></h3>
                        </div>
                        <div class="p-3 rounded-3" style="background-color: #c7ceea;">
                            <i class="bi bi-cart-check fs-4 text-dark"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Transaksi Hari Ini -->
    <div class="card card-custom">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-journal-text me-2 text-muted"></i> Transaksi Hari Ini
            </h5>
            <a href="laporan.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Lihat Semua Laporan</a>
        </div>

        <div class="table-responsive">
            <table class="table table-custom align-middle mb-0">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>ID Penjualan</th>
                        <th>Tanggal & Waktu</th>
                        <th>Nama Pelanggan</th>
                        <th>Kasir / Admin</th>
                        <th>Item Dibeli</th>
                        <th>Total Harga</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($q_trx_hari_ini) > 0) : ?>
                        <?php $no = 1; while ($trx = mysqli_fetch_assoc($q_trx_hari_ini)) : 
                            $id_penjualan = $trx['PenjualanID'];

                            // Label User / Peran
                            if ($trx['role'] == 'admin') {
                                $label_user = 'Admin';
                            } elseif ($trx['role'] == 'kasir') {
                                $label_user = 'Kasir';
                            } elseif (!empty($trx['username'])) {
                                $label_user = ucfirst($trx['username']);
                            } else {
                                $label_user = 'Admin';
                            }

                            // Detail Item Produk
                            $q_items = mysqli_query($koneksi, "
                                SELECT detailpenjualan.*, produk.NamaProduk 
                                FROM detailpenjualan 
                                JOIN produk ON detailpenjualan.ProdukID = produk.ProdukID 
                                WHERE detailpenjualan.PenjualanID = '$id_penjualan'
                            ");
                        ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td class="fw-semibold">TRX-<?= sprintf("%04d", $trx['PenjualanID']); ?></td>
                            <td>
                                <div><?= !empty($trx['TanggalPenjualan']) ? date('d M Y', strtotime($trx['TanggalPenjualan'])) : '-'; ?></div>
                                <span class="text-muted" style="font-size: 11px;">
                                    <i class="bi bi-clock me-1"></i><?= !empty($trx['TanggalPenjualan']) ? date('H:i:s', strtotime($trx['TanggalPenjualan'])) : ''; ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($trx['NamaPelanggan']); ?></td>
                            <td><span class="badge-role"><?= htmlspecialchars($label_user); ?></span></td>
                            <td>
                                <div style="max-width: 250px;">
                                    <?php while ($it = mysqli_fetch_assoc($q_items)) : ?>
                                        <span class="badge-item">
                                            <?= htmlspecialchars($it['NamaProduk']); ?> <strong class="text-dark">(<?= $it['JumlahProduk']; ?>)</strong>
                                        </span>
                                    <?php endwhile; ?>
                                </div>
                            </td>
                            <td class="text-success fw-bold">Rp <?= number_format($trx['TotalHarga'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Belum ada transaksi yang tercatat hari ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>