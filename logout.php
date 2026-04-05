<?php
// Kode ini diletakkan di C:\Users\User\Downloads\whatsapp_clone\logout.php

session_start(); // Mulai sesi
session_unset(); // Bersihkan semua variabel sesi
session_destroy(); // Hancurkan sesi sepenuhnya

// Arahkan kembali ke halaman login
header("Location: index.php");
exit();
?>