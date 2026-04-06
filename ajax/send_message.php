<?php
// Kode ini diletakkan di PATH_FOLDER/ajax/send_message.php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = $_POST['receiver_id'];
    
    // CEK STATUS BLOKIR SEBELUM MENGIRIM PESAN
    $stmt_check = $conn->prepare("SELECT id FROM user_blocks WHERE (blocker_id = :s AND blocked_id = :r) OR (blocker_id = :r AND blocked_id = :s)");
    $stmt_check->execute(['s' => $sender_id, 'r' => $receiver_id]);
    
    if ($stmt_check->rowCount() > 0) {
        // Tolak pengiriman pesan jika terdeteksi blokir
        echo json_encode(['success' => false, 'error' => 'Tidak dapat mengirim pesan. Kontak ini diblokir atau memblokir Anda.']);
        exit();
    }

    // PROSES PENGIRIMAN PESAN JIKA AMAN
    $text = !empty($_POST['message']) ? $_POST['message'] : null;
    $image_url = !empty($_POST['image_url']) ? $_POST['image_url'] : null;
    $file_link = !empty($_POST['file_link']) ? $_POST['file_link'] : null;
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