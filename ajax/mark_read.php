<?php
// Kode ini diletakkan di PATH_FOLDER/ajax/mark_read.php

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $me = $_SESSION['user_id'];
    $them = $_POST['sender_id'] ?? null;

    if ($them) {
        try {
            // Ubah status pesan dari false menjadi true (Centang Biru)
            $stmt = $conn->prepare("UPDATE messages SET is_read = TRUE WHERE sender_id = :them AND receiver_id = :me AND is_read = FALSE");
            $stmt->execute(['them' => $them, 'me' => $me]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false]);
        }
    }
}
?>