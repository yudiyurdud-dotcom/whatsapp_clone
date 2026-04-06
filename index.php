<?php
// Kode ini diletakkan di PATH_FOLDER/index.php

require_once 'config.php';

// Jika user sudah login, langsung arahkan ke halaman utama
if (isset($_SESSION['user_id'])) {
    header("Location: main_panel.php");
    exit();
}

$error_message = '';
$success_message = '';

// Logika untuk proses Registrasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['reg_username']);
    $email = trim($_POST['reg_email']);
    $display_name = trim($_POST['reg_display_name']);
    $password = $_POST['reg_password'];

    // Enkripsi password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Cek apakah username atau email sudah ada
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
        $check_stmt->execute(['username' => $username, 'email' => $email]);

        if ($check_stmt->rowCount() > 0) {
            $error_message = "Username atau Email sudah terdaftar!";
        } else {
            // Masukkan data ke database
            $insert_stmt = $conn->prepare("INSERT INTO users (username, email, display_name, password) VALUES (:username, :email, :display_name, :password)");
            $insert_stmt->execute([
                'username' => $username,
                'email' => $email,
                'display_name' => $display_name,
                'password' => $hashed_password
            ]);
            $success_message = "Pendaftaran berhasil! Silakan Login.";
        }
    } catch (PDOException $e) {
        $error_message = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Logika untuk proses Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $login_id = trim($_POST['login_id']); // Bisa berupa username atau email
    $password = $_POST['login_password'];

    try {
        // Cari user berdasarkan username ATAU email
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :login_id OR email = :login_id");
        $stmt->execute(['login_id' => $login_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifikasi password
        if ($user && password_verify($password, $user['password'])) {

            // Cek apakah akun ini sedang diblokir oleh Admin
            if ($user['is_blocked'] == 1) {
                $error_message = "Akses Ditolak! Akun Anda telah diblokir oleh Admin.";
            } else {
                // Set Session jika tidak diblokir
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                
                // Arahkan ke halaman chat setelah berhasil login
                header("Location: main_panel.php");
                exit();
            }

        } else {
            $error_message = "Username/Email atau Password salah!";
        }
    } catch (PDOException $e) {
        $error_message = "Terjadi kesalahan: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(WEB_NAME); ?> - Masuk</title>
    <style>
        /* TEMA GELAP UNTUK HALAMAN AUTENTIKASI */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #111b21; color: #e9edef; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        .auth-container { background: #202c33; padding: 30px; border-radius: 10px; width: 100%; max-width: 400px; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
        .tabs { display: flex; margin-bottom: 20px; cursor: pointer; border-bottom: 2px solid #2a3942; }
        .tab { flex: 1; text-align: center; padding: 10px; font-weight: bold; color: #aebac1; transition: 0.3s; }
        .tab:hover { color: #e9edef; }
        .tab.active { color: #25D366; border-bottom: 2px solid #25D366; }
        .form-section { display: none; }
        .form-section.active { display: block; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #aebac1; font-size: 14px; }
        .form-group input { width: 100%; padding: 12px; background-color: #111b21; border: 1px solid #2a3942; color: #e9edef; border-radius: 5px; box-sizing: border-box; outline: none; transition: border 0.3s; }
        .form-group input:focus { border-color: #25D366; }
        .form-group input::placeholder { color: #8696a0; }
        .btn { width: 100%; padding: 12px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; background-color: #005c4b; color: white; margin-top: 10px; transition: background 0.3s; font-size: 15px; }
        .btn:hover { background-color: #008f75; }
        .alert { padding: 12px; border-radius: 5px; margin-bottom: 15px; text-align: center; font-weight: bold; font-size: 14px; }
        .alert-error { background-color: #ea0038; color: white; }
        .alert-success { background-color: #25D366; color: white; }
    </style>
</head>
<body>

    <div class="auth-container">
        <h2 style="text-align: center; color: #25D366; margin-top:0;"><?php echo htmlspecialchars(WEB_NAME); ?></h2>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <div class="tab active" onclick="switchTab('login')">Masuk</div>
            <div class="tab" onclick="switchTab('register')">Daftar</div>
        </div>

        <div id="form-login" class="form-section active">
            <form action="index.php" method="POST">
                <div class="form-group">
                    <label>Username atau Email</label>
                    <input type="text" name="login_id" required placeholder="Masukkan Username / Email">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="login_password" required placeholder="Masukkan Password">
                </div>
                <button type="submit" name="login" class="btn">Masuk</button>
            </form>
        </div>

        <div id="form-register" class="form-section">
            <form action="index.php" method="POST">
                <div class="form-group">
                    <label>Nama Tampilan</label>
                    <input type="text" name="reg_display_name" required placeholder="Contoh: Miyamura">
                </div>
                <div class="form-group">
                    <label>Username (Harus Unik)</label>
                    <input type="text" name="reg_username" required placeholder="Contoh: miyamura99">
                </div>
                <div class="form-group">
                    <label>Email (Harus Unik)</label>
                    <input type="email" name="reg_email" required placeholder="email@contoh.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="reg_password" required placeholder="Buat Password">
                </div>
                <button type="submit" name="register" class="btn">Daftar Akun</button>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.form-section').forEach(f => f.classList.remove('active'));

            if (tabName === 'login') {
                document.querySelectorAll('.tab')[0].classList.add('active');
                document.getElementById('form-login').classList.add('active');
            } else {
                document.querySelectorAll('.tab')[1].classList.add('active');
                document.getElementById('form-register').classList.add('active');
            }
        }
    </script>
</body>
</html>