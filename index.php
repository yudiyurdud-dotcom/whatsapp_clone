<?php
// Kode ini diletakkan di C:\Users\User\Downloads\whatsapp_clone\index.php

require_once 'config.php';

// Jika user sudah login, langsung arahkan ke halaman profil (atau chat nanti)
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
            // Set Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            
            // Arahkan ke halaman profil (nanti bisa diubah ke chat.php)
            header("Location: main_panel.php");
            exit();
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
    <title><?php echo htmlspecialchars(WEB_NAME); ?></title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #ece5dd; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .auth-container { background: white; padding: 30px; border-radius: 10px; width: 100%; max-width: 400px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .tabs { display: flex; margin-bottom: 20px; cursor: pointer; border-bottom: 2px solid #ddd; }
        .tab { flex: 1; text-align: center; padding: 10px; font-weight: bold; color: #555; }
        .tab.active { color: #25D366; border-bottom: 2px solid #25D366; }
        .form-section { display: none; }
        .form-section.active { display: block; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .btn { width: 100%; padding: 10px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; background-color: #25D366; color: white; margin-top: 10px; }
        .alert { padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; }
        .alert-error { background-color: #ffdddd; color: red; }
        .alert-success { background-color: #ddffdd; color: green; }
    </style>
</head>
<body>

    <div class="auth-container">
        <h2 style="text-align: center; color: #25D366; margin-top:0;">WhatsApp Clone</h2>

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
                    <label>Nama Tampilan (Bisa diubah nanti)</label>
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
            // Reset active states
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.form-section').forEach(f => f.classList.remove('active'));

            // Set clicked tab to active
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