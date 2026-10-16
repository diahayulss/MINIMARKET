<?php
session_start();
include 'koneksi.php';

$error = '';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    $query = mysqli_query($koneksi, "SELECT * FROM user WHERE username='$username' AND password='$password'");

    if (mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_assoc($query);

        $_SESSION['user_id']  = $row['user_id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role']     = $row['role'];

        if ($row['role'] == 'admin') {
            header("Location: index.php");
            exit;
        } elseif ($row['role'] == 'kasir') {
            header("Location: index_kasir.php");
            exit;
        } else {
            $error = "Role pengguna tidak dikenali!";
        }
    } else {
        $error = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aplikasi Kasir</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #fbc53120, #e8411810, #f5cd7940); background-color: #fcf8f7; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card-login { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05); background: #ffffff; padding: 2rem; width: 100%; max-width: 400px; }
        .btn-pastel { background-color: #b5ead7; border: none; color: #2d3436; font-weight: 500; border-radius: 10px; padding: 10px; transition: 0.3s; }
        .btn-pastel:hover { background-color: #97d4be; color: #000; }
        .form-control { border-radius: 10px; background-color: #fdfbfb; border: 1px solid #e2dede; padding: 12px; }
        .form-control:focus { box-shadow: none; border-color: #b5ead7; background-color: #fff; }
    </style>
</head>
<body>

    <div class="card card-login">
        <div class="text-center mb-4">
            <h4 class="fw-bold" style="color: #6c5ce7;">Selamat Datang ✨</h4>
            <p class="text-muted small">Silakan masuk ke akun Anda</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small text-center" role="alert" style="border-radius: 10px;"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label small text-muted fw-semibold">Username</label>
                <input type="text" name="username" class="form-control" required autocomplete="off">
            </div>

            <div class="mb-4">
                <label class="form-label small text-muted fw-semibold">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button type="submit" name="login" class="btn btn-pastel w-100 shadow-sm">Masuk</button>
        </form>
    </div>

</body>
</html>