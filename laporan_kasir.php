<?php
include 'header_kasir.php';
include 'koneksi.php';

// Cek apakah kolom user_id sudah ada di tabel penjualan
$check_column = mysqli_query($koneksi, "SHOW COLUMNS FROM penjualan LIKE 'user_id'");

if (mysqli_num_rows($check_column) == 0) {
    mysqli_query($koneksi, "ALTER TABLE penjualan ADD COLUMN user_id INT(11) DEFAULT NULL");
}

// ======================================================
// FILTER TANGGAL & PENCARIAN
// ======================================================
$dari_tanggal   = isset($_GET['dari_tanggal']) ? mysqli_real_escape_string($koneksi, $_GET['dari_tanggal']) : '';
$sampai_tanggal = isset($_GET['sampai_tanggal']) ? mysqli_real_escape_string($koneksi, $_GET['sampai_tanggal']) : '';
$search         = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';

// ======================================================
// QUERY LAPORAN
// ======================================================
$sql = "
    SELECT
        penjualan.*,
        pelanggan.NamaPelanggan,
        user.username,
        user.role
    FROM penjualan
    JOIN pelanggan ON penjualan.PelangganID = pelanggan.PelangganID
    LEFT JOIN user ON penjualan.user_id = user.user_id
    WHERE 1=1
";

// Filter tanggal
if (!empty($dari_tanggal) && !empty($sampai_tanggal)) {
    $sql .= " AND DATE(penjualan.TanggalPenjualan) BETWEEN '$dari_tanggal' AND '$sampai_tanggal' ";
} elseif (!empty($dari_tanggal)) {
    $sql .= " AND DATE(penjualan.TanggalPenjualan) >= '$dari_tanggal' ";
} elseif (!empty($sampai_tanggal)) {
    $sql .= " AND DATE(penjualan.TanggalPenjualan) <= '$sampai_tanggal' ";
}

// Filter pencarian
if (!empty($search)) {
    $sql .= " AND (pelanggan.NamaPelanggan LIKE '%$search%' OR penjualan.PenjualanID LIKE '%$search%')";
}

$sql .= " ORDER BY penjualan.PenjualanID DESC ";

$query_laporan = mysqli_query($koneksi, $sql);

// ======================================================
// TOTAL PENDAPATAN
// ======================================================
$sql_total = "
    SELECT
        SUM(penjualan.TotalHarga) AS total_pendapatan
    FROM penjualan
    JOIN pelanggan ON penjualan.PelangganID = pelanggan.PelangganID
    LEFT JOIN user ON penjualan.user_id = user.user_id
    WHERE 1=1
";

if (!empty($dari_tanggal) && !empty($sampai_tanggal)) {
    $sql_total .= " AND DATE(penjualan.TanggalPenjualan) BETWEEN '$dari_tanggal' AND '$sampai_tanggal' ";
} elseif (!empty($dari_tanggal)) {
    $sql_total .= " AND DATE(penjualan.TanggalPenjualan) >= '$dari_tanggal' ";
} elseif (!empty($sampai_tanggal)) {
    $sql_total .= " AND DATE(penjualan.TanggalPenjualan) <= '$sampai_tanggal' ";
}

if (!empty($search)) {
    $sql_total .= " AND (pelanggan.NamaPelanggan LIKE '%$search%' OR penjualan.PenjualanID LIKE '%$search%')";
}

$query_total      = mysqli_query($koneksi, $sql_total);
$data_total       = mysqli_fetch_assoc($query_total);
$total_pendapatan = $data_total['total_pendapatan'] ?? 0;
?>

<style>
/* ======================================================
   CARD
====================================================== */
.card-custom {
    border: none;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    background: #ffffff;
    padding: 25px;
}

/* ======================================================
   TABLE
====================================================== */
.table-custom th {
    color: #718096;
    font-weight: 600;
    border-bottom: 2px solid #edf2f7;
    background: transparent !important;
    font-size: 0.9rem;
}

.table-custom td {
    color: #2d3748;
    vertical-align: middle;
    border-bottom: 1px solid #f7fafc;
    padding: 14px 10px;
    font-size: 0.9rem;
}

/* Jarak kolom total harga */
.table-custom th.col-total-harga,
.table-custom td.col-total-harga {
    padding-right: 25px !important;
}

/* ======================================================
   BADGE ITEM
====================================================== */
.badge-item {
    background-color: #f1f5f9;
    color: #475569;
    font-weight: 500;
    border: 1px solid #e2e8f0;
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 6px;
    display: inline-block;
    margin: 2px;
}

/* ======================================================
   BADGE KASIR / ADMIN
====================================================== */
.badge-role {
    display: inline-block;
    background-color: #f8f9fa;
    color: #212529;
    border: 1px solid #d9dee5;
    border-radius: 6px;
    padding: 3px 8px;
    font-size: 11px;
    font-weight: 600;
    line-height: 1.2;
    white-space: nowrap;
}

/* ======================================================
   BUTTON FILTER
====================================================== */
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

/* ======================================================
   BUTTON PRINT
====================================================== */
.btn-action-print {
    background-color: #e2e8f0;
    color: #4a5568;
    border: none;
    border-radius: 10px;
    padding: 8px 16px;
    font-weight: 500;
}

.btn-action-print:hover {
    background-color: #cbd5e0;
    color: #1a202c;
}

/* ======================================================
   FORM
====================================================== */
.form-control,
.form-select {
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    padding: 8px 14px;
    font-size: 0.9rem;
}

.form-control:focus,
.form-select:focus {
    box-shadow: none;
    border-color: #b5ead7;
}

/* ======================================================
   CARD STATISTIK
====================================================== */
.card-stats {
    border: none;
    border-radius: 15px;
    background: linear-gradient(135deg, #b5ead7 0%, #c7ceea 100%);
    color: #2d3748;
    padding: 20px;
}

/* ======================================================
   PRINT
====================================================== */
@media print {
    body * { visibility: hidden; }
    .print-area, .print-area * { visibility: visible; }
    .print-area { position: absolute; left: 0; top: 0; width: 100%; background: white; }
    .no-print { display: none !important; }
}
</style>

<!-- ======================================================
     HEADER
====================================================== -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3 no-print">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Laporan Penjualan</h4>
        <p class="text-muted mb-0 small">Rekapitulasi data transaksi penjualan lengkap dengan detail item dan petugas kasir.</p>
    </div>
    <div>
        <button type="button" class="btn btn-action-print shadow-sm" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Cetak Laporan
        </button>
    </div>
</div>

<!-- ======================================================
     TOTAL PENDAPATAN
====================================================== -->
<div class="row mb-4 no-print">
    <div class="col-md-4">
        <div class="card card-stats shadow-sm">
            <h6 class="text-muted fw-semibold small mb-1">TOTAL PENDAPATAN PERIODE INI</h6>
            <h3 class="fw-bold mb-0 text-dark">Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?></h3>
        </div>
    </div>
</div>

<!-- ======================================================
     FILTER TANGGAL
====================================================== -->
<div class="card card-custom mb-4 no-print">
    <form method="GET" action="">
        <!-- Jika ada search aktif, sertakan di form filter agar tidak hilang -->
        <?php if (!empty($search)): ?>
            <input type="hidden" name="search" value="<?= htmlspecialchars($search); ?>">
        <?php endif; ?>

        <div class="row align-items-end g-3">
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Dari Tanggal</label>
                <input type="date" name="dari_tanggal" class="form-control" value="<?= htmlspecialchars($dari_tanggal); ?>" required>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Sampai Tanggal</label>
                <input type="date" name="sampai_tanggal" class="form-control" value="<?= htmlspecialchars($sampai_tanggal); ?>" required>
            </div>

            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-pastel-primary flex-grow-1">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>

                <?php if (!empty($dari_tanggal) || !empty($sampai_tanggal)): ?>
                    <a href="laporan_kasir.php<?= !empty($search) ? '?search=' . urlencode($search) : ''; ?>" class="btn btn-outline-danger" style="border-radius: 10px;" title="Reset Filter">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- ======================================================
     TABEL LAPORAN
====================================================== -->
<div class="card card-custom print-area">
    <div class="row align-items-center justify-content-between mb-4 g-3">
        <div class="col-md-6">
            <h5 class="fw-bold mb-0 text-dark d-flex align-items-center">
                <i class="bi bi-file-earmark-text me-2 text-muted"></i> Data Rekap Laporan Penjualan
            </h5>

            <?php if (!empty($dari_tanggal) && !empty($sampai_tanggal)): ?>
                <span class="text-muted small mt-1 d-block">
                    Periode Tanggal: 
                    <strong><?= date('d M Y', strtotime($dari_tanggal)); ?></strong> s.d. 
                    <strong><?= date('d M Y', strtotime($sampai_tanggal)); ?></strong>
                </span>
            <?php endif; ?>
        </div>

        <!-- TOMBOL / FORM SEARCH -->
        <div class="col-md-5 no-print">
            <form method="GET" action="" class="d-flex">
                <!-- Sertakan parameter tanggal jika sedang aktif agar pencarian tidak mereset filter tanggal -->
                <?php if (!empty($dari_tanggal)): ?>
                    <input type="hidden" name="dari_tanggal" value="<?= htmlspecialchars($dari_tanggal); ?>">
                <?php endif; ?>
                <?php if (!empty($sampai_tanggal)): ?>
                    <input type="hidden" name="sampai_tanggal" value="<?= htmlspecialchars($sampai_tanggal); ?>">
                <?php endif; ?>

                <input type="text" name="search" class="form-control form-control-sm me-2" placeholder="Cari pelanggan / ID..." value="<?= htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-outline-secondary btn-sm px-3 text-nowrap" style="border-radius: 10px;"><i class="bi bi-search"></i> Cari</button>
                <?php if (!empty($search)): ?>
                    <?php 
                        $reset_link = 'laporan_kasir.php';
                        $params = [];
                        if (!empty($dari_tanggal)) $params[] = 'dari_tanggal=' . urlencode($dari_tanggal);
                        if (!empty($sampai_tanggal)) $params[] = 'sampai_tanggal=' . urlencode($sampai_tanggal);
                        if (!empty($params)) $reset_link .= '?' . implode('&', $params);
                    ?>
                    <a href="<?= $reset_link; ?>" class="btn btn-outline-danger btn-sm ms-2 d-flex align-items-center" style="border-radius: 10px;" title="Reset Pencarian"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 12%;">ID Penjualan</th>
                    <th style="width: 15%;">Tanggal & Waktu</th>
                    <th style="width: 18%;">Nama Pelanggan</th>
                    <th style="width: 12%;">Kasir / Admin</th>
                    <th style="width: 23%;">Item Dibeli</th>
                    <th class="text-end col-total-harga" style="width: 15%;">Total Harga</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($query_laporan) > 0): ?>
                    <?php
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($query_laporan)):
                        $penjualan_id = $row['PenjualanID'];

                        // ==================================================
                        // MENENTUKAN KASIR / ADMIN
                        // ==================================================
                        if ($row['role'] == 'admin') {
                            $label_user = 'Admin';
                        } elseif ($row['role'] == 'kasir') {
                            $label_user = 'Kasir';
                        } elseif (!empty($row['username'])) {
                            $label_user = ucfirst($row['username']);
                        } else {
                            $label_user = 'Admin';
                        }

                        // ==================================================
                        // DETAIL PRODUK
                        // ==================================================
                        $q_items = mysqli_query(
                            $koneksi,
                            "SELECT detailpenjualan.*, produk.NamaProduk 
                             FROM detailpenjualan 
                             JOIN produk ON detailpenjualan.ProdukID = produk.ProdukID 
                             WHERE detailpenjualan.PenjualanID = '$penjualan_id'"
                        );
                    ?>

                    <tr>
                        <!-- NO -->
                        <td><?= $no++; ?></td>

                        <!-- ID PENJUALAN -->
                        <td class="fw-semibold">TRX-<?= sprintf("%04d", $penjualan_id); ?></td>

                        <!-- TANGGAL & WAKTU -->
                        <td>
                            <div><?= date('d M Y', strtotime($row['TanggalPenjualan'])); ?></div>
                            <span class="text-muted" style="font-size: 11px;">
                                <i class="bi bi-clock me-1"></i> <?= date('H:i:s', strtotime($row['TanggalPenjualan'])); ?>
                            </span>
                        </td>

                        <!-- PELANGGAN -->
                        <td><?= htmlspecialchars($row['NamaPelanggan']); ?></td>

                        <!-- KASIR / ADMIN -->
                        <td>
                            <span class="badge-role"><?= htmlspecialchars($label_user); ?></span>
                        </td>

                        <!-- ITEM -->
                        <td>
                            <div style="max-width: 220px;">
                                <?php while ($it = mysqli_fetch_assoc($q_items)): ?>
                                    <span class="badge-item">
                                        <?= htmlspecialchars($it['NamaProduk']); ?> 
                                        <strong class="text-dark">(<?= $it['JumlahProduk']; ?>)</strong>
                                    </span>
                                <?php endwhile; ?>
                            </div>
                        </td>

                        <!-- TOTAL -->
                        <td class="fw-bold text-success text-end col-total-harga" style="white-space: nowrap;">
                            Rp <?= number_format($row['TotalHarga'], 0, ',', '.'); ?>
                        </td>
                    </tr>

                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            Belum ada data laporan atau data tidak ditemukan.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>