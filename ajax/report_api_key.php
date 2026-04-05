<?php
// Kode ini diletakkan di C:\Users\User\Downloads\whatsapp_clone\ajax\report_api_key.php

require_once '../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key_id = $_POST['id'] ?? '';
    $status = $_POST['status'] ?? ''; // Bernilai 'success' atau 'failed'

    if (!empty($key_id) && !empty($status)) {
        try {
            if ($status === 'failed') {
                // Tandai API Key sebagai gagal/limit
                $stmt = $conn->prepare("UPDATE imgbb_api_keys SET status = 'failed' WHERE id = :id");
                $stmt->execute(['id' => $key_id]);
            } elseif ($status === 'success') {
                // Tambah angka penggunaan
                $stmt = $conn->prepare("UPDATE imgbb_api_keys SET usage_count = usage_count + 1 WHERE id = :id");
                $stmt->execute(['id' => $key_id]);
            }
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
?>