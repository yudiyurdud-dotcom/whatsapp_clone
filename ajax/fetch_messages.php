<?php
// Kode ini diletakkan di C:\Users\User\Downloads\whatsapp_clone\ajax\fetch_messages.php
require_once '../config.php';

$me = $_SESSION['user_id'];
$them = $_GET['receiver_id'];

$stmt = $conn->prepare("SELECT * FROM messages 
    WHERE (sender_id = :me AND receiver_id = :them) 
    OR (sender_id = :them AND receiver_id = :me) 
    ORDER BY created_at ASC");
$stmt->execute(['me' => $me, 'them' => $them]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($messages);
?>