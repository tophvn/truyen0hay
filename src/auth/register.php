<?php
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../../config/database.php'; 
header('Content-Type: text/html; charset=UTF-8');

$turnstileSecret = '0x4AAAAAABBmdz5FqnaxoDoaMqkvkbV7Q1o';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : ''; 
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $cfToken = $_POST['cf-turnstile-response'] ?? '';

    $turnstileUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $turnstileData = [
        'secret' => $turnstileSecret,
        'response' => $cfToken,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];

    $ch = curl_init($turnstileUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($turnstileData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $turnstileResponse = json_decode(curl_exec($ch), true);
    curl_close($ch);

    // Kiểm tra phản hồi từ Turnstile
    if (!$turnstileResponse || !isset($turnstileResponse['success']) || !$turnstileResponse['success']) {
        $errors['turnstile'] = 'Xác minh thất bại!';
    } else {
        // Kiểm tra dữ liệu
        if (empty($username)) {
            $errors['username'] = 'Tên đăng nhập không được để trống!';
        }
        if (empty($name)) {
            $errors['name'] = 'Tên không được để trống!';
        } elseif (strlen($name) < 2) {
            $errors['name'] = 'Tên phải có ít nhất 2 ký tự!';
        }
        if ($password !== $confirmPassword) {
            $errors['confirm_password'] = 'Mật khẩu không trùng khớp!';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự!';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email không hợp lệ!';
        }

        // Kiểm tra username và email đã tồn tại chưa
        if (!isset($conn)) {
            $errors['database'] = 'Không thể kết nối cơ sở dữ liệu!';
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if ($user['username'] === $username) {
                    $errors['username'] = 'Tên đăng nhập đã tồn tại!';
                }
                if ($user['email'] === $email) {
                    $errors['email'] = 'Email đã được sử dụng!';
                }
            }

            // Nếu không có lỗi, thêm vào CSDL
            if (empty($errors)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, name, email, password, roles) VALUES (?, ?, ?, ?, 'user')");
                $stmt->bind_param("ssss", $username, $name, $email, $hashedPassword);
                if ($stmt->execute()) {
                    header("Location: /src/auth/login.php"); 
                    exit();
                } else {
                    $errors['database'] = 'Đăng ký không thành công: ' . $conn->error;
                }
            }
        }
    }
} else {
    $username = '';
    $name = '';
    $email = '';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/../../public/truyentranhnet.png">
    <link href="/css/style.css" rel="stylesheet">
    <title>Đăng Ký - TRUYEN0HAY</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body class="bg-white text-gray-900 min-h-screen">
<?php include '../../includes/navbar.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>
    <main class="container mx-auto px-4 py-8 pt-16 flex items-center justify-center min-h-screen">
        <div class="w-full max-w-md bg-white border border-gray-200 rounded-xl shadow-lg p-8">
            <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Đăng Ký</h1>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6">
                    <?php foreach ($errors as $error): ?>
                        <p class="text-sm"><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Tên Đăng Nhập</label>
                    <input type="text" id="username" name="username" placeholder="Tên Đăng Nhập" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="off"
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Tên</label>
                    <input type="text" id="name" name="name" placeholder="Tên của bạn" value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="off"
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="off"
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu</label>
                    <input type="password" id="password" name="password" placeholder="Mật khẩu" required autocomplete="new-password"
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Xác Nhận Mật Khẩu</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Xác Nhận Mật Khẩu" required autocomplete="new-password"
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="cf-turnstile" data-sitekey="0x4AAAAAABBmd_DNscv-5Eca"></div>
                <button type="submit" class="w-full bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                    Đăng Ký
                </button>
            </form>

            <div class="text-center text-sm text-gray-600 mt-4">
                Đã có tài khoản? 
                <a href="/src/auth/login.php" class="text-blue-600 hover:text-blue-500 font-medium">Đăng nhập</a>
            </div>
        </div>
    </main>
    
    <script src="/js/main.js"></script>
    <script src="/js/search.js"></script>
</body>
</html>