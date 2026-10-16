<?php
session_start();
include 'header.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// CEK LOGIN
if (!isset($_SESSION['user_id'])) {
    echo "<script>
        alert('Silakan login terlebih dahulu!');
        window.location='login.php';
    </script>";
    exit;
}

// AMBIL DATA USER YANG SEDANG LOGIN
$user_id = (int) $_SESSION['user_id'];
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// CEK KERANJANG
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 1. TAMBAH PRODUK KE KERANJANG (Stok langsung berkurang di database)
if (isset($_GET['tambah_cart'])) {
    $id_produk = (int) $_GET['tambah_cart'];
    $cek_stok = mysqli_query(
        $koneksi,
        "SELECT * FROM produk WHERE ProdukID = '$id_produk'"
    );
    $prod = mysqli_fetch_assoc($cek_stok);
    
    if ($prod && $prod['Stok'] > 0) {
        mysqli_query(
            $koneksi,
            "UPDATE produk SET Stok = Stok - 1 WHERE ProdukID = '$id_produk'"
        );

        if (isset($_SESSION['cart'][$id_produk])) {
            $_SESSION['cart'][$id_produk]['jumlah'] += 1;
        } else {
            $_SESSION['cart'][$id_produk] = [
                'ProdukID'   => $prod['ProdukID'],
                'NamaProduk' => $prod['NamaProduk'],
                'Harga'      => $prod['Harga'],
                'jumlah'     => 1
            ];
        }
    } else {
        echo "<script>
            alert('Stok produk habis!');
        </script>";
    }
    
    echo "<script>
        window.location='transaksi.php';
    </script>";
    exit;
}

// 2. KURANGI JUMLAH PRODUK DI KERANJANG
if (isset($_GET['kurang_cart'])) {
    $id_produk = (int) $_GET['kurang_cart'];
    if (isset($_SESSION['cart'][$id_produk])) {
        if ($_SESSION['cart'][$id_produk]['jumlah'] > 1) {
            $_SESSION['cart'][$id_produk]['jumlah'] -= 1;
            mysqli_query(
                $koneksi,
                "UPDATE produk SET Stok = Stok + 1 WHERE ProdukID = '$id_produk'"
            );
        } else {
            $jml = $_SESSION['cart'][$id_produk]['jumlah'];
            mysqli_query(
                $koneksi,
                "UPDATE produk SET Stok = Stok + $jml WHERE ProdukID = '$id_produk'"
            );
            unset($_SESSION['cart'][$id_produk]);
        }
    }
    echo "<script>
        window.location='transaksi.php';
    </script>";
    exit;
}

// 3. HAPUS ITEM DARI KERANJANG
if (isset($_GET['hapus_item'])) {
    $id_produk = (int) $_GET['hapus_item'];
    if (isset($_SESSION['cart'][$id_produk])) {
        $jml = $_SESSION['cart'][$id_produk]['jumlah'];
        mysqli_query(
            $koneksi,
            "UPDATE produk SET Stok = Stok + $jml WHERE ProdukID = '$id_produk'"
        );
        unset($_SESSION['cart'][$id_produk]);
    }
    echo "<script>
        window.location='transaksi.php';
    </script>";
    exit;
}

// 4. KOSONGKAN KERANJANG
if (isset($_GET['kosongkan'])) {
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $pid = (int) $item['ProdukID'];
            $jml = (int) $item['jumlah'];
            mysqli_query(
                $koneksi,
                "UPDATE produk SET Stok = Stok + $jml WHERE ProdukID = '$pid'"
            );
        }
        $_SESSION['cart'] = [];
    }
    echo "<script>
        window.location='transaksi.php';
    </script>";
    exit;
}

// 5. BATALKAN TRANSAKSI SAAT PRATINJAU STRUK
if (isset($_GET['batal_checkout'])) {
    $penjualan_id_batal = isset($_SESSION['pending_struk']['id']) ? (int) $_SESSION['pending_struk']['id'] : 0;

    if ($penjualan_id_batal > 0) {
        $q_detail = mysqli_query(
            $koneksi,
            "SELECT ProdukID, JumlahProduk FROM detailpenjualan WHERE PenjualanID = '$penjualan_id_batal'"
        );
        while ($row = mysqli_fetch_assoc($q_detail)) {
            $prod_id = (int) $row['ProdukID'];
            $jumlah_beli = (int) $row['JumlahProduk'];

            mysqli_query(
                $koneksi,
                "UPDATE produk SET Stok = Stok + $jumlah_beli WHERE ProdukID = '$prod_id'"
            );
        }

        mysqli_query($koneksi, "DELETE FROM detailpenjualan WHERE PenjualanID = '$penjualan_id_batal'");
        mysqli_query($koneksi, "DELETE FROM penjualan WHERE PenjualanID = '$penjualan_id_batal'");
    }

    unset($_SESSION['pending_struk']);
    echo "<script>
        alert('Transaksi dibatalkan. Stok berhasil dikembalikan!');
        window.location='transaksi.php';
    </script>";
    exit;
}

// 6. SELESAI TRANSAKSI -> PINDAH KE LAPORAN.PHP
if (isset($_GET['selesai_checkout'])) {
    unset($_SESSION['pending_struk']);
    echo "<script>
        window.location='laporan.php';
    </script>";
    exit;
}

// 7. PROSES CHECKOUT (PRATINJAU STRUK)
if (isset($_POST['checkout'])) {
    $pelangganid = isset($_POST['PelangganID']) ? (int) $_POST['PelangganID'] : 0;
    $metode_pembayaran = isset($_POST['MetodePembayaran']) ? mysqli_real_escape_string($koneksi, $_POST['MetodePembayaran']) : 'Cash';
    $tanggalpenjualan = date('Y-m-d');
    $waktu_penjualan = date('H:i:s');
    $datetime_lengkap = $tanggalpenjualan . ' ' . $waktu_penjualan;
    $user_id = (int) $_SESSION['user_id'];
    $total_harga = 0;

    foreach ($_SESSION['cart'] as $item) {
        $harga = (float) $item['Harga'];
        $jumlah = (int) $item['jumlah'];
        $total_harga += $harga * $jumlah;
    }

    if ($metode_pembayaran == 'Cash') {
        $uang_bayar = isset($_POST['UangBayar']) ? (float) $_POST['UangBayar'] : 0;
    } else {
        $uang_bayar = $total_harga;
    }

    if ($metode_pembayaran == 'Cash' && $uang_bayar < $total_harga) {
        echo "<script>
            alert('Uang bayar kurang dari total belanja!');
        </script>";
    } elseif ($total_harga > 0 && !empty($_SESSION['cart']) && $pelangganid > 0) {

        $query_penjualan = mysqli_query(
            $koneksi,
            "INSERT INTO penjualan (TanggalPenjualan, TotalHarga, PelangganID, user_id)
             VALUES ('$datetime_lengkap', '$total_harga', '$pelangganid', '$user_id')"
        );

        if ($query_penjualan) {
            $penjualan_id = mysqli_insert_id($koneksi);

            foreach ($_SESSION['cart'] as $item) {
                $pid = (int) $item['ProdukID'];
                $jml = (int) $item['jumlah'];
                $sub = $item['Harga'] * $jml;

                mysqli_query(
                    $koneksi,
                    "INSERT INTO detailpenjualan (PenjualanID, ProdukID, JumlahProduk, Subtotal)
                     VALUES ('$penjualan_id', '$pid', '$jml', '$sub')"
                );
            }

            $q_pelanggan = mysqli_query(
                $koneksi,
                "SELECT NamaPelanggan FROM pelanggan WHERE PelangganID = '$pelangganid'"
            );
            $d_pelanggan = mysqli_fetch_assoc($q_pelanggan);

            $_SESSION['pending_struk'] = [
                'id' => $penjualan_id,
                'tanggal' => $datetime_lengkap,
                'pelanggan' => $d_pelanggan ? $d_pelanggan['NamaPelanggan'] : 'Umum',
                'items' => $_SESSION['cart'],
                'total' => $total_harga,
                'metode' => $metode_pembayaran,
                'bayar' => $uang_bayar,
                'kembalian' => $uang_bayar - $total_harga,
                'username' => $username
            ];

            unset($_SESSION['cart']);
        } else {
            echo "<script>
                alert('Gagal memproses transaksi: " . mysqli_error($koneksi) . "');
            </script>";
        }
    } else {
        echo "<script>
            alert('Pelanggan dan produk harus dipilih!');
        </script>";
    }
}

$search = '';
if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($koneksi, $_GET['search']);
    $query_produk = mysqli_query(
        $koneksi,
        "SELECT * FROM produk WHERE NamaProduk LIKE '%$search%' ORDER BY ProdukID DESC"
    );
} else {
    $query_produk = mysqli_query(
        $koneksi,
        "SELECT * FROM produk ORDER BY ProdukID DESC"
    );
}

$query_pelanggan = mysqli_query(
    $koneksi,
    "SELECT * FROM pelanggan ORDER BY NamaPelanggan ASC"
);
?>

<style>
.card-custom {
    border: none;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    background: #ffffff;
    padding: 25px;
}
.product-card {
    border: 1px solid #edf2f7;
    border-radius: 16px;
    background: #fff;
    transition: 0.2s;
    padding: 15px;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.product-card:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,0.06);
    border-color: #cbd5e0;
}
.product-img-placeholder {
    height: 110px;
    background: #f7fafc;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #a0aec0;
    font-size: 2rem;
    margin-bottom: 12px;
}
.btn-add-cart {
    background-color: #38a169;
    color: white;
    border: none;
    border-radius: 10px;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.btn-add-cart:hover {
    background-color: #2f855a;
    color: white;
}
.cart-box {
    background: #ffffff;
    border-radius: 20px;
    padding: 22px;
    border: 1px solid #edf2f7;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
}
.cart-item-box {
    background: #f8fafc;
    border-radius: 12px;
    padding: 12px;
    margin-bottom: 10px;
    border: 1px solid #f1f5f9;
}
.qty-btn {
    background: #fff;
    border: 1px solid #cbd5e0;
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #2d3748;
    font-weight: bold;
}
.qty-btn:hover {
    background: #edf2f7;
}
.form-control, .form-select {
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    padding: 10px 14px;
}
@media print {
    body * { visibility: hidden; }
    .print-struk, .print-struk * { visibility: visible; }
    .print-struk {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        background: white;
        padding: 20px;
    }
    .no-print { display: none !important; }
}
</style>

<!-- JIKA ADA TRANSAKSI YANG SEDANG TAMPIL (PRATINJAU STRUK) -->
<?php if (isset($_SESSION['pending_struk'])): 
    $s = $_SESSION['pending_struk'];
?>
<div class="card card-custom mb-4 border border-success print-struk" style="max-width: 400px; margin: auto;">
    <div class="text-center mb-3">
        <h4 class="fw-bold text-dark mb-1">APLIKASI KASIR</h4>
        <p class="text-muted small mb-0">Jl. Sistem Informasi No. 45</p>
        <hr style="border-style: dashed;">
    </div>
    <div class="row small mb-2">
        <div class="col-6">No TRX: <strong>#TRX-<?= sprintf("%04d", $s['id']); ?></strong></div>
        <div class="col-6 text-end"><?= $s['tanggal']; ?></div>
        <div class="col-12 mt-1">Pelanggan: <strong><?= htmlspecialchars($s['pelanggan']); ?></strong></div>
    </div>
    <table class="table table-sm align-middle mb-2" style="font-size: 13px;">
        <thead>
            <tr class="border-bottom">
                <th>Item</th>
                <th>Qty</th>
                <th class="text-end">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($s['items'] as $it): ?>
            <tr>
                <td><?= htmlspecialchars($it['NamaProduk']); ?></td>
                <td><?= $it['jumlah']; ?></td>
                <td class="text-end">Rp <?= number_format($it['Harga'] * $it['jumlah'], 0, ',', '.'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="border-top pt-2 small">
        <div class="d-flex justify-content-between">
            <span>Total:</span>
            <span>Rp <?= number_format($s['total'], 0, ',', '.'); ?></span>
        </div>
        <div class="d-flex justify-content-between text-muted mt-1">
            <span>Metode:</span>
            <span><?= htmlspecialchars($s['metode']); ?></span>
        </div>
        <?php if ($s['metode'] == 'Cash'): ?>
        <div class="d-flex justify-content-between text-muted">
            <span>Uang Bayar:</span>
            <span>Rp <?= number_format($s['bayar'], 0, ',', '.'); ?></span>
        </div>
        <div class="d-flex justify-content-between text-success fw-semibold">
            <span>Kembalian:</span>
            <span>Rp <?= number_format($s['kembalian'], 0, ',', '.'); ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- TOMBOL AKSI STRUK (SELESAI AKAN LARI KE LAPORAN.PHP) -->
    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn btn-dark btn-sm rounded-pill px-3 mb-2">
            <i class="bi bi-printer me-1"></i> Cetak Struk
        </button>
        <br>
        <a href="transaksi.php?selesai_checkout=true" class="btn btn-success btn-sm rounded-pill px-4 me-1">
            Selesai
        </a>
        <a href="transaksi.php?batal_checkout=true" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Batalkan transaksi ini? Stok produk akan dikembalikan.');">
            Batalkan Transaksi
        </a>
    </div>
</div>
<?php else: ?>

<!-- HALAMAN UTAMA (KATALOG & KERANJANG) -->
<div class="row g-4 no-print">
    <div class="col-lg-7">
        <div class="card card-custom mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-grid me-2 text-muted"></i> Katalog Produk
                </h5>
                <form method="GET" action="" class="d-flex" style="max-width: 250px;">
                    <input type="text" name="search" class="form-control form-control-sm me-2" placeholder="Cari produk..." value="<?= htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-outline-secondary btn-sm rounded-10 px-3">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>
            <div class="row g-3" style="max-height: 520px; overflow-y: auto; padding-right: 5px;">
                <?php
                if (mysqli_num_rows($query_produk) > 0) {
                    while ($p = mysqli_fetch_assoc($query_produk)) {
                ?>
                <div class="col-md-4 col-sm-6">
                    <div class="product-card">
                        <div>
                            <div class="product-img-placeholder">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <h6 class="fw-semibold text-dark text-truncate mb-1">
                                <?= htmlspecialchars($p['NamaProduk']); ?>
                            </h6>
                            <p class="text-success fw-bold mb-1" style="font-size: 0.95rem;">
                                Rp <?= number_format($p['Harga'], 0, ',', '.'); ?>
                            </p>
                            <span class="text-muted" style="font-size: 11px;">
                                Stok: <?= $p['Stok']; ?>
                            </span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mt-3 pt-2 border-top">
                            <span class="badge bg-light text-dark border" style="font-size: 10px;">Produk</span>
                            <?php if ($p['Stok'] > 0): ?>
                            <a href="transaksi.php?tambah_cart=<?= $p['ProdukID']; ?>" class="btn-add-cart shadow-sm" title="Tambah">
                                <i class="bi bi-plus-lg"></i>
                            </a>
                            <?php else: ?>
                            <button class="btn btn-secondary btn-sm opacity-50" disabled>
                                <i class="bi bi-x"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php
                    }
                } else {
                    echo '<div class="col-12 text-center py-4 text-muted">Produk tidak ditemukan.</div>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-5">
        <div class="cart-box">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <span class="text-uppercase text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">KERANJANG</span>
                    <h5 class="fw-bold mb-0 text-dark">Pesanan Saya</h5>
                </div>
                <a href="transaksi.php?kosongkan=true" class="btn btn-outline-danger btn-sm rounded-pill px-3" style="font-size: 12px;" onclick="return confirm('Kosongkan keranjang dan kembalikan stok?')">
                    Kosongkan
                </a>
            </div>
            <form method="POST" action="">
                <!-- PELANGGAN -->
                <div class="mb-3">
                    <select name="PelangganID" class="form-select form-select-sm" required>
                        <option value="" disabled selected>-- Pilih Pelanggan --</option>
                        <?php
                        mysqli_data_seek($query_pelanggan, 0);
                        while ($pel = mysqli_fetch_assoc($query_pelanggan)) {
                        ?>
                        <option value="<?= $pel['PelangganID']; ?>">
                            <?= htmlspecialchars($pel['NamaPelanggan']); ?>
                        </option>
                        <?php } ?>
                    </select>
                </div>
                
                <!-- ISI KERANJANG -->
                <div style="max-height: 220px; overflow-y: auto; padding-right: 4px;" class="mb-3">
                    <?php
                    $subtotal_cart = 0;
                    if (!empty($_SESSION['cart'])) {
                        foreach ($_SESSION['cart'] as $id => $item) {
                            $sub = $item['Harga'] * $item['jumlah'];
                            $subtotal_cart += $sub;
                    ?>
                    <div class="cart-item-box">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <span class="fw-bold text-dark small"><?= htmlspecialchars($item['NamaProduk']); ?></span>
                            <span class="fw-bold text-dark small">Rp <?= number_format($sub, 0, ',', '.'); ?></span>
                        </div>
                        <div class="text-muted mb-2" style="font-size: 11px;">
                            Rp <?= number_format($item['Harga'], 0, ',', '.'); ?> × <?= $item['jumlah']; ?>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-1">
                                <a href="transaksi.php?kurang_cart=<?= $id; ?>" class="qty-btn">-</a>
                                <span class="px-2 fw-semibold small"><?= $item['jumlah']; ?></span>
                                <a href="transaksi.php?tambah_cart=<?= $id; ?>" class="qty-btn">+</a>
                            </div>
                            <a href="transaksi.php?hapus_item=<?= $id; ?>" class="text-danger small text-decoration-none fw-semibold" style="font-size: 12px;">Hapus</a>
                        </div>
                    </div>
                    <?php
                        }
                    } else {
                        echo '<div class="text-center text-muted py-4 small">Keranjang masih kosong</div>';
                    }
                    ?>
                </div>
                
                <!-- TOTAL -->
                <div class="border-top pt-2 small mb-3">
                    <div class="d-flex justify-content-between fw-bold text-dark fs-5">
                        <span>Total</span>
                        <span>Rp <?= number_format($subtotal_cart, 0, ',', '.'); ?></span>
                    </div>
                </div>
                
                <!-- METODE PEMBAYARAN -->
                <div class="mb-2">
                    <label class="form-label text-muted small fw-semibold">Metode Pembayaran</label>
                    <select name="MetodePembayaran" id="metodePembayaran" class="form-select form-select-sm" onchange="toggleBayar()" required>
                        <option value="Cash">Cash (Tunai)</option>
                        <option value="Debit">Debit / Transfer</option>
                    </select>
                </div>
                
                <!-- UANG BAYAR -->
                <div class="mb-3" id="formUangBayar">
                    <label class="form-label text-muted small fw-semibold">Uang Bayar</label>
                    <input type="number" name="UangBayar" id="uangBayarInput" class="form-control form-control-sm" placeholder="Nominal uang..." oninput="hittungKembalian(<?= $subtotal_cart; ?>)">
                    <div class="d-flex justify-content-between text-muted mt-1" style="font-size: 12px;">
                        <span>Kembalian:</span>
                        <strong id="labelKembalian" class="text-success"> Rp 0</strong>
                    </div>
                </div>
                
                <button type="submit" name="checkout" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold shadow-sm" style="background-color: #2b6cb0; border: none;" <?= empty($_SESSION['cart']) ? 'disabled' : ''; ?>>
                    Simpan & Cetak Struk &rarr;
                </button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function toggleBayar() {
    let metode = document.getElementById('metodePembayaran').value;
    let formUang = document.getElementById('formUangBayar');
    if (metode === 'Debit') {
        formUang.style.display = 'none';
    } else {
        formUang.style.display = 'block';
    }
}

function hittungKembalian(totalBelanja) {
    let uangBayar = parseFloat(document.getElementById('uangBayarInput').value) || 0;
    let kembalian = uangBayar - totalBelanja;
    let label = document.getElementById('labelKembalian');
    
    if (kembalian >= 0) {
        label.innerText = 'Rp ' + kembalian.toLocaleString('id-ID');
        label.className = 'text-success fw-bold';
    } else {
        label.innerText = 'Uang Kurang';
        label.className = 'text-danger fw-bold';
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>