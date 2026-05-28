-- MySQL dump 10.13  Distrib 8.0.22, for Win64 (x86_64)
--
-- Host: localhost    Database: garitademo
-- ------------------------------------------------------
-- Server version	8.0.22

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `config` (
  `id` int NOT NULL,
  `path_brand` varchar(200) DEFAULT NULL,
  `path_logo` varchar(200) DEFAULT NULL,
  `enable_fotolicencia` tinyint(1) DEFAULT '0',
  `enable_fotovisitante` tinyint(1) DEFAULT '0',
  `enable_webcam` tinyint(1) DEFAULT '0',
  `enable_accesotel` tinyint(1) DEFAULT '0',
  `enable_tel` tinyint(1) DEFAULT '0',
  `enable_egreso` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `path_config` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config`
--

LOCK TABLES `config` WRITE;
/*!40000 ALTER TABLE `config` DISABLE KEYS */;
INSERT INTO `config` VALUES (1,'1629304004logo.png','1629304004mardysa.png',1,1,1,1,1,1,NULL,'2021-08-18 10:26:44',NULL);
/*!40000 ALTER TABLE `config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `accion` varchar(200) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs`
--

LOCK TABLES `logs` WRITE;
/*!40000 ALTER TABLE `logs` DISABLE KEYS */;
INSERT INTO `logs` VALUES (1,1,'Realizo Cambios en la Configuracion de la Pagina ','2021-08-18 10:26:44','2021-08-18 10:26:44'),(2,1,'Edito al Usuario administrador','2021-09-16 09:43:34','2021-09-16 09:43:34');
/*!40000 ALTER TABLE `logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
-- Table structure for table `residentes`
--

DROP TABLE IF EXISTS `residentes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `residentes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Codigo` varchar(20) NOT NULL,
  `Nombres` varchar(50) NOT NULL,
  `Nombres2` varchar(100) DEFAULT NULL,
  `Apellidos` varchar(50) NOT NULL,
  `Apellidos2` varchar(45) DEFAULT NULL,
  `accesotel` varchar(12) DEFAULT NULL,
  `Direccion` varchar(100) DEFAULT NULL,
  `Telefono` varchar(8) DEFAULT NULL,
  `estado` tinyint DEFAULT '1',
  `tipoResidente` tinyint DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Codigo_UNIQUE` (`Codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `residentes`
--

LOCK TABLES `residentes` WRITE;
/*!40000 ALTER TABLE `residentes` DISABLE KEYS */;
/*!40000 ALTER TABLE `residentes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` int DEFAULT '1',
  `is_admin` tinyint DEFAULT '0',
  `is_superuser` tinyint DEFAULT '0',
  `tipoResidente` tinyint DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','administrador','admin@admin.com',NULL,'$2y$10$LVUb4Rsx/yRU9pnaNRdckuOOmMBjzXB5521CmATauBDy2wa1MCTcG','UM8NCFzMyfKnzOdjvPVGmffI7Dkf7sn7Tx0kPlsyNZWxQelfO0PA338cUcqk',NULL,'2021-09-16 15:43:34',1,1,1,1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `validation`
--

DROP TABLE IF EXISTS `validation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `validation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `token` varchar(100) DEFAULT NULL,
  `pass` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `validation`
--

LOCK TABLES `validation` WRITE;
/*!40000 ALTER TABLE `validation` DISABLE KEYS */;
INSERT INTO `validation` VALUES (1,'9jZuVzDKBtWOKUPwyAYp','$2y$10$BsG3N.vqAlhig/UcdeselOICtHLt9Eue1dh0B9qU9Q/VdGAcV36HC'),(2,'jrUNgEhXZULES0eSI27X','$2y$10$Ujv59npsdS6K0XUWurCvp.JL2C6Z4JJsBZ./xbeLirbgtRbmBHrzK'),(3,'4CNHhHqirXkXIhodvcAh','$2y$10$UCKdy5Qot9M3AalTs37LCuDHI9xgWPwNo.ppfnFFK7jdZAcND/Ufm'),(4,'0tFNWfW8lVvdU1Ujhu5Y','$2y$10$q0bR6qKIGn1q/ENo4ywCDOhQ6RpW3.E3QDEFYjxProTd2aE7HRbQK'),(5,'gZFOADrUhb9OMfsPz2Sn','$2y$10$sFOTmqSAIRvfoT0RIdY/zOXKbDXLmb1/Vf3l51wSBOr/C7FkwofuW'),(6,'Y5fAvmy7OeOKyZQLGFqG','$2y$10$Tes4ZuH.zG5TEFKJX1KgWuVAhrNEUdFFiNXoRHMi6GJYoIIutA..e'),(7,'Xzf95fDal0D4v6tXwJAf','$2y$10$Q/nuP3saCSWYpXbeczX0W.vkLEt.A6vGZBzQQp8tXK497hGQlpf3S'),(8,'uP8PIHSi0kLusnxPNWLT','$2y$10$IdSFGKcsk8/VOgfiZEg.6O5/bqVSx2eWYksz5g7ets8kEeVMKUJHm'),(9,'FxA5NAc7Pd50b8rttv98','$2y$10$bSGiJaEypLLXbSYvJ1.2.OpGfN2eyWITtHzcahTBimQocN3yq7Cyy'),(10,'tjB0MuIRHZfpOt0PVE9g','$2y$10$kxe8or58voQi8lWN9V0OH.JF5v3KrFLrqC56dhL3N5.ox.rXxVOES'),(11,'ib7QzIGNyum13P9p1W0h','$2y$10$csWoaHZDYPjSFrR7Top71eUtYGXzsxpf58BjFpc5hyX3S4Z/4eeCC'),(12,'CWkw7d6qbHhGNGdW4tvX','$2y$10$GJqVD79cmrC9QxZLTn3aJOHlrEopzWoct7DvyeMeYbTmJMOS/nIzC'),(13,'peEnWijgFntDA74CQQXA','$2y$10$mbbs49s5GIJUwccpNs1TguYhKdlNlWr1Fe/BY8cAmYO8SvMh4GxN2'),(14,'oWcsuOdtZxfIsXAH3HZ1','$2y$10$wSEBLZMNe0ef1NXuIDX0XOp2wHGLmyEJwBRzd2cYF10ojlTszQ0ny'),(15,'txpGjE5dCZ5iOasvOby3','$2y$10$XuQbUL5RQYkcZUmbZJukiO34mt8tAjmrfW7tiggkHOc5Fqjl7WyR2'),(16,'SpRsjgK39lQ59UlxUA4T','$2y$10$XgItfaCXuBD/4s9czV0ssuA5gN1lNCmwnMRlYmJ.ZigNGvHx29HS6'),(17,'nFv8HuMQyMVUFMfrwOiB','$2y$10$WYODr8/xhlrq4pFlIwsPS.cvZPL4rMvCnBZKjYcDPP1iJSpP86yqO'),(18,'lt3CwaooNSf1TBtKvTXk','$2y$10$.2EvkYp/0MHhpNxkk36ubO0schrWrtWACMTGgIn/rd6hcmLEwHjve'),(19,'vWbwdw5lDlCY7ivqgVEr','$2y$10$lgNISXOhFh3FhwZoecp/r.2ykqFn0fA33Q8e7QCMWFBYRi1CVZZW6'),(20,'f6HoNcZs4gJrl5GbLPAp','$2y$10$.hkw7/DHAIID6K9iBS3WNe99SfaBlZBRpfzUw6MXKG2W5kf5lHhpa'),(21,'gdIjmMRxGhIrkAiH8T1M','$2y$10$BFADEqMWab3tKJKtd60Af.OygsWfNN.BTfUrDskkv8x7rrpzOuRkq'),(22,'oTxLca8Y8MlO9MOCVWYS','$2y$10$Q1oo1Lv083Ler/Am8nasCeb4te.LXtmHStwV4xHP9b9Ois9WdTspW'),(23,'YrJjE1onrgImkh81w95r','$2y$10$kKKlaW1/sRhTGkmDJT.3Aec4HfwqLEbSe/LquDdby3yAKml.iLr/u'),(24,'HQCR063c0y52SecT0F1N','$2y$10$ZVqCBJXFL6.sEHf/f2prEuqX4BXTdEEndBVDLOCB71BMI7nM5c1Ha'),(25,'YjvhQwzGKktuNpG5OUk6','$2y$10$Z7PmOOlxOzESMI7hBvadIuUj9cjdjzFzeQc0TsU4C448k.FhyfolO'),(26,'4As4P0hoYXYa6BBxzrID','$2y$10$KpEi.kPx3y7hQ3iu9zE/p.BNtENodVExQJOmIMTzXg6AZsNone72G'),(27,'aFHPnMYeIiVHONx8Skj8','$2y$10$AlBTPjZ0fuwdpqYShEYmd.twKUTcsZDdaXjz7xe5zTDn48sfdmlz2'),(28,'CgbvSpf4NN9DLlZlX9eM','$2y$10$7jvsGYni0nooUBJg4FAv6OzSd.gpHgaku1A3YwHJIFPZlEkd.h2fi'),(29,'eU4bQp0IOZKgaSNIKclu','$2y$10$X1zwgU/8eApKGBPy92JILOIuyp1b7Xv9sBPZ7b7tKs825TN1ekuqC'),(30,'jxvCp3SJmy1URR1VACQb','$2y$10$M/WhHQaoyD08iWjHS4wKCOrVHrvTqgkAjz9evqrcF6tekB4GhNTmS'),(31,'WQSrLKeMOWoi344u4wZ9','$2y$10$kFZLmfVy9pzw6qN9fXQTnec.FXIVwmRhmRngiD2rjxW9J9.Pzu2ou'),(32,'p2YrkFDpdvPnKXsmWQgr','$2y$10$qhmPu0c0UQY7iiM0Wfca2eq4Gp.sy5E5qPwZ4hxTUMx2jWUyUDDby'),(33,'EEnkH4iNVWirG9OE9kvt','$2y$10$/oxWD846r8HcrCMx2LQVhuogcE5mxrunvyjsiXg2gBGtCrUfGlVCW'),(34,'7XhyuAjx8RpzMQkWoSpW','$2y$10$zMCMltQho1b3hd1rOtz7meHY/6J1v/H6u0283jqAug4g8SPcvmhY2'),(35,'zRqCBgM3cef6nCUO1Ypr','$2y$10$LtZ1fHBZ5stf8NWM9b4U7uCA/FviYghMaUQmd9UvQzsnngOm5Wtnq'),(36,'VgOWzsF2O5s8dqbmER4o','$2y$10$uDkEuLKkYKnzynmtOFirXu7UkYjDK9Y.7tAm42Rt9UKOyvaMFlWXG'),(37,'vl18kpmWZpcAgG5Z6Sun','$2y$10$HXh2u4F0qVXuoRMoAJ31pO2BOAUcNysrE53E5zHvYJ7yMCSTEikHy'),(38,'9AtYQN277sOxC7acVIoE','$2y$10$QMD1K/X55CuFfjoJP/uYh.pDud.HLav4cv8DXNMBQ1nOxQEcY.TD2'),(39,'CDnr4UQzISQvGkpjasHi','$2y$10$xn4RzXUDmsbkDkRrRIPsVeTjhi0b71Qs6czgIrFl0XmFlOPGC2B9K'),(40,'G0wTp5xVUJFxlv3mFfoh','$2y$10$W2M0fXe79Jc/9RL7Zvy2l.sxX94PPKLrPQ2Jq41sCaca6HneVLr9G'),(41,'ixLkzMBjHct9dXe9YMtW','$2y$10$o0465gNXiuZ038oNp4vi/eA/4i7Jntl6dhNxk21uqv3DYuAk/xZDO'),(42,'6MiEfCwOp2TM5uux9Ydr','$2y$10$ysps.ZQiiOemeVfumH5dAewHOpSbuwIOIz1Mh46wSiHPpfrCgPTCa'),(43,'vw8AKBNBZ3tOq2NsKZ5L','$2y$10$dIyqQoWU8/csoJ.nu4McL.tt81uhaHiu3oac/gCnE7WGy0e9iocGm'),(44,'FY65GS8gwP7FfRHRV0xf','$2y$10$/HUSYfhKZTMMD4idbuqNseyCDLOrfQ3JylD13nPPzAdgPOD20o9Uq'),(45,'Eimz6pKLVgGpe4mY3lMq','$2y$10$szu./CqAHCGQypo2ooeMc.FaiuGwmVKav7hD84gpnZYnbEmRks4kG'),(46,'7u85tLngTjYIvqaaiuMo','$2y$10$1AjptiCG8zC7IfwXXz6XQOO0aGizq285NmVFF1jIuiBr2dsJz6wJG'),(47,'xkBYRCjgPUU4kFcMJexC','$2y$10$ihJmnhrkN2/8jV5u.UoHIeDz8NXYBGQYnqFvWFi4axbqTe91lSrl6'),(48,'I3mkKYOpXbl3cwksEd99','$2y$10$PBuZ/fYidIOsxy436eA8rOMO/T.1o1L4jszuNuU4wEvrdLMMAIwYm'),(49,'xTqzIBQbc0lwYjKzkQiI','$2y$10$bhhS5jKDXyvGln1voy30Cu7yd3BgH42zdgYrcRz.3R5rx02soJ1WK'),(50,'ilB2fx3o3oepEylPYsMN','$2y$10$a82SNr0QFcZXJZadzarfv.sVh14fcfXK.F7SWlm4exd/K.kMNN46u'),(51,'nuwVScP9WsaGqDMYJYWA','$2y$10$Bp3UAwHmzsLcc./3Cz6KweG3maTohmCEmiVyqjvoqtXC6vaoH9yi6'),(52,'jMkPqs3lIKsYlZllNU4B','$2y$10$5ahcYVsDJJVObPkVZxyL/OuTyPtP9GEINPY00O07GQGBYTbYJ5ypS'),(53,'HXkBzRvPbLnsTyzJXvKm','$2y$10$5iNwb.5V9Rnrdm7D/tbhku2LHTvb6PZAYiK9KxUjo74SmODtjHg7e'),(54,'mCSLDAQCGG4KFUzOVf9r','$2y$10$TwaOBI1JgN7Md50ZiRiJh.n8pnjkHsfD3ooWISskuVoFjZSO2CQlS'),(55,'BezISv3Z2BWNrvHcgPKv','$2y$10$yS26u/tywQ7KZ/Qqvrvoa.fzc.IWDd.e9FpSsM/Dyc4lToDbllOYW'),(56,'b4f8Z25VldHnkGu2z2K6','$2y$10$Hs/qYUAy5h9FTW4I2M4FkOZlKKCQ9pFzIYhbgN2lwTI2GW5EMLl0.'),(57,'E8Ad3UoIr0FxCVkHBK8l','$2y$10$tI7szUvbs.5.QuQZ/dydfe1ZR7UxZQZtxedT221m.LYeUCBRVHlae'),(58,'JiHFNay9w1Ag12CcVDAM','$2y$10$3YJks/s5R9Vrc9enIWvTyeflAIsFyofqok83FFCuFeWQTZiwxocWa'),(59,'EmR1x6o0JWC6YB8JNabb','$2y$10$pLt8xQDrhYEItyeRL36BguC48z5rm9lBuf5Mly3IN54IStHgk1C1i'),(60,'RDuBjN4G9kGkA0v0qLPH','$2y$10$ZdMbuFMW1I0b8IlowQHIH.Wi0O8fkZqWQIngq4o5BDt4COoOTV066'),(61,'AiLaoQ21xgqjhlLNxJ4o','$2y$10$h9s8EZLnYPmjHQFdS8WwA.OKdpfoUmz6ysOvYirbDixAXyOS5mO4O'),(62,'dQ0kLlJtBjfyDJhjSGVI','$2y$10$/0bHyndrZ1yZLwZ/EsCDeuRpyM9Ggub0uIOXqO5vBRneMpxwnqkvm'),(63,'MtKxTnNtFeE2FfKWfFep','$2y$10$SYq0YPhYStaR6txUFYHrUOeMDGBTP8uCqYyzJCpEhDYEvdb2uU1aa'),(64,'40x1CCBpDxKihRUtKn9p','$2y$10$LxLNxhIL72mB6aT/PKVnOu.1qmyMDQg4IsgSkg7.KYagMUohxDEp2'),(65,'qzySTT98mX9ioD6flZs9','$2y$10$qFwPKrDzbJN1GKYNbt7cKuNghwXlj90aUFjS.wHYxBlncMXIzdW1y'),(66,'tatHvEUjUNIdTIEy84oF','$2y$10$RFTjnBvXkeB49MTqx2sJQe3dqhXduPGxGvUPved4x6pY/fZDmaIrm'),(67,'9PLRLZLPguKOIWyRU89X','$2y$10$oYPYlM646djaD/pPD54pS.83lw.w/uZyP7gUltYv6GvHWjl1xJY56'),(68,'RdgZS9L9sEFN5L5jhHYP','$2y$10$fC7oCA1i/fk8Y0pCbqZzcO7qfWe3zmPXqU/1Rg1OYMv4FWkt/.TJ.'),(69,'NRkELmHN6pSJLDqdq4vV','$2y$10$XXwUZQLbDXB0oDlmbiIsmO7nZ4o85SiA638rELSPXGspPmIXvuz1a'),(70,'OKTR2PU6rWV8Fcg2NXJ1','$2y$10$ynRpo0MSxk5xktVngi/UcuBYtojgg.hOPzHbfjBahh2cD2LqyWzLS'),(71,'Hd8rG9FNtyLPHA15yYTO','$2y$10$.vatp/bWI5D8R/achzxjlOHBCCDmFXPFw49gv0Co/O/OO5V9xvxti'),(72,'qSEnO7q61vrjWTr7btle','$2y$10$P9TksyNF6EydiIuYnWAi1O5pJei8/C1VWboYzEjD6bKQ1SDICz9Ky'),(73,'EBxZF4VOtksEiaKDZDNj','$2y$10$xYsM6iKsSgkJxErhwUIGweV82TnI1en.MQ3fQeuBSNavEiXMUq7JG'),(74,'X5Bmy1J2nSh6rGn0H3RH','$2y$10$p.K99xUywZIdCQJCaEwZN.bFTIj8o9PxB8s18YCH89DXj.Q/NSkzi'),(75,'VyxORTpIpJG9DtsP3fW0','$2y$10$.QYFtCnRrzeFavb0Vq6.GuogeS2iXQZkKDXt4Yttae3YhlmTP.jcO'),(76,'5g8wdaiNS7VTFyWEhu2k','$2y$10$DYrG0rYhn6BWFtZsW1Vnz.5YbxQ3U.dUkVFsvaon2uJvCTXmhDp66'),(77,'7AatgzOrvYqvFjLg9bxp','$2y$10$PcC7r0RZdodozMihvdxMouomtCQy8trxTZaxOrUW84TyXdgfxVJ/a'),(78,'n7w5yUNbiviGH4DsoVrx','$2y$10$R.qtZR7S4ucUg8rbsPmRme7kXH3ShrA35H8tPbYOxe1jrl0J4n5iO'),(79,'gxePUJomoYZIfobJb7A9','$2y$10$fPPGs7iSRQxsbRpzIkimuO5X6hBOJYwNZqe5/B6YnQff1oVgSXvb.'),(80,'61jd8FFbJQTdTBxFIZ5z','$2y$10$UPDDx9REc39YNxprNPhLDe5R6476syWtYXkct4gflp9kfPSHUwmZi'),(81,'3sJwSyNzxxGMiHMW0Nt7','$2y$10$jRqK3h2YYHndgsmROSDJU..DWQUnqpd.t75R6X8nRvGkJnLqUH3KO'),(82,'1oTcRcrPGU6z0SH7Lued','$2y$10$9H9xvF9s6oq9gD8AHmeAeOULOM.I6fB1fFJQejVvKdqKDReOuNwB6'),(83,'6zAWUpcZAcAIHihzxot3','$2y$10$QovK8geQqLI7rRNDKyaxvOl47hr.rz9WUkakAdyz.6Vqs8TYH7qyy'),(84,'rRsP0NrxQV1Nr0I198Mb','$2y$10$/LA6pWEyCZtk.Sbtz90KYe1Fe5fLod7SGRMCEz9vDcwTS9TdV3.P.'),(85,'D5EPoPAc7uqGkNYGih6t','$2y$10$3a1/E2tymdGyJwWK4Skm3OfHr4AjrlMOaGXYomy2KQ/q5GPRzVqQe'),(86,'Tk4D7pZOnzbwcyOagLw9','$2y$10$nUU.tOyTZyEjGs7xF.NSfemhiwnucoSLu/30Nrj3b72kghL4o3IQa'),(87,'Zt1VrbPgAg7rSExvaocb','$2y$10$jKTxVsBDmiHvBTiFxN0yROHCg/UKFIEVnBw6z3x0Ok6Kqe1Jq7oyS'),(88,'MnWbwLDN0GiLx0lZ70F3','$2y$10$mVNJaH.OYPanUH3N4q3RauR9w5q4zIfgu9yDMRxDZCVY44T9Y00uu'),(89,'3OhEgCZ3zfa31yGZpSUt','$2y$10$IiKZQxqtllX2XnAO8wI2GOgRei5/maOeI/1dFxsEND2SG/jgSWr4a'),(90,'0lLUlBwhhvdKHeorbtSX','$2y$10$4o63U9cr2vl89rWsPoTSTevQS1gNmssV1Au6iq6FMiMlgmF2unG3.'),(91,'xAdvmGS1X8AKO3yaqfuf','$2y$10$RVgh9ROEDR.J5/xrDmO/7e2q4W6fqNap1S2wQr3.5vzMdjo.Eh1Ei'),(92,'Vx2oNxfpl2KYNruPNxTJ','$2y$10$VNakScdCiFEB6ReIDIhm6OHIt43OzuUtw6jfxWr844orCLQ0YtSnO'),(93,'vnexENFz5ArmIWgZkGQq','$2y$10$slJ4RBjYq5jzCaYRFta.oOVD4K4iJMkcOBk/4i9J6hHZDGA9s/UXC'),(94,'lwBojWYENiFrtuG0NjNa','$2y$10$PWmI2cIKbCyxMBmTo4OfPONP.fFZXF1Dy77jN./lvtGXn.StZdDrK'),(95,'9a11JYv71tqveq9tLnox','$2y$10$UJoMEnNOfj.WO.Tl44QIAusiYyRthj9YpTN5WgFZbuG4DUhdwHHRK'),(96,'LvB0UXLcqeVg2Zcld33e','$2y$10$EnF2x41zph0qp6aLJLonaOnhohT0KS6cWCAgKYZNz.hl1gAhKD8l.'),(97,'OKbXf3HcLGgQOsSwnar7','$2y$10$IoWFMNSK3Xk.tc/kGwoWy.sMDJgMDwxnAOx6h7zfmXGeE0goKAwWq'),(98,'4tEDa72WfYZZuYB46wWG','$2y$10$Ll9QRRclNeMHJuMPkKAtlOE0vAE4Xm9Apxp7jhJhkYCNcICXYjdQS'),(99,'wHWWQDwTFaKfmIAIZtwU','$2y$10$75EP0lMiOfTpANm2/kN1L.422WnZo4Y1vjulqhT5iCn5ackBFBhhm'),(100,'zed39qMPAoUE2WPQJUuJ','$2y$10$KocLH6RCTqqmcVG9YkzU0O0kYmB2QEKmBC0z4l9KRwgfscVP5xTOC');
/*!40000 ALTER TABLE `validation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visitas`
--

DROP TABLE IF EXISTS `visitas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `visitas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `residente_id` int NOT NULL,
  `numeroDocumento` varchar(8) NOT NULL,
  `cui` varchar(13) NOT NULL,
  `Placa` varchar(8) NOT NULL,
  `Primer_Nombre` varchar(30) NOT NULL,
  `Segundo_Nombre` varchar(70) DEFAULT NULL,
  `Primer_Apellido` varchar(45) NOT NULL,
  `Segundo_Apellido` varchar(70) DEFAULT NULL,
  `Fecha_nac` varchar(20) NOT NULL,
  `Fecha_vencimiento` varchar(20) NOT NULL,
  `cono` varchar(20) DEFAULT '0',
  `tipoLicencia` varchar(2) NOT NULL,
  `fechaingreso` datetime NOT NULL,
  `fechaegreso` datetime DEFAULT NULL,
  `tel_emergencia` varchar(8) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `path_licencia` varchar(150) DEFAULT NULL,
  `path_placa` varchar(150) DEFAULT NULL,
  `path_visitante` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visitas`
--

LOCK TABLES `visitas` WRITE;
/*!40000 ALTER TABLE `visitas` DISABLE KEYS */;
/*!40000 ALTER TABLE `visitas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'garitademo'
--

--
-- Dumping routines for database 'garitademo'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2021-09-16  9:44:51
