<?php
// Kode ini diletakkan di PATH_FOLDER/ajax/send_message.php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = $_POST['receiver_id'];
    $text = !empty($_POST['message']) ? $_POST['message'] : null;
    $image_url = !empty($_POST['image_url']) ? $_POST['image_url'] : null;
    
    // Tangkap ID pesan yang dibalas (jika ada)
    $reply_to = !empty($_POST['reply_to_id']) ? $_POST['reply_to_id'] : null;

    try {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message_text, image_url, reply_to_id) VALUES (:s, :r, :t, :i, :reply)");
        $stmt->execute(['s' => $sender_id, 'r' => $receiver_id, 't' => $text, 'i' => $image_url, 'reply' => $reply_to]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>