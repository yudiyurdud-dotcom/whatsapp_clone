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
    html += `
            <div class="contact-item" onclick="openChat(${user.id}, '${user.display_name}', '${user.avatar_url || "https://i.ibb.co/30B37f8/default-avatar.png"}')">
                <img src="${user.avatar_url || "https://i.ibb.co/30B37f8/default-avatar.png"}">
                <span><strong>${user.display_name}</strong></span>
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
  const res = await fetch(`ajax/fetch_messages.php?receiver_id=${receiverId}`);
  const data = await res.json();
  let html = "";

  data.forEach((m) => {
    let isMyMessage = m.sender_id == myUserId;
    let side = isMyMessage ? "msg-sent" : "msg-received";

    // Tampilkan tombol tong sampah HANYA untuk pesan yang kita kirim
    let deleteBtn = isMyMessage
      ? `<span onclick="deleteMessage(${m.id})" style="cursor:pointer; margin-left:10px; float:right;" title="Hapus Pesan">🗑️</span>`
      : "";

    html += `<div class="msg ${side}">
                ${m.message_text ? `<div>${m.message_text}</div>` : ""}
                ${m.image_url ? `<img src="${m.image_url}" class="msg-img">` : ""}
                <small style="font-size:10px; color:#aebac1; display:block; margin-top:5px; text-align:right;">
                    ${m.created_at.substr(11, 5)} ${deleteBtn}
                </small>
             </div>`;
  });

  const display = document.getElementById("messages-display");
  display.innerHTML = html;

  // Gulir ke bawah secara otomatis
  display.scrollTop = display.scrollHeight;
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
