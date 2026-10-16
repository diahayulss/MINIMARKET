<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Cek autentikasi sederhana
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
$current_page = basename($_SERVER['PHP_SELF']);

// Menentukan judul halaman berdasarkan file yang sedang dibuka
$page_titles = [
    'index.php' => 'Dashboard Admin',
    'pelanggan.php' => 'Data Pelanggan',
    'user.php' => 'Data User',
    'transaksi.php' => 'Kelola Transaksi',
    'riwayat.php' => 'Riwayat Penjualan',
    'laporan.php' => 'Laporan Penjualan',
    'produk.php' => 'Stok Produk',
    'struk.php' => 'Cetak Struk'
];
$current_title = $page_titles[$current_page] ?? 'Dashboard Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $current_title; ?> - Toko Baju Coer</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --pastel-blue: #e2eafc;
            --pastel-pink-soft: #ffe5e5;
            --text-dark: #4a5568;
            --text-green: #2f855a;
            --bg-main: #f8fafc;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-dark);
        }
        /* Top Navbar Styling - Tinggi dan posisi disamakan */
        .top-navbar {
            height: 75px;
            background-color: #ffffff;
            border-bottom: 1px solid #edf2f7;
            position: fixed;
            top: 0;
            right: 0;
            left: 260px;
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
        }
        /* Sidebar Styling */
        .sidebar {
            height: 100vh;
            width: 260px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #ffffff;
            border-right: 1px solid #edf2f7;
            display: flex;
            flex-direction: column;
            z-index: 1000;
            padding: 0;
        }
        .sidebar-brand {
            height: 75px;
            padding: 0 20px;
            border-bottom: 1px solid #edf2f7;
            display: flex;
            align-items: center;
            gap: 12px;
            background-color: #ffffff;
        }
        .brand-icon-box {
            width: 42px;
            height: 42px;
            background: #e6f4ea;
            color: #2f855a;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.25rem;
        }
        .sidebar-menu {
            overflow-y: auto;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 5px;
            padding: 15px;
        }
        .nav-link-custom {
            font-weight: 500;
            color: #718096;
            padding: 12px 18px;
            border-radius: 12px;
            transition: all 0.2s ease-in-out;
            display: flex;
            align-items: center;
            text-decoration: none;
            font-size: 0.95rem;
        }
        .nav-link-custom:hover {
            background-color: #f7fafc;
            color: #2d3748;
        }
        .nav-link-custom.active {
            background-color: var(--pastel-blue);
            color: #1a365d;
            font-weight: 600;
        }
        .sidebar-footer {
            padding: 15px;
            border-top: 1px solid #edf2f7;
        }
        .btn-logout {
            background-color: var(--pastel-pink-soft);
            color: #e53e3e;
            font-weight: 600;
            border-radius: 12px;
            padding: 12px 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: 0.2s;
        }
        .btn-logout:hover {
            background-color: #fed7d7;
            color: #c53030;
        }
        /* Main Content Layout */
        .main-content {
            margin-left: 260px;
            margin-top: 75px;
            padding: 30px;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .top-navbar {
                left: 0;
                position: relative;
            }
            .main-content {
                margin-left: 0;
                margin-top: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <div class="d-flex align-items-center">
            <i class="bi bi-shop fs-4 me-2" style="color: #2f855a;"></i>
            <span class="fw-bold fs-5" style="color: #2f855a;"><?= $current_title; ?></span>
        </div>
        <div class="d-flex align-items-center gap-4 text-muted small fw-medium">
            <div>
                <i class="bi bi-calendar-event me-1"></i> <?= date('d M Y'); ?>
            </div>
            <div class="fw-bold text-dark">
                <?= htmlspecialchars(ucfirst($_SESSION['username'])); ?>
            </div>
        </div>
    </div>

    <!-- Sidebar / Navigasi Samping Kiri -->
    <div class="sidebar">
        <!-- Brand / Logo & Nama Toko -->
        <div class="sidebar-brand">
            <div class="brand-icon-box shadow-sm">
                <i class="bi bi-bag-heart-fill"></i>
            </div>
            <div>
                <span class="text-uppercase text-muted fw-bold" style="font-size: 9px; letter-spacing: 0.5px; display: block;">Aplikasi Kasir</span>
                <h6 class="fw-bold mb-0 text-dark" style="color: #2f855a !important;">Toko Baju Coer</h6>
            </div>
        </div>

        <div class="sidebar-menu">
            <a class="nav-link-custom <?= ($current_page == 'index.php') ? 'active' : ''; ?>" href="index.php">
                <i class="bi bi-house-door-fill me-3 fs-5"></i> Beranda / Dashboard
            </a>
            <a class="nav-link-custom <?= ($current_page == 'pelanggan.php') ? 'active' : ''; ?>" href="pelanggan.php">
                <i class="bi bi-people-fill me-3 fs-5"></i> Data Pelanggan
            </a>
            <a class="nav-link-custom <?= ($current_page == 'user.php') ? 'active' : ''; ?>" href="user.php">
                <i class="bi bi-person-badge-fill me-3 fs-5"></i> Data User
            </a>
            <a class="nav-link-custom <?= ($current_page == 'transaksi.php') ? 'active' : ''; ?>" href="transaksi.php">
                <i class="bi bi-cart-fill me-3 fs-5"></i> Kelola Transaksi
            </a>
            <a class="nav-link-custom <?= ($current_page == 'laporan.php') ? 'active' : ''; ?>" href="laporan.php">
                <i class="bi bi-graph-up-arrow me-3 fs-5"></i> Laporan Penjualan
            </a>
            <a class="nav-link-custom <?= ($current_page == 'produk.php') ? 'active' : ''; ?>" href="produk.php">
                <i class="bi bi-box-seam-fill me-3 fs-5"></i> Stok Produk
            </a>
        </div>

        <!-- Tombol Keluar di Bawah Sidebar -->
        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout">
                <i class="bi bi-box-arrow-right me-2 fs-5"></i> Keluar
            </a>
        </div>
    </div>

    <!-- Wrapper Konten Utama -->
    <div class="main-content">