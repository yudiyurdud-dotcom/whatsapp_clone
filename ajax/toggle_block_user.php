<?php
// Kode ini diletakkan di PATH_FOLDER/ajax/toggle_block_user.php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $me = $_SESSION['user_id'];
    $them = $_POST['target_id'] ?? 0;

    if ($them) {
        // Cek apakah statusnya saat ini sudah diblokir
        $stmt = $conn->prepare("SELECT id FROM user_blocks WHERE blocker_id = :me AND blocked_id = :them");
        $stmt->execute(['me' => $me, 'them' => $them]);

        if ($stmt->rowCount() > 0) {
            // Jika sudah diblokir, maka BUKA BLOKIR (Hapus dari database)
            $del = $conn->prepare("DELETE FROM user_blocks WHERE blocker_id = :me AND blocked_id = :them");
            $del->execute(['me' => $me, 'them' => $them]);
            echo json_encode(['status' => 'unblocked']);
        } else {
            // Jika belum, maka BLOKIR (Masukkan ke database)
            $ins = $conn->prepare("INSERT INTO user_blocks (blocker_id, blocked_id) VALUES (:me, :them)");
            $ins->execute(['me' => $me, 'them' => $them]);
            echo json_encode(['status' => 'blocked']);
        }
    } else {
        echo json_encode(['error' => 'ID tidak valid']);
    }
}
?>