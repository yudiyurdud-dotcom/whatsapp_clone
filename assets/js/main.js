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
  fetchMessages(id);
}

async function fetchMessages(receiverId) {
  // 1. Beritahu server bahwa kita sedang membuka/membaca chat ini
  const fd = new FormData();
  fd.append("sender_id", receiverId);
  fetch("ajax/mark_read.php", { method: "POST", body: fd });

  // 2. Ambil pesan terbaru
  const res = await fetch(`ajax/fetch_messages.php?receiver_id=${receiverId}`);
  const data = await res.json();
  let html = "";

  data.forEach((m) => {
    let isMyMessage = m.sender_id == myUserId;
    let side = isMyMessage ? "msg-sent" : "msg-received";

    // Logika Ikon Status Pesan (Centang Abu-abu / Centang Biru)
    let statusIcon = "";
    if (isMyMessage) {
      statusIcon = m.is_read
        ? `<span style="color:#53bdeb; margin-left:5px; font-size:12px;">✓✓</span>` // Biru (Dibaca)
        : `<span style="color:#aebac1; margin-left:5px; font-size:12px;">✓</span>`; // Abu-abu (Terkirim)
    }

    let deleteBtn = isMyMessage
      ? `<span onclick="deleteMessage(${m.id})" style="cursor:pointer; margin-left:8px; font-size:12px;" title="Hapus Pesan">🗑️</span>`
      : "";

    html += `<div class="msg ${side}">
                ${m.message_text ? `<div>${m.message_text}</div>` : ""}
                ${m.image_url ? `<img src="${m.image_url}" class="msg-img">` : ""}
                <div style="display:flex; justify-content:flex-end; align-items:center; margin-top:4px;">
                    <small style="font-size:10px; color:#aebac1;">${m.created_at.substr(11, 5)}</small>
                    ${statusIcon}
                    ${deleteBtn}
                </div>
             </div>`;
  });

  const display = document.getElementById("messages-display");

  // Deteksi jika ada pesan baru, otomatis gulir ke bawah
  const isScrolledToBottom =
    display.scrollHeight - display.clientHeight <= display.scrollTop + 50;
  display.innerHTML = html;
  if (isScrolledToBottom) {
    display.scrollTop = display.scrollHeight;
  }
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

  input.value = "";
  await fetch("ajax/send_message.php", { method: "POST", body: fd });
  fetchMessages(currentReceiverId);
}

async function sendImage() {
  const fileInput = document.getElementById("chat-image");
  if (!fileInput.files[0]) return;

  // Gunakan fungsi upload cerdas yang sudah kita buat sebelumnya
  const imageUrl = await uploadToImgBB(fileInput.files[0]);
  if (imageUrl) {
    const fd = new FormData();
    fd.append("receiver_id", currentReceiverId);
    fd.append("image_url", imageUrl);
    await fetch("ajax/send_message.php", { method: "POST", body: fd });
    fetchMessages(currentReceiverId);
  }
}
