<?php
// Kode ini diletakkan di PATH_FOLDER/ajax/fetch_messages.php
require_once '../config.php';

$me = $_SESSION['user_id'];
$them = $_GET['receiver_id'];

// LEFT JOIN untuk mengambil isi pesan yang dibalas
$stmt = $conn->prepare("
    SELECT m1.*, m2.message_text as replied_text, m2.image_url as replied_image 
    FROM messages m1 
    LEFT JOIN messages m2 ON m1.reply_to_id = m2.id
    WHERE (m1.sender_id = :me AND m1.receiver_id = :them) 
       OR (m1.sender_id = :them AND m1.receiver_id = :me) 
    ORDER BY m1.created_at ASC
");
$stmt->execute(['me' => $me, 'them' => $them]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($messages);
?>