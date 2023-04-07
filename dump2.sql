-- MySQL dump 10.13  Distrib 5.7.41, for Linux (x86_64)
--
-- Host: localhost    Database: laravelList
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `list_tickets`
--

DROP TABLE IF EXISTS `list_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `list_tickets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fio` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `project` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `curator` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT '1',
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `list_tickets_user_id_index` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=50065 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `list_tickets`
--

LOCK TABLES `list_tickets` WRITE;
/*!40000 ALTER TABLE `list_tickets` DISABLE KEYS */;
INSERT INTO `list_tickets` VALUES (50000,'bednovv@mail.ru','Беднов Виктор Борисович','Видеосъемка. Коптер','Систо_Штаб','2023-03-26 11:12:41','2023-03-26 11:12:41',3,''),(50001,'bednovv@mail.ru','Никитина Надежда Григорьевна','Видеосъемка. Коптер','Систо_Штаб','2023-03-26 11:12:41','2023-03-26 11:12:41',3,''),(50002,'7168448@mail.ru','Филипп Буянов','Сфера для детского сада','Краснощеков','2023-03-27 16:25:24','2023-03-27 16:25:24',2,''),(50003,'7168448@mail.ru','Юлия Атапина','Сфера для детского сада','Краснощеков','2023-03-27 16:25:24','2023-03-27 16:25:24',2,''),(50004,'abdoolbuy@me.com','Дмитрий Дащенко','Systopeople','Краснощеков','2023-03-27 20:23:17','2023-03-27 20:23:17',2,''),(50005,'abdoolbuy@me.com','Виктор Павлов','Systopeople','Краснощеков','2023-03-27 20:23:17','2023-03-27 20:23:17',2,''),(50006,'oburaten@mail.ru','Артем Камышников','Pranaweb','Краснощеков','2023-03-29 11:52:58','2023-03-29 11:52:58',2,''),(50007,'jenya@spaceofjoy.ru','Геннадий Краснощеков','Семья Краснощекова','Краснощеков','2023-03-29 21:15:05','2023-03-29 21:15:05',2,''),(50008,'jenya@spaceofjoy.ru','Валерий Власов','Семья Краснощекова','Краснощеков','2023-03-29 21:15:05','2023-03-29 21:15:05',2,''),(50009,'Larksfilms@gmail.com','Искандер Садыков','LarkFilms (видеооператоры Искандера)','Краснощеков','2023-03-31 09:59:05','2023-03-31 09:59:05',2,''),(50010,'Larksfilms@gmail.com','Волков Артем','LarkFilms (видеооператоры Искандера)','Краснощеков','2023-03-31 09:59:05','2023-03-31 09:59:05',2,''),(50011,'Larksfilms@gmail.com','Бурдаков Евгений','LarkFilms (видеооператоры Искандера)','Краснощеков','2023-03-31 09:59:05','2023-03-31 09:59:05',2,''),(50012,'Larksfilms@gmail.com','Валентин Попцов','LarkFilms (видеооператоры Искандера)','Краснощеков','2023-03-31 09:59:05','2023-03-31 09:59:05',2,''),(50013,'Larksfilms@gmail.com','Чепельникова Жанна','LarkFilms (видеооператоры Искандера)','Краснощеков','2023-03-31 09:59:05','2023-03-31 09:59:05',2,''),(50014,'Larksfilms@gmail.com','Вилен Жанна','LarkFilms (видеооператоры Искандера)','Краснощеков','2023-03-31 09:59:05','2023-03-31 09:59:05',2,''),(50015,'Larksfilms@gmail.com','Сергей Кызы-оол','LarkFilms (видеооператоры Искандера)','Краснощеков','2023-03-31 09:59:05','2023-03-31 09:59:05',2,''),(50016,'Larksfilms@gmail.com','Маша Каверзнева','LarkFilms (видеооператоры Искандера)','Краснощеков','2023-03-31 09:59:05','2023-03-31 09:59:05',2,''),(50017,'Larksfilms@gmail.com','Шашерин Евгений','LarkFilms (видеооператоры Искандера)','Краснощеков','2023-03-31 09:59:05','2023-03-31 09:59:05',2,''),(50018,'Larksfilms@gmail.com','Satanic Disney','LarkFilms (видеооператоры Искандера)','Краснощеков','2023-03-31 09:59:05','2023-03-31 09:59:05',2,''),(50019,'Larksfilms@gmail.com','Дмитрий Кузьмин ','LarkFilms (видеооператоры Искандера)','Краснощеков','2023-03-31 09:59:05','2023-03-31 09:59:05',2,''),(50020,'Larksfilms@gmail.com','Юлия Фенева','LarkFilms (видеооператоры Искандера)','Краснощеков','2023-03-31 09:59:05','2023-03-31 09:59:05',2,''),(50021,'noostya@mail.ru','Анастасия Пахомова','Победитель конкурса','Краснощеков','2023-04-02 17:05:20','2023-04-02 17:05:20',2,''),(50022,'kesha2@yandex.ru','Рыжков Иннокентий ','Создатель группы Вконтакте','Краснощеков','2023-04-02 20:09:44','2023-04-02 20:09:44',2,''),(50023,'kesha2@yandex.ru','Рыжкова Екатерина','Создатель группы Вконтакте','Краснощеков','2023-04-02 20:09:44','2023-04-02 20:09:44',2,''),(50024,'jenya@spaceofjoy.ru','Александр Курячий','ВШЭ','Краснощеков','2023-04-03 10:07:15','2023-04-03 10:07:15',2,'Почетный гость'),(50026,'e.yambaev@gmail.com','Сабиров Ренат','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50027,'e.yambaev@gmail.com','Иванова Анна','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50028,'e.yambaev@gmail.com','Павлова Анна','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50029,'e.yambaev@gmail.com','Фанин Денис','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50030,'e.yambaev@gmail.com','Ломакин Денис','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50031,'e.yambaev@gmail.com','Лощенко Константин','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50032,'e.yambaev@gmail.com','Тюленев Вячеслав','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50033,'e.yambaev@gmail.com','Рогачев Дмитрий','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50034,'e.yambaev@gmail.com','Лимонова Галина','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50035,'e.yambaev@gmail.com','Соколов Игорь','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50036,'e.yambaev@gmail.com','Ельникова Александра','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50037,'e.yambaev@gmail.com','Стародубцев Павел','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50038,'e.yambaev@gmail.com','Силаев Виктор','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50039,'e.yambaev@gmail.com','Силаева Анна','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50040,'e.yambaev@gmail.com','Дмитрий Черкасов','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50041,'e.yambaev@gmail.com','Худи Ирина','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50042,'e.yambaev@gmail.com','Романов Дмитрий','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50043,'e.yambaev@gmail.com','Баев Павел','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50044,'e.yambaev@gmail.com','Виталий Ющенко','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50045,'e.yambaev@gmail.com','Мария Воронина','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50046,'e.yambaev@gmail.com','Мишалов Сергей','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50047,'e.yambaev@gmail.com','Мацукевич Дина','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50048,'e.yambaev@gmail.com','Каравай Татьяна','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50049,'e.yambaev@gmail.com','Алиса Гасенко','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50050,'e.yambaev@gmail.com','Виталий Гасенко','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50051,'e.yambaev@gmail.com','Христофорова Марьяна','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50052,'e.yambaev@gmail.com','Сенкевич Кирилл','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50053,'e.yambaev@gmail.com','Маслова Эмма','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50054,'e.yambaev@gmail.com','Залетова Мария','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50055,'e.yambaev@gmail.com','Крохалева Ирина','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50056,'e.yambaev@gmail.com','Козлов Глеб','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50057,'e.yambaev@gmail.com','Васильев Андрей','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50058,'e.yambaev@gmail.com','Соколова Анна','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50059,'e.yambaev@gmail.com','Ямбаев Евгений','\"Свои комьюнити\"','Краснощеков','2023-04-04 13:15:41','2023-04-04 13:15:41',2,'Лагерь в стороне Гулливера. Договоренность за папа принт продукцию'),(50062,'bakunina1@gmail.com','Бакунина Алина .','Система (модератор чата и корректор текстов Бакунина Алина . Одинцов Виктор. Ларионов Дмитрий.Систо)','Краснощеков','2023-04-04 17:38:13','2023-04-04 17:38:13',2,''),(50063,'bakunina1@gmail.com','Одинцов Виктор.','Система (модератор чата и корректор текстов Бакунина Алина . Одинцов Виктор. Ларионов Дмитрий.Систо)','Краснощеков','2023-04-04 17:38:13','2023-04-04 17:38:13',2,''),(50064,'bakunina1@gmail.com','Ларионов Дмитрий','Система (модератор чата и корректор текстов Бакунина Алина . Одинцов Виктор. Ларионов Дмитрий.Систо)','Краснощеков','2023-04-04 17:38:13','2023-04-04 17:38:13',2,'');
/*!40000 ALTER TABLE `list_tickets` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (37,'2014_10_12_000000_create_users_table',1),(38,'2014_10_12_100000_create_password_resets_table',1),(39,'2014_10_12_200000_add_two_factor_columns_to_users_table',1),(40,'2019_08_19_000000_create_failed_jobs_table',1),(41,'2019_12_14_000001_create_personal_access_tokens_table',1),(42,'2021_03_25_174554_create_sessions_table',1),(43,'2021_03_25_182026_create_friendly_ticket',1),(44,'2021_03_27_104954_add_field_is_admin_users_table',1),(45,'2023_03_14_095818_create_jobs_table',1),(46,'2023_03_22_130936_add_user_id_in_friendly_tickets',1),(47,'2023_03_22_141339_add_comment_in_friendly_tickets',1),(48,'2023_03_22_161816_add_curator_in_users',1);
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
INSERT INTO `sessions` VALUES ('JCn5ejnTULEW145zTBBC8RT34VdVCRtadfphgZw5',1,'37.215.62.149','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoibHg1SHE4M2xHUnNqUGNIRFZza0pWN0xiNjk2VzNTOVd6Z2NjeDJhWiI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2MDoiJDJ5JDEwJHZVazZTZW1XeWlMaW1JbEpsbkwwL3Vielp3OWVNa0cySmVrTzdXb3J4OEwxVnlEdllwWXN1IjtzOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo0MToiaHR0cDovL3NwaXNvay5zb2xhcnN5c3RvLnJ1L2FkbWluL3RpY2tldHMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1680614712),('ncXH7c6rJcc9sVON7Z0II0Gnjsv5q02MP2jnsHI5',1,'37.215.62.149','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoicFI2Tk9aTjB5Uk41a3IyVjhDYllZcHFxZlk4M3JLam1kWU95YWc1RyI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2MDoiJDJ5JDEwJHZVazZTZW1XeWlMaW1JbEpsbkwwL3Vielp3OWVNa0cySmVrTzdXb3J4OEwxVnlEdllwWXN1IjtzOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1680769120),('NRTrTv2pP5zX2qWIiA6i4S9wXURapV9RMZrZj0lS',2,'92.62.56.175','Mozilla/5.0 (iPhone; CPU iPhone OS 16_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.2 Mobile/15E148 Safari/604.1','YTo2OntzOjY6Il90b2tlbiI7czo0MDoia1IySkxocHdienNVSFJvQldmRnp2WXp3czJ5SGZFVEJGdVF5eThobyI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjI3OiJodHRwOi8vc3Bpc29rLnNvbGFyc3lzdG8ucnUiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO3M6MTc6InBhc3N3b3JkX2hhc2hfd2ViIjtzOjYwOiIkMnkkMTAkZnlDdVIwQVhlckNud0FFWjk0R2hOT1F4cUQ2bUNtc0FrWDljQnVPYWRVVkl3VGNMaGwzT08iO30=',1680596854),('OJB1PCyfYW6iiIBUVGg452QkylP0NR9uMYisrB2t',2,'188.93.140.177','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiaXI3UmpQRDFGcUp2RGJTc09yUFdMSllHeTB3YXRlQXRKNzF2bGpmRCI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2MDoiJDJ5JDEwJGZ5Q3VSMEFYZXJDbndBRVo5NEdoTk9ReHFENm1DbXNBa1g5Y0J1T2FkVVZJd1RjTGhsM09PIjtzOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo0MToiaHR0cDovL3NwaXNvay5zb2xhcnN5c3RvLnJ1L2FkbWluL3RpY2tldHMiO319',1680614382),('vqW4odPUfsIk9Sbrl37xeffi5435itQnMwvqQghi',2,'188.93.140.177','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoibnNlekhKc0ZsNExVSzNaQm9kTTBzTnJLSUY4bEIyOFRucnRncFNGNyI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2MDoiJDJ5JDEwJGZ5Q3VSMEFYZXJDbndBRVo5NEdoTk9ReHFENm1DbXNBa1g5Y0J1T2FkVVZJd1RjTGhsM09PIjtzOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czoyNzoiaHR0cDovL3NwaXNvay5zb2xhcnN5c3RvLnJ1Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1680516436),('ZNyc7VLwYEz8qm4NpKMxdH0clOTyVqYSa1vcnDan',2,'188.93.140.177','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoicFVNemZPdWppZmszSTJvaW9wQUFKVlVJc1lWSzlYSGN4QVdxeExUaiI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2MDoiJDJ5JDEwJGZ5Q3VSMEFYZXJDbndBRVo5NEdoTk9ReHFENm1DbXNBa1g5Y0J1T2FkVVZJd1RjTGhsM09PIjtzOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czoyNzoiaHR0cDovL3NwaXNvay5zb2xhcnN5c3RvLnJ1Ijt9fQ==',1680632613);
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
  `curator` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'SystoAdmin','admin@spaceofjoy.ru','2023-03-24 07:15:25','$2y$10$vUk6SemWyiLimIlJlnL0/ubzZw9eMkG2JekO7Worx8L1VyDvYpYsu',NULL,NULL,'kzhruedjdv',NULL,NULL,'2023-03-24 07:15:26','2023-03-24 07:15:26',1,'SystoTeam'),(2,'Евгений Краснощеков','jenya@spaceofjoy.ru',NULL,'$2y$10$fyCuR0AXerCnwAEZ94GhNOQxqD6mCmsAkX9cBuOadUVIwTcLhl3OO',NULL,NULL,'pbzvgh23yNsTF9K0fUBC7eLN0KP57mzPSB2WYtMQkpn7AXncN7le52rfoP6n',NULL,NULL,'2023-03-24 13:34:05','2023-04-03 06:25:57',1,'Краснощеков'),(3,'Дмитрий Воробьев','vertograd23@gmail.com',NULL,'$2y$10$8vocenKtO06cG.1iK2eM7O4UFE1cwltZGX3hyP.QpUjviJl12UtyW',NULL,NULL,NULL,NULL,NULL,'2023-03-24 13:34:46','2023-03-24 13:34:46',0,'Систо_Штаб');
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

-- Dump completed on 2023-04-06  9:25:11
