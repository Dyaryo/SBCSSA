-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: esabong
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `actor_user_id` bigint(20) unsigned DEFAULT NULL,
  `actor_role` varchar(20) DEFAULT NULL,
  `action` varchar(60) NOT NULL,
  `entity_type` varchar(40) DEFAULT NULL,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `before` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`before`)),
  `after` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`after`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `workstation_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `audit_logs_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  KEY `audit_logs_created_at_index` (`created_at`),
  KEY `audit_logs_actor_user_id_index` (`actor_user_id`),
  KEY `audit_logs_action_index` (`action`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,2,'game_master','fight.betting_opened','Fight',1,NULL,'{\"status\":\"betting_open\"}',NULL,NULL,'2026-11-11 00:02:42'),(2,2,'game_master','fight.betting_locked','Fight',1,NULL,'{\"status\":\"betting_locked\"}',NULL,NULL,'2026-11-11 00:02:42'),(3,2,'game_master','fight.result_declared','Fight',1,NULL,'{\"winner\":\"red\",\"payout_total\":950,\"settled_bets\":2}',NULL,NULL,'2026-11-11 00:02:42'),(4,2,'game_master','fight.betting_opened','Fight',2,NULL,'{\"status\":\"betting_open\"}',NULL,NULL,'2026-11-11 00:02:42');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bets`
--

DROP TABLE IF EXISTS `bets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fight_id` bigint(20) unsigned NOT NULL,
  `teller_id` bigint(20) unsigned NOT NULL,
  `shift_session_id` bigint(20) unsigned DEFAULT NULL,
  `side` varchar(10) NOT NULL,
  `amount_credits` bigint(20) NOT NULL,
  `odds_at_placement` decimal(6,3) NOT NULL,
  `potential_payout` bigint(20) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `placed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `settled_at` timestamp NULL DEFAULT NULL,
  `voided_by` bigint(20) unsigned DEFAULT NULL,
  `void_reason` text DEFAULT NULL,
  `idempotency_key` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bets_idempotency_key_unique` (`idempotency_key`),
  KEY `bets_shift_session_id_foreign` (`shift_session_id`),
  KEY `bets_fight_id_status_index` (`fight_id`,`status`),
  KEY `bets_teller_id_placed_at_index` (`teller_id`,`placed_at`),
  CONSTRAINT `bets_fight_id_foreign` FOREIGN KEY (`fight_id`) REFERENCES `fights` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bets_shift_session_id_foreign` FOREIGN KEY (`shift_session_id`) REFERENCES `shift_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bets_teller_id_foreign` FOREIGN KEY (`teller_id`) REFERENCES `teller_profiles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bets`
--

LOCK TABLES `bets` WRITE;
/*!40000 ALTER TABLE `bets` DISABLE KEYS */;
INSERT INTO `bets` VALUES (1,1,1,1,'red',500,1.900,950,'won','2026-11-11 00:02:42','2026-11-11 00:02:42',NULL,NULL,NULL,'2026-11-11 00:02:42','2026-11-11 00:02:42'),(2,1,2,2,'blue',750,1.950,1462,'lost','2026-11-11 00:02:42','2026-11-11 00:02:42',NULL,NULL,NULL,'2026-11-11 00:02:42','2026-11-11 00:02:42'),(3,2,1,1,'red',300,1.750,525,'active','2026-11-11 00:02:42',NULL,NULL,NULL,NULL,'2026-11-11 00:02:42','2026-11-11 00:02:42'),(4,2,2,2,'blue',400,2.100,840,'active','2026-11-11 00:02:42',NULL,NULL,NULL,NULL,'2026-11-11 00:02:42','2026-11-11 00:02:42');
/*!40000 ALTER TABLE `bets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('e-sabong-virtual-console-cache-5c785c036466adea360111aa28563bfd556b5fba','i:1;',1794386656),('e-sabong-virtual-console-cache-5c785c036466adea360111aa28563bfd556b5fba:timer','i:1794386656;',1794386656);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cocks`
--

DROP TABLE IF EXISTS `cocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `breed` varchar(255) DEFAULT NULL,
  `weight_grams` int(10) unsigned DEFAULT NULL,
  `owner_name` varchar(255) DEFAULT NULL,
  `band_number` varchar(30) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cocks`
--

LOCK TABLES `cocks` WRITE;
/*!40000 ALTER TABLE `cocks` DISABLE KEYS */;
INSERT INTO `cocks` VALUES (1,'Thunder','Kelso',2100,'R. Bautista','B-001','available','2026-11-11 00:02:42','2026-11-11 00:02:42'),(2,'Lightning','Hatch',2050,'D. Villanueva','B-002','available','2026-11-11 00:02:42','2026-11-11 00:02:42'),(3,'Storm','Sweater',2200,'J. Aquino','B-003','available','2026-11-11 00:02:42','2026-11-11 00:02:42'),(4,'Blaze','Roundhead',2150,'F. Mercado','B-004','available','2026-11-11 00:02:42','2026-11-11 00:02:42'),(5,'Titan','Kelso',2300,'M. Ocampo','B-005','available','2026-11-11 00:02:42','2026-11-11 00:02:42'),(6,'Viper','Hatch',2250,'A. Salazar','B-006','available','2026-11-11 00:02:42','2026-11-11 00:02:42'),(7,'Falcon','Asil',2000,'P. Navarro','B-007','available','2026-11-11 00:02:42','2026-11-11 00:02:42'),(8,'Rogue','Sweater',2080,'E. Domingo','B-008','available','2026-11-11 00:02:42','2026-11-11 00:02:42');
/*!40000 ALTER TABLE `cocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `name` varchar(255) NOT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `game_master_id` bigint(20) unsigned NOT NULL,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `events_code_unique` (`code`),
  KEY `events_game_master_id_foreign` (`game_master_id`),
  CONSTRAINT `events_game_master_id_foreign` FOREIGN KEY (`game_master_id`) REFERENCES `game_master_profiles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
INSERT INTO `events` VALUES (1,'EVT-0101','Friday Night Derby','North Arena',1,'2026-11-10 23:02:42',NULL,'open','2026-11-11 00:02:42','2026-11-11 00:02:42'),(2,'EVT-0102','South Side Showdown','South Arena',2,'2026-11-12 00:02:42',NULL,'draft','2026-11-11 00:02:42','2026-11-11 00:02:42');
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
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
-- Table structure for table `fight_events`
--

DROP TABLE IF EXISTS `fight_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fight_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fight_id` bigint(20) unsigned NOT NULL,
  `kind` varchar(30) NOT NULL,
  `round_no` tinyint(3) unsigned DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fight_events_fight_id_foreign` (`fight_id`),
  CONSTRAINT `fight_events_fight_id_foreign` FOREIGN KEY (`fight_id`) REFERENCES `fights` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fight_events`
--

LOCK TABLES `fight_events` WRITE;
/*!40000 ALTER TABLE `fight_events` DISABLE KEYS */;
INSERT INTO `fight_events` VALUES (1,1,'betting_open',NULL,'Betting window opened.',2,'2026-11-11 00:02:42'),(2,1,'bet_placed',NULL,'Bet #1 red for 500 credits.',4,'2026-11-11 00:02:42'),(3,1,'bet_placed',NULL,'Bet #2 blue for 750 credits.',5,'2026-11-11 00:02:42'),(4,1,'betting_locked',NULL,'Betting closed. No new bets accepted.',2,'2026-11-11 00:02:42'),(5,1,'result',NULL,'Winner: RED. Payouts: 950 credits.',2,'2026-11-11 00:02:42'),(6,2,'betting_open',NULL,'Betting window opened.',2,'2026-11-11 00:02:42'),(7,2,'bet_placed',NULL,'Bet #3 red for 300 credits.',4,'2026-11-11 00:02:42'),(8,2,'bet_placed',NULL,'Bet #4 blue for 400 credits.',5,'2026-11-11 00:02:42');
/*!40000 ALTER TABLE `fight_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fights`
--

DROP TABLE IF EXISTS `fights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fights` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) unsigned NOT NULL,
  `fight_no` int(10) unsigned NOT NULL,
  `red_cock_id` bigint(20) unsigned NOT NULL,
  `blue_cock_id` bigint(20) unsigned NOT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `odds_red` decimal(6,3) NOT NULL DEFAULT 1.850,
  `odds_blue` decimal(6,3) NOT NULL DEFAULT 1.850,
  `status` varchar(20) NOT NULL DEFAULT 'scheduled',
  `betting_opened_at` timestamp NULL DEFAULT NULL,
  `betting_locked_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `winner` varchar(10) DEFAULT NULL,
  `declared_by` bigint(20) unsigned DEFAULT NULL,
  `declared_at` timestamp NULL DEFAULT NULL,
  `void_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fights_event_id_fight_no_unique` (`event_id`,`fight_no`),
  KEY `fights_red_cock_id_foreign` (`red_cock_id`),
  KEY `fights_blue_cock_id_foreign` (`blue_cock_id`),
  KEY `fights_status_scheduled_at_index` (`status`,`scheduled_at`),
  CONSTRAINT `fights_blue_cock_id_foreign` FOREIGN KEY (`blue_cock_id`) REFERENCES `cocks` (`id`),
  CONSTRAINT `fights_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fights_red_cock_id_foreign` FOREIGN KEY (`red_cock_id`) REFERENCES `cocks` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fights`
--

LOCK TABLES `fights` WRITE;
/*!40000 ALTER TABLE `fights` DISABLE KEYS */;
INSERT INTO `fights` VALUES (1,1,1,1,2,'2026-11-10 23:32:42',1.900,1.950,'settled','2026-11-11 00:02:42','2026-11-11 00:02:42',NULL,'2026-11-11 00:02:42','red',2,'2026-11-11 00:02:42',NULL,'2026-11-11 00:02:42','2026-11-11 00:02:42'),(2,1,2,3,4,'2026-11-11 00:17:42',1.750,2.100,'betting_open','2026-11-11 00:02:42',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-11 00:02:42','2026-11-11 00:02:42'),(3,1,3,5,6,'2026-11-11 00:47:42',1.850,2.000,'scheduled',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-11 00:02:42','2026-11-11 00:02:42'),(4,1,4,7,8,'2026-11-11 01:17:42',1.950,1.900,'scheduled',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-11 00:02:42','2026-11-11 00:02:42');
/*!40000 ALTER TABLE `fights` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `game_master_profiles`
--

DROP TABLE IF EXISTS `game_master_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_master_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `employee_no` varchar(40) DEFAULT NULL,
  `contact` varchar(60) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `game_master_profiles_user_id_unique` (`user_id`),
  CONSTRAINT `game_master_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `game_master_profiles`
--

LOCK TABLES `game_master_profiles` WRITE;
/*!40000 ALTER TABLE `game_master_profiles` DISABLE KEYS */;
INSERT INTO `game_master_profiles` VALUES (1,2,'GM-001','North Arena pit',NULL,'2026-11-11 00:02:41','2026-11-11 00:02:41'),(2,3,'GM-002','South Arena pit',NULL,'2026-11-11 00:02:41','2026-11-11 00:02:41');
/*!40000 ALTER TABLE `game_master_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_events`
--

DROP TABLE IF EXISTS `login_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `username_attempt` varchar(50) DEFAULT NULL,
  `workstation_id` bigint(20) unsigned DEFAULT NULL,
  `kind` varchar(30) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `login_events_user_id_index` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_events`
--

LOCK TABLES `login_events` WRITE;
/*!40000 ALTER TABLE `login_events` DISABLE KEYS */;
INSERT INTO `login_events` VALUES (1,1,'supervisor',NULL,'login','127.0.0.1','curl/8.22.0','2026-11-11 00:04:51'),(2,4,'pos01',1,'login','127.0.0.1','curl/8.22.0','2026-11-11 00:04:53'),(3,2,'gm.north',NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','2026-11-11 00:39:37'),(4,2,'gm.north',NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-11 00:40:03'),(5,4,'pos01',1,'login','127.0.0.1','curl/8.22.0','2026-11-11 00:43:16');
/*!40000 ALTER TABLE `login_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_01_01_000002_create_account_tables',1),(5,'2026_01_01_000003_create_event_tables',1),(6,'2026_01_01_000004_create_credit_tables',1),(7,'2026_01_01_000005_create_monitoring_tables',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report_snapshots`
--

DROP TABLE IF EXISTS `report_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `report_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `scope` varchar(30) NOT NULL,
  `scope_ref_id` bigint(20) unsigned DEFAULT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `payload_hash` varchar(64) NOT NULL,
  `prev_hash` varchar(64) DEFAULT NULL,
  `generated_by` bigint(20) unsigned DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `report_snapshots_scope_scope_ref_id_index` (`scope`,`scope_ref_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_snapshots`
--

LOCK TABLES `report_snapshots` WRITE;
/*!40000 ALTER TABLE `report_snapshots` DISABLE KEYS */;
INSERT INTO `report_snapshots` VALUES (1,'fight',1,'2026-11-11','2026-11-11','{\"title\":\"Fight report\",\"fight\":{\"id\":1,\"label\":\"Fight #1 \\u2014 Thunder vs Lightning\",\"event\":\"Friday Night Derby\",\"status\":\"settled\",\"winner\":\"red\",\"odds_red\":1.9,\"odds_blue\":1.95},\"totals\":{\"bets\":2,\"red_stake\":500,\"blue_stake\":750,\"stake_total\":1250,\"payout_total\":950,\"refunded_total\":0},\"rows\":[{\"bet_id\":1,\"teller\":\"POS 001\",\"side\":\"red\",\"amount\":500,\"odds\":1.9,\"potential_payout\":950,\"status\":\"won\",\"placed_at\":\"2026-11-11 08:02:42\"},{\"bet_id\":2,\"teller\":\"POS 002\",\"side\":\"blue\",\"amount\":750,\"odds\":1.95,\"potential_payout\":1462,\"status\":\"lost\",\"placed_at\":\"2026-11-11 08:02:42\"}]}','ab67ba22db5a6d0edaf0702579325ee16e22b66b62ab0d34fbfa18bdf95cd8b0',NULL,1,'2026-11-11 00:02:42');
/*!40000 ALTER TABLE `report_snapshots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
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
INSERT INTO `sessions` VALUES ('13XYiQ616PyfPEPiuR3BT55um9KTG1WFGAYUhJi1',4,'127.0.0.1','curl/8.22.0','YTo2OntzOjY6Il90b2tlbiI7czo0MDoiNUs0ekRBQTZhanYxRlN4WFluTzFhMDI5NlJnVjFTS1dBMmZWZHRlTSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDM6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9wb3Mvc2hpZnRzLzEvY2xvc2VvdXQiO3M6NToicm91dGUiO3M6MTk6InBvcy5zaGlmdHMuY2xvc2VvdXQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo0O3M6MTc6InBhc3N3b3JkX2hhc2hfd2ViIjtzOjY0OiIxYTZlZjIxMDlmNGNlZTQ5ZjNkYmJlYjcwYTQ0NjE4YTRlODNiNjg3OTUyYzFkOTA4ZDA2YTQ1NmUwNmNlZTM4IjtzOjE0OiJ3b3Jrc3RhdGlvbl9pZCI7aToxO30=',1794386598),('6ReuAy0YW7JNRkw3HHG0NqZ9EGFgWIusFRz5G5AL',NULL,'127.0.0.1','curl/8.22.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUzZobDVMYXhHaFpUUTJnMFZReXBsQnJTT0t3MXUxSk1hWEw3SGIxeCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjg6Imh0dHBzOi8vbG9jYWxob3N0Ojg0NDMvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1794384290),('cRHYZQNA4zwSRjnTKtEjYXEjGz6YNxGPL2pyb7h2',4,'127.0.0.1','curl/8.22.0','YTo2OntzOjY6Il90b2tlbiI7czo0MDoid2pKYUVCUnpiTk5QUDIzVXlvRHEyTmF6MHhaRmF6WWtyR2RYTGp0aCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vbG9jYWxob3N0Ojg0NDMvZ20iO3M6NToicm91dGUiO3M6MTI6ImdtLmRhc2hib2FyZCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjQ7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjQ6IjFhNmVmMjEwOWY0Y2VlNDlmM2RiYmViNzBhNDQ2MThhNGU4M2I2ODc5NTJjMWQ5MDhkMDZhNDU2ZTA2Y2VlMzgiO3M6MTQ6IndvcmtzdGF0aW9uX2lkIjtpOjE7fQ==',1794384294),('fRkOBZOkmdTQ7QJP0Q6J1JbfOiunHJbkFpkxxhOc',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','YTo2OntzOjY6Il90b2tlbiI7czo0MDoidHFxMEdDa1BSRzVWTnl3VTVhMFF5WnlMRDRTM1Q3dVVkeDJLWGUyViI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjQ6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9nbSI7czo1OiJyb3V0ZSI7czoxMjoiZ20uZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2NDoiZGRkZDBkNDI4YWM1OWY1OTM2YjZlYzM0YTVhYjUwN2JjZjdlYjFhY2YxMjZhN2JkYjA2MDVlMTY5Yjg3OGU2MCI7czoxNDoid29ya3N0YXRpb25faWQiO047fQ==',1794386377),('K86FMexJH8LqJktEIwcCeq49tdxaBfGjiZZPKw4T',NULL,'127.0.0.1','curl/8.22.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiU285OVlwNDNHZWN2MlhyREJ5UnBBWjh6Y2JjVnJQSEMxQzRNZ0VPVSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vbG9jYWxob3N0Ojg0NDMiO3M6NToicm91dGUiO047fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1794384290),('lJ2qixbsNYcnxWj4CfT6bWV1r99qH9b9cQTK9U9x',NULL,'127.0.0.1','curl/8.22.0','YToyOntzOjY6Il90b2tlbiI7czo0MDoia1FLUU1XN0tHTFFNYTVWU3UxcjVCWEpybW9tS2N3VzNpNHJHd2VVbCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1794384290),('lWaQTn9Cnu7RQz228a6BRTMVoukmkd5oBeZnukqU',1,'127.0.0.1','curl/8.22.0','YTo2OntzOjY6Il90b2tlbiI7czo0MDoicW80d3pjSzh4MUNlMXVFWmVweGxGRkplZVcyYkJmTmpkTjVzY3NZNSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzk6Imh0dHBzOi8vbG9jYWxob3N0Ojg0NDMvc3VwZXJ2aXNvci9hdWRpdCI7czo1OiJyb3V0ZSI7czoxNjoic3VwZXJ2aXNvci5hdWRpdCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjQ6ImRjYzE4NDIzZmQ0MDcxNjk3NzhmNWRmOWQwOGQ2MDZjNDllODMwYjA2ODhkOWMwZmEwNDRmYmJmZTViYjQzOTMiO3M6MTQ6IndvcmtzdGF0aW9uX2lkIjtOO30=',1794384292),('tqVV6Av9tnprACdyYIa2djuJkT3Jwz5VxKtcmUlU',NULL,'127.0.0.1','curl/8.22.0','YTo0OntzOjY6Il90b2tlbiI7czo0MDoia0w1YzNmYVRKNHNldkFHMTZHamNkandxSGgydkpOa1luZ2NoS2hLTSI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozMzoiaHR0cHM6Ly9sb2NhbGhvc3Q6ODQ0My9zdXBlcnZpc29yIjt9czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzM6Imh0dHBzOi8vbG9jYWxob3N0Ojg0NDMvc3VwZXJ2aXNvciI7czo1OiJyb3V0ZSI7czoyMDoic3VwZXJ2aXNvci5kYXNoYm9hcmQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1794384355),('uXk1D16VP91lQjGSlCx6I7lnRUvIQ0Lp36daSlbB',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','YTo3OntzOjY6Il90b2tlbiI7czo0MDoiMEplRkw2aFdrY25QR1Y0T1puYzdEYUhjZ1RIaTJXZWx4bFVlN05zciI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjMyOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvZ20vdGVsbGVycyI7czo1OiJyb3V0ZSI7czoxMDoiZ20udGVsbGVycyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjI7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjQ6ImRkZGQwZDQyOGFjNTlmNTkzNmI2ZWMzNGE1YWI1MDdiY2Y3ZWIxYWNmMTI2YTdiZGIwNjA1ZTE2OWI4NzhlNjAiO3M6MTQ6IndvcmtzdGF0aW9uX2lkIjtOO30=',1794386513),('V5nKl9YmUmJEY7PHlyseAhW5soeFzGeNij8HAWux',NULL,'127.0.0.1','Bun/1.4.2','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRUJSdnZINVNYTjJuS054M0FqSXNZSmdwN0Vka2YyV0NYbWhjU2dKRiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1794384358),('vFcgjXNQlS5Ftb2usPohNC5GtCPhurSeEhlxw1EW',NULL,'127.0.0.1','curl/8.22.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiQnVVTjNhb2Ryc05ocnVFRFEwRGR2bE5MMFozNndzZjZZbXFtbTdPTSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjg6Imh0dHBzOi8vbG9jYWxob3N0Ojg0NDMvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1794384355);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_sessions`
--

DROP TABLE IF EXISTS `shift_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shift_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `teller_id` bigint(20) unsigned NOT NULL,
  `workstation_id` bigint(20) unsigned DEFAULT NULL,
  `opened_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `opening_note` text DEFAULT NULL,
  `closing_note` text DEFAULT NULL,
  `total_bets_count` int(10) unsigned NOT NULL DEFAULT 0,
  `total_amount_credits` bigint(20) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `closed_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shift_sessions_workstation_id_foreign` (`workstation_id`),
  KEY `shift_sessions_teller_id_status_index` (`teller_id`,`status`),
  CONSTRAINT `shift_sessions_teller_id_foreign` FOREIGN KEY (`teller_id`) REFERENCES `teller_profiles` (`id`),
  CONSTRAINT `shift_sessions_workstation_id_foreign` FOREIGN KEY (`workstation_id`) REFERENCES `workstations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_sessions`
--

LOCK TABLES `shift_sessions` WRITE;
/*!40000 ALTER TABLE `shift_sessions` DISABLE KEYS */;
INSERT INTO `shift_sessions` VALUES (1,1,1,'2026-11-10 22:02:42',NULL,'Seeded demo shift',NULL,0,0,'open',NULL,'2026-11-11 00:02:42','2026-11-11 00:02:42'),(2,2,2,'2026-11-10 22:02:42',NULL,'Seeded demo shift',NULL,0,0,'open',NULL,'2026-11-11 00:02:42','2026-11-11 00:02:42'),(3,3,3,'2026-11-10 22:02:42',NULL,'Seeded demo shift',NULL,0,0,'open',NULL,'2026-11-11 00:02:42','2026-11-11 00:02:42');
/*!40000 ALTER TABLE `shift_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teller_profiles`
--

DROP TABLE IF EXISTS `teller_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teller_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `teller_code` varchar(20) NOT NULL,
  `game_master_id` bigint(20) unsigned NOT NULL,
  `workstation_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teller_profiles_user_id_unique` (`user_id`),
  UNIQUE KEY `teller_profiles_teller_code_unique` (`teller_code`),
  KEY `teller_profiles_game_master_id_foreign` (`game_master_id`),
  KEY `teller_profiles_workstation_id_foreign` (`workstation_id`),
  CONSTRAINT `teller_profiles_game_master_id_foreign` FOREIGN KEY (`game_master_id`) REFERENCES `game_master_profiles` (`id`),
  CONSTRAINT `teller_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teller_profiles_workstation_id_foreign` FOREIGN KEY (`workstation_id`) REFERENCES `workstations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teller_profiles`
--

LOCK TABLES `teller_profiles` WRITE;
/*!40000 ALTER TABLE `teller_profiles` DISABLE KEYS */;
INSERT INTO `teller_profiles` VALUES (1,4,'POS 001',1,1,'active','2026-11-11 00:02:42','2026-11-11 00:02:42'),(2,5,'POS 002',1,2,'active','2026-11-11 00:02:42','2026-11-11 00:02:42'),(3,6,'POS 003',2,3,'active','2026-11-11 00:02:42','2026-11-11 00:02:42');
/*!40000 ALTER TABLE `teller_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'teller',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_created_by_foreign` (`created_by`),
  CONSTRAINT `users_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'System Supervisor','supervisor',NULL,'$2y$12$89xp/b0SGgzTMk9w5xvCWeAuiUqGYhY4xTBBBBxyeUn0Sathl5qKm','supervisor','active','2026-11-11 00:04:51',NULL,NULL,'2026-11-11 00:02:41','2026-11-11 00:04:51'),(2,'Marco Reyes','gm.north',NULL,'$2y$12$FgumFohbsF4lRC3wEhT2U.yLKLy8xK8EBjot0ozGuh2km/BcDw2w2','game_master','active','2026-11-11 00:40:03',1,NULL,'2026-11-11 00:02:41','2026-11-11 00:40:03'),(3,'Liza Cruz','gm.south',NULL,'$2y$12$YU61lDdvbvyRIH0xzNVPU.0M0N/WLPKpvAWNF2jJvK5wYSLe5tRXG','game_master','active',NULL,1,NULL,'2026-11-11 00:02:41','2026-11-11 00:02:41'),(4,'Ana Dela Cruz','pos01',NULL,'$2y$12$DemMSlmBUTDWINeKvypdXe6SD6QLHyHUSrgk2JWkKqxwY6.B064HW','teller','active','2026-11-11 00:43:16',1,NULL,'2026-11-11 00:02:42','2026-11-11 00:43:16'),(5,'Ben Santos','pos02',NULL,'$2y$12$a7CqdkdfW/P6cuEnpwm0TeJZt7y5JAdFuN7guf0DFJJPViqOWgKRC','teller','active',NULL,1,NULL,'2026-11-11 00:02:42','2026-11-11 00:02:42'),(6,'Cora Lim','pos03',NULL,'$2y$12$eGDtFxqdEm60y1oMVNMR/.WfUdoLCDVDbwKmXna.tk3yDdMkVHR9S','teller','active',NULL,1,NULL,'2026-11-11 00:02:42','2026-11-11 00:02:42');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `virtual_wallets`
--

DROP TABLE IF EXISTS `virtual_wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `virtual_wallets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `balance_credits` bigint(20) NOT NULL DEFAULT 0,
  `version` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `virtual_wallets_user_id_unique` (`user_id`),
  CONSTRAINT `virtual_wallets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `virtual_wallets`
--

LOCK TABLES `virtual_wallets` WRITE;
/*!40000 ALTER TABLE `virtual_wallets` DISABLE KEYS */;
INSERT INTO `virtual_wallets` VALUES (1,1,0,0,'2026-11-11 00:02:41','2026-11-11 00:02:41'),(2,4,150150,4,'2026-11-11 00:02:42','2026-11-11 00:02:42'),(3,5,148850,3,'2026-11-11 00:02:42','2026-11-11 00:02:42'),(4,6,150000,1,'2026-11-11 00:02:42','2026-11-11 00:02:42');
/*!40000 ALTER TABLE `virtual_wallets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallet_transactions`
--

DROP TABLE IF EXISTS `wallet_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wallet_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `wallet_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `fight_id` bigint(20) unsigned DEFAULT NULL,
  `bet_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(40) NOT NULL,
  `direction` varchar(10) NOT NULL,
  `amount_credits` bigint(20) NOT NULL,
  `balance_after` bigint(20) NOT NULL,
  `reference_no` varchar(40) NOT NULL,
  `idempotency_key` varchar(64) DEFAULT NULL,
  `performed_by` bigint(20) unsigned DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `wallet_transactions_reference_no_unique` (`reference_no`),
  UNIQUE KEY `wallet_transactions_idempotency_key_unique` (`idempotency_key`),
  KEY `wallet_transactions_wallet_id_foreign` (`wallet_id`),
  KEY `wallet_transactions_fight_id_foreign` (`fight_id`),
  KEY `wallet_transactions_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `wallet_transactions_bet_id_index` (`bet_id`),
  CONSTRAINT `wallet_transactions_fight_id_foreign` FOREIGN KEY (`fight_id`) REFERENCES `fights` (`id`) ON DELETE SET NULL,
  CONSTRAINT `wallet_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wallet_transactions_wallet_id_foreign` FOREIGN KEY (`wallet_id`) REFERENCES `virtual_wallets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallet_transactions`
--

LOCK TABLES `wallet_transactions` WRITE;
/*!40000 ALTER TABLE `wallet_transactions` DISABLE KEYS */;
INSERT INTO `wallet_transactions` VALUES (1,2,4,NULL,NULL,'seed','credit',150000,150000,'TXN-261111-NWRCTTLC',NULL,1,'Demo credit allocation','2026-11-11 00:02:42'),(2,3,5,NULL,NULL,'seed','credit',150000,150000,'TXN-261111-BKU2BTE6',NULL,1,'Demo credit allocation','2026-11-11 00:02:42'),(3,4,6,NULL,NULL,'seed','credit',150000,150000,'TXN-261111-JTNAL2ZC',NULL,1,'Demo credit allocation','2026-11-11 00:02:42'),(4,2,4,1,1,'bet_hold','debit',500,149500,'TXN-261111-KPCJU59M',NULL,4,'Bet hold — Fight #1 — Thunder vs Lightning','2026-11-11 00:02:42'),(5,3,5,1,2,'bet_hold','debit',750,149250,'TXN-261111-LG4WPYCE',NULL,5,'Bet hold — Fight #1 — Thunder vs Lightning','2026-11-11 00:02:42'),(6,2,4,1,1,'payout','credit',950,150450,'TXN-261111-EIGIZ0BY',NULL,2,'Payout — Fight #1 — Thunder vs Lightning','2026-11-11 00:02:42'),(7,2,4,2,3,'bet_hold','debit',300,150150,'TXN-261111-OCQNMZ49',NULL,4,'Bet hold — Fight #2 — Storm vs Blaze','2026-11-11 00:02:42'),(8,3,5,2,4,'bet_hold','debit',400,148850,'TXN-261111-QTFAF1BG',NULL,5,'Bet hold — Fight #2 — Storm vs Blaze','2026-11-11 00:02:42');
/*!40000 ALTER TABLE `wallet_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workstations`
--

DROP TABLE IF EXISTS `workstations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workstations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `label` varchar(80) NOT NULL,
  `device_uuid` varchar(64) DEFAULT NULL,
  `assigned_teller_id` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `workstations_code_unique` (`code`),
  UNIQUE KEY `workstations_device_uuid_unique` (`device_uuid`),
  KEY `workstations_assigned_teller_id_index` (`assigned_teller_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workstations`
--

LOCK TABLES `workstations` WRITE;
/*!40000 ALTER TABLE `workstations` DISABLE KEYS */;
INSERT INTO `workstations` VALUES (1,'POS-001','Counter 1 — North','pos-north-1',1,'2026-11-11 00:02:41','2026-11-11 00:43:16','2026-11-11 00:02:41','2026-11-11 00:43:16'),(2,'POS-002','Counter 2 — North','pos-north-2',2,'2026-11-11 00:02:42','2026-11-11 00:02:42','2026-11-11 00:02:42','2026-11-11 00:02:42'),(3,'POS-003','Counter 1 — South','pos-south-1',3,'2026-11-11 00:02:42','2026-11-11 00:02:42','2026-11-11 00:02:42','2026-11-11 00:02:42');
/*!40000 ALTER TABLE `workstations` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-11 16:44:25
