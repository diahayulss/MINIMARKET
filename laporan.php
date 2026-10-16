<?php
include 'header.php';
include 'koneksi.php';

// Cek apakah kolom user_id sudah ada di tabel penjualan
$check_column = mysqli_query($koneksi, "SHOW COLUMNS FROM penjualan LIKE 'user_id'");
if (mysqli_num_rows($check_column) == 0) {
    mysqli_query($koneksi, "ALTER TABLE penjualan ADD COLUMN user_id INT(11) DEFAULT NULL");
}

// ======================================================
// PROSES HAPUS PENJUALAN + KEMBALIKAN STOK
// ======================================================
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['hapus']);

    // Ambil detail transaksi
    $q_detail = mysqli_query($koneksi, "SELECT * FROM detailpenjualan WHERE PenjualanID = '$id'");

    // Kembalikan stok
    while ($d = mysqli_fetch_assoc($q_detail)) {
        $p_id = $d['ProdukID'];
        $jml_prod = $d['JumlahProduk'];
        mysqli_query($koneksi, "UPDATE produk SET Stok = Stok + $jml_prod WHERE ProdukID = '$p_id'");
    }

    // Hapus detail transaksi & transaksi
    mysqli_query($koneksi, "DELETE FROM detailpenjualan WHERE PenjualanID = '$id'");
    $query = mysqli_query($koneksi, "DELETE FROM penjualan WHERE PenjualanID = '$id'");

    // Pertahankan parameter filter
    $dari_param   = isset($_GET['dari_tanggal']) && $_GET['dari_tanggal'] !== '' ? '&dari_tanggal=' . urlencode($_GET['dari_tanggal']) : '';
    $sampai_param = isset($_GET['sampai_tanggal']) && $_GET['sampai_tanggal'] !== '' ? '&sampai_tanggal=' . urlencode($_GET['sampai_tanggal']) : '';
    $search_param = isset($_GET['search']) && $_GET['search'] !== '' ? '&search=' . urlencode($_GET['search']) : '';
    $tab_param    = isset($_GET['tab']) && $_GET['tab'] !== '' ? '&tab=' . urlencode($_GET['tab']) : '';

    if ($query) {
        $parameter    = ltrim($dari_param . $sampai_param . $search_param . $tab_param, '&');
        $redirect_url = !empty($parameter) ? 'laporan.php?' . $parameter : 'laporan.php';

        echo "
        <script>
            alert('Data laporan & stok berhasil dikembalikan/dihapus!');
            window.location='$redirect_url';
        </script>
        ";
    } else {
        echo "
        <script>
            alert('Gagal menghapus data laporan!');
            window.location='laporan.php';
        </script>
        ";
    }
    exit;
}

// ======================================================
// FILTER TANGGAL & PENCARIAN
// ======================================================
$dari_tanggal   = isset($_GET['dari_tanggal']) ? mysqli_real_escape_string($koneksi, $_GET['dari_tanggal']) : '';
$sampai_tanggal = isset($_GET['sampai_tanggal']) ? mysqli_real_escape_string($koneksi, $_GET['sampai_tanggal']) : '';
$search         = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$active_tab     = isset($_GET['tab']) ? $_GET['tab'] : 'transaksi'; 

// Fungsi pembantu kondisi WHERE (untuk tab transaksi)
function getWhereClause($dari, $sampai, $cari) {
    $where = " WHERE 1=1 ";
    if (!empty($dari) && !empty($sampai)) {
        $where .= " AND DATE(penjualan.TanggalPenjualan) BETWEEN '$dari' AND '$sampai' ";
    } elseif (!empty($dari)) {
        $where .= " AND DATE(penjualan.TanggalPenjualan) >= '$dari' ";
    } elseif (!empty($sampai)) {
        $where .= " AND DATE(penjualan.TanggalPenjualan) <= '$sampai' ";
    }
    if (!empty($cari)) {
        $where .= " AND (pelanggan.NamaPelanggan LIKE '%$cari%' OR penjualan.PenjualanID LIKE '%$cari%')";
    }
    return $where;
}

$where_clause = getWhereClause($dari_tanggal, $sampai_tanggal, $search);

// ======================================================
// QUERY UTAMA LAPORAN TRANSAKSI
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
    $where_clause
    ORDER BY penjualan.PenjualanID DESC
";
$query_laporan = mysqli_query($koneksi, $sql);

// ======================================================
// TOTAL PENDAPATAN & LABA RUGI KESELURUHAN (BALANCE)
// ======================================================
$sql_summary = "
    SELECT
        SUM(penjualan.TotalHarga) AS total_pendapatan
    FROM penjualan
    JOIN pelanggan ON penjualan.PelangganID = pelanggan.PelangganID
    LEFT JOIN user ON penjualan.user_id = user.user_id
    $where_clause
";
$query_summary = mysqli_query($koneksi, $sql_summary);
$data_summary  = mysqli_fetch_assoc($query_summary);
$total_pendapatan = $data_summary['total_pendapatan'] ?? 0;

$sql_laba = "
    SELECT 
        SUM(dp.JumlahProduk * IFNULL(p.HargaBeli, 0)) AS total_modal
    FROM detailpenjualan dp
    JOIN penjualan ON dp.PenjualanID = penjualan.PenjualanID
    JOIN pelanggan ON penjualan.PelangganID = pelanggan.PelangganID
    JOIN produk p ON dp.ProdukID = p.ProdukID
    $where_clause
";
$query_laba = mysqli_query($koneksi, $sql_laba);
$data_laba  = mysqli_fetch_assoc($query_laba);
$total_modal = $data_laba['total_modal'] ?? 0;
$total_laba  = $total_pendapatan - $total_modal;
?>

<style>
.card-custom { border: none; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); background: #ffffff; padding: 25px; }
.table-custom th { color: #718096; font-weight: 600; border-bottom: 2px solid #edf2f7; background: transparent !important; font-size: 0.9rem; }
.table-custom td { color: #2d3748; vertical-align: middle; border-bottom: 1px solid #f7fafc; padding: 14px 10px; font-size: 0.9rem; }
.badge-item { background-color: #f1f5f9; color: #475569; font-weight: 500; border: 1px solid #e2e8f0; font-size: 11px; padding: 4px 8px; border-radius: 6px; display: inline-block; margin: 2px; }
.badge-role { display: inline-block; background-color: #f8f9fa; color: #212529; border: 1px solid #d9dee5; border-radius: 6px; padding: 3px 8px; font-size: 11px; font-weight: 600; }
.btn-pastel-primary { background-color: #b5ead7; border: none; color: #2d3748; font-weight: 500; border-radius: 10px; padding: 8px 16px; transition: 0.2s; }
.btn-pastel-primary:hover { background-color: #97d4be; color: #000; }
.btn-action-delete { background-color: #f8d7da; color: #721c24; border: none; border-radius: 8px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; }
.btn-action-print { background-color: #e2e8f0; color: #4a5568; border: none; border-radius: 10px; padding: 8px 16px; font-weight: 500; }
.form-control, .form-select { border-radius: 10px; border: 1px solid #e2e8f0; padding: 8px 14px; font-size: 0.9rem; }
.card-stats { border: none; border-radius: 15px; background: linear-gradient(135deg, #b5ead7 0%, #c7ceea 100%); color: #2d3748; padding: 20px; }
.card-stats-profit { border: none; border-radius: 15px; background: linear-gradient(135deg, #d4fc79 0%, #96e6a1 100%); color: #2d3748; padding: 20px; }
.nav-pills .nav-link { border-radius: 10px; font-weight: 600; color: #4a5568; background: #f8f9fa; margin-right: 5px; }
.nav-pills .nav-link.active { background-color: #b5ead7 !important; color: #2d3748 !important; }
@media print { body * { visibility: hidden; } .print-area, .print-area * { visibility: visible; } .print-area { position: absolute; left: 0; top: 0; width: 100%; background: white; } .no-print { display: none !important; } }
</style>

<!-- HEADER -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3 no-print">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Laporan Penjualan & Keuangan</h4>
        <p class="text-muted mb-0 small">Rekapitulasi transaksi, laporan berkala (Harian, Mingguan, Bulanan, Tahunan), serta analisis laba rugi.</p>
    </div>
    <div>
        <button type="button" class="btn btn-action-print shadow-sm" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Cetak Laporan
        </button>
    </div>
</div>

<!-- KARTU STATISTIK -->
<div class="row mb-4 no-print g-3">
    <div class="col-md-6">
        <div class="card card-stats shadow-sm">
            <h6 class="text-muted fw-semibold small mb-1"><i class="bi bi-wallet2 me-1"></i> TOTAL PENDAPATAN (OMSET)</h6>
            <h3 class="fw-bold mb-0 text-dark">Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?></h3>
            <small class="text-muted mt-1 d-block">Total balance sesuai dengan filter periode aktif.</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-stats-profit shadow-sm">
            <h6 class="text-muted fw-semibold small mb-1"><i class="bi bi-graph-up-arrow me-1"></i> ESTIMASI LABA BERSIH (PROFIT)</h6>
            <h3 class="fw-bold mb-0 text-dark">Rp <?= number_format($total_laba, 0, ',', '.'); ?></h3>
            <small class="text-muted mt-1 d-block">Pendapatan dikurangi total modal produk terjual.</small>
        </div>
    </div>
</div>

<!-- FILTER TANGGAL -->
<div class="card card-custom mb-4 no-print">
    <form method="GET" action="">
        <?php if (!empty($search)): ?>
            <input type="hidden" name="search" value="<?= htmlspecialchars($search); ?>">
        <?php endif; ?>
        <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab); ?>">

        <div class="row align-items-end g-3">
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Dari Tanggal</label>
                <input type="date" name="dari_tanggal" class="form-control" value="<?= htmlspecialchars($dari_tanggal); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Sampai Tanggal</label>
                <input type="date" name="sampai_tanggal" class="form-control" value="<?= htmlspecialchars($sampai_tanggal); ?>">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-pastel-primary flex-grow-1">
                    <i class="bi bi-filter me-1"></i> Filter Tanggal
                </button>
                <?php if (!empty($dari_tanggal) || !empty($sampai_tanggal)): ?>
                    <a href="laporan.php?tab=<?= $active_tab; ?>" class="btn btn-outline-danger" style="border-radius: 10px;" title="Reset Filter">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- NAVIGASI TAB LAPORAN -->
<ul class="nav nav-pills mb-3 no-print" id="reportTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link <?= $active_tab == 'transaksi' ? 'active' : ''; ?>" href="laporan.php?tab=transaksi<?= !empty($dari_tanggal)?'&dari_tanggal='.$dari_tanggal:''; ?><?= !empty($sampai_tanggal)?'&sampai_tanggal='.$sampai_tanggal:''; ?>">
            <i class="bi bi-list-check me-1"></i> Detail Transaksi
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $active_tab == 'harian' ? 'active' : ''; ?>" href="laporan.php?tab=harian<?= !empty($dari_tanggal)?'&dari_tanggal='.$dari_tanggal:''; ?><?= !empty($sampai_tanggal)?'&sampai_tanggal='.$sampai_tanggal:''; ?>">
            <i class="bi bi-calendar-day me-1"></i> Rekap Per Hari
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $active_tab == 'mingguan' ? 'active' : ''; ?>" href="laporan.php?tab=mingguan<?= !empty($dari_tanggal)?'&dari_tanggal='.$dari_tanggal:''; ?><?= !empty($sampai_tanggal)?'&sampai_tanggal='.$sampai_tanggal:''; ?>">
            <i class="bi bi-calendar-week me-1"></i> Rekap Per Minggu
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $active_tab == 'bulanan' ? 'active' : ''; ?>" href="laporan.php?tab=bulanan<?= !empty($dari_tanggal)?'&dari_tanggal='.$dari_tanggal:''; ?><?= !empty($sampai_tanggal)?'&sampai_tanggal='.$sampai_tanggal:''; ?>">
            <i class="bi bi-calendar-month me-1"></i> Rekap Per Bulan
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $active_tab == 'tahunan' ? 'active' : ''; ?>" href="laporan.php?tab=tahunan<?= !empty($dari_tanggal)?'&dari_tanggal='.$dari_tanggal:''; ?><?= !empty($sampai_tanggal)?'&sampai_tanggal='.$sampai_tanggal:''; ?>">
            <i class="bi bi-calendar-range me-1"></i> Rekap Per Tahun
        </a>
    </li>
</ul>

<!-- KONTEN TAB -->
<div class="card card-custom print-area mb-5">
    <?php if ($active_tab == 'transaksi'): ?>
        <!-- TAB 1: DETAIL TRANSAKSI -->
        <div class="row align-items-center justify-content-between mb-4 g-3">
            <div class="col-md-6">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-file-earmark-text me-2 text-muted"></i> Data Rekap Laporan Penjualan</h5>
                <?php if (!empty($dari_tanggal) && !empty($sampai_tanggal)): ?>
                    <span class="text-muted small mt-1 d-block">Periode: <strong><?= date('d M Y', strtotime($dari_tanggal)); ?></strong> s.d. <strong><?= date('d M Y', strtotime($sampai_tanggal)); ?></strong></span>
                <?php endif; ?>
            </div>
            <div class="col-md-5 no-print">
                <form method="GET" action="" class="d-flex">
                    <input type="hidden" name="tab" value="transaksi">
                    <?php if (!empty($dari_tanggal)): ?><input type="hidden" name="dari_tanggal" value="<?= $dari_tanggal; ?>"><?php endif; ?>
                    <?php if (!empty($sampai_tanggal)): ?><input type="hidden" name="sampai_tanggal" value="<?= $sampai_tanggal; ?>"><?php endif; ?>
                    <input type="text" name="search" class="form-control form-control-sm me-2" placeholder="Cari pelanggan / ID..." value="<?= htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-outline-secondary btn-sm px-3 text-nowrap" style="border-radius: 10px;"><i class="bi bi-search"></i></button>
                    <?php if (!empty($search)): ?>
                        <a href="laporan.php?tab=transaksi<?= !empty($dari_tanggal)?'&dari_tanggal='.$dari_tanggal:''; ?><?= !empty($sampai_tanggal)?'&sampai_tanggal='.$sampai_tanggal:''; ?>" class="btn btn-outline-danger btn-sm ms-2" style="border-radius: 10px;"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-custom align-middle mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>ID Penjualan</th>
                        <th>Tanggal & Waktu</th>
                        <th>Nama Pelanggan</th>
                        <th>Kasir / Admin</th>
                        <th>Item Dibeli</th>
                        <th class="text-end">Total Harga</th>
                        <th class="text-center no-print">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($query_laporan) > 0): ?>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($query_laporan)): 
                            $penjualan_id = $row['PenjualanID'];
                            $label_user = !empty($row['username']) ? ucfirst($row['username']) : 'Admin';
                            
                            $q_items = mysqli_query($koneksi, "SELECT detailpenjualan.*, produk.NamaProduk FROM detailpenjualan JOIN produk ON detailpenjualan.ProdukID = produk.ProdukID WHERE detailpenjualan.PenjualanID = '$penjualan_id'");
                        ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td class="fw-semibold">TRX-<?= sprintf("%04d", $penjualan_id); ?></td>
                            <td>
                                <div><?= date('d M Y', strtotime($row['TanggalPenjualan'])); ?></div>
                                <span class="text-muted small"><i class="bi bi-clock me-1"></i><?= date('H:i:s', strtotime($row['TanggalPenjualan'])); ?></span>
                            </td>
                            <td><?= htmlspecialchars($row['NamaPelanggan']); ?></td>
                            <td><span class="badge-role"><?= htmlspecialchars($label_user); ?></span></td>
                            <td>
                                <div style="max-width: 220px;">
                                    <?php while ($it = mysqli_fetch_assoc($q_items)): ?>
                                        <span class="badge-item"><?= htmlspecialchars($it['NamaProduk']); ?> <strong class="text-dark">(<?= $it['JumlahProduk']; ?>)</strong></span>
                                    <?php endwhile; ?>
                                </div>
                            </td>
                            <td class="fw-bold text-success text-end" style="white-space: nowrap;">Rp <?= number_format($row['TotalHarga'], 0, ',', '.'); ?></td>
                            <td class="text-center no-print">
                                <a href="laporan.php?hapus=<?= $penjualan_id; ?>&tab=transaksi<?= !empty($dari_tanggal)?'&dari_tanggal='.$dari_tanggal:''; ?><?= !empty($sampai_tanggal)?'&sampai_tanggal='.$sampai_tanggal:''; ?><?= !empty($search)?'&search='.$search:''; ?>" 
                                   class="btn btn-action-delete btn-sm shadow-sm" 
                                   onclick="return confirm('Yakin ingin menghapus data laporan ini? Stok produk akan dikembalikan.')" title="Hapus">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada data transaksi ditemukan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php 
    else: 
        // TAB BERKALA (HARIAN / MINGGUAN / BULANAN / TAHUNAN)
        if ($active_tab == 'harian') {
            $group_sql_select = "DATE(penjualan.TanggalPenjualan) AS periode_label";
            $group_by = "GROUP BY DATE(penjualan.TanggalPenjualan)";
            $title_label = "Hari";
        } elseif ($active_tab == 'mingguan') {
            $group_sql_select = "CONCAT('Minggu ke-', WEEK(penjualan.TanggalPenjualan, 1), ' Tahun ', YEAR(penjualan.TanggalPenjualan)) AS periode_label";
            $group_by = "GROUP BY YEAR(penjualan.TanggalPenjualan), WEEK(penjualan.TanggalPenjualan, 1)";
            $title_label = "Minggu";
        } elseif ($active_tab == 'bulanan') {
            $group_sql_select = "DATE_FORMAT(penjualan.TanggalPenjualan, '%Y-%m') AS periode_label";
            $group_by = "GROUP BY DATE_FORMAT(penjualan.TanggalPenjualan, '%Y-%m')";
            $title_label = "Bulan";
        } else {
            $group_sql_select = "YEAR(penjualan.TanggalPenjualan) AS periode_label";
            $group_by = "GROUP BY YEAR(penjualan.TanggalPenjualan)";
            $title_label = "Tahun";
        }

        // Tambahan fitur pencarian khusus rekap berkala
        $rekap_search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
        $having_clause = "";
        if (!empty($rekap_search)) {
            $having_clause = " HAVING periode_label LIKE '%$rekap_search%' ";
        }

        $sql_rekap = "
            SELECT 
                $group_sql_select,
                COUNT(penjualan.PenjualanID) AS jumlah_transaksi,
                SUM(penjualan.TotalHarga) AS total_omset,
                SUM(
                    (SELECT IFNULL(SUM(dp.JumlahProduk * p.HargaBeli), 0) 
                     FROM detailpenjualan dp 
                     JOIN produk p ON dp.ProdukID = p.ProdukID 
                     WHERE dp.PenjualanID = penjualan.PenjualanID)
                ) AS total_modal
            FROM penjualan
            JOIN pelanggan ON penjualan.PelangganID = pelanggan.PelangganID
            $where_clause
            $group_by
            $having_clause
            ORDER BY penjualan.TanggalPenjualan DESC
        ";
        $query_rekap = mysqli_query($koneksi, $sql_rekap);
    ?>
        <div class="row align-items-center justify-content-between mb-4 g-3">
            <div class="col-md-6">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-bar-chart-fill me-2 text-muted"></i> Rekapitulasi Penjualan Per <?= ucfirst($title_label); ?></h5>
                <span class="text-muted small">Data keuangan di bawah ini sudah diverifikasi balance otomatis dengan filter utama.</span>
            </div>
            <div class="col-md-5 no-print">
                <form method="GET" action="" class="d-flex">
                    <input type="hidden" name="tab" value="<?= $active_tab; ?>">
                    <?php if (!empty($dari_tanggal)): ?><input type="hidden" name="dari_tanggal" value="<?= $dari_tanggal; ?>"><?php endif; ?>
                    <?php if (!empty($sampai_tanggal)): ?><input type="hidden" name="sampai_tanggal" value="<?= $sampai_tanggal; ?>"><?php endif; ?>
                    <input type="text" name="search" class="form-control form-control-sm me-2" placeholder="Cari periode (contoh: 2026, 2026-04)..." value="<?= htmlspecialchars($rekap_search); ?>">
                    <button type="submit" class="btn btn-outline-secondary btn-sm px-3 text-nowrap" style="border-radius: 10px;"><i class="bi bi-search"></i></button>
                    <?php if (!empty($rekap_search)): ?>
                        <a href="laporan.php?tab=<?= $active_tab; ?><?= !empty($dari_tanggal)?'&dari_tanggal='.$dari_tanggal:''; ?><?= !empty($sampai_tanggal)?'&sampai_tanggal='.$sampai_tanggal:''; ?>" class="btn btn-outline-danger btn-sm ms-2" style="border-radius: 10px;"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-custom align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 25%;">Periode <?= $title_label; ?></th>
                        <th class="text-center" style="width: 15%;">Total Transaksi</th>
                        <th class="text-end" style="width: 18%;">Total Omset (Pendapatan)</th>
                        <th class="text-end" style="width: 18%;">Total Modal</th>
                        <th class="text-end" style="width: 19%;">Laba Bersih (Profit)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (mysqli_num_rows($query_rekap) > 0): 
                        $no = 1;
                        $sum_omset = 0;
                        $sum_modal = 0;
                        $sum_laba = 0;
                        while ($r = mysqli_fetch_assoc($query_rekap)):
                            $omset = $r['total_omset'] ?? 0;
                            $modal = $r['total_modal'] ?? 0;
                            $laba  = $omset - $modal;

                            $sum_omset += $omset;
                            $sum_modal += $modal;
                            $sum_laba  += $laba;

                            $display_periode = $r['periode_label'];
                            if ($active_tab == 'harian') {
                                $display_periode = date('d M Y', strtotime($r['periode_label']));
                            } elseif ($active_tab == 'bulanan') {
                                $display_periode = date('F Y', strtotime($r['periode_label'] . '-01'));
                            }
                    ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($display_periode); ?></td>
                        <td class="text-center"><span class="badge bg-light text-dark border px-2 py-1"><?= $r['jumlah_transaksi']; ?> Transaksi</span></td>
                        <td class="text-end fw-bold text-dark">Rp <?= number_format($omset, 0, ',', '.'); ?></td>
                        <td class="text-end text-muted">Rp <?= number_format($modal, 0, ',', '.'); ?></td>
                        <td class="text-end fw-bold text-success">Rp <?= number_format($laba, 0, ',', '.'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <!-- Baris Total Keseluruhan (Balance) - Rata Kiri -->
                    <tr style="background-color: #f8fafc; font-weight: 700; border-top: 2px solid #cbd5e1;">
                        <td colspan="3" class="text-start py-3 text-secondary ps-3">TOTAL KESELURUHAN (BALANCE) :</td>
                        <td class="text-end text-dark py-3">Rp <?= number_format($sum_omset, 0, ',', '.'); ?></td>
                        <td class="text-end text-muted py-3">Rp <?= number_format($sum_modal, 0, ',', '.'); ?></td>
                        <td class="text-end text-success py-3">Rp <?= number_format($sum_laba, 0, ',', '.'); ?></td>
                    </tr>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada data rekapitulasi untuk periode ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Penutup tag pembuka dari header.php tanpa error file footer.php -->
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>