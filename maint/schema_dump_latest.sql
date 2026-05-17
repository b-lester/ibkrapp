mysqldump: [Warning] Using a password on the command line interface can be insecure.
-- MySQL dump 10.13  Distrib 8.0.42, for Linux (x86_64)
--
-- Host: production.bretlester.com    Database: mymarketdata
-- ------------------------------------------------------
-- Server version	8.0.41-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
mysqldump: Error: 'Access denied; you need (at least one of) the PROCESS privilege(s) for this operation' when trying to dump tablespaces

--
-- Table structure for table `marketdata_history_bars`
--

DROP TABLE IF EXISTS `marketdata_history_bars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `marketdata_history_bars` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cache_key` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conid` bigint unsigned NOT NULL,
  `symbol` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sec_type` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'STK',
  `exchange` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exchange_key` varchar(32) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (coalesce(`exchange`,_utf8mb4'')) STORED,
  `period_value` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bar_value` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `outside_rth` tinyint(1) NOT NULL DEFAULT '0',
  `source_value` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Trades',
  `bar_time` bigint unsigned NOT NULL,
  `bar_time_iso` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `open_price` decimal(20,8) DEFAULT NULL,
  `high_price` decimal(20,8) DEFAULT NULL,
  `low_price` decimal(20,8) DEFAULT NULL,
  `close_price` decimal(20,8) DEFAULT NULL,
  `volume` decimal(24,8) DEFAULT NULL,
  `fetched_at` int unsigned NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `marketdata_history_bar_unique` (`cache_key`,`bar_time`),
  UNIQUE KEY `marketdata_history_bar_identity_unique` (`conid`,`sec_type`,`exchange_key`,`bar_value`,`outside_rth`,`source_value`,`bar_time`),
  KEY `marketdata_history_lookup` (`conid`,`period_value`,`bar_value`,`outside_rth`,`source_value`),
  KEY `marketdata_history_bar_time` (`conid`,`bar_time`),
  KEY `marketdata_history_fetched_at` (`fetched_at`),
  KEY `marketdata_history_bar_identity_lookup` (`conid`,`sec_type`,`exchange_key`,`bar_value`,`outside_rth`,`source_value`,`bar_time`,`fetched_at`)
) ENGINE=InnoDB AUTO_INCREMENT=14222 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'mymarketdata'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-17 11:51:02
