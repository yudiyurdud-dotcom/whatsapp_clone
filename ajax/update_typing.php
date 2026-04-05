<?php
// Kode ini diletakkan di whatsapp_clone/ajax/update_typing.php
require_once '../config.php';

// Pastikan request berupa POST dan user sudah login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $receiver_id = $_POST['receiver_id'] ?? 0; // Berisi ID teman, atau 0 jika berhenti mengetik
    
    // Perbarui kolom is_typing_to milik kita sendiri
    $stmt = $conn->prepare("UPDATE users SET is_typing_to = :receiver_id WHERE id = :my_id");
    $stmt->execute([
        'receiver_id' => $receiver_id,
        'my_id' => $_SESSION['user_id']
    ]);
}
?>