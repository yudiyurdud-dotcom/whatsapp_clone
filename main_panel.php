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

// TAMBAHAN: Jika sedang online lalu diblokir, paksa Logout!
if ($me['is_blocked'] == 1) {
    header("Location: logout.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(WEB_NAME); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="main-wrapper">
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo $me['avatar_url'] ?: 'https://i.ibb.co/30B37f8/default-avatar.png'; ?>" onclick="location.href='profile.php'" title="Buka Profil" style="object-fit: cover;">
            <div class="icons">
                <span class="btn-icon" onclick="location.href='admin/'" title="Panel Admin">⚙️</span>
            </div>
        </div>
        <div class="user-list" id="contact-list">
            </div>
    </div>

    <div class="chat-container">
        <div id="chat-welcome" style="margin: auto; text-align: center; color: #8696a0;">
            <h2 style="color: #e9edef;"><?php echo htmlspecialchars(WEB_NAME); ?></h2>
            <p>Pilih teman untuk mulai berkirim pesan.</p>
        </div>

        <div id="active-chat-window" style="display: none; height: 100%; flex-direction: column;">
            <div class="chat-header">
                <span id="back-btn-mobile" onclick="closeChatMobile()" style="display: none; font-size: 22px; margin-right: 15px; cursor: pointer;" title="Kembali ke Kontak">⬅️</span>
                <span class="btn-icon" onclick="reportUser()" title="Laporkan Pengguna" style="font-size: 18px; margin-left: 10px; cursor: pointer;">⚠️</span>

                <img id="active-avatar" src="" alt="Avatar" style="object-fit: cover;">
                <div style="display: flex; flex-direction: column;">
                    <h4 id="active-name" style="margin: 0; color: #e9edef;"></h4>
                    <small id="active-status" style="font-size: 12px; margin-top: 2px;"></small>
                </div>
            </div>

            <div class="messages-area" id="messages-display">
                </div>

            <div id="reply-preview" style="display: none; background-color: #202c33; padding: 10px 15px; border-left: 5px solid #25D366; color: #aebac1; font-size: 13px; position: relative; border-bottom: 1px solid #2a3942;">
                <strong style="color: #25D366; display: block; margin-bottom: 3px;">Membalas:</strong>
                <span id="reply-preview-text" style="color: #e9edef;"></span>
                <span onclick="cancelReply()" style="position: absolute; right: 15px; top: 15px; cursor: pointer; font-size: 18px; font-weight: bold;" title="Batal Membalas">&times;</span>
            </div>

            <div class="input-area" style="position: relative;">
                <span class="btn-icon" id="emoji-btn" title="Pilih Emoji" onclick="toggleEmojiPicker()" style="font-size: 24px; margin-right: 5px;">😀</span>
                <span class="btn-icon" title="Kirim Dokumen (Link)" onclick="promptDocumentLink()" style="font-size: 24px; margin-right: 5px;">📎</span>
                <label for="chat-image" class="btn-icon" title="Kirim Gambar">📷</label>
                <input type="file" id="chat-image" style="display: none;" accept="image/*" onchange="sendImage()">
                <input type="text" id="msg-input" placeholder="Ketik pesan..." onkeypress="checkEnter(event)">
                <button onclick="sendMessage()" style="background: none; border: none; font-size: 24px; cursor: pointer;" title="Kirim Pesan">📩</button>
            </div>

            <div id="emoji-picker" class="emoji-picker-container" style="display: none;"></div>
        </div>
    </div>
</div>

<div id="image-modal" class="modal">
  <span class="close-modal" onclick="closeModal()" title="Tutup">&times;</span>
  <img class="modal-content" id="modal-img">
</div>

<script src="assets/js/main.js"></script>
<script>
    // Mendefinisikan ID kamu ke JavaScript
    const myUserId = <?php echo $_SESSION['user_id']; ?>;

    let currentReceiverId = null;

    window.onload = () => {
        if (typeof initEmojiPicker === "function") initEmojiPicker();
        loadContacts();
        
        // Polling setiap 5 Detik
        setInterval(() => {
            fetch("ajax/update_activity.php"); // 1. Lapor ke server bahwa kita sedang online
            
            if(currentReceiverId) {
                fetchMessages(currentReceiverId); // 2. Cek pesan baru
                fetchUserStatus(currentReceiverId); // 3. Cek apakah teman kita online
            }
        }, 5000); 
    };

    function checkEnter(e) {
        if (e.key === 'Enter') sendMessage();
    }
</script>
</body>
</html>