<?php
// Kode ini diletakkan di PATH_FOLDER/ajax/update_activity.php
require_once '../config.php';

if (isset($_SESSION['user_id'])) {
    // Perbarui waktu terakhir aktif ke waktu sekarang
    $stmt = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
}
?>