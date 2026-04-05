<?php
// Kode ini diletakkan di C:\Users\User\Downloads\whatsapp_clone\chat.php
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
    <style>
        /* Layout Utama */
        .main-wrapper { display: flex; height: 100vh; background-color: #f0f2f5; overflow: hidden; }
        
        /* Sidebar */
        .sidebar { width: 30%; background: white; border-right: 1px solid #ddd; display: flex; flexDirection: column; }
        .sidebar-header { padding: 15px; background: #ededed; display: flex; justify-content: space-between; align-items: center; }
        .sidebar-header img { width: 40px; height: 40px; border-radius: 50%; cursor: pointer; }
        .user-list { flex: 1; overflow-y: auto; }
        .contact-item { padding: 15px; border-bottom: 1px solid #f2f2f2; display: flex; align-items: center; cursor: pointer; }
        .contact-item:hover { background-color: #f5f5f5; }
        .contact-item img { width: 45px; height: 45px; border-radius: 50%; margin-right: 15px; }

        /* Container Chat */
        .chat-container { width: 70%; display: flex; flex-direction: column; background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); }
        .chat-header { padding: 10px 20px; background: #ededed; display: flex; align-items: center; border-left: 1px solid #ddd; }
        .chat-header img { width: 40px; height: 40px; border-radius: 50%; margin-right: 15px; }
        
        .messages-area { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; }
        .msg { max-width: 60%; padding: 8px 12px; border-radius: 8px; margin-bottom: 5px; position: relative; font-size: 14px; }
        .msg-sent { align-self: flex-end; background-color: #dcf8c6; }
        .msg-received { align-self: flex-start; background-color: white; }
        .msg-img { max-width: 100%; border-radius: 5px; margin-top: 5px; }
        
        /* Input Area */
        .input-area { padding: 10px 20px; background: #f0f0f0; display: flex; align-items: center; gap: 10px; }
        .input-area input[type="text"] { flex: 1; padding: 10px; border: none; border-radius: 20px; outline: none; }
        .btn-icon { cursor: pointer; font-size: 20px; color: #54656f; }
    </style>
</head>
<body>

<div class="main-wrapper">
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo $me['avatar_url'] ?: 'https://i.ibb.co/30B37f8/default-avatar.png'; ?>" onclick="location.href='profile.php'">
            <div class="icons">
                <span class="btn-icon" onclick="location.href='admin.php'">⚙️</span>
            </div>
        </div>
        <div class="user-list" id="contact-list">
            </div>
    </div>

    <div class="chat-container">
        <div id="chat-welcome" style="margin: auto; text-align: center; color: #666;">
            <h2>WhatsApp Clone</h2>
            <p>Pilih teman untuk mulai berkirim pesan.</p>
        </div>

        <div id="active-chat-window" style="display: none; height: 100%; display: flex; flex-direction: column;">
            <div class="chat-header">
                <img id="active-avatar" src="">
                <h4 id="active-name" style="margin: 0;"></h4>
            </div>

            <div class="messages-area" id="messages-display">
                </div>

            <div class="input-area">
                <label for="chat-image" class="btn-icon">📷</label>
                <input type="file" id="chat-image" style="display: none;" accept="image/*" onchange="sendImage()">
                
                <input type="text" id="msg-input" placeholder="Ketik pesan..." onkeypress="checkEnter(event)">
                
                <button onclick="sendMessage()" style="background: none; border: none; font-size: 24px; cursor: pointer;">📩</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/main.js"></script>
<script>
    // 1. TAMBAHKAN BARIS INI: Mendefinisikan ID kamu ke JavaScript
    const myUserId = <?php echo $_SESSION['user_id']; ?>;

    // Variabel global untuk melacak siapa yang sedang diajak chat
    let currentReceiverId = null;

    // Load daftar kontak saat pertama kali buka
    window.onload = () => {
        loadContacts();
        
        // 2. UBAH ANGKA 3000 MENJADI 5000 (5 Detik)
        // Ini sangat penting agar InfinityFree tidak mengira website-mu sedang melakukan serangan DDoS
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