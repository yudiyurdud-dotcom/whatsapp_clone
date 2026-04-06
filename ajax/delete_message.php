<?php
// Kode ini diletakkan di PATH_FOLDER/ajax/delete_message.php
require_once '../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message_id = $_POST['message_id'] ?? null;
    $user_id = $_SESSION['user_id'];

    if ($message_id) {
        try {
            // PERUBAHAN: Ubah status is_deleted menjadi 1, bukannya dihapus permanen
            $stmt = $conn->prepare("UPDATE messages SET is_deleted = 1 WHERE id = :id AND sender_id = :sender_id");
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