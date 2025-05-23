<?php
$host = $_SERVER['HTTP_HOST'];
$isLocalhost = (stripos($host, 'localhost') !== false || stripos($host, '127.0.0.1') !== false);
define('SITE_URL', $isLocalhost ? 'http://localhost/truyenkhonghay' : 'https://truyen0hay.site');
define('API_BASE_URL', 'https://api.mangadex.org');
define('COVER_BASE_URL', '/proxy-0hay.php?url=' . urlencode('https://uploads.mangadex.org/covers')); 
define('SITE_NAME', 'TRUYENOHAY');
define('LIMIT_PER_PAGE', 18);
define('R18', false);
define('TRANSLATED_LANGUAGES', ['vi']);
?>