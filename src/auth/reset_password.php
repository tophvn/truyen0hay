<?php
require_once __DIR__ . '/../session.php'; 
require_once __DIR__ . '/../../config/database.php';

if (!$conn) {
    die("Kết nối cơ sở dữ liệu thất bại: " . mysqli_connect_error());
}

// Kiểm tra token từ URL
if (!isset($_GET['token']) || empty($_GET['token'])) {
    $error_message = "Mã xác nhận không được cung cấp.";
} else {
    $token = trim($_GET['token']);
    error_log("Token từ URL: " . $token);

    // Sử dụng prepared statement để tránh SQL Injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $error_message = "Mã xác nhận không hợp lệ hoặc đã hết hạn.";
        error_log("Không tìm thấy token trong DB: " . $token);
    } else {
        $row = $result->fetch_assoc();
        $email = $row['email'];
        error_log("Token hợp lệ, email: " . $email);
    }
}

$error = "";
$success_message = "";

if (isset($email) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 6) {
        $error = "Mật khẩu phải lớn hơn 6 ký tự.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Mật khẩu xác nhận không khớp.";
    } else {
        // Mã hóa mật khẩu bằng password_hash
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);

        if ($stmt->execute()) {
            $success_message = "Mật khẩu của bạn đã được thay đổi thành công.";
        } else {
            $error = "Có lỗi xảy ra khi cập nhật mật khẩu: " . $conn->error;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../../public/images/logo.png" rel="icon">
    <title>Đặt Lại Mật Khẩu - TRUYENTRANHNET</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <?php include '../../includes/navbar.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>

    <main class="container mx-auto px-4 py-8 pt-16 flex items-center justify-center min-h-screen">
        <div class="max-w-md w-full bg-gray-800 p-6 rounded-lg shadow-lg">
            <h1 class="text-center text-3xl font-bold mb-4">Đặt Lại Mật Khẩu</h1>
            <p class="text-center text-gray-400 mb-6">Nhập mật khẩu mới cho tài khoản của bạn.</p>

            <?php if (isset($error_message)): ?>
                <div class="bg-red-500 text-white p-3 rounded-lg mb-4">
                    <p class="text-sm"><?php echo $error_message; ?></p>
                </div>
                <div class="text-center text-sm text-gray-400 mt-4">
                    Quay lại <a href="<?php echo getPath('login'); ?>" class="text-blue-400 hover:text-blue-300">Đăng Nhập</a>
                </div>
            <?php elseif (!empty($error)): ?>
                <div class="bg-red-500 text-white p-3 rounded-lg mb-4">
                    <p class="text-sm"><?php echo $error; ?></p>
                </div>
            <?php elseif (!empty($success_message)): ?>
                <div class="bg-green-500 text-white p-3 rounded-lg mb-4">
                    <p class="text-sm"><?php echo $success_message; ?></p>
                </div>
                <div class="text-center text-sm text-gray-400 mt-4">
                    Quay lại <a href="<?php echo getPath('login'); ?>" class="text-blue-400 hover:text-blue-300">Đăng Nhập</a>
                </div>
            <?php else: ?>
                <form action="" method="POST" class="space-y-4">
                    <div>
                        <label for="new_password" class="block text-sm font-medium mb-1">Mật Khẩu Mới</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Mật khẩu mới" required
                               class="w-full p-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium mb-1">Xác Nhận Mật Khẩu</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Xác nhận mật khẩu" required
                               class="w-full p-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded-lg hover:bg-blue-700 transition">
                        Cập Nhật Mật Khẩu
                    </button>
                    <div class="text-center text-sm text-gray-400 mt-4">
                        Quay lại <a href="<?php echo getPath('login'); ?>" class="text-blue-400 hover:text-blue-300">Đăng Nhập</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>
    <script src="/js/main.js"></script>
    <script src="/js/search.js"></script>
</body>
</html>