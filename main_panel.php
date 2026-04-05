<?php
// Kode ini diletakkan di PATH_FOLDER/main_panel.php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user yang sedang login
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - WhatsApp Clone</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="main-wrapper">
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo $me['avatar_url'] ?: 'https://i.ibb.co/30B37f8/default-avatar.png'; ?>" onclick="location.href='profile.php'" title="Buka Profil" style="object-fit: cover;">
            <div class="icons">
                <span class="btn-icon" onclick="location.href='admin.php'" title="Panel Admin">⚙️</span>
            </div>
        </div>
        <div class="user-list" id="contact-list">
            </div>
    </div>

    <div class="chat-container">
        <div id="chat-welcome" style="margin: auto; text-align: center; color: #8696a0;">
            <h2 style="color: #e9edef;">WhatsApp Clone</h2>
            <p>Pilih teman untuk mulai berkirim pesan.</p>
        </div>

        <div id="active-chat-window" style="display: none; height: 100%; flex-direction: column;">
            <div class="chat-header">
                <img id="active-avatar" src="" alt="Avatar" style="object-fit: cover;">
                <h4 id="active-name" style="margin: 0; color: #e9edef;"></h4>
            </div>

            <div class="messages-area" id="messages-display">
                </div>

            <div class="input-area">
                <label for="chat-image" class="btn-icon" title="Kirim Gambar">📷</label>
                <input type="file" id="chat-image" style="display: none;" accept="image/*" onchange="sendImage()">
                
                <input type="text" id="msg-input" placeholder="Ketik pesan..." onkeypress="checkEnter(event)">
                
                <button onclick="sendMessage()" style="background: none; border: none; font-size: 24px; cursor: pointer;" title="Kirim Pesan">📩</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/main.js"></script>
<script>
    // Mendefinisikan ID kamu ke JavaScript
    const myUserId = <?php echo $_SESSION['user_id']; ?>;

    let currentReceiverId = null;

    window.onload = () => {
        loadContacts();
        
        // Polling (menyegarkan pesan) setiap 5 Detik
        setInterval(() => {
            if(currentReceiverId) fetchMessages(currentReceiverId);
        }, 5000); 
    };

    function checkEnter(e) {
        if (e.key === 'Enter') sendMessage();
    }
</script>
</body>
</html>