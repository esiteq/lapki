-- phpMyAdmin SQL Dump
-- version 5.2.2deb1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 18, 2025 at 11:49 PM
-- Server version: 8.4.6-0ubuntu0.25.04.1
-- PHP Version: 8.4.5

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lapki`
--

-- --------------------------------------------------------

--
-- Table structure for table `wp_lapki_animals`
--

DROP TABLE IF EXISTS `wp_lapki_animals`;
CREATE TABLE `wp_lapki_animals` (
  `id` bigint UNSIGNED NOT NULL,
  `organization_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `species` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'adoptable',
  `breed_primary` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `breed_secondary` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `breed_mixed` tinyint(1) DEFAULT '0',
  `breed_unknown` tinyint(1) DEFAULT '0',
  `color_primary` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_secondary` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_tertiary` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `age` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `coat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `spayed_neutered` tinyint(1) DEFAULT NULL,
  `house_trained` tinyint(1) DEFAULT NULL,
  `declawed` tinyint(1) DEFAULT NULL,
  `special_needs` tinyint(1) DEFAULT NULL,
  `shots_current` tinyint(1) DEFAULT NULL,
  `good_with_children` tinyint(1) DEFAULT NULL,
  `good_with_dogs` tinyint(1) DEFAULT NULL,
  `good_with_cats` tinyint(1) DEFAULT NULL,
  `contact_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_postcode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_country` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT 'UA',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wp_lapki_attributes`
--

DROP TABLE IF EXISTS `wp_lapki_attributes`;
CREATE TABLE `wp_lapki_attributes` (
  `id` int NOT NULL,
  `entity` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'animal',
  `entity_type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attr_name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attr_value` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attr_display` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lang` char(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wp_lapki_attributes`
--

INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(1, 'animal', 'dog', 'gender', 'male', 'Самець', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(2, 'animal', 'dog', 'gender', 'male', 'Male', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(3, 'animal', 'dog', 'gender', 'female', 'Самка', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(4, 'animal', 'dog', 'gender', 'female', 'Female', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(5, 'animal', 'dog', 'coat', 'hairless', 'Без шерсті', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(6, 'animal', 'dog', 'coat', 'hairless', 'Hairless', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(7, 'animal', 'dog', 'coat', 'short', 'Коротка', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(8, 'animal', 'dog', 'coat', 'short', 'Short', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(9, 'animal', 'dog', 'coat', 'medium', 'Середня', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(10, 'animal', 'dog', 'coat', 'medium', 'Medium', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(11, 'animal', 'dog', 'coat', 'long', 'Довга', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(12, 'animal', 'dog', 'coat', 'long', 'Long', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(13, 'animal', 'dog', 'coat', 'wire', 'Жорстка', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(14, 'animal', 'dog', 'coat', 'wire', 'Wire', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(15, 'animal', 'dog', 'coat', 'curly', 'Кучерява', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(16, 'animal', 'dog', 'coat', 'curly', 'Curly', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(17, 'animal', 'dog', 'color', 'apricot_beige', 'Абрикосовий / Бежевий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(18, 'animal', 'dog', 'color', 'apricot_beige', 'Apricot / Beige', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(19, 'animal', 'dog', 'color', 'bicolor', 'Двокольоровий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(20, 'animal', 'dog', 'color', 'bicolor', 'Bicolor', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(21, 'animal', 'dog', 'color', 'black', 'Чорний', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(22, 'animal', 'dog', 'color', 'black', 'Black', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(23, 'animal', 'dog', 'color', 'brindle', 'Тигровий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(24, 'animal', 'dog', 'color', 'brindle', 'Brindle', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(25, 'animal', 'dog', 'color', 'brown_chocolate', 'Коричневий / Шоколадний', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(26, 'animal', 'dog', 'color', 'brown_chocolate', 'Brown / Chocolate', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(27, 'animal', 'dog', 'color', 'golden', 'Золотистий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(28, 'animal', 'dog', 'color', 'golden', 'Golden', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(29, 'animal', 'dog', 'color', 'gray_blue_silver', 'Сірий / Блакитний / Срібний', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(30, 'animal', 'dog', 'color', 'gray_blue_silver', 'Gray / Blue / Silver', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(31, 'animal', 'dog', 'color', 'harlequin', 'Арлекін', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(32, 'animal', 'dog', 'color', 'harlequin', 'Harlequin', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(33, 'animal', 'dog', 'color', 'merle_blue', 'Мерль (блакитний)', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(34, 'animal', 'dog', 'color', 'merle_blue', 'Merle (Blue)', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(35, 'animal', 'dog', 'color', 'merle_red', 'Мерль (червоний)', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(36, 'animal', 'dog', 'color', 'merle_red', 'Merle (Red)', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(37, 'animal', 'dog', 'color', 'red_chestnut_orange', 'Рудий / Каштановий / Помаранчевий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(38, 'animal', 'dog', 'color', 'red_chestnut_orange', 'Red / Chestnut / Orange', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(39, 'animal', 'dog', 'color', 'sable', 'Соболиний', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(40, 'animal', 'dog', 'color', 'sable', 'Sable', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(41, 'animal', 'dog', 'color', 'tricolor', 'Тричольоровий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(42, 'animal', 'dog', 'color', 'tricolor', 'Tricolor (Brown, Black, & White)', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(43, 'animal', 'dog', 'color', 'white_cream', 'Білий / Кремовий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(44, 'animal', 'dog', 'color', 'white_cream', 'White / Cream', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(45, 'animal', 'dog', 'color', 'yellow_tan_blond_fawn', 'Жовтий / Палевий / Світлий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(46, 'animal', 'dog', 'color', 'yellow_tan_blond_fawn', 'Yellow / Tan / Blond / Fawn', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(47, 'animal', 'cat', 'gender', 'male', 'Самець', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(48, 'animal', 'cat', 'gender', 'male', 'Male', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(49, 'animal', 'cat', 'gender', 'female', 'Самка', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(50, 'animal', 'cat', 'gender', 'female', 'Female', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(51, 'animal', 'cat', 'coat', 'hairless', 'Без шерсті', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(52, 'animal', 'cat', 'coat', 'hairless', 'Hairless', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(53, 'animal', 'cat', 'coat', 'short', 'Коротка', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(54, 'animal', 'cat', 'coat', 'short', 'Short', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(55, 'animal', 'cat', 'coat', 'medium', 'Середня', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(56, 'animal', 'cat', 'coat', 'medium', 'Medium', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(57, 'animal', 'cat', 'coat', 'long', 'Довга', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(58, 'animal', 'cat', 'coat', 'long', 'Long', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(59, 'animal', 'cat', 'color', 'black', 'Чорний', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(60, 'animal', 'cat', 'color', 'black', 'Black', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(61, 'animal', 'cat', 'color', 'black_white_tuxedo', 'Чорно-білий / Смокінг', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(62, 'animal', 'cat', 'color', 'black_white_tuxedo', 'Black & White / Tuxedo', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(63, 'animal', 'cat', 'color', 'blue_cream', 'Блакитно-кремовий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(64, 'animal', 'cat', 'color', 'blue_cream', 'Blue Cream', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(65, 'animal', 'cat', 'color', 'blue_point', 'Блакитний пойнт', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(66, 'animal', 'cat', 'color', 'blue_point', 'Blue Point', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(67, 'animal', 'cat', 'color', 'brown_chocolate', 'Коричневий / Шоколадний', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(68, 'animal', 'cat', 'color', 'brown_chocolate', 'Brown / Chocolate', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(69, 'animal', 'cat', 'color', 'buff_white', 'Бежево-білий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(70, 'animal', 'cat', 'color', 'buff_white', 'Buff & White', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(71, 'animal', 'cat', 'color', 'buff_tan_fawn', 'Бежевий / Палевий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(72, 'animal', 'cat', 'color', 'buff_tan_fawn', 'Buff / Tan / Fawn', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(73, 'animal', 'cat', 'color', 'calico', 'Тричольоровий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(74, 'animal', 'cat', 'color', 'calico', 'Calico', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(75, 'animal', 'cat', 'color', 'chocolate_point', 'Шоколадний пойнт', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(76, 'animal', 'cat', 'color', 'chocolate_point', 'Chocolate Point', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(77, 'animal', 'cat', 'color', 'cream_ivory', 'Кремовий / Слонової кістки', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(78, 'animal', 'cat', 'color', 'cream_ivory', 'Cream / Ivory', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(79, 'animal', 'cat', 'color', 'cream_point', 'Кремовий пойнт', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(80, 'animal', 'cat', 'color', 'cream_point', 'Cream Point', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(81, 'animal', 'cat', 'color', 'dilute_calico', 'Розбавлений тричольоровий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(82, 'animal', 'cat', 'color', 'dilute_calico', 'Dilute Calico', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(83, 'animal', 'cat', 'color', 'dilute_tortoiseshell', 'Розбавлений черепаховий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(84, 'animal', 'cat', 'color', 'dilute_tortoiseshell', 'Dilute Tortoiseshell', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(85, 'animal', 'cat', 'color', 'flame_point', 'Вогняний пойнт', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(86, 'animal', 'cat', 'color', 'flame_point', 'Flame Point', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(87, 'animal', 'cat', 'color', 'gray_white', 'Сіро-білий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(88, 'animal', 'cat', 'color', 'gray_white', 'Gray & White', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(89, 'animal', 'cat', 'color', 'gray_blue_silver', 'Сірий / Блакитний / Срібний', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(90, 'animal', 'cat', 'color', 'gray_blue_silver', 'Gray / Blue / Silver', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(91, 'animal', 'cat', 'color', 'lilac_point', 'Ліловий пойнт', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(92, 'animal', 'cat', 'color', 'lilac_point', 'Lilac Point', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(93, 'animal', 'cat', 'color', 'orange_white', 'Помаранчево-білий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(94, 'animal', 'cat', 'color', 'orange_white', 'Orange & White', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(95, 'animal', 'cat', 'color', 'orange_red', 'Помаранчевий / Рудий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(96, 'animal', 'cat', 'color', 'orange_red', 'Orange / Red', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(97, 'animal', 'cat', 'color', 'seal_point', 'Сіл пойнт', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(98, 'animal', 'cat', 'color', 'seal_point', 'Seal Point', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(99, 'animal', 'cat', 'color', 'smoke', 'Димчастий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(100, 'animal', 'cat', 'color', 'smoke', 'Smoke', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(101, 'animal', 'cat', 'color', 'tabby_brown_chocolate', 'Тебі (коричневий)', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(102, 'animal', 'cat', 'color', 'tabby_brown_chocolate', 'Tabby (Brown / Chocolate)', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(103, 'animal', 'cat', 'color', 'tabby_buff_tan_fawn', 'Тебі (бежевий)', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(104, 'animal', 'cat', 'color', 'tabby_buff_tan_fawn', 'Tabby (Buff / Tan / Fawn)', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(105, 'animal', 'cat', 'color', 'tabby_gray_blue_silver', 'Тебі (сірий)', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(106, 'animal', 'cat', 'color', 'tabby_gray_blue_silver', 'Tabby (Gray / Blue / Silver)', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(107, 'animal', 'cat', 'color', 'tabby_leopard_spotted', 'Тебі (плямистий)', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(108, 'animal', 'cat', 'color', 'tabby_leopard_spotted', 'Tabby (Leopard / Spotted)', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(109, 'animal', 'cat', 'color', 'tabby_orange_red', 'Тебі (рудий)', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(110, 'animal', 'cat', 'color', 'tabby_orange_red', 'Tabby (Orange / Red)', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(111, 'animal', 'cat', 'color', 'tabby_tiger_striped', 'Тебі (смугастий)', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(112, 'animal', 'cat', 'color', 'tabby_tiger_striped', 'Tabby (Tiger Striped)', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(113, 'animal', 'cat', 'color', 'torbie', 'Торбі', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(114, 'animal', 'cat', 'color', 'torbie', 'Torbie', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(115, 'animal', 'cat', 'color', 'tortoiseshell', 'Черепаховий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(116, 'animal', 'cat', 'color', 'tortoiseshell', 'Tortoiseshell', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(117, 'animal', 'cat', 'color', 'white', 'Білий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(118, 'animal', 'cat', 'color', 'white', 'White', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(119, 'animal', 'rabbit', 'gender', 'male', 'Самець', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(120, 'animal', 'rabbit', 'gender', 'male', 'Male', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(121, 'animal', 'rabbit', 'gender', 'female', 'Самка', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(122, 'animal', 'rabbit', 'gender', 'female', 'Female', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(123, 'animal', 'rabbit', 'coat', 'short', 'Коротка', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(124, 'animal', 'rabbit', 'coat', 'short', 'Short', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(125, 'animal', 'rabbit', 'coat', 'long', 'Довга', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(126, 'animal', 'rabbit', 'coat', 'long', 'Long', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(127, 'animal', 'bird', 'gender', 'male', 'Самець', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(128, 'animal', 'bird', 'gender', 'male', 'Male', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(129, 'animal', 'bird', 'gender', 'female', 'Самка', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(130, 'animal', 'bird', 'gender', 'female', 'Female', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(131, 'animal', 'bird', 'gender', 'unknown', 'Невідомо', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(132, 'animal', 'bird', 'gender', 'unknown', 'Unknown', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(147, 'animal', 'dog', 'spayed_neutered', 'yes', 'Так', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(148, 'animal', 'dog', 'spayed_neutered', 'yes', 'Yes', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(149, 'animal', 'dog', 'spayed_neutered', 'no', 'Ні', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(150, 'animal', 'dog', 'spayed_neutered', 'no', 'No', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(151, 'animal', 'dog', 'spayed_neutered', 'unknown', 'Невідомо', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(152, 'animal', 'dog', 'spayed_neutered', 'unknown', 'Unknown', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(153, 'animal', 'cat', 'spayed_neutered', 'yes', 'Так', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(154, 'animal', 'cat', 'spayed_neutered', 'yes', 'Yes', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(155, 'animal', 'cat', 'spayed_neutered', 'no', 'Ні', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(156, 'animal', 'cat', 'spayed_neutered', 'no', 'No', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(157, 'animal', 'cat', 'spayed_neutered', 'unknown', 'Невідомо', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(158, 'animal', 'cat', 'spayed_neutered', 'unknown', 'Unknown', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(159, 'animal', 'cat', 'breed', 'abyssinian', 'Abyssinian', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(160, 'animal', 'cat', 'breed', 'abyssinian', 'Абісинська', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(161, 'animal', 'cat', 'breed', 'american_bobtail', 'American Bobtail', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(162, 'animal', 'cat', 'breed', 'american_bobtail', 'Американський бобтейл', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(163, 'animal', 'cat', 'breed', 'american_curl', 'American Curl', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(164, 'animal', 'cat', 'breed', 'american_curl', 'Американський керл', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(165, 'animal', 'cat', 'breed', 'american_shorthair', 'American Shorthair', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(166, 'animal', 'cat', 'breed', 'american_shorthair', 'Американська короткошерста', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(167, 'animal', 'cat', 'breed', 'american_wirehair', 'American Wirehair', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(168, 'animal', 'cat', 'breed', 'american_wirehair', 'Американська жорсткошерста', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(169, 'animal', 'cat', 'breed', 'applehead_siamese', 'Applehead Siamese', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(170, 'animal', 'cat', 'breed', 'applehead_siamese', 'Сіамська яблукоголова', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(171, 'animal', 'cat', 'breed', 'balinese', 'Balinese', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(172, 'animal', 'cat', 'breed', 'balinese', 'Балінезійська', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(173, 'animal', 'cat', 'breed', 'bengal', 'Bengal', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(174, 'animal', 'cat', 'breed', 'bengal', 'Бенгальська', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(175, 'animal', 'cat', 'breed', 'birman', 'Birman', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(176, 'animal', 'cat', 'breed', 'birman', 'Бірманська', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(177, 'animal', 'cat', 'breed', 'bombay', 'Bombay', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(178, 'animal', 'cat', 'breed', 'bombay', 'Бомбейська', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(179, 'animal', 'cat', 'breed', 'british_shorthair', 'British Shorthair', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(180, 'animal', 'cat', 'breed', 'british_shorthair', 'Британська короткошерста', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(181, 'animal', 'cat', 'breed', 'burmese', 'Burmese', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(182, 'animal', 'cat', 'breed', 'burmese', 'Бурманська', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(183, 'animal', 'cat', 'breed', 'burmilla', 'Burmilla', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(184, 'animal', 'cat', 'breed', 'burmilla', 'Бурмілла', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(185, 'animal', 'cat', 'breed', 'calico', 'Calico', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(186, 'animal', 'cat', 'breed', 'calico', 'Каліко', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(187, 'animal', 'cat', 'breed', 'canadian_hairless', 'Canadian Hairless', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(188, 'animal', 'cat', 'breed', 'canadian_hairless', 'Канадська безшерста', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(189, 'animal', 'cat', 'breed', 'chartreux', 'Chartreux', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(190, 'animal', 'cat', 'breed', 'chartreux', 'Шартре', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(191, 'animal', 'cat', 'breed', 'chausie', 'Chausie', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(192, 'animal', 'cat', 'breed', 'chausie', 'Чаузі', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(193, 'animal', 'cat', 'breed', 'chinchilla', 'Chinchilla', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(194, 'animal', 'cat', 'breed', 'chinchilla', 'Шиншила', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(195, 'animal', 'cat', 'breed', 'cornish_rex', 'Cornish Rex', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(196, 'animal', 'cat', 'breed', 'cornish_rex', 'Корніш-рекс', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(197, 'animal', 'cat', 'breed', 'cymric', 'Cymric', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(198, 'animal', 'cat', 'breed', 'cymric', 'Кімрік', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(199, 'animal', 'cat', 'breed', 'devon_rex', 'Devon Rex', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(200, 'animal', 'cat', 'breed', 'devon_rex', 'Девон-рекс', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(201, 'animal', 'cat', 'breed', 'dilute_calico', 'Dilute Calico', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(202, 'animal', 'cat', 'breed', 'dilute_calico', 'Розбавлена каліко', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(203, 'animal', 'cat', 'breed', 'dilute_tortoiseshell', 'Dilute Tortoiseshell', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(204, 'animal', 'cat', 'breed', 'dilute_tortoiseshell', 'Розбавлена черепахова', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(205, 'animal', 'cat', 'breed', 'domestic_long_hair', 'Domestic Long Hair', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(206, 'animal', 'cat', 'breed', 'domestic_long_hair', 'Домашня довгошерста', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(207, 'animal', 'cat', 'breed', 'domestic_medium_hair', 'Domestic Medium Hair', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(208, 'animal', 'cat', 'breed', 'domestic_medium_hair', 'Домашня середньошерста', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(209, 'animal', 'cat', 'breed', 'domestic_short_hair', 'Domestic Short Hair', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(210, 'animal', 'cat', 'breed', 'domestic_short_hair', 'Домашня короткошерста', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(211, 'animal', 'cat', 'breed', 'egyptian_mau', 'Egyptian Mau', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(212, 'animal', 'cat', 'breed', 'egyptian_mau', 'Єгипетська мау', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(213, 'animal', 'cat', 'breed', 'exotic_shorthair', 'Exotic Shorthair', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(214, 'animal', 'cat', 'breed', 'exotic_shorthair', 'Екзотична короткошерста', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(215, 'animal', 'cat', 'breed', 'extra_toes_cat_hemingway_polydactyl', 'Extra-Toes Cat / Hemingway Polydactyl', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(216, 'animal', 'cat', 'breed', 'extra_toes_cat_hemingway_polydactyl', 'Багатопалий кіт', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(217, 'animal', 'cat', 'breed', 'havana', 'Havana', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(218, 'animal', 'cat', 'breed', 'havana', 'Гавана', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(219, 'animal', 'cat', 'breed', 'himalayan', 'Himalayan', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(220, 'animal', 'cat', 'breed', 'himalayan', 'Гімалайська', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(221, 'animal', 'cat', 'breed', 'japanese_bobtail', 'Japanese Bobtail', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(222, 'animal', 'cat', 'breed', 'japanese_bobtail', 'Японський бобтейл', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(223, 'animal', 'cat', 'breed', 'javanese', 'Javanese', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(224, 'animal', 'cat', 'breed', 'javanese', 'Яванська', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(225, 'animal', 'cat', 'breed', 'korat', 'Korat', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(226, 'animal', 'cat', 'breed', 'korat', 'Корат', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(227, 'animal', 'cat', 'breed', 'laperm', 'LaPerm', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(228, 'animal', 'cat', 'breed', 'laperm', 'Ла-перм', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(229, 'animal', 'cat', 'breed', 'maine_coon', 'Maine Coon', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(230, 'animal', 'cat', 'breed', 'maine_coon', 'Мейн-кун', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(231, 'animal', 'cat', 'breed', 'manx', 'Manx', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(232, 'animal', 'cat', 'breed', 'manx', 'Менкс', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(233, 'animal', 'cat', 'breed', 'munchkin', 'Munchkin', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(234, 'animal', 'cat', 'breed', 'munchkin', 'Манчкін', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(235, 'animal', 'cat', 'breed', 'nebelung', 'Nebelung', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(236, 'animal', 'cat', 'breed', 'nebelung', 'Нібелунг', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(237, 'animal', 'cat', 'breed', 'norwegian_forest_cat', 'Norwegian Forest Cat', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(238, 'animal', 'cat', 'breed', 'norwegian_forest_cat', 'Норвезька лісова', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(239, 'animal', 'cat', 'breed', 'ocicat', 'Ocicat', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(240, 'animal', 'cat', 'breed', 'ocicat', 'Оцикет', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(241, 'animal', 'cat', 'breed', 'oriental_long_hair', 'Oriental Long Hair', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(242, 'animal', 'cat', 'breed', 'oriental_long_hair', 'Орієнтальна довгошерста', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(243, 'animal', 'cat', 'breed', 'oriental_short_hair', 'Oriental Short Hair', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(244, 'animal', 'cat', 'breed', 'oriental_short_hair', 'Орієнтальна короткошерста', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(245, 'animal', 'cat', 'breed', 'oriental_tabby', 'Oriental Tabby', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(246, 'animal', 'cat', 'breed', 'oriental_tabby', 'Орієнтальна тебі', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(247, 'animal', 'cat', 'breed', 'persian', 'Persian', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(248, 'animal', 'cat', 'breed', 'persian', 'Перська', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(249, 'animal', 'cat', 'breed', 'pixiebob', 'Pixiebob', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(250, 'animal', 'cat', 'breed', 'pixiebob', 'Піксі-боб', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(251, 'animal', 'cat', 'breed', 'ragamuffin', 'Ragamuffin', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(252, 'animal', 'cat', 'breed', 'ragamuffin', 'Регамаффін', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(253, 'animal', 'cat', 'breed', 'ragdoll', 'Ragdoll', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(254, 'animal', 'cat', 'breed', 'ragdoll', 'Регдол', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(255, 'animal', 'cat', 'breed', 'russian_blue', 'Russian Blue', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(256, 'animal', 'cat', 'breed', 'russian_blue', 'Російська блакитна', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(257, 'animal', 'cat', 'breed', 'scottish_fold', 'Scottish Fold', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(258, 'animal', 'cat', 'breed', 'scottish_fold', 'Шотландська висловуха', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(259, 'animal', 'cat', 'breed', 'selkirk_rex', 'Selkirk Rex', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(260, 'animal', 'cat', 'breed', 'selkirk_rex', 'Селкірк-рекс', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(261, 'animal', 'cat', 'breed', 'siamese', 'Siamese', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(262, 'animal', 'cat', 'breed', 'siamese', 'Сіамська', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(263, 'animal', 'cat', 'breed', 'siberian', 'Siberian', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(264, 'animal', 'cat', 'breed', 'siberian', 'Сибірська', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(265, 'animal', 'cat', 'breed', 'silver', 'Silver', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(266, 'animal', 'cat', 'breed', 'silver', 'Срібна', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(267, 'animal', 'cat', 'breed', 'singapura', 'Singapura', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(268, 'animal', 'cat', 'breed', 'singapura', 'Сінгапура', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(269, 'animal', 'cat', 'breed', 'snowshoe', 'Snowshoe', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(270, 'animal', 'cat', 'breed', 'snowshoe', 'Сноу-шу', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(271, 'animal', 'cat', 'breed', 'somali', 'Somali', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(272, 'animal', 'cat', 'breed', 'somali', 'Сомалійська', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(273, 'animal', 'cat', 'breed', 'sphynx_hairless_cat', 'Sphynx / Hairless Cat', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(274, 'animal', 'cat', 'breed', 'sphynx_hairless_cat', 'Сфінкс', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(275, 'animal', 'cat', 'breed', 'tabby', 'Tabby', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(276, 'animal', 'cat', 'breed', 'tabby', 'Тебі', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(277, 'animal', 'cat', 'breed', 'tiger', 'Tiger', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(278, 'animal', 'cat', 'breed', 'tiger', 'Тигрова', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(279, 'animal', 'cat', 'breed', 'tonkinese', 'Tonkinese', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(280, 'animal', 'cat', 'breed', 'tonkinese', 'Тонкінська', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(281, 'animal', 'cat', 'breed', 'torbie', 'Torbie', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(282, 'animal', 'cat', 'breed', 'torbie', 'Торбі', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(283, 'animal', 'cat', 'breed', 'tortoiseshell', 'Tortoiseshell', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(284, 'animal', 'cat', 'breed', 'tortoiseshell', 'Черепахова', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(285, 'animal', 'cat', 'breed', 'toyger', 'Toyger', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(286, 'animal', 'cat', 'breed', 'toyger', 'Тойгер', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(287, 'animal', 'cat', 'breed', 'turkish_angora', 'Turkish Angora', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(288, 'animal', 'cat', 'breed', 'turkish_angora', 'Турецька ангора', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(289, 'animal', 'cat', 'breed', 'turkish_van', 'Turkish Van', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(290, 'animal', 'cat', 'breed', 'turkish_van', 'Турецький ван', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(291, 'animal', 'cat', 'breed', 'tuxedo', 'Tuxedo', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(292, 'animal', 'cat', 'breed', 'tuxedo', 'Смокінг', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(293, 'animal', 'cat', 'breed', 'york_chocolate', 'York Chocolate', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(294, 'animal', 'cat', 'breed', 'york_chocolate', 'Йорк шоколад', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(295, 'animal', 'dog', 'breed', 'affenpinscher', 'Affenpinscher', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(296, 'animal', 'dog', 'breed', 'affenpinscher', 'Аффенпінчер', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(297, 'animal', 'dog', 'breed', 'afghan_hound', 'Afghan Hound', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(298, 'animal', 'dog', 'breed', 'afghan_hound', 'Афганська борзая', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(299, 'animal', 'dog', 'breed', 'airedale_terrier', 'Airedale Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(300, 'animal', 'dog', 'breed', 'airedale_terrier', 'Ердельтер\'єр', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(301, 'animal', 'dog', 'breed', 'akbash', 'Akbash', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(302, 'animal', 'dog', 'breed', 'akbash', 'Акбаш', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(303, 'animal', 'dog', 'breed', 'akita', 'Akita', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(304, 'animal', 'dog', 'breed', 'akita', 'Акіта', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(305, 'animal', 'dog', 'breed', 'alaskan_malamute', 'Alaskan Malamute', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(306, 'animal', 'dog', 'breed', 'alaskan_malamute', 'Аляскинський маламут', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(307, 'animal', 'dog', 'breed', 'american_bulldog', 'American Bulldog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(308, 'animal', 'dog', 'breed', 'american_bulldog', 'Американський бульдог', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(309, 'animal', 'dog', 'breed', 'american_bully', 'American Bully', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(310, 'animal', 'dog', 'breed', 'american_bully', 'Американський буллі', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(311, 'animal', 'dog', 'breed', 'american_eskimo_dog', 'American Eskimo Dog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(312, 'animal', 'dog', 'breed', 'american_eskimo_dog', 'Американський ескімоський собака', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(313, 'animal', 'dog', 'breed', 'american_foxhound', 'American Foxhound', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(314, 'animal', 'dog', 'breed', 'american_foxhound', 'Американська лисича гончая', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(315, 'animal', 'dog', 'breed', 'american_hairless_terrier', 'American Hairless Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(316, 'animal', 'dog', 'breed', 'american_hairless_terrier', 'Американський голий тер\'єр', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(317, 'animal', 'dog', 'breed', 'american_staffordshire_terrier', 'American Staffordshire Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(318, 'animal', 'dog', 'breed', 'american_staffordshire_terrier', 'Американський стаффордширський тер\'єр', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(319, 'animal', 'dog', 'breed', 'american_water_spaniel', 'American Water Spaniel', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(320, 'animal', 'dog', 'breed', 'american_water_spaniel', 'Американський водяний спаніель', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(321, 'animal', 'dog', 'breed', 'anatolian_shepherd', 'Anatolian Shepherd', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(322, 'animal', 'dog', 'breed', 'anatolian_shepherd', 'Анатолійська вівчарка', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(323, 'animal', 'dog', 'breed', 'appenzell_mountain_dog', 'Appenzell Mountain Dog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(324, 'animal', 'dog', 'breed', 'appenzell_mountain_dog', 'Appenzell Mountain Dog', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(325, 'animal', 'dog', 'breed', 'aussiedoodle', 'Aussiedoodle', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(326, 'animal', 'dog', 'breed', 'aussiedoodle', 'Aussiedoodle', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(327, 'animal', 'dog', 'breed', 'australian_cattle_dog_blue_heeler', 'Australian Cattle Dog / Blue Heeler', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(328, 'animal', 'dog', 'breed', 'australian_cattle_dog_blue_heeler', 'Австралійська пастуша собака', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(329, 'animal', 'dog', 'breed', 'australian_kelpie', 'Australian Kelpie', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(330, 'animal', 'dog', 'breed', 'australian_kelpie', 'Австралійський келпі', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(331, 'animal', 'dog', 'breed', 'australian_shepherd', 'Australian Shepherd', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(332, 'animal', 'dog', 'breed', 'australian_shepherd', 'Австралійська вівчарка', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(333, 'animal', 'dog', 'breed', 'australian_terrier', 'Australian Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(334, 'animal', 'dog', 'breed', 'australian_terrier', 'Австралійський тер\'єр', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(335, 'animal', 'dog', 'breed', 'basenji', 'Basenji', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(336, 'animal', 'dog', 'breed', 'basenji', 'Басенджі', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(337, 'animal', 'dog', 'breed', 'basset_hound', 'Basset Hound', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(338, 'animal', 'dog', 'breed', 'basset_hound', 'Бассет-хаунд', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(339, 'animal', 'dog', 'breed', 'beagle', 'Beagle', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(340, 'animal', 'dog', 'breed', 'beagle', 'Бігль', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(341, 'animal', 'dog', 'breed', 'bearded_collie', 'Bearded Collie', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(342, 'animal', 'dog', 'breed', 'bearded_collie', 'Бородатий коллі', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(343, 'animal', 'dog', 'breed', 'beauceron', 'Beauceron', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(344, 'animal', 'dog', 'breed', 'beauceron', 'Beauceron', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(345, 'animal', 'dog', 'breed', 'bedlington_terrier', 'Bedlington Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(346, 'animal', 'dog', 'breed', 'bedlington_terrier', 'Бедлінгтон-тер\'єр', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(347, 'animal', 'dog', 'breed', 'belgian_shepherd_laekenois', 'Belgian Shepherd / Laekenois', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(348, 'animal', 'dog', 'breed', 'belgian_shepherd_laekenois', 'Belgian Shepherd / Laekenois', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(349, 'animal', 'dog', 'breed', 'belgian_shepherd_malinois', 'Belgian Shepherd / Malinois', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(350, 'animal', 'dog', 'breed', 'belgian_shepherd_malinois', 'Бельгійська вівчарка маліноа', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(351, 'animal', 'dog', 'breed', 'belgian_shepherd_sheepdog', 'Belgian Shepherd / Sheepdog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(352, 'animal', 'dog', 'breed', 'belgian_shepherd_sheepdog', 'Belgian Shepherd / Sheepdog', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(353, 'animal', 'dog', 'breed', 'belgian_shepherd_tervuren', 'Belgian Shepherd / Tervuren', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(354, 'animal', 'dog', 'breed', 'belgian_shepherd_tervuren', 'Belgian Shepherd / Tervuren', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(355, 'animal', 'dog', 'breed', 'bernedoodle', 'Bernedoodle', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(356, 'animal', 'dog', 'breed', 'bernedoodle', 'Bernedoodle', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(357, 'animal', 'dog', 'breed', 'bernese_mountain_dog', 'Bernese Mountain Dog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(358, 'animal', 'dog', 'breed', 'bernese_mountain_dog', 'Бернський зенненхунд', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(359, 'animal', 'dog', 'breed', 'bichon_frise', 'Bichon Frise', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(360, 'animal', 'dog', 'breed', 'bichon_frise', 'Бішон фрізе', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(361, 'animal', 'dog', 'breed', 'black_and_tan_coonhound', 'Black and Tan Coonhound', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(362, 'animal', 'dog', 'breed', 'black_and_tan_coonhound', 'Black and Tan Coonhound', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(363, 'animal', 'dog', 'breed', 'black_labrador_retriever', 'Black Labrador Retriever', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(364, 'animal', 'dog', 'breed', 'black_labrador_retriever', 'Чорний лабрадор ретривер', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(365, 'animal', 'dog', 'breed', 'black_mouth_cur', 'Black Mouth Cur', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(366, 'animal', 'dog', 'breed', 'black_mouth_cur', 'Black Mouth Cur', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(367, 'animal', 'dog', 'breed', 'black_russian_terrier', 'Black Russian Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(368, 'animal', 'dog', 'breed', 'black_russian_terrier', 'Black Russian Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(369, 'animal', 'dog', 'breed', 'bloodhound', 'Bloodhound', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(370, 'animal', 'dog', 'breed', 'bloodhound', 'Бладхаунд', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(371, 'animal', 'dog', 'breed', 'blue_lacy', 'Blue Lacy', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(372, 'animal', 'dog', 'breed', 'blue_lacy', 'Blue Lacy', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(373, 'animal', 'dog', 'breed', 'bluetick_coonhound', 'Bluetick Coonhound', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(374, 'animal', 'dog', 'breed', 'bluetick_coonhound', 'Bluetick Coonhound', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(375, 'animal', 'dog', 'breed', 'boerboel', 'Boerboel', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(376, 'animal', 'dog', 'breed', 'boerboel', 'Boerboel', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(377, 'animal', 'dog', 'breed', 'bolognese', 'Bolognese', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(378, 'animal', 'dog', 'breed', 'bolognese', 'Bolognese', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(379, 'animal', 'dog', 'breed', 'border_collie', 'Border Collie', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(380, 'animal', 'dog', 'breed', 'border_collie', 'Бордер коллі', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(381, 'animal', 'dog', 'breed', 'border_terrier', 'Border Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(382, 'animal', 'dog', 'breed', 'border_terrier', 'Бордер тер\'єр', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(383, 'animal', 'dog', 'breed', 'borzoi', 'Borzoi', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(384, 'animal', 'dog', 'breed', 'borzoi', 'Борзой', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(385, 'animal', 'dog', 'breed', 'boston_terrier', 'Boston Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(386, 'animal', 'dog', 'breed', 'boston_terrier', 'Бостон тер\'єр', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(387, 'animal', 'dog', 'breed', 'bouvier_des_flandres', 'Bouvier des Flandres', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(388, 'animal', 'dog', 'breed', 'bouvier_des_flandres', 'Bouvier des Flandres', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(389, 'animal', 'dog', 'breed', 'boxer', 'Boxer', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(390, 'animal', 'dog', 'breed', 'boxer', 'Боксер', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(391, 'animal', 'dog', 'breed', 'boykin_spaniel', 'Boykin Spaniel', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(392, 'animal', 'dog', 'breed', 'boykin_spaniel', 'Boykin Spaniel', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(393, 'animal', 'dog', 'breed', 'briard', 'Briard', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(394, 'animal', 'dog', 'breed', 'briard', 'Briard', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(395, 'animal', 'dog', 'breed', 'brittany_spaniel', 'Brittany Spaniel', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(396, 'animal', 'dog', 'breed', 'brittany_spaniel', 'Бретонський спаніель', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(397, 'animal', 'dog', 'breed', 'brussels_griffon', 'Brussels Griffon', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(398, 'animal', 'dog', 'breed', 'brussels_griffon', 'Brussels Griffon', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(399, 'animal', 'dog', 'breed', 'bull_terrier', 'Bull Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(400, 'animal', 'dog', 'breed', 'bull_terrier', 'Буль тер\'єр', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(401, 'animal', 'dog', 'breed', 'bullmastiff', 'Bullmastiff', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(402, 'animal', 'dog', 'breed', 'bullmastiff', 'Бульмастиф', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(403, 'animal', 'dog', 'breed', 'cairn_terrier', 'Cairn Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(404, 'animal', 'dog', 'breed', 'cairn_terrier', 'Кернтер\'єр', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(405, 'animal', 'dog', 'breed', 'canaan_dog', 'Canaan Dog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(406, 'animal', 'dog', 'breed', 'canaan_dog', 'Canaan Dog', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(407, 'animal', 'dog', 'breed', 'cane_corso', 'Cane Corso', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(408, 'animal', 'dog', 'breed', 'cane_corso', 'Кане корсо', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(409, 'animal', 'dog', 'breed', 'cardigan_welsh_corgi', 'Cardigan Welsh Corgi', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(410, 'animal', 'dog', 'breed', 'cardigan_welsh_corgi', 'Вельш коргі кардіган', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(411, 'animal', 'dog', 'breed', 'carolina_dog', 'Carolina Dog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(412, 'animal', 'dog', 'breed', 'carolina_dog', 'Carolina Dog', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(413, 'animal', 'dog', 'breed', 'catahoula_leopard_dog', 'Catahoula Leopard Dog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(414, 'animal', 'dog', 'breed', 'catahoula_leopard_dog', 'Катахула', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(415, 'animal', 'dog', 'breed', 'cattle_dog', 'Cattle Dog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(416, 'animal', 'dog', 'breed', 'cattle_dog', 'Cattle Dog', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(417, 'animal', 'dog', 'breed', 'caucasian_sheepdog_caucasian_ovtcharka', 'Caucasian Sheepdog / Caucasian Ovtcharka', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(418, 'animal', 'dog', 'breed', 'caucasian_sheepdog_caucasian_ovtcharka', 'Caucasian Sheepdog / Caucasian Ovtcharka', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(419, 'animal', 'dog', 'breed', 'cavachon', 'Cavachon', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(420, 'animal', 'dog', 'breed', 'cavachon', 'Cavachon', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(421, 'animal', 'dog', 'breed', 'cavalier_king_charles_spaniel', 'Cavalier King Charles Spaniel', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(422, 'animal', 'dog', 'breed', 'cavalier_king_charles_spaniel', 'Кавалер кінг чарльз спаніель', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(423, 'animal', 'dog', 'breed', 'cavapoo', 'Cavapoo', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(424, 'animal', 'dog', 'breed', 'cavapoo', 'Cavapoo', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(425, 'animal', 'dog', 'breed', 'chesapeake_bay_retriever', 'Chesapeake Bay Retriever', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(426, 'animal', 'dog', 'breed', 'chesapeake_bay_retriever', 'Чесапік бей ретривер', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(427, 'animal', 'dog', 'breed', 'chihuahua', 'Chihuahua', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(428, 'animal', 'dog', 'breed', 'chihuahua', 'Чіхуахуа', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(429, 'animal', 'dog', 'breed', 'chinese_crested_dog', 'Chinese Crested Dog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(430, 'animal', 'dog', 'breed', 'chinese_crested_dog', 'Китайська чубата собака', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(431, 'animal', 'dog', 'breed', 'chinese_foo_dog', 'Chinese Foo Dog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(432, 'animal', 'dog', 'breed', 'chinese_foo_dog', 'Chinese Foo Dog', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(433, 'animal', 'dog', 'breed', 'chinook', 'Chinook', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(434, 'animal', 'dog', 'breed', 'chinook', 'Chinook', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(435, 'animal', 'dog', 'breed', 'chiweenie', 'Chiweenie', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(436, 'animal', 'dog', 'breed', 'chiweenie', 'Chiweenie', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(437, 'animal', 'dog', 'breed', 'chocolate_labrador_retriever', 'Chocolate Labrador Retriever', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(438, 'animal', 'dog', 'breed', 'chocolate_labrador_retriever', 'Chocolate Labrador Retriever', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(439, 'animal', 'dog', 'breed', 'chow_chow', 'Chow Chow', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(440, 'animal', 'dog', 'breed', 'chow_chow', 'Чау-чау', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(441, 'animal', 'dog', 'breed', 'cirneco_dell_etna', 'Cirneco dell\'Etna', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(442, 'animal', 'dog', 'breed', 'cirneco_dell_etna', 'Cirneco dell\'Etna', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(443, 'animal', 'dog', 'breed', 'clumber_spaniel', 'Clumber Spaniel', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(444, 'animal', 'dog', 'breed', 'clumber_spaniel', 'Clumber Spaniel', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(445, 'animal', 'dog', 'breed', 'cockapoo', 'Cockapoo', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(446, 'animal', 'dog', 'breed', 'cockapoo', 'Cockapoo', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(447, 'animal', 'dog', 'breed', 'cocker_spaniel', 'Cocker Spaniel', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(448, 'animal', 'dog', 'breed', 'cocker_spaniel', 'Кокер спаніель', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(449, 'animal', 'dog', 'breed', 'collie', 'Collie', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(450, 'animal', 'dog', 'breed', 'collie', 'Коллі', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(451, 'animal', 'dog', 'breed', 'coonhound', 'Coonhound', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(452, 'animal', 'dog', 'breed', 'coonhound', 'Coonhound', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(453, 'animal', 'dog', 'breed', 'corgi', 'Corgi', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(454, 'animal', 'dog', 'breed', 'corgi', 'Коргі', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(455, 'animal', 'dog', 'breed', 'coton_de_tulear', 'Coton de Tulear', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(456, 'animal', 'dog', 'breed', 'coton_de_tulear', 'Coton de Tulear', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(457, 'animal', 'dog', 'breed', 'curly_coated_retriever', 'Curly-Coated Retriever', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(458, 'animal', 'dog', 'breed', 'curly_coated_retriever', 'Curly-Coated Retriever', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(459, 'animal', 'dog', 'breed', 'dachshund', 'Dachshund', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(460, 'animal', 'dog', 'breed', 'dachshund', 'Такса', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(461, 'animal', 'dog', 'breed', 'dalmatian', 'Dalmatian', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(462, 'animal', 'dog', 'breed', 'dalmatian', 'Далматинець', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(463, 'animal', 'dog', 'breed', 'dandie_dinmont_terrier', 'Dandie Dinmont Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(464, 'animal', 'dog', 'breed', 'dandie_dinmont_terrier', 'Dandie Dinmont Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(465, 'animal', 'dog', 'breed', 'doberman_pinscher', 'Doberman Pinscher', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(466, 'animal', 'dog', 'breed', 'doberman_pinscher', 'Доберман', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(467, 'animal', 'dog', 'breed', 'dogo_argentino', 'Dogo Argentino', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(468, 'animal', 'dog', 'breed', 'dogo_argentino', 'Dogo Argentino', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(469, 'animal', 'dog', 'breed', 'dogue_de_bordeaux', 'Dogue de Bordeaux', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(470, 'animal', 'dog', 'breed', 'dogue_de_bordeaux', 'Dogue de Bordeaux', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(471, 'animal', 'dog', 'breed', 'dutch_shepherd', 'Dutch Shepherd', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(472, 'animal', 'dog', 'breed', 'dutch_shepherd', 'Dutch Shepherd', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(473, 'animal', 'dog', 'breed', 'english_bulldog', 'English Bulldog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(474, 'animal', 'dog', 'breed', 'english_bulldog', 'Англійський бульдог', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(475, 'animal', 'dog', 'breed', 'english_cocker_spaniel', 'English Cocker Spaniel', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(476, 'animal', 'dog', 'breed', 'english_cocker_spaniel', 'Англійський кокер спаніель', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(477, 'animal', 'dog', 'breed', 'english_coonhound', 'English Coonhound', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(478, 'animal', 'dog', 'breed', 'english_coonhound', 'English Coonhound', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(479, 'animal', 'dog', 'breed', 'english_foxhound', 'English Foxhound', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(480, 'animal', 'dog', 'breed', 'english_foxhound', 'English Foxhound', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(481, 'animal', 'dog', 'breed', 'english_pointer', 'English Pointer', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(482, 'animal', 'dog', 'breed', 'english_pointer', 'English Pointer', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(483, 'animal', 'dog', 'breed', 'english_setter', 'English Setter', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(484, 'animal', 'dog', 'breed', 'english_setter', 'Англійський сетер', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(485, 'animal', 'dog', 'breed', 'english_shepherd', 'English Shepherd', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(486, 'animal', 'dog', 'breed', 'english_shepherd', 'English Shepherd', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(487, 'animal', 'dog', 'breed', 'english_springer_spaniel', 'English Springer Spaniel', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(488, 'animal', 'dog', 'breed', 'english_springer_spaniel', 'Англійський спрингер спаніель', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(489, 'animal', 'dog', 'breed', 'english_toy_spaniel', 'English Toy Spaniel', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(490, 'animal', 'dog', 'breed', 'english_toy_spaniel', 'English Toy Spaniel', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(491, 'animal', 'dog', 'breed', 'entlebucher', 'Entlebucher', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(492, 'animal', 'dog', 'breed', 'entlebucher', 'Entlebucher', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(493, 'animal', 'dog', 'breed', 'eskimo_dog', 'Eskimo Dog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(494, 'animal', 'dog', 'breed', 'eskimo_dog', 'Eskimo Dog', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(495, 'animal', 'dog', 'breed', 'feist', 'Feist', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(496, 'animal', 'dog', 'breed', 'feist', 'Feist', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(497, 'animal', 'dog', 'breed', 'field_spaniel', 'Field Spaniel', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(498, 'animal', 'dog', 'breed', 'field_spaniel', 'Field Spaniel', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(499, 'animal', 'dog', 'breed', 'fila_brasileiro', 'Fila Brasileiro', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(500, 'animal', 'dog', 'breed', 'fila_brasileiro', 'Fila Brasileiro', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(501, 'animal', 'dog', 'breed', 'finnish_lapphund', 'Finnish Lapphund', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(502, 'animal', 'dog', 'breed', 'finnish_lapphund', 'Finnish Lapphund', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(503, 'animal', 'dog', 'breed', 'finnish_spitz', 'Finnish Spitz', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(504, 'animal', 'dog', 'breed', 'finnish_spitz', 'Finnish Spitz', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(505, 'animal', 'dog', 'breed', 'flat_coated_retriever', 'Flat-Coated Retriever', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(506, 'animal', 'dog', 'breed', 'flat_coated_retriever', 'Flat-Coated Retriever', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(507, 'animal', 'dog', 'breed', 'fox_terrier', 'Fox Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(508, 'animal', 'dog', 'breed', 'fox_terrier', 'Fox Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(509, 'animal', 'dog', 'breed', 'foxhound', 'Foxhound', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(510, 'animal', 'dog', 'breed', 'foxhound', 'Foxhound', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(511, 'animal', 'dog', 'breed', 'french_bulldog', 'French Bulldog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(512, 'animal', 'dog', 'breed', 'french_bulldog', 'Французький бульдог', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(513, 'animal', 'dog', 'breed', 'galgo_spanish_greyhound', 'Galgo Spanish Greyhound', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(514, 'animal', 'dog', 'breed', 'galgo_spanish_greyhound', 'Galgo Spanish Greyhound', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(515, 'animal', 'dog', 'breed', 'german_pinscher', 'German Pinscher', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(516, 'animal', 'dog', 'breed', 'german_pinscher', 'German Pinscher', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(517, 'animal', 'dog', 'breed', 'german_shepherd_dog', 'German Shepherd Dog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(518, 'animal', 'dog', 'breed', 'german_shepherd_dog', 'Німецька вівчарка', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(519, 'animal', 'dog', 'breed', 'german_shorthaired_pointer', 'German Shorthaired Pointer', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(520, 'animal', 'dog', 'breed', 'german_shorthaired_pointer', 'Німецький короткошерстий пойнтер', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(521, 'animal', 'dog', 'breed', 'german_spitz', 'German Spitz', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(522, 'animal', 'dog', 'breed', 'german_spitz', 'German Spitz', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(523, 'animal', 'dog', 'breed', 'german_wirehaired_pointer', 'German Wirehaired Pointer', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(524, 'animal', 'dog', 'breed', 'german_wirehaired_pointer', 'German Wirehaired Pointer', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(525, 'animal', 'dog', 'breed', 'giant_schnauzer', 'Giant Schnauzer', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(526, 'animal', 'dog', 'breed', 'giant_schnauzer', 'Великий шнауцер', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(527, 'animal', 'dog', 'breed', 'glen_of_imaal_terrier', 'Glen of Imaal Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(528, 'animal', 'dog', 'breed', 'glen_of_imaal_terrier', 'Glen of Imaal Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(529, 'animal', 'dog', 'breed', 'golden_retriever', 'Golden Retriever', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(530, 'animal', 'dog', 'breed', 'golden_retriever', 'Золотистий ретривер', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(531, 'animal', 'dog', 'breed', 'goldendoodle', 'Goldendoodle', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(532, 'animal', 'dog', 'breed', 'goldendoodle', 'Goldendoodle', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(533, 'animal', 'dog', 'breed', 'gordon_setter', 'Gordon Setter', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(534, 'animal', 'dog', 'breed', 'gordon_setter', 'Gordon Setter', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(535, 'animal', 'dog', 'breed', 'great_dane', 'Great Dane', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(536, 'animal', 'dog', 'breed', 'great_dane', 'Німецький дог', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(537, 'animal', 'dog', 'breed', 'great_pyrenees', 'Great Pyrenees', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(538, 'animal', 'dog', 'breed', 'great_pyrenees', 'Піренейська гірська собака', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(539, 'animal', 'dog', 'breed', 'greater_swiss_mountain_dog', 'Greater Swiss Mountain Dog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(540, 'animal', 'dog', 'breed', 'greater_swiss_mountain_dog', 'Greater Swiss Mountain Dog', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(541, 'animal', 'dog', 'breed', 'greyhound', 'Greyhound', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(542, 'animal', 'dog', 'breed', 'greyhound', 'Грейхаунд', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(543, 'animal', 'dog', 'breed', 'hamiltonstovare', 'Hamiltonstovare', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(544, 'animal', 'dog', 'breed', 'hamiltonstovare', 'Hamiltonstovare', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(545, 'animal', 'dog', 'breed', 'harrier', 'Harrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(546, 'animal', 'dog', 'breed', 'harrier', 'Harrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(547, 'animal', 'dog', 'breed', 'havanese', 'Havanese', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(548, 'animal', 'dog', 'breed', 'havanese', 'Гаванський бішон', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(549, 'animal', 'dog', 'breed', 'hound', 'Hound', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(550, 'animal', 'dog', 'breed', 'hound', 'Hound', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(551, 'animal', 'dog', 'breed', 'hovawart', 'Hovawart', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(552, 'animal', 'dog', 'breed', 'hovawart', 'Hovawart', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(553, 'animal', 'dog', 'breed', 'husky', 'Husky', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(554, 'animal', 'dog', 'breed', 'husky', 'Хаскі', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(555, 'animal', 'dog', 'breed', 'ibizan_hound', 'Ibizan Hound', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(556, 'animal', 'dog', 'breed', 'ibizan_hound', 'Ibizan Hound', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(557, 'animal', 'dog', 'breed', 'icelandic_sheepdog', 'Icelandic Sheepdog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(558, 'animal', 'dog', 'breed', 'icelandic_sheepdog', 'Icelandic Sheepdog', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(559, 'animal', 'dog', 'breed', 'illyrian_sheepdog', 'Illyrian Sheepdog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(560, 'animal', 'dog', 'breed', 'illyrian_sheepdog', 'Illyrian Sheepdog', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(561, 'animal', 'dog', 'breed', 'irish_setter', 'Irish Setter', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(562, 'animal', 'dog', 'breed', 'irish_setter', 'Ірландський сетер', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(563, 'animal', 'dog', 'breed', 'irish_terrier', 'Irish Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(564, 'animal', 'dog', 'breed', 'irish_terrier', 'Irish Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(565, 'animal', 'dog', 'breed', 'irish_water_spaniel', 'Irish Water Spaniel', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(566, 'animal', 'dog', 'breed', 'irish_water_spaniel', 'Irish Water Spaniel', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(567, 'animal', 'dog', 'breed', 'irish_wolfhound', 'Irish Wolfhound', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(568, 'animal', 'dog', 'breed', 'irish_wolfhound', 'Ірландський вовкодав', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(569, 'animal', 'dog', 'breed', 'italian_greyhound', 'Italian Greyhound', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(570, 'animal', 'dog', 'breed', 'italian_greyhound', 'Італійська борзая', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(571, 'animal', 'dog', 'breed', 'jack_russell_terrier', 'Jack Russell Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(572, 'animal', 'dog', 'breed', 'jack_russell_terrier', 'Джек рассел тер\'єр', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(573, 'animal', 'dog', 'breed', 'japanese_chin', 'Japanese Chin', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(574, 'animal', 'dog', 'breed', 'japanese_chin', 'Японський хін', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(575, 'animal', 'dog', 'breed', 'jindo', 'Jindo', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(576, 'animal', 'dog', 'breed', 'jindo', 'Jindo', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(577, 'animal', 'dog', 'breed', 'kai_dog', 'Kai Dog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(578, 'animal', 'dog', 'breed', 'kai_dog', 'Kai Dog', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(579, 'animal', 'dog', 'breed', 'karelian_bear_dog', 'Karelian Bear Dog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(580, 'animal', 'dog', 'breed', 'karelian_bear_dog', 'Karelian Bear Dog', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(581, 'animal', 'dog', 'breed', 'keeshond', 'Keeshond', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(582, 'animal', 'dog', 'breed', 'keeshond', 'Keeshond', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(583, 'animal', 'dog', 'breed', 'kerry_blue_terrier', 'Kerry Blue Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(584, 'animal', 'dog', 'breed', 'kerry_blue_terrier', 'Kerry Blue Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(585, 'animal', 'dog', 'breed', 'kishu', 'Kishu', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(586, 'animal', 'dog', 'breed', 'kishu', 'Kishu', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(587, 'animal', 'dog', 'breed', 'klee_kai', 'Klee Kai', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(588, 'animal', 'dog', 'breed', 'klee_kai', 'Klee Kai', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(589, 'animal', 'dog', 'breed', 'komondor', 'Komondor', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(590, 'animal', 'dog', 'breed', 'komondor', 'Komondor', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(591, 'animal', 'dog', 'breed', 'kuvasz', 'Kuvasz', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(592, 'animal', 'dog', 'breed', 'kuvasz', 'Kuvasz', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(593, 'animal', 'dog', 'breed', 'kyi_leo', 'Kyi Leo', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(594, 'animal', 'dog', 'breed', 'kyi_leo', 'Kyi Leo', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(595, 'animal', 'dog', 'breed', 'labradoodle', 'Labradoodle', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(596, 'animal', 'dog', 'breed', 'labradoodle', 'Labradoodle', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(597, 'animal', 'dog', 'breed', 'labrador_retriever', 'Labrador Retriever', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(598, 'animal', 'dog', 'breed', 'labrador_retriever', 'Лабрадор ретривер', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(599, 'animal', 'dog', 'breed', 'lakeland_terrier', 'Lakeland Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(600, 'animal', 'dog', 'breed', 'lakeland_terrier', 'Lakeland Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(601, 'animal', 'dog', 'breed', 'lancashire_heeler', 'Lancashire Heeler', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(602, 'animal', 'dog', 'breed', 'lancashire_heeler', 'Lancashire Heeler', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(603, 'animal', 'dog', 'breed', 'leonberger', 'Leonberger', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(604, 'animal', 'dog', 'breed', 'leonberger', 'Leonberger', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(605, 'animal', 'dog', 'breed', 'lhasa_apso', 'Lhasa Apso', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(606, 'animal', 'dog', 'breed', 'lhasa_apso', 'Lhasa Apso', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(607, 'animal', 'dog', 'breed', 'lowchen', 'Lowchen', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(608, 'animal', 'dog', 'breed', 'lowchen', 'Lowchen', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(609, 'animal', 'dog', 'breed', 'lurcher', 'Lurcher', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(610, 'animal', 'dog', 'breed', 'lurcher', 'Lurcher', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(611, 'animal', 'dog', 'breed', 'maltese', 'Maltese', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(612, 'animal', 'dog', 'breed', 'maltese', 'Мальтійська болонка', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(613, 'animal', 'dog', 'breed', 'maltipoo', 'Maltipoo', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(614, 'animal', 'dog', 'breed', 'maltipoo', 'Maltipoo', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(615, 'animal', 'dog', 'breed', 'manchester_terrier', 'Manchester Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(616, 'animal', 'dog', 'breed', 'manchester_terrier', 'Manchester Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(617, 'animal', 'dog', 'breed', 'maremma_sheepdog', 'Maremma Sheepdog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(618, 'animal', 'dog', 'breed', 'maremma_sheepdog', 'Maremma Sheepdog', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(619, 'animal', 'dog', 'breed', 'mastiff', 'Mastiff', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(620, 'animal', 'dog', 'breed', 'mastiff', 'Мастиф', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(621, 'animal', 'dog', 'breed', 'mcnab', 'McNab', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(622, 'animal', 'dog', 'breed', 'mcnab', 'McNab', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(623, 'animal', 'dog', 'breed', 'miniature_bull_terrier', 'Miniature Bull Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(624, 'animal', 'dog', 'breed', 'miniature_bull_terrier', 'Miniature Bull Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(625, 'animal', 'dog', 'breed', 'miniature_dachshund', 'Miniature Dachshund', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(626, 'animal', 'dog', 'breed', 'miniature_dachshund', 'Мініатюрна такса', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(627, 'animal', 'dog', 'breed', 'miniature_pinscher', 'Miniature Pinscher', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(628, 'animal', 'dog', 'breed', 'miniature_pinscher', 'Мініатюрний пінчер', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(629, 'animal', 'dog', 'breed', 'miniature_poodle', 'Miniature Poodle', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(630, 'animal', 'dog', 'breed', 'miniature_poodle', 'Мініатюрний пудель', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(631, 'animal', 'dog', 'breed', 'miniature_schnauzer', 'Miniature Schnauzer', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(632, 'animal', 'dog', 'breed', 'miniature_schnauzer', 'Мініатюрний шнауцер', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(633, 'animal', 'dog', 'breed', 'mixed_breed', 'Mixed Breed', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(634, 'animal', 'dog', 'breed', 'mixed_breed', 'Метис', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(635, 'animal', 'dog', 'breed', 'morkie', 'Morkie', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(636, 'animal', 'dog', 'breed', 'morkie', 'Morkie', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(637, 'animal', 'dog', 'breed', 'mountain_cur', 'Mountain Cur', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(638, 'animal', 'dog', 'breed', 'mountain_cur', 'Mountain Cur', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(639, 'animal', 'dog', 'breed', 'mountain_dog', 'Mountain Dog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(640, 'animal', 'dog', 'breed', 'mountain_dog', 'Mountain Dog', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(641, 'animal', 'dog', 'breed', 'munsterlander', 'Munsterlander', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(642, 'animal', 'dog', 'breed', 'munsterlander', 'Munsterlander', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(643, 'animal', 'dog', 'breed', 'neapolitan_mastiff', 'Neapolitan Mastiff', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(644, 'animal', 'dog', 'breed', 'neapolitan_mastiff', 'Neapolitan Mastiff', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(645, 'animal', 'dog', 'breed', 'new_guinea_singing_dog', 'New Guinea Singing Dog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(646, 'animal', 'dog', 'breed', 'new_guinea_singing_dog', 'New Guinea Singing Dog', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(647, 'animal', 'dog', 'breed', 'newfoundland_dog', 'Newfoundland Dog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(648, 'animal', 'dog', 'breed', 'newfoundland_dog', 'Ньюфаундленд', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(649, 'animal', 'dog', 'breed', 'norfolk_terrier', 'Norfolk Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(650, 'animal', 'dog', 'breed', 'norfolk_terrier', 'Norfolk Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(651, 'animal', 'dog', 'breed', 'norwegian_buhund', 'Norwegian Buhund', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(652, 'animal', 'dog', 'breed', 'norwegian_buhund', 'Norwegian Buhund', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(653, 'animal', 'dog', 'breed', 'norwegian_elkhound', 'Norwegian Elkhound', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(654, 'animal', 'dog', 'breed', 'norwegian_elkhound', 'Norwegian Elkhound', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(655, 'animal', 'dog', 'breed', 'norwegian_lundehund', 'Norwegian Lundehund', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(656, 'animal', 'dog', 'breed', 'norwegian_lundehund', 'Norwegian Lundehund', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(657, 'animal', 'dog', 'breed', 'norwich_terrier', 'Norwich Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(658, 'animal', 'dog', 'breed', 'norwich_terrier', 'Norwich Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(659, 'animal', 'dog', 'breed', 'nova_scotia_duck_tolling_retriever', 'Nova Scotia Duck Tolling Retriever', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(660, 'animal', 'dog', 'breed', 'nova_scotia_duck_tolling_retriever', 'Nova Scotia Duck Tolling Retriever', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(661, 'animal', 'dog', 'breed', 'old_english_sheepdog', 'Old English Sheepdog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(662, 'animal', 'dog', 'breed', 'old_english_sheepdog', 'Староанглійська вівчарка', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(663, 'animal', 'dog', 'breed', 'otterhound', 'Otterhound', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(664, 'animal', 'dog', 'breed', 'otterhound', 'Otterhound', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(665, 'animal', 'dog', 'breed', 'papillon', 'Papillon', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(666, 'animal', 'dog', 'breed', 'papillon', 'Папільйон', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(667, 'animal', 'dog', 'breed', 'parson_russell_terrier', 'Parson Russell Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(668, 'animal', 'dog', 'breed', 'parson_russell_terrier', 'Parson Russell Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(669, 'animal', 'dog', 'breed', 'patterdale_terrier_fell_terrier', 'Patterdale Terrier / Fell Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(670, 'animal', 'dog', 'breed', 'patterdale_terrier_fell_terrier', 'Patterdale Terrier / Fell Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(671, 'animal', 'dog', 'breed', 'pekingese', 'Pekingese', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(672, 'animal', 'dog', 'breed', 'pekingese', 'Пекінес', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(673, 'animal', 'dog', 'breed', 'pembroke_welsh_corgi', 'Pembroke Welsh Corgi', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(674, 'animal', 'dog', 'breed', 'pembroke_welsh_corgi', 'Вельш коргі пемброк', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(675, 'animal', 'dog', 'breed', 'peruvian_inca_orchid', 'Peruvian Inca Orchid', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(676, 'animal', 'dog', 'breed', 'peruvian_inca_orchid', 'Peruvian Inca Orchid', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(677, 'animal', 'dog', 'breed', 'petit_basset_griffon_vendeen', 'Petit Basset Griffon Vendeen', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(678, 'animal', 'dog', 'breed', 'petit_basset_griffon_vendeen', 'Petit Basset Griffon Vendeen', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(679, 'animal', 'dog', 'breed', 'pharaoh_hound', 'Pharaoh Hound', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(680, 'animal', 'dog', 'breed', 'pharaoh_hound', 'Pharaoh Hound', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(681, 'animal', 'dog', 'breed', 'pit_bull_terrier', 'Pit Bull Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(682, 'animal', 'dog', 'breed', 'pit_bull_terrier', 'Піт буль тер\'єр', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(683, 'animal', 'dog', 'breed', 'plott_hound', 'Plott Hound', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(684, 'animal', 'dog', 'breed', 'plott_hound', 'Plott Hound', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(685, 'animal', 'dog', 'breed', 'pointer', 'Pointer', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(686, 'animal', 'dog', 'breed', 'pointer', 'Пойнтер', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(687, 'animal', 'dog', 'breed', 'polish_lowland_sheepdog', 'Polish Lowland Sheepdog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(688, 'animal', 'dog', 'breed', 'polish_lowland_sheepdog', 'Polish Lowland Sheepdog', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(689, 'animal', 'dog', 'breed', 'pomeranian', 'Pomeranian', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(690, 'animal', 'dog', 'breed', 'pomeranian', 'Померанський шпіц', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(691, 'animal', 'dog', 'breed', 'pomsky', 'Pomsky', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(692, 'animal', 'dog', 'breed', 'pomsky', 'Pomsky', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(693, 'animal', 'dog', 'breed', 'poodle', 'Poodle', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(694, 'animal', 'dog', 'breed', 'poodle', 'Пудель', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(695, 'animal', 'dog', 'breed', 'portuguese_podengo', 'Portuguese Podengo', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(696, 'animal', 'dog', 'breed', 'portuguese_podengo', 'Portuguese Podengo', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(697, 'animal', 'dog', 'breed', 'portuguese_water_dog', 'Portuguese Water Dog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(698, 'animal', 'dog', 'breed', 'portuguese_water_dog', 'Португальська водяна собака', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(699, 'animal', 'dog', 'breed', 'presa_canario', 'Presa Canario', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(700, 'animal', 'dog', 'breed', 'presa_canario', 'Presa Canario', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(701, 'animal', 'dog', 'breed', 'pug', 'Pug', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(702, 'animal', 'dog', 'breed', 'pug', 'Мопс', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(703, 'animal', 'dog', 'breed', 'puggle', 'Puggle', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(704, 'animal', 'dog', 'breed', 'puggle', 'Puggle', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(705, 'animal', 'dog', 'breed', 'puli', 'Puli', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(706, 'animal', 'dog', 'breed', 'puli', 'Puli', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(707, 'animal', 'dog', 'breed', 'pumi', 'Pumi', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(708, 'animal', 'dog', 'breed', 'pumi', 'Pumi', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(709, 'animal', 'dog', 'breed', 'pyrenean_shepherd', 'Pyrenean Shepherd', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(710, 'animal', 'dog', 'breed', 'pyrenean_shepherd', 'Pyrenean Shepherd', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(711, 'animal', 'dog', 'breed', 'rat_terrier', 'Rat Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(712, 'animal', 'dog', 'breed', 'rat_terrier', 'Rat Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(713, 'animal', 'dog', 'breed', 'redbone_coonhound', 'Redbone Coonhound', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(714, 'animal', 'dog', 'breed', 'redbone_coonhound', 'Redbone Coonhound', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(715, 'animal', 'dog', 'breed', 'retriever', 'Retriever', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(716, 'animal', 'dog', 'breed', 'retriever', 'Retriever', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(717, 'animal', 'dog', 'breed', 'rhodesian_ridgeback', 'Rhodesian Ridgeback', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(718, 'animal', 'dog', 'breed', 'rhodesian_ridgeback', 'Родезійський риджбек', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(719, 'animal', 'dog', 'breed', 'rottweiler', 'Rottweiler', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(720, 'animal', 'dog', 'breed', 'rottweiler', 'Ротвейлер', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(721, 'animal', 'dog', 'breed', 'rough_collie', 'Rough Collie', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(722, 'animal', 'dog', 'breed', 'rough_collie', 'Довгошерстий коллі', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(723, 'animal', 'dog', 'breed', 'saint_bernard', 'Saint Bernard', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(724, 'animal', 'dog', 'breed', 'saint_bernard', 'Сенбернар', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(725, 'animal', 'dog', 'breed', 'saluki', 'Saluki', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(726, 'animal', 'dog', 'breed', 'saluki', 'Saluki', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(727, 'animal', 'dog', 'breed', 'samoyed', 'Samoyed', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(728, 'animal', 'dog', 'breed', 'samoyed', 'Самоїд', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(729, 'animal', 'dog', 'breed', 'sarplaninac', 'Sarplaninac', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(730, 'animal', 'dog', 'breed', 'sarplaninac', 'Sarplaninac', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(731, 'animal', 'dog', 'breed', 'schipperke', 'Schipperke', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(732, 'animal', 'dog', 'breed', 'schipperke', 'Schipperke', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(733, 'animal', 'dog', 'breed', 'schnauzer', 'Schnauzer', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(734, 'animal', 'dog', 'breed', 'schnauzer', 'Шнауцер', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(735, 'animal', 'dog', 'breed', 'schnoodle', 'Schnoodle', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(736, 'animal', 'dog', 'breed', 'schnoodle', 'Schnoodle', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(737, 'animal', 'dog', 'breed', 'scottish_deerhound', 'Scottish Deerhound', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(738, 'animal', 'dog', 'breed', 'scottish_deerhound', 'Scottish Deerhound', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(739, 'animal', 'dog', 'breed', 'scottish_terrier', 'Scottish Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(740, 'animal', 'dog', 'breed', 'scottish_terrier', 'Скотч тер\'єр', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(741, 'animal', 'dog', 'breed', 'sealyham_terrier', 'Sealyham Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(742, 'animal', 'dog', 'breed', 'sealyham_terrier', 'Sealyham Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(743, 'animal', 'dog', 'breed', 'setter', 'Setter', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(744, 'animal', 'dog', 'breed', 'setter', 'Setter', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(745, 'animal', 'dog', 'breed', 'shar_pei', 'Shar-Pei', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(746, 'animal', 'dog', 'breed', 'shar_pei', 'Шар-пей', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(747, 'animal', 'dog', 'breed', 'sheep_dog', 'Sheep Dog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(748, 'animal', 'dog', 'breed', 'sheep_dog', 'Sheep Dog', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(749, 'animal', 'dog', 'breed', 'sheepadoodle', 'Sheepadoodle', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(750, 'animal', 'dog', 'breed', 'sheepadoodle', 'Sheepadoodle', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(751, 'animal', 'dog', 'breed', 'shepherd', 'Shepherd', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(752, 'animal', 'dog', 'breed', 'shepherd', 'Shepherd', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(753, 'animal', 'dog', 'breed', 'shetland_sheepdog_sheltie', 'Shetland Sheepdog / Sheltie', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(754, 'animal', 'dog', 'breed', 'shetland_sheepdog_sheltie', 'Шетландська вівчарка', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(755, 'animal', 'dog', 'breed', 'shiba_inu', 'Shiba Inu', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(756, 'animal', 'dog', 'breed', 'shiba_inu', 'Сіба-іну', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(757, 'animal', 'dog', 'breed', 'shih_poo', 'Shih poo', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(758, 'animal', 'dog', 'breed', 'shih_poo', 'Shih poo', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(759, 'animal', 'dog', 'breed', 'shih_tzu', 'Shih Tzu', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(760, 'animal', 'dog', 'breed', 'shih_tzu', 'Ши-тцу', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(761, 'animal', 'dog', 'breed', 'shollie', 'Shollie', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(762, 'animal', 'dog', 'breed', 'shollie', 'Shollie', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(763, 'animal', 'dog', 'breed', 'siberian_husky', 'Siberian Husky', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(764, 'animal', 'dog', 'breed', 'siberian_husky', 'Сибірський хаскі', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(765, 'animal', 'dog', 'breed', 'silky_terrier', 'Silky Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(766, 'animal', 'dog', 'breed', 'silky_terrier', 'Silky Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(767, 'animal', 'dog', 'breed', 'skye_terrier', 'Skye Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(768, 'animal', 'dog', 'breed', 'skye_terrier', 'Skye Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(769, 'animal', 'dog', 'breed', 'sloughi', 'Sloughi', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(770, 'animal', 'dog', 'breed', 'sloughi', 'Sloughi', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(771, 'animal', 'dog', 'breed', 'smooth_collie', 'Smooth Collie', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(772, 'animal', 'dog', 'breed', 'smooth_collie', 'Smooth Collie', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(773, 'animal', 'dog', 'breed', 'smooth_fox_terrier', 'Smooth Fox Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(774, 'animal', 'dog', 'breed', 'smooth_fox_terrier', 'Smooth Fox Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(775, 'animal', 'dog', 'breed', 'south_russian_ovtcharka', 'South Russian Ovtcharka', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(776, 'animal', 'dog', 'breed', 'south_russian_ovtcharka', 'South Russian Ovtcharka', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(777, 'animal', 'dog', 'breed', 'spaniel', 'Spaniel', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(778, 'animal', 'dog', 'breed', 'spaniel', 'Spaniel', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(779, 'animal', 'dog', 'breed', 'spanish_water_dog', 'Spanish Water Dog', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(780, 'animal', 'dog', 'breed', 'spanish_water_dog', 'Spanish Water Dog', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(781, 'animal', 'dog', 'breed', 'spinone_italiano', 'Spinone Italiano', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(782, 'animal', 'dog', 'breed', 'spinone_italiano', 'Spinone Italiano', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(783, 'animal', 'dog', 'breed', 'spitz', 'Spitz', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(784, 'animal', 'dog', 'breed', 'spitz', 'Spitz', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(785, 'animal', 'dog', 'breed', 'staffordshire_bull_terrier', 'Staffordshire Bull Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(786, 'animal', 'dog', 'breed', 'staffordshire_bull_terrier', 'Staffordshire Bull Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(787, 'animal', 'dog', 'breed', 'standard_poodle', 'Standard Poodle', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(788, 'animal', 'dog', 'breed', 'standard_poodle', 'Стандартний пудель', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(789, 'animal', 'dog', 'breed', 'standard_schnauzer', 'Standard Schnauzer', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(790, 'animal', 'dog', 'breed', 'standard_schnauzer', 'Standard Schnauzer', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(791, 'animal', 'dog', 'breed', 'sussex_spaniel', 'Sussex Spaniel', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(792, 'animal', 'dog', 'breed', 'sussex_spaniel', 'Sussex Spaniel', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(793, 'animal', 'dog', 'breed', 'swedish_vallhund', 'Swedish Vallhund', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(794, 'animal', 'dog', 'breed', 'swedish_vallhund', 'Swedish Vallhund', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(795, 'animal', 'dog', 'breed', 'tennessee_treeing_brindle', 'Tennessee Treeing Brindle', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(796, 'animal', 'dog', 'breed', 'tennessee_treeing_brindle', 'Tennessee Treeing Brindle', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(797, 'animal', 'dog', 'breed', 'terrier', 'Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(798, 'animal', 'dog', 'breed', 'terrier', 'Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(799, 'animal', 'dog', 'breed', 'thai_ridgeback', 'Thai Ridgeback', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(800, 'animal', 'dog', 'breed', 'thai_ridgeback', 'Thai Ridgeback', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(801, 'animal', 'dog', 'breed', 'tibetan_mastiff', 'Tibetan Mastiff', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(802, 'animal', 'dog', 'breed', 'tibetan_mastiff', 'Tibetan Mastiff', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(803, 'animal', 'dog', 'breed', 'tibetan_spaniel', 'Tibetan Spaniel', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(804, 'animal', 'dog', 'breed', 'tibetan_spaniel', 'Tibetan Spaniel', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(805, 'animal', 'dog', 'breed', 'tibetan_terrier', 'Tibetan Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(806, 'animal', 'dog', 'breed', 'tibetan_terrier', 'Tibetan Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(807, 'animal', 'dog', 'breed', 'tosa_inu', 'Tosa Inu', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(808, 'animal', 'dog', 'breed', 'tosa_inu', 'Tosa Inu', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(809, 'animal', 'dog', 'breed', 'toy_fox_terrier', 'Toy Fox Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(810, 'animal', 'dog', 'breed', 'toy_fox_terrier', 'Toy Fox Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(811, 'animal', 'dog', 'breed', 'toy_manchester_terrier', 'Toy Manchester Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(812, 'animal', 'dog', 'breed', 'toy_manchester_terrier', 'Toy Manchester Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(813, 'animal', 'dog', 'breed', 'treeing_walker_coonhound', 'Treeing Walker Coonhound', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(814, 'animal', 'dog', 'breed', 'treeing_walker_coonhound', 'Treeing Walker Coonhound', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(815, 'animal', 'dog', 'breed', 'vizsla', 'Vizsla', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(816, 'animal', 'dog', 'breed', 'vizsla', 'Візла', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(817, 'animal', 'dog', 'breed', 'weimaraner', 'Weimaraner', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(818, 'animal', 'dog', 'breed', 'weimaraner', 'Веймаранер', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(819, 'animal', 'dog', 'breed', 'welsh_springer_spaniel', 'Welsh Springer Spaniel', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(820, 'animal', 'dog', 'breed', 'welsh_springer_spaniel', 'Welsh Springer Spaniel', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(821, 'animal', 'dog', 'breed', 'welsh_terrier', 'Welsh Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(822, 'animal', 'dog', 'breed', 'welsh_terrier', 'Вельш тер\'єр', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(823, 'animal', 'dog', 'breed', 'west_highland_white_terrier_westie', 'West Highland White Terrier / Westie', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(824, 'animal', 'dog', 'breed', 'west_highland_white_terrier_westie', 'Вест хайленд вайт тер\'єр', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(825, 'animal', 'dog', 'breed', 'wheaten_terrier', 'Wheaten Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(826, 'animal', 'dog', 'breed', 'wheaten_terrier', 'Wheaten Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(827, 'animal', 'dog', 'breed', 'whippet', 'Whippet', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(828, 'animal', 'dog', 'breed', 'whippet', 'Віппет', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(829, 'animal', 'dog', 'breed', 'white_german_shepherd', 'White German Shepherd', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(830, 'animal', 'dog', 'breed', 'white_german_shepherd', 'Біла німецька вівчарка', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(831, 'animal', 'dog', 'breed', 'wire_fox_terrier', 'Wire Fox Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(832, 'animal', 'dog', 'breed', 'wire_fox_terrier', 'Wire Fox Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(833, 'animal', 'dog', 'breed', 'wirehaired_dachshund', 'Wirehaired Dachshund', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(834, 'animal', 'dog', 'breed', 'wirehaired_dachshund', 'Wirehaired Dachshund', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(835, 'animal', 'dog', 'breed', 'wirehaired_pointing_griffon', 'Wirehaired Pointing Griffon', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(836, 'animal', 'dog', 'breed', 'wirehaired_pointing_griffon', 'Wirehaired Pointing Griffon', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(837, 'animal', 'dog', 'breed', 'wirehaired_terrier', 'Wirehaired Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(838, 'animal', 'dog', 'breed', 'wirehaired_terrier', 'Wirehaired Terrier', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(839, 'animal', 'dog', 'breed', 'xoloitzcuintli_mexican_hairless', 'Xoloitzcuintli / Mexican Hairless', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(840, 'animal', 'dog', 'breed', 'xoloitzcuintli_mexican_hairless', 'Xoloitzcuintli / Mexican Hairless', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(841, 'animal', 'dog', 'breed', 'yellow_labrador_retriever', 'Yellow Labrador Retriever', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(842, 'animal', 'dog', 'breed', 'yellow_labrador_retriever', 'Жовтий лабрадор ретривер', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(843, 'animal', 'dog', 'breed', 'yorkshire_terrier', 'Yorkshire Terrier', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(844, 'animal', 'dog', 'breed', 'yorkshire_terrier', 'Йоркширський тер\'єр', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(845, 'animal', 'bird', 'breed', 'african_grey', 'African Grey', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(846, 'animal', 'bird', 'breed', 'african_grey', 'Сірий африканський папуга', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(847, 'animal', 'bird', 'breed', 'amazon', 'Amazon', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(848, 'animal', 'bird', 'breed', 'amazon', 'Амазон', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(849, 'animal', 'bird', 'breed', 'brotogeris', 'Brotogeris', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(850, 'animal', 'bird', 'breed', 'brotogeris', 'Бротогеріс', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(851, 'animal', 'bird', 'breed', 'budgie_budgerigar', 'Budgie / Budgerigar', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(852, 'animal', 'bird', 'breed', 'budgie_budgerigar', 'Хвилястий папуга', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(853, 'animal', 'bird', 'breed', 'button_quail', 'Button-Quail', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(854, 'animal', 'bird', 'breed', 'button_quail', 'Трипершка', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(855, 'animal', 'bird', 'breed', 'caique', 'Caique', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(856, 'animal', 'bird', 'breed', 'caique', 'Кайк', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(857, 'animal', 'bird', 'breed', 'canary', 'Canary', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(858, 'animal', 'bird', 'breed', 'canary', 'Канарка', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(859, 'animal', 'bird', 'breed', 'chicken', 'Chicken', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(860, 'animal', 'bird', 'breed', 'chicken', 'Курка', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(861, 'animal', 'bird', 'breed', 'cockatiel', 'Cockatiel', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(862, 'animal', 'bird', 'breed', 'cockatiel', 'Корелла', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(863, 'animal', 'bird', 'breed', 'cockatoo', 'Cockatoo', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(864, 'animal', 'bird', 'breed', 'cockatoo', 'Какаду', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(865, 'animal', 'bird', 'breed', 'conure', 'Conure', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(866, 'animal', 'bird', 'breed', 'conure', 'Конура', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(867, 'animal', 'bird', 'breed', 'dove', 'Dove', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(868, 'animal', 'bird', 'breed', 'dove', 'Голуб', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(869, 'animal', 'bird', 'breed', 'duck', 'Duck', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(870, 'animal', 'bird', 'breed', 'duck', 'Качка', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(871, 'animal', 'bird', 'breed', 'eclectus', 'Eclectus', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(872, 'animal', 'bird', 'breed', 'eclectus', 'Благородний папуга', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(873, 'animal', 'bird', 'breed', 'emu', 'Emu', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(874, 'animal', 'bird', 'breed', 'emu', 'Ему', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(875, 'animal', 'bird', 'breed', 'finch', 'Finch', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(876, 'animal', 'bird', 'breed', 'finch', 'Зяблик', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(877, 'animal', 'bird', 'breed', 'goose', 'Goose', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(878, 'animal', 'bird', 'breed', 'goose', 'Гуска', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(879, 'animal', 'bird', 'breed', 'guinea_fowl', 'Guinea Fowl', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(880, 'animal', 'bird', 'breed', 'guinea_fowl', 'Цесарка', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(881, 'animal', 'bird', 'breed', 'kakariki', 'Kakariki', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(882, 'animal', 'bird', 'breed', 'kakariki', 'Какарикі', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(883, 'animal', 'bird', 'breed', 'lory_lorikeet', 'Lory / Lorikeet', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(884, 'animal', 'bird', 'breed', 'lory_lorikeet', 'Лорі', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(885, 'animal', 'bird', 'breed', 'lovebird', 'Lovebird', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(886, 'animal', 'bird', 'breed', 'lovebird', 'Нерозлучник', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(887, 'animal', 'bird', 'breed', 'macaw', 'Macaw', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(888, 'animal', 'bird', 'breed', 'macaw', 'Ара', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(889, 'animal', 'bird', 'breed', 'ostrich', 'Ostrich', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(890, 'animal', 'bird', 'breed', 'ostrich', 'Страус', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(891, 'animal', 'bird', 'breed', 'parakeet_other', 'Parakeet (Other)', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(892, 'animal', 'bird', 'breed', 'parakeet_other', 'Папуга (інший)', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(893, 'animal', 'bird', 'breed', 'parrot_other', 'Parrot (Other)', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(894, 'animal', 'bird', 'breed', 'parrot_other', 'Папуга (інший)', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(895, 'animal', 'bird', 'breed', 'parrotlet', 'Parrotlet', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(896, 'animal', 'bird', 'breed', 'parrotlet', 'Воробйиний папуга', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(897, 'animal', 'bird', 'breed', 'peacock_peafowl', 'Peacock / Peafowl', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(898, 'animal', 'bird', 'breed', 'peacock_peafowl', 'Павич', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(899, 'animal', 'bird', 'breed', 'pheasant', 'Pheasant', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(900, 'animal', 'bird', 'breed', 'pheasant', 'Фазан', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(901, 'animal', 'bird', 'breed', 'pigeon', 'Pigeon', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(902, 'animal', 'bird', 'breed', 'pigeon', 'Голуб', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(903, 'animal', 'bird', 'breed', 'pionus', 'Pionus', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(904, 'animal', 'bird', 'breed', 'pionus', 'Піонус', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(905, 'animal', 'bird', 'breed', 'poicephalus_senegal', 'Poicephalus / Senegal', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(906, 'animal', 'bird', 'breed', 'poicephalus_senegal', 'Сенегальський папуга', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(907, 'animal', 'bird', 'breed', 'quail', 'Quail', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(908, 'animal', 'bird', 'breed', 'quail', 'Перепілка', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(909, 'animal', 'bird', 'breed', 'quaker_parakeet', 'Quaker Parakeet', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(910, 'animal', 'bird', 'breed', 'quaker_parakeet', 'Квакер', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(911, 'animal', 'bird', 'breed', 'rhea', 'Rhea', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(912, 'animal', 'bird', 'breed', 'rhea', 'Нанду', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(913, 'animal', 'bird', 'breed', 'ringneck_psittacula', 'Ringneck / Psittacula', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(914, 'animal', 'bird', 'breed', 'ringneck_psittacula', 'Ожерелковий папуга', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(915, 'animal', 'bird', 'breed', 'rosella', 'Rosella', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(916, 'animal', 'bird', 'breed', 'rosella', 'Розелла', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(917, 'animal', 'bird', 'breed', 'swan', 'Swan', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(918, 'animal', 'bird', 'breed', 'swan', 'Лебідь', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(919, 'animal', 'bird', 'breed', 'toucan', 'Toucan', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(920, 'animal', 'bird', 'breed', 'toucan', 'Тукан', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(921, 'animal', 'bird', 'breed', 'turkey', 'Turkey', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(922, 'animal', 'bird', 'breed', 'turkey', 'Індичка', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(923, 'animal', 'status', 'status', 'adoptable', 'Доступна для прилаштування', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(924, 'animal', 'status', 'status', 'adoptable', 'Adoptable', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(925, 'animal', 'status', 'status', 'adopted', 'Прилаштована', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(926, 'animal', 'status', 'status', 'adopted', 'Adopted', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(927, 'animal', 'status', 'status', 'hold', 'На утриманні', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(928, 'animal', 'status', 'status', 'hold', 'Hold', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(929, 'animal', 'status', 'status', 'found', 'Знайдена', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(930, 'animal', 'status', 'status', 'found', 'Found', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(931, 'animal', 'status', 'status', 'removed', 'Видалена', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(932, 'animal', 'status', 'status', 'removed', 'Removed', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(933, 'animal', 'age', 'age', 'baby', 'Малюк', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(934, 'animal', 'age', 'age', 'baby', 'Baby', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(935, 'animal', 'age', 'age', 'young', 'Молодий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(936, 'animal', 'age', 'age', 'young', 'Young', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(937, 'animal', 'age', 'age', 'adult', 'Дорослий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(938, 'animal', 'age', 'age', 'adult', 'Adult', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(939, 'animal', 'age', 'age', 'senior', 'Літній', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(940, 'animal', 'age', 'age', 'senior', 'Senior', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(941, 'animal', 'all', 'size', 'small', 'Маленький', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(942, 'animal', 'all', 'size', 'small', 'Small', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(943, 'animal', 'all', 'size', 'medium', 'Середній', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(944, 'animal', 'all', 'size', 'medium', 'Medium', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(945, 'animal', 'all', 'size', 'large', 'Великий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(946, 'animal', 'all', 'size', 'large', 'Large', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(947, 'animal', 'all', 'size', 'xlarge', 'Дуже великий', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(948, 'animal', 'all', 'size', 'xlarge', 'Extra Large', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(949, 'organization', 'type', 'org_type', 'individual', 'Приватна особа', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(950, 'organization', 'type', 'org_type', 'individual', 'Individual', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(951, 'organization', 'type', 'org_type', 'shelter', 'Притулок', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(952, 'organization', 'type', 'org_type', 'shelter', 'Shelter', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(953, 'organization', 'type', 'org_type', 'rescue', 'Організація порятунку', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(954, 'organization', 'type', 'org_type', 'rescue', 'Rescue Organization', 'en');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(955, 'organization', 'type', 'org_type', 'vet_clinic', 'Ветеринарна клініка', 'ua');
INSERT INTO `wp_lapki_attributes` (`id`, `entity`, `entity_type`, `attr_name`, `attr_value`, `attr_display`, `lang`) VALUES(956, 'organization', 'type', 'org_type', 'vet_clinic', 'Veterinary Clinic', 'en');

-- --------------------------------------------------------

--
-- Table structure for table `wp_lapki_media`
--

DROP TABLE IF EXISTS `wp_lapki_media`;
CREATE TABLE `wp_lapki_media` (
  `id` bigint UNSIGNED NOT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` bigint UNSIGNED NOT NULL,
  `media_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `embed_code` text COLLATE utf8mb4_unicode_ci,
  `video_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `alt_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` tinyint DEFAULT '0',
  `is_primary` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `file_size` int UNSIGNED DEFAULT NULL,
  `width` int UNSIGNED DEFAULT NULL,
  `height` int UNSIGNED DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wp_lapki_organizations`
--

DROP TABLE IF EXISTS `wp_lapki_organizations`;
CREATE TABLE `wp_lapki_organizations` (
  `id` bigint UNSIGNED NOT NULL,
  `wp_user_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'individual',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postcode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT 'UA',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wp_lapki_tags`
--

DROP TABLE IF EXISTS `wp_lapki_tags`;
CREATE TABLE `wp_lapki_tags` (
  `id` bigint UNSIGNED NOT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` bigint UNSIGNED NOT NULL,
  `tag` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `wp_lapki_animals`
--
ALTER TABLE `wp_lapki_animals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type_status` (`type`,`status`),
  ADD KEY `idx_location` (`address_state`,`address_city`),
  ADD KEY `idx_coords` (`latitude`,`longitude`),
  ADD KEY `idx_published` (`published_at`),
  ADD KEY `idx_age_gender_size` (`age`,`gender`,`size`),
  ADD KEY `idx_breed_primary` (`breed_primary`),
  ADD KEY `idx_color_primary` (`color_primary`),
  ADD KEY `idx_organization` (`organization_id`),
  ADD KEY `idx_attributes` (`spayed_neutered`,`house_trained`,`special_needs`),
  ADD KEY `idx_compatibility` (`good_with_children`,`good_with_dogs`,`good_with_cats`);

--
-- Indexes for table `wp_lapki_attributes`
--
ALTER TABLE `wp_lapki_attributes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attr` (`entity`,`entity_type`,`attr_name`,`attr_value`,`lang`),
  ADD KEY `idx_entity_type` (`entity`,`entity_type`),
  ADD KEY `idx_entity_attr` (`entity`,`attr_name`),
  ADD KEY `idx_entity_lang` (`entity`,`entity_type`,`lang`);

--
-- Indexes for table `wp_lapki_media`
--
ALTER TABLE `wp_lapki_media`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_primary` (`entity_type`,`entity_id`,`media_type`,`is_primary`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_entity_order` (`entity_type`,`entity_id`,`sort_order`),
  ADD KEY `idx_media_type` (`media_type`),
  ADD KEY `idx_primary` (`entity_type`,`entity_id`,`is_primary`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `wp_lapki_organizations`
--
ALTER TABLE `wp_lapki_organizations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wp_user` (`wp_user_id`),
  ADD KEY `idx_location` (`state`,`city`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_verified` (`is_verified`);

--
-- Indexes for table `wp_lapki_tags`
--
ALTER TABLE `wp_lapki_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_entity_tag` (`entity_type`,`entity_id`,`tag`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_tag` (`tag`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `wp_lapki_animals`
--
ALTER TABLE `wp_lapki_animals`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wp_lapki_attributes`
--
ALTER TABLE `wp_lapki_attributes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=957;

--
-- AUTO_INCREMENT for table `wp_lapki_media`
--
ALTER TABLE `wp_lapki_media`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wp_lapki_organizations`
--
ALTER TABLE `wp_lapki_organizations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wp_lapki_tags`
--
ALTER TABLE `wp_lapki_tags`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
