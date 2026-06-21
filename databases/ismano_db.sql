-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 15, 2026 at 07:16 AM
-- Server version: 8.0.45
-- PHP Version: 8.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ismano_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `blogs`
--

CREATE TABLE `blogs` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `excerpt` text COLLATE utf8mb4_general_ci,
  `content` longtext COLLATE utf8mb4_general_ci,
  `featured_image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `category_id` int DEFAULT NULL,
  `author_id` int NOT NULL,
  `status` enum('draft','published','archived') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `view_count` int DEFAULT '0',
  `is_featured` tinyint(1) DEFAULT '0',
  `meta_title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_general_ci,
  `meta_keywords` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_categories`
--

CREATE TABLE `blog_categories` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `color` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '#667eea',
  `created_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blog_categories`
--

INSERT INTO `blog_categories` (`id`, `name`, `slug`, `description`, `created_at`, `color`, `created_by`) VALUES
(1, 'sample', 'sample', '', '2026-06-03 16:48:28', '#667eea', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `blog_faqs`
--

CREATE TABLE `blog_faqs` (
  `id` int NOT NULL,
  `blog_id` int NOT NULL,
  `question` varchar(300) COLLATE utf8mb4_general_ci NOT NULL,
  `answer` text COLLATE utf8mb4_general_ci NOT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_sections`
--

CREATE TABLE `blog_sections` (
  `id` int NOT NULL,
  `blog_id` int NOT NULL,
  `section_type` enum('text_only','text_image_left','text_image_right','image_gallery','video','youtube','code_block','quote') COLLATE utf8mb4_general_ci DEFAULT 'text_only',
  `title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `content` text COLLATE utf8mb4_general_ci,
  `media_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `media_type` enum('image','video','youtube') COLLATE utf8mb4_general_ci DEFAULT 'image',
  `video_id` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_tags`
--

CREATE TABLE `blog_tags` (
  `id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_tag_relations`
--

CREATE TABLE `blog_tag_relations` (
  `blog_id` int NOT NULL,
  `tag_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int NOT NULL,
  `cart_session_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_sessions`
--

CREATE TABLE `cart_sessions` (
  `id` int NOT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enquiries`
--

CREATE TABLE `enquiries` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `service` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_general_ci,
  `status` enum('new','read','contacted','closed') COLLATE utf8mb4_general_ci DEFAULT 'new',
  `priority` enum('low','medium','high') COLLATE utf8mb4_general_ci DEFAULT 'medium',
  `notes` text COLLATE utf8mb4_general_ci,
  `contacted_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enquiries`
--

INSERT INTO `enquiries` (`id`, `name`, `email`, `phone`, `service`, `message`, `status`, `priority`, `notes`, `contacted_at`, `closed_at`, `created_at`, `updated_at`) VALUES
(1, 'Test User', 'test@example.com', '0712345678', 'Commercial Kitchen', 'This is a test enquiry', 'closed', 'high', NULL, NULL, NULL, '2026-06-15 06:09:54', '2026-06-15 06:16:31'),
(2, 'john', 'user@gmail.com', '072344322', 'Plumbing', 'osnjovjeofnv', 'contacted', 'high', NULL, '2026-06-15 06:14:38', NULL, '2026-06-15 06:13:50', '2026-06-15 06:14:38');

-- --------------------------------------------------------

--
-- Table structure for table `enquiry_replies`
--

CREATE TABLE `enquiry_replies` (
  `id` int NOT NULL,
  `enquiry_id` int NOT NULL,
  `admin_id` int NOT NULL,
  `reply` text COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `media_type` enum('image','video') COLLATE utf8mb4_general_ci DEFAULT 'image',
  `file_path` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `thumbnail_path` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `video_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `video_embed_code` text COLLATE utf8mb4_general_ci,
  `category` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tags` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `is_featured` tinyint(1) DEFAULT '0',
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `view_count` int DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gallery`
--

INSERT INTO `gallery` (`id`, `title`, `description`, `media_type`, `file_path`, `thumbnail_path`, `video_url`, `video_embed_code`, `category`, `tags`, `sort_order`, `is_featured`, `status`, `view_count`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Nyashisnki', 'adwe', 'image', '/Ismano/public/uploads/gallery/1781182938_6a2ab1dae3fb8.jpg', '/Ismano/public/uploads/gallery/thumb_1781182938_6a2ab1dae3fb8.jpg', '', '', 'Sample', '', 0, 0, 'active', 0, 9, '2026-06-11 13:02:19', '2026-06-11 13:02:19'),
(3, 'Sample 2', 'hii ni sample gallery', 'image', '/Ismano/public/uploads/gallery/1781185636_6a2abc64644aa.png', '/Ismano/public/uploads/gallery/thumb_1781185636_6a2abc64644aa.png', '', '', 'Category', '', 0, 0, 'active', 0, 9, '2026-06-11 13:47:16', '2026-06-11 13:47:16');

-- --------------------------------------------------------

--
-- Table structure for table `gallery_categories`
--

CREATE TABLE `gallery_categories` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hero_slides`
--

CREATE TABLE `hero_slides` (
  `id` int UNSIGNED NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `caption` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `attempt_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `email`, `ip_address`, `attempt_time`) VALUES
(1, 'user@gmail.com', '::1', '2026-05-30 15:42:52'),
(2, 'user@gmail.com', '::1', '2026-05-30 15:43:27'),
(3, 'admin@gmail.com', '::1', '2026-06-02 09:47:18'),
(4, 'admin@gmail.com', '::1', '2026-06-02 09:47:27'),
(5, 'admin1@gmail.com', '::1', '2026-06-02 09:48:22'),
(6, 'user@test.com', '::1', '2026-06-07 10:22:15'),
(7, 'user@gmail.com', '::1', '2026-06-07 10:22:30'),
(8, 'user@gmail.com', '::1', '2026-06-07 10:22:39'),
(9, 'user1@gmail.com', '::1', '2026-06-07 10:22:52'),
(10, 'user1@gmail.com', '::1', '2026-06-07 10:23:01'),
(11, 'user1@gmail.com', '::1', '2026-06-07 10:23:13'),
(12, 'user1@gmail.com', '::1', '2026-06-07 17:22:52'),
(13, 'user1@gmail.com', '::1', '2026-06-07 17:23:01'),
(14, 'user1@gmail.com', '::1', '2026-06-07 17:23:20'),
(15, 'admin1@gmail.com', '::1', '2026-06-07 18:12:44'),
(16, 'admin1@gmail.cpm', '::1', '2026-06-07 18:12:55'),
(17, 'admin1@gmail.com', '::1', '2026-06-07 18:13:08'),
(18, 'admin@gmail.com', '::1', '2026-06-11 04:32:51'),
(19, 'admin@gmail.com', '::1', '2026-06-11 04:32:57'),
(20, 'admin@gmail.com', '::1', '2026-06-11 04:33:00'),
(21, 'admin@gmail.com', '::1', '2026-06-11 09:28:02'),
(22, 'user@gmail.com', '::1', '2026-06-14 11:31:42'),
(23, 'user@gmail.com', '::1', '2026-06-14 11:31:50');

-- --------------------------------------------------------

--
-- Table structure for table `page_headers`
--

CREATE TABLE `page_headers` (
  `id` int UNSIGNED NOT NULL,
  `page_key` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtitle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `page_headers`
--

INSERT INTO `page_headers` (`id`, `page_key`, `title`, `subtitle`, `image_path`, `updated_at`) VALUES
(1, 'services', 'Our Services', 'Comprehensive digital solutions tailored to elevate your business.', NULL, '2026-06-07 18:58:01'),
(2, 'projects', 'Our Projects', 'A selection of the work we are proud of.', NULL, '2026-06-07 18:58:01'),
(3, 'blogs', 'Our Blog', 'Insights, ideas and updates from the team.', NULL, '2026-06-07 18:58:01'),
(4, 'contact', 'Get in Touch', 'We would love to hear about your project.', NULL, '2026-06-07 18:58:01');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `price` decimal(10,2) NOT NULL,
  `compare_price` decimal(10,2) DEFAULT NULL,
  `sku` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `stock_quantity` int DEFAULT '0',
  `category_id` int DEFAULT NULL,
  `featured_image` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gallery_images` text COLLATE utf8mb4_general_ci,
  `status` enum('active','inactive','draft') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `is_featured` tinyint(1) DEFAULT '0',
  `meta_title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_general_ci,
  `meta_keywords` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `view_count` int DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `image_path` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int NOT NULL,
  `category_id` int NOT NULL,
  `small_title` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `major_title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `project_slug` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `cover_image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('draft','published','archived') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `view_count` int DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_categories`
--

CREATE TABLE `project_categories` (
  `id` int NOT NULL,
  `category_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `category_slug` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `category_description` text COLLATE utf8mb4_general_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_categories`
--

INSERT INTO `project_categories` (`id`, `category_name`, `category_slug`, `category_description`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 'Die Single(Upweke)', 'die-single-upweke', '', NULL, '2026-05-30 19:17:56', '2026-05-30 19:17:56');

-- --------------------------------------------------------

--
-- Table structure for table `project_gallery`
--

CREATE TABLE `project_gallery` (
  `id` int NOT NULL,
  `project_id` int NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `image_title` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image_description` text COLLATE utf8mb4_general_ci,
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_tags`
--

CREATE TABLE `project_tags` (
  `id` int NOT NULL,
  `tag_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `tag_slug` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_tag_relations`
--

CREATE TABLE `project_tag_relations` (
  `project_id` int NOT NULL,
  `tag_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_videos`
--

CREATE TABLE `project_videos` (
  `id` int NOT NULL,
  `project_id` int NOT NULL,
  `video_title` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `video_url` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `video_embed_code` text COLLATE utf8mb4_general_ci,
  `video_type` enum('youtube','vimeo','local','other') COLLATE utf8mb4_general_ci DEFAULT 'youtube',
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int NOT NULL,
  `role_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `created_at`) VALUES
(1, 'superadmin', '2026-05-30 14:59:40'),
(2, 'admin', '2026-05-30 14:59:40'),
(3, 'user', '2026-05-30 14:59:40');

-- --------------------------------------------------------

--
-- Table structure for table `saved_for_later`
--

CREATE TABLE `saved_for_later` (
  `id` int NOT NULL,
  `cart_session_id` int NOT NULL,
  `product_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `short_description` text COLLATE utf8mb4_general_ci,
  `cover_image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('draft','published','archived') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `view_count` int DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_benefits`
--

CREATE TABLE `service_benefits` (
  `id` int NOT NULL,
  `service_id` int NOT NULL,
  `benefit_title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `benefit_description` text COLLATE utf8mb4_general_ci,
  `icon_class` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_faqs`
--

CREATE TABLE `service_faqs` (
  `id` int NOT NULL,
  `service_id` int NOT NULL,
  `question` varchar(300) COLLATE utf8mb4_general_ci NOT NULL,
  `answer` text COLLATE utf8mb4_general_ci NOT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_gallery`
--

CREATE TABLE `service_gallery` (
  `id` int NOT NULL,
  `service_id` int NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `image_title` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image_description` text COLLATE utf8mb4_general_ci,
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_sections`
--

CREATE TABLE `service_sections` (
  `id` int NOT NULL,
  `service_id` int NOT NULL,
  `section_type` enum('text_only','text_image_left','text_image_right','image_gallery','video') COLLATE utf8mb4_general_ci DEFAULT 'text_only',
  `title` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `content` text COLLATE utf8mb4_general_ci,
  `media_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `media_type` enum('image','video','youtube','vimeo') COLLATE utf8mb4_general_ci DEFAULT 'image',
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('logo_alt', 'Ismano', '2026-06-07 18:58:01'),
('logo_path', 'assets/images/branding/1780859184_a626ef1d247e.png', '2026-06-07 19:06:24'),
('site_name', 'Ismano', '2026-06-07 18:58:01');

-- --------------------------------------------------------

--
-- Table structure for table `store_cart`
--

CREATE TABLE `store_cart` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `saved_for_later` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_categories`
--

CREATE TABLE `store_categories` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `image_path` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `store_categories`
--

INSERT INTO `store_categories` (`id`, `name`, `slug`, `description`, `image_path`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Electronics', 'electronics', 'Electronic devices and gadgets', NULL, 1, 1, '2026-06-11 14:14:09', '2026-06-11 14:14:09'),
(2, 'Clothing', 'clothing', 'Fashion and apparel', NULL, 2, 1, '2026-06-11 14:14:09', '2026-06-11 14:14:09'),
(3, 'Books', 'books', 'Books and publications', NULL, 3, 1, '2026-06-11 14:14:09', '2026-06-11 14:14:09'),
(4, 'Home & Living', 'home-living', 'Home decor and living essentials', NULL, 4, 1, '2026-06-11 14:14:09', '2026-06-11 14:14:09'),
(6, 'Engineering Tools', 'engineering-tools', 'Professional engineering tools', NULL, 0, 1, '2026-06-11 14:53:08', '2026-06-11 14:53:08'),
(7, 'Safety Equipment', 'safety-equipment', 'PPE and safety gear', NULL, 0, 1, '2026-06-11 14:53:08', '2026-06-11 14:53:08'),
(8, 'Measuring Instruments', 'measuring-instruments', 'Precision measuring tools', NULL, 0, 1, '2026-06-11 14:53:08', '2026-06-11 14:53:08');

-- --------------------------------------------------------

--
-- Table structure for table `store_orders`
--

CREATE TABLE `store_orders` (
  `id` int NOT NULL,
  `order_number` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int NOT NULL,
  `customer_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `contact_phone` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `fulfillment_method` enum('walkin','delivery') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'walkin',
  `pickup_location` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `delivery_notes` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `currency` varchar(8) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'KES',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_status` enum('pending','paid','failed','cancelled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `payment_method` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'mpesa',
  `mpesa_merchant_request_id` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mpesa_checkout_request_id` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mpesa_receipt` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mpesa_phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mpesa_payer_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `store_orders`
--

INSERT INTO `store_orders` (`id`, `order_number`, `user_id`, `customer_name`, `contact_phone`, `fulfillment_method`, `pickup_location`, `delivery_notes`, `currency`, `subtotal`, `total`, `payment_status`, `payment_method`, `mpesa_merchant_request_id`, `mpesa_checkout_request_id`, `mpesa_receipt`, `mpesa_phone`, `mpesa_payer_name`, `paid_at`, `created_at`, `updated_at`) VALUES
(1, 'ISM-260615-E167', 10, 'Kichwa ngumu', '0768475485', 'walkin', 'Tao thika thika', '', 'KES', 2.00, 2.00, 'failed', 'mpesa', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-15 03:24:13', '2026-06-15 03:24:13'),
(2, 'ISM-260615-CDCE', 10, 'Kichwa ngumu', '0768475485', 'walkin', 'Tao thika thika', '', 'KES', 2.00, 2.00, 'failed', 'mpesa', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-15 03:24:15', '2026-06-15 03:24:16'),
(3, 'ISM-260615-6C5B', 10, 'Karanja', '0768457485', 'walkin', 'Juja', '', 'KES', 2.00, 2.00, 'failed', 'mpesa', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-15 04:42:04', '2026-06-15 04:42:04'),
(4, 'ISM-260615-11D1', 10, 'User', '0768457485', 'walkin', 'Meru', '', 'KES', 2.00, 2.00, 'failed', 'mpesa', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-15 04:42:42', '2026-06-15 04:42:42'),
(5, 'ISM-260615-17CE', 10, 'User', '0768457485', 'walkin', 'Juja', '', 'KES', 2.00, 2.00, 'failed', 'mpesa', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-15 04:45:48', '2026-06-15 04:45:48'),
(6, 'ISM-260615-4FCD', 10, 'User', '0768457485', 'walkin', 'Thika', '', 'KES', 2.00, 2.00, 'failed', 'mpesa', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-15 04:51:33', '2026-06-15 04:51:33'),
(7, 'ISM-260615-65A7', 10, 'User', '0768457485', 'walkin', 'Thika', '', 'KES', 2.00, 2.00, 'failed', 'mpesa', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-15 04:51:35', '2026-06-15 04:51:35'),
(8, 'ISM-260615-89A1', 10, 'User', '0768457485', 'walkin', 'Thika', '', 'KES', 2.00, 2.00, 'failed', 'mpesa', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-15 04:51:35', '2026-06-15 04:51:35'),
(9, 'ISM-260615-75E4', 10, 'User', '0768457485', 'walkin', 'Thika', '', 'KES', 2.00, 2.00, 'failed', 'mpesa', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-15 04:51:36', '2026-06-15 04:51:36'),
(10, 'ISM-260615-5ACD', 10, 'User', '0768457485', 'walkin', 'Thika', '', 'KES', 2.00, 2.00, 'failed', 'mpesa', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-15 04:51:37', '2026-06-15 04:51:37'),
(11, 'ISM-260615-409B', 10, 'User', '0768457485', 'walkin', 'Juja', '', 'KES', 2.00, 2.00, 'paid', 'mpesa', '09a6-4dfd-b161-78235d40b86c530107', 'ws_CO_15062026080125001768457485', 'UFF707UKYC', '254768457485', NULL, '2026-06-15 05:00:57', '2026-06-15 05:00:43', '2026-06-15 05:00:57');

-- --------------------------------------------------------

--
-- Table structure for table `store_orders_backup`
--

CREATE TABLE `store_orders_backup` (
  `id` int NOT NULL,
  `order_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','completed','cancelled') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `shipping_address` text COLLATE utf8mb4_general_ci,
  `billing_address` text COLLATE utf8mb4_general_ci,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_order_items`
--

CREATE TABLE `store_order_items` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `parcel_id` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `product_id` int DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `quantity` int NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  `fulfillment_status` enum('processing','ready_for_pickup','out_for_delivery','picked_up','delivered','arrived','cancelled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'processing',
  `fulfilled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `store_order_items`
--

INSERT INTO `store_order_items` (`id`, `order_id`, `parcel_id`, `product_id`, `product_name`, `unit_price`, `quantity`, `line_total`, `fulfillment_status`, `fulfilled_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'PCL-HJFK5', 5, 'Sample product', 2.00, 1, 2.00, 'processing', NULL, '2026-06-15 03:24:13', '2026-06-15 03:24:13'),
(2, 2, 'PCL-TXCTE', 5, 'Sample product', 2.00, 1, 2.00, 'processing', NULL, '2026-06-15 03:24:15', '2026-06-15 03:24:15'),
(3, 3, 'PCL-R1INB', 5, 'Sample product', 2.00, 1, 2.00, 'processing', NULL, '2026-06-15 04:42:04', '2026-06-15 04:42:04'),
(4, 4, 'PCL-FGP24', 5, 'Sample product', 2.00, 1, 2.00, 'processing', NULL, '2026-06-15 04:42:42', '2026-06-15 04:42:42'),
(5, 5, 'PCL-CCQ45', 5, 'Sample product', 2.00, 1, 2.00, 'processing', NULL, '2026-06-15 04:45:48', '2026-06-15 04:45:48'),
(6, 6, 'PCL-SM0GI', 5, 'Sample product', 2.00, 1, 2.00, 'processing', NULL, '2026-06-15 04:51:33', '2026-06-15 04:51:33'),
(7, 7, 'PCL-73TYV', 5, 'Sample product', 2.00, 1, 2.00, 'processing', NULL, '2026-06-15 04:51:35', '2026-06-15 04:51:35'),
(8, 8, 'PCL-8D909', 5, 'Sample product', 2.00, 1, 2.00, 'processing', NULL, '2026-06-15 04:51:35', '2026-06-15 04:51:35'),
(9, 9, 'PCL-8H0W2', 5, 'Sample product', 2.00, 1, 2.00, 'processing', NULL, '2026-06-15 04:51:36', '2026-06-15 04:51:36'),
(10, 10, 'PCL-65KT7', 5, 'Sample product', 2.00, 1, 2.00, 'processing', NULL, '2026-06-15 04:51:37', '2026-06-15 04:51:37'),
(11, 11, 'PCL-G6XH1', 5, 'Sample product', 2.00, 1, 2.00, 'picked_up', '2026-06-15 05:13:32', '2026-06-15 05:00:43', '2026-06-15 05:13:32');

-- --------------------------------------------------------

--
-- Table structure for table `store_order_items_backup`
--

CREATE TABLE `store_order_items_backup` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_products`
--

CREATE TABLE `store_products` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `price` decimal(10,2) NOT NULL,
  `compare_price` decimal(10,2) DEFAULT NULL,
  `sku` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `stock_quantity` int DEFAULT '0',
  `category_id` int DEFAULT NULL,
  `featured_image` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gallery_images` text COLLATE utf8mb4_general_ci,
  `status` enum('active','inactive','draft') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `is_featured` tinyint(1) DEFAULT '0',
  `meta_title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_general_ci,
  `sort_order` int DEFAULT '0',
  `view_count` int DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `store_products`
--

INSERT INTO `store_products` (`id`, `name`, `slug`, `description`, `price`, `compare_price`, `sku`, `stock_quantity`, `category_id`, `featured_image`, `gallery_images`, `status`, `is_featured`, `meta_title`, `meta_description`, `sort_order`, `view_count`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Marriage Certificate', '', 'get your free wife with zero stress hormones', 5000000.00, 78000000.00, 'HGO900', 1000, 4, '/Ismano/public/uploads/store/products/1781189335_6a2acad78631a.png', NULL, 'active', 1, '', '', 0, 0, 9, '2026-06-11 14:48:55', '2026-06-11 14:48:55'),
(5, 'Sample product', 'sample-product', 'testing my callback url', 2.00, 10.00, 'HGH100', 89, 2, '/Ismano/public/uploads/store/products/1781429597_6a2e755deaba6.jpg', NULL, 'active', 0, '', '', 0, 2, 9, '2026-06-14 09:33:17', '2026-06-15 05:00:57');

-- --------------------------------------------------------

--
-- Table structure for table `store_saved_for_later`
--

CREATE TABLE `store_saved_for_later` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int NOT NULL,
  `tenant_id` int NOT NULL,
  `plan_id` int NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `mpesa_ref` varchar(60) DEFAULT NULL,
  `phone_paid_from` varchar(20) DEFAULT NULL,
  `payment_status` enum('pending','confirmed','failed') DEFAULT 'pending',
  `confirmed_at` datetime DEFAULT NULL,
  `starts_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_plans`
--

CREATE TABLE `subscription_plans` (
  `id` int NOT NULL,
  `plan_code` varchar(20) NOT NULL,
  `plan_name` varchar(60) NOT NULL,
  `duration_days` int NOT NULL,
  `price_ksh` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscription_plans`
--

INSERT INTO `subscription_plans` (`id`, `plan_code`, `plan_name`, `duration_days`, `price_ksh`, `description`, `is_active`) VALUES
(1, 'WEEKLY', 'Weekly', 7, 250.00, '7 days of full access', 1),
(2, 'BIWEEKLY', '2-Week', 14, 500.00, '14 days of full access', 1),
(3, 'MONTHLY', 'Monthly', 30, 1000.00, '30 days — best value', 1);

-- --------------------------------------------------------

--
-- Table structure for table `subscription_stk`
--

CREATE TABLE `subscription_stk` (
  `id` int NOT NULL,
  `tenant_id` int NOT NULL,
  `plan_id` int NOT NULL,
  `phone` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `checkout_request_id` varchar(100) NOT NULL,
  `merchant_request_id` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','failed','cancelled') DEFAULT 'pending',
  `mpesa_receipt` varchar(60) DEFAULT NULL,
  `result_desc` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tenants`
--

CREATE TABLE `tenants` (
  `id` int NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `business_name` varchar(150) NOT NULL,
  `owner_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `is_verified` tinyint(1) DEFAULT '0',
  `failed_attempts` int DEFAULT '0',
  `locked_until` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tenant_otp`
--

CREATE TABLE `tenant_otp` (
  `id` int NOT NULL,
  `email` varchar(100) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `testimonials`
--

CREATE TABLE `testimonials` (
  `id` int NOT NULL,
  `customer_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `customer_email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `customer_phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `customer_initial` varchar(5) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rating` int DEFAULT '5',
  `testimonial_text` text COLLATE utf8mb4_general_ci NOT NULL,
  `service_tag` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `is_featured` tinyint(1) DEFAULT '0',
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `approved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `testimonials`
--

INSERT INTO `testimonials` (`id`, `customer_name`, `customer_email`, `customer_phone`, `customer_initial`, `rating`, `testimonial_text`, `service_tag`, `role`, `status`, `is_featured`, `sort_order`, `created_at`, `updated_at`, `approved_at`) VALUES
(1, 'James Mwangi', NULL, NULL, 'J', 5, 'ISMAN designed and installed our 450 sqm hotel kitchen in under 8 weeks. The SS304 fabrication quality exceeded international standards, and their team worked around our operational hours without a single disruption to guests.', 'Commercial Kitchen', 'General Manager, Radisson Blu Nairobi', 'approved', 1, 0, '2026-06-15 06:27:11', '2026-06-15 06:27:11', NULL),
(2, 'Aisha Noor', NULL, NULL, 'A', 5, 'The stainless balustrade work at Two Rivers was flawless. Precision welds, perfect alignment across three floors, and delivered ahead of schedule. We have used them on every project since.', 'Stainless Railing', 'Project Lead, Centum Investment', 'approved', 1, 0, '2026-06-15 06:27:11', '2026-06-15 06:27:11', NULL),
(3, 'Dr. Peter Otieno', NULL, NULL, 'P', 5, 'Their hospital fit-out met every infection-control requirement we set. Documentation was thorough and the finish on the SS316 surfaces is exactly what a sterile environment needs.', 'Hospital Fit-out', 'Facilities Director, Kenyatta National Hospital', 'approved', 1, 0, '2026-06-15 06:27:11', '2026-06-15 06:27:11', NULL),
(4, 'Grace Wambui', NULL, NULL, 'G', 5, 'We commissioned a full processing line and ISMAN handled design, fabrication and install end to end. HACCP-ready, on budget, and running at full throughput from day one.', 'Food Processing', 'Operations Manager, Brookside Dairy', 'approved', 1, 0, '2026-06-15 06:27:11', '2026-06-15 06:27:11', NULL),
(5, 'James Mwangi', NULL, NULL, 'J', 5, 'ISMAN designed and installed our 450 sqm hotel kitchen in under 8 weeks. The SS304 fabrication quality exceeded international standards, and their team worked around our operational hours without a single disruption to guests.', 'Commercial Kitchen', 'General Manager, Radisson Blu Nairobi', 'approved', 1, 0, '2026-06-15 06:27:25', '2026-06-15 06:27:25', NULL),
(6, 'Aisha Noor', NULL, NULL, 'A', 5, 'The stainless balustrade work at Two Rivers was flawless. Precision welds, perfect alignment across three floors, and delivered ahead of schedule. We have used them on every project since.', 'Stainless Railing', 'Project Lead, Centum Investment', 'approved', 1, 0, '2026-06-15 06:27:25', '2026-06-15 06:27:25', NULL),
(7, 'Dr. Peter Otieno', NULL, NULL, 'P', 5, 'Their hospital fit-out met every infection-control requirement we set. Documentation was thorough and the finish on the SS316 surfaces is exactly what a sterile environment needs.', 'Hospital Fit-out', 'Facilities Director, Kenyatta National Hospital', 'approved', 1, 0, '2026-06-15 06:27:25', '2026-06-15 06:27:25', NULL),
(8, 'Grace Wambui', NULL, NULL, 'G', 5, 'We commissioned a full processing line and ISMAN handled design, fabrication and install end to end. HACCP-ready, on budget, and running at full throughput from day one.', 'Food Processing', 'Operations Manager, Brookside Dairy', 'approved', 1, 0, '2026-06-15 06:27:25', '2026-06-15 06:27:25', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role_id` int NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `email_verified` tinyint(1) DEFAULT '0',
  `verification_token` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reset_token` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role_id`, `is_active`, `email_verified`, `verification_token`, `reset_token`, `reset_token_expires`, `created_at`, `updated_at`) VALUES
(9, 'Administrator', 'admin@gmail.com', '$2y$10$VnxQo6O7A5W8htzag2H0LejkXWqFViJYbpw9eIKVXRx8222.jzHx.', 2, 1, 1, NULL, NULL, NULL, '2026-06-08 07:58:24', '2026-06-08 07:58:24'),
(10, 'User', 'user@gmail.com', '$2y$10$BjAwB6sYAwqKpLZaQM2XluXTNXOB3HZ67fwFCO2kNsyVq0kLHeg1i', 3, 1, 1, NULL, NULL, NULL, '2026-06-11 15:35:23', '2026-06-11 15:35:23');

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `user_id` int NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  `profile_pic` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`user_id`, `first_name`, `last_name`, `phone`, `address`, `profile_pic`, `created_at`) VALUES
(9, '', '', '', NULL, NULL, '2026-06-08 07:58:24'),
(10, '', '', '', NULL, NULL, '2026-06-11 15:35:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `blogs`
--
ALTER TABLE `blogs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_author` (`author_id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_published` (`published_at`),
  ADD KEY `idx_featured` (`is_featured`);

--
-- Indexes for table `blog_categories`
--
ALTER TABLE `blog_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`);

--
-- Indexes for table `blog_faqs`
--
ALTER TABLE `blog_faqs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_blog` (`blog_id`);

--
-- Indexes for table `blog_sections`
--
ALTER TABLE `blog_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_blog` (`blog_id`),
  ADD KEY `idx_sort` (`sort_order`);

--
-- Indexes for table `blog_tags`
--
ALTER TABLE `blog_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `blog_tag_relations`
--
ALTER TABLE `blog_tag_relations`
  ADD PRIMARY KEY (`blog_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart_product` (`cart_session_id`,`product_id`),
  ADD KEY `idx_cart` (`cart_session_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `cart_sessions`
--
ALTER TABLE `cart_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `enquiries`
--
ALTER TABLE `enquiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_created` (`created_at`);
ALTER TABLE `enquiries` ADD FULLTEXT KEY `idx_search` (`name`,`email`,`message`);

--
-- Indexes for table `enquiry_replies`
--
ALTER TABLE `enquiry_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `idx_enquiry` (`enquiry_id`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_media_type` (`media_type`),
  ADD KEY `idx_sort` (`sort_order`);

--
-- Indexes for table `gallery_categories`
--
ALTER TABLE `gallery_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `hero_slides`
--
ALTER TABLE `hero_slides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hero_active_order` (`is_active`,`sort_order`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_time` (`email`,`attempt_time`);

--
-- Indexes for table `page_headers`
--
ALTER TABLE `page_headers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `page_key` (`page_key`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token` (`token`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_price` (`price`);
ALTER TABLE `products` ADD FULLTEXT KEY `idx_search` (`name`,`description`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_slug` (`slug`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `project_slug` (`project_slug`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_slug` (`project_slug`);

--
-- Indexes for table `project_categories`
--
ALTER TABLE `project_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_slug` (`category_slug`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_slug` (`category_slug`);

--
-- Indexes for table `project_gallery`
--
ALTER TABLE `project_gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project` (`project_id`);

--
-- Indexes for table `project_tags`
--
ALTER TABLE `project_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tag_name` (`tag_name`),
  ADD UNIQUE KEY `tag_slug` (`tag_slug`);

--
-- Indexes for table `project_tag_relations`
--
ALTER TABLE `project_tag_relations`
  ADD PRIMARY KEY (`project_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `project_videos`
--
ALTER TABLE `project_videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project` (`project_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `saved_for_later`
--
ALTER TABLE `saved_for_later`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_saved` (`cart_session_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `service_benefits`
--
ALTER TABLE `service_benefits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_service` (`service_id`);

--
-- Indexes for table `service_faqs`
--
ALTER TABLE `service_faqs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_service` (`service_id`);

--
-- Indexes for table `service_gallery`
--
ALTER TABLE `service_gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_service` (`service_id`),
  ADD KEY `idx_sort` (`sort_order`);

--
-- Indexes for table `service_sections`
--
ALTER TABLE `service_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_service` (`service_id`),
  ADD KEY `idx_sort` (`sort_order`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `store_cart`
--
ALTER TABLE `store_cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `store_categories`
--
ALTER TABLE `store_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_slug` (`slug`);

--
-- Indexes for table `store_orders`
--
ALTER TABLE `store_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_orders_user` (`user_id`),
  ADD KEY `idx_orders_pay` (`payment_status`),
  ADD KEY `idx_orders_checkout` (`mpesa_checkout_request_id`);

--
-- Indexes for table `store_orders_backup`
--
ALTER TABLE `store_orders_backup`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_order_number` (`order_number`);

--
-- Indexes for table `store_order_items`
--
ALTER TABLE `store_order_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `parcel_id` (`parcel_id`),
  ADD KEY `idx_items_order` (`order_id`),
  ADD KEY `idx_items_parcel` (`parcel_id`),
  ADD KEY `idx_items_status` (`fulfillment_status`);

--
-- Indexes for table `store_order_items_backup`
--
ALTER TABLE `store_order_items_backup`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_order` (`order_id`);

--
-- Indexes for table `store_products`
--
ALTER TABLE `store_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_price` (`price`);
ALTER TABLE `store_products` ADD FULLTEXT KEY `idx_search` (`name`,`description`);

--
-- Indexes for table `store_saved_for_later`
--
ALTER TABLE `store_saved_for_later`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product_saved` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_user_saved` (`user_id`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `idx_current` (`tenant_id`,`is_current`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indexes for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plan_code` (`plan_code`);

--
-- Indexes for table `subscription_stk`
--
ALTER TABLE `subscription_stk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `checkout_request_id` (`checkout_request_id`),
  ADD KEY `tenant_id` (`tenant_id`);

--
-- Indexes for table `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `tenant_otp`
--
ALTER TABLE `tenant_otp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `testimonials`
--
ALTER TABLE `testimonials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_featured` (`is_featured`),
  ADD KEY `idx_sort` (`sort_order`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_reset_token` (`reset_token`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `blogs`
--
ALTER TABLE `blogs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `blog_categories`
--
ALTER TABLE `blog_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `blog_faqs`
--
ALTER TABLE `blog_faqs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blog_sections`
--
ALTER TABLE `blog_sections`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `blog_tags`
--
ALTER TABLE `blog_tags`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_sessions`
--
ALTER TABLE `cart_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enquiries`
--
ALTER TABLE `enquiries`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `enquiry_replies`
--
ALTER TABLE `enquiry_replies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gallery_categories`
--
ALTER TABLE `gallery_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hero_slides`
--
ALTER TABLE `hero_slides`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `page_headers`
--
ALTER TABLE `page_headers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `project_categories`
--
ALTER TABLE `project_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `project_gallery`
--
ALTER TABLE `project_gallery`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `project_tags`
--
ALTER TABLE `project_tags`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_videos`
--
ALTER TABLE `project_videos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `saved_for_later`
--
ALTER TABLE `saved_for_later`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `service_benefits`
--
ALTER TABLE `service_benefits`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_faqs`
--
ALTER TABLE `service_faqs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `service_gallery`
--
ALTER TABLE `service_gallery`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `service_sections`
--
ALTER TABLE `service_sections`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `store_cart`
--
ALTER TABLE `store_cart`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `store_categories`
--
ALTER TABLE `store_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `store_orders`
--
ALTER TABLE `store_orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `store_orders_backup`
--
ALTER TABLE `store_orders_backup`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `store_order_items`
--
ALTER TABLE `store_order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `store_order_items_backup`
--
ALTER TABLE `store_order_items_backup`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `store_products`
--
ALTER TABLE `store_products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `store_saved_for_later`
--
ALTER TABLE `store_saved_for_later`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `subscription_stk`
--
ALTER TABLE `subscription_stk`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tenants`
--
ALTER TABLE `tenants`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tenant_otp`
--
ALTER TABLE `tenant_otp`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `testimonials`
--
ALTER TABLE `testimonials`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `blogs`
--
ALTER TABLE `blogs`
  ADD CONSTRAINT `blogs_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `blogs_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blog_faqs`
--
ALTER TABLE `blog_faqs`
  ADD CONSTRAINT `blog_faqs_ibfk_1` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blog_sections`
--
ALTER TABLE `blog_sections`
  ADD CONSTRAINT `blog_sections_ibfk_1` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blog_tag_relations`
--
ALTER TABLE `blog_tag_relations`
  ADD CONSTRAINT `blog_tag_relations_ibfk_1` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `blog_tag_relations_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `blog_tags` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_session_id`) REFERENCES `cart_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_sessions`
--
ALTER TABLE `cart_sessions`
  ADD CONSTRAINT `cart_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enquiry_replies`
--
ALTER TABLE `enquiry_replies`
  ADD CONSTRAINT `enquiry_replies_ibfk_1` FOREIGN KEY (`enquiry_id`) REFERENCES `enquiries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enquiry_replies_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gallery`
--
ALTER TABLE `gallery`
  ADD CONSTRAINT `gallery_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD CONSTRAINT `product_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `project_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `projects_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `project_categories`
--
ALTER TABLE `project_categories`
  ADD CONSTRAINT `project_categories_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `project_gallery`
--
ALTER TABLE `project_gallery`
  ADD CONSTRAINT `project_gallery_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_tag_relations`
--
ALTER TABLE `project_tag_relations`
  ADD CONSTRAINT `project_tag_relations_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_tag_relations_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `project_tags` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_videos`
--
ALTER TABLE `project_videos`
  ADD CONSTRAINT `project_videos_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `saved_for_later`
--
ALTER TABLE `saved_for_later`
  ADD CONSTRAINT `saved_for_later_ibfk_1` FOREIGN KEY (`cart_session_id`) REFERENCES `cart_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_for_later_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `service_benefits`
--
ALTER TABLE `service_benefits`
  ADD CONSTRAINT `service_benefits_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_faqs`
--
ALTER TABLE `service_faqs`
  ADD CONSTRAINT `service_faqs_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_gallery`
--
ALTER TABLE `service_gallery`
  ADD CONSTRAINT `service_gallery_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_sections`
--
ALTER TABLE `service_sections`
  ADD CONSTRAINT `service_sections_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `store_cart`
--
ALTER TABLE `store_cart`
  ADD CONSTRAINT `store_cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `store_cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `store_products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `store_orders`
--
ALTER TABLE `store_orders`
  ADD CONSTRAINT `fk_orders_user_v2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `store_orders_backup`
--
ALTER TABLE `store_orders_backup`
  ADD CONSTRAINT `store_orders_backup_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `store_order_items`
--
ALTER TABLE `store_order_items`
  ADD CONSTRAINT `fk_items_order_v2` FOREIGN KEY (`order_id`) REFERENCES `store_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `store_order_items_backup`
--
ALTER TABLE `store_order_items_backup`
  ADD CONSTRAINT `store_order_items_backup_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `store_orders_backup` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `store_order_items_backup_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `store_products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `store_products`
--
ALTER TABLE `store_products`
  ADD CONSTRAINT `store_products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `store_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `store_products_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `store_saved_for_later`
--
ALTER TABLE `store_saved_for_later`
  ADD CONSTRAINT `store_saved_for_later_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `store_saved_for_later_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `store_products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `subscription_stk`
--
ALTER TABLE `subscription_stk`
  ADD CONSTRAINT `subscription_stk_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
