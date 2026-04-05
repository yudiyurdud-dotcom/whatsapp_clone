<?php
// Kode ini diletakkan di whatsapp_clone/ajax/check_status.php
require_once '../config.php';

if (isset($_GET['user_id']) && isset($_SESSION['user_id'])) {
    $me = $_SESSION['user_id']; // ID kita sendiri
    
    // Ambil waktu terakhir aktif dan ID orang yang sedang dia ketik
    $stmt = $conn->prepare("SELECT last_activity, is_typing_to FROM users WHERE id = :id");
    $stmt->execute(['id' => $_GET['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // 1. Cek apakah dia sedang mengetik ke arah kita
        if ($user['is_typing_to'] == $me) {
            echo "Sedang mengetik...";
            exit(); // Hentikan script agar tidak lanjut mengecek Online/Offline
        }

        // 2. Jika tidak sedang mengetik, baru cek status Online / Offline
        $last_activity = strtotime($user['last_activity']);
        $now = time();
        
        if ($now - $last_activity <= 15) {
            echo "Online";
        } else {
            echo "Terakhir dilihat " . date("d/m/y H:i", $last_activity);
        }
    }
}
?>