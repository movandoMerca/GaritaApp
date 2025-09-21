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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs`
--

LOCK TABLES `logs` WRITE;
/*!40000 ALTER TABLE `logs` DISABLE KEYS */;
INSERT INTO `logs` VALUES (1,1,'Realizo Cambios en la Configuracion de la Pagina ','2021-08-18 10:26:44','2021-08-18 10:26:44');
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
INSERT INTO `users` VALUES (1,'admin','administrador','admin@admin.com',NULL,'$2y$10$fW9mO44iBzWmn5c0tADRtutIr59/47UJf7ZyF6.KHoaTMJVnA9N2G','UM8NCFzMyfKnzOdjvPVGmffI7Dkf7sn7Tx0kPlsyNZWxQelfO0PA338cUcqk',NULL,'2021-08-10 20:00:21',1,1,1,1);
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
INSERT INTO `validation` VALUES (1,'nbiLPvfG2YeGLVxPBP7Z','$2y$10$KMR/d.MmR.XhYwh9MLG.5eNE10QHYyKC35WKBwJYLdkOHrbyJHIRu'),(2,'bWZ7uZJ8fc25c8Mb4uRp','$2y$10$txFAPyh1H9Q5yQ7Sx9.x3OUG/VHU5A6cV6RLVtOCm0.hlB/V8uRoK'),(5,'jUyrAgAKfgE4wdzFu2tu','$2y$10$FJ219bL1u73Se09gtJahoOLfCQVjjf.7q3mY8LJjyh19.NIWRS2KS'),(6,'bnqMBPbhXFbcwbgAKrzx','$2y$10$Kll2BrgGebJNgfiL.JwKCe0ry7DwRl9fCafoLaJO/75BD00mQSKwS'),(7,'U7CcEugWngYaqp9mbS68','$2y$10$i0Uvd405F0KGuWiZ.YJQGuDNpwmYf4nscBmw5myUOccUduYeBeRmy'),(8,'gx4jJLfAnApFw28qiUSi','$2y$10$bA0mzZkj2irSamJEkNYrtOFvtLpLywSkQxaME7qMrveFDQvyV5X/m'),(9,'L9PmRRHqUnmmhFkFnzRA','$2y$10$3jkfgLl5hxeV1veMmwJj8eA38MGxnOxYVPGmjfosSdNFQIkVn.U2e'),(10,'97Pukcg9VbZgV4CEdGuf','$2y$10$YwYKUkS8z/L8vsqFPONZ9Ol955dIIZJva8jyTSLmouGLbVlsbE1r.'),(11,'i82wukPPvGfbW8vPyzC6','$2y$10$uvikftJs.kJ3hKz0tz05oeuxoBJ1YLBQo8J98pK75bAMwqOacrwe2'),(12,'66gmwaZ4dtVazdQLnKzj','$2y$10$JR1EIqhR1l6cV5Rzz1Vxyer0SwkAFISUHd/rg.qCJkcF/OD1ggw2K'),(13,'LGagCFXrAaC7vepgQuSM','$2y$10$h9g7H.7qwc6KqvRncMhU/.bU8WkqS5OhDcIFB.YohRvZJZj/Ojahi'),(14,'adeynbwQBCihGSbRWE53','$2y$10$yx7j/TnQPT3LsnCBocrEPeFkJHo2rPaws5kd8puvwWXU7JYlGhnEO'),(15,'rdwf6RUpdkgSkkCzizRp','$2y$10$RvKjkMZwXJml58kNoiJk3.wrFLpvwLi1V4Q0duBsKi2e39tqabvG6'),(16,'WzgLTwVzWBUjbwLBGP87','$2y$10$.UhXeQAw6RMt/F8/AIQEmesF/slxLtdio3R27zEIWG4XXbeP4UilS'),(17,'gHaxPbjHum94eQZvavux','$2y$10$rAV4aO1zLWB.1El5Vq1JCOSCDhgI3wOwcDypHieVICH.EmD9Ka.1W'),(18,'PY3PtjPCU3RGhqtATxRG','$2y$10$N6A9p56GM74MAjZSgSHunOu0OAiQMltCNekXuER8jt9Hruk3MT7cS'),(19,'JfLYmpUJkBPBb9wNUJrQ','$2y$10$GGz6apK8u/3rDHl9uufKuOqN7IDXUzmNrUdXIZsho4bLodFnMzney'),(20,'9eAtCTMqbbha8pyGbR42','$2y$10$uYVB8PPB5AucRhPTsGcgYO4iaZ31F1PILvqE15QvvbkmDYzFFdkli'),(21,'ejpKQKQ7VE9hWVUY8hk6','$2y$10$AnugCqBodWUJvjr3d7fHOOfSnHju4v1KmXieHCyrX0CNjEYTsZmU2'),(22,'tDxW3qg4SwFamViAyGGX','$2y$10$mrjiLvDax7owVIAqCGPzPe4xiqYsKPjt.itLNC2O8DmxOHYQbEVo2'),(23,'u7Sq4zYCjgqtXnSG3teZ','$2y$10$OKwLvaMiYZwlfa9EmunpaeDobT5nn7xCDXcG8LSxRsuLj1z8iMqqq'),(24,'HruJnrUJegxQ2QpSnpaq','$2y$10$W1cnLWfFY2jBj8xhHS.1vOn7n8ZjoHxQzdt14Y0RjtCZ1Ilt1n6Zu'),(25,'LFvRbGa8DCKpA7M55YMn','$2y$10$2H.ldDaFKH81cyNQIe9bduSLXSGZzSXvy6VykXFHbZvcDrL2Jt1da'),(26,'T8Vi8WMxjNYBM3Pw9PwB','$2y$10$mScMjEicc25V3Pm1uOd43.QjS85zn1Hru7HENZJloYrWhUCNSGFce'),(27,'abgYv2b5TgX9BrwGXLcL','$2y$10$af5gPYdGPODXxLGtsc0RSuhJiM3xSpRdfkiLRjtXQbZ8LUvzy5nOC'),(28,'8G5DXK4wDF2pndv7NHUZ','$2y$10$gtAaYwtBuBr2ejOUQlTmGO/n9kAgpni8ubLe6iZ.b0cd4ayxHrDsC'),(29,'WQijeA8x6VXLxZuySkXi','$2y$10$17aRiVPWa8R5Sa8JuocbXeB0EYx5.bWnzvmqMhh4v35PffFj7R5X6'),(30,'vRYLdWPGv9j3bw4uX9Nh','$2y$10$STkvMbqpgQCBCzRojeQbEOFqFZBSD3I.vckHGZB6mHorxlTo84lhG'),(31,'m8qyuVAe2kuCbMrqBaf9','$2y$10$RloIqNsRVZnEglaOrKp0.uEgF.fL4VZbUi1Z6ab23Z8EIo6SAJ/Xq'),(32,'uefaLfHGAqdXMxYVKcfi','$2y$10$yTvGGjx27yrc6VZAiZt5z.Ux55Usg2NuJfoaHs5PyArOUcyKpN3VS'),(33,'UxxjzHxXAeDGyD4qxPUK','$2y$10$ClOyQrPl93jtTCj6NAcWHuJACpbP6YsSQ2gnsAeH6DCqfZUs6Ave.'),(34,'AS8q8vXgryjY8MdXMYYm','$2y$10$yy2Hltzz5YyTW1ufXtEF2OhJlNYQVmuGdS3RkKGb57Ke4Elhmj7K.'),(35,'Rfmn4rSpAnricVGEp35e','$2y$10$5Kj50OqI1h5UML4b8TKI/upWx1QWliiCqVPNmlJqNqtcQjTA8AKpm'),(36,'g34Y4RDSVxKq4Ckkg6jN','$2y$10$0Kx5qDjmmU4VVhwtLdbJLeD7xlYRVmlTjozdn6tDrRFwSgzPiFYhK'),(37,'nXuKk4X3ZuSDXNRanXN5','$2y$10$pV7t/psXOvQKbYVYHSGCFuLTAF07Wqol61cB0IROp2elQt66KDKam'),(38,'bvajBUxz6T9QKEmmwxu8','$2y$10$zwG7.ggSKOsqPyZrQ6mwMebWDDCd8ggylSkNfqnDlspZqoi.HwF.C'),(39,'EXcCUKMKiuRDv9bMXeYH','$2y$10$KM/lePRZcesFhCB0D6J0g.Dz3zN60/xvsUaO4gnWQ8DZZTEin8aca'),(40,'uTurFfr75BLEDMHfQBFp','$2y$10$Fm8vKbtNRxdiBdWgJMVvp.Cnm1fqbuxyE80G9.4nVAn.p1bDMMFbK'),(41,'5zb4eeJr7FH8J7kU8Ax6','$2y$10$BIvptBicsN1kPHsaNi6WkObk6XZfTGxt5hZZxJisFMwTrdpnEGVga'),(42,'j5iprShb6NyWkWLqPqri','$2y$10$xJ7d0vJpObR4l6j2f74xF.eE4mTcXzuKxedkj3q/H37YdqetOAc7y'),(43,'9MmudNL6TnD3gVhuK7ZK','$2y$10$YXXENg8JeAmIcuP3fqTVL.4SvbkK2HVTTnRXb4762yyBbsc7tUrdm'),(44,'Hbwv5GhvSPH94nbwY4GB','$2y$10$6lnfhWcnNpxm7AikZ9./jeh6SxrXuqQX6AsM.9pd8x.FL.JHa3QaS'),(45,'4YjYXz5b8HBgdUK6VTgp','$2y$10$N3Opx7HsJ1a6JkngoV2lCOqKv3SGQV1D0neBWrdEtFPcwWYmsF4T6'),(46,'2j2LYTYcCzEZJk2GT9zg','$2y$10$eSbA1gY6e7xmYIb.rQzAHOjqTGtPRyKoOSW6dDj3KKeXUSnyT5Ks6'),(47,'vh48TjMUtJz9K5GZLiby','$2y$10$khy6yo4EsGky55dkK5K/p.bA4pmxwGrQtiHVrDYiOeXVeYUDp4wfy'),(48,'J7ZHVpWfhAt6J67NBV2C','$2y$10$2VPacD6V1Qvl4hwyXWVmmej/mNcQ/bo/BnZv/hLf9ZIdgB5/yypIK'),(49,'6kFv8SVCHvi3Zrcyw3FT','$2y$10$AoXU9NNLlqas5qL8x2LyneIpdMZEMSasRWuAOKCleXB9gsu4v0xTu'),(50,'UD5kakEhkRQtzZnAPE69','$2y$10$lJQEnFsr6OJK5YLKnoBME.K12MIEhBy/CUBskN7PoX7RcIRcCp96W'),(51,'JEJAvgMYhXEPeKdWkZi2','$2y$10$u5I1r2iqTaslj9o.oXYQh.dmjbl.Q02YFzIP.mSE60IIKPO/2jp.i'),(52,'A9ARwqtbhkpCNz2iXxrj','$2y$10$iXKf51L5F74I5IQQMn0qe.xqSTQTme8B0pu.tR0f3kExn/QXfDBDW'),(53,'NtYjVUdqeyuD7QfqV5Kr','$2y$10$KvVGz2Ucza0fbjj/0mlWb.J49PYPc5CH6hi0CP1oguDoUurK4pDbC'),(54,'VT5UXha9PZ728BpTwtD2','$2y$10$PLGJFzR2vdTdgAe7cTmvXOMf9jRbqDtFHe/jWnWDZZYGZlEA6Ff4O'),(55,'bumSva4wPf26TpvQbgnM','$2y$10$4pgL..MqHy18iIG0AL4smeJ4Aj.97yJwhpOPmc4KcvgvSqOUVScKK'),(56,'4SxuTydpSf3irbb2egcb','$2y$10$O/KDhzFBCvyLfIJ.nFY6H.ZsYcTJpiLX5xRON.9Gu2qpeSAn0NkdW'),(57,'S5YCLBvajYeQJhbWFg8c','$2y$10$lYmsSDBOd0fYcbfCydrJKevNAMeerGmWo/kV5axH12byFuZexyEg.'),(58,'kdM2KxQbg6GMkF5KW99e','$2y$10$qxKWn9e/nwxIYGoF.VN/yO.JxZHVTMZaetxoCgPnBb0URsG99kGdW'),(59,'yv33xL9KZmmfqEBSxE7y','$2y$10$Hz7LlZr358XbAl0S79gglevy6FNlzzgqw59Ruxz44rtEFH46vkmkW'),(60,'RbCGjDcTeKCpFYyiSvT8','$2y$10$Zj0RZrMuJUVoWE22zTgwQue/ovxe8aSe4fp0wM.4IFwukPfijufqK'),(61,'8UD54CE2mq383VRvBNng','$2y$10$ZBGNQpfgsuZ3TPS0WDCrdOtMYLUfowCcTHxasK4HXj4fEzoQ5DhAG'),(62,'5jyzXx5NzhFMWXvfhymP','$2y$10$NqroJ9KY84ivSnySW9ifZeswXhApLZa4qiRKbUSXIv.HfCvwV4mv6'),(63,'zPqM5cCjm6SiiihLeJTe','$2y$10$s66.6kFJnbTso18tm4siSeEXP3emlolQnvbBej.Hv4xRFewkKep7e'),(64,'CxTnMv7CPEKkRRkqazLD','$2y$10$1lxLmF9cQ4fr4X7ohvhjHuRug3N9cKGF4KW7/g4cBXkfTuvX89Pgq'),(65,'cRx6cURCi9GpP988ZaiV','$2y$10$9oTyaAYtkKm2A23lHuUmR.aJ5GQBDNmIxEF.IDSMhkPyI3tZpN2E.'),(66,'9quN2kRfp5nNfzLD2vym','$2y$10$0PoRHRglnvQI/QFU5Go1zenek1Tb9v0/3ECGhckwaXPK.if2KAeMK'),(67,'evbpmrWXR7B3vCSKZh4G','$2y$10$XRn4hBAWxVdpFR09VjuUD.f.rk4f7WtL9J0cov6zNJ48jhNv7flva'),(68,'C8qjDaNx8B4tGAVvXfCn','$2y$10$lHUAKJGmitN3jPS0IkeiPO10n7ZCsTJ5qNPSm9j0tLGGOHU8KswgS'),(69,'TNxG4RgWT2jcWmmwfXKa','$2y$10$qaJOf5s09mWDUURnFf6NjubSfqlgEHFHFqoE9jtQ6M9fkgpuBNxwm'),(70,'rPLGqr8tD3qwM5VaB5pD','$2y$10$PCMnkfdpEcuqcOVw75K8KO6okxlpH/DlFBWcrG2lK6wAnsyx99rwC'),(71,'iWdS6BzF7aCau4bTaY8B','$2y$10$TFgXHHkthWHOD5Py52Mv1OeWFtyDUkaXc6klIkA6XMxt7osJW7bmC'),(72,'mJZhvXFJa3JkaL3uQwwb','$2y$10$skyxRar98am8cSZa8ifuSu9EO6FhlRwSGENzwQ9Bx2mPawMs3dpy2'),(73,'YUfmtpk2UqzuPDvrRjHU','$2y$10$w.K50XJ/kjO5vOud6FNJE.BaXnjYeTErjqcdY.pZpmDRvB0Ha43TK'),(74,'RNKF33gaqDKcJhvAFiyF','$2y$10$jJosTQFIBDJe8H18UvVtxeKmAzvml1rNN6BhxjWG5UfOw0WQG8VWK'),(75,'JqYTQQw2NwbCRxP76RdS','$2y$10$GKXycndfedBa/oESEMIfS.XnRZE48XMHPXGrJdtsqdv8hCiPS.jy.'),(76,'rDn8YqvLfky5ycjfrLL7','$2y$10$XwhIJ9/YKzANxG.Bodpxbemh89.e3OZyxbO6.q41mRypq7sm/8aLG'),(77,'YpG2fFUqaZnKWwvuU66d','$2y$10$S2HYS9FMi2p3uQMzF.0IW.aoB604QX9rxgB.yWytC7q71tq1VJq82'),(78,'mwEVYLuUnh6e5Zpy7vhM','$2y$10$js.VqjVraeov0G1Hs6dzqeU9AwrOLdHYAaVGd2epEt2S6Y0wBIDG2'),(79,'FSqHVHDAW4qubvHNqAJZ','$2y$10$f19ZIW7tA57Q.KvkRN4vzeH0rAOOJ6AP8qhtvAxhqT.LzmJ8pqrQm'),(80,'7RUK6PTig3mz8ippAHTp','$2y$10$.e4gDpa4exC1BU0Fh6Q.hupXuuqX.dZ5e6XewRRD3TkR/BqT1b9qW'),(81,'F6UvAj7CZhwBu2JR7vUb','$2y$10$3B4A7O0Mx/wLL71fMekVSeqbpfs2YpXhICsZpp5/78bVPBYpvKHnm'),(82,'uhWZ8Vya3rpxBq9yAXAL','$2y$10$Whba7IasML7S/yZGAKK0zucwEPodc3flQr5WJ.d4lhFkS74GpWqG2'),(83,'XKbYLrhhecpntfWma9ju','$2y$10$olKFKdhoA0IbuDxiO5OMkePx/xc/AuDXraFIP07Xckw3KGdOgnH0S'),(84,'RW3UZmmHhapRcVzJ385J','$2y$10$8uBx8bEFR3qzsFS.ISstxOrJDeihb0wHOyYwX6smjQd7tWhku1Nuu'),(85,'B3KTXxgJz4Rhy9QWh2Zh','$2y$10$7qk1UsbStySlOy3g1dRj6OW1edhePPxSqgJkrzW/DXlNsFZj1ykSq'),(86,'caGiYUQtCtneUGYr4kYX','$2y$10$4ZldMgg1A5JWZxvC8s3McOFcpSMQEogGsm8PoOlHS8Qpo9qRB/g26'),(87,'e6mudZfkM5dbtVnbUUnB','$2y$10$HeVfnWBl9Czs8JYHG7IX2uQGfcX0rTkcCbwKo30PTrASnNEm8AwTq'),(88,'ANbnncFprxpqgMUHxjri','$2y$10$RKE6AckiKpb95gurSJLwAurk9zkUET/CMCxqvIpVmSzFShF7uNmsS'),(89,'wPzGwDdDR5pdtd7PaQT7','$2y$10$cQgD6z/cCKoIcCIKUGCNdue0jvShj40eF3bhnpTa.7U8syKWli2ze'),(90,'y66WkJZw6ZCR52wYcagy','$2y$10$Zqgs9OC8jMXZOzqG2MReduCO6BbKFlPKGgXH/JTQSXXrfsYW2D4fu'),(91,'Frk2bQmxmytTmSwU8Aj5','$2y$10$JMLXYdh6oM7QAiJvAumzIOQxVGLjBNWdexwlHgOmal90dB3k4YQoK'),(92,'Z7aPKqEvyrz6TndNHXSi','$2y$10$4Cf5Aa1XDTo0l1tkaqRZ7Oy9zkqjqnPmhUIv9xmn4DqgnWm5WQAjq'),(93,'tmJZ7mX5jBwmAGbyc6jw','$2y$10$H3Kpl9w5hbD4vF4wjn.iLec0M8Jirqae6ChMD6OH.u3Bg3eEwGUzG'),(94,'LXB3dHB7UywGAiL8AH3C','$2y$10$RHLR6VrvUlgLA9o2eK2N0eizwGyonF4eTE5tboNz451497K95/7na'),(95,'HZa5E9Z4CVeiBW53uvm8','$2y$10$0TW55clPztFm0UDUxC.uL.j7fXpOwl.Ai66NWg/cH39XS9r96GYp2'),(96,'BHf2yHGNMWiNQnryRNXK','$2y$10$I7EvIhMj9WMovPv31q1AMef/4xspxf1m.d1LcOW0wVlPotz3JPut.'),(97,'DAcTXqDWW5QEf9jH9uAY','$2y$10$7gon2sc2/xR/ls3pVtYeKOoOIFnzbxgSduyhPzUvKlRBcBTmtFVo.'),(98,'j5C3g4DLL8ZMRccFDJMj','$2y$10$6DStyI4dAPDNwsy4D5oMR.oyqnCiTqKgIHcoswNEGXKLsxpGQScve'),(99,'in8WrkVq3PuMwLa8YLVk','$2y$10$oBZNpkvcSHbRvQ4IT0mUU.oaJlusUBwa57IBJhVEDwkTJgYPx9836'),(100,'LNknyLUwayhCh9jHTx58','$2y$10$TNTmh.PsC9Ie/hCF07gulugLTEZ2NEX2QJMpTm9gYCuvo53BykiLi'),(101,'yTrCakc2NAUSm5N3m7Db','$2y$10$hkm.ovAEHjIPTRp4VYptDesFJ2nTwf4SrTi3VdXDWz5eIJsBzoOSS'),(102,'tURDqAA7Y4qgJKR4ca6Y','$2y$10$ENi2yG4dk8bYFY3iXEem3OCLzWCDo.eTTdQuNPoL0QGx/VRQCPlka');
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
  `tipoLicencia` varchar(2) NOT NULL,
  `fechaingreso` datetime NOT NULL,
  `fechaegreso` datetime DEFAULT NULL,
  `tel_emergencia` varchar(8) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `path_licencia` varchar(150) DEFAULT NULL,
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

-- Dump completed on 2021-08-18 10:45:20
