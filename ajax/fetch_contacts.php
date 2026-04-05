<?php
// Kode ini diletakkan di PATH_FOLDER/ajax/fetch_contacts.php

require_once '../config.php';

$me = $_SESSION['user_id'];

try {
    // Mengambil data user beserta jumlah pesan yang belum dibaca
    $stmt = $conn->prepare("
        SELECT u.*, 
               (SELECT COUNT(id) FROM messages WHERE sender_id = u.id AND receiver_id = :me AND is_read = FALSE) as unread_count
        FROM users u 
        WHERE u.id != :me
    ");
    $stmt->execute(['me' => $me]);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($contacts);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>