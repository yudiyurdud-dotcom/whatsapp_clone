<?php
// Kode ini diletakkan di whatsapp-clone/ajax/update_avatar.php
require_once '../config.php';

// Pastikan request berupa POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? '';
    $avatar_url = $_POST['avatar_url'] ?? '';

    // Validasi sederhana
    if (!empty($user_id) && !empty($avatar_url)) {
        try {
            // Update kolom avatar_url di tabel users
            $stmt = $conn->prepare("UPDATE users SET avatar_url = :avatar_url WHERE id = :id");
            $stmt->bindParam(':avatar_url', $avatar_url);
            $stmt->bindParam(':id', $user_id);
            
            if ($stmt->execute()) {
                // Berikan respon JSON sukses ke JavaScript
                echo json_encode(['success' => true, 'message' => 'Avatar berhasil diperbarui']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal memperbarui database']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak diizinkan']);
}
?>