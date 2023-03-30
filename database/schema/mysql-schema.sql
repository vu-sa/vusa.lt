/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(125) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `subject_type` varchar(125) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `event` varchar(125) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `subject_id` varchar(26) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `causer_type` varchar(125) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `causer_id` varchar(26) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `batch_uuid` char(36) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agenda_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agenda_items` (
  `id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `meeting_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `matter_id` char(26) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `title` text COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `start_time` time DEFAULT NULL,
  `outcome` varchar(255) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agenda_items_meeting_id_foreign` (`meeting_id`),
  KEY `agenda_items_matter_id_foreign` (`matter_id`),
  CONSTRAINT `institution_meeting_matter_matter_id_foreign` FOREIGN KEY (`matter_id`) REFERENCES `matters` (`id`),
  CONSTRAINT `institution_meeting_matter_meeting_id_foreign` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banners` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_lithuanian_ci NOT NULL,
  `image_url` text COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `link_url` text COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `lang` varchar(2) CHARACTER SET utf8mb3 COLLATE utf8mb3_lithuanian_ci NOT NULL DEFAULT 'lt',
  `order` int(11) NOT NULL,
  `is_active` int(11) NOT NULL DEFAULT 1,
  `padalinys_id` int(10) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `banners_order_role_id_unique` (`order`,`padalinys_id`),
  KEY `banners_padalinys_id_foreign` (`padalinys_id`),
  CONSTRAINT `banners_padalinys_id_foreign` FOREIGN KEY (`padalinys_id`) REFERENCES `padaliniai` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `title` text COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `description` text COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `category` varchar(255) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `url` mediumtext COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `padalinys_id` int(10) unsigned NOT NULL DEFAULT 16,
  `extra_attributes` longtext COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `registration_form_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `calendar_padalinys_id_foreign` (`padalinys_id`),
  KEY `calendar_category_foreign` (`category`),
  CONSTRAINT `calendar_category_foreign` FOREIGN KEY (`category`) REFERENCES `categories` (`alias`),
  CONSTRAINT `calendar_padalinys_id_foreign` FOREIGN KEY (`padalinys_id`) REFERENCES `padaliniai` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alias` varchar(255) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `description` text COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_alias_unique` (`alias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `changelog_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `changelog_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`title`)),
  `date` datetime NOT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`description`)),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments` (
  `id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `parent_id` char(26) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `comment` text COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `decision` varchar(125) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL COMMENT 'The decision made alongside the comment.',
  `user_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `commentable_type` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `commentable_id` char(36) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comments_commentable_type_commentable_id_index` (`commentable_type`,`commentable_id`),
  KEY `comments_user_id_foreign` (`user_id`),
  CONSTRAINT `comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contacts` (
  `id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `name` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `email` varchar(125) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `phone` varchar(125) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `profile_photo_path` varchar(125) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `extra_attributes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extra_attributes`)),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `doables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doables` (
  `doable_type` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `doable_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `doing_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`doable_id`,`doable_type`,`doing_id`),
  KEY `doables_doing_id_foreign` (`doing_id`),
  CONSTRAINT `doables_doing_id_foreign` FOREIGN KEY (`doing_id`) REFERENCES `doings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `doing_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doing_user` (
  `doing_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `user_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`doing_id`,`user_id`),
  KEY `doing_user_user_id_foreign` (`user_id`),
  CONSTRAINT `doing_user_doing_id_foreign` FOREIGN KEY (`doing_id`) REFERENCES `doings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `doing_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `doings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doings` (
  `id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `title` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `drive_item_name` varchar(125) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL COMMENT 'The name of the folder in the Sharepoint drive',
  `state` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `date` datetime NOT NULL,
  `extra_attributes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extra_attributes`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `doings_drive_item_name_unique` (`drive_item_name`),
  KEY `doings_status_index` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dutiables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dutiables` (
  `duty_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `dutiable_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `dutiable_type` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `extra_attributes` longtext COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`dutiable_id`,`dutiable_type`,`duty_id`,`start_date`),
  KEY `duties_users_duty_id_foreign` (`duty_id`),
  KEY `duties_users_user_id_foreign` (`dutiable_id`),
  CONSTRAINT `dutiables_duty_id_foreign` FOREIGN KEY (`duty_id`) REFERENCES `duties` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `duties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `duties` (
  `id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `description` text COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `institution_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `order` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Order of duty in institution',
  `email` varchar(255) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL COMMENT 'Commonly the @vusa.lt email address, which is used as the OAuth login. Personal mail is stored in users.email.',
  `extra_attributes` longtext COLLATE utf8mb4_lithuanian_ci DEFAULT NULL COMMENT 'For specifying, e.g. study programme.',
  `places_to_occupy` int(10) unsigned DEFAULT 1 COMMENT 'Full number of positions to occupy for this duty',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `duties_name_institution_id_email_unique` (`name`,`institution_id`,`email`),
  KEY `duties_institution_id_foreign` (`institution_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `connection` text COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `queue` text COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `goal_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `goal_groups` (
  `id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `title` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `description` text COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `goal_matter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `goal_matter` (
  `goal_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `matter_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`goal_id`,`matter_id`),
  KEY `goal_matter_matter_id_foreign` (`matter_id`),
  CONSTRAINT `goal_institution_matter_goal_id_foreign` FOREIGN KEY (`goal_id`) REFERENCES `goals` (`id`),
  CONSTRAINT `goal_institution_matter_matter_id_foreign` FOREIGN KEY (`matter_id`) REFERENCES `matters` (`id`),
  CONSTRAINT `goal_matter_goal_id_foreign` FOREIGN KEY (`goal_id`) REFERENCES `goals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `goal_matter_matter_id_foreign` FOREIGN KEY (`matter_id`) REFERENCES `matters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `goals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `goals` (
  `id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `group_id` char(26) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `padalinys_id` int(10) unsigned NOT NULL,
  `title` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `description` text COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `goals_title_padalinys_id_unique` (`title`,`padalinys_id`),
  KEY `goals_group_id_foreign` (`group_id`),
  KEY `goals_padalinys_id_foreign` (`padalinys_id`),
  CONSTRAINT `goals_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `goal_groups` (`id`),
  CONSTRAINT `goals_padalinys_id_foreign` FOREIGN KEY (`padalinys_id`) REFERENCES `padaliniai` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `institution_meeting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `institution_meeting` (
  `institution_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `meeting_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`institution_id`,`meeting_id`),
  KEY `institution_meeting_meeting_id_foreign` (`meeting_id`),
  CONSTRAINT `institution_meeting_institution_id_foreign` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`),
  CONSTRAINT `institution_meeting_meeting_id_foreign` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `institutions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `institutions` (
  `id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `parent_id` char(26) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `short_name` varchar(255) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `alias` varchar(255) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `description` text COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `padalinys_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `extra_attributes` longtext COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `institutions_parent_id_alias_unique` (`parent_id`,`alias`),
  UNIQUE KEY `institutions_name_padalinys_id_unique` (`name`,`padalinys_id`),
  KEY `duties_institutions_padalinys_id_foreign` (`padalinys_id`),
  CONSTRAINT `institutions_padalinys_id_foreign` FOREIGN KEY (`padalinys_id`) REFERENCES `padaliniai` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `institutions_matters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `institutions_matters` (
  `institution_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `matter_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`institution_id`,`matter_id`),
  KEY `institutions_matters_matter_id_foreign` (`matter_id`),
  CONSTRAINT `institutions_matters_institution_id_foreign` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `institutions_matters_matter_id_foreign` FOREIGN KEY (`matter_id`) REFERENCES `matters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `main_page`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `main_page` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `link` varchar(255) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `text` varchar(100) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `image` varchar(100) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `position` varchar(100) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `order` int(11) DEFAULT NULL,
  `type` varchar(60) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `padalinys_id` int(10) unsigned NOT NULL,
  `lang` varchar(2) COLLATE utf8mb4_lithuanian_ci DEFAULT 'lt',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `main_page_padalinys_id_foreign` (`padalinys_id`),
  CONSTRAINT `main_page_padalinys_id_foreign` FOREIGN KEY (`padalinys_id`) REFERENCES `padaliniai` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `matters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `matters` (
  `id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `title` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `description` text COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  `uuid` char(36) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `collection_name` varchar(255) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `disk` varchar(255) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `conversions_disk` varchar(255) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `size` bigint(20) unsigned NOT NULL,
  `manipulations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`manipulations`)),
  `custom_properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`custom_properties`)),
  `generated_conversions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`generated_conversions`)),
  `responsive_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`responsive_images`)),
  `order_column` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_uuid_unique` (`uuid`),
  KEY `media_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `media_order_column_index` (`order_column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `meetings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `meetings` (
  `id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `title` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `description` text COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `model_type` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `model_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `model_type` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `model_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `navigation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `navigation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT 0,
  `padalinys_id` int(10) unsigned NOT NULL DEFAULT 16,
  `name` varchar(100) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `lang` varchar(2) COLLATE utf8mb4_lithuanian_ci NOT NULL DEFAULT 'lt',
  `url` varchar(255) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `order` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `navigation_padalinys_id_foreign` (`padalinys_id`),
  CONSTRAINT `navigation_padalinys_id_foreign` FOREIGN KEY (`padalinys_id`) REFERENCES `padaliniai` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `news` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `permalink` varchar(150) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `short` mediumtext COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `text` mediumtext COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `lang` varchar(2) COLLATE utf8mb4_lithuanian_ci NOT NULL DEFAULT 'lt',
  `other_lang_id` int(10) unsigned DEFAULT NULL,
  `image` varchar(200) COLLATE utf8mb4_lithuanian_ci DEFAULT '058543019bc51198ea1dc255580d215be99f2297.jpeg',
  `image_author` varchar(200) COLLATE utf8mb4_lithuanian_ci DEFAULT 'VU SA archyvo nuotrauka',
  `important` tinyint(1) NOT NULL DEFAULT 0,
  `padalinys_id` int(10) unsigned NOT NULL,
  `publish_time` timestamp NULL DEFAULT NULL,
  `main_points` mediumtext COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `read_more` varchar(100) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `draft` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `permalink` (`permalink`),
  UNIQUE KEY `news_permalink_role_id_unique` (`permalink`,`padalinys_id`),
  UNIQUE KEY `news_other_lang_id_unique` (`other_lang_id`),
  KEY `news_other_lang_id_index` (`other_lang_id`),
  KEY `news_category_id_foreign` (`category_id`),
  KEY `news_padalinys_id_foreign` (`padalinys_id`),
  CONSTRAINT `news_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `news_padalinys_id_foreign` FOREIGN KEY (`padalinys_id`) REFERENCES `padaliniai` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `type` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `notifiable_type` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `notifiable_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `data` text COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `padaliniai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `padaliniai` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8mb4_lithuanian_ci DEFAULT 'padalinys',
  `fullname` varchar(100) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `shortname` varchar(100) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `alias` varchar(20) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `en` tinyint(1) NOT NULL DEFAULT 0,
  `phone` varchar(255) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `shortname_vu` varchar(255) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `padaliniai_shortname_unique` (`shortname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `permalink` varchar(200) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `text` mediumtext COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `lang` varchar(2) COLLATE utf8mb4_lithuanian_ci NOT NULL DEFAULT 'lt',
  `other_lang_id` int(10) unsigned DEFAULT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `padalinys_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `pages_permalink_role_id_unique` (`permalink`,`padalinys_id`),
  UNIQUE KEY `pages_other_lang_id_unique` (`other_lang_id`),
  KEY `pages_other_lang_id_index` (`other_lang_id`),
  KEY `pages_padalinys_id_foreign` (`padalinys_id`),
  CONSTRAINT `pages_padalinys_id_foreign` FOREIGN KEY (`padalinys_id`) REFERENCES `padaliniai` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `name` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `guard_name` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `posts_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` int(10) unsigned DEFAULT NULL,
  `tag_id` int(10) unsigned NOT NULL,
  `news_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `posts_tags_page_id_tag_id_unique` (`page_id`,`tag_id`),
  UNIQUE KEY `posts_tags_news_id_tag_id_unique` (`news_id`,`tag_id`),
  KEY `posts_tags_tag_id_foreign` (`tag_id`),
  CONSTRAINT `posts_tags_news_id_foreign` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`),
  CONSTRAINT `posts_tags_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`),
  CONSTRAINT `posts_tags_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `registration_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `registration_forms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `registrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `registration_form_id` bigint(20) unsigned NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `relationshipables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `relationshipables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `relationship_id` bigint(20) unsigned NOT NULL,
  `relationshipable_type` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `relationshipable_id` varchar(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `related_model_id` varchar(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `relationshipables_relationship_id_foreign` (`relationship_id`),
  KEY `relationshipable_id_index` (`relationshipable_type`,`relationshipable_id`),
  KEY `related_model_id_index` (`related_model_id`),
  CONSTRAINT `relationshipables_relationship_id_foreign` FOREIGN KEY (`relationship_id`) REFERENCES `relationships` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `relationships` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `slug` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `description` text COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `type` varchar(125) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `role_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  KEY `role_has_permissions_permission_id_foreign` (`permission_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `name` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `guard_name` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `saziningai_exam_flows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saziningai_exam_flows` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `exam_uuid` varchar(30) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `start_time` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `saziningai_exam_flows_exam_uuid_foreign` (`exam_uuid`),
  CONSTRAINT `saziningai_exam_flows_exam_uuid_foreign` FOREIGN KEY (`exam_uuid`) REFERENCES `saziningai_exams` (`uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `saziningai_exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saziningai_exams` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(30) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `name` varchar(30) COLLATE utf8mb4_lithuanian_ci DEFAULT 'Anonimas',
  `email` varchar(100) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `exam_type` varchar(100) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `padalinys_id` int(10) unsigned DEFAULT NULL,
  `place` varchar(100) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `duration` varchar(200) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `subject_name` varchar(100) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `exam_holders` int(11) DEFAULT NULL,
  `students_need` int(10) unsigned DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uuid uniq` (`uuid`),
  KEY `saziningai_exams_padalinys_id_foreign` (`padalinys_id`),
  CONSTRAINT `saziningai_exams_padalinys_id_foreign` FOREIGN KEY (`padalinys_id`) REFERENCES `padaliniai` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `saziningai_observers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saziningai_observers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_uuid` varchar(30) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `phone` varchar(100) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `flow` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `has_arrived` varchar(11) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `phone_p` varchar(255) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `padalinys_id` int(10) unsigned NOT NULL DEFAULT 16,
  PRIMARY KEY (`id`),
  KEY `saziningai_observers_exam_uuid_foreign` (`exam_uuid`),
  KEY `saziningai_observers_padalinys_id_foreign` (`padalinys_id`),
  KEY `saziningai_observers_flow_foreign` (`flow`),
  CONSTRAINT `saziningai_observers_exam_uuid_foreign` FOREIGN KEY (`exam_uuid`) REFERENCES `saziningai_exams` (`uuid`),
  CONSTRAINT `saziningai_observers_flow_foreign` FOREIGN KEY (`flow`) REFERENCES `saziningai_exam_flows` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saziningai_observers_padalinys_id_foreign` FOREIGN KEY (`padalinys_id`) REFERENCES `padaliniai` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_lithuanian_ci NOT NULL,
  `user_id` char(26) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_lithuanian_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb3 COLLATE utf8mb3_lithuanian_ci DEFAULT NULL,
  `payload` text CHARACTER SET utf8mb3 COLLATE utf8mb3_lithuanian_ci NOT NULL,
  `last_activity` int(11) NOT NULL,
  UNIQUE KEY `sessions_id_unique` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sharepoint_fileables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sharepoint_fileables` (
  `sharepoint_file_id` char(36) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `fileable_type` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `fileable_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  UNIQUE KEY `sharepoint_fileables_unique` (`sharepoint_file_id`,`fileable_id`,`fileable_type`),
  KEY `sharepoint_fileables_fileable_type_fileable_id_index` (`fileable_type`,`fileable_id`),
  CONSTRAINT `sharepoint_fileables_sharepoint_file_id_foreign` FOREIGN KEY (`sharepoint_file_id`) REFERENCES `sharepoint_files` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sharepoint_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sharepoint_files` (
  `sharepoint_id` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `id` char(36) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alias` varchar(255) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `description` text COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `task_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `user_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `task_user_user_id_foreign` (`user_id`),
  KEY `task_user_task_id_foreign` (`task_id`),
  CONSTRAINT `task_user_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `name` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `description` text COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `taskable_type` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `taskable_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_taskable_type_taskable_id_index` (`taskable_type`,`taskable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `telescope_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_entries` (
  `sequence` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `batch_id` char(36) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `family_hash` varchar(255) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `should_display_on_index` tinyint(1) NOT NULL DEFAULT 1,
  `type` varchar(20) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`sequence`),
  UNIQUE KEY `telescope_entries_uuid_unique` (`uuid`),
  KEY `telescope_entries_batch_id_index` (`batch_id`),
  KEY `telescope_entries_family_hash_index` (`family_hash`),
  KEY `telescope_entries_created_at_index` (`created_at`),
  KEY `telescope_entries_type_should_display_on_index_index` (`type`,`should_display_on_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `telescope_entries_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_entries_tags` (
  `entry_uuid` char(36) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `tag` varchar(255) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  KEY `telescope_entries_tags_entry_uuid_tag_index` (`entry_uuid`,`tag`),
  KEY `telescope_entries_tags_tag_index` (`tag`),
  CONSTRAINT `telescope_entries_tags_entry_uuid_foreign` FOREIGN KEY (`entry_uuid`) REFERENCES `telescope_entries` (`uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `telescope_monitoring`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_monitoring` (
  `tag` varchar(255) COLLATE utf8mb4_lithuanian_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `typeables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `typeables` (
  `type_id` bigint(20) unsigned NOT NULL,
  `typeable_type` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `typeable_id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  UNIQUE KEY `typeables_type_id_typeable_id_typeable_type_unique` (`type_id`,`typeable_id`,`typeable_type`),
  KEY `typeables_typeable_type_typeable_id_index` (`typeable_type`,`typeable_id`),
  CONSTRAINT `typeables_type_id_foreign` FOREIGN KEY (`type_id`) REFERENCES `types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(125) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `model_type` varchar(125) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `slug` varchar(125) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `extra_attributes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extra_attributes`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `types_title_model_type_unique` (`title`,`model_type`),
  KEY `types_parent_id_foreign` (`parent_id`),
  CONSTRAINT `types_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` char(26) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_lithuanian_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_lithuanian_ci DEFAULT NULL,
  `last_action` timestamp NULL DEFAULT NULL,
  `last_changelog_check` datetime DEFAULT NULL,
  `microsoft_token` text COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `google_token` varchar(255) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_photo_path` varchar(2048) COLLATE utf8mb4_lithuanian_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `websockets_statistics_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `websockets_statistics_entries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `app_id` varchar(125) COLLATE utf8mb4_lithuanian_ci NOT NULL,
  `peak_connection_count` int(11) NOT NULL,
  `websocket_message_count` int(11) NOT NULL,
  `api_message_count` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_lithuanian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` VALUES (1,'2016_03_20_213911_sessions',1);
INSERT INTO `migrations` VALUES (2,'2021_07_18_202817_agenda',1);
INSERT INTO `migrations` VALUES (3,'2021_07_18_204151_calendar',1);
INSERT INTO `migrations` VALUES (4,'2021_07_18_205259_contacts',1);
INSERT INTO `migrations` VALUES (5,'2021_07_18_214617_mainpage',1);
INSERT INTO `migrations` VALUES (6,'2021_07_18_215755_menu',1);
INSERT INTO `migrations` VALUES (7,'2021_07_18_221240_news',1);
INSERT INTO `migrations` VALUES (8,'2021_07_27_000000_users',1);
INSERT INTO `migrations` VALUES (9,'2021_07_27_173208_news_cats',1);
INSERT INTO `migrations` VALUES (10,'2021_07_27_173405_padaliniai',1);
INSERT INTO `migrations` VALUES (11,'2021_07_27_173517_page',1);
INSERT INTO `migrations` VALUES (12,'2021_07_27_180125_page_cats',1);
INSERT INTO `migrations` VALUES (13,'2021_07_27_180251_saziningai',1);
INSERT INTO `migrations` VALUES (14,'2021_07_27_181006_saziningai_people',1);
INSERT INTO `migrations` VALUES (15,'2021_07_27_181545_sidebar',1);
INSERT INTO `migrations` VALUES (16,'2021_07_27_183748_users_groups',1);
INSERT INTO `migrations` VALUES (19,'2018_08_08_100000_create_telescope_entries_table',2);
INSERT INTO `migrations` VALUES (20,'2022_02_11_221228_add_phone_to_saziningai',2);
INSERT INTO `migrations` VALUES (21,'2014_10_12_100000_create_password_resets_table',3);
INSERT INTO `migrations` VALUES (22,'2019_08_19_000000_create_failed_jobs_table',3);
INSERT INTO `migrations` VALUES (23,'2019_12_14_000001_create_personal_access_tokens_table',3);
INSERT INTO `migrations` VALUES (24,'2021_12_31_000000_seed_with_old_structure_records',3);
INSERT INTO `migrations` VALUES (25,'2021_12_31_083656_add_email_to_users',3);
INSERT INTO `migrations` VALUES (26,'2021_12_31_093035_run_jetstream_migrations',3);
INSERT INTO `migrations` VALUES (27,'2022_01_01_134132_refactor_saziningai',3);
INSERT INTO `migrations` VALUES (28,'2022_01_01_142502_create_saziningai_exam_flows_table',3);
INSERT INTO `migrations` VALUES (29,'2022_01_02_203706_refactor_users',3);
INSERT INTO `migrations` VALUES (30,'2022_01_02_231537_refactor_calendar',3);
INSERT INTO `migrations` VALUES (31,'2022_01_03_002947_refactor_banners',3);
INSERT INTO `migrations` VALUES (32,'2022_01_03_005450_refactor_main_page',3);
INSERT INTO `migrations` VALUES (33,'2022_01_03_011405_refactor_posts',3);
INSERT INTO `migrations` VALUES (34,'2022_01_04_001102_refactor_contacts',3);
INSERT INTO `migrations` VALUES (35,'2022_01_04_020632_refactor_menu',3);
INSERT INTO `migrations` VALUES (36,'2022_01_04_185357_contact_rearrangement',3);
INSERT INTO `migrations` VALUES (37,'2022_01_05_132952_refactor_roles_and_padaliniai',3);
INSERT INTO `migrations` VALUES (38,'2022_05_06_143246_create_page_views_table',3);
INSERT INTO `migrations` VALUES (39,'2022_06_14_125808_create_vusa_tables',3);
INSERT INTO `migrations` VALUES (40,'2022_06_14_130000_seed_with_records',3);
INSERT INTO `migrations` VALUES (41,'2022_07_25_031738_create_freshmen_camps',4);
INSERT INTO `migrations` VALUES (42,'2022_07_25_035609_create_media_table',4);
INSERT INTO `migrations` VALUES (43,'2022_07_30_020755_create_registration_forms_table',5);
INSERT INTO `migrations` VALUES (44,'2022_07_30_023740_create_registrations_table',5);
INSERT INTO `migrations` VALUES (45,'2022_08_26_021134_add_order_column_to_duties',6);
INSERT INTO `migrations` VALUES (46,'2022_08_27_195256_add_end_date_to_calendar',7);
INSERT INTO `migrations` VALUES (47,'2022_09_09_115120_create_jobs_table',8);
INSERT INTO `migrations` VALUES (48,'2022_09_16_202545_create_permission_tables',8);
INSERT INTO `migrations` VALUES (49,'2022_09_25_193504_create_notifications_table',9);
INSERT INTO `migrations` VALUES (50,'2022_09_27_002343_cleanup_unneeded_columns',10);
INSERT INTO `migrations` VALUES (51,'2022_09_28_181019_create_doing_types_table',10);
INSERT INTO `migrations` VALUES (52,'2022_09_28_181105_create_question_groups_table',10);
INSERT INTO `migrations` VALUES (53,'2022_09_28_183537_create_questions_table',10);
INSERT INTO `migrations` VALUES (54,'2022_09_28_185446_create_doings_table',10);
INSERT INTO `migrations` VALUES (55,'2022_09_29_191803_create_activity_log_table',10);
INSERT INTO `migrations` VALUES (56,'2022_09_29_191804_add_event_column_to_activity_log_table',10);
INSERT INTO `migrations` VALUES (57,'2022_09_29_191805_add_batch_uuid_column_to_activity_log_table',10);
INSERT INTO `migrations` VALUES (58,'2022_10_01_220648_create_model_types',10);
INSERT INTO `migrations` VALUES (59,'2022_10_01_230056_create_model_has_content_types',10);
INSERT INTO `migrations` VALUES (60,'2022_10_01_231743_migrate_types',10);
INSERT INTO `migrations` VALUES (61,'2022_10_02_140310_remove_unneeded_type_tables_columns',10);
INSERT INTO `migrations` VALUES (62,'2022_10_02_163206_create_sharepoint_documents_table',10);
INSERT INTO `migrations` VALUES (63,'2022_10_03_180438_create_tasks_table',10);
INSERT INTO `migrations` VALUES (64,'2022_10_03_211559_create_comments_table',10);
INSERT INTO `migrations` VALUES (65,'2022_10_06_023413_create_doing_question_table',10);
INSERT INTO `migrations` VALUES (66,'2022_12_01_140437_add_student_rep_coordinator_permissions',10);
INSERT INTO `migrations` VALUES (67,'2022_12_11_195445_create_relationships_table',10);
INSERT INTO `migrations` VALUES (68,'2022_12_13_151830_table_adjustments',10);
INSERT INTO `migrations` VALUES (69,'2022_12_15_133833_uuids_for_sharepoint_documents',10);
INSERT INTO `migrations` VALUES (70,'2022_12_24_160945_index_doing_statuses',10);
INSERT INTO `migrations` VALUES (71,'2022_12_29_002330_attributes_for_doings',10);
INSERT INTO `migrations` VALUES (72,'2023_01_02_170828_add_duty_time_periods',10);
INSERT INTO `migrations` VALUES (73,'2023_01_03_001033_restructurize_visak',10);
INSERT INTO `migrations` VALUES (74,'2023_01_03_144546_more_cleanup',10);
INSERT INTO `migrations` VALUES (75,'2023_01_05_043216_make_goal_groups_optional',10);
INSERT INTO `migrations` VALUES (76,'2023_01_05_200056_institutions_questions_many_to_many',10);
INSERT INTO `migrations` VALUES (77,'2023_01_05_211431_create_doing_user_table',10);
INSERT INTO `migrations` VALUES (78,'2023_01_08_013753_update_tasks_table',10);
INSERT INTO `migrations` VALUES (79,'2023_01_08_220824_make_meetings_have_many_institutions',10);
INSERT INTO `migrations` VALUES (80,'2023_01_09_085210_changes_roles_n_permissions_to_ulid',10);
INSERT INTO `migrations` VALUES (81,'2023_01_12_000000_create_websockets_statistics_entries_table',10);
INSERT INTO `migrations` VALUES (82,'2023_01_12_003850_add_padalinys_to_goals',10);
INSERT INTO `migrations` VALUES (83,'2023_01_13_075927_make_agenda_item_matter_nullable',10);
INSERT INTO `migrations` VALUES (84,'2023_01_13_143413_make_comment_commentable_varchar',10);
INSERT INTO `migrations` VALUES (85,'2023_01_14_115031_extra_attributes_for_contacts',10);
INSERT INTO `migrations` VALUES (86,'2023_01_14_200524_user_last_action_time_logger',10);
INSERT INTO `migrations` VALUES (87,'2023_01_18_110344_multiple_sharepoint_files_per_model',10);
INSERT INTO `migrations` VALUES (88,'2023_01_21_105827_add_folder_drive_item_columns_to_models',11);
INSERT INTO `migrations` VALUES (89,'2023_01_23_105853_update_status_column_in_doings',12);
INSERT INTO `migrations` VALUES (90,'2023_01_24_123740_add_decision_field_to_comments',13);
INSERT INTO `migrations` VALUES (91,'2023_02_18_022134_create_changelog_items_table',14);
INSERT INTO `migrations` VALUES (92,'2023_02_20_002150_change_title_to_text_in_agenda_items',15);
INSERT INTO `migrations` VALUES (93,'2023_03_20_212910_add_start_date_to_primary_key_dutiable',16);
