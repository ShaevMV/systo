-- MySQL dump 10.13  Distrib 5.7.41, for Linux (x86_64)
--
-- Host: localhost    Database: laravel
-- ------------------------------------------------------
-- Server version	5.7.41

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `friendly_tickets`
--

DROP TABLE IF EXISTS `friendly_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `friendly_tickets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fio` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `seller` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` double(8,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `fio_friendly` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT '1',
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `friendly_tickets_user_id_index` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30047 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `friendly_tickets`
--

LOCK TABLES `friendly_tickets` WRITE;
/*!40000 ALTER TABLE `friendly_tickets` DISABLE KEYS */;
INSERT INTO `friendly_tickets` VALUES (30002,'Spirov14042000@gmail.com','SystoOrg','Евгений Краснощеков',0.00,'2023-03-16 19:07:20','2023-03-16 19:07:20','Федор Спиров',1,''),(30003,'Spirov14042000@gmail.com','SystoOrg','Евгений Краснощеков',0.00,'2023-03-16 19:07:20','2023-03-16 19:07:20','Даниил Спасский',1,''),(30004,'maxnikulnikov@gmail.com','Штаб','Настя Рикари',3800.00,'2023-03-22 18:50:58','2023-03-22 18:50:58','Никульников Максим Сергеевич',11,'Покупка билет артисту, кого не приняли в лайн-ап'),(30005,'polushkina1990@mail.ru','программист','Митрофан Шаев',3800.00,'2023-03-27 11:55:07','2023-03-27 11:55:07','Прокина Юлия Сергеевна',20,''),(30006,'polushkina1990@mail.ru','программист','Митрофан Шаев',3800.00,'2023-03-27 11:55:07','2023-03-27 11:55:07','Жилочкин Дмитрий Евгеньевич',20,''),(30007,'polushkina1990@mail.ru','программист','Митрофан Шаев',3800.00,'2023-03-27 11:55:07','2023-03-27 11:55:07','Стрелкова Дарья Александровна',20,''),(30009,'Vasss999@mail.ru','WebWork','Настя Иванова',3800.00,'2023-03-28 12:13:33','2023-03-28 12:13:33','Васькин Сергей',21,''),(30010,'zdrvsmsl@gmail.com','Кружок Костроведения (Выборг)','Кирилл Швецов',2500.00,'2023-03-28 13:00:50','2023-03-28 13:00:50','Альбина Ребитва',10,''),(30011,'zdrvsmsl@gmail.com','Кружок Костроведения (Выборг)','Кирилл Швецов',2500.00,'2023-03-28 13:03:52','2023-03-28 13:03:52','Алёна Бервер',10,''),(30012,'zdrvsmsl@gmail.com','Кружок Костроведения (Выборг)','Кирилл Швецов',2500.00,'2023-03-28 13:24:09','2023-03-28 13:24:09','Камалетдинов Расим',10,''),(30013,'zdrvsmsl@gmail.com','Кружок Костроведения (Выборг)','Кирилл Швецов',2500.00,'2023-03-28 13:24:09','2023-03-28 13:24:09','Киян Марина',10,''),(30014,'zdrvsmsl@gmail.com','Кружок Костроведения (Выборг)','Кирилл Швецов',2500.00,'2023-03-28 13:24:09','2023-03-28 13:24:09','Соловьёв Егор',10,''),(30015,'zdrvsmsl@gmail.com','Кружок Костроведения (Выборг)','Кирилл Швецов',2500.00,'2023-03-28 13:25:05','2023-03-28 13:25:05','Марков Егор',10,''),(30016,'zdrvsmsl@gmail.com','Кружок Костроведения (Выборг)','Кирилл Швецов',2500.00,'2023-03-28 13:25:05','2023-03-28 13:25:05','Рольгейзер Кирилл',10,''),(30017,'zdrvsmsl@gmail.com','Кружок Костроведения (Выборг)','Кирилл Швецов',2500.00,'2023-03-28 13:25:05','2023-03-28 13:25:05','Нагайцева Нелли',10,''),(30018,'zdrvsmsl@gmail.com','Кружок Костроведения (Выборг)','Кирилл Швецов',2500.00,'2023-03-28 13:25:05','2023-03-28 13:25:05','Ивашов Александр',10,''),(30019,'negativu_net@mail.ru','WebWork','Настя Иванова',3800.00,'2023-03-28 15:28:41','2023-03-28 15:28:41','Рыжая ведьма',21,''),(30020,'negativu_net@mail.ru','WebWork','Настя Иванова',3800.00,'2023-03-28 15:28:41','2023-03-28 15:28:41','Розовая ведьма',21,''),(30021,'negativu_net@mail.ru','WebWork','Настя Иванова',3800.00,'2023-03-28 15:28:41','2023-03-28 15:28:41','Белая ведьма',21,''),(30024,'skandakoff@gmail.com','Низовье (DnB)','Иван Бобков',3500.00,'2023-03-28 16:09:15','2023-03-28 16:09:15','Кандаков Сергей',18,''),(30025,'shaevmv@gmail.com','программист','Митрофан Шаев',90.00,'2023-03-28 16:14:32','2023-03-28 16:14:32','Анхим Анна Александровна',20,'Беларусы, в бел рублях'),(30026,'shaevmv@gmail.com','программист','Митрофан Шаев',90.00,'2023-03-28 16:14:32','2023-03-28 16:14:32','Анхим Полина Александровна',20,'Беларусы, в бел рублях'),(30027,'better77@inbox.ru','Низовье (DnB)','Иван Бобков',3500.00,'2023-03-28 19:26:47','2023-03-28 19:26:47','Антипина Ксения',18,''),(30028,'shaevmv@gmail.com','SystoTeam','SystoAdmin',90.00,'2023-03-29 05:24:16','2023-03-29 05:24:16','Дадеркин Иван Александрович',1,'Беларусы, бел руб'),(30029,'anna.sonli.nevermore@gmail.com','GALANTER','Настя',3800.00,'2023-03-29 17:46:06','2023-03-29 17:46:06','Анна Пустота',7,''),(30030,'drugoff.evgen@mail.ru','Кастры','Артём Лопата',3800.00,'2023-03-30 16:57:47','2023-03-30 16:57:47','Другов Евгений Олегович',12,''),(30031,'zdrvsmsl@gmail.com','Кружок Костроведения (Выборг)','Кирилл Швецов',3000.00,'2023-03-30 18:44:18','2023-03-30 18:44:18','Красников Юрий',10,''),(30032,'zdrvsmsl@gmail.com','Кружок Костроведения (Выборг)','Кирилл Швецов',2500.00,'2023-03-30 20:43:00','2023-03-30 20:43:00','Александр Судаков',10,''),(30033,'himi4ewa@mail.ru','Mystic Sound','Алла Вагнер',3800.00,'2023-03-31 16:13:14','2023-03-31 16:13:14','Химичева Татьяна',13,''),(30034,'Chap202099@yandex.ru','WebWork','Настя Иванова',3800.00,'2023-04-01 18:22:01','2023-04-01 18:22:01','Вадя Гагагерь',21,''),(30035,'symbol-herz@mail.ru','КЛИН','Ефим Емельянов',3800.00,'2023-04-01 21:24:43','2023-04-01 21:24:43','Бреславская Мария',24,''),(30036,'rse1@yandex.ru','Низовье (DnB)','Иван Бобков',3500.00,'2023-04-02 12:32:39','2023-04-02 12:32:39','Романюта Сергей',18,''),(30037,'lady.vafel@yandex.ru','Низовье (DnB)','Иван Бобков',3500.00,'2023-04-02 13:08:17','2023-04-02 13:08:17','Петрова Мария',18,''),(30038,'ilyamorozwork@yandex.ru','Кастры','Артём Лопата',3300.00,'2023-04-03 09:25:04','2023-04-03 09:25:04','Мороз Илья Викторович',12,''),(30039,'ilyamorozwork@yandex.ru','Кастры','Артём Лопата',3300.00,'2023-04-03 09:25:04','2023-04-03 09:25:04','Проскурина Полина Станиславовна',12,''),(30040,'ilyamorozwork@yandex.ru','Кастры','Артём Лопата',3300.00,'2023-04-03 09:25:04','2023-04-03 09:25:04','Лизунас Илья Романович',12,''),(30041,'Awosica@gmail.com','Поляна-Валяна','Synergia',3800.00,'2023-04-03 10:47:02','2023-04-03 10:47:02','Оксана Мамай',26,''),(30042,'conopleva.maria2015@yandex.ru','PARADOX','Евгений',3800.00,'2023-04-03 17:21:04','2023-04-03 17:21:04','Коноплева Мария',5,''),(30043,'showshop@yandex.ru','SystoOrg','Евгений Краснощеков',2000.00,'2023-04-04 08:14:34','2023-04-04 08:14:34','Test test',2,''),(30044,'admin@pranaweb.ru','SystoOrg','Евгений Краснощеков',1.00,'2023-04-04 08:15:43','2023-04-04 08:15:43','Test 2',2,''),(30045,'soscirezci@gmail.com','Детская Поляна','Маша Краснощекова',3800.00,'2023-04-05 15:32:12','2023-04-05 15:32:12','Семёнов Алексей',16,''),(30046,'goleta@vintage-co.ru','Кастры','Артём Лопата',3300.00,'2023-04-06 08:47:44','2023-04-06 08:47:44','Голёта Сергей Александрович',12,'');
/*!40000 ALTER TABLE `friendly_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (51,'2014_10_12_000000_create_users_table',1),(52,'2014_10_12_100000_create_password_resets_table',1),(53,'2014_10_12_200000_add_two_factor_columns_to_users_table',1),(54,'2019_08_19_000000_create_failed_jobs_table',1),(55,'2019_12_14_000001_create_personal_access_tokens_table',1),(56,'2021_03_25_174554_create_sessions_table',1),(57,'2021_03_25_182026_create_friendly_ticket',1),(58,'2021_03_27_104954_add_field_is_admin_users_table',1),(59,'2023_03_12_111722_add_project_in_users',1),(60,'2023_03_13_125016_add_fio_friendly_in_friendly_tickets',1),(61,'2023_03_14_095818_create_jobs_table',1),(62,'2023_03_16_150206_add_in_user',2),(63,'2023_03_22_130936_add_user_id_in_friendly_tickets',3),(64,'2023_03_22_141339_add_comment_in_friendly_tickets',4);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('4V2uAYAjFrUDfEse6LPRNiVJF9Nc5Jjp2mh4maE4',NULL,'192.241.206.16','Mozilla/5.0 zgrab/0.x','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiV3poYkRMbU5WTUUwalhyU2J4WWJaVEQ5Q2ZXUURHY3F3QldoUFBFViI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyMjoiaHR0cDovLzE5My4xMDYuMTc1LjE2NyI7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjIyOiJodHRwOi8vMTkzLjEwNi4xNzUuMTY3Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1680769444),('6s29RJgfchm2trwJtqv9NfhG12unXwID7yWdpaeQ',NULL,'193.32.162.159','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46','YToyOntzOjY6Il90b2tlbiI7czo0MDoiWGZEcjNMTnEwbEI1ampwYlFXWm1LeUVnSDVZUU9WT2s4YnhFZ2V1ZiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1680765822),('eFjjyqDziXmCbkEGXJps6FVv2YnJGUJNGbSXKWDY',NULL,'117.199.213.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/601.7.7 (KHTML, like Gecko) Version/9.1.2 Safari/601.7.7','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiRDJxRDBodllvckJtRHdTbkJFNGhwczAwWER6RDJOSnptOFNlRXBjNSI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyMjoiaHR0cDovLzE5My4xMDYuMTc1LjE2NyI7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjIyOiJodHRwOi8vMTkzLjEwNi4xNzUuMTY3Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1680771726),('efTDyASp0e7PId79qgWwHKZGvuJjOEiJwpiA6ImG',NULL,'94.102.61.10','python-requests/2.26.0','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiREkycmVFVUtvd1R2eVVTcGlkM3N0Q28yY1pqUDl2VmNhd2N1QUNGNyI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyMjoiaHR0cDovLzE5My4xMDYuMTc1LjE2NyI7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjI4OiJodHRwOi8vMTkzLjEwNi4xNzUuMTY3L2xvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1680768368),('m88Jz3VEKlDbvdF1WnDvsXQGVPSIJB00UkUSCcbz',NULL,'104.197.124.179','Mozilla/5.0 (X11; Linux x86_64; rv:83.0) Gecko/20100101 Firefox/83.0','YToyOntzOjY6Il90b2tlbiI7czo0MDoiUEc3QkVsSno5MzRScjhEbXZpa3BBYnZad3MyRnQxRU9jSE51QzRtYyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1680766833),('p3anCfL8R9aUuVnIFv2ybh1JSjR4jFzwTnWLTEzd',1,'37.215.62.149','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiUDBtSEdPOXRKYks4alBTRW9nZXNCRWY3VFRHMzVVZko1a2h0S0NqZyI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2MDoiJDJ5JDEwJHBKUWthdnYxdDI2WVAzc0JlYUZ2R2VtQi9wL0YuRVBndmoweWVqUnA3d3JLMS84dm9mNWtpIjtzOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozNjoiaHR0cDovL2RydWcuc29sYXJzeXN0by5ydS9hZG1pbi91c2VyIjt9fQ==',1680772773),('seci606BXregZAovR3N4UoX2r68tMaIxZZZxAhgM',NULL,'3.73.253.89','Mozilla/5.0 (iPhone; CPU iPhone OS 16_1_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.1 Mobile/15E148 Safari/604.1','YToyOntzOjY6Il90b2tlbiI7czo0MDoibXFsZVpOb0R2WUpqUHVzZWJZNDgxWUcwdW1LamQyWEl3NDNZS1FHVSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1680772354),('VKeKtxuPzcXIoyWeKsJIOqyW1inyzEnfD9wXAoie',12,'46.32.66.170','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36','YTo2OntzOjY6Il90b2tlbiI7czo0MDoiZ1NaMVhLamNUTFZ3ZkdxRHNrSTBJZUFkd3gwcXpNSHIyYVd2ampHaSI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjI1OiJodHRwOi8vZHJ1Zy5zb2xhcnN5c3RvLnJ1Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTI7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjA6IiQyeSQxMCRTcUZ4NUdYTkdaNzNPRlVHaWliQ1l1THZBd2l2M2swaXU3NURJSVFjV245LmJPbUU4VHlzUyI7fQ==',1680770864),('W5hLj77EIxOqBe9RCTnGt0O343lDjdKHnm7fZtQN',NULL,'37.215.62.149','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoid0t4YVhxdVh4U3R0TXdBWjd2U1ZKaTU0RkZIUzJadHkzd21xbWp6QiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly8xOTMuMTA2LjE3NS4xNjcvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1680770117);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_team_id` bigint(20) unsigned DEFAULT NULL,
  `profile_photo_path` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `project` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'SystoAdmin','admin@spaceofjoy.ru','2023-03-15 20:25:14','$2y$10$pJQkavv1t26YP3sBeaFvGemB/p/F.EPgvj0yejRp7wrK1/8vof5ki',NULL,NULL,'8VFeuBPvtTEF8XXOtbl0J6VaD5veQ3f5m7eVezLINQRp2M5VcJdb3jh0nc96',NULL,NULL,'2023-03-15 20:25:15','2023-03-15 20:25:15',1,'SystoTeam'),(2,'Евгений Краснощеков','jenya@spaceofjoy.ru',NULL,'$2y$10$giQ.XqhOeCP.c9olN5GAce.ZI0SZ4IkWlGGIP8IOAlKw.crZs0yoO',NULL,NULL,'KgIZx6oIYvUmuXFlmcRBxCgMBI2XRtWnZ2tm8tGq2LSUWieoeE9VpBbaRozp',NULL,NULL,'2023-03-15 20:43:45','2023-03-15 20:43:45',1,'SystoOrg'),(4,'Кристина','kris@spaceofjoy.ru',NULL,'$2y$10$mWcSf4me8ieRvFsla4Xtfew19ciqNccKimDXyX/N.ZmHBkU6/W6Pa',NULL,NULL,NULL,NULL,NULL,'2023-03-16 14:17:40','2023-03-16 14:17:40',1,'SystoOrg_Kris'),(5,'Евгений','vgick_@mail.ru',NULL,'$2y$10$NzrsU30YxX6Aw76Bqvfq6uLKeSaaIss3Or08MHlbYPGECeBaDaSXq',NULL,NULL,'8fP5vUYiGUIKHNYyhSjf58vBztgn4qYrCkQdJxDxqi4zNMHuFOItFRqjQjqb',NULL,NULL,'2023-03-19 18:25:56','2023-03-19 18:25:56',0,'PARADOX'),(7,'Настя','Galanterart@gmail.com',NULL,'$2y$10$fhKurOEqD.nLaQ3BYRCKi.lFZnqRTc1/je8kEdU53upIP8P1jTU4y',NULL,NULL,NULL,NULL,NULL,'2023-03-19 18:46:38','2023-03-19 18:46:38',0,'GALANTER'),(8,'Максим','87mma87@gmail.com',NULL,'$2y$10$Y9eXLlacnLL7ZQgFZv..Heoq4pgbPReRjNYKHlLbi/NSawxS5q9j6',NULL,NULL,'ymczI7SkWqPnUyj8poLa1zcFmyTWa6snMTIQ2Q5aKDq9VIY2Mhy3kvNmV6xr',NULL,NULL,'2023-03-20 07:38:57','2023-03-20 07:38:57',0,'sys4 -  AXIOMA'),(9,'Артем Новизнов','Auratech108@gmail.com',NULL,'$2y$10$AC8fLgBZKMd9X2KIO6XWgumi4Eat.OAgFTcTYwRXal/IYyCB9YGle',NULL,NULL,'6Av765rKsd7KqY04nYBPUeah9b6eS5Y3JoKtVRFNt3HluFtTH4gFoJNmTwmk',NULL,NULL,'2023-03-21 11:36:53','2023-03-21 11:36:53',0,'Auratech'),(10,'Кирилл Швецов','zdrvsmsl@gmail.com',NULL,'$2y$10$L.HkX1jzE9uCkABl5HPsfeXQ3MAQN9Nto5d0umy.4U4pnXxnIdzVi',NULL,NULL,NULL,NULL,NULL,'2023-03-21 11:48:09','2023-03-21 11:48:09',0,'Кружок Костроведения (Выборг)'),(11,'Настя Рикари','Anastasiagubenkova@gmail.com',NULL,'$2y$10$YtbehKtbZXvv2p9xa2VoreM9t03O4iYD6zZSIHeC1BRfK3YWhWwOW',NULL,NULL,NULL,NULL,NULL,'2023-03-21 15:07:35','2023-03-21 15:08:32',0,'Штаб'),(12,'Артём Лопата','a.dizainazona@gmail.com',NULL,'$2y$10$SqFx5GXNGZ73OFUGiibCYuLvAwiv3k0iu75DIIQcWn9.bOmE8TysS',NULL,NULL,NULL,NULL,NULL,'2023-03-21 20:09:43','2023-03-21 20:10:04',0,'Кастры'),(13,'Алла Вагнер','milaimaiia@gmail.com',NULL,'$2y$10$jCYe8PhQ2upNzygqY/UO.OZwE.rjF5k5ZKXjwmELzqQYthjJt3Lru',NULL,NULL,NULL,NULL,NULL,'2023-03-22 12:13:09','2023-03-23 09:40:55',0,'Mystic Sound'),(14,'Артур Измайлов','Simbasmusic@gmail.com',NULL,'$2y$10$P8rPsarOugpDqgqt6XhV9er2U8Hxldx7I.1d8e81CwRnQqloD5pdu',NULL,NULL,NULL,NULL,NULL,'2023-03-22 12:16:20','2023-03-22 12:16:20',0,'Племя кайфа'),(15,'Мультик','rechistaya@spaceofjoy.ru',NULL,'$2y$10$O9.T8moJnIKr6bkZvXJMTOA8TPphUraqMzU4UhE7H4ZenRroubLEm',NULL,NULL,NULL,NULL,NULL,'2023-03-22 17:19:02','2023-03-22 17:19:02',0,'Речистая'),(16,'Маша Краснощекова','Euphoria.wind@gmail.com',NULL,'$2y$10$pQ/PS2hceKObPnTJKGRdQeGK2JKca6YBD0/RVG9d8SVnIp4eqbfdK',NULL,NULL,'hEMI7FVZiWCxzWAJ7RpaKfW2pGNTZnb9C9Dw9L3V445oOCFzLtCB7T97t7qG',NULL,NULL,'2023-03-25 13:53:10','2023-03-25 13:53:10',0,'Детская Поляна'),(18,'Иван Бобков','dj606@mail.ru',NULL,'$2y$10$xKPFCkMhMUEaGO4Wtzit/OmQRei0/Vp2cNhCaWXR7MSXChbuhwCEO',NULL,NULL,'SIMp5PeCCkDoIiZP68z0svPsM9TIjrgK5PmyEZ3QKu4bpr0w7nZSKdmE6Fap',NULL,NULL,'2023-03-25 14:56:50','2023-03-25 14:56:50',0,'Низовье (DnB)'),(19,'Антон Засека','Zaseka@list.ru',NULL,'$2y$10$XCW6KFgPjoc8vymT/kvwBOdGHb.d96Yq.02eYzRevjhg34.NLpbtS',NULL,NULL,'XwfJYzdX6VE1SyGZedelr66SQi62tm7qwjhx6QppN7HKWtWGqR15bkTWL7LO',NULL,NULL,'2023-03-25 20:51:49','2023-03-25 20:51:49',0,'Zaseka'),(20,'Митрофан Шаев','shaevmv@gmail.com',NULL,'$2y$10$tLAnt6OKMhfK/w0ek6nSyuMQfVN687jf.kLWnCusuGmDUbQ068l1u',NULL,NULL,'vMn05JslJB1cI5DzZVJCXclb7ZajZx1q7vGqHzmGuKCRNUDvy4yKom70rHwV',NULL,NULL,'2023-03-27 11:53:53','2023-03-27 11:53:53',0,'программист'),(21,'Настя Иванова','Nastina@mitlabs.ru',NULL,'$2y$10$T3qbqR/TMz7exFM6QgAbrepp7xN15OBmrJOe2c3EvOkariswr/PYW',NULL,NULL,'tpElrkDw4Fu1cz5OXnxsC5l41kAPtH3Xku9svoIjKwcAnRoqWho7fKZ7i95t',NULL,NULL,'2023-03-27 16:31:07','2023-03-27 16:31:07',0,'WebWork'),(22,'Сат Сим','satsunmen@gmail.com',NULL,'$2y$10$KbZOthQ/gnQlE09ITth3DeWM/q2Ig/4ql/4UvPlQPx9hYFqTgpfSm',NULL,NULL,'7Vc7maHJMetbyX4amPxop4bCbBDnhl8Iui0xiadD6KXgzMqmRQQgODNpuwh9',NULL,NULL,'2023-03-27 17:50:17','2023-03-27 17:50:17',0,'Сат Сим (видео)'),(23,'Макс Потапов','festummedicina@gmail.com',NULL,'$2y$10$rfy7EBCrVyjpCGKZa7mmk.Mkadr0rnh8mH0nBEFaPq9i2BQqBI57u',NULL,NULL,NULL,NULL,NULL,'2023-03-27 21:01:16','2023-03-27 21:01:16',0,'Festum Medicine'),(24,'Ефим Емельянов','b.boards@yandex.ru',NULL,'$2y$10$O8sKVJYqJES7kO1UnIaQiON3CtCyDiX3Y5XlF2uwDhMWnV6K2E88C',NULL,NULL,'CPVSr4H8djzgLcU6i5S0oSmRTKilUdmQdNejKv5xU0VkhqGz2IMHxbsWa2YH',NULL,NULL,'2023-03-29 09:20:47','2023-03-29 09:20:47',0,'КЛИН'),(25,'Стас Афро','mudracommunity@gmail.com',NULL,'$2y$10$KVd9QSQI3mvgsPw5L6ZSQO9e2dhkf/QU5WS25h58Mao093FXzljZm',NULL,NULL,NULL,NULL,NULL,'2023-03-29 19:00:17','2023-03-29 19:00:17',0,'Mudra Music'),(26,'Synergia','kyzyaneatrava@gmail.com',NULL,'$2y$10$ptK8t2w.f.cP0HQHeYHuh.9rD/34BDDZvRCMzLT1SBrbCweKM8Xp6',NULL,NULL,'VyHFHC6sh4IWxhFtDtJpQtZ3UZydMVBasUJAdZjLYTYn1a4G7fnkGtGLUCWh',NULL,NULL,'2023-04-03 09:41:28','2023-04-03 09:41:28',0,'Поляна-Валяна');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2023-04-06  9:24:40
