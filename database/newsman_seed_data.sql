-- Updated SQL Dump for Newsman Laravel 12 Project
-- Adapted from original newspaper database
-- Compatible with Laravel 12 and modern MySQL

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `newsman`
--

-- --------------------------------------------------------

--
-- Data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `parent_id`, `image`, `description`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Bangladesh', 'bangladesh', NULL, NULL, 'Local news from Bangladesh', 1, 1, '2018-02-26 04:13:52', '2018-02-26 04:13:52'),
(2, 'International', 'international', NULL, NULL, 'International news and updates', 2, 1, '2018-02-26 04:15:11', '2018-02-26 04:15:11'),
(3, 'Economy', 'economy', NULL, NULL, 'Economic news and analysis', 3, 1, '2018-02-26 04:15:45', '2018-02-26 04:15:45'),
(4, 'Sports', 'sports', NULL, NULL, 'Sports news and updates', 4, 1, '2018-02-26 04:16:17', '2018-02-26 04:16:17'),
(5, 'Entertainment', 'entertainment', NULL, NULL, 'Entertainment and celebrity news', 5, 1, '2018-02-26 04:16:48', '2018-02-26 04:16:48'),
(6, 'Features', 'features', NULL, NULL, 'Feature articles and stories', 6, 1, '2018-02-26 04:17:11', '2018-02-26 04:17:11'),
(7, 'Life Style', 'life-style', NULL, NULL, 'Lifestyle tips and trends', 7, 1, '2018-02-26 04:17:50', '2018-02-26 04:17:50'),
(8, 'Photo', 'photo', NULL, NULL, 'Photo galleries and visual stories', 8, 1, '2018-02-26 04:18:13', '2018-02-26 04:18:13'),
(9, 'Video', 'video', NULL, NULL, 'Video content and news', 9, 1, '2018-02-26 04:18:35', '2018-02-26 04:18:35'),
(10, 'Technology', 'technology', NULL, NULL, 'Technology news and reviews', 10, 1, '2018-02-26 04:19:21', '2018-02-26 04:19:21'),
(11, 'Education', 'education', NULL, NULL, 'Education news and updates', 11, 1, '2018-02-26 04:19:46', '2018-02-26 04:19:46'),
(12, 'Art and Literature', 'art-and-literature', NULL, NULL, 'Arts, culture and literature', 12, 1, '2018-02-26 04:20:35', '2018-02-26 04:20:35'),
(13, 'Others', 'others', NULL, NULL, 'Miscellaneous news', 13, 1, '2018-02-26 04:21:17', '2018-02-26 04:21:17'),
(14, 'Opinion', 'opinion', NULL, NULL, 'Opinion pieces and editorials', 14, 1, '2018-02-26 04:30:28', '2018-02-26 04:30:28'),
(15, 'Business', 'business', NULL, NULL, 'Business news and market updates', 15, 1, '2018-02-26 04:43:21', '2018-02-26 04:43:21'),
(16, 'Showbiz', 'showbiz', NULL, NULL, 'Show business and entertainment industry', 16, 1, '2018-02-26 04:44:03', '2018-02-26 04:44:03'),
(17, 'Home', 'home', NULL, NULL, 'Homepage featured content', 17, 1, '2018-02-26 04:45:16', '2018-02-26 04:45:16');

-- --------------------------------------------------------

--
-- Data for table `tags`
--

INSERT INTO `tags` (`id`, `name`, `slug`, `created_at`, `updated_at`) VALUES
(1, 'Road accident', 'road-accident', '2018-02-26 04:25:31', '2018-02-26 04:25:31'),
(2, 'Bangladesh', 'bangladesh', '2018-02-26 04:25:55', '2018-02-26 04:25:55'),
(3, 'Life Style', 'life-style', '2018-02-26 04:26:29', '2018-02-26 04:26:29'),
(4, 'Death', 'death', '2018-02-26 04:27:00', '2018-02-26 04:27:00'),
(5, 'MWC 2018', 'mwc-2018', '2018-02-26 04:27:26', '2018-02-26 04:27:26'),
(6, 'Technology', 'technology', '2024-01-01 00:00:00', '2024-01-01 00:00:00'),
(7, 'Politics', 'politics', '2024-01-01 00:00:00', '2024-01-01 00:00:00'),
(8, 'Health', 'health', '2024-01-01 00:00:00', '2024-01-01 00:00:00'),
(9, 'Environment', 'environment', '2024-01-01 00:00:00', '2024-01-01 00:00:00'),
(10, 'Science', 'science', '2024-01-01 00:00:00', '2024-01-01 00:00:00');

-- --------------------------------------------------------

--
-- Data for table `advertisements`
--

INSERT INTO `advertisements` (`id`, `title`, `description`, `image_path`, `url`, `position`, `price`, `duration_days`, `start_date`, `end_date`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Sample Advertisement', 'This is a sample advertisement for testing purposes.', NULL, '#', 'header', 5000.00, 30, '2024-01-01', '2024-01-31', 1, '2018-02-26 04:28:52', '2018-02-26 04:28:52');

-- --------------------------------------------------------

--
-- Data for table `rss_feeds`
--

INSERT INTO `rss_feeds` (`id`, `name`, `url`, `category_id`, `language`, `country`, `priority`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'The Daily Star Frontpage', 'http://www.thedailystar.net/frontpage/rss.xml', 17, 'en', 'BD', 1, 1, '2018-02-15 19:53:13', '2018-02-26 04:48:08'),
(2, 'Prothom-alo', 'http://www.prothomalo.com/feed/', 17, 'bn', 'BD', 1, 1, '2018-02-15 21:56:52', '2018-02-26 04:53:11'),
(3, 'The Daily Star - Business', 'http://www.thedailystar.net/business/rss.xml', 15, 'en', 'BD', 1, 1, '2018-02-26 05:01:10', '2018-02-26 05:01:10'),
(4, 'BDnews24.com', 'http://bangla.bdnews24.com/?widgetName=rssfeed&widgetId=1151&getXmlFeed=true', 17, 'bn', 'BD', 1, 1, '2018-02-26 05:10:14', '2018-02-26 05:10:14'),
(5, 'The Daily Star - Sports', 'http://www.thedailystar.net/sports/rss.xml', 4, 'en', 'BD', 2, 1, '2018-02-26 05:25:56', '2018-02-26 05:25:56');

-- --------------------------------------------------------

--
-- Data for table `users`
-- Note: Passwords are properly hashed with bcrypt for Laravel 12 compatibility
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `avatar`, `phone`, `location`, `is_active`, `email_verified_at`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin User', 'admin@newsman.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, NULL, NULL, 1, '2024-01-01 00:00:00', NULL, '2024-01-01 00:00:00', '2024-01-01 00:00:00'),
(2, 'Editor User', 'editor@newsman.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'editor', NULL, NULL, NULL, 1, '2024-01-01 00:00:00', NULL, '2024-01-01 00:00:00', '2024-01-01 00:00:00'),
(3, 'Reporter User', 'reporter@newsman.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reporter', NULL, NULL, NULL, 1, '2024-01-01 00:00:00', NULL, '2024-01-01 00:00:00', '2024-01-01 00:00:00');

-- --------------------------------------------------------

--
-- Data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Newsman', 'Website name', '2024-01-01 00:00:00', '2024-01-01 00:00:00'),
(2, 'site_description', 'Your trusted source for news', 'Website description', '2024-01-01 00:00:00', '2024-01-01 00:00:00'),
(3, 'site_logo', '', 'Website logo path', '2024-01-01 00:00:00', '2024-01-01 00:00:00'),
(4, 'contact_email', 'contact@newsman.com', 'Contact email address', '2024-01-01 00:00:00', '2024-01-01 00:00:00'),
(5, 'timezone', 'Asia/Dhaka', 'Website timezone', '2024-01-01 00:00:00', '2024-01-01 00:00:00'),
(6, 'posts_per_page', '10', 'Number of posts per page', '2024-01-01 00:00:00', '2024-01-01 00:00:00'),
(7, 'allow_comments', '1', 'Allow comments on posts', '2024-01-01 00:00:00', '2024-01-01 00:00:00'),
(8, 'comment_moderation', '1', 'Enable comment moderation', '2024-01-01 00:00:00', '2024-01-01 00:00:00'),
(9, 'google_analytics', '', 'Google Analytics tracking code', '2024-01-01 00:00:00', '2024-01-01 00:00:00'),
(10, 'facebook_url', '', 'Facebook page URL', '2024-01-01 00:00:00', '2024-01-01 00:00:00'),
(11, 'twitter_url', '', 'Twitter profile URL', '2024-01-01 00:00:00', '2024-01-01 00:00:00'),
(12, 'instagram_url', '', 'Instagram profile URL', '2024-01-01 00:00:00', '2024-01-01 00:00:00');

-- --------------------------------------------------------

--
-- Reset AUTO_INCREMENT values for imported tables
--

ALTER TABLE `advertisements` AUTO_INCREMENT = 2;
ALTER TABLE `categories` AUTO_INCREMENT = 18;
ALTER TABLE `rss_feeds` AUTO_INCREMENT = 6;
ALTER TABLE `settings` AUTO_INCREMENT = 13;
ALTER TABLE `tags` AUTO_INCREMENT = 11;
ALTER TABLE `users` AUTO_INCREMENT = 4;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;