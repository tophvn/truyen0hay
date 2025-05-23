<?php
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/send_email.php';

$errors = [];
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    // Kiểm tra email hợp lệ
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ!';
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $token = bin2hex(random_bytes(50));
            $stmt = $conn->prepare("UPDATE users SET reset_token = ? WHERE email = ?");
            $stmt->bind_param("ss", $token, $email);

            if ($stmt->execute()) {
                send_password_reset_email($email, $token);
                $message = 'Thành công! Truy cập email của bạn để đổi mật khẩu!';
            } else {
                $errors[] = 'Lỗi khi cập nhật token đặt lại mật khẩu.';
            }
        } else {
            $errors[] = 'Email không tồn tại trong hệ thống.';
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
    <title>Quên Mật Khẩu - TRUYEN0HAY</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <?php include '../../includes/navbar.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>

    <main class="container mx-auto px-4 py-8 pt-16 flex items-center justify-center min-h-screen">
        <div class="max-w-md w-full bg-gray-800 p-6 rounded-lg shadow-lg">
            <h1 class="text-center text-3xl font-bold mb-4">Quên Mật Khẩu</h1>
            <p class="text-center text-gray-400 mb-6">Vui lòng nhập địa chỉ email của bạn để đặt lại mật khẩu.</p>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-500 text-white p-3 rounded-lg mb-4">
                    <?php foreach ($errors as $error): ?>
                        <p class="text-sm"><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="bg-green-500 text-white p-3 rounded-lg mb-4">
                    <p class="text-sm"><?php echo $message; ?></p>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium mb-1">Địa Chỉ Email</label>
                    <input type="email" id="email" name="email" placeholder="info@example.com" required
                           class="w-full p-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded-lg hover:bg-blue-700 transition">
                    Đặt Lại Mật Khẩu
                </button>
                <div class="text-center text-sm text-gray-400 mt-4">
                    Quay lại <a href="<?php echo getPath('login'); ?>" class="text-blue-400 hover:text-blue-300">Đăng Nhập</a>
                </div>
            </form>
        </div>
    </main>
    <script src="/js/main.js"></script>
    <script src="/js/search.js"></script>
</body>
</html>