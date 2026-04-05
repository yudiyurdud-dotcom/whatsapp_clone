<?php
// Kode ini diletakkan di C:\Users\User\Downloads\whatsapp_clone\ajax\send_message.php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = $_POST['receiver_id'];
    $text = $_POST['message'] ?? null;
    $image_url = $_POST['image_url'] ?? null;

    try {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message_text, image_url) VALUES (:s, :r, :t, :i)");
        $stmt->execute(['s' => $sender_id, 'r' => $receiver_id, 't' => $text, 'i' => $image_url]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>