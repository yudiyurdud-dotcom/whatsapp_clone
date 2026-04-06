<?php
// Kode ini diletakkan di C:\Users\User\Downloads\whatsapp_clone\config.php

// Mulai session di sini agar semua halaman yang memanggil config.php otomatis memiliki session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// PENGATURAN DATABASE
// ==========================================
// Jika masih di Laragon (Lokal):
$host = 'sql309.infinityfree.com';
$db_user = 'if0_41585256';
$db_pass = 'Z3eeJiFzl154M';
$db_name = 'if0_41585256_db_whatsapp_clone';

// Jika sudah di InfinityFree, ubah nilai di atas menjadi seperti contoh di bawah ini:
// $host = 'sql123.infinityfree.com'; (Lihat di MySQL Databases cPanel)
// $db_user = 'if0_12345678'; (Username akun hostingmu)
// $db_pass = 'password_hosting_kamu'; (Password akun hosting atau vPanel)
// $db_name = 'if0_12345678_db_whatsapp_clone';

try {
    $conn = new PDO("mysql:host={$host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ambil pengaturan web secara global
    $stmt_set = $conn->query("SELECT * FROM site_settings WHERE id = 1");
    $web_settings = $stmt_set->fetch(PDO::FETCH_ASSOC);
    
    // Definisikan Konstanta agar bisa dipakai di semua file
    define('WEB_NAME', $web_settings['site_name'] ?? 'WhatsApp Clone');
    define('WEB_DESC', $web_settings['site_description'] ?? '');

} catch(PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}

try {
    $stmt_set = $conn->query("SELECT * FROM site_settings WHERE id = 1");
    $web_settings = $stmt_set->fetch(PDO::FETCH_ASSOC);
    // Jadikan nama web sebagai Konstanta Global agar bisa dipanggil di mana saja
    define('WEB_NAME', $web_settings['site_name'] ?? 'WhatsApp Clone'); 
} catch (Exception $e) {
    define('WEB_NAME', 'WhatsApp Clone'); // Jika gagal, gunakan nama default
}
?>