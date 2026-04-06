<?php
// Kode ini diletakkan di PATH_FOLDER/ajax/send_message.php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = $_POST['receiver_id'];
    
    $text = !empty($_POST['message']) ? $_POST['message'] : null;
    $image_url = !empty($_POST['image_url']) ? $_POST['image_url'] : null;
    $file_link = !empty($_POST['file_link']) ? $_POST['file_link'] : null; // Tangkap tautan file
    $reply_to = !empty($_POST['reply_to_id']) ? $_POST['reply_to_id'] : null;

    try {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message_text, image_url, file_link, reply_to_id) VALUES (:s, :r, :t, :i, :f, :reply)");
        $stmt->execute(['s' => $sender_id, 'r' => $receiver_id, 't' => $text, 'i' => $image_url, 'f' => $file_link, 'reply' => $reply_to]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>