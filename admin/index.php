<?php
// Kode ini diletakkan di admin/index.php

require_once '../config.php'; 

// Proteksi: Hanya Admin yang boleh masuk
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("<h2 style='text-align:center; color:#ff4d4d; margin-top:50px;'>Akses Ditolak!</h2>");
}

$message = '';

// 1. Logika Update Pengaturan Web (Nama & Deskripsi)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $site_name = trim($_POST['site_name']);
    $site_description = trim($_POST['site_description']);
    $stmt = $conn->prepare("UPDATE site_settings SET site_name = :name, site_description = :desc WHERE id = 1");
    $stmt->execute(['name' => $site_name, 'desc' => $site_description]);
    header("Location: index.php?status=updated");
    exit();
}

// 2. Logika Toggle Blokir User
if (isset($_GET['toggle_block']) && isset($_GET['id'])) {
    $stmt = $conn->prepare("UPDATE users SET is_blocked = :status WHERE id = :id");
    $stmt->execute(['status' => $_GET['toggle_block'], 'id' => $_GET['id']]);
    header("Location: index.php");
    exit();
}

// 3. Logika Hapus API Key
if (isset($_GET['delete_key']) && isset($_GET['id'])) {
    $stmt = $conn->prepare("DELETE FROM imgbb_api_keys WHERE id = :id");
    $stmt->execute(['id' => $_GET['id']]);
    header("Location: index.php");
    exit();
}

// Ambil Data untuk Tampilan
$api_keys = $conn->query("SELECT * FROM imgbb_api_keys ORDER BY status ASC")->fetchAll(PDO::FETCH_ASSOC);
$users = $conn->query("SELECT * FROM users WHERE id != ".$_SESSION['user_id'])->fetchAll(PDO::FETCH_ASSOC);
$reports = $conn->query("
    SELECT r.*, u1.username as reporter_name, u2.username as reported_name 
    FROM reports r 
    JOIN users u1 ON r.reporter_id = u1.id 
    JOIN users u2 ON r.reported_id = u2.id 
    ORDER BY r.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - <?php echo htmlspecialchars(WEB_NAME); ?></title>
    <style>
        body { font-family: sans-serif; background-color: #111b21; color: #e9edef; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: auto; }
        .card { background: #202c33; padding: 20px; border-radius: 10px; margin-bottom: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #2a3942; padding-bottom: 15px; margin-bottom: 20px; }
        h1, h2 { margin: 0; color: #25D366; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; border-bottom: 1px solid #2a3942; text-align: left; font-size: 14px; }
        th { color: #aebac1; background: #111b21; }
        input, textarea { width: 100%; padding: 10px; margin-top: 5px; background: #111b21; border: 1px solid #2a3942; color: white; border-radius: 5px; }
        .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-green { background: #00a884; color: white; }
        .btn-red { background: #f15c5c; color: white; }
        .btn-gray { background: #3b4a54; color: white; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1><?php echo htmlspecialchars(WEB_NAME); ?> Admin</h1>
        <a href="../main_panel.php" class="btn btn-gray">← Kembali ke Chat</a>
    </div>

    <div class="card">
        <h2>⚙️ Pengaturan Website</h2>
        <form action="" method="POST" style="margin-top: 15px;">
            <label>Nama Website:</label>
            <input type="text" name="site_name" value="<?php echo htmlspecialchars(WEB_NAME); ?>" required>
            <br><br>
            <label>Deskripsi:</label>
            <textarea name="site_description" rows="2"><?php echo htmlspecialchars(WEB_DESC); ?></textarea>
            <br><br>
            <button type="submit" name="update_settings" class="btn btn-green">Simpan Perubahan</button>
        </form>
    </div>

    <div class="card">
        <h2>⚠️ Laporan Masuk</h2>
        <table>
            <thead>
                <tr>
                    <th>Pelapor</th>
                    <th>Dilaporkan</th>
                    <th>Alasan</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($reports): foreach($reports as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['reporter_name']); ?></td>
                    <td style="color: #f15c5c;"><?php echo htmlspecialchars($r['reported_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['reason']); ?></td>
                    <td><?php echo date('d/m H:i', strtotime($r['created_at'])); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="4" style="text-align:center;">Belum ada laporan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>👥 Daftar Pengguna</h2>
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $u): ?>
                <tr>
                    <td><?php echo htmlspecialchars($u['username']); ?> (<?php echo $u['role']; ?>)</td>
                    <td><?php echo $u['is_blocked'] ? "<b style='color:#f15c5c;'>Diblokir</b>" : "Aktif"; ?></td>
                    <td>
                        <?php if($u['is_blocked']): ?>
                            <a href="index.php?toggle_block=0&id=<?php echo $u['id']; ?>" class="btn btn-green">Buka Blokir</a>
                        <?php else: ?>
                            <a href="index.php?toggle_block=1&id=<?php echo $u['id']; ?>" class="btn btn-red">Blokir</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>🔑 API Keys ImgBB</h2>
        <table>
            <thead>
                <tr>
                    <th>Key</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($api_keys as $k): ?>
                <tr>
                    <td><code><?php echo substr($k['api_key'], 0, 10); ?>...</code></td>
                    <td><?php echo $k['status']; ?></td>
                    <td>
                        <a href="index.php?delete_key=true&id=<?php echo $k['id']; ?>" class="btn btn-red" onclick="return confirm('Hapus Key?')">Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>