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
    // Set mode error PDO ke exception agar mudah dilacak jika ada salah kode
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}
?>