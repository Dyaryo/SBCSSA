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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'supervisor','floor.reset',NULL,NULL,NULL,'{\"cleared\":{\"events\":2,\"fights\":8,\"bets\":2,\"shifts\":2,\"ledger\":8,\"snapshots\":0},\"till\":0,\"accounts_kept\":6,\"workstations_kept\":3}','127.0.0.1',NULL,'2026-11-14 10:17:39'),(2,4,'teller','shift.opened','ShiftSession',1,NULL,'{\"workstation_id\":1,\"attendance\":true}','127.0.0.1',1,'2026-11-14 10:22:46'),(3,4,'teller','shift.closed','ShiftSession',1,NULL,'{\"bets\":0,\"stake_total\":0,\"attendance\":true}','127.0.0.1',1,'2026-11-14 10:25:44'),(4,2,'game_master','event.created','Event',1,NULL,'{\"name\":\"HACKFIGHT \\/ 2 COCK ULUTAN\",\"code\":\"REG-261114-1TUO\",\"type\":\"regular\",\"entries\":null,\"stop_ratio\":1.4}','127.0.0.1',NULL,'2026-11-14 10:28:22'),(5,2,'game_master','fight.created','Fight',1,NULL,'{\"fight_no\":\"1\",\"event_id\":1}','127.0.0.1',NULL,'2026-11-14 10:28:30'),(6,2,'game_master','fight.betting_opened','Fight',1,NULL,'{\"status\":\"betting_open\"}','127.0.0.1',NULL,'2026-11-14 10:34:09'),(7,2,'game_master','fight.side_locked','Fight',1,NULL,'{\"side\":\"red\",\"locked\":true}','127.0.0.1',NULL,'2026-11-14 10:34:18'),(8,2,'game_master','fight.side_unlocked','Fight',1,NULL,'{\"side\":\"red\",\"locked\":false}','127.0.0.1',NULL,'2026-11-14 10:35:28'),(9,2,'game_master','fight.side_locked','Fight',1,NULL,'{\"side\":\"red\",\"locked\":true}','127.0.0.1',NULL,'2026-11-14 10:45:14'),(10,2,'game_master','fight.side_unlocked','Fight',1,NULL,'{\"side\":\"red\",\"locked\":false}','127.0.0.1',NULL,'2026-11-14 10:45:25'),(11,2,'game_master','fight.side_locked','Fight',1,NULL,'{\"side\":\"red\",\"locked\":true}','127.0.0.1',NULL,'2026-11-14 10:45:34'),(12,1,'supervisor','event.stop_ratio_changed','Event',1,'{\"stop_ratio\":1.4}','{\"stop_ratio\":2}',NULL,NULL,'2026-11-14 10:45:36'),(13,2,'game_master','fight.side_unlocked','Fight',1,NULL,'{\"side\":\"red\",\"locked\":false}','127.0.0.1',NULL,'2026-11-14 10:57:24'),(14,2,'game_master','fight.side_locked','Fight',1,NULL,'{\"side\":\"red\",\"locked\":true}','127.0.0.1',NULL,'2026-11-14 10:57:28'),(15,2,'game_master','fight.side_unlocked','Fight',1,NULL,'{\"side\":\"red\",\"locked\":false}','127.0.0.1',NULL,'2026-11-14 10:57:31'),(16,4,'teller','shift.opened','ShiftSession',2,NULL,'{\"workstation_id\":1,\"attendance\":true}','127.0.0.1',1,'2026-11-14 10:58:54'),(17,4,'teller','shift.closed','ShiftSession',2,NULL,'{\"bets\":0,\"stake_total\":0,\"attendance\":true}','127.0.0.1',1,'2026-11-14 10:59:05'),(18,1,'supervisor','wallet.seeded','User',4,NULL,'{\"amount\":1000,\"balance_after\":1000}','127.0.0.1',NULL,'2026-11-14 10:59:23'),(19,4,'teller','shift.opened','ShiftSession',3,NULL,'{\"workstation_id\":1,\"attendance\":true}','127.0.0.1',1,'2026-11-14 10:59:40'),(20,2,'game_master','workstation.bound','Workstation',1,'{\"device_uuid\":\"e9ad4e8b-6f7c-4424-96f2-63e06cfea5ac\",\"approved_at\":\"2026-11-14 19:23:11\"}','{\"device_uuid\":\"4346a97c-3bc4-4006-814b-2fc0274ee4a3\",\"assigned_teller_id\":1}','127.0.0.1',NULL,'2026-11-14 11:24:53');
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
  `paid_out_at` timestamp NULL DEFAULT NULL,
  `paid_out_by` bigint(20) unsigned DEFAULT NULL,
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
  KEY `bets_teller_paid_out_index` (`teller_id`,`paid_out_at`),
  CONSTRAINT `bets_fight_id_foreign` FOREIGN KEY (`fight_id`) REFERENCES `fights` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bets_shift_session_id_foreign` FOREIGN KEY (`shift_session_id`) REFERENCES `shift_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bets_teller_id_foreign` FOREIGN KEY (`teller_id`) REFERENCES `teller_profiles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bets`
--

LOCK TABLES `bets` WRITE;
/*!40000 ALTER TABLE `bets` DISABLE KEYS */;
INSERT INTO `bets` VALUES (2,1,1,3,'red',100,1.850,185,'active','2026-11-14 10:59:46',NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-14 10:59:46','2026-11-14 10:59:46'),(3,1,1,3,'draw',100,7.000,700,'active','2026-11-14 11:11:16',NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-14 11:11:16','2026-11-14 11:11:16');
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
INSERT INTO `cache` VALUES ('e-sabong-virtual-console-cache-1a08ff3537b58141d5c2a9021f9f4d924e3f67a1','i:1;',1794684672),('e-sabong-virtual-console-cache-1a08ff3537b58141d5c2a9021f9f4d924e3f67a1:timer','i:1794684672;',1794684672),('e-sabong-virtual-console-cache-2697c212a0ed3a80c6170c8290e8262f31aff77a','i:2;',1794394873),('e-sabong-virtual-console-cache-2697c212a0ed3a80c6170c8290e8262f31aff77a:timer','i:1794394873;',1794394873),('e-sabong-virtual-console-cache-5c785c036466adea360111aa28563bfd556b5fba','i:1;',1794684440),('e-sabong-virtual-console-cache-5c785c036466adea360111aa28563bfd556b5fba:timer','i:1794684440;',1794684440),('e-sabong-virtual-console-cache-login:pos 001|127.0.0.1','i:1;',1794684680),('e-sabong-virtual-console-cache-login:pos 001|127.0.0.1:timer','i:1794684680;',1794684680);
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
INSERT INTO `cache_locks` VALUES ('e-sabong-virtual-console-cache-framework\\schedule-d6abe685ab037bed9472bbbdc0f4110e3e164fd3','n4dvnaVY028MKwEs',1794701700);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cocks`
--

LOCK TABLES `cocks` WRITE;
/*!40000 ALTER TABLE `cocks` DISABLE KEYS */;
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
  `name` varchar(255) DEFAULT NULL,
  `planned_fights` int(10) unsigned DEFAULT NULL,
  `bomba_amount` bigint(20) NOT NULL DEFAULT 0,
  `bomba_breakdown` int(10) unsigned NOT NULL DEFAULT 3,
  `bomba_interval` int(10) unsigned NOT NULL DEFAULT 30,
  `type` varchar(20) NOT NULL DEFAULT 'regular',
  `entries` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`entries`)),
  `venue` varchar(255) DEFAULT NULL,
  `game_master_id` bigint(20) unsigned NOT NULL,
  `starts_at` timestamp NULL DEFAULT NULL,
  `stop_ratio` decimal(6,3) DEFAULT NULL,
  `draw_ratio` decimal(6,3) NOT NULL DEFAULT 7.000,
  `payout_display` varchar(10) NOT NULL DEFAULT 'decimals',
  `tv_total_bets` tinyint(3) unsigned NOT NULL DEFAULT 100,
  `ends_at` timestamp NULL DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `events_code_unique` (`code`),
  KEY `events_game_master_id_foreign` (`game_master_id`),
  CONSTRAINT `events_game_master_id_foreign` FOREIGN KEY (`game_master_id`) REFERENCES `game_master_profiles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
INSERT INTO `events` VALUES (1,'REG-261114-1TUO','HACKFIGHT / 2 COCK ULUTAN',500,0,3,30,'regular',NULL,'CSSA',1,'2026-11-13 16:00:00',2.000,7.000,'decimals',100,NULL,'draft','2026-11-14 10:28:22','2026-11-14 10:45:36');
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fight_events`
--

LOCK TABLES `fight_events` WRITE;
/*!40000 ALTER TABLE `fight_events` DISABLE KEYS */;
INSERT INTO `fight_events` VALUES (1,1,'created',NULL,'Fight scheduled.',2,'2026-11-14 10:28:30'),(2,1,'betting_open',NULL,'Betting window opened.',2,'2026-11-14 10:34:09'),(3,1,'side_locked',NULL,'MERON locked — no new bets on this side.',2,'2026-11-14 10:34:18'),(4,1,'side_unlocked',NULL,'MERON unlocked — bets accepted again.',2,'2026-11-14 10:35:28'),(5,1,'side_locked',NULL,'MERON locked — no new bets on this side.',2,'2026-11-14 10:45:14'),(6,1,'side_unlocked',NULL,'MERON unlocked — bets accepted again.',2,'2026-11-14 10:45:25'),(7,1,'side_locked',NULL,'MERON locked — no new bets on this side.',2,'2026-11-14 10:45:34'),(8,1,'side_unlocked',NULL,'MERON unlocked — bets accepted again.',2,'2026-11-14 10:57:24'),(9,1,'side_locked',NULL,'MERON locked — no new bets on this side.',2,'2026-11-14 10:57:28'),(10,1,'side_unlocked',NULL,'MERON unlocked — bets accepted again.',2,'2026-11-14 10:57:31'),(11,1,'bet_placed',NULL,'Bet #2 red for 100 credits.',4,'2026-11-14 10:59:47'),(12,1,'bet_placed',NULL,'Bet #3 draw for 100 credits.',4,'2026-11-14 11:11:16');
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
  `red_cock_id` bigint(20) unsigned DEFAULT NULL,
  `blue_cock_id` bigint(20) unsigned DEFAULT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `odds_red` decimal(6,3) NOT NULL DEFAULT 1.850,
  `odds_blue` decimal(6,3) NOT NULL DEFAULT 1.850,
  `red_locked` tinyint(1) NOT NULL DEFAULT 0,
  `blue_locked` tinyint(1) NOT NULL DEFAULT 0,
  `stop_ratio` decimal(6,3) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'scheduled',
  `betting_opened_at` timestamp NULL DEFAULT NULL,
  `betting_locked_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `winner` varchar(10) DEFAULT NULL,
  `result` varchar(20) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fights`
--

LOCK TABLES `fights` WRITE;
/*!40000 ALTER TABLE `fights` DISABLE KEYS */;
INSERT INTO `fights` VALUES (1,1,1,NULL,NULL,NULL,1.850,2.050,0,0,2.000,'betting_open','2026-11-14 10:34:09',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-14 10:28:30','2026-11-14 10:57:31');
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
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_events`
--

LOCK TABLES `login_events` WRITE;
/*!40000 ALTER TABLE `login_events` DISABLE KEYS */;
INSERT INTO `login_events` VALUES (1,1,'supervisor',NULL,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 10:17:47'),(2,2,'gm.north',NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 10:18:03'),(3,2,'gm.north',NULL,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 10:21:38'),(4,4,'pos01',1,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 10:22:46'),(5,4,'pos01',1,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 10:25:44'),(6,2,'gm.north',NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 10:27:11'),(7,4,'pos01',1,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 10:58:54'),(8,4,'pos01',1,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 10:59:05'),(9,1,'supervisor',NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 10:59:19'),(10,1,'supervisor',NULL,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 10:59:26'),(11,4,'pos01',1,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 10:59:40'),(12,4,'pos01',1,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','2026-11-14 11:14:34'),(13,4,'pos01',1,'blocked_login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 11:23:55'),(14,4,'pos01',1,'blocked_login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 11:24:09'),(15,4,'pos01',1,'blocked_login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 11:24:10'),(16,2,'gm.north',NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 11:24:38'),(17,4,'pos01',1,'blocked_login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 11:25:05'),(18,2,'gm.north',NULL,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 11:25:35'),(19,1,'supervisor',NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 11:25:47'),(20,NULL,'POS 001',NULL,'failed_login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 11:26:20'),(21,4,'pos01',1,'blocked_login','192.168.1.15','curl/8.22.0','2026-11-14 11:29:01'),(22,4,'pos01',1,'blocked_login','192.168.1.15','curl/8.22.0','2026-11-14 11:29:01'),(23,4,'pos01',1,'blocked_login','192.168.1.15','curl/8.22.0','2026-11-14 11:29:17'),(24,4,'pos01',1,'login','192.168.1.15','curl/8.22.0','2026-11-14 11:30:12'),(25,1,'supervisor',NULL,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 11:42:05');
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_01_01_000002_create_account_tables',1),(5,'2026_01_01_000003_create_event_tables',1),(6,'2026_01_01_000004_create_credit_tables',1),(7,'2026_01_01_000005_create_monitoring_tables',1),(8,'2026_01_01_000006_add_result_to_fights_table',2),(9,'2026_01_01_000007_add_payout_recording_to_bets_table',3),(10,'2026_01_01_000008_add_event_type_and_stop_ratio',4),(11,'2026_01_01_000009_meron_wala_and_event_stop_ratio',5),(12,'2026_01_01_000010_add_card_settings_to_events',6),(13,'2026_01_01_000011_add_draw_ratio_to_events',7),(14,'2026_11_13_120000_add_side_locks_to_fights',8);
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_snapshots`
--

LOCK TABLES `report_snapshots` WRITE;
/*!40000 ALTER TABLE `report_snapshots` DISABLE KEYS */;
INSERT INTO `report_snapshots` VALUES (1,'teller_shift',1,'2026-11-14','2026-11-14','{\"title\":\"Teller shift report\",\"shift\":{\"id\":1,\"teller_code\":\"POS 001\",\"teller_name\":\"Ana Dela Cruz\",\"workstation\":\"POS-001\",\"status\":\"closed\",\"opened_at\":\"2026-11-14 18:22:46\",\"closed_at\":\"2026-11-14 18:25:44\"},\"totals\":{\"bets\":0,\"stake_total\":0,\"active\":0,\"won\":0,\"voided\":0},\"rows\":[]}','f60a2215c610085d70ac3dc5118eeac301305e581d33aa091ac41b396dac7625',NULL,4,'2026-11-14 10:25:44'),(2,'teller_shift',2,'2026-11-14','2026-11-14','{\"title\":\"Teller shift report\",\"shift\":{\"id\":2,\"teller_code\":\"POS 001\",\"teller_name\":\"Ana Dela Cruz\",\"workstation\":\"POS-001\",\"status\":\"closed\",\"opened_at\":\"2026-11-14 18:58:54\",\"closed_at\":\"2026-11-14 18:59:05\"},\"totals\":{\"bets\":0,\"stake_total\":0,\"active\":0,\"won\":0,\"voided\":0},\"rows\":[]}','5bbf6edac19ee7cdc30611e027b0b4991c849fe1eec9abc7cecebd9aa7a4ceaf','f60a2215c610085d70ac3dc5118eeac301305e581d33aa091ac41b396dac7625',4,'2026-11-14 10:59:05');
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
INSERT INTO `sessions` VALUES ('0FMi8265QSCgJeMNy8aIQ81s87RYWtzjZqYk7AVJ',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','ZXlKcGRpSTZJbEpMUkdkS1VrbFFiSFJsWm1ONk5tTXpXRlZPTmxFOVBTSXNJblpoYkhWbElqb2lOMVJJU2xvdmRGSkxLMlYyV1RWamRsbzJkR2x3YVZSSWRVZHFUazFOTTFsd09HaE9lVVJRZFZsMVNtTkJlV3AwUlZFNFNtcGlRbXhsUVc5TFRVVXphRWw2YzFkd1FUZGpXbXBETldSc1FXeFRSVTB6UTFReFlVY3dlSGcyVWsxTE9XYzBlRkZ2ZFV4TVVUTlpNV2RwU3pVNGNrMUpRbEJWWTNaVmFUQmxTRzVWY0UxV1ZuVmtkazlPVHl0WVNXZHhiM1I2TDA0emFGSmpVRXBrT0VKMVkxZFlTamxZT1ROb1ZWSldUVTQzYTFoT1ZFa3hWWGhqWm1KblNFZGpZVXN4UWtKelRHdzFZMFp5VTBJM01HVjNVbVp1YkRKSVlYWkhWbGQzVVNzMlRreHFWQ3RGWlRJMlVqbE5SMUppUmt0cUszbEtZVlV6UzJGa1dXTXdabUpSY3k5SU5WWTJhREZKV1hadVJWRm1PRTUyZGt0S05VSktSak52ZWtWUlIxZFJORXRvY2xadWF5dDVkRlU5SWl3aWJXRmpJam9pTTJFMU5XSmpPVE5tTldFd01XRXpaamd3TTJVek1EVXdZek5tWkRrMk5XTXdZekZoTlRjd1l6azBZams0TkRreU9EVmtZbU5rTWpjek1UYzFNRGxsT0NJc0luUmhaeUk2SWlKOQ==',1794685329),('5kNp0xKqBiiAVdaWp6rAukHd7ZekFPQJ4cHK8X9H',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJbXR3ZG14TVUzUkNaM0JwWms1b1ZYa3lNbmMwZVVFOVBTSXNJblpoYkhWbElqb2llakYwU2s1aFRFVnVaR3BUYkZkNU4wdFJjRVJPVlZWQlVqRkZiMnBaZVV4ME0wNTBNMXA0Y25wVWNVa3dNbXhSUmpoMmEwaDVjRWxZYVRaMVdXUTBTekJsTkdZM2JYQjBXbkZqYUVjNGVYWkpMM0V2VHpaUk5DczRlV3BuVERsbFZrOTRVVmRxZFVOSmRYZE5PVFpNYTFOeWVHWlhWMDQyVGtOcGRtbHJZbmxXWjNaVmExQkJaM3BKWWtsVWFrNXFkVE0wWW1wVE5FRlBjblYzTUhGU1YwcHpkRzFoTDNoalprTjVXV2RxZEZkdlNsRkNlV1ZFY25wNVRraFFSWFpZUW5sc1dXWldRbE5NUVZOVGFHaE1kMGx0TWpkVE9XTlVaSEZNV2l0bE56VndXSGRYVGxGeFpYZzRNVzl0WjJwRmQyUklRelZGUzJvMVowZHBkVUZzVVdOclJXbG5helF4SzBSVFYyNVNVbkZoTlhWeFIybFZka3RSTmpCbVQzZERWRFlyZDFwclp6VndRVTA5SWl3aWJXRmpJam9pTUdRek5qbGxPREl5T1dNME5qYzBaalpqWWpWa1kyUXdObUZpTVRZM1pUTXhNVFpqTURKbU1tTTRNbUpsWldRM01tUTNOVFJqWWpobVpqSTNORFU1TUNJc0luUmhaeUk2SWlKOQ==',1794685330),('8y0l04eQsThB02g3Vgz3oL3Cz3Hoagb3jCi0juSf',NULL,'192.168.1.15','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','ZXlKcGRpSTZJamxJVjFwWUswaFRTM3BhU2xJNVEydGxWRmhvVkdjOVBTSXNJblpoYkhWbElqb2lUR0ZpYjFoS1JVeFVUR2RrU1d4VGNVRkZjSE5aYkVoVmJuaHBWRVpKVkRoRVVsWlJVa0ptYkZOdlNFTk1ZVWd3VVdGR1NGUXZaV0pCU1V0V01tOTZRVE5wVG1oT1ZuQnBTMlU1Y0V0d2FVZFhiMGxWSzBoNFZrcFVVbFphTldVNE9WSkhVSFpFWWxCNkwzcDRXRW94VlZOdU5YcDFSamhtVWpjME5IZDZlVlZUVkhWcWRFSkhSMUJrV0dRMGRtMDFlSFUxYTFCSWR5czFURzExZDBKM2EweDFjVGhMVHpCU2NsVjZTbE0yZGxWTmFHYzRTRXQ1YmxnNFVXaFlWa2swVUdGcVdGTmhXWFI2SzBsWFRucFBhRmhMZG1KclRYbDVPSGhrWWpsSmRuWktXVGw0WjJocGJreG5iMjl0YjBKeWJ6TlpaMDh3VjBWaFdYQjBUbHBTY0hoWGRVTlRTbVZ4Um1nclExa3JjM2gzUkdGc05GUTBVaTlhTmpOTGFXNHdlVkZ2VjNKT2RscHpZazA5SWl3aWJXRmpJam9pTURaa05XVTBNMkppT1dFMU5UTmtORFl5WW1SaE0yVTFOalppTWpreU5qVTVNbVF6WmpGaU16VmpNR0kzWldWaU56TTNPVGszTlRJeFltTmlNVFkzTkNJc0luUmhaeUk2SWlKOQ==',1794684778),('9irigExQFHtpXJnxRAKbVbyIinZLOrW9NWR69F61',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJbXBQU1RaT2FVTldWRXA2WTBKRWMzSlZSRVptV2tFOVBTSXNJblpoYkhWbElqb2laRzVXYW5oRVF6bFBRamh5YUZScVZGaENWekJsVldaU1dVUlFVa0YyY21SQ01tdHliSGN2V1ZSdWJuWXdNazFXWm1adFdEQnJaREFyVFRKMldISllhemhRUTBwRmNsQm5XVFppYWtscU9UQlBURTVxV1M5WGVDOTZUbW80UmpacFQwRnpURlZwTUN0NFoyRnRablFyVWpCelpGWXdOSEo0UkhGMWVuZFROWFpQUkRCaVZYSTJiSEpCYkRWMU9FbGthVWwyVm1GaVNucHJaazFwYlVrMmVTOTBLMVpET0ZkcVJtMUhSblJ0UjNOWVF6WjZXamxIUlc5TVVtZFBlRkppYlZOc2VqUlpjVmRsY1hwbFNGWktTRVIwV1hwbmQwdGlWVXBUZW5SUk5WYzNVekptU1ZaSlkwUktieXR4ZFRWdFZFRldlVFJDYlZCTlFsSnRURTF6VEVoWFluSmtaRWxCSzA5eFF5OHJkMDloV0VkdlJGZ3hkR2xTUWxWd2MwUTJUMEpRSzFsVGNtSktUSE05SWl3aWJXRmpJam9pTldNMFl6aGlNV1ZqTm1Oa05Ea3hOV1F5WlRNM00ySm1ORFJoWmpjd05HUmlNMll3WVdReFlUZzFOV1kwTjJObFpHWTROVFJrWkRGbVlXTm1ZbVkwTkNJc0luUmhaeUk2SWlKOQ==',1794685949),('BpTeG8rWg9c2hSN87xPK440gtCvgPkR5ueNwim9k',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJbUpYYmtkUldtTldZa0V4TjJoNU5USmFhMDF4T0VFOVBTSXNJblpoYkhWbElqb2lkMEkzU1ZjM1JreE5hbEo2UTNkaGJqVnlXREEwZWtkTWFGSmxNMmxwU0V0TWVYcDJNV3RtTDJSSVRteEhTaTlyV0dsbU4ySlNUbVpNVjA0dllWazJNM1pJYzI5SFFYWTVVME5OUjFkdWJFeDVRbkpKVUVoa1NrNTRka0p2YlhJMVNDOUlVVUprTDFSVFowbzRUWGt5WnpoTk9VVlJkRzVSVG5aRlMwWm5LMGN3UVZNelZYSTNkMWRhYVd0YWJFNUJMMEZFWldac1RXTmxVVWRVSzBjekwwVjFhVTlNWlZKRmVsTnlUQzlQZG5GRVJYRlZOM0lyVGpWNFExbHNhMEZ2UlZvd1oxTnZSMU4yTmpoTVptNHhZMHAzWkZkMFpXVnhNV3RCUkV4TlRVSlJkMlZSV1VVNFkxWTRNa1pxYVZGSFN6SkNXbEIzT1ZkbGRVRnNUR3RqVXpOMFRuWktjR2RDV25GUFdIWnphMlZOYkdSNVNuRlZXVXh0WWs1aWNIUnJVVFoyVTBOMlpUQkNhVEE5SWl3aWJXRmpJam9pTVdKaVlqTmxOak0yT0dObU5XTXhaVEV6WkRGaE16aGpNMlZsTkRaaU5qZ3pOelU0WkRReU1UZzROVFU0T0RNNU5XUXpabVkzWVRRM1pqZ3pNekExTnlJc0luUmhaeUk2SWlKOQ==',1794689171),('c2Mf1WeYbfXOsNdq6d5KLHuAi0IAH2chzheK6qRR',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJakJFUWpsNlYyRTNRVXh0YURSTlMzZGhjVk5DTjJjOVBTSXNJblpoYkhWbElqb2lVSFJ6TTBnclQxcG1WaTgzTlVkSVQwMHlZMlZUWkVoU1ZVaGlPVzB2YlVaa1JUZFpUMGhHVkZaRFJWRmpWMUZEZFVKTFUzWlhkMFZKVTA4eWRqUXJTMEZMYkhwcWFXOUxNVVpDY1ROYWJTdHRlQzlTWTAwelUxWkJiREZLTTFKSFZWSlBWalJ0YUVodldWY3lNVzlvZFhwU1RUQTRZM2xrZVRZclUzbG9iV2RuUVhaV015OUVlbFp1YkVWa1RYVkVhRXBQVFZsNlNVMU5PR1J6UnpReFJFeGxWa3hsWVhaVFUzbEVkR3RqTDNaTWFqSnZiblJaY214R01VdDFZM1ZRVDJodWVGWktlRE4zWjBOT1UzUXZZVzVuVVVsbWJqRkZhV2t6TkZGRVIxTnBRMFJHYXpaTGJYQndUa1EwTUhCVGIySlhhWFJwUmtVd2NtWkZkbG95ZW1WUVpWWkZUbGg0Y2xjNFNpdERlbVpJU3pSTFIyMWhSa1JVTkhReVVsZEdkbkZqWVhJcmVDOVpVRVU5SWl3aWJXRmpJam9pWmpFeVpEWTBNR1ZpTm1WbE16WTFOR1UzTVRrNE9EY3pPR1ptTmpsaE1USTROVGhoTkROa09UbG1ZamRtWTJZNVlqY3hNVEkzWWpabU5qUTFOR1UyTmlJc0luUmhaeUk2SWlKOQ==',1794684753),('CoLlc5K3vdnF097mUtqIa5sDqb88X8PcgHUJ1fEG',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJbEJrUldKMmFWaHljbUphSzFvNVNXWTJhbVp3U1ZFOVBTSXNJblpoYkhWbElqb2lZWFZHWjNWNU1UZzVTbTlPU0dSbmRHRkNZVmh4Ympjd1VFcHJkbTVLZEUxMmREZExOM3AxZEhONFZuRk1Wa001ZWtJeU1ISm1ZVkprWkhnMFNucG9SbGRGVDI1dFIwaEtjME5qVkVaWk1VeERVWGxPTVhGek1FUjBTMmhuTDFOTmMwSnZSRlZtTjFSUU5HWXpiVTVpT1VaNmJrSXpWM0ptUmt3NVNsWjRVM05FTW1Nek5XNVVTblJWUzNKbVNDOUNReXRJUVZKNU9ESldXakZIY0M5U1pWSlJXRlV4VlhCT09FUmxOSGRVVTJ0VVprVjJiMVp0YlRkNGMzSTVUVzVaYjJGS1ZGcFRlR0V4UzNaNFJHUmtVekIzVW1WMlFWRkNXVWxoYkRsd2JGQllSV0o1V2xrd2EzZGpiV2RwVDFkVFJEVjZWMGR6YzJsSGRWRjZiMGRoVjNKbmJHcENXRFY0V21wSlFubHBXRk5JT1c5cFozSTBXRzFpZUhOUlRXcHpZbUZPU1ZGSVl5OVhNemt4TlM4NWMyVlNOMHcyVFdWVk1XcFJiWE5VWkU0MGMyaDFWblpQZERsU2FYcHpSbGh1TXpKSk1uWndUakkyVm1oNFdVRjRObVVyV1V4UFEwaFBNa1UyZFROMGFWUjVPRFpXTm01emJFUlZXR3QwTmxwc09XRm9kVmhRVUdwM1ZtZG5SVWhxWVVSQlRsY3lkejA5SWl3aWJXRmpJam9pTVdRMU1UVXlZVE5qWlRGak1ERTNOekkzT1RWbE1URXlaakF6WkRJMVlUYzNNVEJoT0RKallqWTNaVFJsTWpKbE0yRTBNMk0xTVdRNVpEUmxNVFkxTWlJc0luUmhaeUk2SWlKOQ==',1794684541),('iS1BIoCuwHHQUrbOtnZco695UG5EA8Bt07xSHhG7',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJakJ5WnpWaFpuSklOMWxCYzJSbFNURm1aV2hqV0ZFOVBTSXNJblpoYkhWbElqb2lVeXRKVERJMWJYUXlSMkp1TWpWc2NUSldUbEJLT1dKeVdVVTNTRUZVUjBWV00yeHVXSFZ6VFhNd05tVllialpIYUVKeE1XaHpjWEJoYWpBdmEyVjBaMFFyVVdkVGRtVmlObk50YWtwSEwxVXZOME5aYjNWcGNGTlpOVk5rT0VoWWFucHFZWGxXWjFSVmJXZGlUV0pCV2tONllWUlpNR1oyVjJsTk4zVXZWbE40YURCTVkwOU5NMHRaV0hCSWIxaHpXakZ2VW14QmMwVnZiM0pYTkRNeWVqRjVaRUYwV21SQ2EzTjNMMmhrYms1b1kxVmtNME5XUW5sRFpHVkhiM2RoY2sxU2Iyb3pNWGxzYkVWdVZuVTFZMWhyUmxKNFRuVlZhazB6Y0dOa1MzZDRTbmhDWkVJMmJ5dE1jbWRLYmxsSWVtZFhUWGRySzAxcFVFdFBiemRpT1VZdmFXWXhWM3BJTDNaWFRrTnhkMUJKTlhKMlkyWndNRkZ3YmxOS1RWTktUbWRzTTBsamRVTjBWMk05SWl3aWJXRmpJam9pTVdZelptUTNOamt4WXpNeFlXWTNNVGM0T0RNellURTBaV1k1WW1Sa1ltVXlZMkZsTURneVpXRXlNV014WXpFNE16WXlaV0ZrTmpNNU5qQXdNVGswTmlJc0luUmhaeUk2SWlKOQ==',1794688792),('jODnJg3nWwovAHAKLkv4eO1RbYlNEaiLkVSkgqMK',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJbEJJWVcxaVJVZzFNa1JYYzBOemEyVm9WRkU0ZFhjOVBTSXNJblpoYkhWbElqb2liRU40Y0ZOVVMyZFZUbVZ0T0habmRXODFUVWc1V0ROV1NrdHZUMXBpWlhGTFRHOUdiMEp4WlRKNlRFeEhOWEZJYzFBMFNHa3dRVzVDTkM5VVFWcDRNekkzTmxob1kwZHBZbFIyU0RWV1JFeGFUSHB0TkhrMGRuUktMMkoyUkRNM2FFTmxRbHA0V0ZWVGVWTlBNVlp5VDNaRVF6bDRlVlJ4VjNscU5HSkRZMDlZTTJ0bFUzRnhNek5QWTJka1NrUXhNM2xyVFZKRVFUUTVhVTlRV0hoUU5EWTNNM1pLU0RkbFNXYzJZV2RRVjBZeVlYQlRURlpLWm1NMU0zaFRhR2x1ZFdaMFprWTRaVU00T1dKMldGUlFkRkUzWjJsemVYQnVPRWhGU1ZSRk5YVklhMmRSYkhoa01GQnVhbTFRVUZweVRHUlhOblpXVFRoTVIxTnZNRE5hYlVJM1p6SnhWSFI1WlRWa2NsRnJibVIwU1VKT2RrTmtjbWRFVDBjM09XMHZka05tYWs1d1lrWTBSVFE5SWl3aWJXRmpJam9pWWpWaFpUTTJabUV3WXpRMU0yWTNPRFkxTVdNMU1XUTBNelkyWkRZM05HVmpOR1kwT1RjME5HVXhZemMwWW1abU5qVTJaRGRtTTJGaU5qVTNORGxqTVNJc0luUmhaeUk2SWlKOQ==',1794685227),('JY01l8jJYRb398Lc3Sp8ToY7kN3HMUOKqJUsjE6w',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJa1ZCUVUxTFptZHRVVE4wU1hKWGEydHBXSHBqU1hjOVBTSXNJblpoYkhWbElqb2lZemh5T0dreVNGSmtVbmhCTVhacWJucFhWa1l5UVZwNmNVTm5NMVpSU205RGJWZENRMW81TkdoUVIycDJVU3RFU1RKQ1ZrVk5XSGx3U0VkTmFra3pRa05QZWxoV2MybzRUM1ZCT1hVNWJFeFlkVzFRVlRWU05WRkhkMVZCWW5KM2NFWm9PWGt6Ym01TFEwMUJkelJwZDB0dGNUazVNRWhDYlU1RVZsWndRaTlDT1VWYVV6aG5UVGRMVW5WQ01YZzNlblI0UW5kbVpXcDBUVU51V0habFdVVktlV2RaZFdkNFNIWktiV292Y1RCMVJtUnpkMDVQWW5Kc1dGSk1hVUp6UjBwbFdpOUtXVm80YURCMlZscEhZMHgzTlM5cFdIbFpXa0ZPYWs4M2FtdE1Wamw2T1RaM2VqQkdZMGt3UlZaNFZtbHhWSGxZVDFOWGJraE1iVzFCVVdNNFMzSjNiVmRoWlhObGRsQllNWFJtU1VoaWFYTmllWE5qYWtKbFJuVTNLMGN2VFhsdVJUTjZVbEU5SWl3aWJXRmpJam9pWlRObU1qYzVOR1kwWWpBeE9USXdObU5oWmpRd05XRTRNVFZsTVRZek5qQmxPVFUwT0Rrd1pqa3pabVprTnpObFpHTmpObUU0WkdJd09XRmpaRGt3TWlJc0luUmhaeUk2SWlKOQ==',1794684417),('lA4erHN5SAevB5hP2L0nuCu2uPJagCGaKz0RZqdJ',4,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJbk5JT1hscVVFbHpUemx3T0doSVVrdzVkMnRXTWtFOVBTSXNJblpoYkhWbElqb2lVelJNWkRoelRqRnhLemQyWkZJeE5UVklOSFJHZUc5NVYyWjBjME52VkRBNVJWWnZWbVIyTjIxUVkxRm5VM2R3V2tkSVJYbDVORXBtVTBkRFlWWXhiRWxXY21GWmNHTTFiR1ZvZFV0cFFucHVaVFpNUjAxa056aFBZMDQ0UlhOTE9XcFZUVFZLTkhsYWRscENTRzlGV2tOdVVtRllZbWhCWW14TE1FcFRVRXAyWmsxWlREZERka2RKU2sxRlZsVkVWMUk1UkVoQllUZGhVbUpJZUZVd2RGQnBjMk5qYTFWQmVYQkhUalJDUzB0eFYwbDNSbHB0U21wTWNuTkdUR2sxVFZSRlNuUkRiR3NyT1dob1VXOVNlazV3ZUN0VFprOW9VMXA0Tm14MGRXMXBkMlVyUjJkRFprNW9Semt6ZVVodE1sZG1heTltY1ZwM0wydzBRMmhCVDBWV1QxUnFZMHd2TlZKWlExRmxMMFp6T1RkSUsxTmlaa1ZXYlhwc1JqbHBVME5QVTBGQmNrNVpZMElyYldwbWIzVXZjbFJRV0hWTFEySnhVMVp1ZURaS05HTnliM1pSVDBnMlNXZFJjV0puVWxSVmFUaFhUVTFVTlVwSlZVRmpha3RPWVhoYVUydHVWWE4wYWtSRFlVTkhPVEU0TlZwb1R5dEtjMFlyWmtseUwxaDJhMVpEV1doNVRGcE9SMGQ2Y0RobmRYZDFiR1o2ZW5aT1RHVklNREpaTDBOaFMwWndhVE01TlZNdmJERklabXBoYVdwdU1rVnVNRWQ0TTBGcFZIVXhVVzFGZW14Tk1ISk1SbXBhYWxFeWVUbERiVFZIYjJWRVlWRlhRa1pSVTBkQlNUWldhbFpZVERKWVVXOVZkMFE1VlVsV2NrZGpVMXBDV21rMmJFRk5WbFJ5WkVkeU9VZDRZVUZFYWxCSFMyOWhWbWR1VWpsMmMwZ3ZjWEpHVjNObWVtOUZlbEpCYkRrNFBTSXNJbTFoWXlJNklqVTFPV1F6TWpnMU9ERTBPR0ZrWkRZeVpUY3hPRGcxTURWaE1EZzRNbUkzT1RReU1UVTJZVGhtWlRrME1qVXdNemhoWVdJM1lUazJNakUyTm1FMlkySWlMQ0owWVdjaU9pSWlmUT09',1794684612),('nPFw3lv3Q3gVHg4P7mi5R55QJqCE6aep8RadLH5h',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJblI0TTBWdVJXVnVXalZEVFdKU1NGTTVabkp6YVhjOVBTSXNJblpoYkhWbElqb2liekZyVWpaME0zUm1TbVZaTURONVRWRk1XRlpOTVdodFRGQlRMeTlpY0hSM04xUmhVVXBpVEVoeU9EWkJSM2hNT0RNeFMxUkRaRWN2YlZKVGNrNDRlRWRsVTFKNldUVkRSVXR2ZURkM1RXSmFRV1JXVVZwTFNraElTVmhxVGtrclRGcHhUMlZqY1hJeFR6aENiV1ZMY2xSelZWTkRMelJOUlhGUFJYWnZWemh0VTFwaFVpOVFVelZtYldGQmEwVklOREJHY1VGaFZXdFllVEJZTkRSTWIzTjNSRTlwYzIxUk9VdEtjRXRIV0ZCa2VHMWFSRGhaYmxkM1YxUTFSMWRVYUZab1ZqSmxOMFZHYmt0eFFsWkdMekJqU0hWcVNUVlhibFYyVFdwWVdFNUZOVVZqVURoa01sbHJWWEpTU1c4eVRGUnlWMmh0YW5wQ2JGQmtXSEp4TVdWaU9FbGFOMVJ6YlhCcGQwaFJURXgxY1V3NVJEVXhXRlU1VkRKMlUxVlBPVmcyTjJ0MGN6RjVZazA5SWl3aWJXRmpJam9pTmprME4yUXpaRGt5TmpZeU1UWTJPVEF3Tm1FeU56azBNRFJpTWpNd05EbGtNelZqWkRrek16YzNNMkl4WmpZMFpEZGhabUU0TWpRNE56bG1OV1E0TUNJc0luUmhaeUk2SWlKOQ==',1794688191),('OdzmWi10B23TtQzqy6R3inC3Fjf0QEUeZmnaLn8f',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJbEpTT0VreFR6aExTaTlHYjFkb04yaE1kelZNVUZFOVBTSXNJblpoYkhWbElqb2lPRTlvUkVSRk16Z3ZXblJwWjNjeU1VdEdTMk5aV1ZwMU1tWktkMWhIYTNOd0szTXZTek5yZWpaU2FrRnZOWEpNZDFkMVpVeFdWR3h1UzB3NU5HZFhNa3g2SzNocEswSlhkV3RMZUZwWmJrTlZlRXhoUkZRNU1VRlRNRWMzV2pCblNHeGFVMDE1Ym5sUWN6azRjVFp5WW5oMGFrTnhkV3MzUm1Wa1JEZGhjbkV3TUhjek5sUlVRMUZGUkVsVGJGTTJObU4wU0cwMGQzSkJUV2RoV0U5V1YxWlZNM00wV25ScmFVTXhRazVpVlN0dVZVaFlSRFpCVkVJd01FcFpkREl3ZDJFNFdUbHFOV1JDZDJ3MmMySnllWGRYVVRZMVRGWnJUbGR6TTJWb2EzWnZTalJpT1VSa1IyRnBSR1kyVDJGUVNrRnJUblJ4V0RGdE1URnBPV1V3YkhJdldWVXpjeloxVnpsQ1oxUkpZa1JJZVZOUFQwUkZUV2RPWjNwQlExZDNZbXhPWTNOclRXcEhSbEU5SWl3aWJXRmpJam9pTldOak1HSXdZVGhqTW1Jd09UUmxOV0ptWkRVM016SmhOREk0TW1ZNE0yTmxZalZqTVRSa056UmtOMlEyTlRneVpqWm1NakZqWlRJM1pqUTNaVFppTmlJc0luUmhaeUk2SWlKOQ==',1794688578),('p6mRH5CnUmZHaWG6EjCM1Sr9L9IB14XooNSc7Xf1',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoidzcxcVRWc09lNTIzc2tjRVRZYlRPR3dyaWtnaFFqaTZEeWpTQ3BuOCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9ib2FyZCI7czo1OiJyb3V0ZSI7czo1OiJib2FyZCI7fX0=',1794682572),('Q0F7QxsTFKJA5L9zk4Feh6S1LpUxd3ZrjeTWWN5O',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJazVSUnpSWGFWRm9TR2gxU3l0blVFMTBZVzVxWjBFOVBTSXNJblpoYkhWbElqb2llV2RzYW5nNFVXbE9PR012U205bVdHaExUVGhSUkVoRWNFWm1lRFk0YTNwb1JuSnRSa2REVlVOUU0wbHhSa1VyYVdSR1ZWazRRM2x5YW01aWVITnBjRzF4TjFNM2VFeDZWbWh0TjBWek9WZ3ZOSEZHWVhGWFoySlJUVkJpYXpKbWRGVndOM1ZzWWt4SGVsZFpkWEJ0UVU1NVJHcElhR0Y1UmxvMlVYSjBXVlYyWmpWTFpVNXJkRFoxV25OUWFHSk1OazV0Wm5wM2RqTjVSbmxwTjI0eVl6Qk1ZMHg1V2lzNVZsWjNjbEZoWTBkak1VdHFWMlpMTWxrdlJGWlVjbUZGUzBSUFdGVk9VWEZuUW1salZYUTVZbTgwUVRWeU5XSkphR3BWYmpCdk5qTXplR2gyWkZaa2FGZDNkMHBRU1V4Wk1ubGFhMGN3TmtzdmJrb3laRXQ1VjNKMlJraDNRelJNYjJkcVltYzVPV3hRZGpSTllucFZkRnBXSzFSVU5UZGtWekJRY21GaFdIazVNRzg5SWl3aWJXRmpJam9pTXpneU1qSXdNVEEyWkRReFpqazBOek5tTkdObU9XTTVaVFE1WVRrd056RTRPVGczWXpRNFpUWTNaVEppTVRsbFpqazNOMlZqTldRNU0ySmxOMkUxTmlJc0luUmhaeUk2SWlKOQ==',1794687331),('QJ4YsdGU8LBgtafgDXrePogYX3kijieZKxK7Fvvr',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','ZXlKcGRpSTZJak42UkVsbmNtSXJRVGRXVWxkM1RGZFdOREYyVUVFOVBTSXNJblpoYkhWbElqb2lhMjkyT1VveFZpdElaRkJxU0hWa01WRXhkVTVTV0Rabk0yOW9Tbmx4WmtGQlozZEpRbWc1VTA5c2FHMURiR1pCTmxaRldVUkJUa1JpV1hWeFpIQkVia2x4U1M5cllTOXlhbWh5WTFoR2NWUm9ka1Z5V0d4U1FuSTJaMGhVZFZsdEwySjFNSE0yZFVOblIyOVFaak5hZW5aT09YcGlTV0pZWW05Sk1XSXdVQzlxY2tGUGFFSkZWMGhoVlZKb04wa3ZRakl4Y1RCeWRrdHlVRGQyTDNSNGRGazNhVTVOV1U5a1JqWkVhakl5ZHpaTFZYZHZWM2NyVkdsdk1XVjRWek5zTW5KRE5GTnFReTlPVkZaWWVWcHNlRzlsVFZjMGJFVmFMMjluYVZBd2NXeE9hRTVYZW1kb2JtdzRSVFpJU0dSSmJWSjBRbmhZVlZkcE5VY3lMMDVQY0VWdlluTlFieXMzZWpoQ0swVklkR2xDWWt4aWRqYzRlSEV3WVc1cU1FbFZjVEZqZWtaclRWcFNRMjlXUjFsemRWbEVkREZDTlRaM2FHVXlLMmxLTWtvMFIzSnlkakp2UVZkdkszaGFaRFZWZEZaeE56SmhVMkUwSzAwM2NIUkNRblF6TVhNd1NVcDRaSEppUkhkeVpHMDBPR0ZrYTFwNFQzZ3lTR2xCU0ZoRElpd2liV0ZqSWpvaVpUQmtaRGxqTnpBME5qbGhPVGRrTURObVpUazJaV1ZpTTJFM09EVXdPV1JpWXpOak1qWTNPREkyTVRNNE16ZGpPVEl5TkdRMFpqVTJObVZrT0dJeFppSXNJblJoWnlJNklpSjk=',1794684380),('S82WdNyunecTZrWMz57FmenqLmtmL8CWXF3XFeUB',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJbVoyZG10cFFuaHFhVFJEY21OblRVZzNTaTlHV0djOVBTSXNJblpoYkhWbElqb2lLMGRuTlRKaGQwNVNRelJyWTNZek9USTFlblIzWnpsc2QwOXFiQ3R5WjB0WWNFRlNTMVJxV1RsS09YaHhUMEpzVVZkQ1EzZDZNa3BOY2twQk9VVXhjMWx5U25CRlNHOUZTR3hRZWprd1dIaExaV1JUYVVkTE9FazVXVmNyZEdkcWVrbzRXbkZwVjA5c00wVkdkekJTUmxaSlEyUTFjRUUyWjJJd2NYcFJTVGxRUzFobmFIQnNhREZCVkZBd1lVaFZOMGxCVkVSSlRVbG9LM0ExYkV4TlZtdEdjVGRoVEhkNmJsaGhhRlYwV25seWJXSlZOR05xYjFwUk5GazFjMmRxUW5RdmVHRndUMVJxYkVGeFQwaGpOa2xTUzJWeGFUQlFSbEpGYUhSRksycFFjVzVTSzJkdFR5dENXV3hxUlN0aFpqRlRWMGxQV1dVMFEzTk9jWFpoTm14aVVGUm5TVGdyV1hkbE5EVXhNbWh5YW1kelJucEtNSHBCV0hOa1IzZHJPV3RVYUdvdmJscEVkVEE5SWl3aWJXRmpJam9pWTJFeU5XRXdaREU0TkdVNFlqZGtNalJpT1RCaU1ERTJOREU1T1dKbFpqWTRPV1E0WkdNMk1USXhPVGcxTXpWbU5qUTBOVE13T1RWaE9EUmxPV0ZrWmlJc0luUmhaeUk2SWlKOQ==',1794684417),('UhrXDyADWBlJzxkQCpA17ecy3oWp3wJLwPmlOQnw',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJbkpsUTNCb1R5OHdUSFUwYkVKbFJETjFlbVJhVVdjOVBTSXNJblpoYkhWbElqb2liVXBSWXpaaVRUaHlUMnBuVVV0YVRrRk1aVWM1VjNCQ04wWjZiMjQxT1dacVUwdFpNMEpKTWtWaE5URXpObkF6SzBGcFEwZFJPVlprV2pKUGVtMXZObTlDYzBkU1FUbERTalZJVFhkVU1Xc3lOMFZISzFKRE5rMUZUMlJ4UTNSbk9WbzBVMUYxU0hwQ05tRm9TVlFyWkVkUmJXdG5WbXR5VVdOblMyWnlRbXR5ZUd0WVpEbHhaM1UzTDJoSlVrbDZXblpKYWtWNWF6QklUMjVDVEZreU9GSlJPVk5RTjFGSU5tRXhlVGhOY0ZrMGR6SlBiRk41VUZJME4zZzJiREI1TUM5VlZtMHdRalJGTUhWb2MwcDZiVXBwWlhjNFF6SllNRzlqYmtaUE1GQXZaVGtyVVZKWVF6QmpNekZzTTFSTGRqWlVOMk00V1c4M2JHVXpiRkV2VmpSd1QwUlhUa3RPZGpOU09YQnVRM1p6UVVaVVVpdEVRMHRqWlZoclpYSlpkRWd6Y0M5MVNXZzVha2R5TkdwaU1EZG5OR1o1WkVWcGNXdHNjbk4wYWl0emRuWlZWMWR0VW1sTFNEQjFkVUZHY2s1UGNsTTRiRWg2U21GSlZsUldibkYwYlVFemFHTkRUVWxOYjNWV1ZGcFVWbFJ6ZFZkeVRYSjRkMDFvY1hKVVVVb3hLM2N4U1hsbFZGaENVMnhGUVZacWJGRlRabnBMTUdGeGJXUTVVSGd6VTJRMVdXTnJOMEZhV0c4d2FTdHNVREJtTkdOdWNFc3hkR0l5TTNOdE1GZEtVR0k0Y21aM1UzUlRia1ZJZFVaTVlrOUxXbXA0V1RjNVJFNVNXbWxoUzBwbGRUSnhNalIwUVdkQ2FXVmhjMjQ1Y1ZKR1pEVXJlbHAzVVRadmF6Qk9hV1UxYlN0Uk16SklNRWxEVTB4aE5GVkNUVEZGVG1kU2ExWTFRakJMU0VrMlRYazJTaTlFZW5wNVlYVkJhbkZaWTNOVGRuSXpjMWt4UW5GWlVXNW1WRWRDWlRWek4wZ3dkSGhSTDJaaU1YWjRUV2RGV1dGUGVHZFBNRGRyY0ZCd1dFRm5aamRvY2tnNVFYaExlRlZ6ZVVaTFlXNXJVR1ZrUlV0WGJHUnBXRkJzYmtOMlNFTmpkamhoT1Vaa1lXWTVNVXB0ZERGc09FMVhSRUZUTmpsSWRtbFJZak0xU0dKbGVVWjFkMkl4VlVwa2RuQXdWREJOUWxoQmVIVkhaRlY2UlROS1UzQkNlVXR4VjBobGNXOTJWR0ZKZG1KYWExRjFNVWN4V2pCNmJGUkpOV2dyY0hNMUwxTkxNVTUwU25KeVEycHlkRll6YVdwTmVEVlpWVFE1V0dwcmRHSlhXa0ZwTTFSWlIzTldVRmxaUldzelNXNDJlbU50Y0dWM2FsVktaelpKYVc1Q2QyOW5VMDVKU0RoTWJrcEdWVXhSZWk5SlNqRk5lalIyZUVWQlRXUjBSbkptZFhoTmIxaFFORGt4TVdkS01EVmhTQ3RJTkhaeFdHdHRSWEF2WjBONWExRnZUVGhUZVZwa2RYbExMMXByZVZCSVlsbHJRU3RYYVdZNVZHUmFURWRpZWtkTVIxVkVXVkJLVldReU1qSmFVMkl2TnpSM01FSXpUMlJpUW1zMFUyZHRXVzl3VTA1bU15dENaazF6TUVsVVUyVXhNeTk2T0RWSUlpd2liV0ZqSWpvaU1EaG1aakk0WWpFME4yWXpZVFJqTXpJeU9HSXhNR0pqWkRjM1lqY3lZemc1Tm1NM09HVm1ZVGN6TlRBd1pHWm1OV014TUdJNE1UQmhNamMwWVRNNE1TSXNJblJoWnlJNklpSjk=',1794684557),('WaAHsxUc5lPR32hBgSMj2zNJnN1B17UFjKyBKwtr',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJa3BVUWs5eVVrVmhjbVpMT1ZsNlVFMUZaR3R4WldjOVBTSXNJblpoYkhWbElqb2lObEJtVUVWVWFrdGhURm9yYm0xcFQwTjJZbGt6TmlzM2JYSmhSMjk2ZFRRM1YwRk5kV05JTTBOdmJHTnRXVVZPY1RkUWRqTTFjMHREZGs5elVsUkxZbGRhTDNSaVNXSjZRa3c0V21GbVQyZDFORmRYVWpSa2MyazVNelZrU0Zadlp6QlFPRkl4WVRCdVpXazFSbTF0TmxweUwyaE9hVTVtVDFBNGVHWTFhM1JXUW5WdE56Rm9RUzkwYWpoS2IzcE9NRUZUTlZsVmRsRkZZalp1UkVKa05rMTVXRk5xZUZCNVZWRndkRUpGY3pWc1l6WlJSV1pDU25KM2VsZHBZVWt4VVVsVkwyeHZUbmxEU0ZOQmEwNDBlRlJCYm5OSGJqRk1SVmt6UkRBMmIwRXZNRGR1ZDJKVVJVaHVXbE5SZGs4d1dEZDRNelEyWkdKNWFuRmxlRzFpTkhsaFZEbE1UVnBUZUdSSVYyZEdhMlZHVG1jeVoxRnZXSGhJTHpWblJ6UnZPR0k0T1VoMmJqZEtXbk05SWl3aWJXRmpJam9pT0Rsa09UQTBNalF5WW1JeE9XSTBaakZrTURabFpXRmhOamRoTWpNME5HWTJaakJsTnpVMlpEUm1Nall3WW1Oa01qWmhPVEUwTmpoa1ltSmlPREJrTmlJc0luUmhaeUk2SWlKOQ==',1794688233),('WMOeNVkZ9spKGnZ7LKdCmJzf4gKPHd1uXIRD6lgY',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJa2gwV0dobldEVkVURGRQU0RBMVMxZHFhM2hxZVZFOVBTSXNJblpoYkhWbElqb2lia2xvZFhkVFptRTVTREp2Y25KM09HTmlXQ3RuY0dGalRXRlliMk4xWTFndmFWbFBiR1pSUVhVNVJqVTVNWEZFYUdsNk9IUnRjV2RJY0Vkb1dXTm9NVVpTUWxBMGVuZFNXbmhyVERZMWMxaE5SeTkxVTFCd1JXVkVPRkZVVkdGWlNXUXpabXhKUlVWTE1uTXZVMnRMSzBaMFJFRm9TMWxsYVhGTGRXOXZXWHBRWkRkNmJsQmtNVVZQU1VOM1pIVnFlV3BKYTNsR1drWkpTa0UwU2psRU9Fd3hSbGh2TmtVM1pERndVVWxTYW5KdWVscGtTV0pCYkdKWWFVdzNiVGtyYW5NMEszY3ZWM1puY2paVE5XODNLMjVTY25GMVZHbEdUMWh1UVhsdFRXcFpjMjlDZFZKTVFTOUNhbm80Tnk5M1dYQkRTbE56TkRKbWRsQnpNbVJOY2xCNk9YbHNiVUpPTkd0aVduSllPRlJYUWtoSFJGZ3lhRzVQU0hreVZXVlBjekp2VEhORVIzUnpjMGs5SWl3aWJXRmpJam9pWlRJek5XTTBPVGd6TlRObU1HTmpaR1F5TURZeFpEaGlNVEl6WlRKbE5HSXhPVEU1Tm1KalpHSTRNV1kyT1RCbVpEZzNOR1EwWmpSaFlURXdPR016WkNJc0luUmhaeUk2SWlKOQ==',1794684752),('zFPMfKCmy7TbYi1nfEAbABRrKdn9FRx2ccBkYCtO',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJakpWUVRob2VucHFWbVJPV21sVlFWQjNjMkYzUW5jOVBTSXNJblpoYkhWbElqb2lXWFZOVlhObWNtRlpjbTR6UlRWUWNrcGtRVVJhTHpKMVN6VlFkbEpUYkdoTlIyZHNTR1EwYkVoSlEwRTJla3BhVVZBMFNraHJUMVo0ZG1SSE9ITmxVMnBxUzFWTmVXNVJjRlJITkVWQmVWSTFhbkoyY2tsdFVXZ3JiSE5OT1ZCV1pYTllSbFJ1WjFodmRUWlRaaTl4Y0ZFeUswOVZPUzlTZVVaNWRrRklObTFsWTJOTWFXUmpUREpPVEdKMk5UUmlNMmxHUTFjelZWVkdObkl5U1ZJNFVXWXZkMjEySzFRd1puQTVabE5qTm5KRGRIaGhNbkVyVWxrMFdXc3dSVVJyT0hKaFlsaHRhRTVzY0dweGJXWmFWVkpSTm1aSk5tZzBjRUp0VG5SbVdtZDFlbU5MVDFFeFdrVmlNRE5QY3psdkx6TnBMM2hRZW5sT1EyaERObnBTWkhsMmVUTkRjRVZ2Y1RWRk4yUnVhRlJSUTBWWGVsaEZWRkpYTmtkak1rSmliMkZSY1ZWNlNrNDNORTA5SWl3aWJXRmpJam9pWlROaE9EUmtOak5oTjJGa04yUmlaalkzWkdGaE5qTmtaVFF5WlRsbU5Ea3hOelptT1Rsa01tWXdZalkyTW1Vek4yVTJaV00wTW1VeFlURmhaREZrTnlJc0luUmhaeUk2SWlKOQ==',1794684870),('zV37CoIzIlGY90Zj1aq5rHO9w0xMPGzI6ERq1T0a',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','YTo2OntzOjY6Il90b2tlbiI7czo0MDoiRUtOTVlPSW1wRzJjb2RENjFzclp2NjFMOEpqM1hkcFg3Vnh3YVBzayI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDA6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9wb3MvYmV0cy8zL3JlY2VpcHQiO3M6NToicm91dGUiO3M6MTY6InBvcy5iZXRzLnJlY2VpcHQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo0O3M6MTc6InBhc3N3b3JkX2hhc2hfd2ViIjtzOjY0OiIxYTZlZjIxMDlmNGNlZTQ5ZjNkYmJlYjcwYTQ0NjE4YTRlODNiNjg3OTUyYzFkOTA4ZDA2YTQ1NmUwNmNlZTM4IjtzOjE0OiJ3b3Jrc3RhdGlvbl9pZCI7aToxO30=',1794683681);
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
INSERT INTO `shift_sessions` VALUES (1,1,1,'2026-11-14 10:22:46','2026-11-14 10:25:44','Attendance — clocked in at sign-in','Attendance — clocked out at sign-out',0,0,'closed',NULL,'2026-11-14 10:22:46','2026-11-14 10:25:44'),(2,1,1,'2026-11-14 10:58:54','2026-11-14 10:59:05','Attendance — clocked in at sign-in','Attendance — clocked out at sign-out',0,0,'closed',NULL,'2026-11-14 10:58:54','2026-11-14 10:59:05'),(3,1,1,'2026-11-14 10:59:40',NULL,'Attendance — clocked in at sign-in',NULL,0,0,'open',NULL,'2026-11-14 10:59:40','2026-11-14 10:59:40');
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
INSERT INTO `users` VALUES (1,'System Supervisor','supervisor',NULL,'$2y$12$Kr8s/wS95aPMOP442ccyHOs9FdV6augkpBbHAaAFnyfCzMFGjIk9.','supervisor','active','2026-11-14 11:25:47',NULL,NULL,'2026-11-11 00:02:41','2026-11-14 12:35:07'),(2,'Marco Reyes','gm.north',NULL,'$2y$12$yPHmRrgPZL3tXSsSASaoNOR4VUN6LYCljYxaetztGZFpQJNvOcJZS','game_master','active','2026-11-14 11:24:38',1,NULL,'2026-11-11 00:02:41','2026-11-14 12:35:08'),(3,'Liza Cruz','gm.south',NULL,'$2y$12$DhyMvzo9.kYDDRgGJpInnehDajVYa.sOLQWnsKYLFXBXMF.yNLwpe','game_master','active',NULL,1,NULL,'2026-11-11 00:02:41','2026-11-14 12:35:08'),(4,'Ana Dela Cruz','pos01',NULL,'$2y$12$4Hgrs4lokHYdwNrRcT5wt.YWtz9DZQvfWUdPDvBF.v5lHqNfUyRNi','teller','active','2026-11-14 11:30:12',1,NULL,'2026-11-11 00:02:42','2026-11-14 12:35:09'),(5,'Ben Santos','pos02',NULL,'$2y$12$lsrCd9hOozQ5P6/JxiMKvev0ij5GUMJ2W1AqweXaWOzVLPWI1El8y','teller','active','2026-11-13 03:02:14',1,NULL,'2026-11-11 00:02:42','2026-11-14 12:35:09'),(6,'Cora Lim','pos03',NULL,'$2y$12$dSrff2yi.pxfJXVyfJqwq.PzSUXp16RG/l/VzuGQVx8WsMsPkXSEq','teller','active',NULL,1,NULL,'2026-11-11 00:02:42','2026-11-14 12:35:10');
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
INSERT INTO `virtual_wallets` VALUES (1,1,0,0,'2026-11-11 00:02:41','2026-11-14 10:17:39'),(2,4,800,11,'2026-11-11 00:02:42','2026-11-14 11:11:16'),(3,5,0,9,'2026-11-11 00:02:42','2026-11-14 10:17:39'),(4,6,0,2,'2026-11-11 00:02:42','2026-11-14 10:17:39');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallet_transactions`
--

LOCK TABLES `wallet_transactions` WRITE;
/*!40000 ALTER TABLE `wallet_transactions` DISABLE KEYS */;
INSERT INTO `wallet_transactions` VALUES (1,2,4,NULL,NULL,'seed','credit',1000,1000,'TXN-261114-XLGBEQVR',NULL,1,'Low-till top-up from dashboard','2026-11-14 10:59:23'),(2,2,4,1,2,'bet_hold','debit',100,900,'TXN-261114-AZ7BTUGR',NULL,4,'Bet hold — Fight #1 — Meron vs Wala','2026-11-14 10:59:46'),(3,2,4,1,3,'bet_hold','debit',100,800,'TXN-261114-NALVYDZW',NULL,4,'Bet hold — Fight #1 — Meron vs Wala','2026-11-14 11:11:16');
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
INSERT INTO `workstations` VALUES (1,'POS-001','Counter 1 — North','e9ad4e8b-6f7c-4424-96f2-63e06cfea5ac',1,'2026-11-14 11:30:11','2026-11-14 11:30:12','2026-11-11 00:02:41','2026-11-14 11:30:12'),(2,'POS-002','Counter 2 — North','pos-north-2',2,'2026-11-11 00:02:42','2026-11-13 03:02:14','2026-11-11 00:02:42','2026-11-13 03:02:14'),(3,'POS-003','Counter 1 — South','pos-south-1',3,'2026-11-11 00:02:42','2026-11-11 00:02:42','2026-11-11 00:02:42','2026-11-11 00:02:42');
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

-- Dump completed on 2026-11-15  7:45:00
