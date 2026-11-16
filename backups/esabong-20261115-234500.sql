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
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'supervisor','floor.reset',NULL,NULL,NULL,'{\"cleared\":{\"events\":2,\"fights\":8,\"bets\":2,\"shifts\":2,\"ledger\":8,\"snapshots\":0},\"till\":0,\"accounts_kept\":6,\"workstations_kept\":3}','127.0.0.1',NULL,'2026-11-14 10:17:39'),(2,4,'teller','shift.opened','ShiftSession',1,NULL,'{\"workstation_id\":1,\"attendance\":true}','127.0.0.1',1,'2026-11-14 10:22:46'),(3,4,'teller','shift.closed','ShiftSession',1,NULL,'{\"bets\":0,\"stake_total\":0,\"attendance\":true}','127.0.0.1',1,'2026-11-14 10:25:44'),(4,2,'game_master','event.created','Event',1,NULL,'{\"name\":\"HACKFIGHT \\/ 2 COCK ULUTAN\",\"code\":\"REG-261114-1TUO\",\"type\":\"regular\",\"entries\":null,\"stop_ratio\":1.4}','127.0.0.1',NULL,'2026-11-14 10:28:22'),(5,2,'game_master','fight.created','Fight',1,NULL,'{\"fight_no\":\"1\",\"event_id\":1}','127.0.0.1',NULL,'2026-11-14 10:28:30'),(6,2,'game_master','fight.betting_opened','Fight',1,NULL,'{\"status\":\"betting_open\"}','127.0.0.1',NULL,'2026-11-14 10:34:09'),(7,2,'game_master','fight.side_locked','Fight',1,NULL,'{\"side\":\"red\",\"locked\":true}','127.0.0.1',NULL,'2026-11-14 10:34:18'),(8,2,'game_master','fight.side_unlocked','Fight',1,NULL,'{\"side\":\"red\",\"locked\":false}','127.0.0.1',NULL,'2026-11-14 10:35:28'),(9,2,'game_master','fight.side_locked','Fight',1,NULL,'{\"side\":\"red\",\"locked\":true}','127.0.0.1',NULL,'2026-11-14 10:45:14'),(10,2,'game_master','fight.side_unlocked','Fight',1,NULL,'{\"side\":\"red\",\"locked\":false}','127.0.0.1',NULL,'2026-11-14 10:45:25'),(11,2,'game_master','fight.side_locked','Fight',1,NULL,'{\"side\":\"red\",\"locked\":true}','127.0.0.1',NULL,'2026-11-14 10:45:34'),(12,1,'supervisor','event.stop_ratio_changed','Event',1,'{\"stop_ratio\":1.4}','{\"stop_ratio\":2}',NULL,NULL,'2026-11-14 10:45:36'),(13,2,'game_master','fight.side_unlocked','Fight',1,NULL,'{\"side\":\"red\",\"locked\":false}','127.0.0.1',NULL,'2026-11-14 10:57:24'),(14,2,'game_master','fight.side_locked','Fight',1,NULL,'{\"side\":\"red\",\"locked\":true}','127.0.0.1',NULL,'2026-11-14 10:57:28'),(15,2,'game_master','fight.side_unlocked','Fight',1,NULL,'{\"side\":\"red\",\"locked\":false}','127.0.0.1',NULL,'2026-11-14 10:57:31'),(16,4,'teller','shift.opened','ShiftSession',2,NULL,'{\"workstation_id\":1,\"attendance\":true}','127.0.0.1',1,'2026-11-14 10:58:54'),(17,4,'teller','shift.closed','ShiftSession',2,NULL,'{\"bets\":0,\"stake_total\":0,\"attendance\":true}','127.0.0.1',1,'2026-11-14 10:59:05'),(18,1,'supervisor','wallet.seeded','User',4,NULL,'{\"amount\":1000,\"balance_after\":1000}','127.0.0.1',NULL,'2026-11-14 10:59:23'),(19,4,'teller','shift.opened','ShiftSession',3,NULL,'{\"workstation_id\":1,\"attendance\":true}','127.0.0.1',1,'2026-11-14 10:59:40'),(20,2,'game_master','workstation.bound','Workstation',1,'{\"device_uuid\":\"e9ad4e8b-6f7c-4424-96f2-63e06cfea5ac\",\"approved_at\":\"2026-11-14 19:23:11\"}','{\"device_uuid\":\"4346a97c-3bc4-4006-814b-2fc0274ee4a3\",\"assigned_teller_id\":1}','127.0.0.1',NULL,'2026-11-14 11:24:53'),(21,7,'teller','shift.opened','ShiftSession',4,NULL,'{\"workstation_id\":4,\"attendance\":true}','127.0.0.1',4,'2026-11-15 09:24:43'),(22,9,'game_master','workstation.bound','Workstation',5,'{\"device_uuid\":null,\"approved_at\":null}','{\"device_uuid\":\"b177f314-39fd-45ce-a9ca-2bd82fe1b55e\",\"assigned_teller_id\":5}','127.0.0.1',NULL,'2026-11-15 09:44:49'),(23,10,'teller','shift.opened','ShiftSession',5,NULL,'{\"workstation_id\":5,\"attendance\":true}','127.0.0.1',5,'2026-11-15 09:45:08'),(24,10,'teller','shift.closed','ShiftSession',5,NULL,'{\"bets\":0,\"stake_total\":0,\"attendance\":true}','127.0.0.1',5,'2026-11-15 09:45:36'),(25,10,'teller','shift.opened','ShiftSession',6,NULL,'{\"workstation_id\":5,\"attendance\":true}','127.0.0.1',5,'2026-11-15 09:51:03'),(26,10,'teller','shift.closed','ShiftSession',6,NULL,'{\"bets\":0,\"stake_total\":0,\"attendance\":true}','127.0.0.1',5,'2026-11-15 09:53:59'),(27,9,'game_master','event.created','Event',2,NULL,'{\"name\":\"Demo Fight Night\",\"code\":\"REG-261115-9VMR\",\"type\":\"regular\",\"entries\":null,\"stop_ratio\":1.4}','127.0.0.1',NULL,'2026-11-15 09:54:18'),(28,9,'game_master','fight.created','Fight',2,NULL,'{\"fight_no\":\"1\",\"event_id\":2}','127.0.0.1',NULL,'2026-11-15 09:54:38'),(29,9,'game_master','fight.betting_opened','Fight',2,NULL,'{\"status\":\"betting_open\"}','127.0.0.1',NULL,'2026-11-15 09:54:57'),(30,10,'teller','shift.opened','ShiftSession',7,NULL,'{\"workstation_id\":5,\"attendance\":true}','127.0.0.1',5,'2026-11-15 09:55:18'),(31,9,'game_master','fight.betting_opened','Fight',3,NULL,'{\"status\":\"betting_open\"}',NULL,NULL,'2026-11-15 10:27:38'),(32,9,'game_master','fight.voided','Fight',3,NULL,'{\"reason\":\"Rehearsal cleanup of an earlier run\",\"refunded\":500}',NULL,NULL,'2026-11-15 10:28:22'),(33,9,'game_master','fight.betting_opened','Fight',4,NULL,'{\"status\":\"betting_open\"}',NULL,NULL,'2026-11-15 10:28:22'),(34,10,'teller','bet.voided','Bet',5,NULL,'{\"reason\":\"Rehearsal: wrong side entered\",\"amount\":500}',NULL,NULL,'2026-11-15 10:28:22'),(35,9,'game_master','fight.voided','Fight',4,NULL,'{\"reason\":\"Rehearsal cleanup of an earlier run\",\"refunded\":800}',NULL,NULL,'2026-11-15 10:28:46'),(36,9,'game_master','fight.betting_opened','Fight',5,NULL,'{\"status\":\"betting_open\"}',NULL,NULL,'2026-11-15 10:28:46'),(37,10,'teller','bet.voided','Bet',7,NULL,'{\"reason\":\"Rehearsal: wrong side entered\",\"amount\":500}',NULL,NULL,'2026-11-15 10:28:46'),(38,9,'game_master','fight.betting_locked','Fight',5,NULL,'{\"status\":\"betting_locked\"}',NULL,NULL,'2026-11-15 10:28:46'),(39,9,'game_master','fight.result_declared','Fight',5,NULL,'{\"result\":\"wala\",\"winner\":\"blue\",\"payout_total\":1120,\"settled_bets\":1}',NULL,NULL,'2026-11-15 10:28:46'),(40,10,'teller','bet.paid_out','Bet',8,NULL,'{\"bet_id\":8,\"payout\":1120,\"teller_id\":10}',NULL,NULL,'2026-11-15 10:28:46'),(41,9,'game_master','fight.betting_opened','Fight',6,NULL,'{\"status\":\"betting_open\"}',NULL,NULL,'2026-11-15 10:29:27'),(42,10,'teller','bet.voided','Bet',9,NULL,'{\"reason\":\"Rehearsal: wrong side entered\",\"amount\":500}',NULL,NULL,'2026-11-15 10:29:27'),(43,9,'game_master','fight.betting_locked','Fight',6,NULL,'{\"status\":\"betting_locked\"}',NULL,NULL,'2026-11-15 10:29:27'),(44,9,'game_master','fight.result_declared','Fight',6,NULL,'{\"result\":\"wala\",\"winner\":\"blue\",\"payout_total\":1120,\"settled_bets\":1}',NULL,NULL,'2026-11-15 10:29:27'),(45,10,'teller','bet.paid_out','Bet',10,NULL,'{\"bet_id\":10,\"payout\":1120,\"teller_id\":10}',NULL,NULL,'2026-11-15 10:29:27'),(46,9,'game_master','fight.betting_opened','Fight',7,NULL,'{\"status\":\"betting_open\"}',NULL,NULL,'2026-11-15 10:29:27'),(47,9,'game_master','fight.betting_locked','Fight',7,NULL,'{\"status\":\"betting_locked\"}',NULL,NULL,'2026-11-15 10:29:27'),(48,9,'game_master','fight.result_declared','Fight',7,NULL,'{\"result\":\"meron\",\"winner\":\"red\",\"payout_total\":420,\"settled_bets\":1}',NULL,NULL,'2026-11-15 10:29:27'),(49,9,'game_master','fight.result_declared','Fight',7,NULL,'{\"result\":\"wala\",\"winner\":\"blue\",\"payout_total\":0,\"settled_bets\":1}',NULL,NULL,'2026-11-15 10:29:27'),(50,9,'game_master','fight.regraded','Fight',7,'{\"result\":\"meron\"}','{\"result\":\"wala\",\"reason\":\"Rehearsal: result corrected\",\"reversed_payouts\":420}',NULL,NULL,'2026-11-15 10:29:27'),(51,10,'teller','shift.closed','ShiftSession',7,NULL,'{\"bets\":8,\"stake_total\":4700,\"attendance\":true}','127.0.0.1',5,'2026-11-15 11:02:10'),(52,8,'supervisor','shift.force_closed','ShiftSession',3,NULL,'{\"reason\":\"Left open overnight from 14 Nov; terminal unattended - supervisor forced close\",\"unsettled_bets\":2,\"unsettled_stake\":200}','127.0.0.1',NULL,'2026-11-15 11:02:37'),(53,10,'teller','shift.opened','ShiftSession',8,NULL,'{\"workstation_id\":5,\"attendance\":true}','127.0.0.1',5,'2026-11-15 11:10:15');
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bets`
--

LOCK TABLES `bets` WRITE;
/*!40000 ALTER TABLE `bets` DISABLE KEYS */;
INSERT INTO `bets` VALUES (2,1,1,3,'red',100,1.850,185,'active','2026-11-14 10:59:46',NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-14 10:59:46','2026-11-14 10:59:46'),(3,1,1,3,'draw',100,7.000,700,'active','2026-11-14 11:11:16',NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-14 11:11:16','2026-11-14 11:11:16'),(4,3,5,7,'red',500,1.850,700,'refunded','2026-11-15 10:27:38','2026-11-15 10:28:22',NULL,NULL,9,'Fight voided — Rehearsal cleanup of an earlier run','rehearsal-hold-6af9f99a37251','2026-11-15 10:27:38','2026-11-15 10:28:22'),(5,4,5,7,'red',500,1.850,700,'void','2026-11-15 10:28:22','2026-11-15 10:28:22',NULL,NULL,10,'Rehearsal: wrong side entered','rehearsal-hold-6af9f9c6af044','2026-11-15 10:28:22','2026-11-15 10:28:22'),(6,4,5,7,'blue',800,2.050,1120,'refunded','2026-11-15 10:28:22','2026-11-15 10:28:46',NULL,NULL,9,'Fight voided — Rehearsal cleanup of an earlier run','rehearsal-payout-6af9f9c6c4fc8','2026-11-15 10:28:22','2026-11-15 10:28:46'),(7,5,5,7,'red',500,1.850,700,'void','2026-11-15 10:28:46','2026-11-15 10:28:46',NULL,NULL,10,'Rehearsal: wrong side entered','rehearsal-hold-6af9f9de64cf6','2026-11-15 10:28:46','2026-11-15 10:28:46'),(8,5,5,7,'blue',800,2.050,1120,'won','2026-11-15 10:28:46','2026-11-15 10:28:46','2026-11-15 10:28:46',10,NULL,NULL,'rehearsal-payout-6af9f9de6b0c1','2026-11-15 10:28:46','2026-11-15 10:28:46'),(9,6,5,7,'red',500,1.850,700,'void','2026-11-15 10:29:27','2026-11-15 10:29:27',NULL,NULL,10,'Rehearsal: wrong side entered','rehearsal-hold-6af9fa072e0f3','2026-11-15 10:29:27','2026-11-15 10:29:27'),(10,6,5,7,'blue',800,2.050,1120,'won','2026-11-15 10:29:27','2026-11-15 10:29:27','2026-11-15 10:29:27',10,NULL,NULL,'rehearsal-payout-6af9fa0734567','2026-11-15 10:29:27','2026-11-15 10:29:27'),(11,7,5,7,'red',300,1.850,420,'lost','2026-11-15 10:29:27','2026-11-15 10:29:27',NULL,NULL,NULL,NULL,'rehearsal-regrade-6af9fa073d0e0','2026-11-15 10:29:27','2026-11-15 10:29:27');
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
INSERT INTO `cache` VALUES ('e-sabong-virtual-console-cache-1a08ff3537b58141d5c2a9021f9f4d924e3f67a1','i:1;',1794768742),('e-sabong-virtual-console-cache-1a08ff3537b58141d5c2a9021f9f4d924e3f67a1:timer','i:1794768742;',1794768742),('e-sabong-virtual-console-cache-2697c212a0ed3a80c6170c8290e8262f31aff77a','i:2;',1794394873),('e-sabong-virtual-console-cache-2697c212a0ed3a80c6170c8290e8262f31aff77a:timer','i:1794394873;',1794394873),('e-sabong-virtual-console-cache-5c785c036466adea360111aa28563bfd556b5fba','i:2;',1794769859),('e-sabong-virtual-console-cache-5c785c036466adea360111aa28563bfd556b5fba:timer','i:1794769859;',1794769859),('e-sabong-virtual-console-cache-login:gm.north|192.168.1.15','i:1;',1794723585),('e-sabong-virtual-console-cache-login:gm.north|192.168.1.15:timer','i:1794723585;',1794723585),('e-sabong-virtual-console-cache-login:pos 001|127.0.0.1','i:1;',1794684680),('e-sabong-virtual-console-cache-login:pos 001|127.0.0.1:timer','i:1794684680;',1794684680),('e-sabong-virtual-console-cache-login:pos01|127.0.0.1','i:1;',1794763566),('e-sabong-virtual-console-cache-login:pos01|127.0.0.1:timer','i:1794763566;',1794763566),('e-sabong-virtual-console-cache-login:supervisor|127.0.0.1','i:1;',1794764644),('e-sabong-virtual-console-cache-login:supervisor|127.0.0.1:timer','i:1794764644;',1794764644),('e-sabong-virtual-console-cache-login:supervisor|192.168.1.15','i:1;',1794768982),('e-sabong-virtual-console-cache-login:supervisor|192.168.1.15:timer','i:1794768982;',1794768982);
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
INSERT INTO `cache_locks` VALUES ('e-sabong-virtual-console-cache-framework\\schedule-d6abe685ab037bed9472bbbdc0f4110e3e164fd3','5FkzTGH51fON5q8q',1794788100);
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
INSERT INTO `events` VALUES (1,'REG-261114-1TUO','HACKFIGHT / 2 COCK ULUTAN',500,0,3,30,'regular',NULL,'CSSA',1,'2026-11-13 16:00:00',2.000,7.000,'decimals',100,NULL,'draft','2026-11-14 10:28:22','2026-11-14 10:45:36'),(2,'REG-261115-9VMR','Demo Fight Night',10,0,3,30,'regular',NULL,NULL,3,'2026-11-14 16:00:00',1.400,7.000,'decimals',100,NULL,'draft','2026-11-15 09:54:18','2026-11-15 09:54:18');
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
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fight_events`
--

LOCK TABLES `fight_events` WRITE;
/*!40000 ALTER TABLE `fight_events` DISABLE KEYS */;
INSERT INTO `fight_events` VALUES (1,1,'created',NULL,'Fight scheduled.',2,'2026-11-14 10:28:30'),(2,1,'betting_open',NULL,'Betting window opened.',2,'2026-11-14 10:34:09'),(3,1,'side_locked',NULL,'MERON locked — no new bets on this side.',2,'2026-11-14 10:34:18'),(4,1,'side_unlocked',NULL,'MERON unlocked — bets accepted again.',2,'2026-11-14 10:35:28'),(5,1,'side_locked',NULL,'MERON locked — no new bets on this side.',2,'2026-11-14 10:45:14'),(6,1,'side_unlocked',NULL,'MERON unlocked — bets accepted again.',2,'2026-11-14 10:45:25'),(7,1,'side_locked',NULL,'MERON locked — no new bets on this side.',2,'2026-11-14 10:45:34'),(8,1,'side_unlocked',NULL,'MERON unlocked — bets accepted again.',2,'2026-11-14 10:57:24'),(9,1,'side_locked',NULL,'MERON locked — no new bets on this side.',2,'2026-11-14 10:57:28'),(10,1,'side_unlocked',NULL,'MERON unlocked — bets accepted again.',2,'2026-11-14 10:57:31'),(11,1,'bet_placed',NULL,'Bet #2 red for 100 credits.',4,'2026-11-14 10:59:47'),(12,1,'bet_placed',NULL,'Bet #3 draw for 100 credits.',4,'2026-11-14 11:11:16'),(13,2,'created',NULL,'Fight scheduled.',9,'2026-11-15 09:54:38'),(14,2,'betting_open',NULL,'Betting window opened.',9,'2026-11-15 09:54:57'),(15,3,'betting_open',NULL,'Betting window opened.',9,'2026-11-15 10:27:38'),(16,3,'bet_placed',NULL,'Bet #4 red for 500 credits.',10,'2026-11-15 10:27:38'),(17,3,'void',NULL,'Fight voided. Refunded 500 credits.',9,'2026-11-15 10:28:22'),(18,4,'betting_open',NULL,'Betting window opened.',9,'2026-11-15 10:28:22'),(19,4,'bet_placed',NULL,'Bet #5 red for 500 credits.',10,'2026-11-15 10:28:22'),(20,4,'bet_placed',NULL,'Bet #6 blue for 800 credits.',10,'2026-11-15 10:28:22'),(21,4,'void',NULL,'Fight voided. Refunded 800 credits.',9,'2026-11-15 10:28:46'),(22,5,'betting_open',NULL,'Betting window opened.',9,'2026-11-15 10:28:46'),(23,5,'bet_placed',NULL,'Bet #7 red for 500 credits.',10,'2026-11-15 10:28:46'),(24,5,'bet_placed',NULL,'Bet #8 blue for 800 credits.',10,'2026-11-15 10:28:46'),(25,5,'betting_locked',NULL,'Betting closed. No new bets accepted.',9,'2026-11-15 10:28:46'),(26,5,'result',NULL,'Result: WALA. Payouts: 1,120 credits.',9,'2026-11-15 10:28:46'),(27,6,'betting_open',NULL,'Betting window opened.',9,'2026-11-15 10:29:27'),(28,6,'bet_placed',NULL,'Bet #9 red for 500 credits.',10,'2026-11-15 10:29:27'),(29,6,'bet_placed',NULL,'Bet #10 blue for 800 credits.',10,'2026-11-15 10:29:27'),(30,6,'betting_locked',NULL,'Betting closed. No new bets accepted.',9,'2026-11-15 10:29:27'),(31,6,'result',NULL,'Result: WALA. Payouts: 1,120 credits.',9,'2026-11-15 10:29:27'),(32,7,'betting_open',NULL,'Betting window opened.',9,'2026-11-15 10:29:27'),(33,7,'bet_placed',NULL,'Bet #11 red for 300 credits.',10,'2026-11-15 10:29:27'),(34,7,'betting_locked',NULL,'Betting closed. No new bets accepted.',9,'2026-11-15 10:29:27'),(35,7,'result',NULL,'Result: MERON. Payouts: 420 credits.',9,'2026-11-15 10:29:27'),(36,7,'regrade',NULL,'Result regraded from MERON to WALA.',9,'2026-11-15 10:29:27'),(37,7,'result',NULL,'Result: WALA. Payouts: 0 credits.',9,'2026-11-15 10:29:27');
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fights`
--

LOCK TABLES `fights` WRITE;
/*!40000 ALTER TABLE `fights` DISABLE KEYS */;
INSERT INTO `fights` VALUES (1,1,1,NULL,NULL,NULL,1.850,2.050,0,0,2.000,'betting_open','2026-11-14 10:34:09',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-14 10:28:30','2026-11-14 10:57:31'),(2,2,1,NULL,NULL,NULL,1.850,2.050,0,0,1.400,'betting_open','2026-11-15 09:54:57',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-15 09:54:38','2026-11-15 09:54:57'),(3,2,2,NULL,NULL,'2026-11-15 10:27:38',1.850,2.050,0,0,1.400,'void','2026-11-15 10:27:38',NULL,NULL,'2026-11-15 10:28:22',NULL,NULL,9,'2026-11-15 10:28:22','Rehearsal cleanup of an earlier run','2026-11-15 10:27:38','2026-11-15 10:28:22'),(4,2,3,NULL,NULL,'2026-11-15 10:28:22',1.850,2.050,0,0,1.400,'void','2026-11-15 10:28:22',NULL,NULL,'2026-11-15 10:28:46',NULL,NULL,9,'2026-11-15 10:28:46','Rehearsal cleanup of an earlier run','2026-11-15 10:28:22','2026-11-15 10:28:46'),(5,2,4,NULL,NULL,'2026-11-15 10:28:46',1.850,2.050,0,0,1.400,'settled','2026-11-15 10:28:46','2026-11-15 10:28:46',NULL,'2026-11-15 10:28:46','blue','wala',9,'2026-11-15 10:28:46',NULL,'2026-11-15 10:28:46','2026-11-15 10:28:46'),(6,2,5,NULL,NULL,'2026-11-15 10:29:27',1.850,2.050,0,0,1.400,'settled','2026-11-15 10:29:27','2026-11-15 10:29:27',NULL,'2026-11-15 10:29:27','blue','wala',9,'2026-11-15 10:29:27',NULL,'2026-11-15 10:29:27','2026-11-15 10:29:27'),(7,2,6,NULL,NULL,'2026-11-15 10:29:27',1.850,2.050,0,0,1.400,'settled','2026-11-15 10:29:27','2026-11-15 10:29:27',NULL,'2026-11-15 10:29:27','blue','wala',9,'2026-11-15 10:29:27',NULL,'2026-11-15 10:29:27','2026-11-15 10:29:27');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `game_master_profiles`
--

LOCK TABLES `game_master_profiles` WRITE;
/*!40000 ALTER TABLE `game_master_profiles` DISABLE KEYS */;
INSERT INTO `game_master_profiles` VALUES (1,2,'GM-001','North Arena pit',NULL,'2026-11-11 00:02:41','2026-11-11 00:02:41'),(2,3,'GM-002','South Arena pit',NULL,'2026-11-11 00:02:41','2026-11-11 00:02:41'),(3,9,'GM-DEMO',NULL,NULL,'2026-11-15 09:42:55','2026-11-15 09:42:55');
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
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_events`
--

LOCK TABLES `login_events` WRITE;
/*!40000 ALTER TABLE `login_events` DISABLE KEYS */;
INSERT INTO `login_events` VALUES (1,1,'supervisor',NULL,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 10:17:47'),(2,2,'gm.north',NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 10:18:03'),(3,2,'gm.north',NULL,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 10:21:38'),(4,4,'pos01',1,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 10:22:46'),(5,4,'pos01',1,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 10:25:44'),(6,2,'gm.north',NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 10:27:11'),(7,4,'pos01',1,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 10:58:54'),(8,4,'pos01',1,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 10:59:05'),(9,1,'supervisor',NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 10:59:19'),(10,1,'supervisor',NULL,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 10:59:26'),(11,4,'pos01',1,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 10:59:40'),(12,4,'pos01',1,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','2026-11-14 11:14:34'),(13,4,'pos01',1,'blocked_login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 11:23:55'),(14,4,'pos01',1,'blocked_login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 11:24:09'),(15,4,'pos01',1,'blocked_login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 11:24:10'),(16,2,'gm.north',NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 11:24:38'),(17,4,'pos01',1,'blocked_login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 11:25:05'),(18,2,'gm.north',NULL,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 11:25:35'),(19,1,'supervisor',NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 11:25:47'),(20,NULL,'POS 001',NULL,'failed_login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 11:26:20'),(21,4,'pos01',1,'blocked_login','192.168.1.15','curl/8.22.0','2026-11-14 11:29:01'),(22,4,'pos01',1,'blocked_login','192.168.1.15','curl/8.22.0','2026-11-14 11:29:01'),(23,4,'pos01',1,'blocked_login','192.168.1.15','curl/8.22.0','2026-11-14 11:29:17'),(24,4,'pos01',1,'login','192.168.1.15','curl/8.22.0','2026-11-14 11:30:12'),(25,1,'supervisor',NULL,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 11:42:05'),(26,1,'supervisor',NULL,'failed_login','192.168.1.15','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 22:14:28'),(27,2,'gm.north',NULL,'failed_login','192.168.1.15','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 22:14:45'),(28,1,'Supervisor',NULL,'failed_login','192.168.1.15','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 22:14:59'),(29,1,'supervisor',NULL,'failed_login','192.168.1.15','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 22:54:56'),(30,1,'supervisor',NULL,'failed_login','192.168.1.15','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-14 23:04:19'),(31,4,'pos01',NULL,'failed_login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','2026-11-15 09:21:06'),(34,1,'supervisor',4,'failed_login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','2026-11-15 09:39:04'),(35,8,'demo.supervisor',NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','2026-11-15 09:43:53'),(36,8,'demo.supervisor',NULL,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','2026-11-15 09:44:05'),(37,9,'demo.gm',NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','2026-11-15 09:44:14'),(38,9,'demo.gm',NULL,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','2026-11-15 09:45:01'),(39,10,'demo.teller',5,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','2026-11-15 09:45:08'),(40,10,'demo.teller',5,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','2026-11-15 09:45:36'),(41,10,'demo.teller',5,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','2026-11-15 09:51:03'),(42,10,'demo.teller',5,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','2026-11-15 09:53:59'),(43,9,'demo.gm',NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','2026-11-15 09:54:04'),(44,9,'demo.gm',NULL,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','2026-11-15 09:55:12'),(45,10,'demo.teller',5,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','2026-11-15 09:55:18'),(46,1,'Supervisor',NULL,'failed_login','192.168.1.15','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','2026-11-15 10:51:22'),(47,10,'demo.teller',5,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','2026-11-15 11:02:10'),(48,8,'demo.supervisor',NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','2026-11-15 11:02:19'),(49,8,'demo.supervisor',NULL,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','2026-11-15 11:07:37'),(50,10,'demo.teller',5,'blocked_login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','2026-11-15 11:10:00'),(51,10,'demo.teller',5,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','2026-11-15 11:10:15');
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_snapshots`
--

LOCK TABLES `report_snapshots` WRITE;
/*!40000 ALTER TABLE `report_snapshots` DISABLE KEYS */;
INSERT INTO `report_snapshots` VALUES (1,'teller_shift',1,'2026-11-14','2026-11-14','{\"title\":\"Teller shift report\",\"shift\":{\"id\":1,\"teller_code\":\"POS 001\",\"teller_name\":\"Ana Dela Cruz\",\"workstation\":\"POS-001\",\"status\":\"closed\",\"opened_at\":\"2026-11-14 18:22:46\",\"closed_at\":\"2026-11-14 18:25:44\"},\"totals\":{\"bets\":0,\"stake_total\":0,\"active\":0,\"won\":0,\"voided\":0},\"rows\":[]}','f60a2215c610085d70ac3dc5118eeac301305e581d33aa091ac41b396dac7625',NULL,4,'2026-11-14 10:25:44'),(2,'teller_shift',2,'2026-11-14','2026-11-14','{\"title\":\"Teller shift report\",\"shift\":{\"id\":2,\"teller_code\":\"POS 001\",\"teller_name\":\"Ana Dela Cruz\",\"workstation\":\"POS-001\",\"status\":\"closed\",\"opened_at\":\"2026-11-14 18:58:54\",\"closed_at\":\"2026-11-14 18:59:05\"},\"totals\":{\"bets\":0,\"stake_total\":0,\"active\":0,\"won\":0,\"voided\":0},\"rows\":[]}','5bbf6edac19ee7cdc30611e027b0b4991c849fe1eec9abc7cecebd9aa7a4ceaf','f60a2215c610085d70ac3dc5118eeac301305e581d33aa091ac41b396dac7625',4,'2026-11-14 10:59:05'),(3,'teller_shift',5,'2026-11-15','2026-11-15','{\"title\":\"Teller shift report\",\"shift\":{\"id\":5,\"teller_code\":\"POS DEMO\",\"teller_name\":\"Demo Teller\",\"workstation\":\"POS-DEMO\",\"status\":\"closed\",\"opened_at\":\"2026-11-15 17:45:08\",\"closed_at\":\"2026-11-15 17:45:36\"},\"totals\":{\"bets\":0,\"stake_total\":0,\"active\":0,\"won\":0,\"voided\":0},\"rows\":[]}','8485bf965cb35a9733fb343222bf1ed7f3155d2f4777ccd5911ffe8be9032482','5bbf6edac19ee7cdc30611e027b0b4991c849fe1eec9abc7cecebd9aa7a4ceaf',10,'2026-11-15 09:45:36'),(4,'teller_shift',6,'2026-11-15','2026-11-15','{\"title\":\"Teller shift report\",\"shift\":{\"id\":6,\"teller_code\":\"POS DEMO\",\"teller_name\":\"Demo Teller\",\"workstation\":\"POS-DEMO\",\"status\":\"closed\",\"opened_at\":\"2026-11-15 17:51:03\",\"closed_at\":\"2026-11-15 17:53:59\"},\"totals\":{\"bets\":0,\"stake_total\":0,\"active\":0,\"won\":0,\"voided\":0},\"rows\":[]}','36442ced312a5eded23e1859c2f84e22d701f2953837c86984e0ef6153666562','8485bf965cb35a9733fb343222bf1ed7f3155d2f4777ccd5911ffe8be9032482',10,'2026-11-15 09:53:59'),(5,'teller_shift',7,'2026-11-15','2026-11-15','{\"title\":\"Teller shift report\",\"shift\":{\"id\":7,\"teller_code\":\"POS DEMO\",\"teller_name\":\"Demo Teller\",\"workstation\":\"POS-DEMO\",\"status\":\"closed\",\"opened_at\":\"2026-11-15 17:55:18\",\"closed_at\":\"2026-11-15 19:02:10\"},\"totals\":{\"bets\":8,\"stake_total\":4700,\"active\":0,\"won\":2240,\"voided\":2800},\"rows\":[{\"bet_id\":4,\"fight\":\"Fight #2 \\u2014 Meron vs Wala\",\"side\":\"red\",\"amount\":500,\"odds\":1.85,\"status\":\"refunded\",\"placed_at\":\"2026-11-15 18:27:38\"},{\"bet_id\":5,\"fight\":\"Fight #3 \\u2014 Meron vs Wala\",\"side\":\"red\",\"amount\":500,\"odds\":1.85,\"status\":\"void\",\"placed_at\":\"2026-11-15 18:28:22\"},{\"bet_id\":6,\"fight\":\"Fight #3 \\u2014 Meron vs Wala\",\"side\":\"blue\",\"amount\":800,\"odds\":2.05,\"status\":\"refunded\",\"placed_at\":\"2026-11-15 18:28:22\"},{\"bet_id\":7,\"fight\":\"Fight #4 \\u2014 Meron vs Wala\",\"side\":\"red\",\"amount\":500,\"odds\":1.85,\"status\":\"void\",\"placed_at\":\"2026-11-15 18:28:46\"},{\"bet_id\":8,\"fight\":\"Fight #4 \\u2014 Meron vs Wala\",\"side\":\"blue\",\"amount\":800,\"odds\":2.05,\"status\":\"won\",\"placed_at\":\"2026-11-15 18:28:46\"},{\"bet_id\":9,\"fight\":\"Fight #5 \\u2014 Meron vs Wala\",\"side\":\"red\",\"amount\":500,\"odds\":1.85,\"status\":\"void\",\"placed_at\":\"2026-11-15 18:29:27\"},{\"bet_id\":10,\"fight\":\"Fight #5 \\u2014 Meron vs Wala\",\"side\":\"blue\",\"amount\":800,\"odds\":2.05,\"status\":\"won\",\"placed_at\":\"2026-11-15 18:29:27\"},{\"bet_id\":11,\"fight\":\"Fight #6 \\u2014 Meron vs Wala\",\"side\":\"red\",\"amount\":300,\"odds\":1.85,\"status\":\"lost\",\"placed_at\":\"2026-11-15 18:29:27\"}]}','21d1c2f7bbe275dd2b9eb71a64f4b1321c8ab0c8bc0b14b954acfb1af5ae259c','36442ced312a5eded23e1859c2f84e22d701f2953837c86984e0ef6153666562',10,'2026-11-15 11:02:10'),(6,'teller_shift',3,'2026-11-14','2026-11-15','{\"title\":\"Teller shift report\",\"shift\":{\"id\":3,\"teller_code\":\"POS 001\",\"teller_name\":\"Ana Dela Cruz\",\"workstation\":\"POS-001\",\"status\":\"force_closed\",\"opened_at\":\"2026-11-14 18:59:40\",\"closed_at\":\"2026-11-15 19:02:37\"},\"totals\":{\"bets\":2,\"stake_total\":200,\"active\":2,\"won\":0,\"voided\":0},\"rows\":[{\"bet_id\":2,\"fight\":\"Fight #1 \\u2014 Meron vs Wala\",\"side\":\"red\",\"amount\":100,\"odds\":1.85,\"status\":\"active\",\"placed_at\":\"2026-11-14 18:59:46\"},{\"bet_id\":3,\"fight\":\"Fight #1 \\u2014 Meron vs Wala\",\"side\":\"draw\",\"amount\":100,\"odds\":7,\"status\":\"active\",\"placed_at\":\"2026-11-14 19:11:16\"}]}','93826e6b75584ac3759d58848c4a95c198b7a337761a5f27be4638bff0d9c664','21d1c2f7bbe275dd2b9eb71a64f4b1321c8ab0c8bc0b14b954acfb1af5ae259c',8,'2026-11-15 11:02:37');
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
INSERT INTO `sessions` VALUES ('05arEcDeySl3So42yGKEqaK6OQcyjbl2dLjgE5TA',NULL,'127.0.0.1','curl/8.22.0','ZXlKcGRpSTZJbWRvZW5CWU1rWldXVkpTYlM5SFRFcGxkVVIwTW1jOVBTSXNJblpoYkhWbElqb2lVRWxwUVRkRGREaHNhbnBoWWtKRlkxQnBVaXRVZVhkRlZteDBWRkJPYTFWMVJ6WXljV3hUT0c5VFIxbG1aemxIYVZOU01rbG1aSFZSWms0elQxVnNlbWszWVhSQ1UzWnlMMGxrTjBkemRURk1NMHRHTTI0MFVGaGFTVVJzTlUxRFZUUnVXSEEyV1RodVZDOTBlbUZ2TldWQ1p6ZExWemcyTW14T0t6VlVjbUpqVVd4blpYRXdjREpGWWtOb2VGTjRWMmh2TWk5bFJtWkJaMjFqVUhsWllXSkJWbloxVTFSeVJrcHRjelZIVUVaSWNGWlNNR2g1UkV4d1dTOUtSRUZGZGtkSVJXVnFlR0Y2Wm00M2VsTktSRlkxZVZwNWFURjVValJoZW1oTWRERjZURVpHUWt0UGVYbFJkREZsTVhKeFJsWnBVMVJ3VFcxS1RITlZLMWxpTHpaVE9FeG1aVUpQYXpCU1JtcEVNRVJGZG5aek9ISjRVbTltUVRsVlZGbDNXSGxxTVhkalprVTRabGs5SWl3aWJXRmpJam9pWkdZMVlUTXlOR0pqTkdGbE16TmpObVZrTm1ZM05URmlZalEwTUdObU1qazBOV0U1WkdOaVltWmhNalpsTmpabFptWmhPVEl4TkRJek4yWmhOamt4TnlJc0luUmhaeUk2SWlKOQ==',1794769545),('1GEo0D7AOc07ky12ajyo2jNCbwgkHbaUCXHWIix0',NULL,'127.0.0.1','curl/8.22.0','ZXlKcGRpSTZJbWw1TTIxS1UyOTZRM2g0Y2paclQyazJaVXgxUkdjOVBTSXNJblpoYkhWbElqb2lTRlp3U0dKS05FOTNRa3BaYkVseFdqWTRlVlpIYW1GNWVEUlBSVzlHVUN0UFJESm1lbFZxYlRWSU1VdHhTalY2VW1aRVltTnhOV3RISzBWR2J6bGhkaTlHVkUxbVFqbFllbTV2YUVkeFZGWldWek5JTm1ad01IaGhSemhyVldkTE9XSlJSMkpxY1VzeGRITmFXbGx4VXpGUGFucFlUVWRaVlZneVJFUk5jM2hsV0hsUVp5dDViR3BwUzI5dlkzRmFTeTlXTjNGNVZXWm1TSFpoUkVsUGVWTk1TMEUyY0hJdk9HTk5jV1pNUWtWSFpqWXhia3N3U2pOdFVtYzVjbWt5VEVjeVEzZHZWWGxXZG1sdFZEQTRNakowVEVwU1NqbDRPRGx6UVd0MGVqbFBURTFwYWtaelJYZ3ljVk5wVVZweE5qbDVZbFJPTlVWelJFSndTVVptSzNseUsyNW1NRkZFTTFJdmJGcEZWMFp6VjB4a1QzRXpUbE5zWm1aS1FVTnhaa3hzTUdKbmRrbExXbFU5SWl3aWJXRmpJam9pT0RFNE1EVXlNR0V3TTJReVpXWmxNbU5oTVdOak9UWTJaalJqTXpoaE9XWTNZbVJpWVRJM1ptWmlOVFZqTVRBM1lXSTRNemM0WkdZNE5HSXhPV0V4TkNJc0luUmhaeUk2SWlKOQ==',1794762545),('4vGMpu8E2QC3aqeeIHTAqnScNetPhEw5U1zaqb42',NULL,'127.0.0.1','curl/8.22.0','ZXlKcGRpSTZJbTB2YVdRemQwOXhVVFp1VWpKRFJYWklVRWd4TDJjOVBTSXNJblpoYkhWbElqb2lLMU4xTDNOWGNVSnlOako0TkhGRlZqVTJlbTVPTVd3NFFXNXdSaXRMU1dsWmVVSjRNVVpKU0VSeE9UQnVaMU5HY1Zwa1ZVdGhiU3QyY1ZCM1ZHaERObGRWV1hZeU4wczBOVVZRYTI5RWJuVkxTamRWYWtscloxa3lXamhQUTAxc1MwdDJhSFpvTUhCUldHaHFTWFZZVnpaYVZqaEJkR0U0WlZOVU56UkZlVWRQVDBneWFFMHpVMVpNVkd0U01UVktPVFp6TTBSbmJEVTRNM1p3ZUUxMFQyVTVabFZTYWl0cVNXZEtNRWxCYVVrcmFEbFNTMnhRTjNOVGVubFdXVWRyV0hkNU1qZFdka1pqTWxKNWJWVlpXSFpxZW1SV1dXb3lTVk14ZDNaVlptNXVNa0ZGY2sxTmNWcEVURE40YkZsMGVreEpNR0phVlRkek5sVmtkRVJCSzJwb05XcGxNbFJWVURGRWNqVXdXbE5OY0ZacFNsRTlQU0lzSW0xaFl5STZJalk1WVdKak4ySXdPRE15Tm1Fd05URXhNRE13Wm1GaVpqSmlNakprWlRSaU4yWTVZak5tWWpVeU1XUmxaVGhtTXprM09UWTNZbU15WW1JME0yUXdabVlpTENKMFlXY2lPaUlpZlE9PQ==',1794766442),('8J368MSS2fe3MhZYlW5YZX4ZCIijiVijYfkOvGQm',NULL,'127.0.0.1','curl/8.22.0','ZXlKcGRpSTZJbFZRV21sdFQyZGliMU55YWpoVlZteEVUek41T0djOVBTSXNJblpoYkhWbElqb2lPVEJoYlhGbWNVWklObWRzWmpCTVVISnVVVFYxYUU5YVJDdHdhU3RqZVdWM2ExWkZURUowY0U5YVN6UlBUVE5aVmtsS1VqTXpXRE5oZVRFMGJFVmhZbE5YYkROMlRVZHBjWEU0WW5FNGVITmlVRXRDTTFkeGVWWkdWbk5qWlVkaWFWRktUMGt4TkUxTFNWRlFUVWd3TVhKeVUwUnlXVnBSU2s1WVoweFJZMUZyY2xKUFRFa3dlV1ZOVkdrdmRGaG1OVUZQVjFGRmJIZzJUbGd3VGtKNk1YWm1jWEZKVkhadU5UbENObEJTV214dVVtMHZjMHNyT1U1blVFWkthbk5YZEZOb2FsUm9NR0ZwTWxKblNsRlpRbXhrWVV0cUwxWnBhMVpxTVZNdlJVODBjVGR6Y1ZsQ01tdFZSV2d6WjJ0UGVIbHJTemw2VVhGSlYwSjVkRFpXVkdaT1YzRjNSR1pOTUhnd2RtTlNiVUpqTkUxck1YYzlQU0lzSW0xaFl5STZJakUxTldVd05UWmlORGt3Tm1Ga1lUYzVNR1ppT0dKaFlUSmhOVEk1TXpBeFpqSTBZemxsWXpsak56VTFNalUxTWpFMk56a3dNelF4T0dVeVpHRXlOakFpTENKMFlXY2lPaUlpZlE9PQ==',1794766442),('8kRCcInoHO2OKSD6QNbNINPdHgOLq3hkWStFO3rp',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJbXhNTW1ONWNuVTNZMnMzU2s1UVpVZExTQ3QxVlhjOVBTSXNJblpoYkhWbElqb2lOVTlLV1U0clJ6bG9WRFJsY0dwTE5XRllaRVF4YlZwMWQySTJORWw2VEZadkswZENTRm96VlZRMk5XeHNUeTl1TkdKc1ZXUkJTVXR6YjI5UVdsVm1WM1JOZG1FNGJGQmlha3RKWjNkSmFVZEhjbFZzSzNsR1l6ZDJiRTFHYlZoMllqUnlRbVpQT1Vab1ZXdzJhVkY1U2paWE9FUkRPVkJHY0U0NFNUaDRhV1JXZG5VMGJXSmhSR3RTTVZGVU1XWXdibkJOWWtKaFlYWmFSMUJZTm01MVRXcEtkMkpDUWpGclNXSktSMnM0YUhsTGVHdDBlbFJCWWxCNk5XUkZjVzgxSzBOaVIzY3haRm81YlRKSlltSlBWeTlJYWk4MFVHSTVVV2h5UjNnM1RGZHFRMmROYm1NdlVtbFdiVzVzWmk4ME1rNVhlRmRZUTJRclNDdHZXUzhyY0dGWmEyRkZhM1pwZWxaSkt6TlhNbUpDUjNvclZYaERTMlI1ZUdOVFdEQktUM1ZsVUZWV2VWbFRhMEU5SWl3aWJXRmpJam9pTURneFpURmpOVGxrWlRkaVltWXhNV001WW1RNE5HWTVOVEV4TXpWbFlXTmtNR1l5TUdVMVpUYzFNbUV5WW1ObE1EQmpOMkl6TlRVeE56a3pNV1JqWVNJc0luUmhaeUk2SWlKOQ==',1794762939),('9tJJnAgFJjNb8jk1D3bFQIxK2a1sCWZ3l7HHYbyC',NULL,'127.0.0.1','curl/8.22.0','ZXlKcGRpSTZJbGR5VXpSYVpYRlVWMDEwTXpoUVVtdFBOV1Y2U21jOVBTSXNJblpoYkhWbElqb2laalZJZHpaME4xazNUMnBVU1VGYVJYUnVOMFZyV0VOTFlrdE9ibHBhUlZkdVIwWkRSMVkzYjFkTmFuUm5SVFF5YlU1Rk9VaHBSMXAzYjBvNGRETlBUWGRqZVRaSWJYVnRTV2xGZVZjM1ltRkRNVTVTYkRsc1ZqTjNZWE5XWTBWdFIxcFpjMUJQV0hCMmFTOXhhV3hOTW1wSlNsSmxZVVpVZVV0bWNIbEtNa05GWVZGWGVYZExTa2xWV2psS2RFNUtORGQwYUVwRlpUVnFhR1JKYmxKa1FVaEtlVE5aYVZBMVZtODVaRGhVYUVwb2FXa3ZWVWRNUWxwaFQyTm9Wa0pvWlRWRVVuRndRVE5CU3pCVVJXVjVWSEptUlc5T1NWWm1ZbXhWUlVKc1QxSnBPVzl6ZHpnNU5FbG5NbXBXV0hwVk5IVllaRkZFTkRWclJrVXlkVTR6ZW5aalRIWXdVRWRqT0RoTWFISkZPRlEwVEUxSGNXaEdVa1JEUmtWTVYwZDFRVzF4TlZWMmRuQlNlVUU5SWl3aWJXRmpJam9pTVRobU9URXhaalZsTm1JMU1HWTRNbVl5WkdNMFpUSmpNbVZpTWpaaFpEa3dNamMwWkRrM05qRXlPR0k1Wm1Oa05HTm1Oak0zTVRneU5tTTBZbVprTUNJc0luUmhaeUk2SWlKOQ==',1794763671),('Ak0bmojuk4llv1jvZ08HdMaGJwk2V6MI7gbSpvvA',NULL,'127.0.0.1','curl/8.22.0','ZXlKcGRpSTZJamQzYm1KRFlWUmtSM2Q1T1M4eVdsRXdOSGhXUjNjOVBTSXNJblpoYkhWbElqb2lhV0ZLVlVaYWJGWlhXRzkyZFRWRldIUXhhelJsWTFwak4ycGtabGxxU0VsaWFsSmhTU3RQVTJkbGFHRTJkM05RVjJKMFkyeFBSbTRyWkdSWE5ubEpWV2R5VkhGMmMwMVRTVXBRYTI4MUwyUlRVbE0zTm1oaWJHVkJkU3RsVkVwS1ZsWkJkM0JRUmtzelExZzRjV05RVTFjeVIybzFTRU1yWkVvMWNXeDVSbUUxVlcweVpsQk1XRVoxVHpoUlp6RlJUbEl6UmtNMGRtVkVXVVZSU3pJclZXWmxlbEJXWW5VclFtNTBTRzl2T0VoS1lVVkJOVzlqYTBReGVXMVdSa0owV25kM05HeG5WMDVGZW10aVJXdG9Zbmx0WjJOemFYVjZiMWxxUm1WaGVtTk5Ubk5aWnpFMlVHNDFXWE50TUhkYVEwd3hiMGROWWxoVFkxUkdPV1V2ZUdWV1FqWkdRamRTTTJSVk1uRnNXbU40WTBoSFluWlVXa3RPTlcwd1VVVklRbE5FUjNOTFEydE9kbGs5SWl3aWJXRmpJam9pWXpBMllUQTVORGt3WmpJNFkyTmpNRFEyWVROaE5qTTBNV00zTmpkaU5HUXdOVEUyTjJReVltVmxOalkyTkRJeFpHSXpPRFUwTjJabVpEY3pPVFZpT0NJc0luUmhaeUk2SWlKOQ==',1794766047),('bKIUUYdNJClaPz1nVv96mZ0Z5Z3TdodskLx487Oc',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJbGx1TXpjNGVFUXhkbVk1Wm1nNU1EbEVURGhPVlZFOVBTSXNJblpoYkhWbElqb2lOazFrVUZCU1FYSlljRFpwTTJWTVJEbFZMeTlrYjJrNU5WSlBWSEEzYVdRemJ6UkliRlZMT1ZwMVFTODFTbXRWUWs1VmExRlJXR1YxTURoamNWWmpOeXRVYjJ0RlVXTnRTVXN5ZEU5SmFYTldRMU5NVkVWT01rbHRVREUyVUVkQmIwMUhUMms0VUhaSVkzaDZhbmcwZFVZdk1FbHpUVUp1VDBWcWJqVTJaRGhFYzBKTE1GZG1OVTVZUVVGWFQwcEZPR05GYTBOdU9XNDFOR2hrUTJKU1QweDROR3d4YUVKWmNTOU1WazlEZUhKb2VHbEpXVTlDYUcxNFEwOUhlSGxxYTFRclRVUXZkR0pyYzFaclZqUnBSM1p1ZUVoUFFXTjFSa28xUVRCRFNFMWlabGRRUWpKMGJVZzFhMkV5Y25kQ2RrOUNjMDlKVmpWcmVIcE5LM2hTWlhwR1NsQlJXV00xYzJOWVIxUjRUazlaTWxrd1dYUmtaMlprYldsTlZrdzFOa1ExZURodGJHSmlXbk05SWl3aWJXRmpJam9pT0dGalpEWTRPR1ExWldOa09XUmlNRFV4WmpVelpqWXhZVEl6TkRSaVlqUTFZVGs0TXpBMlpXWTVPR1V3TVRGbU1tWXhZall6TXpGbU5HTmhPVFE1WWlJc0luUmhaeUk2SWlKOQ==',1794764308),('bLDyUjpS4Ad3DQ2tidMct952CZwqTBxb2pUNhYnw',NULL,'127.0.0.1','curl/8.22.0','ZXlKcGRpSTZJbmRHTmxsdmJtbFFZMjR4YmxCM1owVlZOVzF0YVdjOVBTSXNJblpoYkhWbElqb2lVMjFSUWpkcFNsTk9kMGhHY0doaVJHRmxMekJOTDJ4SVRUUlJaVzVtYW10NE1uZDRSbFUyVFVoaldIUjZSME53ZFVaM1pXcG5iMVpQY0hkR1ZpczVaRVYyVUUwMlN6WnZkRVV4ZDJOMGIwcDZZVUp0SzJNeVNYVmhSV1lyYTB4NldHdEplQ3RhYTNseFNtUkJiRlpIY1V0ck5rUnhSM1psY1ZoSmVGVnVTMUZEVDBkalIyRlNkVU4zU1ZCYWQyRjVRbmhEWVRkeWNYSkxiVzlwUmtSUFMySXZZM2xTZWtaTlVsQnZObEpUWlhZM1lrdzRkVTV2UkdRcldVUkZabWx0T1doMVlsVkNVbVZXV0dRck9ESjNlR3RyVFhkUmJEWklSU3RVV1RrclVrVTJlV0oyTTFReU5TdHJXbHBhTUdZM1NITXlla2xUUjBkb1FXeEpkV3BrYm5KaE5sVmpMMHN5ZWxWMlVFbFdlVXhOU1RrNGVuVXhaMHBYTnk5aGRDdEhRVWMzWVZwcVYxRlNPSGM5SWl3aWJXRmpJam9pTlRrMlptVTBNV0UxWldZM09HWTRORGc1TURneVl6a3hNakV6TVdZeFlURTVZekJrTWpBMFlqUmhOV0ZoTVdNM05XSTFaV014TWpkalltRTRPVFpsT1NJc0luUmhaeUk2SWlKOQ==',1794762939),('CL3w3gtCsVgVczJLXqFXuoNyxVYttXYGd8ddU9Lb',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJak0xYVRkdFFUbG9MMVU1VVhKUmNUVlFURzU1YW1jOVBTSXNJblpoYkhWbElqb2lLMGhZZW05bVkyUldWSEZ4WkdWWlNIcHRhMEl6YWtwVWVqSlFNWFF4WjNnME4yTkNjWGg0YW5abFJITldVakp2VlV4dlYxSkpjM1JQTWl0SWEzcE1TMDFMTkV4NVZXTXdhV2xZTVhaWGVteEllVmN5WmxOV2RrZGxXazQwWlhFd2JIbzNlRlU1Ym1aM1ozSXpVMGhPYzNsb2VWcFplbTE1V1VkSmRIcGtaRzB5T0hCeE9Ea3pWMDlNZEZkcFZrTnRZV1ZKWkVZelRtbEpWVXhtU0RCMllYUjVVRm80TDNoaFJIWkpUMmxxUjFZMFYwbHVibUZXVlU5SFFtVlpTSGt3VGtkWWFsVmpjamxwVTBwbFNWTnVWVVJRTDNCUFJrODBkWFJwVWtoM1UyZFpORkEyTDNoT1lucHJPVWx2TDFoelRuWlpWbTB4WWtKVFNGUmxLemsxT1RKalluQXhXbkZYVldscFYxQm5hMHQxTjFVdmJXZDBPVWQ1UWpGbUszaGlVbTVSU2taNWVrWk5SMEU5SWl3aWJXRmpJam9pWTJGa1pqazBPVEU0TjJabFpURmlNVE5pTlRoaE1qSm1aREUzTWpneFl6YzNOVGhpWVRnNE9XVmtZMkl6WmpZME16ZzROek5pWlRRMU1tRXpZVGd5WXlJc0luUmhaeUk2SWlKOQ==',1794763949),('Clk0ZDlCgVLsPFkE1kDvpsh74M1gSzBBDl4BoLMK',NULL,'127.0.0.1','curl/8.22.0','ZXlKcGRpSTZJbVJCUlZsWVdtNHlhVU5uUjBWNlIzQkxhM3BqWW1jOVBTSXNJblpoYkhWbElqb2lSalE1VUdZdk5YSkJVbkp5Y1VZeEswUkRhR0ZMUkc0MUx6TlVVbTlITmpsRWJqTkNhRVY0YmxodU5EZG9OV05MUmpGT01VZDZkakJFWlU4elIwSkROV2R0TmtGak1VcFlaRVpCU0UxdGNEWlFXbTVsU1dVellXSmhWbUl3WkZKVVVXWXhNemxRWjJ4NlRHMUhXVTVtT0hWMVRGVmhTR0poYkRoeWVYUk9WMmRZTUVKbGVtUmxjbGx2TlRoTlRUSkViSGhWUkZWblVtZHNkM1ZYU1hKMlltSk5ZM0JGS3psMldrTm9ZemhWVVRoWlYySkNhV2RsWWs4elV5dDNNRVpQZW1oYVpHc3ZOSGxaZVdwUGR6Um5SVXBQV0hsdWVWaHBWVVF4YW5SbFdpdFRRM1EwYlhaT2VUSTNjekkwWlhKclNFdFdhbEZrZEcxVVJYWlRTRkozZWxCV2IzZEdWVWgxYUZoaE9ETllhbXQxV1dwNk5ucHVkMWRMZEdkMlYwZDNTV2x1ZWtsb1YxVldSa0U5SWl3aWJXRmpJam9pWm1GbVpUQmxPRGN5T1RFMFpUZzFNVFkyTnpJMllqUXlNV1poTWpWaFpESTNPV1ptTURGbVlXVTBZemhrT0dFME5UaGtZVEUyWWpReVptRTJOekZpT1NJc0luUmhaeUk2SWlKOQ==',1794768285),('cXybXLTQijLmghKK7icJ2qmSTOiY3IUyobSGBf9r',NULL,'127.0.0.1','curl/8.22.0','ZXlKcGRpSTZJbXRqWWtkdmVGRkhhWEZrTm5ac1RGUllWalprUVVFOVBTSXNJblpoYkhWbElqb2lTa1pUT0RONmJHRlBVMmRYVUVaclN5dHBNREZFUm14NVpXeHZhbVpOZG1sd1ZuZFZjVlJ0ZDB0M1lYWTNlRTlMZUhaSmF5ODFiMnBOUmxNd1NtRTRZMGxPY0VONE1IcEdWbWd3VDFCS2VIcDVRVlpGVEM5eVkyb3hXbmhWS3lzMVkwWkJMM1J1TUdWVlMwWXJSMnRPTTNGVFpHZENibU5WZFhkNVVUSTFiMXBVT1hSVWNYSmxlVTFyVjFsTGFGcHViVXh4YlVGeFdETTRVRTR5VFM5UmJXd3plVmczUms1cFFYVktlVE4wVlRSVldYRm9NRFl2YzB4MmJVMVdOSEF2WVZkR1dtTTNiV3BrU3poWlJYbGhjMlpwZDFZM1dXSmpNV3c0YkVGaVp6UnVaMjVNVURsSk9HZEVkMDlNYlc5VEt6TlhlRGxPU2s1VVJsVkRTR1F4WTNJcmJqWXlaVmRHZG5SUFN6Vm9lV3g1UjI1eU15dE1VM3A1ZGs1a1RsWmlkakkyVmpGdFZXaGpWMmt2ZDNONVNFOXNlamQ2TnpkTk9XcGhPSGszTW5Kd01ETkxMekJxYlhaa2RtWnJjMjlFTVcxYU4xSjRjSFpGVDNOMVZuUm1NRVZNWmpaTFZ6WlhTVTV0UTFacE16UjZNa2t5TVc5elVITkNkbkp3YVRGSlduVkxlamdyY0hsdE0wOHphemxrZWsxT2MwWjZRVDA5SWl3aWJXRmpJam9pWW1JNU16STRObUUzTVdSa05qWmtZV05sTTJWbVpqazBNV0psWkRFek1qRTFOelV5TlRRNU1tVXhOR013TlRCa1pqSXhNamsxT0dFek1qWmxZakpoWkNJc0luUmhaeUk2SWlKOQ==',1794766047),('e7rAbTz7CGvkmYGebyB4QhRzPIfWW54Tai3OW5wK',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJbkUyVm1Gb1NXdFhkMUZoVWt4c09HcDFaMGxaV2xFOVBTSXNJblpoYkhWbElqb2liRlZCVFdobFdtWk9kMWx1TVZGdFMzTXhTa2gzUTB0ME0xaDBaRUZPT1RabFZGSjNkVFZ4UzNoWFRrWndUMkprUlZKWWVrcHhUbEZ5UlRVeE1UTTRkMmhITVc1TWNYTlBOVkJ3UTNWWFRGaDFaVlpKWlVNM2MxVXplQzlhZWtoQ1pqTmhWQzg1TURsQlRESjZRekprSzBkVVJsUXdZVFJYU3pCSE1FUlZWVUZETkdwSWRqQkJiRXB5Y1U1MVZYQldRMU42YUdKeFNqaE9NM0k1TTJWSVNERnRkbnByTWxnMGVFd3JlVnA2Y1haelR5OWpabnB4UzNGcFZpOTRTeXRTWlVkRU5qaDFUbkp2ZDNod1ZYcFFORGRSY21aS01qaGFhSFJZZDFGM1JrMDJPUzgxYzFWc1N6ZFNWWFJhTTJoelRtUmxaVWgzWTFOWmNXOVhRU3RzYkd0clRtODJZMUpYZFVkTFFWSnBZeTlRZDNkTE1EbGtWMXAwUm5aaGVHTmlaV0ZIT1hobk0zVnlibU05SWl3aWJXRmpJam9pT0dFNE9UbGxaVGs1TXpWa01UVTBOR1V3TVdWa05UQTBaV1F3TlROa09XTmhPVE0wTmpaak9UVXdZekU0T0RGbE5XSmxZV1V6T1Rrd09UZGpOVEZtT0NJc0luUmhaeUk2SWlKOQ==',1794766647),('EIAuR65aXCzEsUVXKHIVN2C1uJVo8vXws9YZ7KHh',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJbXRpWldaUVpuRlVaak5sV2t0MU1tOU9hWFo2ZUhjOVBTSXNJblpoYkhWbElqb2llWE5FWm5oUVpVVk9VekprVFRrcmJVVTJhV1JTVUVOQ1pUbDBWRkJtVVM5d1ZUZzVka2hIY1ZwME5YaFZZVzQxVUV4cmNFRklRWEYxZWk5Uk4yMVBlbk5qYms1YVExUlRNMUF4VG1wa1JrTkJNa3h4V2xaVGJqTnBRbFZoUVN0SE1HVlZUREJKTVhac2FYbEZZMUZGVEV3MWFUTnRWRE0wTnprd2JXSnpUa3B5YkhWd1l6TjBTRkpsZEV4S2REbHBWVzVvWVhCTlVWRjZlWG8xYW5adWNDODBSbE5WYVVaR1RIRXJiakpOYjFKbE9UbHlNaXRoU21kQk9HaE9VbWRhYkdWVFMyVTRZM1JIWkVWYVMxZDJibFJsYURkMVVWTllXRkptTDJOUVkxcE9ObGxuV0RCSFRFdEpRVFUwZUVvd2JtaE1ZM0I1VERSTVoyNUZTMGM0UmxoVGFGRlJOVEU1YWlzM1FXbDBNMFl2VDBndk1rOUtUMHBHWVVVcmNFRXpkMVJwTHpCemFWWnNabTg5SWl3aWJXRmpJam9pT0RaaE5qZ3pObU5sWWpVNE1HTmlNak13TkRGbU5EbGhZMk5sTmpCbFlUVmtZbVEyT0RJMU5EVXlNR05rT1dJd05XVXdaV00zTlRObFl6WXhNemd6TnlJc0luUmhaeUk2SWlKOQ==',1794769742),('fQELJSRPNMoBmAQPLDdf1cARp28mZpJQvPV4Vkcj',NULL,'127.0.0.1','curl/8.22.0','ZXlKcGRpSTZJamhJVFRGc2RFdExjemsxYUZOak1GbFNNWEUzVkdjOVBTSXNJblpoYkhWbElqb2lhVWxGYmxZNVUyNDBhbEUxZFRsT2RVOVNhVzVST1d4QmF6Z3lhVmsxV1RKNlNESmxiblZKV1U0emFUbHhMMWxOZUd0aE9HTnBWek40WW5WVFJtaFFZalpXTjJselkxVnRORGN4U2xNeFNEWjFVbVpRY3pSek9ERm9ZWEZWWVRKdFozbDNiMk01T0d3eWQydFJNMEowVjJVeE5teGhPVVpHUjFSSlZVeEhTRFp3WWt4VVMzb3hZMVpxZFc0ck9UY3ZhVXRhZWxkM2FGRmlOelpMUlc5bVNXTjNTalJwVVdwQ1pFNWFVWEpZUmpsTlpHVlliVkZCVW1sVk5WUk1XbmN6WkdGU2RXTklZamcwVmtvMWFpOXdOVFZIYjBJelNYaElhRE0zZVZKRVRXZ3pXVTUxV1hGQ2JUVTBaV3BhTW1aM1VVTldUelIxWW1WQk9IWTVTMXB6TVVodldXMWpVR3BtV0N0a1RESkJMMDVIUm0xcVN5c3pkSFpIT0hBeFJFdHpURUZOYURGd05sQkdVMVU5SWl3aWJXRmpJam9pTVRkalpUZzJaakU1TldGaE5XVXlPREkyWmpBM09EQXdOV1kxWlRObVltUmhaR1poTWpKaVpXSTBOemsxTWpKak5tSTVOVFkyTkRFeVpEVTJNVE5qWVNJc0luUmhaeUk2SWlKOQ==',1794769642),('FzvaGpTgAPctOmwmfE9OXF8YKJo4u2d42gX8OqYR',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJbk5VSzNsYVNHbE1kSEpLYUc5UmJuZDJVVUZIWjNjOVBTSXNJblpoYkhWbElqb2lObXBEYzJwWmJHNVNWa2xKUzFGUmJ6ZHJjbEozYURGYVpsSjBTSHBHWlVaMk5tSlVjMWhDWm05TFRVWjViblZ3UTAxMVJIWkhZbVpOV2tNMlluVnFiM05wYmpoR1NFTjZMeXRNTW1WYVRVOVdPUzkwV0hWMVlXWXpPR0ZoVWpWbFRUZ3ZaVmRQTW10d2RuRXhXV3BRZUVwVVIydFZVSGhtYVZKME1VWXlla2RaYmprM2VIQXZTRVZEUm5WYWNFTkJWVkJ1WW13Mk9WSndUSE12UzI5VmVrZ3lLMngxZEVsQlUyTlJiRkY2TkhCcFdWSnBNamRSWm14V2MwRm5iQ3N4UTBNMFoyVnViMmx0YVRWU2FXRXZWbUY0V2xaRlRWa3piRkpTZWxGd1VtTk1halJoWlRsbFJtUlRNWEpoZEhWbVEzSklZMUEzZW1kcFMzZDZObmx6TXpSV01GY3hUVEpwVG13MlowaFJVbGhXZVhOaWRtWnFaM1ZhTm1GTk0wSTFiamw2Y0c1RFJsWkhRbEU5SWl3aWJXRmpJam9pTXpVNVpXVTNZVFJoTVRaaE16VXdaV0V5WkRNellXRmtNR1JqWlRjME1EVmxNMkl3TTJGa1kyWXpaRFl6TlRFM1l6TXpNek5tWmpBME16VTJNVGs1WXlJc0luUmhaeUk2SWlKOQ==',1794762340),('GRkGnDrp3paKQJ3xWLyhXoYYFq6ycLC8A81vNmO7',NULL,'127.0.0.1','curl/8.22.0','ZXlKcGRpSTZJbGgxUTA1c1pISldSbE5SY21scmNGY3lORzEzWm5jOVBTSXNJblpoYkhWbElqb2lUSHBuYVN0R1drSlZSV1pSTVZZelZWVkRXa2hqYkdWMVZuQnpRVzQxVWl0YVNXMVJWa2h2UjBkeGVGcDZOalJ5V1dvMGFGZzFabFV4ZWxNeWNsRkNSelJrVEVoT2FtNVhZVWt4Ulc1ME1ESk1hbWxRTHpOek4wSXdNR0pZVUhGMFZXVkdiVTluUlVveWQyWkVNRWxGYVdGV05rNXFTM1F5Y1RadlIzSlNRVUpUTUdWR2VVUTFRWGQ1YkRsaVpERm9jak5sWVhFMVJsSjZha1JxUlhocVJEUllUVEp5VFdaTmVWbDRiRFpVYUVFek5FeEVaRVJwT0dFd01tNXJhWGM1WVVwbFkyRkpVRzVXUVcxalNWRTBjUzluTkd4SWVWQlpNVVI0YWtvM1ZsYzVkMm8zT0hkWlUzcHBSVTV0TjJneWR6TmlPVUZ4YjA5U1RUWXhRbGhPWVdobVVFTjJWRmN2V2xWdEwyTnVkM0JhYzNWQlJqTktTaXRST1V0dlptWnFkV3BwU1VnMWFXTm1PVEE5SWl3aWJXRmpJam9pWXpneVlUaGlOek0wTlRrMVpXWmhZakprTkRCbU9ERmlNemRpWXpZeE1UTXdabU13WW1Jd01URmpPRE14TlRjMk1ERXhOek0yWldVNFpqWmlNVGczTWlJc0luUmhaeUk2SWlKOQ==',1794762621),('H0mNNaJXGRe0YuTBxsXs0LSg7eI2YIlCUSp4f5iU',NULL,'127.0.0.1','curl/8.22.0','ZXlKcGRpSTZJbXRETmxWVlRWTkJSVnB5VUdNMWVUWTVaMGhFV2xFOVBTSXNJblpoYkhWbElqb2lTR295VEhrNU1WY3dkRGhWUW5KdWQwMXFUbXBPWTNoQk1FMW9kMFJuVWpONGN6RmpTR28zSzB0dFpXRlVVV2N4VTJsR1FUWmlTVkJET0VKR1pIWmlZVkZ1TWpsQmVIZzJVekZHZEdSNE56VTVNRlI1VnpaV1ZYTnNTV0ZKYVZGVVUzRldkRlJwUjFaRldsRTFUbWd5T1RaUVRHOWtUVGxrZUVKYVFYcERaSFJSWTFOSFlubEdiMHd4VUZwMFdHeHdkMVZRUjFGWlVFNVVTVXR2WmxWSGRWWjZWVU5WT0ZsTU5ERkNRa3htU1M5dVNIRktaVUZJVnpaalJsQnpia0l6T1RKdFluY3pNRVk0TjNkS1dWRjFUWE4zUVZwbWRDODFaMDl4ZFU0eWFrTnNTM2haYmtoQ2FHVTVaSGRsVG1GVlptUjFiSGgzV1U5VlJVdDFiM1o0V0UxRlFYZGtUWFkzTTBSb1VuZFhURWw0ZUdadmQyZEJVVXREYkdoVGIwOTBabEV5WlVoWWRFbE1UakE5SWl3aWJXRmpJam9pT1RkbU9UVmhZbVl3Wm1JelpUSmhOV0V6TURnMFptUXpPVFk1T0dWbE1HVTNOamRrTnpabE1XVTRNamhpTURsbU5qa3dZekE0TW1ObVlXVXdaRFZtWXlJc0luUmhaeUk2SWlKOQ==',1794768643),('HBHZFZF0UkVSizQvcjWIHehNGwaIZjwsAu2Jfwhm',NULL,'127.0.0.1','curl/8.22.0','ZXlKcGRpSTZJbFZ5VTJWVVowWlBkbTF3VTA5TE1rTjVXRUZNTTNjOVBTSXNJblpoYkhWbElqb2lMM1Y2YmxCR1REWlpVVkJ1VVZWQ1FXTTVUMHhzZEhSNWFHcExWRVJyTkROWGVYZ3liM1pqYTNKVlFsb3ZiRWRXYTJocVZVcGhXa2RrWTJsamFXWnlkV015S3k5eU1VbERWR05sWkZGSk1WVXhNMnBCZW1sVFJGTXpLMDlPU21OVVVIVmlhVkZuYTAxNVRXcGFkemxtVW1kTFJYaEVibU5XVjBsMU1rTkViR2RuTWt0SWQzbElOVXhNYlhwRFdYZE9kblYwWTJ3dmFYazBSbWw2TUVFNFltNWlhV3h4ZGtRMk9UUjJSa3hoUlV0c2FUbHplakJxUWxoWFpsZEdhbUpaTVUxblQyWlFSRXN2UTFGNEsza3Jaemx4TVZsNGQzWkdTbnBNT0RWeVduaFdibTVHWlV0VFJrbEhkWEJGUzJWbk4zSTJlRkZ4Y2pKR1ptTlRPVnB2UzJGalEwZEhTRWRHYWpWSVQya3JTV1pwVEhSVFNVSnlkRzl6U0c1U0syRlhNbkpzVGtSRFRFTmlTbWM5SWl3aWJXRmpJam9pTTJaaE5URXlNbUZoT1daak16STNNek5rWWpFMlpUWTRZbUU1WkRGbFpUVm1Nekl6Tm1OalpHVmpaamxoTWpVMk4yTXdNVGN5WVRRellUSmtNRGd4WWlJc0luUmhaeUk2SWlKOQ==',1794766020),('hfYkv7CVnzyKBdbLEIiwkEPFXCeEV9Q60cnvc9ct',NULL,'127.0.0.1','curl/8.22.0','ZXlKcGRpSTZJbmgyTkRjeU5tRnJRVGswVkhCRUwwZFpURGszUzFFOVBTSXNJblpoYkhWbElqb2lUWGRKWkhwWGVUSndWMVkxVVZwR2VDOXhVMUowVEZaUFEzZ3liaTlKVW5CS1JqRXpaMmRvV0VGV016VkdSRUZHVEhsVVUwSXZUMFZhU2psdmIxRkZiQ3RVY2pkeVJGWnpjVmRLTTNWcGRIQktTbTVKUWxoRFduRnNiRFJ2V25kRkswOXhSVGRGVkhGaGFURk1ZbkppUm5ONGVIbHFVM2NyVDFSRGVsQXliVk5PZVdGNlRWTkVZVXhVVnk5S1lsWTFWM1JXU1VKTVdHOVJaelV2TkVzM1JFUlhVSGh4YkRCV1JIbDBlRlJvU205c1FYRXhUMlZyTDFaNVRtOWtSSFZEZW5SVU9HYzBlVkpMU1RGbGFtOTFSREJwUTNKV2JIQktURTU0Um5GcE4xQk9hMUpHYlZOaWNFVlRMM2RtTDNkRkswUnpZakJyU0RoRU4yNWhWVlZEY1RSUVZqZFVUVmxXS3psMloyZDBOM1o1V2pOV2R6UktOV1o1U214MVIwSjRTSGhMY1hCbVFUUnpiakE5SWl3aWJXRmpJam9pWW1NeVkyVmtPREk0WVROa1kyUXdOalk1T1RSalpXRmhaVEJrT0dReE9UUTVOemcxTmpnMFpUTTVaVEEyWXpobFkyTXlOMll6TWpka1l6TTFNMll6WlNJc0luUmhaeUk2SWlKOQ==',1794769545),('HwZ98Vaw63nHunG3n3fwIWbKHT1DnsAe0O8TJXeS',NULL,'127.0.0.1','curl/8.22.0','ZXlKcGRpSTZJaXR4Y1VzNVJGRk5OWEJqU2taaGRFRlNlbU00YkVFOVBTSXNJblpoYkhWbElqb2laMGhXV0dwNldEVXhkRzh6ZEhrMVpWSTJTemhXWlU5RGFtaDJRbk0xWkZGV1EyMHZjRmxoYVhwRFFYcEZUWEIyYW1Nek9IaE1aMVZ4WjJGdWVFSTNXVkZZTjI5RFZucHZWR2hZWmxSb1dGTkdkRFZoWW1OeWMyNTNXakZzVnpGblMybDZhbVJXUms1VWQyeEdiaTkzTUhOcGFHUkRPVzFOTjFka1ZXeDVTWEpuWlRBdldGZGpVbkI0VDA1cldFVlFOa1ZzY20xYVkxRXZNM05zYlVac1ZtaDBXalo0Ym5aYWNFZGtkRkpuYURKT09XTnBOazlLTVhKUWMwdHFkbEpDWjFWWFVHeG5abEJTTkZjd2FXMHZORkZhU0c5TU5ERmtRVmRTYUZGclNXdExablZEVnpGNlJuRlRlWHA0UW1aalVFUXJTak5rVkZCeFZrOWhZVzFHT0hCRVMydHRORlJCV0dkWE1VUklialJUT0VNM1pEWjJlbG80YVRGcFdtUjNSVzk2WW1zdmEzVXJOREE5SWl3aWJXRmpJam9pTURJNU9HRmhNVFZqTXpkaFptVTRPR0kzTVdGbE5XSTJObVV3WVdNek9EZG1NelEzWWpka1pUaGhOekZtWXpJMk5qYzRNREJpWkRFM056VTRPR0kwWVNJc0luUmhaeUk2SWlKOQ==',1794768257),('i8yj9r7qIjaaTjLctGuigtjslFmeSKErDUKW1f1u',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJbXhtU0ZwcVptSXhRVEptWldGcEswUkJTV1ozTVZFOVBTSXNJblpoYkhWbElqb2lNR1Z1WnpoRWQweHlSVGQ0TmpaWlNXUjRSVlZPZG5KV04wVktURk1yWW5jMFJsQnJXWEU0ZGpKTmRXdHhjMWRzYWprMWVUWldTWEY2T1dSa2Izb3Zka2xPU1VwWGFsQm5VMjlZV0VGWFVIcFdRbTgyV1ZoWldEaHpjalJFUVU0ME5sTkdNVFY1Y0daSVdGazVaRVF4U2pZMU9HNXlkRXQyVGxsSlVrcENVbWhNU2tGUE1VeG9NblpuV0dGUGFrTk1ia2hPUXpKb2JVVjZTVlZHYTFReWJVUnlRMGszV0daQlpWWkJhbkpGTVM5RVZIQllZVlIxUVV4aGNrSndhV2xMZWtkdVMyZE5SVlpJTWxwRVJHdG9jV1I2TUVGS1pXNVNUbWxDUkU5RGEzcFdUa1VyUmxsM1NGbDFjbGh1UVVVMWMzUm9iRFY0Y1Rsbk5GY3lhVTFXWkRkQ2RTOXRRbGxFTVVOUVRIUnJiM3BLWkZoT1NESkRWMUpRTkM5aE4zbFlhREZ3T1VzNVExQk9hRFE5SWl3aWJXRmpJam9pWVRreU1ERTVZVEZtWldZellUSXdaakpoWmpOaVpETmtORE0wT1RneU1EZzNPR0ZoTkRVNFpEa3laR1ZoTmpZNFpXWmpaV1ppWVRVMU5qVm1OelZsTlNJc0luUmhaeUk2SWlKOQ==',1794768514),('JYtixs7zV7ji2kndTgXk9Iot3oBmbhDahhE3vsS0',10,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.202 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36','ZXlKcGRpSTZJa1ZZVm1WR1MzQmhaVVJDVVZOT2ExY3hORXhIYTNjOVBTSXNJblpoYkhWbElqb2laM05xU1dsRVpuRTVNMDF6WVhjeFYwazRPRWhDZDJ4VGFERndWMm8yYUhabVpWUTFhRGNyYTFCSFlYVkNVV281WVhnd2NtSkdhM3B3VGpCeFlqUlFaV2hNUjJ4WGNWSTVkazU2Ym14blpGRkZWWGN6Ymk5eFIwRmpLM2wyTDBaNk1XaE5ha2s1UlVwT2VFOU5RMXBxVkdnM2MzaGpiMXBCVmtObFJGTTVZM1JOWjFWeVNYQlBMMmcwUm1sNFoxUkJjekJ2YzNwUVRIRnlNa2hNVUZGYVduTlNNblp3VXk5SFpIVTVaMUJWTlVNNFREWmlUbU5HUzBwVlNEbFhSbWxaZFhFM2NWRk9kVTF2TVVrM01tUXdaVGREUm1kR2VVVlVLMEl2Vms1NlZHNVZTMkZCYzJzemVFaHZZazVSVTFJNFpsZFJZazVLUm5kQ05VczVaRVZYTTNReVIwUlJXV1JpTVhkRFQxcFNOSFk1VlZsSE1XOVlkbnAzY0VwRlEyWkxlbFp3V25GWlFscENaMFpzWjJWeU5IcFRSbWx3WXpZeE1rMHhZMDVsTVZsS1VEWkdjRzVUZHprellqaE1TMHhTVWpCNFRrNWlNVXg0UlZCbU5rSkpkekJuTkZKcVFqQlRkMUF4YVhCb1RFZEdaSFE0Um14a1NUbFlkazlTVjNCQ2JFRXJRV0l5Y21KUFQxaE5UMnBHWm5WQlptSTFiR2hPYkZwaGIzbFNiR3g0VG5CVGJ6VllLMFZYV0ZFdlYzQk9NRWNyV0VkNlZIcEZTRko0ZW1WcVVUVndkbnAzU1hGTU1IUXZNMk42ZFNzM1Z6TXJZekpGVG1GbFZVRlRPRXd3TjAxdk1rTlRlSEIwVkhGcWRWSkJkMWhuUkVzNGNYZFBkRlpoU1dwUVUzbHJUMjlYYjB4cFVWUTBTV1FyUVhSYVRFNWxkRUZLYzAwclFXeGFSbVZ5YVZkQ2VVOXdSSEI1ZEhkM1BTSXNJbTFoWXlJNklqUTROMlF3WWpWaU1tRXdObU5oTldRMVlqZ3pOMlZrTXpoaU5UTmtOek0zTmpReFpqVTBNbVkxWmpSbFpqSmpPVE14TW1abE5URmhPR1V5TVdVNFlUY2lMQ0owWVdjaU9pSWlmUT09',1794769842),('kgN5XQzsQgDAOQEovfDhAWUTi92UtyeCfMK9I59I',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJbE5EVURsVlFtd3dXVGhKSzJkNFZtRk5XakJTV25jOVBTSXNJblpoYkhWbElqb2lNbE00UTFndlVsb3pkbFJpUVVGVlRUaExLM1paVFV4V1VGUlZTbU5NV0U1VlduVXJTR1IyTmpOVlJqRkNkblIzYmxSWVVuZDVRMGxHSzBoVVRtOXBXRGMyVVhORU5tMVNWelZWYlRKQ2NYQjNhSE5RVDNvd01YTmtRWFZOY2pReU1rVjNhR1JOYVZkWk1WUXdRa2RRWWxwNWNVbEVXRUp4VjFGcVQyMHhjVUk0VW5wdlJYSjZkeXRSUW5CTE1FaDRjVWxwVEZJMWVEQnpUVWxxSzBoM1FVSkZNRVFyVVRsdGFVdzVlSE5qZWxscFJURnJPR2hPVmxrMWMyaHRXREEyZVdaYVFuSlZZM2xIZDNoWU1ITldRMFJKZGt3NWRtUmpORE14VEZKTlpWaHhla3A0ZERSR2FsaExOVFZOTW5KRWJTOVZVRXR1V0cxa1pVZGhhRzR3WTJ4U2FDOVFWbUZ3UVhoV1oyZG1Tak15YXpKd2F6SnBUbXM0YWk5cEwyTkVTQzlJWTBOS2JYWnpMelE5SWl3aWJXRmpJam9pTkRNd1lXUmtaakEyTkdFNVltVmxaRGc0TVRZeU1tWmtZbVEyWm1SbVptUTFOR00xTVdZd1pURmtNVGswTVRFek9EVTNNRGcyWm1JME4yWmhPREZqWWlJc0luUmhaeUk2SWlKOQ==',1794762390),('mLZaWWftAJyAg5C3b9NevkIg1WLTpLeZfiZ0GBxb',NULL,'127.0.0.1','curl/8.22.0','ZXlKcGRpSTZJbXBMYlV0NWNsVlNXamhHZEVRck9YSnZPRGRMU1djOVBTSXNJblpoYkhWbElqb2lNbEZXWW10Rk5VRTBWRTFhVDBaSVRXSkVkemR6TVdvNFIyeEViRmhUYm1ZMWFsaDROVkpLVkRsUlRpOUZLM05vTUVwQllraGtOVUpTVnpBNWVubGhVblZyZW5WV1ZEWjNUamxuWldSelpGTnhSemN3U0dSUmVqTXZlRXROUzNBd2JYZFdOR0ZwWWpKQ2FIaEVZekZPWnpWcGVHeDJUMnBQVUVsV2VVcE9XbmRPVEU4emFtdFVZMHhsTkhCT1RGY3dablEwV0dKa1VUSTVha2RrVVVVemQwczBibUpYZFd0V1UyUXJlR3Q0ZWxSVVVGSnliMWc1VUU5NGJVOWhVblYyZUVGV1NqbHZaMDFMTm04MmIwMTVZazVsY21zNFVXcFhiMnRxUTBoQlZqVnJlV05tUWtKNWJtTTRPVlZhZUdnNE1YWjVZMEZWY3k5Q2EzQTRNRXQ2VTFSaGFHdHpWa2gxY0c5WFNuQTRNbGd2WlVkek1GSkpVVTB6YXpKc1dGUlNRWGhoWkVKa1NVcG9ZMFU5SWl3aWJXRmpJam9pWXpRek5qSTNaalJrT0Rnd01qVXpPRGxrT1dOaU5USXdOV0kwWmpSaU56UTNaalF6TUdVME9HRTBPV0l3WW1Nd05XUXhZMkV6TUdSbE1UUTROVE5pTUNJc0luUmhaeUk2SWlKOQ==',1794766083),('nszpyDru16YCO6taSoJ6dSPsSrO0w9jKJfnDCcAC',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJaXQwYVhsWWRFeHBkVUprTldSc1luWXdjMjh4YTNjOVBTSXNJblpoYkhWbElqb2lNRlJ0VjNGbU9FTXdiRkJ4WkdkcFQyZFZNalZzUjJrMFUyWnhka3BIWjFKNlpVNXdhelIzV1hBMU1WWmhla3hsWWtjeU56SlViRlEzU1hoU2FUaHRjemh4Vlc5bmJuVllOazVMU0dWWlRGTTFWVk5IYkdkTVdHaElSRmhaUjJWcFdYWnZVa0ZCYTJWVlRqZHZOamhrU1VsQ04wcE9NWEpNTUcwekswbGFUWEpCS3pWMVFVcE9Sa0pMWjJVd1dXeHJOMHRhZFV0alFqbG9ORkpDUldwdk0xQm5SRmt4VW14aWEzRnhTbE4xTTJwc1MzY3lXRTkwWTNsUlJuWmxTRWxtYVdvNWFXZGlSMkYxY2pSaFFUQlpkVUpDVm1WWFJqQkhXR05yWTA5elkxcGxaMDlSY21WS1JtUnJjemRSUjJsYWMxWnRObHBTU21WdFNIWXdVWEJRZW5OaFNrVXpNQ3RDVUN0b1pISm1NVzlaYVZkck9XTjNSelF2UjNoS05tb3hWblJqU2t0UlIzZ3JkRFE5SWl3aWJXRmpJam9pTm1ZeU5qQTNZemcyTVRKbU0yVXdNekE1WmpGak9EUTRPR1EzTVdaalpUSXpNemt4TmpZeVl6Vm1PR05qWmpNNVlqRm1aRGszTnpneVpXWmlOVFk0WXlJc0luUmhaeUk2SWlKOQ==',1794763672),('NY6000TD2DeFxxvISWHyjjh0EHstotM2y9Y0LvfG',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJazVhUzBrMVZHRjBiaXMyUjJWTFUzcHJMMkpSZEdjOVBTSXNJblpoYkhWbElqb2lSR1ZXTmtaeFpHbFZSVVpFWm1kWVJHOXVTa2QyUjJOMFkyRlFkRWxrZVU5c05XVkJRbm92YW05YVNHOXJlbVJEVVd0Qk1IcHdhMEl3Wkc5M2FYbFVWVWxJWVVKWlYyUlphMDkwUzNOcmEyNDJaWEJxZWxWRFRsWnVaRXBYUTIxWVdFVTBkMVZuU1hsQmMweFpTVnBYWWs5MlNXRXpiRzlWTldaaWNYZDBhVVJLZVdwU01sVjRhWFZNZUhaTGJ6WTNWMEZWU0dvM1RWQlRWamx2YzJnd00ybFllVkJ6VTJWRWNXc3ZUVEJ6YlVkb1ZXc3lhemM0YURRekszbGlaR3BGZWpoT2FuSkhkbkk0YWtaaFRGWTRUemR0TUVsVFV6QnRaRVk0WjNsSmEwaFhVRE5RUW5RNGIwZEhNV1pJSzBSR1UwOVFjVnBsUlUxM1ZtbHdWM3BIYUVwTlFXTldXRGhIWVRNMVptYzNTMXBOUlhsaVVUZDZPU3RwZVZaSFNsWjJkWGh5YUc5VVFXY3pVbmM5SWl3aWJXRmpJam9pTnpVeU5USTNOV0k1WlRsa1kyRXlaVEkzWVdVeU1XSXdOREF5WlRVeU1EZ3daVE0wT0dWa05UVmxPR016WkdWaU9XTmxNMlJtWmpJMk56SXpNRFV3TmlJc0luUmhaeUk2SWlKOQ==',1794764593),('PsqWST7SGJ3zyW9R1xBtJSiLDSn0wu7wT8IcAtQC',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJbk52YzJkM2JDdDZNbUZoZWxkRFRXZFhhRzQ0Y0VFOVBTSXNJblpoYkhWbElqb2lZVVV5TkhBd1pXdzBTa1JxYVhwMldVczBjVFoxYVhOWFdrMXdUa0ZPTW1SNk1FVnBRMWhzWlZjeWNFNXhSRmhsTlZOVkszSkxOMUJQUzB0elVWWkNUMFV3UVVVMVIwaHNPVXhUY1hwUVYyTktjM0kyVUZScU9FbHlSbGhVTDFoNlZ6VkJWVTlOV0hGdFVIVmFhbFZpYTBOVVN6UkZRMGhYVW5aRWMzWjRWamgyTHpkSFNXcHlZVE16U0hsYVNWSktSamMyUTJkdVJHTXlabkYxV0d0dE1UaGxOa2R6V1d4blJFdEtaa2xJU0hGYVZrWTVkVE5IT1ZVelEzTXdLM1pWUmpScU1teEVMMFZtWlZac1EyaGhSRzVyV21wU1dFOTRUWGM1VlVwcE9WQTFhbXczU0N0NlFYbFVlRkUwV1ZCRU5EWmxkM3BVZURkdk1HRnRjVnB0Y0c1bGJGcG1aaTlQU1ZaNFRpdEtNRFYzWlhaUlYyWldkRzU0VUV4Sk1XdzNSakl3TmpoRGNqUllia0U5SWl3aWJXRmpJam9pTW1Nd05EbGlaRFZpWVRFMU1HSTBZVGswWW1ZelltTmxNREpoTlRnNVpEY3dOR1F6T1RKbFlUWXhNVFE1TkRnNFlqVm1aak00TXpoak5qRXpZVGM1WVNJc0luUmhaeUk2SWlKOQ==',1794762867),('PTKBetouUBh2OuDr9srKRQAbosfPc25XqRrRNqda',NULL,'127.0.0.1','curl/8.22.0','ZXlKcGRpSTZJamxCZVhSa1RuWjFka1ZDVTJWSFprcExXalZ2WmxFOVBTSXNJblpoYkhWbElqb2lZVzQwTkVGbFkyOHpiMFJzVjA1UlFVbDZObVV5Ym14QldXMW1RV3gzUjNoT1lua3dZV1JsVUZSTGJuSnBaVTByY0VKUFNHWkhWbTlLUm5SaVRUUnRZVkpMYms5QlRURTFiRzFuWjBGUlRUVkVTME5YVkZkcFRVbFlZbXRVY1dkYVYwNTZjRXRKTlUxa1ZXMWFLMjl3Y2taNWMyRnJRMG95VUdSMU1GTXdOMkozV0hsWFdtbHBkbkY1WlhFeWMyZHBZM0ZKVGtvM1dsTklVRzVoTjNkVEsxTTNPR3A0YjI1WVpVdHBZMnRUV1N0bVlWZEllVE5pTUVad0wwSjNTMDlFU1hZNGJIVk5VRFpHT0hVelNrcERiVTlCZWpKS1pERXlSa0paTkM4NGVUSkZPVTFhYW14QlRIcEZXbkYwTDNWVlVIUlJWbVpDYzJ4YVJWWTBRa3MxY21SV2Exa3hRV1EyY0hsSmJIcFFkelV5TTAwNVlXNXJOakJvY2xoRVVHeHRTVzEwWkVwS2FHNUNNemc5SWl3aWJXRmpJam9pT1dZd09XVXhOREptWTJFMk1qRTJNRFJrWW1ZME1HSXdZVFZqWXpCaU56azNNekJtTmpRNE5XWmtZell4WkdNM1pXWTRaamsxTURGak1qSXdOelJtTnlJc0luUmhaeUk2SWlKOQ==',1794766045),('Q4B7ZG6QoJ3niDlcDObgQCTzz0lG4jiIuOQxKZZF',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJbUpOWm1RclZVdHRNbk5PTlhaTlNEVlRTRTFVTDBFOVBTSXNJblpoYkhWbElqb2lTWGx4U1dWS056aGlkV2RVZURab01tRlpiRXRHVERaNVdtSndjRFI1Y2pkR09HZFVUemRsWlZKclJuWm5SRWN3YjI0clZHMURabGhKVlVZNVEwTkxUMDVNTVVRM1lYbDFTbWgwWkROUVlVUkpOR3hZY1hSSE0wcDVTRzVpWTNCbllXSmtVSFJWZUdwbFpUSkRTVkpyU2s5c2VERTBaQ3RQVDJSSVFscE1ia0p4VlhOVFQycHRWa0ZSUTJGUUswMTBSR0pEYkN0VFZrcDZWak13WnpRMVNITjBjVmhuTVRKcmNIRlVkbk5LVkhaMU1YbzNOVWMwZG5sVlIxUnJOa3cxUVZsdU9EaFhObTVzWlV0TFdVSnBWMjl5T1Znd2IyVm5UbU5rVEZsbFREbG1lREpFVkhkSVNtRkJiV1JIZDBGUWRuVTBTMEZMS3pjd05tZDNTeTlJTjJ0NFkzRTBLM1pCZEZwUVQyRkRVMWc1UTBReWRVZDFUVGxLVEZkMlowbHRNSGxGTVRWTGFTdEtZVEE5SWl3aWJXRmpJam9pTXpVeVpUTTFPRE5sWkdFek5XVTNPVGxqTmpFM01UTmlZVE5rT1Rjek9EUXhORFUwWVRrNFlqa3hOMk5sWlRrM05HSXdZVGhpWXpNeVpXSm1PR1UwWlNJc0luUmhaeUk2SWlKOQ==',1794762747),('QfjtuFEakSRWmjQ23mzCokfxvghpDRxLvwmCIZHO',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJamt6V1RabVRIQjNUM1JKWlVjMFVsaEdTRlowTldjOVBTSXNJblpoYkhWbElqb2lRMjltTjBOSVVWbzJlR1ZCY1M5MFJ6QXhiRWM0Y1dScmFscFVUak0xTkc0MlNrcDBlR1JqVWxwTFdYVmhOVlF2WkhwdVlYTlpNVEZNWm5jdk1qWnFZelJ0WVVrNFFYUjJWSFJ0VUZsSmFFNXVTM2hOU0VObE5GbFllRVEwTmxaR2VIb3JkVk5aYUdGV2NTdDVVeTlTYWsxcFdVTXdTMVIxTDBOR1ozTkpiVVpJTWl0bk9ERlpjbUpqY1RSVWVHY3pVRGMxVEdkbFZVZFdkRGMxV1hWd1kxUldOSFZHWXpkUVlVeFRUMjlsVFhoMWFteFdabkJ0Y25CdVVrazFjbWRrVDFCUFRtWXZXWEp0ZW1WaGFUZERTa05ZYzBGbVNGaFZLM042ZVROWGRYZFFTVmx1VldOMFRIZFNSMVZOSzJ0YVlWVlBVRWxNVjNRMk1sVm5iVmswVmtGYVdFSnpUQzlEWlhKYWVsWm9SVTFCWmpZdmJXVlRZVFl3WkdRNFIwaEliRzVCU0cwellpc3JhRkU5SWl3aWJXRmpJam9pTUdKaFl6WmhPV1l5WXpJM04yUmxaVGswWkRCaE5HUmpZemxqWldaak1UTTVPV1V5WlRrek5XWmpZMkpoWm1ZMVl6YzBZMlUyT0RFMVpqYzNNakU0TUNJc0luUmhaeUk2SWlKOQ==',1794765808),('QR0qEiafQGlC1RtEdsSoI7Siorl3mTeaktTBelqy',NULL,'127.0.0.1','curl/8.22.0','ZXlKcGRpSTZJbTFYUkhrNE4zWndhV1V2Wm05SlQyTlJZbGhZUkdjOVBTSXNJblpoYkhWbElqb2laVzQ1TjJvd09FOHlhRkpCVkU1c2NuRXJLMU0yYWsxa2VtWm1ZVXgzTDBaQ1VVMWpSbFZoZGtRNFkxRTFXbXh0YVRSM2FtNHlkbEp4TVhVclRGaERVMVYyYW1wWGVIZGxSVlZuV0haVk1sVnhVVlZWUlVsdmJpOUhXamRwU25kSFpGTjVURTFvV2tOelNURm9Na0pPYlhGRGR6QnNkblJWT1ZnMlZtSTROSEZQUW1SR2RESkhMelp6V1ZsbmFVa3ZOMGR6V2xWV01XRXZhVGxCVDNKdWFVYzJWRXhxV21oQldEWnZURFV2VTBodVJtUlBXbVptWVhCcVlVMHlWMlJPWmtkcVpIWjJRMXBXU0RaWU9HMVVkMDVTWVN0cVZXeFNaa015Vkdob2IzQnZTMkZyYTBNMkx6TmhOVVJOSzBvdllVaEJNMGRzWmtJMWRFY3dNM2xYUTB0MWRYZDVVVmxJZVV0aU1qZHdVVnBHVkRKVlozUm5SR3N5VjNsTVRTc3lhMFF3VWxBeGJXazRlbGs5SWl3aWJXRmpJam9pWTJFNE5qTTNOREpsT0RkbU5tWXpaV1ExWXpGaFlURmhaak01T0Raa05tRmxZMkl3TldObFpEUXpNMk5pT1RkbFlXUTRZelZrTWprNFlXSmxOV0UzTnlJc0luUmhaeUk2SWlKOQ==',1794763188),('r1Iua7ZWnZqyGxCd2KhqBPmdIiUvAWlgtqOMRCn7',NULL,'127.0.0.1','curl/8.22.0','ZXlKcGRpSTZJbEZ2UVRSNmJreDJRMjR5Y1hod1JYQmlkR2hWVG1jOVBTSXNJblpoYkhWbElqb2lVWHBIT1c0ek5IQmxSREI0V0RORE4wWjNNSHBDUWtGQ1dUUlZZMVJoVjNWck0xZ3JVRnBxYmtreFdubzBielJ3UldWWlVVbFRNR05zTTFWUU5WZExaak41U25kb1RuQm5lVzFvTDFCTmR6ZFBlbGgxYkcxaE4xTndjRk5STDJOaGFHTXhUbWg2TlZSamVIaDNibGxKY0habWNWRm1iVzVXUkRWU1JUTmtXVTVHVmk5MVdHdHhWWFpEY0VGS1ZIRkZNRXB6ZGtsU09VdEpiVWxSYmpadGJDdGlWWE0zU25Sd2RVTmtNemhSVjBKU1dFNVFUV1ZJY0U1RlN6Wm1jbkJZY2toMlMyeGxXbkV3ZERoaFNFVlFLMUZCZUhVM1ZYSjBaRFp2T1VsV2EzSTRTMjQxUVNzck9GaEpkbUo0WVdSbWJVMTZTVm80YW1zck1GQllNMHh4ZW05a04xZFJZMjVrU3k5dmJsTmtWMjAwYUdGU2FERm5hRmh1TDJoU2FUWXlTazh6ZWk5VGNTdDFlRms5SWl3aWJXRmpJam9pTkRoaVpUaGhabUUxTVRrMll6WmpOVGczTkdRek9ERm1NakV3TTJOa00yTTNPR1EwTnpGaU4yRXlaVGxqTm1JNE16SmhaRFkxWkdNd01qaGlOekF3TWlJc0luUmhaeUk2SWlKOQ==',1794762747),('rCh0fwdVl52IevRM9ivrUy5eIWt8s0o6Oe4mWukB',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJaTkxVGtGTmVUUlZWMVZUVDJVeFlVUnJOVTV3Vm1jOVBTSXNJblpoYkhWbElqb2llRFZLTjBWQ01VNXhNVFo2VTA0MmRVeGhiemd6U0dvemFqVkJla1ZWV0haRmVqUlBVV1ZqSzIxRVoyVkVlblExWjJsNWIwVnZWWFEzTUhNdlNEWXJWMjlJYVdSNFkzTklkMmxRZFVkSmRFUm9XREExVEV4dVpWRTVhV0Y0TDJWb1VHOXNNa2hISzI5d1N6a3piWEE1Um1keU1sbDNUbUUyVTJsT00xaEJLMnBUVlhGaVRYWlNTVEE0VlN0VmRFODRSa3c0VERNcmFqbDJNbEU1UmtGWmRFWkdObmRpYTJObk5GQXJWbEo0U0dab2RtWTVWV0poWkdoUE9TOUNRbnBxT1UxbGNETmxNMFJJWTFKRFZrOUxiR2huVTNoNWMwRlBZVkYxWkRWd1IxUmFTMVZZVDNodmExSklUbGd6TTBRME1XVlNjRU0xTUc1T1YxSk9aMnhNVGxKQk5UTnRXak5XYmt4d2RWaExZVlJsU25sQ05XMUlRbGRMVFZweWVYSm1VVTloVFdJMFMySXhPRWs5SWl3aWJXRmpJam9pTm1Oa1pETmlOVEEwT0RnM09EVTBZamMzWmpOa01UWTBaVFV5TldFMk1tRTBOVFJtWkdGak1tVmpOemd6Tnprd01qWm1ZemRpTlRVek9URTNNV0UxTUNJc0luUmhaeUk2SWlKOQ==',1794768030),('td5Rhz5jukslZCxKu2cNLXeXzZHrQPak92dp3JhV',NULL,'127.0.0.1','curl/8.22.0','ZXlKcGRpSTZJbFpSUzBVeksxWjVXR1pJZERadmQyVmhiV1p0YTBFOVBTSXNJblpoYkhWbElqb2lNMGhsVjBKM05uQjZPVE5EVUhnd2NIcE9SM2hoYlUxMmVITnNjRWxoUkRoU2FteFNhalZaU0dnelMzQldURWh2V0RocVJqRnRSVmQ1T0dOSWFuQk5SekpWVEdoNFpUZHljVzlaWnpNd1NWbFRZMjV6T1N0emRVUlBVSEZaTDBkdlUxSjBOM2RTTmtzNVpYaENVRzh2YjBaYWFVRldObmhTYW1kT1NrVXZTRE4xWjBGb2VIWXpRa1pKU2xVMlZtdHpaekJvT1ROTlIyRkpTMmRHUTJrd2VHZHpZbGw2V0RSTllqbDVRMGRSV0dnMlVsQkdkSFphUkZsRFRHaFlMMmxNY0ZwbVpUVXdTa3RNTW5WSmFuQnpNbEJTYnpKMmRXaEVkRGd3UlRkNlYydHdOWHBLWlRCT00zbEhZMjR2Vmt4blpUSndWSE5JV0ZGNGVGTkxOblYxZURNNFNVWnRiMGRGV0VobWRpOUtOSFpsSzNOb2NreElNMmxFVG5kWVIyOWhRMHh2V1RWTGFEWjBkazA5SWl3aWJXRmpJam9pTVRkaFptUXpPR1pqWVdKaE9XSXpPV0V5TmpGaE1tVXpPV1V5TURZellUVmlZakk0T0RnMU9UZzNOVFkzTlRNeU56QTNOalU0WkdZek5ETTBaVFpqTWlJc0luUmhaeUk2SWlKOQ==',1794762417),('Tm02DSbgUMGbyzVLLkK69HSrgNXoro6Bwbf0UT58',NULL,'192.168.1.15','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','ZXlKcGRpSTZJbmx6ZW1aNlRGY3JjMWg1UjJGSGVXVkRjVFpJVWtFOVBTSXNJblpoYkhWbElqb2lNRTVuWW1vMlNXVnBUSGM0V0drelQydDBNMkpUY0hOeVlVRm5ZMnh4VmtjeGNFUktVbTFXUWt0R2FsQnZSVlp2U1dWM0szbFZVRVJuYTJwbFNrOU1kaXR4VTBsSVMwTXlPRVpDWjJsaVVIbEtZbmR3U21Sb1dXaHVhekZsZFRRMVNrVkdiemh1WVZCaFlrbHZZeXRuT1ZvemFuSkpSMUp6WTIxbFJUVnhhWFo1WlhWek5tdzBkRzF4UTFaNmR6TTNhM1EwZW1oMEswSXJaV2gzYnpBMFZEVlRkMGhaVmpsSU9GZDRWRXhTUjJ4blJUQlJRVTFwT0dKWmMyVjBWVkIyTUZkSVpHVjVkVlYwTm5Gbk5GQm1ZWFJHVVdJNFpIQXplblpCWWt4b2NWWkxTbmRNUzFwa1NtcE9lV3B3T0hoTlZWWk1WblE0WjFCcFJrOXFTRXB4VEU1dk5IZHJWazV4UW5kcFZuSlVWMmRHWTA1aFduWmlWSEExUVhvME9XUndhRXN3VlM4cmNWSXZObFU5SWl3aWJXRmpJam9pTkdVelpUZ3lPV1ZrTnpZNFlqYzJaV1l4TVdVek9XTTBaamxrWW1VME1HVXpObVE1TnpVNU1qY3pZMlkyTTJFell6UTBOVEJsTmpVeFltVmhaR0ZrTnlJc0luUmhaeUk2SWlKOQ==',1794768682),('UARVV3KRbZDdBMm1IVIDCwEVK2if92ZP00h59ACy',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJbGRMUjFWSGNYTXlhbFlyYlhaUU9VNDNZM0EzSzFFOVBTSXNJblpoYkhWbElqb2lSamMyTUZCdlQxcEphbmRDYVZCVlNWUnFVelJ2T1daNFNrdENUSEpSVWtGclYxbENjQzk1ZVdKRllYRnFhM1F3TVZjeVdqVTFPVGt4U2pBd2J6Rk9UV0ZDZURJelNsZHVjVGR4ZUdrd1dYQjNhRlZPWVhveFJUY3JSMmxWZUVGTWNsRnRlamh5YlRsVmRUVmhkRGxTY1hWYVFTODJNVkZSWlc0cldWVlVaM1ZpSzFwbGFsUmplRzlZWkhvME5rWXZSRVpXUVhjNE4wdGlNSFpRT1ZseFJIWnJVVUZ0YUdjNGF6ZHNNWE5DVVhad2RsWXpiV0ZtVUhWWWJVbHJUbmRrZWxOcmR6WlhRMUF4UlhkTldTdDVMMjFWZVdOVVMzZzFhVk5HZEVabGRUVTJNa2xuWTBWb1MzcGFUVEZNU2xwUFQyMXpOemRTZWtRM1R6RXdUV2R2WVZRck1VTlhWRzFEZDFSbGFHTlFOMXBVY20xMmFsaGFWVEpSZDJSWVVWaE1hWEJRYVZkdFRVeHhjamc5SWl3aWJXRmpJam9pTWpFNU56Y3lNbVEyTm1KbU5UTXpZVEl6T0RRd05tSTFaRGRpWlRobVptSmlPVFUzWXpreFlUQmlaV05tTnpOaE5UVXdObVkwTWpreE1UTmlaamd4TmlJc0luUmhaeUk2SWlKOQ==',1794766216),('UnRSPEz8XxI2zYgszTV2hioMQMCUbE3ReuJnOrY3',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJbmh4VHpWQ1dVbG1ObUZFY1ZnckwyZFRibEJMYVVFOVBTSXNJblpoYkhWbElqb2lTWEJQUTJJMlJGWlZjM2d3VmxJcloxbDNkVUV4ZWk5SVNVWjRhMkZ5WWpCV1FrcFZaVkZaZW1KSVZFTXJORzAwSzFaVFNYTm9NR1JRV0ZOM1NrZEZVR1F5U0U0clZFaDBhWFpSYld0V1QzWnVVRlJ0WXl0c2JXUllUMFpDUVdwTWNWUlFOM2xwVGxOa1dYaHJTMjFqUmxoNWF6UlROM0J1VWpZeGEwSnVSSE5HY0hGTWVFSndRU3N5Ympkbk1tZHRRa2hPWXl0NWVucGFkSE5WYldoNGNISklLM2hxY1V4R2NFNDNkMDVFVkdwNE56Y3JZa05YVjJGWVdGTjNZVVUzYUROWFNESkVXakp4WmtGM1dHTk5RVGNyTm1JNGQzbFhjV3hIVmpsWGVIb3ZMMFpCU1hCd2RYSkpPRlJ6YlhSR1JTdEVNMDRyTW5weE4yWTNUMVY2Y214d2VqVXliR0owYkVZMlJuVlVOVk5QTkRCdFQzQkJlSGx1WmpsV2JrYzFPSGQ1ZW1aSGN6VjRkamc5SWl3aWJXRmpJam9pTnpObU9XTTVabVpsTkdVMU5tSmtOekkyT0RabE1tSmtPR1l3TVdGa04yRmtZekZqT0Rsak1XSXhObU5rWXpNeE5UUTRNalUxWkdVNU1EaGlaalUxTkNJc0luUmhaeUk2SWlKOQ==',1794768117),('VJXu7Hss3sgefBC4cs1KumJGAqJFBGZqfL6P9lNG',NULL,'192.168.1.15','curl/8.22.0','ZXlKcGRpSTZJbGN6VGpseUwxTnpXVmRRY1VONU9GRnROVXcwVlZFOVBTSXNJblpoYkhWbElqb2lVamQ2YlVjM1NHNU1SV3BSZFZOa1ZIZG5iVko0ZGtrcksybHdja2Q0YmpCVU9UTlFTbGxWYkRsd1VWWllSekpTVFZkNWNIbE1jM05YWmxkaVJUWXdXVGxRYUVaRlEzUnVVSEZCVmxWaFYwWXdia1l6ZUVkWk5VWjFkMGhFTVZsU05Wa3lVVGxaZFhKbk1UUlZPWEJvZG1WUVJWTkVUWGQ2UXk5eU1qSkVMMHhRYjJsa1ptcG1PRWRuTVVOamFHcFhTazQyVUdsTWNUZ3diaXRoZEN0blMybHNPVk5FWVU5TWNUWkpWV0phVm5rMlJXZFpTM0p6V2t4RVdHWjVNRXhTTlcxNVdsWm9RV2QwWkcxdU5IYzVjamxyWlhCV1lYTnVSbUk1WTJaQ2JYZDRTWEEwTVZCRU4wTnZkVVl6WW1wbWMxWjJXbGR6TUhsVWJYaENUa2xsWmtKelNpczFjSEl6ZDJWYWRqTmFSRVpJVW1oTWJWQkZhR2xhTkU1MVREVXpNVE5HYjJSRmQzWnhVM005SWl3aWJXRmpJam9pWVdOaE5XTm1ZalF5TWpRNU1UUmhPV1k0T1RZeU5qTTNOVGxoTVRkbU9UWmlPV00xTVRjd09URXpOamswTjJReVlXSXdPVFZrWldGbE16QTFZMk0yTlNJc0luUmhaeUk2SWlKOQ==',1794769566),('y3wEH2RgHu399KlVcsBRFPfz3kNgk5RZ6Po8C1yF',NULL,'127.0.0.1','curl/8.22.0','ZXlKcGRpSTZJazVQUnk5MWNFNUNVRlZ1ZG1WV1QxTllhVGhVZFdjOVBTSXNJblpoYkhWbElqb2lhbU5sVUhGb1dtZFBWbmhoZG5nMmVsWjNSRGhIWm5wT1VuVjJlakJ4WmprMVJIUktkWHA0Y3pOSWJHbFFSazlUTlUxSU9FeDVhbVZTTDJ0alJqZFdiMjFMVEZWcVNrVlNTamxXVld3emJEWkVWMjl1Ums4dkwwSnZZM3A1Y1RkMWNreHNhbmt2Ukd4M2FVRnlNR2s0VEdGcVdFRkRZMnhPVkdORE9FUklSbXB2Vms5VlJtczVhRnA1UjBSM01WVlBZazlsS3paU05WbGhUSHB6UmtWNlNsWlFXa05KU25Rd2N6ZEZabTlpZUhneFRrVnBXbHBJTmtvM1dHdHpaWFJNU3pSVlIwcFBOM1pQUWtJeFNUSnRNR1JyZUcxMFR6aGFja3RNUTFwSlNTOWFkeTl2VTBacWIwVkRZbGt4YWs5ek5tNUVOMGxXYW01V01tNHdPVU5hTTBWMVVISnVORnByUzFwM00zaFFTSEpUWkU5TFJXTnBlSEF3S3pKR1JUbDZXVXB5ZGt0dlJUbFVhR2M5SWl3aWJXRmpJam9pTnpVM00yWmpZemRpWldFNE9ETmtPRFJrT1RrMU4yRm1ZVEJrTmpZd00yVTNNR0U0WlRJMlptUTFPR1UzTkdNNU9URmlPREk1TkROaE5EVTROR05pWXlJc0luUmhaeUk2SWlKOQ==',1794765808),('zEu0QXPk0kE5ZAGP204AZSPXdDDC08gHplgCwsJr',NULL,'127.0.0.1','curl/8.22.0','ZXlKcGRpSTZJbGRSYm1zd1YweFNhR281Vm1OS1Z6UTRTM2RCV0hjOVBTSXNJblpoYkhWbElqb2libU5aTVRGek5HMVZjME4wYXpoMU16TTBVRU1yV1VGcE5VVTBhMVpUV1hVemEyaHpTRUZFTDFWeFNDdHNUbFZ1WVdsRGVVMVFObEZwTjBzeUswWnRTMVJ4ZEROek9IZzRkRmxWTmxwT2JXaHZPV3MxV1RSWFdUQk1VbGwzTlc0NVZrOXlkV3QwYzB0TlNDOXVaRFpEZDBaaVFUTk5TMDgwVUVsYVZGTmtUbFZVU3k4NFdGWkhjVFU1ZGpCWmEwOUJWekZ2WVdKbVQxazBSMGs0WjFrMlRtUkNhVEZyUVcxVWMwY3Zla2xxSzFsTVRrRm9aeTlEVGprclFrdEhWamc1WkVKYWR6ZEVTell5VVhOdWNWZ3dkRXBJYTJjckx6UXJVbVZIUVdOcVVrTmxlbU54WmxwTGFVUkhNVVkyYmxGd2FuSlZVV1owVTJsR2Vpc3ZhVEJ4UlhVMlptZzFWMnRMVFV4bFJXRjJXVWRvTkhkRU1HaDJjVnB3VWtkRk1HOWtTa3hIVkhOVVFucDVjMjg5SWl3aWJXRmpJam9pWkdOaE9EUmhNVEV6TkdVMFlqWm1ZMkpqWkRabE9EZGtZekJtTXpVMU5qRXdOV0kxWlRWaE5USmxORGRpWlRFelpUWTBZalUzWlRJek1ERXpOemcyWmlJc0luUmhaeUk2SWlKOQ==',1794766216);
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_sessions`
--

LOCK TABLES `shift_sessions` WRITE;
/*!40000 ALTER TABLE `shift_sessions` DISABLE KEYS */;
INSERT INTO `shift_sessions` VALUES (1,1,1,'2026-11-14 10:22:46','2026-11-14 10:25:44','Attendance — clocked in at sign-in','Attendance — clocked out at sign-out',0,0,'closed',NULL,'2026-11-14 10:22:46','2026-11-14 10:25:44'),(2,1,1,'2026-11-14 10:58:54','2026-11-14 10:59:05','Attendance — clocked in at sign-in','Attendance — clocked out at sign-out',0,0,'closed',NULL,'2026-11-14 10:58:54','2026-11-14 10:59:05'),(3,1,1,'2026-11-14 10:59:40','2026-11-15 11:02:37','Attendance — clocked in at sign-in',NULL,2,200,'force_closed','Left open overnight from 14 Nov; terminal unattended - supervisor forced close','2026-11-14 10:59:40','2026-11-15 11:02:37'),(5,5,5,'2026-11-15 09:45:08','2026-11-15 09:45:36','Attendance — clocked in at sign-in','Attendance — clocked out at sign-out',0,0,'closed',NULL,'2026-11-15 09:45:08','2026-11-15 09:45:36'),(6,5,5,'2026-11-15 09:51:03','2026-11-15 09:53:59','Attendance — clocked in at sign-in','Attendance — clocked out at sign-out',0,0,'closed',NULL,'2026-11-15 09:51:03','2026-11-15 09:53:59'),(7,5,5,'2026-11-15 09:55:18','2026-11-15 11:02:10','Attendance — clocked in at sign-in','Attendance — clocked out at sign-out',8,4700,'closed',NULL,'2026-11-15 09:55:18','2026-11-15 11:02:10'),(8,5,5,'2026-11-15 11:10:15',NULL,'Attendance — clocked in at sign-in',NULL,0,0,'open',NULL,'2026-11-15 11:10:15','2026-11-15 11:10:15');
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teller_profiles`
--

LOCK TABLES `teller_profiles` WRITE;
/*!40000 ALTER TABLE `teller_profiles` DISABLE KEYS */;
INSERT INTO `teller_profiles` VALUES (1,4,'POS 001',1,1,'active','2026-11-11 00:02:42','2026-11-11 00:02:42'),(2,5,'POS 002',1,2,'active','2026-11-11 00:02:42','2026-11-11 00:02:42'),(3,6,'POS 003',2,3,'active','2026-11-11 00:02:42','2026-11-11 00:02:42'),(5,10,'POS DEMO',3,5,'active','2026-11-15 09:43:11','2026-11-15 09:44:49');
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'System Supervisor','supervisor',NULL,'$2y$12$Kr8s/wS95aPMOP442ccyHOs9FdV6augkpBbHAaAFnyfCzMFGjIk9.','supervisor','active','2026-11-14 11:25:47',NULL,NULL,'2026-11-11 00:02:41','2026-11-14 12:35:07'),(2,'Marco Reyes','gm.north',NULL,'$2y$12$yPHmRrgPZL3tXSsSASaoNOR4VUN6LYCljYxaetztGZFpQJNvOcJZS','game_master','active','2026-11-14 11:24:38',1,NULL,'2026-11-11 00:02:41','2026-11-14 12:35:08'),(3,'Liza Cruz','gm.south',NULL,'$2y$12$DhyMvzo9.kYDDRgGJpInnehDajVYa.sOLQWnsKYLFXBXMF.yNLwpe','game_master','active',NULL,1,NULL,'2026-11-11 00:02:41','2026-11-14 12:35:08'),(4,'Ana Dela Cruz','pos01',NULL,'$2y$12$4Hgrs4lokHYdwNrRcT5wt.YWtz9DZQvfWUdPDvBF.v5lHqNfUyRNi','teller','active','2026-11-14 11:30:12',1,NULL,'2026-11-11 00:02:42','2026-11-14 12:35:09'),(5,'Ben Santos','pos02',NULL,'$2y$12$lsrCd9hOozQ5P6/JxiMKvev0ij5GUMJ2W1AqweXaWOzVLPWI1El8y','teller','active','2026-11-13 03:02:14',1,NULL,'2026-11-11 00:02:42','2026-11-14 12:35:09'),(6,'Cora Lim','pos03',NULL,'$2y$12$dSrff2yi.pxfJXVyfJqwq.PzSUXp16RG/l/VzuGQVx8WsMsPkXSEq','teller','active',NULL,1,NULL,'2026-11-11 00:02:42','2026-11-14 12:35:10'),(8,'Demo Supervisor','demo.supervisor',NULL,'$2y$12$J5gUunHL495h0ezcY80HE.SKzn2oeTUpXo7t5lEGIiJquiL9ejxLm','supervisor','active','2026-11-15 11:02:19',1,NULL,'2026-11-15 09:42:54','2026-11-15 11:02:19'),(9,'Demo Game Master','demo.gm',NULL,'$2y$12$rh/QYCQ1jB/EHElKqUOClO1ElpVxdRnMvy3lQzGA8cusMygc5e2EO','game_master','active','2026-11-15 09:54:04',1,NULL,'2026-11-15 09:42:55','2026-11-15 09:54:04'),(10,'Demo Teller','demo.teller',NULL,'$2y$12$5VrhFPfs8xbLNFwHI0wl2.zhCUhdHlVuAzChw7V53lGtK9U3Qmkty','teller','active','2026-11-15 11:10:15',1,NULL,'2026-11-15 09:42:55','2026-11-15 11:10:15');
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `virtual_wallets`
--

LOCK TABLES `virtual_wallets` WRITE;
/*!40000 ALTER TABLE `virtual_wallets` DISABLE KEYS */;
INSERT INTO `virtual_wallets` VALUES (1,1,0,0,'2026-11-11 00:02:41','2026-11-14 10:17:39'),(2,4,800,11,'2026-11-11 00:02:42','2026-11-14 11:11:16'),(3,5,0,9,'2026-11-11 00:02:42','2026-11-14 10:17:39'),(4,6,0,2,'2026-11-11 00:02:42','2026-11-14 10:17:39'),(6,8,0,0,'2026-11-15 09:42:54','2026-11-15 09:42:54'),(7,9,0,0,'2026-11-15 09:42:55','2026-11-15 09:42:55'),(8,10,150340,18,'2026-11-15 09:42:55','2026-11-15 10:29:27');
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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallet_transactions`
--

LOCK TABLES `wallet_transactions` WRITE;
/*!40000 ALTER TABLE `wallet_transactions` DISABLE KEYS */;
INSERT INTO `wallet_transactions` VALUES (1,2,4,NULL,NULL,'seed','credit',1000,1000,'TXN-261114-XLGBEQVR',NULL,1,'Low-till top-up from dashboard','2026-11-14 10:59:23'),(2,2,4,1,2,'bet_hold','debit',100,900,'TXN-261114-AZ7BTUGR',NULL,4,'Bet hold — Fight #1 — Meron vs Wala','2026-11-14 10:59:46'),(3,2,4,1,3,'bet_hold','debit',100,800,'TXN-261114-NALVYDZW',NULL,4,'Bet hold — Fight #1 — Meron vs Wala','2026-11-14 11:11:16'),(4,8,10,NULL,NULL,'seed','credit',150000,150000,'TXN-261115-QOR79MNE',NULL,1,'Demo till funding','2026-11-15 09:43:11'),(5,8,10,3,4,'bet_hold','debit',500,149500,'TXN-261115-5MDIJDYR','rehearsal-hold-6af9f99a37251:hold',10,'Bet hold — Fight #2 — Meron vs Wala','2026-11-15 10:27:38'),(6,8,10,3,4,'void_refund','credit',500,150000,'TXN-261115-WZFE2QEY',NULL,9,'Fight voided — Rehearsal cleanup of an earlier run','2026-11-15 10:28:22'),(7,8,10,4,5,'bet_hold','debit',500,149500,'TXN-261115-OKNQ2UFQ','rehearsal-hold-6af9f9c6af044:hold',10,'Bet hold — Fight #3 — Meron vs Wala','2026-11-15 10:28:22'),(8,8,10,4,5,'void_refund','credit',500,150000,'TXN-261115-9ILNV5HF',NULL,10,'Bet voided — Rehearsal: wrong side entered','2026-11-15 10:28:22'),(9,8,10,4,6,'bet_hold','debit',800,149200,'TXN-261115-QQJKHNXV','rehearsal-payout-6af9f9c6c4fc8:hold',10,'Bet hold — Fight #3 — Meron vs Wala','2026-11-15 10:28:22'),(10,8,10,4,6,'void_refund','credit',800,150000,'TXN-261115-38S1G1CF',NULL,9,'Fight voided — Rehearsal cleanup of an earlier run','2026-11-15 10:28:46'),(11,8,10,5,7,'bet_hold','debit',500,149500,'TXN-261115-G132ZL7W','rehearsal-hold-6af9f9de64cf6:hold',10,'Bet hold — Fight #4 — Meron vs Wala','2026-11-15 10:28:46'),(12,8,10,5,7,'void_refund','credit',500,150000,'TXN-261115-WXFUPAAY',NULL,10,'Bet voided — Rehearsal: wrong side entered','2026-11-15 10:28:46'),(13,8,10,5,8,'bet_hold','debit',800,149200,'TXN-261115-8SCYF38C','rehearsal-payout-6af9f9de6b0c1:hold',10,'Bet hold — Fight #4 — Meron vs Wala','2026-11-15 10:28:46'),(14,8,10,5,8,'payout','credit',1120,150320,'TXN-261115-TEK3TSZU',NULL,9,'Payout — Fight #4 — Meron vs Wala','2026-11-15 10:28:46'),(15,8,10,6,9,'bet_hold','debit',500,149820,'TXN-261115-UT6MJ2DP','rehearsal-hold-6af9fa072e0f3:hold',10,'Bet hold — Fight #5 — Meron vs Wala','2026-11-15 10:29:27'),(16,8,10,6,9,'void_refund','credit',500,150320,'TXN-261115-KRDIMSAP',NULL,10,'Bet voided — Rehearsal: wrong side entered','2026-11-15 10:29:27'),(17,8,10,6,10,'bet_hold','debit',800,149520,'TXN-261115-6YOAVWSY','rehearsal-payout-6af9fa0734567:hold',10,'Bet hold — Fight #5 — Meron vs Wala','2026-11-15 10:29:27'),(18,8,10,6,10,'payout','credit',1120,150640,'TXN-261115-SAHZC0FK',NULL,9,'Payout — Fight #5 — Meron vs Wala','2026-11-15 10:29:27'),(19,8,10,7,11,'bet_hold','debit',300,150340,'TXN-261115-QPL2T0U1','rehearsal-regrade-6af9fa073d0e0:hold',10,'Bet hold — Fight #6 — Meron vs Wala','2026-11-15 10:29:27'),(20,8,10,7,11,'payout','credit',420,150760,'TXN-261115-8GKJA4ZQ',NULL,9,'Payout — Fight #6 — Meron vs Wala','2026-11-15 10:29:27'),(21,8,10,7,11,'adjustment','debit',420,150340,'TXN-261115-IORTJJLE',NULL,9,'Result regraded from MERON — payout reversed on Fight #6 — Meron vs Wala','2026-11-15 10:29:27');
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workstations`
--

LOCK TABLES `workstations` WRITE;
/*!40000 ALTER TABLE `workstations` DISABLE KEYS */;
INSERT INTO `workstations` VALUES (1,'POS-001','Counter 1 — North','e9ad4e8b-6f7c-4424-96f2-63e06cfea5ac',1,'2026-11-14 11:30:11','2026-11-14 11:30:12','2026-11-11 00:02:41','2026-11-14 11:30:12'),(2,'POS-002','Counter 2 — North','pos-north-2',2,'2026-11-11 00:02:42','2026-11-13 03:02:14','2026-11-11 00:02:42','2026-11-13 03:02:14'),(3,'POS-003','Counter 1 — South','pos-south-1',3,'2026-11-11 00:02:42','2026-11-11 00:02:42','2026-11-11 00:02:42','2026-11-11 00:02:42'),(5,'POS-DEMO','Demo terminal (bind from the POS browser)','b177f314-39fd-45ce-a9ca-2bd82fe1b55e',5,'2026-11-15 09:44:49','2026-11-15 11:10:15','2026-11-15 09:43:11','2026-11-15 11:10:15');
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

-- Dump completed on 2026-11-16  7:45:00
