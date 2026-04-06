<?php
// Kode ini diletakkan di whatsapp_clone/ajax/report_user.php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $reporter_id = $_SESSION['user_id'];
    $reported_id = $_POST['reported_id'];
    $reason = $_POST['reason'];

    try {
        $stmt = $conn->prepare("INSERT INTO reports (reporter_id, reported_id, reason) VALUES (:r1, :r2, :re)");
        $stmt->execute(['r1' => $reporter_id, 'r2' => $reported_id, 're' => $reason]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false]);
    }
}
?>