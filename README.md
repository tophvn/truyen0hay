# 📚 Truyen0Hay - Website Đọc Truyện Tranh Online

Trang web truyện tranh sử dụng api của mangadex được phát triển với PHP thuần, sử dụng Laragon làm môi trường phát triển, tích hợp các dịch vụ như Google OAuth, Cloudflare Turnstile và Imgur API.

## 🚀 Demo Website

👉 [https://truyen0hay.site](https://truyen0hay.site)

---

## 🔧 Cấu hình & Hướng dẫn thay đổi

### 1. 📧 Cấu hình gửi Email - `config/send_email.php`

```php
// Dòng 71-72
$mail->Username = 'truyentranhnetcontact@gmail.com'; // Email gửi
$mail->Password = 'juuf bzoq eysl zdag'; // 🔐 Mật khẩu ứng dụng Gmail

// Dòng 79-80
$resetLink = "http://truyenkhonghay.test/src/auth/reset_password.php?token=" . $token;
// => Khi deploy online, dùng:
$resetLink = "https://truyent0hay.site/src/auth/reset_password.php?token=" . $token;
```
### 2. 🔐 Google OAuth2 Login - `src/auth/login.php`
```
// Dòng 14-16
$googleClientID = '###';  // thay Client ID của gg auth platform
$googleClientSecret = '###'; // thay Client secrets của gg auth platform
$googleRedirectUri = 'http://localhost/truyenkhonghay/src/auth/login.php'; // thay local host = domain của bạn
```
🔗 Tạo OAuth client ID tại: https://console.cloud.google.com/apis/credentials
### 3. 🧱 Cloudflare Turnstile - Chống spam form
// login.php dòng 24
$turnstileSecret = '###';

// register.php dòng 6
$turnstileSecret = '###';
🔗 Tạo Turnstile key tại: https://dash.cloudflare.com/ > Turnstile
### 4. 💾 Cấu hình cơ sở dữ liệu - `config/database.php`
// Dòng 2-5
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "truyen0hay";
📌 Sửa theo thông tin máy chủ SQL của bạn.
Khi chạy trên local thì truy cập đường dẫn ``http://truyenkhonghay.test/ `` để hoạt động chính xác.
### 5. 📸 Cấu hình Imgur API - `admin/upload-manga.php`
// Dòng 48, 123, 256
$imgurClientId = '3cea3f0e5d5c043'; // Client ID của bạn
🔗 Tạo ứng dụng tại: https://api.imgur.com/oauth2/addclient

## ✅ Yêu cầu hệ thống
PHP >= 7.4

MySQL/MariaDB

Laragon (hoặc XAMPP/WAMP)

Composer (nếu mở rộng)

## 📁 Cấu trúc thư mục chính
```truyen0hay/
├── admin/
│   ├── setting.php
│   └── upload-manga.php
├── config/
│   ├── config.php
│   ├── database.php
│   └── send_email.php
├── css/
│   ├── manga.css
│   └── style.css
├── google-api/
├── includes/
│   ├── advanced-search-form.php
│   ├── content-customizer.php
│   ├── count_views.txt
│   ├── footer.php
│   ├── get-group.php
│   ├── latest-card.php
│   ├── manga-up.php
│   ├── navbar.php
│   ├── pagination.php
│   ├── search-groups.php
│   ├── sidebar.php
│   ├── staff-pick-card.php
│   ├── swiper-components.php
│   ├── theme-customizer.php
│   └── track_visits.php
├── js/
│   ├── advanced-search.js
│   ├── main.js
│   ├── reader.js
│   └── search.js
├── lib/
│   ├── functions.php
│   └── PHPMailer/
│       └── (các file của PHPMailer)
├── public/
│   ├── flags/
│   ├── icon/
│   └── images/
├── src/
│   ├── auth/
│   │   ├── forget_password.php
│   │   ├── login.php
│   │   ├── logout.php
│   │   ├── register.php
│   │   └── reset_password.php
│   └── session.php
├── .htaccess
├── advanced-search.php
├── chapter.php
├── doc.php
├── follow.php
├── groups.php
├── history.php
├── index.php
├── latest.php
├── manga.php
├── proxy-0hay.php
├── readme.md
├── recent.php
├── search-suggestions.php
├── search.php
└── truyen.php
```
🤝 Góp ý & Liên hệ
Mọi góp ý hoặc lỗi vui lòng gửi về:
📩 truyentranhnetcontact@gmail.com
