<?php
require_once __DIR__ . '/src/session.php';
require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/config/database.php';

$mangaId = $_GET['id'] ?? '';
$chapterId = $_GET['chapter'] ?? '';
$sortOrder = $_GET['sort'] ?? 'desc'; 

if (empty($mangaId)) {
    header('Location: index.php');
    exit;
}

// Tăng views ngẫu nhiên từ 20 đến 100 khi truy cập trang
if (!empty($mangaId)) {
    $randomViews = rand(20, 100);
    $stmt = $conn->prepare("UPDATE manga SET views = views + ? WHERE manga_id = ?");
    $stmt->bind_param("is", $randomViews, $mangaId);
    $stmt->execute();
    $stmt->close();
}

// Tự động lưu thông tin truyện khi truy cập
if (!empty($mangaId)) {
    $saveResult = saveMangaToDb($mangaId);
    if (!$saveResult) {
        error_log("Failed to save manga to DB: mangaId=$mangaId");
    }
}

// Kiểm tra manga_id trong CSDL
$stmt = $conn->prepare("SELECT manga_id FROM manga WHERE manga_id = ?");
$stmt->bind_param("s", $mangaId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $notFound = true;
} else {
    $manga = getMangaById($mangaId);
    if (!$manga) {
        $notFound = true;
    } else {
        $manga['tags'] = callMangadexApi("/manga/$mangaId")['data']['attributes']['tags'] ?? [];
        $stmt = $conn->prepare("SELECT views, comments_count, follows_count FROM manga WHERE manga_id = ?");
        $stmt->bind_param("s", $mangaId);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
        $manga['stats'] = [
            'rating' => ['bayesian' => 0],
            'follows' => $stats['follows_count'],
            'comments' => $stats['comments_count'],
            'views' => $stats['views']
        ];
        $chapters = getMangaChapters($mangaId, TRANSLATED_LANGUAGES, ['en']);
        if (!empty($chapterId)) {
            $chapterData = getChapterPages($chapterId);
        }
    }
}
$stmt->close();

// Xử lý dịch mô tả
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['translate'])) {
    $content = $_POST['content'] ?? '';
    if (!empty($content)) {
        $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=vi&dt=t&q=" . urlencode($content);
        $response = @file_get_contents($url);
        if ($response === false) {
            echo json_encode(['success' => false, 'message' => 'Không thể kết nối tới dịch vụ dịch!', 'type' => 'error']);
            exit;
        }
        $data = json_decode($response, true);
        if ($data && isset($data[0])) {
            $translatedText = implode('', array_column($data[0], 0));
            echo json_encode(['success' => true, 'translated' => $translatedText]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi xử lý dữ liệu dịch!', 'type' => 'error']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Nội dung cần dịch trống!', 'type' => 'error']);
    }
    exit;
}

// Kiểm tra trạng thái theo dõi
$isFollowing = false;
if (isset($_SESSION['user'])) {
    $userId = $_SESSION['user']['user_id'];
    $stmt = $conn->prepare("SELECT id FROM user_follows WHERE user_id = ? AND manga_id = ?");
    $stmt->bind_param("is", $userId, $mangaId);
    $stmt->execute();
    $result = $stmt->get_result();
    $isFollowing = $result->num_rows > 0;
    $stmt->close();
}

// Xử lý theo dõi/hủy theo dõi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_follow'])) {
    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để theo dõi truyện!', 'type' => 'error']);
        exit;
    }

    $userId = $_SESSION['user']['user_id'];
    $mangaId = $_GET['id'] ?? '';

    if (empty($mangaId) || empty($userId)) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ!', 'type' => 'error']);
        exit;
    }

    $stmt = $conn->prepare("SELECT manga_id FROM manga WHERE manga_id = ?");
    $stmt->bind_param("s", $mangaId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Truyện không tồn tại!', 'type' => 'error']);
        exit;
    }
    $stmt->close();

    $mangaData = callMangadexApi("/manga/{$mangaId}", ['includes' => ['cover_art']]);
    $coverUrl = '/public/images/default_cover.jpg';
    if ($mangaData && isset($mangaData['data'])) {
        foreach ($mangaData['data']['relationships'] as $rel) {
            if ($rel['type'] === 'cover_art' && isset($rel['attributes']['fileName'])) {
                $coverUrl = "https://uploads.mangadex.org/covers/{$mangaId}/{$rel['attributes']['fileName']}.256.jpg";
                break;
            }
        }
    }

    $conn->begin_transaction();
    try {
        if ($isFollowing) {
            $stmt = $conn->prepare("DELETE FROM user_follows WHERE user_id = ? AND manga_id = ?");
            $stmt->bind_param("is", $userId, $mangaId);
            $stmt->execute();
            $stmt = $conn->prepare("UPDATE manga SET follows_count = GREATEST(follows_count - 1, 0) WHERE manga_id = ?");
            $stmt->bind_param("s", $mangaId);
            $stmt->execute();
            echo json_encode(['success' => true, 'following' => false, 'message' => 'Đã hủy theo dõi', 'type' => 'error']);
        } else {
            $stmt = $conn->prepare("INSERT INTO user_follows (user_id, manga_id, cover_url, followed_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE followed_at = NOW()");
            $stmt->bind_param("iss", $userId, $mangaId, $coverUrl);
            $stmt->execute();
            $stmt = $conn->prepare("UPDATE manga SET follows_count = follows_count + 1 WHERE manga_id = ?");
            $stmt->bind_param("s", $mangaId);
            $stmt->execute();
            echo json_encode(['success' => true, 'following' => true, 'message' => 'Đã theo dõi', 'type' => 'success']);
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Follow toggle error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage(), 'type' => 'error']);
    }
    $stmt->close();
    exit;
}

// Xử lý thêm bình luận
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment']) && isset($_SESSION['user'])) {
    $userId = $_SESSION['user']['user_id'];
    $content = trim($_POST['comment_content'] ?? '');

    if (!empty($content)) {
        if (addComment($mangaId, $userId, $content)) {
            $stmt = $conn->prepare("UPDATE manga SET comments_count = comments_count + 1 WHERE manga_id = ?");
            $stmt->bind_param("s", $mangaId);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Không thể thêm bình luận']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Nội dung bình luận không được để trống']);
    }
    exit;
}

// Xử lý xóa bình luận
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment']) && isset($_SESSION['user'])) {
    $userId = $_SESSION['user']['user_id'];
    $commentId = $_POST['comment_id'] ?? 0;

    if (deleteComment($commentId, $userId)) {
        $stmt = $conn->prepare("UPDATE manga SET comments_count = GREATEST(comments_count - 1, 0) WHERE manga_id = ?");
        $stmt->bind_param("s", $mangaId);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Không thể xóa bình luận']);
    }
    exit;
}

// Lấy danh sách bình luận
$comments = getCommentsByMangaId($mangaId);

// Lấy truyện đề xuất
$randomManga = getRandomManga(5);

function renderStatusChip($status) {
    $statusTextColor = [
        'ongoing' => 'text-blue-500',
        'completed' => 'text-green-500',
        'hiatus' => 'text-gray-500',
        'cancelled' => 'text-red-500'
    ][$status] ?? 'text-gray-500';

    $statusOutline = [
        'ongoing' => 'outline-blue-500',
        'completed' => 'outline-green-500',
        'hiatus' => 'outline-gray-500',
        'cancelled' => 'outline-red-500'
    ][$status] ?? 'outline-gray-500';

    $statusBg = [
        'ongoing' => 'bg-blue-500',
        'completed' => 'bg-green-500',
        'hiatus' => 'bg-gray-500',
        'cancelled' => 'bg-red-500'
    ][$status] ?? 'bg-gray-500';

    $statusVietnamese = [
        'ongoing' => 'Đang tiến hành',
        'completed' => 'Hoàn thành',
        'hiatus' => 'Tạm ngưng',
        'cancelled' => 'Đã huỷ'
    ][$status] ?? 'Không rõ';

    return "
        <div class='inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 font-bold rounded-full text-xs uppercase $statusTextColor outline outline-2 -outline-offset-2 $statusOutline'>
            <span class='w-2 h-2 rounded-full $statusBg'></span>
            <a href='index.php?status=$status' class='hover:underline'>" . htmlspecialchars($statusVietnamese) . "</a>
        </div>
    ";
}

function renderContentRatingChip($rating) {
    if ($rating === 'safe') return '';

    $ratingColor = [
        'suggestive' => 'bg-yellow-500',
        'erotica' => 'bg-red-500',
        'pornographic' => 'bg-red-800'
    ][$rating] ?? 'bg-gray-500';

    return "
        <div class='inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 font-bold rounded-full text-xs uppercase text-white $ratingColor'>
            <a href='index.php?content=$rating' class='hover:underline'>" . htmlspecialchars($rating) . "</a>
        </div>
    ";
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <meta name="robots" content="index, follow">
    <title>
        <?php 
        if (isset($manga) && is_array($manga) && !empty($manga['title'])) {
            $mangaTitle = htmlspecialchars($manga['title']);
            if (!empty($chapterId) && isset($chapterData) && is_array($chapterData)) {
                // Lấy thông tin chương từ $chapters dựa trên $chapterId
                $chapterIndex = array_search($chapterId, array_column($chapters, 'id'));
                if ($chapterIndex !== false) {
                    $chapterNumber = htmlspecialchars($chapters[$chapterIndex]['chapter']);
                    $chapterTitle = htmlspecialchars($chapters[$chapterIndex]['title'] ?? 'Chương ' . $chapterNumber);
                    echo "$mangaTitle - $chapterTitle - Đọc Truyện Tranh Online - " . SITE_NAME;
                } else {
                    echo "$mangaTitle - Đọc Truyện Tranh Online - " . SITE_NAME;
                }
            } else {
                echo "$mangaTitle - Đọc Truyện Tranh Online - " . SITE_NAME;
            }
        } else {
            echo "Không tìm thấy - Đọc Truyện Tranh Online - " . SITE_NAME;
        }
        ?>
    </title>

    <!-- Meta Description -->
    <meta name="description" content="<?php 
        if (isset($manga) && is_array($manga) && !empty($manga['title'])) {
            $mangaTitle = htmlspecialchars($manga['title']);
            if (!empty($chapterId) && isset($chapterData) && is_array($chapterData)) {
                $chapterIndex = array_search($chapterId, array_column($chapters, 'id'));
                if ($chapterIndex !== false) {
                    $chapterNumber = htmlspecialchars($chapters[$chapterIndex]['chapter']);
                    $chapterTitle = htmlspecialchars($chapters[$chapterIndex]['title'] ?? 'Chương ' . $chapterNumber);
                    $description = "Đọc $mangaTitle $chapterTitle online, cập nhật chương mới nhất tại " . SITE_NAME . ". Khám phá truyện tranh hấp dẫn!";
                    echo substr($description, 0, 160);
                } else {
                    $description = isset($manga['description']) && is_array($manga['description']) && isset($manga['description']['content']) ? htmlspecialchars(strip_tags($manga['description']['content'])) : (is_string($manga['description']) ? htmlspecialchars(strip_tags($manga['description'])) : '');
                    $description = substr($description, 0, 100) . (strlen($description) > 100 ? '...' : '');
                    echo "Đọc truyện tranh $mangaTitle online, cập nhật chương mới nhất. $description";
                }
            } else {
                $description = isset($manga['description']) && is_array($manga['description']) && isset($manga['description']['content']) ? htmlspecialchars(strip_tags($manga['description']['content'])) : (is_string($manga['description']) ? htmlspecialchars(strip_tags($manga['description'])) : '');
                $description = substr($description, 0, 100) . (strlen($description) > 100 ? '...' : '');
                echo "Đọc truyện tranh $mangaTitle online, cập nhật chương mới nhất. $description";
            }
        } else {
            echo "Không tìm thấy truyện. Khám phá bộ sưu tập truyện tranh đa dạng, cập nhật hàng ngày tại " . SITE_NAME . ".";
        }
    ?>">

    <!-- Meta Keywords -->
    <meta name="keywords" content="<?php 
        if (isset($manga) && is_array($manga) && !empty($manga['title'])) {
            $mangaTitle = htmlspecialchars($manga['title']);
            $tags = [];
            if (isset($manga['tags']) && is_array($manga['tags'])) {
                $tags = array_map(function($tag) {
                    return $tag['attributes']['name']['en'] ?? $tag['attributes']['name']['vi'] ?? 'Unknown';
                }, $manga['tags']);
            }
            $tagsString = implode(', ', $tags);
            if (!empty($chapterId) && isset($chapterData) && is_array($chapterData)) {
                $chapterIndex = array_search($chapterId, array_column($chapters, 'id'));
                if ($chapterIndex !== false) {
                    $chapterNumber = htmlspecialchars($chapters[$chapterIndex]['chapter']);
                    $chapterTitle = htmlspecialchars($chapters[$chapterIndex]['title'] ?? 'Chương ' . $chapterNumber);
                    echo "$mangaTitle, $mangaTitle $chapterTitle, đọc truyện tranh $mangaTitle chương $chapterNumber, truyện tranh online, $tagsString, manga, manhwa, manhua";
                } else {
                    echo "$mangaTitle, đọc truyện tranh $mangaTitle, truyện tranh online, $tagsString, manga, manhwa, manhua";
                }
            } else {
                echo "$mangaTitle, đọc truyện tranh $mangaTitle, truyện tranh online, $tagsString, manga, manhwa, manhua";
            }
        } else {
            echo "truyện tranh, manga, manhwa, manhua, đọc truyện online, " . SITE_NAME;
        }
    ?>">

    <!-- Canonical URL -->
    <link rel="canonical" href="<?php 
        if (isset($manga) && is_array($manga) && !empty($manga['manga_id'])) {
            if (!empty($chapterId) && isset($chapterData) && is_array($chapterData)) {
                echo 'https://' . $_SERVER['HTTP_HOST'] . '/chapter/' . $chapterId;
            } else {
                echo 'https://' . $_SERVER['HTTP_HOST'] . '/manga.php?id=' . $manga['manga_id'];
            }
        } else {
            echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }
    ?>">

    <!-- Favicon -->
    <link href="/public/images/logo.png" rel="icon" type="image/png">

    <!-- Open Graph (OG) Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php 
        if (isset($manga) && is_array($manga) && !empty($manga['title'])) {
            $mangaTitle = htmlspecialchars($manga['title']);
            if (!empty($chapterId) && isset($chapterData) && is_array($chapterData)) {
                $chapterIndex = array_search($chapterId, array_column($chapters, 'id'));
                if ($chapterIndex !== false) {
                    $chapterNumber = htmlspecialchars($chapters[$chapterIndex]['chapter']);
                    $chapterTitle = htmlspecialchars($chapters[$chapterIndex]['title'] ?? 'Chương ' . $chapterNumber);
                    echo "$mangaTitle - $chapterTitle - Đọc Truyện Tranh Online - " . SITE_NAME;
                } else {
                    echo "$mangaTitle - Đọc Truyện Tranh Online - " . SITE_NAME;
                }
            } else {
                echo "$mangaTitle - Đọc Truyện Tranh Online - " . SITE_NAME;
            }
        } else {
            echo "Không tìm thấy - Đọc Truyện Tranh Online - " . SITE_NAME;
        }
    ?>">
    <meta property="og:description" content="<?php 
        if (isset($manga) && is_array($manga) && !empty($manga['title'])) {
            $mangaTitle = htmlspecialchars($manga['title']);
            if (!empty($chapterId) && isset($chapterData) && is_array($chapterData)) {
                $chapterIndex = array_search($chapterId, array_column($chapters, 'id'));
                if ($chapterIndex !== false) {
                    $chapterNumber = htmlspecialchars($chapters[$chapterIndex]['chapter']);
                    $chapterTitle = htmlspecialchars($chapters[$chapterIndex]['title'] ?? 'Chương ' . $chapterNumber);
                    $description = "Đọc $mangaTitle $chapterTitle online, cập nhật chương mới nhất tại " . SITE_NAME . ". Khám phá truyện tranh hấp dẫn!";
                    echo substr($description, 0, 160);
                } else {
                    $description = isset($manga['description']) && is_array($manga['description']) && isset($manga['description']['content']) ? htmlspecialchars(strip_tags($manga['description']['content'])) : (is_string($manga['description']) ? htmlspecialchars(strip_tags($manga['description'])) : '');
                    $description = substr($description, 0, 100) . (strlen($description) > 100 ? '...' : '');
                    echo "Đọc truyện tranh $mangaTitle online, cập nhật chương mới nhất. $description";
                }
            } else {
                $description = isset($manga['description']) && is_array($manga['description']) && isset($manga['description']['content']) ? htmlspecialchars(strip_tags($manga['description']['content'])) : (is_string($manga['description']) ? htmlspecialchars(strip_tags($manga['description'])) : '');
                $description = substr($description, 0, 100) . (strlen($description) > 100 ? '...' : '');
                echo "Đọc truyện tranh $mangaTitle online, cập nhật chương mới nhất. $description";
            }
        } else {
            echo "Không tìm thấy truyện. Khám phá bộ sưu tập truyện tranh đa dạng, cập nhật hàng ngày tại " . SITE_NAME . ".";
        }
    ?>">
    <meta property="og:url" content="<?php 
        if (isset($manga) && is_array($manga) && !empty($manga['manga_id'])) {
            if (!empty($chapterId) && isset($chapterData) && is_array($chapterData)) {
                echo 'https://' . $_SERVER['HTTP_HOST'] . '/chapter/' . $chapterId;
            } else {
                echo 'https://' . $_SERVER['HTTP_HOST'] . '/manga.php?id=' . $manga['manga_id'];
            }
        } else {
            echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }
    ?>">
    <meta property="og:image" content="<?php 
        echo (isset($manga) && !empty($manga['cover'])) ? htmlspecialchars(COVER_BASE_URL . "/$mangaId/" . $manga['cover'] . ".256.jpg") : 'https://' . $_SERVER['HTTP_HOST'] . '/public/images/logo.png';
    ?>">
    <meta property="og:site_name" content="<?php echo SITE_NAME; ?>">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php 
        if (isset($manga) && is_array($manga) && !empty($manga['title'])) {
            $mangaTitle = htmlspecialchars($manga['title']);
            if (!empty($chapterId) && isset($chapterData) && is_array($chapterData)) {
                $chapterIndex = array_search($chapterId, array_column($chapters, 'id'));
                if ($chapterIndex !== false) {
                    $chapterNumber = htmlspecialchars($chapters[$chapterIndex]['chapter']);
                    $chapterTitle = htmlspecialchars($chapters[$chapterIndex]['title'] ?? 'Chương ' . $chapterNumber);
                    echo "$mangaTitle - $chapterTitle - Đọc Truyện Tranh Online - " . SITE_NAME;
                } else {
                    echo "$mangaTitle - Đọc Truyện Tranh Online - " . SITE_NAME;
                }
            } else {
                echo "$mangaTitle - Đọc Truyện Tranh Online - " . SITE_NAME;
            }
        } else {
            echo "Không tìm thấy - Đọc Truyện Tranh Online - " . SITE_NAME;
        }
    ?>">
    <meta name="twitter:description" content="<?php 
        if (isset($manga) && is_array($manga) && !empty($manga['title'])) {
            $mangaTitle = htmlspecialchars($manga['title']);
            if (!empty($chapterId) && isset($chapterData) && is_array($chapterData)) {
                $chapterIndex = array_search($chapterId, array_column($chapters, 'id'));
                if ($chapterIndex !== false) {
                    $chapterNumber = htmlspecialchars($chapters[$chapterIndex]['chapter']);
                    $chapterTitle = htmlspecialchars($chapters[$chapterIndex]['title'] ?? 'Chương ' . $chapterNumber);
                    $description = "Đọc $mangaTitle $chapterTitle online, cập nhật chương mới nhất tại " . SITE_NAME . ". Khám phá truyện tranh hấp dẫn!";
                    echo substr($description, 0, 160);
                } else {
                    $description = isset($manga['description']) && is_array($manga['description']) && isset($manga['description']['content']) ? htmlspecialchars(strip_tags($manga['description']['content'])) : (is_string($manga['description']) ? htmlspecialchars(strip_tags($manga['description'])) : '');
                    $description = substr($description, 0, 100) . (strlen($description) > 100 ? '...' : '');
                    echo "Đọc truyện tranh $mangaTitle online, cập nhật chương mới nhất. $description";
                }
            } else {
                $description = isset($manga['description']) && is_array($manga['description']) && isset($manga['description']['content']) ? htmlspecialchars(strip_tags($manga['description']['content'])) : (is_string($manga['description']) ? htmlspecialchars(strip_tags($manga['description'])) : '');
                $description = substr($description, 0, 100) . (strlen($description) > 100 ? '...' : '');
                echo "Đọc truyện tranh $mangaTitle online, cập nhật chương mới nhất. $description";
            }
        } else {
            echo "Không tìm thấy truyện. Khám phá bộ sưu tập truyện tranh đa dạng, cập nhật hàng ngày tại " . SITE_NAME . ".";
        }
    ?>">
    <meta name="twitter:image" content="<?php 
        echo (isset($manga) && !empty($manga['cover'])) ? htmlspecialchars(COVER_BASE_URL . "/$mangaId/" . $manga['cover'] . ".256.jpg") : 'https://' . $_SERVER['HTTP_HOST'] . '/public/images/logo.png';
    ?>">
    <?php if (isset($manga) && is_array($manga) && !empty($manga['title']) && !empty($chapterId) && isset($chapterData) && is_array($chapterData)): ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "ComicIssue",
        "name": "<?php 
            $mangaTitle = htmlspecialchars($manga['title']);
            $chapterIndex = array_search($chapterId, array_column($chapters, 'id'));
            if ($chapterIndex !== false) {
                $chapterNumber = htmlspecialchars($chapters[$chapterIndex]['chapter']);
                $chapterTitle = htmlspecialchars($chapters[$chapterIndex]['title'] ?? 'Chương ' . $chapterNumber);
                echo "$mangaTitle - $chapterTitle";
            } else {
                echo "$mangaTitle";
            }
        ?>",
        "issueNumber": "<?php 
            $chapterIndex = array_search($chapterId, array_column($chapters, 'id'));
            echo $chapterIndex !== false ? htmlspecialchars($chapters[$chapterIndex]['chapter']) : '';
        ?>",
        "partOfSeries": {
            "@type": "ComicSeries",
            "name": "<?php echo htmlspecialchars($manga['title']); ?>",
            "url": "<?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/manga.php?id=' . $manga['manga_id']; ?>"
        },
        "url": "<?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/chapter/' . $chapterId; ?>",
        "image": "<?php echo htmlspecialchars(COVER_BASE_URL . "/$mangaId/" . $manga['cover'] . ".256.jpg" ?? ''); ?>",
        "genre": <?php 
            $tags = [];
            if (isset($manga['tags']) && is_array($manga['tags'])) {
                $tags = array_map(function($tag) {
                    return $tag['attributes']['name']['en'] ?? $tag['attributes']['name']['vi'] ?? 'Unknown';
                }, $manga['tags']);
            }
            echo json_encode($tags);
        ?>,
        "author": {
            "@type": "Person",
            "name": "<?php 
                $author = 'Unknown';
                if (isset($manga['author']) && is_array($manga['author']) && !empty($manga['author'])) {
                    $author = $manga['author'][0]['name'] ?? 'Unknown';
                }
                echo htmlspecialchars($author);
            ?>"
        },
        "inLanguage": "vi",
        "publisher": {
            "@type": "Organization",
            "name": "<?php echo SITE_NAME; ?>",
            "logo": {
                "@type": "ImageObject",
                "url": "https://<?php echo $_SERVER['HTTP_HOST']; ?>/public/images/logo.png"
            }
        }
    }
    </script>
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <link href="/css/manga.css" rel="stylesheet">
</head>
<body class="bg-white">
    <?php include 'includes/navbar.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <main id="main-content" class="container mx-auto p-4 pt-0">
        <?php if (isset($notFound) && $notFound): ?>
            <div class="relative h-72 overflow-hidden" style="background-image: url('<?php echo isset($manga) ? COVER_BASE_URL . "/$mangaId/" . $manga['cover'] . ".256.jpg" : '/public/images/loading.png'; ?>'); background-size: cover; background-position: center 25%;">
                <div class="absolute inset-0 bg-black bg-opacity-75"></div>
            </div>
            <div class="text-center mt-8">
                <img src="/public/images/loading.png" alt="Không tìm thấy" class="w-32 mx-auto rounded-md shadow-md">
                <h1 class="text-2xl font-bold mt-4 text-gray-900 dark:text-gray-100">Truyện bạn đang tìm không tồn tại!</h1>
                <a href="index.php" class="mt-4 inline-block bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">Quay lại trang chủ</a>
            </div>
        <?php elseif (!empty($chapterId) && $chapterData): ?>
            <h1 class="text-2xl font-bold mb-4 text-gray-900 dark:text-gray-100">
                Đọc truyện: <?php echo htmlspecialchars($manga['title']); ?> - Chương 
                <?php 
                    $chapterIndex = array_search($chapterId, array_column($chapters, 'id'));
                    echo $chapterIndex !== false ? htmlspecialchars($chapters[$chapterIndex]['chapter']) : 'Unknown';
                ?>
            </h1>
            <div class="space-y-4">
                <?php foreach ($chapterData['pages'] as $index => $page): ?>
                    <img 
                        src="<?php echo htmlspecialchars($page); ?>" 
                        alt="Trang <?php echo $index + 1; ?>" 
                        class="h-auto w-auto mx-auto !max-h-screen"
                        loading="lazy"
                        onerror="this.onerror=null; this.src='/public/images/loading.png';"
                    >
                <?php endforeach; ?>
            </div>
            <div class="mt-4 flex flex-col sm:flex-row justify-between gap-4">
                <a href="manga.php?id=<?php echo $mangaId; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors text-center">Quay lại danh sách chương</a>
                <a href="index.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors text-center">Quay lại trang chủ</a>
            </div>
        <?php else: ?>
            <div class="relative h-72 md:h-72 sm:h-56 xs:h-52 overflow-hidden banner" style="background-image: url('<?php echo isset($manga) ? COVER_BASE_URL . "/$mangaId/" . $manga['cover'] . ".256.jpg" : '/public/images/loading.png'; ?>'); background-size: cover; background-position: center 25%;">
                <div class="absolute inset-0 bg-black bg-opacity-75"></div>
                <div class="absolute inset-0 flex flex-row items-center p-5 sm:p-3 text-white banner-content">
                    <img src="<?php echo COVER_BASE_URL . '/' . htmlspecialchars($mangaId) . '/' . htmlspecialchars($manga['cover']) . '.256.jpg'; ?>" 
                         alt="Ảnh bìa <?php echo htmlspecialchars($manga['title']); ?>" 
                         class="w-48 md:w-40 sm:w-24 xs:w-20 h-auto rounded-lg shadow-lg relative manga-cover"
                         onerror="this.src='/public/images/loading.png'">
                    <div class="flex-1 pl-5 sm:pl-3 flex flex-col justify-center banner-text">
                        <div>
                            <h1 class="text-2xl font-semibold leading-snug"><?php echo htmlspecialchars($manga['title']); ?></h1>
                            <p class="text-base text-gray-300 mt-1 alt-title"><?php echo htmlspecialchars($manga['altTitle'] ?? ''); ?></p>
                            <p class="text-sm text-gray-200 mt-2 italic line-clamp-2 description">
                                <?php echo htmlspecialchars(substr($manga['description']['content'], 0, 120)); ?>...
                            </p>

                            <div class="mt-3 flex gap-2 flex-wrap">
                                <?php echo renderStatusChip($manga['status']); ?>
                                <?php echo renderContentRatingChip($manga['contentRating']); ?>

                                <!-- Hiển thị views -->
                                <span class="flex items-center gap-1 text-sm text-gray-200 dark:text-gray-300">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <?php echo number_format($manga['stats']['views'], 0, '.', ','); ?>
                                </span>

                                <!-- Hiển thị follows -->
                                <span class="flex items-center gap-1 text-sm text-gray-200 dark:text-gray-300">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                    </svg>
                                    <?php echo number_format($manga['stats']['follows'], 0, '.', ','); ?>
                                </span>

                                <!-- Hiển thị comments -->
                                <?php if ($manga['stats']['comments']): ?>
                                    <span class="flex items-center gap-1 text-sm text-gray-200 dark:text-gray-300">
                                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8M8 14h4m6-8H6a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V8a2 2 0 00-2-2z"/>
                                        </svg>
                                        <?php echo number_format($manga['stats']['comments'], 0, '.', ','); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mt-4 flex gap-2 action-buttons">
                            <?php if (isset($_SESSION['user'])): ?>
                                <button id="follow-btn-desktop" 
                                        class="flex items-center gap-1 px-3 py-1.5 <?php echo $isFollowing ? 'bg-red-600 hover:bg-red-700' : 'bg-gray-800 dark:bg-gray-700 hover:bg-gray-700 dark:hover:bg-gray-600'; ?> text-white rounded-md text-sm transition-colors"
                                        data-manga-id="<?php echo $mangaId; ?>">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <?php echo $isFollowing ? 'Hủy theo dõi' : 'Theo dõi'; ?>
                                </button>
                            <?php else: ?>
                                <button id="follow-btn-desktop" 
                                        class="flex items-center gap-1 px-3 py-1.5 bg-gray-800 dark:bg-gray-700 hover:bg-gray-700 dark:hover:bg-gray-600 text-white rounded-md text-sm transition-colors"
                                        onclick="alert('Vui lòng đăng nhập để theo dõi truyện!'); window.location.href='/src/auth/login.php';">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Theo dõi
                                </button>
                            <?php endif; ?>

                            <a href="/chapter/<?php echo $chapters[0]['id'] ?? ''; ?>" 
                               class="flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm transition-colors">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                Đọc ngay
                            </a>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2 genre-tags-desktop">
                            <?php foreach ($manga['tags'] as $tag): ?>
                                <a href="index.php?tag=<?php echo htmlspecialchars($tag['id']); ?>" 
                                   class="inline-flex items-center px-3 py-1 bg-white text-blue-900 rounded text-xs font-semibold uppercase hover:bg-blue-100 hover:-translate-y-0.5 transition-all shadow genre-tag">
                                    <?php echo htmlspecialchars($tag['attributes']['name']['en'] ?? $tag['attributes']['name']['vi'] ?? 'Unknown'); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-3 text-sm text-gray-200 sub-info">
                            <span>
                                Tác giả: 
                                <?php foreach ($manga['author'] as $i => $author): ?>
                                    <a href="index.php?author=<?php echo htmlspecialchars($author['name']); ?>" class="text-blue-400 hover:underline"><?php echo htmlspecialchars($author['name']); ?><?php echo $i < count($manga['author']) - 1 ? ', ' : ''; ?></a>
                                <?php endforeach; ?>
                            </span>
                            <span class="before:content-['|'] before:mx-2">
                                Họa sĩ: 
                                <?php foreach ($manga['artist'] as $i => $artist): ?>
                                    <a href="index.php?artist=<?php echo htmlspecialchars($artist['name']); ?>" class="text-blue-400 hover:underline"><?php echo htmlspecialchars($artist['name']); ?><?php echo $i < count($manga['artist']) - 1 ? ', ' : ''; ?></a>
                                <?php endforeach; ?>
                            </span>
                            <span class="before:content-['|'] before:mx-2">
                                Nguồn: 
                                <a href="https://mangadex.org/title/<?php echo $mangaId; ?>" target="_blank" class="text-blue-400 hover:underline">MangaDex</a>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-4 flex flex-col gap-3 bg-gray-50 dark:bg-gray-800 p-4 rounded-lg shadow sm:hidden extra-content-mobile">
                <div class="flex gap-2 justify-center">
                    <?php if (isset($_SESSION['user'])): ?>
                        <button id="follow-btn-mobile" 
                                class="flex items-center gap-1 px-3 py-1.5 <?php echo $isFollowing ? 'bg-red-600 hover:bg-red-700' : 'bg-gray-800 dark:bg-gray-700 hover:bg-gray-700 dark:hover:bg-gray-600'; ?> text-white rounded-md text-sm transition-colors"
                                data-manga-id="<?php echo $mangaId; ?>">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <?php echo $isFollowing ? 'Hủy theo dõi' : 'Theo dõi'; ?>
                        </button>
                    <?php else: ?>
                        <button id="follow-btn-mobile" 
                                class="flex items-center gap-1 px-3 py-1.5 bg-gray-800 dark:bg-gray-700 hover:bg-gray-700 dark:hover:bg-gray-600 text-white rounded-md text-sm transition-colors"
                                onclick="alert('Vui lòng đăng nhập để theo dõi truyện!'); window.location.href='/src/auth/login.php';">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Theo dõi
                        </button>
                    <?php endif; ?>
                    <a href="/chapter/<?php echo $chapters[0]['id'] ?? ''; ?>" 
                       class="flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm transition-colors">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        Đọc ngay
                    </a>
                </div>
                <div class="flex flex-wrap gap-1.5 genre-tags-mobile">
                    <?php foreach ($manga['tags'] as $tag): ?>
                        <a href="index.php?tag=<?php echo htmlspecialchars($tag['id']); ?>" 
                           class="inline-flex items-center px-2.5 py-0.5 bg-white text-blue-900 rounded text-xs font-semibold uppercase hover:bg-blue-100 hover:-translate-y-0.5 transition-all shadow">
                            <?php echo htmlspecialchars($tag['attributes']['name']['en'] ?? $tag['attributes']['name']['vi'] ?? 'Unknown'); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="flex flex-wrap gap-2 text-xs text-gray-600 dark:text-gray-400 sub-info-mobile">
                    <span>
                        Tác giả: 
                        <?php foreach ($manga['author'] as $i => $author): ?>
                            <a href="index.php?author=<?php echo htmlspecialchars($author['name']); ?>" class="text-blue-600 dark:text-blue-400 hover:underline"><?php echo htmlspecialchars($author['name']); ?><?php echo $i < count($manga['author']) - 1 ? ', ' : ''; ?></a>
                        <?php endforeach; ?>
                    </span>
                    <span class="before:content-['|'] before:mx-2">
                        Họa sĩ: 
                        <?php foreach ($manga['artist'] as $i => $artist): ?>
                            <a href="index.php?artist=<?php echo htmlspecialchars($artist['name']); ?>" class="text-blue-600 dark:text-blue-400 hover:underline"><?php echo htmlspecialchars($artist['name']); ?><?php echo $i < count($manga['artist']) - 1 ? ', ' : ''; ?></a>
                        <?php endforeach; ?>
                    </span>
                    <span class="before:content-['|'] before:mx-2">
                        Nguồn: 
                        <a href="https://mangadex.org/title/<?php echo $mangaId; ?>" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">MangaDex</a>
                    </span>
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-6 content-wrapper">
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Mô tả</h2>
                    <div class="mt-4 text-sm text-gray-700 dark:text-gray-300 text-justify relative">
                        <div id="description-content">
                            <?php echo nl2br(htmlspecialchars($manga['description']['content'])); ?>
                        </div>
                        <button id="translate-btn" class="mt-2 inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white font-bold rounded-lg text-sm shadow-md hover:bg-blue-700 hover:shadow-lg transition-all">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.5 8l-3-3m0 0l3-3m-3 3H3m12 6H3m12-2v2"/>
                            </svg>
                            Dịch sang tiếng Việt
                        </button>
                        <div id="loading-indicator" class="hidden absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-800 bg-opacity-75">
                            <svg class="w-6 h-6 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danh sách chương và Sidebar truyện đề xuất -->
            <div class="mt-8 flex flex-col md:flex-row gap-6">
                <!-- Danh sách chương -->
                <div class="md:w-2/3">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-gray-900">Danh sách chương</h2>
                        <button id="sort-button" class="sort-button p-2 rounded-full hover:bg-gray-200 transition-colors" data-sort-order="desc" title="Sắp xếp mới đến cũ">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M3 12h12M3 20h6" />
                            </svg>
                        </button>
                    </div>
                    <?php if (empty($chapters)): ?>
                        <div class="bg-white rounded-md p-4 text-center shadow-md">
                            <p class="text-gray-700 italic">
                                Không có chương nào được tìm thấy bằng tiếng Việt hoặc tiếng Anh!
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="max-h-96 overflow-y-auto bg-white rounded-lg shadow-md">
                            <ul id="chapter-list" class="divide-y divide-gray-200">
                                <?php foreach ($chapters as $chapter): ?>
                                    <li class="chapter-item p-4 hover:bg-gray-50 transition-colors" data-chapter="<?php echo htmlspecialchars($chapter['chapter']); ?>">
                                        <a href="/chapter/<?php echo $chapter['id']; ?>" 
                                           class="flex flex-col">
                                            <div class="text-gray-900 font-medium">
                                                Chương <?php echo htmlspecialchars($chapter['chapter']); ?>
                                                <?php if ($chapter['title']) echo " - " . htmlspecialchars($chapter['title']); ?>
                                                <span class="text-gray-500 text-xs ml-2">[<?php echo htmlspecialchars($chapter['translatedLanguage']); ?>]</span>
                                            </div>
                                            <div class="text-gray-500 text-sm mt-1">
                                                <?php echo formatTimeToNow($chapter['updatedAt']); ?>
                                                <?php if (!empty($chapter['group'])): ?>
                                                    - Nhóm dịch: <?php echo htmlspecialchars($chapter['group'][0]['name']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar truyện đề xuất -->
                <div class="md:w-1/3">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Truyện đề xuất</h2>
                    <div class="space-y-4">
                        <?php foreach ($randomManga as $mangaItem): ?>
                            <div class="flex gap-3 bg-white p-3 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                                <a href="<?php echo htmlspecialchars($mangaItem['manga_link']); ?>">
                                    <img src="<?php echo htmlspecialchars($mangaItem['cover_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($mangaItem['title']); ?>" 
                                         class="w-16 h-24 object-cover rounded"
                                         onerror="this.onerror=null; this.src='/public/images/loading.png';">
                                </a>
                                <div class="flex-1">
                                    <a href="<?php echo htmlspecialchars($mangaItem['manga_link']); ?>" 
                                       class="text-gray-900 font-semibold hover:underline line-clamp-2">
                                        <?php echo htmlspecialchars($mangaItem['title']); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Bình luận -->
            <div class="mt-8">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Bình luận</h2>
                <?php if (isset($_SESSION['user'])): ?>
                    <form id="comment-form" class="mb-6">
                        <textarea name="comment_content" id="comment-content" rows="3" 
                                  class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                                  placeholder="Viết bình luận của bạn..."></textarea>
                        <button type="submit" 
                                class="mt-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Gửi bình luận
                        </button>
                    </form>
                <?php else: ?>
                    <p class="text-gray-600 italic mb-4">Vui lòng <a href="/login.php" class="text-blue-600 hover:underline">đăng nhập</a> để bình luận.</p>
                <?php endif; ?>

                <div id="comments-list" class="space-y-4">
                    <?php if (empty($comments)): ?>
                        <p class="text-gray-600 italic">Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="bg-white p-4 rounded-lg shadow-md flex flex-col gap-2">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($comment['username']); ?></span>
                                        <span class="text-gray-500 text-sm"><?php echo formatTimeToNow($comment['created_at']); ?></span>
                                    </div>
                                    <?php if (isset($_SESSION['user']) && $_SESSION['user']['user_id'] == $comment['user_id']): ?>
                                        <button class="delete-comment-btn text-red-600 hover:text-red-700 text-sm" 
                                                data-comment-id="<?php echo $comment['id']; ?>">
                                            Xóa
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    <script src="/js/main.js"></script>
    <script src="/js/search.js"></script>
    <script>
    // Hàm hiển thị thông báo
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type === 'error' ? 'error' : ''}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.classList.add('show'), 10);
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Dịch mô tả
    document.getElementById('translate-btn').addEventListener('click', function() {
        const contentDiv = document.getElementById('description-content');
        const originalContent = <?php echo json_encode($manga['description']['content'] ?? ''); ?>;
        const button = this;
        const loadingIndicator = document.getElementById('loading-indicator');
        
        if (button.dataset.translated === 'true') {
            contentDiv.innerHTML = <?php echo json_encode(nl2br(htmlspecialchars($manga['description']['content'] ?? ''))); ?>;
            button.innerHTML = `
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.5 8l-3-3m0 0l3-3m-3 3H3m12 6H3m12-2v2"/>
                </svg>
                Dịch sang tiếng Việt
            `;
            button.dataset.translated = 'false';
            return;
        }

        loadingIndicator.classList.remove('hidden');
        fetch('/manga.php?id=<?php echo $mangaId; ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'translate=1&content=' + encodeURIComponent(originalContent)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                contentDiv.innerHTML = data.translated.replace(/\n/g, '<br>');
                button.innerHTML = `
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>
                    </svg>
                    Xem bản gốc
                `;
                button.dataset.translated = 'true';
            } else {
                showNotification(data.message || 'Không thể dịch nội dung!', 'error');
            }
        })
        .catch(error => {
            console.error('Lỗi dịch thuật:', error);
            showNotification('Đã có lỗi xảy ra khi dịch!', 'error');
        })
        .finally(() => loadingIndicator.classList.add('hidden'));
    });

    // Theo dõi/Hủy theo dõi
    document.querySelectorAll('[id^="follow-btn"]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const mangaId = this.getAttribute('data-manga-id');
            
            fetch('/manga.php?id=' + mangaId, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'toggle_follow=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelectorAll('[id^="follow-btn"]').forEach(btn => {
                        if (data.following) {
                            btn.classList.remove('bg-gray-800', 'dark:bg-gray-700', 'hover:bg-gray-700', 'dark:hover:bg-gray-600');
                            btn.classList.add('bg-red-600', 'hover:bg-red-700');
                            btn.innerHTML = `
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Hủy theo dõi
                            `;
                        } else {
                            btn.classList.remove('bg-red-600', 'hover:bg-red-700');
                            btn.classList.add('bg-gray-800', 'dark:bg-gray-700', 'hover:bg-gray-700', 'dark:hover:bg-gray-600');
                            btn.innerHTML = `
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Theo dõi
                            `;
                        }
                    });
                    showNotification(data.message, data.type);
                } else {
                    showNotification(data.message || 'Lỗi thao tác!', 'error');
                }
            })
            .catch(error => {
                console.error('Lỗi:', error);
                showNotification('Lỗi hệ thống!', 'error');
            });
        });
    });

    // Xử lý sắp xếp danh sách chương
    document.getElementById('sort-button').addEventListener('click', function() {
        const sortButton = this;
        const chapterList = document.getElementById('chapter-list');
        const chapters = Array.from(chapterList.getElementsByTagName('li'));
        let sortOrder = sortButton.getAttribute('data-sort-order');
        sortOrder = sortOrder === 'desc' ? 'asc' : 'desc';
        sortButton.setAttribute('data-sort-order', sortOrder);
        const svgPath = sortButton.querySelector('svg path');
        if (sortOrder === 'asc') {
            svgPath.setAttribute('d', 'M3 4h6M3 12h12M3 20h18');
            sortButton.setAttribute('title', 'Sắp xếp mới đến cũ');
        } else {
            svgPath.setAttribute('d', 'M3 4h18M3 12h12M3 20h6');
            sortButton.setAttribute('title', 'Sắp xếp cũ đến mới');
        }
        chapters.sort((a, b) => {
            const chapterA = parseFloat(a.getAttribute('data-chapter'));
            const chapterB = parseFloat(b.getAttribute('data-chapter'));
            return sortOrder === 'asc' ? chapterA - chapterB : chapterB - chapterA;
        });

        chapterList.innerHTML = '';
        chapters.forEach(chapter => chapterList.appendChild(chapter));
    });

    // Xử lý gửi bình luận
    document.getElementById('comment-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const content = document.getElementById('comment-content').value.trim();
        if (!content) {
            showNotification('Nội dung bình luận không được để trống!', 'error');
            return;
        }

        fetch('/manga.php?id=<?php echo $mangaId; ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'add_comment=1&comment_content=' + encodeURIComponent(content)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); 
            } else {
                showNotification(data.error || 'Không thể gửi bình luận!', 'error');
            }
        })
        .catch(error => {
            console.error('Lỗi gửi bình luận:', error);
            showNotification('Đã có lỗi xảy ra khi gửi bình luận!', 'error');
        });
    });

    // Xử lý xóa bình luận
    document.querySelectorAll('.delete-comment-btn').forEach(button => {
        button.addEventListener('click', function() {
            if (!confirm('Bạn có chắc muốn xóa bình luận này?')) return;

            const commentId = this.getAttribute('data-comment-id');
            fetch('/manga.php?id=<?php echo $mangaId; ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'delete_comment=1&comment_id=' + commentId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showNotification(data.error || 'Không thể xóa bình luận!', 'error');
                }
            })
            .catch(error => {
                console.error('Lỗi xóa bình luận:', error);
                showNotification('Đã có lỗi xảy ra khi xóa bình luận!', 'error');
            });
        });
    });

    function toggleTheme() {
        const html = document.documentElement;
        const metaTheme = document.querySelector('meta[name="theme-color"]');
        if (html.classList.contains('dark')) {
            html.classList.remove('dark');
            localStorage.setItem('theme', 'light');
            metaTheme.setAttribute('content', '#ffffff');
        } else {
            html.classList.add('dark');
            localStorage.setItem('theme', 'dark');
            metaTheme.setAttribute('content', '#1f2937');
        }
    }

    if (localStorage.getItem('theme') === 'dark' || 
        (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
        document.querySelector('meta[name="theme-color"]').setAttribute('content', '#1f2937');
    }
    </script>
</body>
</html>