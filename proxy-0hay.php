<?php
if (!isset($_GET['url'])) {
    http_response_code(400);
    die("Thiếu URL ảnh.");
}

$imageUrl = $_GET['url'];
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';

// Tải ảnh từ MangaDex
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $imageUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$imageData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$curlError = curl_error($ch);
curl_close($ch);

if ($httpCode === 200 && $imageData) {
    // Trả về ảnh với HTTP Cache
    header("Content-Type: $contentType");
    header("Access-Control-Allow-Origin: *");
    header("Cache-Control: public, max-age=604800"); // Cache 7 ngày ở trình duyệt
    header("Expires: " . gmdate("D, d M Y H:i:s", time() + 604800) . " GMT");
    echo $imageData;
} else {
    error_log("Proxy image failed: URL=$imageUrl, HTTP_CODE=$httpCode, ERROR=$curlError, USER_AGENT=$userAgent");
    header("Content-Type: image/jpeg");
    header("Access-Control-Allow-Origin: *");
    readfile(__DIR__ . '/public/placeholder.jpg') or die("Placeholder not found");
}
?>