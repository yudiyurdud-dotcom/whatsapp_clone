<?php
// Kode ini diletakkan di C:\Users\User\Downloads\whatsapp_clone\ajax\get_api_key.php

require_once '../config.php';
header('Content-Type: application/json');

try {
    // Cari 1 API Key yang aktif, diurutkan berdasarkan penggunaan paling sedikit
    $stmt = $conn->query("SELECT id, api_key FROM imgbb_api_keys WHERE status = 'active' ORDER BY usage_count ASC LIMIT 1");
    $keyData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($keyData) {
        echo json_encode([
            'success' => true, 
            'id' => $keyData['id'], 
            'api_key' => $keyData['api_key']
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Sistem kehabisan API Key yang aktif. Harap lapor Admin.'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>