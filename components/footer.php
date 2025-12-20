<footer>
  <div class="footer-container">
    <div class="footer-about">
      <h3>🌸 Blossomy Bliss</h3>
      <p>Gửi hoa tươi – gửi yêu thương đến những người bạn trân quý.</p>
    </div>

    <div class="footer-contact">
      <h4>Liên hệ</h4>
      <ul>
        <li><i class="fa-solid fa-location-dot"></i> 123 Hoa Đào, Quận 1, TP.HCM</li>
        <li><i class="fa-solid fa-phone"></i> 0909 999 999</li>
        <li><i class="fa-solid fa-envelope"></i> contact@blossomy.vn</li>
      </ul>
    </div>

    <div class="footer-social">
      <h4>Kết nối với chúng tôi</h4>
      <div class="social-icons">
        <a href="#" class="facebook"><i class="fa-brands fa-facebook-f"></i></a>
        <a href="#" class="instagram"><i class="fa-brands fa-instagram"></i></a>
        <a href="#" class="tiktok"><i class="fa-brands fa-tiktok"></i></a>
      </div>
    </div>
  </div>

  <div class="footer-bottom">
    <p>© 2025 <strong>Blossomy Bliss</strong> – Gửi hoa tươi, gửi yêu thương 🌷</p>
  </div>
  <!-- Floating chat button -->
  <a class="chat-fab" href="https://m.me/889666434226882?ref=chat" target="_blank" rel="noopener noreferrer" aria-label="Chat Facebook">
    <i class="fa-brands fa-facebook-messenger"></i>
  </a>
  <script>
    // Nếu SDK Facebook đã sẵn sàng, mở popup chat thay vì rời trang; nếu chưa, sẽ rơi xuống link m.me
    document.addEventListener('DOMContentLoaded', function() {
      var fab = document.querySelector('.chat-fab');
      if (!fab) return;
      fab.addEventListener('click', function(e) {
        var canShowPopup = false;
        if (window.FB && FB.CustomerChat && typeof FB.CustomerChat.show === 'function') {
          try {
            FB.CustomerChat.show(true);
            canShowPopup = true;
          } catch (err) {
            // plugin chua san sang, cho trinh duyet di theo link
          }
        }

        // Chi chan dieu huong khi da mo duoc popup chat
        if (canShowPopup) {
          e.preventDefault();
        }
      });
    });

  </script>

  <!-- CHATBOT -->
<div id="chatbot-widget">
  <div id="chat-icon">💬</div>

  <div id="chat-window">
    <div id="chat-header">Chat hỗ trợ khách hàng</div>

    <div id="chat-messages"></div>

    <div id="chat-input">
      <input type="text" id="userMessage" placeholder="Nhập tin nhắn...">
      <button id="sendBtn">Gửi</button>
    </div>
  </div>
</div>
<script>
const chatIcon = document.getElementById("chat-icon");
const chatWindow = document.getElementById("chat-window");
const messagesDiv = document.getElementById("chat-messages");
const inputField = document.getElementById("userMessage");
const sendBtn = document.getElementById("sendBtn");

// Avatar
const botAvatar = "assets/images/z7128943872304_7000db2b5f7c476efb8c375bf165f8e8.jpg";
const userAvatar = "assets/images/avatar_user.jpg";

let historyLoaded = false;
let isSending = false;

// ================== GREETING HELPERS ==================
function getBotGreetingHTML() {
  return `Chào anh/chị ạ 🌸<br>
  Em là trợ lý của <b>Blossomy Bliss</b>.<br>
  Anh/chị cần em hỗ trợ tìm hoa theo <b>dịp tặng</b>, <b>ngân sách</b> hay <b>loại hoa</b> nào không ạ?`;
}

function markGreeted() {
  sessionStorage.setItem("bb_chat_greeted", "1");
}

function hasGreeted() {
  return sessionStorage.getItem("bb_chat_greeted") === "1";
}

// ================== UTILS ==================
// Escape HTML cho user (để user không nhét script)
function escapeHtml(str) {
  return String(str)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

// ================== TYPING EFFECT ==================
function showTyping() {
  let box = document.createElement("div");
  box.className = "msg-box bot-box typing-box";
  box.innerHTML = `
      <img src="${botAvatar}" class="avatar">
      <div class="typing">⋯</div>
  `;
  messagesDiv.appendChild(box);
  messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function removeTyping() {
  const t = document.querySelector(".typing-box");
  if (t) t.remove();
}

// ================== APPEND MESSAGE ==================
function appendMessage(text, role) {
  let box = document.createElement("div");
  box.className = `msg-box ${role}-box`;

  let avatar = role === "user" ? userAvatar : botAvatar;
  const content = (role === "user") ? escapeHtml(text) : (text ?? "");

  box.innerHTML = `
    <img src="${avatar}" class="avatar">
    <div class="message ${role}">${content}</div>
  `;

  messagesDiv.appendChild(box);
  messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

// ================== LOAD HISTORY + AUTO GREETING ==================
async function loadHistoryOnce() {
  if (historyLoaded) return;
  historyLoaded = true;

  try {
    const res = await fetch("/Flower_Shop/components/get_history.php", {
      method: "GET",
      credentials: "same-origin"
    });
    const data = await res.json();

    messagesDiv.innerHTML = "";

    if (Array.isArray(data) && data.length > 0) {
      // Có lịch sử -> render lịch sử
      data.forEach(msg => {
        appendMessage(msg.message, msg.role);
      });
      markGreeted();
    } else {
      // ✅ Không có lịch sử -> bot chào trước
      if (!hasGreeted()) {
        appendMessage(getBotGreetingHTML(), "bot");
        markGreeted();
      }
    }
  } catch (e) {
    console.warn("Không load được history", e);

    // fallback: vẫn chào để UX không trống
    if (!hasGreeted()) {
      appendMessage(getBotGreetingHTML(), "bot");
      markGreeted();
    }
  }
}

// ================== TOGGLE CHAT ==================
chatIcon.onclick = async () => {
  const isOpen = (chatWindow.style.display === "flex");
  chatWindow.style.display = isOpen ? "none" : "flex";

  if (!isOpen) {
    await loadHistoryOnce();
    setTimeout(() => inputField.focus(), 100);
  }
};

// ================== SEND MESSAGE ==================
async function sendMessage() {
  const message = inputField.value.trim();
  if (!message || isSending) return;

  isSending = true;
  sendBtn.disabled = true;

  appendMessage(message, "user");
  inputField.value = "";

  showTyping();

  try {
    const response = await fetch("/Flower_Shop/components/chat.php", {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ message })
    });

    const data = await response.json();
    removeTyping();

    const reply = data?.reply ?? "Xin lỗi anh/chị, em chưa nhận được phản hồi!";
    appendMessage(reply, "bot");

  } catch (err) {
    console.error(err);
    removeTyping();
    appendMessage("⚠️ Lỗi kết nối server!", "bot");
  } finally {
    isSending = false;
    sendBtn.disabled = false;
  }
}

sendBtn.onclick = sendMessage;

inputField.addEventListener("keypress", (e) => {
  if (e.key === "Enter") sendMessage();
});
</script>
</footer>