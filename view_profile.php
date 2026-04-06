<?php
// Kode ini diletakkan di PATH_FOLDER/view_profile.php
require_once 'config.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$target_id = $_GET['id'] ?? null;

// Jika tidak ada ID yang dikirim
if (!$target_id) {
    die("<h2 style='text-align:center; color:#e9edef; margin-top:50px;'>Pengguna tidak ditemukan.</h2>");
}

// Ambil data profil HANYA data publik (Email disembunyikan)
$stmt = $conn->prepare("SELECT username, display_name, avatar_url, bio, role FROM users WHERE id = :id");
$stmt->execute(['id' => $target_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("<h2 style='text-align:center; color:#e9edef; margin-top:50px;'>Pengguna tidak ditemukan.</h2>");
}

// Set avatar bawaan jika kosong
$avatar = !empty($user['avatar_url']) ? $user['avatar_url'] : 'https://i.ibb.co/30B37f8/default-avatar.png';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil <?php echo htmlspecialchars($user['display_name']); ?> - <?php echo htmlspecialchars(WEB_NAME); ?></title>
    <style>
        /* PERBAIKAN: Menggunakan min-height agar background gelap menutupi layar sampai bawah */
        body { 
            background-color: #111b21; 
            color: #e9edef; 
            font-family: sans-serif; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; /* Kunci utamanya ada di sini */
            margin: 0; 
            padding: 20px; 
            box-sizing: border-box; 
        }
        
        .profile-card { 
            background: #202c33; 
            padding: 30px 20px; /* Padding sedikit dikurangi agar tidak sumpek di HP */
            border-radius: 10px; 
            text-align: center; 
            max-width: 400px; 
            width: 100%; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.5); 
        }
        
        /* Ukuran avatar sedikit disesuaikan untuk HP */
        .profile-card img { 
            width: 120px; 
            height: 120px; 
            border-radius: 50%; 
            object-fit: cover; 
            border: 3px solid #25D366; 
            margin-bottom: 15px; 
        }
        
        .profile-card h2 { margin: 0 0 5px 0; color: #e9edef; font-size: 22px; }
        .profile-card h4 { margin: 0 0 15px 0; color: #aebac1; font-weight: normal; font-size: 14px; }
        
        /* Mencegah teks bio yang terlalu panjang merusak kotak */
        .profile-card .bio { 
            background: #111b21; 
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 25px; 
            color: #e9edef; 
            font-style: italic; 
            border-left: 4px solid #25D366; 
            font-size: 14px;
            line-height: 1.5;
            word-wrap: break-word; 
        }
        
        .profile-card .role { 
            display: inline-block; 
            background: #25D366; 
            color: white; 
            padding: 4px 12px; 
            border-radius: 20px; 
            font-size: 12px; 
            font-weight: bold; 
            margin-bottom: 20px; 
            text-transform: capitalize; 
        }
        
        .btn-back { 
            display: inline-block; 
            background: #3b4a54; 
            color: white; 
            padding: 10px 20px; 
            text-decoration: none; 
            border-radius: 5px; 
            font-weight: bold; 
            width: 100%; 
            box-sizing: border-box; 
            transition: background 0.3s;
        }
        
        .btn-back:hover { background: #2a3942; }
    </style>
</head>
<body>
    <div class="profile-card">
        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar">
        <h2><?php echo htmlspecialchars($user['display_name']); ?></h2>
        <h4>@<?php echo htmlspecialchars($user['username']); ?></h4>
        
        <?php if ($user['role'] === 'admin'): ?>
            <div class="role">👑 Admin</div>
        <?php else: ?>
            <div class="role" style="background: #3b4a54;">Pengguna</div>
        <?php endif; ?>

        <div class="bio">
            "<?php echo htmlspecialchars($user['bio'] ?? 'Halo, saya menggunakan ' . WEB_NAME . '.'); ?>"
        </div>

        <a href="main_panel.php" class="btn-back">Kembali ke Obrolan</a>
    </div>
</body>
</html>