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
</footer>