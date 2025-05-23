-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 23, 2025 at 10:12 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `truyen0hay`
--

-- --------------------------------------------------------

--
-- Table structure for table `chapters`
--

CREATE TABLE `chapters` (
  `id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `manga_id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `chapter_number` float NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pages` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chapters`
--

INSERT INTO `chapters` (`id`, `manga_id`, `chapter_number`, `title`, `pages`, `created_at`, `updated_at`) VALUES
('0f03666a7c7682c407eac3a8ca3b06c1be2a', 'bcb384d7da5d85bb1d50fc1332bcc9cc1362', 1, 'Demo', '[\"https:\\/\\/i.imgur.com\\/Gj0Fzio.png\",\"https:\\/\\/i.imgur.com\\/suxbrhu.png\",\"https:\\/\\/i.imgur.com\\/HkhceT2.png\",\"https:\\/\\/i.imgur.com\\/0hV6Jz6.png\",\"https:\\/\\/i.imgur.com\\/Y7kYsPG.jpeg\",\"https:\\/\\/i.imgur.com\\/pwPz5Qx.jpeg\"]', '2025-04-08 09:09:29', '2025-04-08 09:09:29'),
('61dd3cabb893aaf69c0d641702b2545bdb2c', 'bcb384d7da5d85bb1d50fc1332bcc9cc1362', 2, 'Demo 2', '[\"https:\\/\\/i.imgur.com\\/ZQPkPJY.jpeg\",\"https:\\/\\/i.imgur.com\\/4XLDGvF.png\",\"https:\\/\\/i.imgur.com\\/BhB1BHh.png\"]', '2025-04-08 09:17:39', '2025-04-08 09:17:39');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int NOT NULL,
  `manga_id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `manga_id`, `user_id`, `content`, `created_at`) VALUES
(1, '1f4b34a3-ee7c-4855-8bd9-996b31b8142b', 1, 'vip', '2025-04-07 11:42:38');

-- --------------------------------------------------------

--
-- Table structure for table `manga`
--

CREATE TABLE `manga` (
  `manga_id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alt_title` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `cover` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('ongoing','completed','hiatus','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'ongoing',
  `content_rating` enum('safe','suggestive','erotica','pornographic') COLLATE utf8mb4_unicode_ci DEFAULT 'safe',
  `cover_url` text COLLATE utf8mb4_unicode_ci,
  `manga_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_manual` tinyint(1) DEFAULT '0',
  `views` int DEFAULT '0',
  `comments_count` int DEFAULT '0',
  `follows_count` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `manga`
--

INSERT INTO `manga` (`manga_id`, `title`, `alt_title`, `description`, `cover`, `status`, `content_rating`, `cover_url`, `manga_link`, `is_manual`, `views`, `comments_count`, `follows_count`) VALUES
('04c892b3-a202-46f5-ad11-0d433e712ab8', 'Ba Chị Em Nhà Mikadono Dễ Đối Phó Thật Đấy', NULL, NULL, '102798c4-34d5-4a48-84cc-d3867d0ca134.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=04c892b3-a202-46f5-ad11-0d433e712ab8', 0, 443, 0, 0),
('05e36dbe-ddf2-45e4-8d12-1f45d8397817', 'Minh Nhật Phương Chu: 123 Rhodes Island!?', NULL, NULL, '6939afe3-7c1d-45d6-9de2-d22d03fe7a48.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=05e36dbe-ddf2-45e4-8d12-1f45d8397817', 0, 1677, 0, 0),
('1090afe3-3b91-4325-a9b4-d92875aa815e', 'Blue Giant', NULL, NULL, '15261fed-7894-4fb7-b748-d3da40705bd7.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=1090afe3-3b91-4325-a9b4-d92875aa815e', 0, 0, 0, 0),
('1f4b34a3-ee7c-4855-8bd9-996b31b8142b', 'Kawaibara-senpai wa Kawaii (Saotome-kun) ga Suki!', NULL, NULL, 'a837901a-cef0-4a0a-aebf-ab76ea17ca82.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=1f4b34a3-ee7c-4855-8bd9-996b31b8142b', 0, 0, 0, 0),
('24c8e2f3-f515-4e58-bfdd-c423ab00499a', 'Study Group', NULL, NULL, '133f7d99-a535-43a3-b125-1783166766e6.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=24c8e2f3-f515-4e58-bfdd-c423ab00499a', 0, 0, 0, 0),
('25ce48c6-b08e-4239-9c33-f6affa3b83da', '2D Comic Magazine Succubus Yuri H', NULL, NULL, '1b3d2706-9914-4180-8b70-b5f3e81ffbaf.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=25ce48c6-b08e-4239-9c33-f6affa3b83da', 0, 0, 0, 0),
('26854d1a-dfd0-4e5c-b6d1-ab291035b8cc', '10-Nen Buri ni Saikai shita Kusogaki wa Seijun Bishoujo JK ni Seichou shiteita', NULL, NULL, '6df71e63-2c8e-4477-9b2e-46f750405ccf.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=26854d1a-dfd0-4e5c-b6d1-ab291035b8cc', 0, 308, 0, 0),
('28f0d967-4110-4a8c-8426-2aeea4489111', 'Designated Bully', NULL, NULL, '5f81ebbe-14f2-41a1-b0ab-50882c5df23e.png', 'ongoing', 'safe', NULL, '/manga.php?id=28f0d967-4110-4a8c-8426-2aeea4489111', 0, 30, 0, 0),
('2df0f28e-297a-49a2-888a-e312d570eeb0', 'Tang Phục Của Con Mèo', NULL, NULL, '45c9e0c3-1aa3-4019-9f6e-3b1aa1ea31bf.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=2df0f28e-297a-49a2-888a-e312d570eeb0', 0, 180, 0, 0),
('30fb8b10-e22a-44cb-9311-5893d77502d7', 'Hoa Hồng Trắng Của Frankenstein', NULL, NULL, '8183dc76-c5d1-476b-9d03-aa993870943c.png', 'ongoing', 'safe', NULL, '/manga.php?id=30fb8b10-e22a-44cb-9311-5893d77502d7', 0, 963, 0, 1),
('3267e61a-8dc7-4d27-b8f7-26d810c08a1c', 'Hanlim Gym', NULL, NULL, 'dabcef25-7ad1-40bf-8003-03f72acd9359.png', 'ongoing', 'safe', NULL, '/manga.php?id=3267e61a-8dc7-4d27-b8f7-26d810c08a1c', 0, 0, 0, 0),
('376c1a15-bddc-4d12-b017-b3d5f76766c6', 'Kareshi ga Iru no ni', NULL, NULL, 'be760335-d769-4463-afd9-d4ae545273cb.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=376c1a15-bddc-4d12-b017-b3d5f76766c6', 0, 0, 0, 0),
('39a11ada-e29e-49dc-b5f6-17e8d9c0d280', 'White Night Bitter Porn', NULL, NULL, '36f9f365-7e01-41ab-8f41-48391c864428.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=39a11ada-e29e-49dc-b5f6-17e8d9c0d280', 0, 0, 0, 0),
('4c13b104-48a6-47be-b059-474216cb234d', 'Aimai Diary', NULL, NULL, '50bcc6e2-b48f-4786-bfe3-735a0054289b.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=4c13b104-48a6-47be-b059-474216cb234d', 0, 0, 0, 0),
('51c202df-34dc-4595-9287-f80d400f682b', 'Và rồi, ả đã trở thành Mina.', NULL, NULL, '77ab287f-1ebc-4372-a519-3027470f6c72.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=51c202df-34dc-4595-9287-f80d400f682b', 0, 0, 0, 0),
('54eafd69-f8f1-4161-bb37-86ee5ec32625', 'Fair Trade Commission', NULL, NULL, 'e7608ed2-ede4-415e-a25f-02da6e0ad3c2.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=54eafd69-f8f1-4161-bb37-86ee5ec32625', 0, 0, 0, 0),
('57fe3f00-8626-462b-8f75-fce0e6faa6be', 'Waka-chan wa Kyou mo Azatoi', NULL, NULL, 'f79728c7-680a-4306-92f6-f43bd9bfa5a1.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=57fe3f00-8626-462b-8f75-fce0e6faa6be', 0, 0, 0, 0),
('5bf1a8cf-e50b-416c-8170-23b67a3a63e5', 'Gokumon Nadeshiko Koko ni Ari', NULL, NULL, '7740cb85-a3b8-4156-ae25-1123758700e1.png', 'ongoing', 'safe', NULL, '/manga.php?id=5bf1a8cf-e50b-416c-8170-23b67a3a63e5', 0, 114, 0, 0),
('603b9d8f-97b6-4f82-b7d6-1de7e356e869', 'Kaijin Fugeki', NULL, NULL, 'c28d4f9c-1929-45a6-953d-079de5c13b22.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=603b9d8f-97b6-4f82-b7d6-1de7e356e869', 0, 0, 0, 0),
('60e75b46306ccd1a35528885bc8bb1ead05f', '2', 'hiệp sĩ online', '', 'Fe6EQSk.png', 'ongoing', 'safe', 'https://i.imgur.com/Fe6EQSk.png', '/truyen.php?id=60e75b46306ccd1a35528885bc8bb1ead05f', 1, 95, 0, 0),
('64f2477e-432f-434f-b30e-1bcc9baf2a78', 'What Do I Do Now?', NULL, NULL, '12fdb85c-9406-465f-b653-cb985d46aa9f.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=64f2477e-432f-434f-b30e-1bcc9baf2a78', 0, 0, 0, 0),
('688eee00-d587-4998-a999-652767aac846', 'Convenient Semi-Friend', NULL, NULL, '3c58affd-ca15-4b2f-b5d1-5798a0a251a8.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=688eee00-d587-4998-a999-652767aac846', 0, 1502, 0, 0),
('6b968611-ed92-421d-9634-9d66ba1b1668', 'Family, Now and Forever', NULL, NULL, 'b76abdd5-0057-40f9-ac0c-18a992bac0d3.png', 'ongoing', 'safe', NULL, '/manga.php?id=6b968611-ed92-421d-9634-9d66ba1b1668', 0, 0, 0, 0),
('6ccbc691-e9a5-4f7e-88b0-6ded05fd7a8e', 'Jizaimen ~ Gương mặt cô ấy mong muốn ở tôi ~', NULL, NULL, '6f93985a-98fd-4855-9602-f0b7186a2cd8.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=6ccbc691-e9a5-4f7e-88b0-6ded05fd7a8e', 0, 0, 0, 0),
('6f1d7e43-01c1-4fdd-84b0-e24049811cb3', 'Love Gacha!', NULL, NULL, '38a36e27-cfd9-4255-9fdd-42492e134aa1.png', 'ongoing', 'safe', NULL, '/manga.php?id=6f1d7e43-01c1-4fdd-84b0-e24049811cb3', 0, 0, 0, 0),
('718d9146-8c9e-4ff3-9dc4-e4736a1239bb', 'The Warrior Returns', NULL, NULL, '04917b57-eeef-48ae-9147-a7e6b4bae0eb.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=718d9146-8c9e-4ff3-9dc4-e4736a1239bb', 0, 0, 0, 0),
('77462deb-15bf-4957-9109-e12f4e06d0e7', 'Maou to Ryuuou ni Sodaterareta Shounen wa Gakuen Seikatsu wo Musou suru You desu', NULL, NULL, 'dcca2227-fbc6-49f3-bd6d-85b87dbfd0b2.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=77462deb-15bf-4957-9109-e12f4e06d0e7', 0, 0, 0, 0),
('7f30dfc3-0b80-4dcc-a3b9-0cd746fac005', 'Thám Tử Lừng Danh Conan', NULL, NULL, 'b65b80fa-217c-4a71-8e8f-5d647990117a.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=7f30dfc3-0b80-4dcc-a3b9-0cd746fac005', 0, 0, 0, 0),
('80422e14-b9ad-4fda-970f-de370d5fa4e5', 'Made in Abyss', NULL, NULL, 'b7a6b10c-20cf-4c9a-8955-1e79c56ac3fd.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=80422e14-b9ad-4fda-970f-de370d5fa4e5', 0, 0, 0, 0),
('8685b705-d626-44a0-b449-2ccd5ae5959d', 'Unemployed Gye Baeksun', NULL, NULL, '101a4971-8f8d-460c-ad10-7c165a716f60.png', 'ongoing', 'safe', NULL, '/manga.php?id=8685b705-d626-44a0-b449-2ccd5ae5959d', 0, 0, 0, 0),
('879af0bb-ce30-47e4-a74e-cd1ce874c6e3', 'Cuộc Sống Nông Dân Ở Thế Giới Khác', NULL, NULL, 'f698b6a6-13b9-4b2d-ac4f-91ad0e363110.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=879af0bb-ce30-47e4-a74e-cd1ce874c6e3', 0, 462, 0, 0),
('896744c0-6593-4cd4-8cad-83fe8ff2840b', 'Beautiful Days', NULL, NULL, 'f701b0a3-c307-41c7-8345-342691f96077.png', 'ongoing', 'safe', NULL, '/manga.php?id=896744c0-6593-4cd4-8cad-83fe8ff2840b', 0, 0, 0, 0),
('8a1a79e4-7f90-450d-854c-0c780179f47f', 'The Martial God Who Regressed Back to Level 2', NULL, NULL, '1c5a58a5-ad5a-495e-a111-9eff4d4d1781.png', 'ongoing', 'safe', NULL, '/manga.php?id=8a1a79e4-7f90-450d-854c-0c780179f47f', 0, 45, 0, 0),
('8ef11280-30bc-434b-bf61-c61e092905ac', 'Class de 2 Banme ni Kawaii Onna no Ko to Tomodachi ni Natta', NULL, NULL, '5d934665-9655-40a1-ae1f-d1ea2dd6d76f.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=8ef11280-30bc-434b-bf61-c61e092905ac', 0, 0, 0, 0),
('955845a3-d8f1-4166-83d5-b17f32fdf20c', 'Her Rejuvenation', NULL, NULL, '69a68436-feed-4e8d-bf09-a30e44de8353.png', 'ongoing', 'safe', NULL, '/manga.php?id=955845a3-d8f1-4166-83d5-b17f32fdf20c', 0, 0, 0, 0),
('971fb926-e2c7-40da-be30-59e540aeb013', 'Hima-Ten!', NULL, NULL, '2f082896-9df8-4143-91b5-0f299011b5e4.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=971fb926-e2c7-40da-be30-59e540aeb013', 0, 0, 0, 0),
('9ad4148b-8c9e-48d5-89ec-e7b98a660e42', 'Oomuro-ke', NULL, NULL, '57886a93-6c2f-4bbd-b78b-9b32805f9199.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=9ad4148b-8c9e-48d5-89ec-e7b98a660e42', 0, 0, 0, 0),
('a08d63ba-c412-4a7b-94b8-ef65bf2e2a30', 'Crush Của Tôi Là Một Đứa Lẳng Lơ', NULL, NULL, '92632b92-0fd9-4d62-87b4-c9b7fb932032.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=a08d63ba-c412-4a7b-94b8-ef65bf2e2a30', 0, 346, 0, 0),
('a4e2eede-c3b6-42f9-bc63-4ea3cd734181', 'The Merman Trapped in My Lake', NULL, NULL, 'ddc372b5-dbbd-4cf2-9b11-30ff7c53289f.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=a4e2eede-c3b6-42f9-bc63-4ea3cd734181', 0, 0, 0, 0),
('a50aa56e-2655-4404-8232-e4970ee63e27', 'Rokudou no Onna-tachi', NULL, NULL, '902545ba-6463-42a8-afa0-b93db7260ca6.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=a50aa56e-2655-4404-8232-e4970ee63e27', 0, 0, 0, 0),
('acdbf57f-bf54-41b4-8d92-b3f3d14c852e', 'Aishiteru Game wo Owarasetai', NULL, NULL, '2e239897-0808-4d2f-bc05-9e12b36b361f.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=acdbf57f-bf54-41b4-8d92-b3f3d14c852e', 0, 586, 0, 0),
('b093bc50-be43-470c-8594-d79ab9fa5b90', 'Youkai Gakkou no Sensei Hajimemashita!', NULL, NULL, 'afc3681c-cf8d-4c5e-927d-ab3097a9f9de.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=b093bc50-be43-470c-8594-d79ab9fa5b90', 0, 0, 0, 0),
('b0bb2270-16b1-4bde-a174-9518a3a145d3', 'I Have to Sleep With a Stranger?', NULL, NULL, '26566ee6-3a53-4dc7-9465-2f3e095a2bad.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=b0bb2270-16b1-4bde-a174-9518a3a145d3', 0, 0, 0, 0),
('b492ec0e-a7f4-4ddb-82c7-ecd017a32905', 'Ookami-kun wa Osowaretai', NULL, NULL, 'c9f10588-6e0c-4a89-a8a5-d511240ea729.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=b492ec0e-a7f4-4ddb-82c7-ecd017a32905', 0, 0, 0, 0),
('b6597d01-7656-4149-ac73-6f7a018b4b18', 'Tanin ni Naru Kusuri', NULL, NULL, 'f5e0a692-0b1d-47d4-b7e7-727ab84396a2.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=b6597d01-7656-4149-ac73-6f7a018b4b18', 0, 0, 0, 0),
('b6cbea7c-222b-467b-985e-e6d588d7e590', 'The Little Onahole Girl', NULL, NULL, '1cb001b3-d9be-443b-8347-468bebd8dadb.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=b6cbea7c-222b-467b-985e-e6d588d7e590', 0, 0, 0, 0),
('b7239cd1-258e-45ad-99ce-7e1eeb693310', 'Rikai no Aru Karen-chan', NULL, NULL, '8fb4a2e3-63b6-461f-81cb-68c1e988c11f.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=b7239cd1-258e-45ad-99ce-7e1eeb693310', 0, 0, 0, 0),
('b9b2fbc4-e351-406c-a468-799be14033df', 'TenPuru -No One Can Live on Loneliness-', NULL, NULL, '928b0625-659b-4576-ad8b-1f240624f4d4.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=b9b2fbc4-e351-406c-a468-799be14033df', 0, 1070, 0, 0),
('bb4483dc-ee17-45f7-a325-fb5d7f251393', 'Your Everything', NULL, NULL, '65271eb9-fcc9-4571-81dd-4bd05ced2620.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=bb4483dc-ee17-45f7-a325-fb5d7f251393', 0, 0, 0, 0),
('bcb384d7da5d85bb1d50fc1332bcc9cc1362', 'f', 'f', 'f\n{\"author\":[\"Ho\\u00e0ng\"],\"artist\":[\"H\\u1ea3i\"],\"tags\":[\"Comedy\"]}', 'nhNACgu.png', 'ongoing', 'safe', 'https://i.imgur.com/nhNACgu.png', '/truyen.php?id=bcb384d7da5d85bb1d50fc1332bcc9cc1362', 1, 1188, 0, 0),
('bd64d469-5826-4993-a52c-09091c146d8a', 'Onee-chan to, Mama to, Honki Koubi.', NULL, NULL, '46ac151d-8027-4575-b80e-344cb14c0be0.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=bd64d469-5826-4993-a52c-09091c146d8a', 0, 0, 0, 0),
('bf32c5e0-8fc3-4323-a47e-2603265f2d7d', 'Bang Dream - Chisato Shirasagi (Doujinshi)', NULL, NULL, '88cb7d1e-c819-45f2-8153-61218cd773c0.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=bf32c5e0-8fc3-4323-a47e-2603265f2d7d', 0, 0, 0, 0),
('c3295871-b930-4ee3-ac0f-ff7102ecec4e', 'Bé sơ trung Sasha và thằng bạn Otaku cùng lớp', NULL, NULL, 'd8ace3c9-0eaa-45ba-a103-c9bc8d25ba65.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=c3295871-b930-4ee3-ac0f-ff7102ecec4e', 0, 0, 0, 0),
('c351ea58-9e8d-4c84-a715-70d939f8e6f7', 'Lồng Giam Thiếu Nữ', NULL, NULL, '468c0a61-752f-44a9-abb3-b088052276f2.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=c351ea58-9e8d-4c84-a715-70d939f8e6f7', 0, 505, 0, 0),
('c7cc0e57-aa04-42d7-a726-822f66718a35', 'Hai chị em nhà Herami bất ổn thực sự!', NULL, NULL, 'fa225b5d-2c0c-4f60-a04f-c4c168159b47.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=c7cc0e57-aa04-42d7-a726-822f66718a35', 0, 0, 0, 0),
('cad82869-1374-4a14-be36-05489f5e7b76', 'Gal Oyako No Egui Kasegikata', NULL, NULL, '8a65286f-03e9-4524-bc56-e2bae90f584e.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=cad82869-1374-4a14-be36-05489f5e7b76', 0, 0, 0, 0),
('cb98bd40-a903-4416-bdd0-dab93d8f36ad', 'Xem phim \"người lớn\" được không?', NULL, NULL, '4dabe0d6-3e6d-4a9e-912a-a28470b928c7.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=cb98bd40-a903-4416-bdd0-dab93d8f36ad', 0, 0, 0, 0),
('cda88d7a-146c-41d6-8339-7e8e187058bc', 'Trăng Rằm', NULL, NULL, '64d13af7-768b-4db2-a083-63d14363e7d6.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=cda88d7a-146c-41d6-8339-7e8e187058bc', 0, 0, 0, 0),
('cedc7401-8c70-4057-b14a-4ecbbcd73945', 'Cô Dâu Thảo Nguyên', NULL, NULL, '578314af-062f-4c1e-9a9c-e99205a2d5f2.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=cedc7401-8c70-4057-b14a-4ecbbcd73945', 0, 325, 0, 0),
('d37e89cd-b959-440b-b617-0a27873a0557', 'Dưới bóng kỳ vương', NULL, NULL, '724d7dcb-76cf-49ac-b8fc-ba8aef892926.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=d37e89cd-b959-440b-b617-0a27873a0557', 0, 1046, 0, 0),
('d6691b67-b35a-4468-9e85-815c689910df', 'Under Observation: My First Loves and I', NULL, NULL, '919f0903-8b4d-4e94-a56e-16945393299f.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=d6691b67-b35a-4468-9e85-815c689910df', 0, 0, 0, 0),
('dc425848-83d2-4356-94ad-a0ba49e6e54e', 'I Saw the Cool Beauty Famous for Being an Iron Wall Secretly Going Out with Someone', NULL, NULL, '6efedab5-4b70-4643-9ec0-02b67b88c26f.jpg', 'ongoing', 'safe', NULL, 'https://mangadex.org/title/dc425848-83d2-4356-94ad-a0ba49e6e54e', 0, 0, 0, 0),
('dca7181a-b747-49d1-90fc-34802906e465', 'Bé Gyaru Nhỏ Nhắn', NULL, NULL, '75405d71-094e-47d2-a6d8-4422dc582de7.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=dca7181a-b747-49d1-90fc-34802906e465', 0, 0, 0, 0),
('dd13dad6-7cda-46cf-8d4f-13adfc6c37d6', 'Shihai Shoujo Kubaru-chan', NULL, NULL, 'fab03ce6-a206-4dff-8de9-9d5f11ab190c.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=dd13dad6-7cda-46cf-8d4f-13adfc6c37d6', 0, 0, 0, 0),
('deb90feb-ad58-4fb4-a8c4-53da5c5b2901', 'Không Ngờ Bệ Hạ Là Nữ', NULL, NULL, 'fd17e055-249e-4ca2-928b-dbe570350262.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=deb90feb-ad58-4fb4-a8c4-53da5c5b2901', 0, 0, 0, 0),
('df5f8e3d-64a1-4287-8f08-4a732e9cb757', 'Myst, Might, Mayhem', NULL, NULL, '3cd106a7-6354-4832-8c6d-21ed5de163fa.png', 'ongoing', 'safe', NULL, '/manga.php?id=df5f8e3d-64a1-4287-8f08-4a732e9cb757', 0, 0, 0, 0),
('e06e6024-9688-4a38-a8b4-6facd3fe1d80', 'Mankitsu Shitai Jouren-san', NULL, NULL, '7caf6d8b-6e25-40fa-8e07-f1ce67e67996.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=e06e6024-9688-4a38-a8b4-6facd3fe1d80', 0, 0, 0, 0),
('e1e38166-20e4-4468-9370-187f985c550e', 'Nô lệ của đội tinh nhuệ Ma đô', NULL, NULL, 'f21c4038-b859-4f80-8bbd-c14dcd73801e.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=e1e38166-20e4-4468-9370-187f985c550e', 0, 0, 0, 0),
('e305f85f-3b0c-45f3-b1e1-6fb42900681a', 'Killer x Killer: Sát Thủ Tâm Thần x Sát Thủ Háu Ăn', NULL, NULL, 'c4bb879d-3b4d-4030-a500-a90a7a1d5172.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=e305f85f-3b0c-45f3-b1e1-6fb42900681a', 0, 0, 0, 0),
('e44b1517-4508-4335-a494-b5b6a39a35de', 'Chán Chường', NULL, NULL, 'ac5717d5-e214-48fd-8883-04a340d58149.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=e44b1517-4508-4335-a494-b5b6a39a35de', 0, 0, 0, 0),
('e78a489b-6632-4d61-b00b-5206f5b8b22b', 'Tensei Shitara Slime Datta Ken', NULL, NULL, '67de8b2f-c080-4006-91dd-a3b87abdb7fd.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=e78a489b-6632-4d61-b00b-5206f5b8b22b', 0, 0, 0, 0),
('e8d4a5e5-29fb-42b1-90e0-a9a3a91ecfa9', 'History of the Kingdom of the Orcsen: How the Barbarian Orcish Nation Came to Burn Down the Peaceful Elfland', NULL, NULL, '5f49d9c7-69bc-4234-9e66-a2deb0cf0c3c.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=e8d4a5e5-29fb-42b1-90e0-a9a3a91ecfa9', 0, 0, 0, 0),
('f3b73f26-3a8e-4b2a-8b8e-ae67a36e4e12', 'Zange Ana', NULL, NULL, '1b0fe004-3d50-4f45-81c7-317ef30b9158.png', 'ongoing', 'safe', NULL, '/manga.php?id=f3b73f26-3a8e-4b2a-8b8e-ae67a36e4e12', 0, 0, 0, 0),
('f52e33d9-d328-4a9b-8fe2-c01e2bcdda98', 'Prostate Capture Report', NULL, NULL, '095ecd90-552c-4bf2-a848-3cd29cdb8262.jpg', 'ongoing', 'safe', NULL, '/manga.php?id=f52e33d9-d328-4a9b-8fe2-c01e2bcdda98', 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `staff_picks`
--

CREATE TABLE `staff_picks` (
  `id` int NOT NULL,
  `manga_id` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_position` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_picks`
--

INSERT INTO `staff_picks` (`id`, `manga_id`, `order_position`) VALUES
(10, '26854d1a-dfd0-4e5c-b6d1-ab291035b8cc', 1),
(11, 'b093bc50-be43-470c-8594-d79ab9fa5b90', 2),
(12, 'acdbf57f-bf54-41b4-8d92-b3f3d14c852e', 3),
(13, '04c892b3-a202-46f5-ad11-0d433e712ab8', 4),
(14, '8ef11280-30bc-434b-bf61-c61e092905ac', 5),
(16, '688eee00-d587-4998-a999-652767aac846', 7),
(17, '28f0d967-4110-4a8c-8426-2aeea4489111', 8),
(18, 'c7cc0e57-aa04-42d7-a726-822f66718a35', 9);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `reset_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `roles` enum('admin','user') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'user',
  `google_auth_secret` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `score` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `name`, `email`, `reset_token`, `roles`, `google_auth_secret`, `avatar`, `score`, `created_at`) VALUES
(1, 'hoang', '$2y$10$1jZIq.DZXnT20XNwmY.vSuFB/HD7/Rwzunlit595vdvxfYnKsyECu', 'hai', 'hoanghai07@gmail.com', '968f41520296716182a5da7459acc5ecaa530b7c6ebb3f9506876528ec0585edc43f441123c98d27ad56367911ea5d2acce3', 'admin', NULL, NULL, 0, '2025-04-02 11:10:19'),
(2, 'admin', '$2y$10$jFTbh1MhNfJ7Mzftg82tjeQFJcOcMJjOBgaD7O.wa1pfz2m7MRxgu', 'hai', 'hoanghai0707d@gmail.com', NULL, 'admin', NULL, NULL, 0, '2025-04-02 11:12:02'),
(3, '3882e43c3c7e93ea855a4b933ff54500', '$2y$10$N37lf6CQhfv0yH0nArgfXuXe//LBxpY3kv..sRX7rmJPIXusfWn7u', 'user1', 'tophvn17@gmail.com', NULL, 'user', NULL, 'https://lh3.googleusercontent.com/a/ACg8ocJ6wu2LKt7wA1G9HCxP__GLLBt9W3hID8mvIaKUc3nnxodh6gc=s96-c', 0, '2025-04-08 13:18:30'),
(4, 'eafc915d712694e2dffe2fb5b041f723', '$2y$10$MQQLei2ZPvEJP6.BTdnoO.GFPJ8CQ3LNhw904hHpjEj9UiHKP2cyu', 'user2', 'tophcyber@gmail.com', NULL, 'user', NULL, 'https://lh3.googleusercontent.com/a/ACg8ocIuOHcu7VhQZg0KCKcVln66WM1Qy9uE9qZFiYTUwEnNcUNPHQ=s96-c', 0, '2025-04-09 02:26:00'),
(5, 'hoang17', '$2y$10$AOEvVhUf/T8z1JgUM7Cr8Ovsw6238gvI2K4rWq3rytfuXP/JrO8pK', 'hoang', 'hoanghaitoph@gmail.com', NULL, 'admin', NULL, NULL, 0, '2025-05-23 09:43:37');

-- --------------------------------------------------------

--
-- Table structure for table `user_follows`
--

CREATE TABLE `user_follows` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `manga_id` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `followed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_read_chapter` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_read_at` timestamp NULL DEFAULT NULL,
  `cover_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_history`
--

CREATE TABLE `user_history` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `manga_id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `chapter_id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `chapter_number` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chapter_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manga_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_history`
--

INSERT INTO `user_history` (`id`, `user_id`, `manga_id`, `chapter_id`, `chapter_number`, `chapter_title`, `manga_title`, `read_at`) VALUES
(3, 1, '603b9d8f-97b6-4f82-b7d6-1de7e356e869', '75a7f8b1-9923-43bc-ae60-399a0623ab1a', '1', '', 'Kaijin Fugeki', '2025-04-07 11:46:01'),
(4, 1, '9ad4148b-8c9e-48d5-89ec-e7b98a660e42', 'f1f44261-4ef8-4e80-8bcc-62e0d80d15c9', '98', 'Ch. 98', 'Oomuro-ke', '2025-04-07 12:15:12'),
(5, 1, '6ccbc691-e9a5-4f7e-88b0-6ded05fd7a8e', '5a694d0a-6a9c-4829-b278-cb8bd454b0f3', '1', 'Biến thân', 'Jizaimen ~ Gương mặt cô ấy mong muốn ở tôi ~', '2025-04-08 03:50:15'),
(6, 1, 'acdbf57f-bf54-41b4-8d92-b3f3d14c852e', '5a46e8ea-d255-4436-9ceb-b6dd3f4b21be', '1', '', 'Aishiteru Game wo Owarasetai', '2025-04-08 05:19:12'),
(7, 1, 'acdbf57f-bf54-41b4-8d92-b3f3d14c852e', 'c4013fa5-3438-470f-b7fe-84580957e878', '37', 'chap 37', 'Aishiteru Game wo Owarasetai', '2025-04-08 11:37:37'),
(18, 1, 'a08d63ba-c412-4a7b-94b8-ef65bf2e2a30', 'aa2566cd-1eff-4d31-98ab-3fafa7713211', '59', 'Bọn Mày Đụ Nhau Rồi Hả?', 'Crush Của Tôi Là Một Đứa Lẳng Lơ', '2025-04-08 12:58:42'),
(20, 5, '8a1a79e4-7f90-450d-854c-0c780179f47f', 'e1fbeb7d-5b53-4b5c-862f-e543964bd8e3', '96', '', 'The Martial God Who Regressed Back to Level 2', '2025-05-23 09:50:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chapters`
--
ALTER TABLE `chapters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `manga_id` (`manga_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `manga_id` (`manga_id`);

--
-- Indexes for table `manga`
--
ALTER TABLE `manga`
  ADD PRIMARY KEY (`manga_id`);

--
-- Indexes for table `staff_picks`
--
ALTER TABLE `staff_picks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `manga_id` (`manga_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_history`
--
ALTER TABLE `user_history`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_manga_chapter` (`user_id`,`manga_id`,`chapter_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `staff_picks`
--
ALTER TABLE `staff_picks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_history`
--
ALTER TABLE `user_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chapters`
--
ALTER TABLE `chapters`
  ADD CONSTRAINT `chapters_ibfk_1` FOREIGN KEY (`manga_id`) REFERENCES `manga` (`manga_id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`manga_id`) REFERENCES `manga` (`manga_id`);

--
-- Constraints for table `staff_picks`
--
ALTER TABLE `staff_picks`
  ADD CONSTRAINT `fk_staff_picks_manga` FOREIGN KEY (`manga_id`) REFERENCES `manga` (`manga_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_history`
--
ALTER TABLE `user_history`
  ADD CONSTRAINT `user_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
