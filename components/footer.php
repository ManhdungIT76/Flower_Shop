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
    document.addEventListener('DOMContentLoaded', function() {
      var fab = document.querySelector('.chat-fab');
      if (!fab) return;
      fab.addEventListener('click', function(e) {
        var canShowPopup = false;
        if (window.FB && FB.CustomerChat && typeof FB.CustomerChat.show === 'function') {
          try {
            FB.CustomerChat.show(true);
            canShowPopup = true;
          } catch (err) {}
        }
        if (canShowPopup) e.preventDefault();
      });
    });
  </script>

  <!-- CHATBOT -->
  <div id="chatbot-widget">
    <div id="chat-icon">💬</div>

    <div id="chat-window">
      <div id="chat-header">Chat hỗ trợ khách hàng</div>

      <!-- START SCREEN -->
      <div id="chat-start-screen">
        <div class="chat-start-card">
          <div class="chat-start-avatar">👤</div>
          <label class="chat-start-label">Họ Tên:<span class="chat-req">*</span></label>
          <input type="text" id="chatUserName" class="chat-start-input" placeholder="Nhập họ tên...">
          <div id="chatNameError" class="chat-name-error"></div>
          <button id="chatStartBtn" class="chat-start-btn">Bắt đầu</button>
        </div>
      </div>

      <!-- CHAT BODY -->
      <div id="chat-body">
        <div id="chat-messages"></div>

        <div id="chat-input">
          <input type="text" id="userMessage" placeholder="Nhập tin nhắn...">
          <button id="sendBtn">Gửi</button>
        </div>
      </div>
    </div>
  </div>

  <style>
    /* Nếu bạn đã có CSS chatbot rồi thì chỉ cần giữ 3 dòng display dưới đây */
    #chat-window { display: none; flex-direction: column; }
    #chat-start-screen { display: none; flex: 1; align-items: center; justify-content: center; padding: 16px; }
    #chat-body { display: none; flex: 1; flex-direction: column; }
    
  </style>

  <script>
    const chatIcon = document.getElementById("chat-icon");
    const chatWindow = document.getElementById("chat-window");
    const messagesDiv = document.getElementById("chat-messages");
    const inputField = document.getElementById("userMessage");
    const sendBtn = document.getElementById("sendBtn");

    const chatStartScreen = document.getElementById("chat-start-screen");
    const chatBody = document.getElementById("chat-body");
    const nameInput = document.getElementById("chatUserName");
    const startBtn = document.getElementById("chatStartBtn");
    const nameError = document.getElementById("chatNameError");

    // Avatar
    const botAvatar = "assets/images/z7128943872304_7000db2b5f7c476efb8c375bf165f8e8.jpg";
    const userAvatar = "assets/images/avt.png";

    let historyLoaded = false;
    let isSending = false;

    function getStoredName() {
      return (localStorage.getItem("bb_chat_name") || "").trim();
    }
    function setStoredName(name) {
      localStorage.setItem("bb_chat_name", name);
    }

    function showStartScreen() {
      chatStartScreen.style.display = "flex";
      chatBody.style.display = "none";
      nameError.textContent = "";
      setTimeout(() => nameInput.focus(), 50);
    }

    function showChatBody() {
      chatStartScreen.style.display = "none";
      chatBody.style.display = "flex";
    }


    async function ensureLoginState() {
  // luôn kiểm tra mỗi lần mở chat để bắt logout/login
  try {
    const res = await fetch("/Flower_Shop/components/chat_user.php", {
      method: "GET",
      credentials: "same-origin",
      cache: "no-store"
    });
    const data = await res.json();

    const loggedIn = !!data?.logged_in;
    const name = (data?.name || "").trim();

    // ✅ Nếu đã đăng nhập: đồng bộ tên từ DB
    if (loggedIn) {
      if (name) setStoredName(name);
      return { loggedIn: true, name };
    }

    // ❌ Nếu chưa đăng nhập: xóa tên cũ để hiện form nhập tên
    localStorage.removeItem("bb_chat_name");
    return { loggedIn: false, name: "" };

  } catch (e) {
    // nếu lỗi gọi API, coi như chưa đăng nhập và xóa tên để tránh chào sai
    localStorage.removeItem("bb_chat_name");
    return { loggedIn: false, name: "" };
  }
}


    // ================== GREETING HELPERS ==================
    function escapeHtml(str) {
      return String(str)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
    }

    function getBotGreetingHTML(name) {
      const safeName = escapeHtml((name || "").trim());
      if (safeName) {
        return `Chào ${safeName}, 🌸<br>
        Em là trợ lý của <b>Blossomy Bliss</b>.<br>
        Anh/chị cần em hỗ trợ tìm hoa theo <b>dịp tặng</b>, <b>ngân sách</b> hay <b>loại hoa</b> nào không ạ?`;
      }
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

  const greetIfEmpty = () => {
    if (messagesDiv.childElementCount === 0) {
      appendMessage(getBotGreetingHTML(getStoredName()), "bot");
      markGreeted();
    }
  };

  try {
    const res = await fetch("/Flower_Shop/components/get_history.php", {
      method: "GET",
      credentials: "same-origin"
    });
    const data = await res.json();

    messagesDiv.innerHTML = "";

    if (Array.isArray(data) && data.length > 0) {
      data.forEach(msg => appendMessage(msg.message, msg.role));
      markGreeted();
    } else {
      // ✅ Không có lịch sử -> luôn chào nếu đang trống
      greetIfEmpty();
    }
  } catch (e) {
    // ✅ Lỗi load -> vẫn chào nếu đang trống
    messagesDiv.innerHTML = "";
    greetIfEmpty();
  }
}

    // ================== TOGGLE CHAT ==================
    chatIcon.onclick = async () => {
      const isOpen = (chatWindow.style.display === "flex");
      chatWindow.style.display = isOpen ? "none" : "flex";
      if (isOpen) return;

      const st = await ensureLoginState();

      // ✅ ĐÃ ĐĂNG NHẬP: mở chat luôn, không hiện form nhập tên
      if (st.loggedIn) {
        showChatBody();
        await loadHistoryOnce();

        // nếu chưa greeted (trường hợp history rỗng + chưa chào) thì chào theo tên DB
        if (!hasGreeted()) {
          appendMessage(getBotGreetingHTML(st.name || getStoredName()), "bot");
          markGreeted();
        }

        setTimeout(() => inputField.focus(), 100);
        return;
      }

      // ❌ CHƯA ĐĂNG NHẬP: yêu cầu nhập tên
      const name = getStoredName();
      if (!name) {
        showStartScreen();
        return;
      }

      showChatBody();
      await loadHistoryOnce();
      setTimeout(() => inputField.focus(), 100);
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

    // ================== START BUTTON ==================
    async function handleStart() {
      const st = await ensureLoginState();

      // Nếu đã đăng nhập thì bỏ qua nhập tên
      if (st.loggedIn) {
        showChatBody();
        await loadHistoryOnce();
        setTimeout(() => inputField.focus(), 50);
        return;
      }

      const name = (nameInput.value || "").trim();
      if (!name) {
        nameError.textContent = "Vui lòng nhập họ tên.";
        return;
      }

      nameError.textContent = "";
      setStoredName(name);

      showChatBody();

      // Chào theo tên (nếu chưa chào)
      if (!hasGreeted()) {
        appendMessage(getBotGreetingHTML(name), "bot");
        markGreeted();
      } else {
        await loadHistoryOnce();
      }

      setTimeout(() => inputField.focus(), 50);
    }

    startBtn.addEventListener("click", handleStart);
    nameInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter") handleStart();
    });
  </script>
</footer>
