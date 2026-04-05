<?php
// Kode ini diletakkan di C:\Users\User\Downloads\whatsapp_clone\profile.php

session_start();
require_once 'config.php';

$user_id = $_SESSION['user_id'];
$message = '';

// Proses ketika tombol "Simpan Perubahan" ditekan (Untuk Nama & Username)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $display_name = trim($_POST['display_name']);
    $username = trim($_POST['username']);

    try {
        // Cek apakah username sudah dipakai orang lain (selain diri sendiri)
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username AND id != :id");
        $stmt->execute(['username' => $username, 'id' => $user_id]);
        
        if ($stmt->rowCount() > 0) {
            $message = "<div class='alert alert-error' style='color:red; margin-bottom:10px;'>Username sudah digunakan oleh pengguna lain!</div>";
        } else {
            // Update nama dan username di database
            $update_stmt = $conn->prepare("UPDATE users SET display_name = :display_name, username = :username WHERE id = :id");
            $update_stmt->execute([
                'display_name' => $display_name,
                'username' => $username,
                'id' => $user_id
            ]);
            $message = "<div class='alert alert-success' style='color:green; margin-bottom:10px;'>Profil berhasil diperbarui!</div>";
        }
    } catch(PDOException $e) {
        $message = "<div class='alert alert-error' style='color:red;'>Error: " . $e->getMessage() . "</div>";
    }
}

// Ambil data user saat ini dari database untuk ditampilkan di form
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Gunakan gambar default jika user belum punya avatar
$avatar_url = !empty($user['avatar_url']) ? $user['avatar_url'] : 'https://i.ibb.co/30B37f8/default-avatar.png'; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - WhatsApp Clone</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* CSS PERBAIKAN: Hapus flex di body agar bisa digulir (scroll) */
        body { 
            font-family: Arial, sans-serif; 
            background-color: #111b21; /* Tema gelap */
            margin: 0; 
            padding: 20px; 
            display: block; 
            overflow-y: auto !important; 
        }
        /* Bikin form di tengah menggunakan margin auto */
        .profile-container { 
            background: white; 
            padding: 30px; 
            border-radius: 10px; 
            width: 100%; 
            max-width: 400px; 
            margin: 40px auto; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.3); 
            color: #333; /* Teks dalam form hitam agar jelas */
        }
        .header-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header-nav a { text-decoration: none; background: #128C7E; color: white; padding: 8px 15px; border-radius: 5px; font-size: 14px; font-weight: bold; }
        .avatar-section { text-align: center; margin-bottom: 20px; }
        .avatar-section img { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #25D366; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .btn { width: 100%; padding: 10px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; margin-top: 10px; }
        .btn-green { background-color: #25D366; color: white; }
    </style>
</head>
<body>

    <div class="profile-container">
        <div class="header-nav">
            <h2 style="margin: 0;">Pengaturan Profil</h2>
            <a href="main_panel.php">⬅ Kembali</a>
        </div>
        
        <?php echo $message; ?>

        <div class="avatar-section">
            <img id="profile-picture" src="<?php echo htmlspecialchars($avatar_url); ?>" alt="Avatar">
            <br><br>
            
            <input type="file" id="avatarInput" accept="image/*" 
                   style="display: block; margin: 0 auto 10px auto;" 
                   onchange="updateAvatarAndRefresh('avatarInput', <?php echo $user_id; ?>)">
            
            <p id="upload-status" style="font-size: 14px; color: #128C7E; font-weight: bold; display: none;">Sedang Mengunggah Gambar...</p>
        </div>

        <hr style="border-top: 1px solid #eee; margin: 20px 0;">

        <form action="profile.php" method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                <small style="color: gray;">Username ini harus unik.</small>
            </div>
            
            <div class="form-group">
                <label>Nama Tampilan (Display Name)</label>
                <input type="text" name="display_name" value="<?php echo htmlspecialchars($user['display_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled style="background-color: #f5f5f5; color: #888;">
                <small style="color: gray;">Email tidak bisa diubah.</small>
            </div>

            <button type="submit" name="update_profile" class="btn btn-green">Simpan Perubahan</button>
        </form>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        // PERBAIKAN: Logika Script khusus untuk Auto-Upload tanpa tombol
        async function updateAvatarAndRefresh(fileInputId, userId) {
            const fileInput = document.getElementById(fileInputId);
            
            if (!fileInput.files[0]) {
                return; // Jika batal memilih file, berhenti.
            }

            // Tampilkan teks status loading
            const statusText = document.getElementById('upload-status');
            statusText.style.display = 'block';
            fileInput.style.display = 'none'; // Sembunyikan input file saat loading

            // Tunggu fungsi upload selesai
            await updateAvatar(fileInputId, userId);
            
            // Segarkan (Refresh) halaman untuk melihat foto baru
            location.reload();
        }
    </script>
</body>
</html>