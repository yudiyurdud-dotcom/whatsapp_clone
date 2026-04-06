<?php
// Kode ini diletakkan di C:\Users\User\Downloads\whatsapp_clone\admin.php

require_once '../config.php';

// Proteksi Halaman: Pastikan hanya ADMIN yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("<h2 style='text-align:center; color:#ff4d4d; margin-top:50px;'>Akses Ditolak! Anda bukan Admin.</h2>");
}

$message = '';

// 1. Proses Update Pengaturan Web
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $site_name = trim($_POST['site_name']);
    $site_description = trim($_POST['site_description']);

    $stmt = $conn->prepare("UPDATE site_settings SET site_name = :name, site_description = :desc WHERE id = 1");
    $stmt->execute(['name' => $site_name, 'desc' => $site_description]);
    $message = "<div class='alert alert-success'>Pengaturan web berhasil diperbarui!</div>";
}

// 2. Proses Tambah API Key ImgBB
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_api_key'])) {
    $new_api_key = trim($_POST['new_api_key']);
    
    try {
        $stmt = $conn->prepare("INSERT INTO imgbb_api_keys (api_key) VALUES (:api_key)");
        $stmt->execute(['api_key' => $new_api_key]);
        $message = "<div class='alert alert-success'>API Key baru berhasil ditambahkan!</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-error'>Gagal! API Key mungkin sudah ada.</div>";
    }
}

// 3. Proses Ubah Status API Key
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $key_id = $_GET['id'];
    $new_status = $_GET['toggle_status'];
    $stmt = $conn->prepare("UPDATE imgbb_api_keys SET status = :status WHERE id = :id");
    $stmt->execute(['status' => $new_status, 'id' => $key_id]);
    header("Location: index.php"); 
    exit();
}

// 4. Proses Hapus API Key
if (isset($_GET['delete_key']) && isset($_GET['id'])) {
    $key_id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM imgbb_api_keys WHERE id = :id");
    $stmt->execute(['id' => $key_id]);
    header("Location: index.php");
    exit();
}

// Ambil Data Pengaturan Web & API Key
$stmt_settings = $conn->query("SELECT * FROM site_settings WHERE id = 1");
$site_settings = $stmt_settings->fetch(PDO::FETCH_ASSOC);

$stmt_keys = $conn->query("SELECT * FROM imgbb_api_keys ORDER BY status ASC, usage_count ASC");
$api_keys = $stmt_keys->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - <?php echo htmlspecialchars(WEB_NAME); ?></title>
    <style>
        /* TEMA GELAP UNTUK ADMIN PANEL */
        body { font-family: Arial, sans-serif; background-color: #111b21; color: #e9edef; margin: 0; padding: 20px; }
        .admin-container { max-width: 1000px; margin: 0 auto; background: #202c33; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #2a3942; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #25D366; }
        .btn-logout { background-color: #2a3942; color: #e9edef; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; border: 1px solid #333; }
        .btn-logout:hover { background-color: #3a4b55; }
        
        .section { margin-bottom: 40px; padding: 20px; border: 1px solid #2a3942; border-radius: 8px; background-color: #111b21; }
        .section h2 { margin-top: 0; color: #e9edef; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #aebac1; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #2a3942; border-radius: 5px; box-sizing: border-box; background-color: #202c33; color: #e9edef; }
        .btn { background-color: #005c4b; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn:hover { background-color: #008f75; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #2a3942; }
        table th { background-color: #202c33; color: #aebac1; }
        .badge-active { background: #005c4b; color: #e9edef; padding: 5px 10px; border-radius: 20px; font-size: 12px; }
        .badge-failed { background: #ff4d4d; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; }
        .action-link { text-decoration: none; padding: 5px 10px; border-radius: 5px; font-size: 13px; margin-right: 5px; }
        .link-red { background: #ff4d4d; color: white; }
        .link-green { background: #005c4b; color: white; }
        .link-gray { background: #3a4b55; color: white; }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; font-weight: bold; }
        .alert-success { background-color: #005c4b; color: white; }
        .alert-error { background-color: #ff4d4d; color: white; }
    </style>
</head>
<body>

    <div class="admin-container">
        <div class="header">
            <h1>Panel Admin</h1>
            <a href="../main_panel.php" class="btn-logout" style="margin-right:10px;">Kembali ke Chat</a>
        </div>

        <?php echo $message; ?>

        <div class="section">
            <h2>Pengaturan Website</h2>
            <form action="" method="POST">
                <div class="form-group">
                    <label>Nama Website</label>
                    <input type="text" name="site_name" value="<?php echo htmlspecialchars($site_settings['site_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Deskripsi Website</label>
                    <textarea name="site_description" rows="3"><?php echo htmlspecialchars($site_settings['site_description'] ?? ''); ?></textarea>
                </div>
                <button type="submit" name="update_settings" class="btn">Simpan Pengaturan</button>
            </form>
        </div>

        <div class="section">
            <h2>Manajemen API Key ImgBB</h2>
            <p style="color: #aebac1; font-size: 14px;">Tambahkan API Key cadangan. Sistem akan otomatis memilih key yang berstatus 'active' dengan jumlah pemakaian paling sedikit.</p>
            
            <form action="" method="POST" style="display: flex; gap: 10px; margin-bottom: 20px;">
                <input type="text" name="new_api_key" placeholder="Masukkan API Key ImgBB baru..." required style="flex: 1; padding: 10px;">
                <button type="submit" name="add_api_key" class="btn">+ Tambah Key</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>API Key</th>
                        <th>Status</th>
                        <th>Dipakai</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($api_keys) > 0): ?>
                        <?php foreach ($api_keys as $key): ?>
                            <tr>
                                <td style="font-family: monospace;"><?php echo htmlspecialchars(substr($key['api_key'], 0, 10)) . '...'; ?></td>
                                <td>
                                    <?php if ($key['status'] === 'active'): ?>
                                        <span class="badge-active">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge-failed">Gagal / Limit</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo $key['usage_count']; ?></strong> kali</td>
                                <td>
                                    <?php if ($key['status'] === 'active'): ?>
                                        <a href="admin.php?toggle_status=failed&id=<?php echo $key['id']; ?>" class="action-link link-gray" title="Tandai Limit">Tandai Gagal</a>
                                    <?php else: ?>
                                        <a href="admin.php?toggle_status=active&id=<?php echo $key['id']; ?>" class="action-link link-green" title="Aktifkan Ulang">Aktifkan</a>
                                    <?php endif; ?>
                                    <a href="admin.php?delete_key=true&id=<?php echo $key['id']; ?>" class="action-link link-red" onclick="return confirm('Yakin ingin menghapus API Key ini?');">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; color:#ff4d4d;">Belum ada API Key. Harap tambahkan minimal 1.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <div class="admin-box">
    <div class="nav-header">
        <h1><?php echo htmlspecialchars(WEB_NAME); ?> Admin</h1>
        <a href="../main_panel.php" style="color: #25D366; text-decoration: none;">← Kembali ke Chat</a>
    </div>

    <h3>Manajemen Pengguna</h3>
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Role</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $users = $conn->query("SELECT * FROM users WHERE id != ".$_SESSION['user_id'])->fetchAll();
            foreach($users as $u) {
                $status = $u['is_blocked'] ? "<span style='color:red;'>Diblokir</span>" : "Aktif";
                echo "<tr>
                    <td>{$u['username']}</td>
                    <td>{$u['role']}</td>
                    <td>{$status}</td>
                    <td><a href='index.php?block_user={$u['id']}' class='btn-red' onclick='return confirm(\"Blokir user ini?\")'>Blokir</a></td>
                </tr>";
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>