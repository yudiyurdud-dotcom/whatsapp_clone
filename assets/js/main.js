// Kode ini diletakkan di C:\Users\User\Downloads\whatsapp_clone\assets\js\main.js

// Fungsi utama dengan sistem Auto-Retry dan Multi-API Key
async function uploadToImgBB(fileInput, retryCount = 0) {
  // Batasi percobaan ulang agar tidak terjadi looping tanpa henti (maksimal 3 kali coba key berbeda)
  if (retryCount > 3) {
    alert(
      "Gagal mengunggah gambar setelah beberapa kali percobaan. Semua API Key mungkin telah limit.",
    );
    return null;
  }

  try {
    // 1. Minta API Key terbaik dari server kita
    const keyResponse = await fetch("ajax/get_api_key.php");
    const keyData = await keyResponse.json();

    if (!keyData.success) {
      alert("Pesan dari sistem: " + keyData.message);
      return null;
    }

    const IMGBB_API_KEY = keyData.api_key;
    const KEY_ID = keyData.id;

    // Siapkan data gambar
    const formData = new FormData();
    formData.append("image", fileInput);

    console.log(`Mencoba upload dengan API Key ID: ${KEY_ID}`);

    // 2. Kirim gambar ke ImgBB
    const imgbbResponse = await fetch(
      `https://api.imgbb.com/1/upload?key=${IMGBB_API_KEY}`,
      {
        method: "POST",
        body: formData,
      },
    );

    const data = await imgbbResponse.json();

    if (data.success) {
      // 3a. JIKA BERHASIL: Laporkan ke server agar usage_count bertambah
      const reportData = new FormData();
      reportData.append("id", KEY_ID);
      reportData.append("status", "success");
      fetch("ajax/report_api_key.php", { method: "POST", body: reportData }); // Berjalan di latar belakang

      return data.data.url; // Kembalikan URL gambar ke HTML
    } else {
      // 3b. JIKA GAGAL: Laporkan ke server agar status API Key diubah menjadi 'failed'
      console.warn("API Key Limit / Gagal:", data.error.message);

      const reportData = new FormData();
      reportData.append("id", KEY_ID);
      reportData.append("status", "failed");

      // Tunggu status diupdate di database sebelum mencoba ulang
      await fetch("ajax/report_api_key.php", {
        method: "POST",
        body: reportData,
      });

      console.log("Mencari API Key cadangan lainnya...");

      // 4. AUTO RETRY: Panggil ulang fungsi ini sendiri (Rekursif)
      return await uploadToImgBB(fileInput, retryCount + 1);
    }
  } catch (error) {
    console.error("Terjadi gangguan jaringan:", error);
    return null;
  }
}

// =========================================================
// Fungsi updateAvatar di bawah ini tetap SAMA seperti sebelumnya
// =========================================================
async function updateAvatar(fileInputId, userId) {
  const fileInput = document.getElementById(fileInputId).files[0];

  if (!fileInput) {
    alert("Pilih gambar terlebih dahulu!");
    return;
  }

  // Memanggil fungsi upload ke ImgBB
  const imageUrl = await uploadToImgBB(fileInput);

  if (imageUrl) {
    try {
      const formDataDB = new FormData();
      formDataDB.append("user_id", userId);
      formDataDB.append("avatar_url", imageUrl);

      const dbResponse = await fetch("ajax/update_avatar.php", {
        method: "POST",
        body: formDataDB,
      });

      const dbResult = await dbResponse.json();

      if (dbResult.success) {
        // Berhasil, tidak perlu alert jika ingin langsung refresh,
        // tapi kita biarkan log saja
        console.log("Avatar sukses tersimpan di database.");
      } else {
        alert("Gambar terunggah, tapi gagal menyimpan ke database lokal.");
      }
    } catch (error) {
      console.error("Gagal menghubungi server lokal:", error);
      alert("Error Server Lokal. Coba lagi.");
    }
  } else {
    // PERBAIKAN: Munculkan pesan jika API ImgBB gagal total!
    alert(
      "Gagal mengunggah foto ke ImgBB. Pastikan API Key di Panel Admin aktif dan Internet stabil.",
    );
  }
}

// Kode ini diletakkan di C:\Users\User\Downloads\whatsapp_clone\assets\js\main.js

async function loadContacts() {
  const res = await fetch("ajax/fetch_contacts.php");
  const users = await res.json();
  let html = "";

  users.forEach((user) => {
    // Tampilkan lencana hijau jika ada pesan yang belum dibaca
    let badge =
      user.unread_count > 0
        ? `<div style="background-color: #25D366; color: white; border-radius: 50%; padding: 2px 8px; font-size: 12px; font-weight: bold; margin-left: auto;">${user.unread_count}</div>`
        : "";

    html += `
            <div class="contact-item" onclick="openChat(${user.id}, '${user.display_name}', '${user.avatar_url || "https://i.ibb.co/30B37f8/default-avatar.png"}')">
                <img src="${user.avatar_url || "https://i.ibb.co/30B37f8/default-avatar.png"}" style="object-fit: cover;">
                <span><strong>${user.display_name}</strong></span>
                ${badge}
            </div>`;
  });
  document.getElementById("contact-list").innerHTML = html;
}

function openChat(id, name, avatar) {
  currentReceiverId = id;
  document.getElementById("chat-welcome").style.display = "none";
  document.getElementById("active-chat-window").style.display = "flex";
  document.getElementById("active-name").innerText = name;
  document.getElementById("active-avatar").src = avatar;

  // Tampilkan teks loading sementara
  document.getElementById("active-status").innerText = "Memuat status...";
  document.getElementById("active-status").style.color = "#aebac1";

  fetchMessages(id);
  fetchUserStatus(id);
}

async function fetchMessages(receiverId) {
  const fd = new FormData();
  fd.append("sender_id", receiverId);
  fetch("ajax/mark_read.php", { method: "POST", body: fd });

  const res = await fetch(`ajax/fetch_messages.php?receiver_id=${receiverId}`);
  const data = await res.json();
  let html = "";

  data.forEach((m) => {
    let isMyMessage = m.sender_id == myUserId;
    let side = isMyMessage ? "msg-sent" : "msg-received";

    let statusIcon = "";
    if (isMyMessage) {
      statusIcon = m.is_read
        ? `<span style="color:#53bdeb; margin-left:5px; font-size:12px;">✓✓</span>`
        : `<span style="color:#aebac1; margin-left:5px; font-size:12px;">✓</span>`;
    }

    let deleteBtn = isMyMessage
      ? `<span onclick="deleteMessage(${m.id})" style="cursor:pointer; margin-left:8px; font-size:12px;" title="Hapus Pesan">🗑️</span>`
      : "";

    // Teks aman untuk dikirim ke fungsi prepareReply (jika ada petik tunggal)
    let safeText = m.message_text
      ? m.message_text.replace(/'/g, "\\'")
      : "📷 Gambar";
    let replyBtn = `<span onclick="prepareReply(${m.id}, '${safeText}')" style="cursor:pointer; margin-left:8px; font-size:12px;" title="Balas Pesan">↩️</span>`;

    // Render kotak pesan yang sedang dibalas (jika ada)
    let quotedContent = "";
    if (m.reply_to_id) {
      let qText = m.replied_text ? m.replied_text : "📷 Gambar";
      quotedContent = `<div class="quoted-msg">${qText}</div>`;
    }

    html += `<div class="msg ${side}">
                ${quotedContent}
                ${m.message_text ? `<div>${m.message_text}</div>` : ""}
                ${m.image_url ? `<img src="${m.image_url}" class="msg-img" onclick="openModal('${m.image_url}')">` : ""}
                <div style="display:flex; justify-content:flex-end; align-items:center; margin-top:4px;">
                    <small style="font-size:10px; color:#aebac1;">${m.created_at.substr(11, 5)}</small>
                    ${replyBtn}
                    ${statusIcon}
                    ${deleteBtn}
                </div>
             </div>`;
  });

  const display = document.getElementById("messages-display");
  const isScrolledToBottom =
    display.scrollHeight - display.clientHeight <= display.scrollTop + 50;
  display.innerHTML = html;
  if (isScrolledToBottom) display.scrollTop = display.scrollHeight;
}

// Tambahkan Fungsi Baru Ini di Bagian Paling Bawah main.js
async function deleteMessage(messageId) {
  if (confirm("Apakah kamu yakin ingin menghapus pesan ini?")) {
    const fd = new FormData();
    fd.append("message_id", messageId);

    const res = await fetch("ajax/delete_message.php", {
      method: "POST",
      body: fd,
    });

    const data = await res.json();

    if (data.success) {
      // Jika berhasil dihapus, segarkan langsung layar obrolan
      fetchMessages(currentReceiverId);
    } else {
      alert("Gagal menghapus pesan: " + data.error);
    }
  }
}

async function sendMessage() {
  const input = document.getElementById("msg-input");
  const msg = input.value.trim();
  if (!msg || !currentReceiverId) return;

  const fd = new FormData();
  fd.append("receiver_id", currentReceiverId);
  fd.append("message", msg);
  if (currentReplyId) fd.append("reply_to_id", currentReplyId); // Kirim ID balasan

  input.value = "";
  cancelReply(); // Sembunyikan kotak balasan setelah terkirim

  await fetch("ajax/send_message.php", { method: "POST", body: fd });
  fetchMessages(currentReceiverId);
}

async function sendImage() {
  const fileInput = document.getElementById("chat-image");
  if (!fileInput.files[0]) return;

  const imageUrl = await uploadToImgBB(fileInput.files[0]);
  if (imageUrl) {
    const fd = new FormData();
    fd.append("receiver_id", currentReceiverId);
    fd.append("image_url", imageUrl);
    if (currentReplyId) fd.append("reply_to_id", currentReplyId); // Kirim ID balasan

    cancelReply(); // Sembunyikan kotak balasan setelah terkirim

    await fetch("ajax/send_message.php", { method: "POST", body: fd });
    fetchMessages(currentReceiverId);
  }
}

// =========================================================
// Fungsi Lightbox / Preview Gambar
// =========================================================
function openModal(imgSrc) {
  const modal = document.getElementById("image-modal");
  const modalImg = document.getElementById("modal-img");
  modal.style.display = "block";
  modalImg.src = imgSrc;
}

function closeModal() {
  const modal = document.getElementById("image-modal");
  modal.style.display = "none";
}

// Menutup modal jika pengguna mengklik area hitam di luar gambar
window.onclick = function (event) {
  const modal = document.getElementById("image-modal");
  if (event.target == modal) {
    modal.style.display = "none";
  }
};

// =========================================================
// Fitur Papan Ketik Emoji
// =========================================================
const emojiList = [
  "😀",
  "😃",
  "😄",
  "😁",
  "😆",
  "😅",
  "😂",
  "🤣",
  "🥲",
  "☺️",
  "😊",
  "😇",
  "🙂",
  "🙃",
  "😉",
  "😌",
  "😍",
  "🥰",
  "😘",
  "😗",
  "😙",
  "😚",
  "😋",
  "😛",
  "😝",
  "😜",
  "🤪",
  "🤨",
  "🧐",
  "🤓",
  "😎",
  "🥸",
  "🤩",
  "🥳",
  "😏",
  "😒",
  "😞",
  "😔",
  "😟",
  "😕",
  "🙁",
  "☹️",
  "😣",
  "😖",
  "😫",
  "😩",
  "🥺",
  "😢",
  "😭",
  "😤",
  "😠",
  "😡",
  "🤬",
  "🤯",
  "😳",
  "🥵",
  "🥶",
  "😱",
  "😨",
  "😰",
  "😥",
  "😓",
  "🤗",
  "🤔",
  "🤭",
  "🤫",
  "🤥",
  "😶",
  "😐",
  "😑",
  "😬",
  "🙄",
  "😯",
  "😦",
  "😧",
  "😮",
  "😲",
  "🥱",
  "😴",
  "🤤",
  "😪",
  "😵",
  "🤐",
  "🥴",
  "🤢",
  "🤮",
  "🤧",
  "😷",
  "🤒",
  "🤕",
  "🤑",
  "🤠",
  "😈",
  "👿",
  "👹",
  "👺",
  "🤡",
  "💩",
  "👻",
  "💀",
  "👽",
  "👾",
  "🤖",
  "🎃",
  "😺",
  "😸",
  "😹",
  "😻",
  "😼",
  "😽",
  "🙀",
  "😿",
  "😾",
  "❤️",
  "🧡",
  "💛",
  "💚",
  "💙",
  "💜",
  "🖤",
  "🤍",
  "🤎",
  "💔",
  "❣️",
  "💕",
  "💞",
  "💓",
  "💗",
  "💖",
  "💘",
  "💝",
  "👍",
  "👎",
  "👏",
  "🙌",
  "👐",
  "🤲",
  "🤝",
  "🙏",
  "✍️",
  "💪",
  "🦾",
  "🦵",
  "🦿",
];

// 1. Fungsi mencetak daftar emoji ke dalam HTML
function initEmojiPicker() {
  const container = document.getElementById("emoji-picker");
  if (!container) return;

  let html = "";
  emojiList.forEach((emoji) => {
    html += `<div class="emoji-item" onclick="insertEmoji('${emoji}')">${emoji}</div>`;
  });
  container.innerHTML = html;
}

// 2. Fungsi buka/tutup papan emoji
function toggleEmojiPicker() {
  const picker = document.getElementById("emoji-picker");
  // Jika sedang sembunyi, tampilkan dengan mode Grid
  if (picker.style.display === "none" || picker.style.display === "") {
    picker.style.display = "grid";
  } else {
    picker.style.display = "none";
  }
}

// 3. Fungsi menyisipkan emoji ke kolom input pesan
function insertEmoji(emoji) {
  const input = document.getElementById("msg-input");
  input.value += emoji; // Tambahkan emoji ke teks yang sudah ada
  input.focus(); // Kembalikan kursor ke kolom input
}

// 4. Tutup papan emoji jika pengguna mengklik area lain di luar papan
window.addEventListener("click", function (e) {
  const picker = document.getElementById("emoji-picker");
  const btn = document.getElementById("emoji-btn");
  if (
    picker &&
    e.target !== picker &&
    e.target !== btn &&
    !picker.contains(e.target)
  ) {
    picker.style.display = "none";
  }
});

// =========================================================
// Fungsi Cek Status Online
// =========================================================
async function fetchUserStatus(userId) {
  try {
    const res = await fetch(`ajax/check_status.php?user_id=${userId}`);
    const text = await res.text();
    const statusEl = document.getElementById("active-status");

    if (statusEl) {
      statusEl.innerText = text;
      // Warnai hijau khas WA jika statusnya Online ATAU Sedang mengetik
      if (text === "Online" || text === "Sedang mengetik...") {
        statusEl.style.color = "#25D366";
      } else {
        statusEl.style.color = "#aebac1"; // Abu-abu jika Offline
      }
    }
  } catch (error) {
    console.error("Gagal mengambil status:", error);
  }
}

// =========================================================
// Sensor "Sedang Mengetik..." pada Keyboard
// =========================================================
let typingTimer;
const msgInput = document.getElementById("msg-input");

if (msgInput) {
  // Event 'input' akan mendeteksi setiap huruf yang diketik, dihapus, atau di-paste
  msgInput.addEventListener("input", function () {
    if (!currentReceiverId) return; // Jangan lakukan apa-apa jika belum memilih teman chat

    // 1. Kirim sinyal ke server bahwa kita mulai mengetik
    const fd = new FormData();
    fd.append("receiver_id", currentReceiverId);
    fetch("ajax/update_typing.php", { method: "POST", body: fd });

    // 2. Hapus timer lama (jika kita mengetik dengan cepat tanpa jeda)
    clearTimeout(typingTimer);

    // 3. Set timer baru: Jika selama 2 detik kita diam tidak mengetik, kirim sinyal berhenti (0)
    typingTimer = setTimeout(() => {
      const fdStop = new FormData();
      fdStop.append("receiver_id", 0);
      fetch("ajax/update_typing.php", { method: "POST", body: fdStop });
    }, 2000);
  });
}

// =========================================================
// Fitur Balas Pesan (Reply)
// =========================================================
let currentReplyId = null;

function prepareReply(messageId, textSnippet) {
  currentReplyId = messageId;
  const previewBox = document.getElementById("reply-preview");
  const previewText = document.getElementById("reply-preview-text");

  // Potong teks jika terlalu panjang
  let displaySnippet =
    textSnippet.length > 50
      ? textSnippet.substring(0, 50) + "..."
      : textSnippet;

  previewText.innerText = displaySnippet;
  previewBox.style.display = "block";
  document.getElementById("msg-input").focus(); // Langsung arahkan kursor ke tempat ketik
}

function cancelReply() {
  currentReplyId = null;
  document.getElementById("reply-preview").style.display = "none";
}
