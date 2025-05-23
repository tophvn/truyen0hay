<?php
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../../config/database.php'; 
require_once __DIR__ . '/../../google-api/vendor/autoload.php';

$baseUrl = rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';

// Lưu trang trước đó vào session nếu chưa có
if (!isset($_SESSION['redirect_url']) && isset($_SERVER['HTTP_REFERER'])) {
    $_SESSION['redirect_url'] = $_SERVER['HTTP_REFERER'];
}

// Google Client Configuration
$googleClientID = 'aaaaaaaaa';  // thay Client ID của gg auth platform
$googleClientSecret = 'aaaaaaaaa'; // thay Client secrets của gg auth platform
$googleRedirectUri = 'http://localhost/truyenkhonghay/src/auth/login.php'; //thay localhost = domain của bạn nếu deloy lên hosting
$client = new Google_Client();
$client->setClientId($googleClientID);
$client->setClientSecret($googleClientSecret);
$client->setRedirectUri($googleRedirectUri);
$client->addScope("email");
$client->addScope("profile");

$turnstileSecret = 'aaaaaaaaa'; //thay bằng turnstile cloudflare của bạn 
$errors = [];

function generateDefaultName($conn) {
    $result = $conn->query("SELECT MAX(CAST(SUBSTRING(name, 5) AS UNSIGNED)) as max_num FROM users WHERE name LIKE 'user%'");
    $row = $result->fetch_assoc();
    $maxNum = $row['max_num'] ?? 0;
    $newNum = $maxNum + 1;
    return "user$newNum";
}

// Handle Google Login Callback
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (empty($token['error'])) {
        $client->setAccessToken($token['access_token']);
        $googleService = new Google_Service_Oauth2($client);
        $googleUser = $googleService->userinfo->get();

        $email = $googleUser->email;
        $username = md5($email); // Tạo username từ email
        $avatar = $googleUser->picture;

        // Kiểm tra xem email đã tồn tại chưa
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user) {
            // Nếu chưa tồn tại, tạo user mới
            $defaultPassword = password_hash(uniqid(), PASSWORD_DEFAULT);
            $name = generateDefaultName($conn); // Gán user1, user2, ...
            $stmt = $conn->prepare("INSERT INTO users (username, email, name, password, roles, avatar) VALUES (?, ?, ?, ?, 'user', ?)");
            $stmt->bind_param("sssss", $username, $email, $name, $defaultPassword, $avatar);
            $stmt->execute();
            $userId = $conn->insert_id;
        } else {
            // Nếu đã tồn tại, lấy thông tin user
            $userId = $user['user_id'];
            $name = $user['name'] ?? generateDefaultName($conn); // Nếu NULL, gán mặc định
            $avatar = $user['avatar'];
            $email = $user['email']; // Lấy email từ DB
            if ($user['name'] === null) {
                $stmt = $conn->prepare("UPDATE users SET name = ? WHERE user_id = ?");
                $stmt->bind_param("si", $name, $userId);
                $stmt->execute();
            }
        }

        // Lưu thông tin vào session, bao gồm email
        $_SESSION['user'] = [
            'user_id' => $userId,
            'username' => $username,
            'name' => $name,
            'email' => $email,
            'roles' => $user['roles'] ?? 'user',
            'avatar' => $avatar,
        ];

        // Chuyển hướng về trang trước đó hoặc mặc định về /index.php
        $redirectUrl = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : '/index.php';
        unset($_SESSION['redirect_url']); // Xóa sau khi dùng
        header("Location: $redirectUrl");
        exit();
    } else {
        $errors[] = 'Đăng nhập Google thất bại!';
    }
}

// Handle Regular Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    $cfToken = $_POST['cf-turnstile-response'] ?? '';

    // Kiểm tra Turnstile
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

    if (!$turnstileResponse || !isset($turnstileResponse['success']) || !$turnstileResponse['success']) {
        $errors[] = 'Xác minh Turnstile thất bại!';
    } else {
        // Kiểm tra username hoặc email
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $login, $login);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            if (password_verify($password, $user['password'])) {
                // Đăng nhập thành công, lưu thông tin vào session, bao gồm email
                $_SESSION['user'] = [
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'roles' => $user['roles'],
                    'avatar' => $user['avatar'],
                ];

                // Chuyển hướng về trang trước đó hoặc mặc định về /index.php
                $redirectUrl = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : '/index.php';
                unset($_SESSION['redirect_url']); // Xóa sau khi dùng
                header("Location: $redirectUrl");
                exit();
            } else {
                $errors[] = 'Sai mật khẩu!';
            }
        } else {
            $errors[] = 'Sai tên đăng nhập hoặc email!';
        }
    }
}

// Generate Google Login URL
$googleLoginUrl = $client->createAuthUrl();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../../public/images/logo.png" rel="icon">
    <title>Đăng Nhập - TRUYEN0HAY</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body class="bg-white text-gray-900 min-h-screen">
    <?php include '../../includes/navbar.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>
 
    <main class="container mx-auto px-4 py-8 pt-16 flex items-center justify-center min-h-screen">
        <div class="w-full max-w-md bg-white border border-gray-200 rounded-xl shadow-lg p-8">
            <!-- Tiêu đề -->
            <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Đăng Nhập</h1>

            <!-- Thông báo lỗi -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6">
                    <?php foreach ($errors as $error): ?>
                        <p class="text-sm"><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Form đăng nhập -->
            <form action="" method="POST" class="space-y-6">
                <div>
                    <label for="login" class="block text-sm font-medium text-gray-700 mb-1">Tên đăng nhập hoặc Email</label>
                    <input type="text" id="login" name="login" placeholder="Tên đăng nhập hoặc Email" required
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu</label>
                    <input type="password" id="password" name="password" placeholder="Mật khẩu" required
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                 <div class="cf-turnstile" data-sitekey="aaaaaaaaaaaaaaa"></div> <!-- thay site key của turnstile cloudflare -->
                <button type="submit" class="w-full bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                    Đăng Nhập
                </button>
            </form>

            <!-- Liên kết đăng ký và quên mật khẩu -->
            <div class="text-center text-sm text-gray-600 mt-4">
                Chưa có tài khoản? 
                <a href="register.php" class="text-blue-600 hover:text-blue-500 font-medium">Đăng ký</a>
                <span class="mx-2">|</span>
                <a href="forgot_password.php" class="text-blue-600 hover:text-blue-500 font-medium">Quên mật khẩu?</a>
            </div>

            <!-- Đăng nhập bằng Google -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 bg-white text-gray-600">Hoặc tiếp tục với</span>
                </div>
            </div>
            <div class="flex justify-center">
                <a href="<?php echo $googleLoginUrl; ?>" class="flex items-center justify-center w-12 h-12 bg-gray-100 rounded-full hover:bg-gray-200 transition duration-300">
                    <img src="../../public/Icon/icon-google.svg" alt="Google" class="w-6 h-6">
                </a>
            </div>
        </div>
    </main>

    <!-- JavaScript -->
    <script src="/js/main.js"></script>
    <script src="/js/search.js"></script>
</body>
</html>