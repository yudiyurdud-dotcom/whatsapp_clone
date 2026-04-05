<?php
// Kode ini diletakkan di C:\Users\User\Downloads\whatsapp_clone\ajax\delete_message.php

require_once '../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message_id = $_POST['message_id'] ?? null;
    $user_id = $_SESSION['user_id'];

    if ($message_id) {
        try {
            // Hapus pesan HANYA JIKA id_pengirim cocok dengan user yang sedang login
            $stmt = $conn->prepare("DELETE FROM messages WHERE id = :id AND sender_id = :sender_id");
            $stmt->execute(['id' => $message_id, 'sender_id' => $user_id]);
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'ID pesan tidak valid.']);
    }
}
?>