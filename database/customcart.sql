-- =====================================================================
-- CustomCart — Complete Customizable E-Commerce Platform
-- Database: customcart  (MySQL 8.x / MariaDB 10.4+, utf8mb4)
--
-- Schema (all tables, indexes and foreign keys) plus initial
-- configuration and demo data: site settings, Sitepanel sections,
-- SEO/URL registry (wl_meta_tags), countries, currencies, sample
-- categories, products, orders and fictitious demo customers.
--
-- Contains NO real customer data and NO real credentials.
-- Demo logins (change them immediately after installing):
--   Sitepanel  : admin / Admin@12345
--   Customer   : nora@example.com / Customer@123
--
-- Import into an EMPTY database:
--   mysql -u <user> -p customcart < database/customcart.sql
-- =====================================================================

-- MySQL dump 10.13  Distrib 8.4.7, for Win64 (x86_64)
--
-- Host: localhost    Database: customcart
-- ------------------------------------------------------
-- Server version	8.4.7

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

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `namespace` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `time` int NOT NULL,
  `batch` int unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES (1,'2026-10-30-140000','App\\Database\\Migrations\\CreateFaqTable','default','App',1794224540,1),(2,'2026-10-30-140100','App\\Database\\Migrations\\CreateCouponTables','default','App',1794224540,1),(3,'2026-10-30-140200','App\\Database\\Migrations\\CreateHeaderImagesTable','default','App',1794224540,1),(4,'2026-10-30-140300','App\\Database\\Migrations\\CreateLocationTables','default','App',1794224540,1),(5,'2026-10-30-140400','App\\Database\\Migrations\\AddProductEnquiryType','default','App',1794224540,1),(6,'2026-10-30-140500','App\\Database\\Migrations\\AddAdminPhone','default','App',1794224540,1),(7,'2026-10-31-100000','App\\Database\\Migrations\\UniqueMetaPageUrl','default','App',1793352979,1),(8,'2026-10-31-110000','App\\Database\\Migrations\\AddCmsPageLayout','default','App',1793354528,1),(9,'2026-10-31-120000','App\\Database\\Migrations\\BlogMetaEntityType','default','App',1793364368,1),(10,'2026-11-02-100000','App\\Database\\Migrations\\CreateShopTables','default','App',1793595977,1),(11,'2026-11-02-120000','App\\Database\\Migrations\\CreateReasonsTable','default','App',1793597164,1),(12,'2026-11-02-120000','App\\Database\\Migrations\\AddProductRecentlyAdded','default','App',1793602809,1),(13,'2026-11-02-121000','App\\Database\\Migrations\\AddProductButtonType','default','App',1793602975,1),(14,'2026-11-02-130000','App\\Database\\Migrations\\CreateProductCategoriesTable','default','App',1793617714,1),(15,'2026-11-02-140000','App\\Database\\Migrations\\StoreUrlsAtRoot','default','App',1793621660,1),(16,'2026-11-03-100000','App\\Database\\Migrations\\CreateGalleryTables','default','App',1793683172,1),(17,'2026-11-03-120000','App\\Database\\Migrations\\AddBlogUrl','default','App',1793697736,1),(18,'2026-11-03-140000','App\\Database\\Migrations\\AddBlogPostDetails','default','App',1793700495,1),(19,'2026-11-03-160000','App\\Database\\Migrations\\CreateTeamTable','default','App',1793703490,1),(20,'2026-11-03-180000','App\\Database\\Migrations\\AddTeamType','default','App',1793704492,1),(21,'2026-11-04-100000','App\\Database\\Migrations\\CreateCourseTables','default','App',1793770887,1),(22,'2026-11-04-140000','App\\Database\\Migrations\\AddVolunteerAccounts','default','App',1793771834,1),(23,'2026-11-04-160000','App\\Database\\Migrations\\CreateServicesTable','default','App',1793773884,1),(24,'2026-11-05-100000','App\\Database\\Migrations\\CreateEventsNewsStoriesTables','default','App',1793776600,1),(25,'2026-11-06-100000','App\\Database\\Migrations\\CreateEventDocumentsAndRegistrations','default','App',1793868737,1),(26,'2026-11-06-120000','App\\Database\\Migrations\\AddSgdDefaultCurrency','default','App',1793938068,1),(27,'2026-11-06-160000','App\\Database\\Migrations\\CreateDonationsTable','default','App',1793960675,1),(28,'2026-11-10-100000','App\\Database\\Migrations\\KareemtexStoreTables','default','App',1794224576,2),(29,'2026-11-10-110000','App\\Database\\Migrations\\KareemtexStoreSetup','default','App',1794224576,2),(30,'2026-11-10-120000','App\\Database\\Migrations\\IndustriesStaticPage','default','App',1794313031,3),(31,'2026-11-12-100000','App\\Database\\Migrations\\LocationStateCityHierarchy','default','App',1794469636,4),(32,'2026-11-12-120000','App\\Database\\Migrations\\CartShippingRoute','default','App',1794475526,5),(33,'2026-11-12-130000','App\\Database\\Migrations\\OrderInvoiceRoute','default','App',1794476246,6),(34,'2026-11-12-140000','App\\Database\\Migrations\\DeliveryDays','default','App',1794480755,7);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_admin`
--

DROP TABLE IF EXISTS `tbl_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_admin` (
  `admin_id` int unsigned NOT NULL AUTO_INCREMENT,
  `admin_type` tinyint unsigned NOT NULL DEFAULT '2' COMMENT '1 = super admin, 2+ = sub-admin (section permissions)',
  `admin_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'password_hash() output; never plaintext',
  `admin_password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'LOCAL DEVELOPMENT ONLY: plain-text password, ignored unless CI_ENV=development',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1 = active, 0 = inactive',
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `failed_attempts` smallint unsigned NOT NULL DEFAULT '0' COMMENT 'Consecutive wrong passwords',
  `locked_until` datetime DEFAULT NULL COMMENT 'Sign-in refused until this time',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `uq_admin_username` (`admin_username`),
  UNIQUE KEY `uq_admin_email` (`admin_email`),
  KEY `idx_admin_status` (`status`),
  KEY `idx_admin_type` (`admin_type`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_admin`
--

LOCK TABLES `tbl_admin` WRITE;
/*!40000 ALTER TABLE `tbl_admin` DISABLE KEYS */;
INSERT INTO `tbl_admin` (`admin_id`, `admin_type`, `admin_name`, `admin_username`, `admin_email`, `phone`, `password_hash`, `admin_password`, `status`, `last_login_at`, `last_login_ip`, `failed_attempts`, `locked_until`, `created_at`, `updated_at`) VALUES (1,1,'Administrator','admin','admin@customcart.com',NULL,'$2y$12$VuLVfdQISAP1VkFFy5mwkubarZOzds7VCh75UfvynlD81X9lipuAi',NULL,1,NULL,NULL,0,NULL,'2026-10-13 11:49:56','2026-11-13 14:20:24');
/*!40000 ALTER TABLE `tbl_admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_admin_allowed_sections`
--

DROP TABLE IF EXISTS `tbl_admin_allowed_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_admin_allowed_sections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subadmin_id` int unsigned NOT NULL,
  `sec_parent_id` int NOT NULL,
  `sec_id` int NOT NULL,
  `permission` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '1' COMMENT '1=View,2=Add,3=Edit,4=Activate,5=Deactivate,6=Delete',
  `allow_category_ids` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_allowed_admin_section` (`subadmin_id`,`sec_id`),
  KEY `idx_allowed_section` (`sec_id`),
  CONSTRAINT `fk_allowed_admin` FOREIGN KEY (`subadmin_id`) REFERENCES `tbl_admin` (`admin_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_allowed_section` FOREIGN KEY (`sec_id`) REFERENCES `tbl_admin_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=208 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_admin_allowed_sections`
--

LOCK TABLES `tbl_admin_allowed_sections` WRITE;
/*!40000 ALTER TABLE `tbl_admin_allowed_sections` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbl_admin_allowed_sections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_admin_login_log`
--

DROP TABLE IF EXISTS `tbl_admin_login_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_admin_login_log` (
  `log_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int unsigned DEFAULT NULL COMMENT 'Set when the username matched an admin',
  `username_attempted` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_success` tinyint unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `idx_login_ip_time` (`ip_address`,`created_at`),
  KEY `idx_login_username_time` (`username_attempted`,`created_at`),
  KEY `idx_login_admin_time` (`admin_id`,`created_at`),
  CONSTRAINT `fk_login_log_admin` FOREIGN KEY (`admin_id`) REFERENCES `tbl_admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_admin_login_log`
--

LOCK TABLES `tbl_admin_login_log` WRITE;
/*!40000 ALTER TABLE `tbl_admin_login_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbl_admin_login_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_admin_sections`
--

DROP TABLE IF EXISTS `tbl_admin_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_admin_sections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parent_id` int NOT NULL DEFAULT '0',
  `section_title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `section_controller` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disp_order` int NOT NULL DEFAULT '0',
  `actual_privilege` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Privileges a sub-admin can be given here: 1=View,2=Add,3=Edit,4=Activate,5=Deactivate,6=Delete',
  `status` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '0=Inactive, 1=Active, 2=Deleted',
  PRIMARY KEY (`id`),
  KEY `idx_sections_parent` (`parent_id`,`disp_order`),
  KEY `idx_sections_controller` (`section_controller`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_admin_sections`
--

LOCK TABLES `tbl_admin_sections` WRITE;
/*!40000 ALTER TABLE `tbl_admin_sections` DISABLE KEYS */;
INSERT INTO `tbl_admin_sections` (`id`, `parent_id`, `section_title`, `section_controller`, `disp_order`, `actual_privilege`, `status`) VALUES (1,0,'General',NULL,1,NULL,'1'),(2,1,'Dashboard','dashboard',1,'1','1'),(3,4,'Admin Settings','settings',1,'1,3','1'),(4,0,'Other Management',NULL,2,NULL,'1'),(6,4,'Manage Banners','banners',3,'1,2,3,4,5,6','1'),(7,4,'Manage Static Pages','staticpages',4,'1,2,3,4,5,6','1'),(8,4,'Manage Testimonials','testimonial',5,'1,2,3,4,5,6','1'),(9,4,'Newsletter Management','newsletter',6,'1,2,3,4,5,6','1'),(10,4,'Manage Enquiry','enquiry',7,'1,3,6','1'),(11,4,'Meta Tags Management','meta',8,'1,2,3,4,5,6','1'),(22,4,'Manage Product Enquiry','product_enquiry',4,'1,3,6','1'),(23,4,'Manage Discount Coupons','coupons',5,'1,2,3,4,5,6','1'),(24,4,'Manage Header Images','header_images',7,'1,2,3,4,5,6','1'),(25,4,'Manage FAQs','faq',9,'1,2,3,4,5,6','1'),(26,4,'Manage Subadmins','subadmin',11,'1,2,3,4,5,6','1'),(27,4,'Manage Location','location',12,'1,2,3,4,5,6','1'),(29,42,'Manage Orders','orders',1,'1,3,6','1'),(30,39,'Manage Products','products',2,'1,2,3,4,5,6','1'),(31,39,'Manage Categories','category',1,'1,2,3,4,5,6','1'),(32,45,'Manage Colors','color',2,'1,2,3,4,5,6','1'),(33,45,'Manage Size','size',3,'1,2,3,4,5,6','1'),(34,45,'Manage Brands','brand',1,'1,2,3,4,5,6','1'),(35,45,'Manage Fabrics','material',4,'1,2,3,4,5,6','1'),(37,42,'Manage Shipping','shipping',3,'1,2,3,4,5,6','1'),(38,41,'Manage Members','members',2,'1,3,4,5,6','1'),(39,0,'Product Management',NULL,2,NULL,'1'),(40,39,'Manage Bulk Upload Product','products_bulk',3,'1,2','1'),(41,0,'Members Management',NULL,3,NULL,'1'),(42,0,'Manage Orders',NULL,4,NULL,'1'),(43,42,'Manage Orders Products','order_products',2,'1,3','1'),(44,42,'Manage Reasons','reasons',4,'1,2,3,4,5,6','1'),(45,0,'Manage Entities',NULL,6,NULL,'1'),(46,4,'Manage Images Gallery','gallery',13,'1,2,3,4,5,6','1'),(47,4,'Manage Video Gallery','video',14,'1,2,3,4,5,6','1'),(48,4,'Manage Team & Partners','team',15,'1,2,3,4,5,6','1'),(54,4,'Manage News & Events','news',20,'1,2,3,4,5,6','1'),(58,4,'Manage Certificates & Brochures','certificates',16,'1,2,3,4,5,6','1'),(60,4,'Manage Running Marquee','marquee',18,'1,2,3,4,5,6','1'),(61,4,'Manage Refer a Friend','referrals',19,'1,6','1'),(62,42,'Manage Returns & Refunds','returns',5,'1,3,6','1'),(63,39,'Manage Product Testimonials','reviews',4,'1,4,5,6','1');
/*!40000 ALTER TABLE `tbl_admin_sections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_site_settings`
--

DROP TABLE IF EXISTS `tbl_site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_site_settings` (
  `setting_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `updated_at` datetime NOT NULL,
  `updated_by` int unsigned DEFAULT NULL,
  PRIMARY KEY (`setting_key`),
  KEY `idx_settings_updated_by` (`updated_by`),
  CONSTRAINT `fk_settings_admin` FOREIGN KEY (`updated_by`) REFERENCES `tbl_admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_site_settings`
--

LOCK TABLES `tbl_site_settings` WRITE;
/*!40000 ALTER TABLE `tbl_site_settings` DISABLE KEYS */;
INSERT INTO `tbl_site_settings` (`setting_key`, `setting_value`, `updated_at`, `updated_by`) VALUES ('company_license','Commercial Registration 1010000000','2026-11-13 13:22:10',1),('contact_address','King Fahd Road, Al Olaya\nRiyadh 12211, Saudi Arabia','2026-11-13 13:22:10',1),('contact_email','hello@customcart.com','2026-11-13 13:22:10',1),('contact_phone','+966 11 000 0000','2026-11-13 13:22:10',1),('facebook_link','https://www.facebook.com/','2026-11-13 13:22:10',1),('footer_copyright','© {year} CustomCart. All Rights Reserved','2026-11-13 12:20:34',1),('footer_description','Premium everyday clothing and accessories, delivered across Saudi Arabia. Your Store, Your Way.','2026-11-13 13:22:10',1),('free_delivery_above','250.00','2026-11-13 13:22:10',1),('google_analytics_id','','2026-11-12 13:01:06',1),('google_web_code','','2026-11-12 13:01:06',1),('gst_percent','15.00','2026-11-12 13:01:06',1),('instagram_link','https://www.instagram.com/','2026-11-13 13:22:10',1),('linkedin_link','https://www.linkedin.com/','2026-11-13 13:22:10',1),('low_stock_threshold','5.00','2026-11-12 11:48:57',16),('maintenance_mode','0','2026-11-12 13:01:06',1),('map_code','<iframe src=\"https://maps.google.com/maps?q=King%20Fahd%20Road%2C%20Al%20Olaya%2C%20Riyadh&amp;z=13&amp;output=embed\" title=\"CustomCart location map\" class=\"d-block w-100 h-100 border-0\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>','2026-11-13 13:22:10',1),('meta_description','CustomCart is a complete, customizable e-commerce platform to launch, manage and grow your online store. Your Store, Your Way.','2026-11-13 12:20:34',1),('meta_keywords','CustomCart, e-commerce platform, online store, shopping cart, customizable e-commerce','2026-11-13 12:20:34',1),('meta_title','CustomCart – Complete E-Commerce Platform','2026-11-13 12:20:34',1),('office_hours','Sun - Thu: 9:00 AM - 9:00 PM, Sat: 12:00 PM - 9:00 PM','2026-11-13 13:22:10',1),('og_image','customcart-og.png','2026-11-13 12:20:34',NULL),('shipping_charges','15.00','2026-11-13 13:22:10',1),('site_favicon','','2026-10-13 17:55:04',NULL),('site_logo','','2026-10-13 17:55:04',NULL),('site_name','CustomCart','2026-11-13 12:20:34',1),('site_tagline','Your Store, Your Way','2026-11-13 12:20:34',1),('site_title','CustomCart','2026-11-13 12:20:34',1),('sp_active_theme','customcart','2026-11-13 13:31:33',1),('sp_theme_accent','#F59E0B','2026-11-13 13:31:33',1),('sp_theme_alert-danger-bg','#FEF2F2','2026-11-09 15:25:41',1),('sp_theme_alert-danger-border','#FECACA','2026-11-09 15:25:41',1),('sp_theme_alert-danger-text','#7F1D1D','2026-11-09 15:25:41',1),('sp_theme_alert-info-bg','#CFF4FC','2026-11-09 15:25:41',1),('sp_theme_alert-info-border','#9EEAF9','2026-11-09 15:25:41',1),('sp_theme_alert-info-text','#055160','2026-11-09 15:25:41',1),('sp_theme_alert-success-bg','#F0FDF4','2026-11-09 15:25:41',1),('sp_theme_alert-success-border','#BBF7D0','2026-11-09 15:25:41',1),('sp_theme_alert-success-text','#14532D','2026-11-09 15:25:41',1),('sp_theme_bg','#F1F5F9','2026-11-13 13:31:33',1),('sp_theme_brand','#2563EB','2026-11-13 13:31:33',1),('sp_theme_brand-soft','#EFF6FF','2026-11-13 13:31:33',1),('sp_theme_breadcrumb-sep','#94A3B8','2026-11-13 13:31:33',1),('sp_theme_btn-primary-bg','#2563EB','2026-11-13 13:31:33',1),('sp_theme_btn-primary-hover-bg','#1D4ED8','2026-11-13 13:31:33',1),('sp_theme_btn-primary-hover-text','#FFFFFF','2026-11-13 13:31:33',1),('sp_theme_btn-primary-text','#FFFFFF','2026-11-13 13:31:33',1),('sp_theme_btn-secondary-bg','#FFFFFF','2026-11-13 13:31:33',1),('sp_theme_btn-secondary-border','#BFDBFE','2026-11-13 13:31:33',1),('sp_theme_btn-secondary-hover-bg','#EFF6FF','2026-11-13 13:31:33',1),('sp_theme_btn-secondary-hover-border','#2563EB','2026-11-13 13:31:33',1),('sp_theme_btn-secondary-hover-text','#172554','2026-11-13 13:31:33',1),('sp_theme_btn-secondary-text','#2563EB','2026-11-13 13:31:33',1),('sp_theme_dashboard-card-bg','#FFFFFF','2026-11-13 13:31:33',1),('sp_theme_error','#DC2626','2026-11-13 13:31:33',1),('sp_theme_error-soft','#FEF2F2','2026-11-13 13:31:33',1),('sp_theme_field-border','#CBD5E1','2026-11-13 13:31:33',1),('sp_theme_field-focus-border','#2563EB','2026-11-13 13:31:33',1),('sp_theme_field-focus-ring','#2563EB33','2026-11-13 13:31:33',1),('sp_theme_focus-outline','#F59E0B','2026-11-13 13:31:33',1),('sp_theme_front-accent','#D97706','2026-10-14 16:42:39',1),('sp_theme_front-footer-bg','#064E3B','2026-10-14 16:42:39',1),('sp_theme_front-footer-text','#D9E2EC','2026-10-14 16:42:39',1),('sp_theme_front-ink','#1F2937','2026-10-14 16:42:39',1),('sp_theme_front-line','#E5E7EB','2026-10-14 16:42:39',1),('sp_theme_front-muted','#6B7280','2026-10-14 16:42:39',1),('sp_theme_front-on-primary','#FFFFFF','2026-10-14 16:42:39',1),('sp_theme_front-paper','#F8FAFC','2026-10-14 16:42:39',1),('sp_theme_front-primary','#047857','2026-10-14 16:42:39',1),('sp_theme_front-primary-dark','#065F46','2026-10-14 16:42:39',1),('sp_theme_front-surface','#FFFFFF','2026-10-14 16:42:39',1),('sp_theme_header-bg','#FFFFFF','2026-11-13 13:31:33',1),('sp_theme_header-text','#0F172A','2026-11-13 13:31:33',1),('sp_theme_ink','#0F172A','2026-11-13 13:31:33',1),('sp_theme_line','#E2E8F0','2026-11-13 13:31:33',1),('sp_theme_link','#2563EB','2026-11-13 13:31:33',1),('sp_theme_link-hover','#1D4ED8','2026-11-13 13:31:33',1),('sp_theme_logo-bg','#FFFFFF','2026-11-13 13:31:33',1),('sp_theme_muted','#64748B','2026-11-13 13:31:33',1),('sp_theme_ok','#16A34A','2026-11-13 13:31:33',1),('sp_theme_ok-soft','#F0FDF4','2026-11-13 13:31:33',1),('sp_theme_panel','#FFFFFF','2026-11-13 13:31:33',1),('sp_theme_sidebar','#172554','2026-11-13 13:31:33',1),('sp_theme_sidebar-active','#2563EB','2026-11-13 13:31:33',1),('sp_theme_sidebar-active-text','#FFFFFF','2026-11-13 13:31:33',1),('sp_theme_sidebar-hover','#1E3A8A','2026-11-13 13:31:33',1),('sp_theme_sidebar-hover-text','#FFFFFF','2026-11-13 13:31:33',1),('sp_theme_sidebar-icon','#A5B4FC','2026-11-13 13:31:33',1),('sp_theme_sidebar-muted','#8B9BC9','2026-11-13 13:31:33',1),('sp_theme_sidebar-text','#C7D2FE','2026-11-13 13:31:33',1),('sp_theme_stat-blogs-angle','135','2026-10-28 16:06:04',1),('sp_theme_stat-blogs-end','#8260c1','2026-10-28 16:06:04',1),('sp_theme_stat-blogs-start','#8464b3','2026-10-28 16:06:04',1),('sp_theme_stat-category-angle','135','2026-11-10 12:34:53',1),('sp_theme_stat-category-end','#1D4ED8','2026-11-13 13:31:33',1),('sp_theme_stat-category-start','#2563EB','2026-11-13 13:31:33',1),('sp_theme_stat-enquiry-angle','135','2026-11-10 12:34:53',1),('sp_theme_stat-enquiry-end','#d0490f','2026-11-10 12:34:53',1),('sp_theme_stat-enquiry-start','#c44d1a','2026-11-10 12:34:53',1),('sp_theme_stat-members-angle','135','2026-11-10 12:34:53',1),('sp_theme_stat-members-end','#EA580C','2026-11-13 13:31:33',1),('sp_theme_stat-members-start','#F59E0B','2026-11-13 13:31:33',1),('sp_theme_stat-orders-angle','135','2026-11-10 12:34:53',1),('sp_theme_stat-orders-end','#172554','2026-11-13 13:31:33',1),('sp_theme_stat-orders-start','#1E3A8A','2026-11-13 13:31:33',1),('sp_theme_stat-products-angle','135','2026-11-10 12:34:53',1),('sp_theme_stat-products-end','#0369A1','2026-11-13 13:31:33',1),('sp_theme_stat-products-start','#0EA5E9','2026-11-13 13:31:33',1),('sp_theme_stat-testimonial-angle','135','2026-11-10 12:34:53',1),('sp_theme_stat-testimonial-end','#0e8374','2026-11-10 12:34:53',1),('sp_theme_stat-testimonial-start','#118473','2026-11-10 12:34:53',1),('sp_theme_table-hover','#F8FAFC','2026-11-13 13:31:33',1),('sp_theme_warn','#9A6700','2026-11-09 15:25:41',1),('sp_theme_warn-soft','#FFF4D6','2026-11-09 15:25:41',1),('timezone','Asia/Riyadh','2026-11-12 13:01:06',1),('twitter_link','https://x.com/','2026-11-13 13:22:10',1),('whatsapp_no','+966500000000','2026-11-13 13:22:10',1),('whatsapp_text','Hello CustomCart, I have a question about an order.','2026-11-13 13:22:10',1),('youtube_link','','2026-11-12 13:01:06',1);
/*!40000 ALTER TABLE `tbl_site_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_banners`
--

DROP TABLE IF EXISTS `wl_banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_banners` (
  `banner_id` int unsigned NOT NULL AUTO_INCREMENT,
  `banner_position` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` int unsigned DEFAULT NULL COMMENT 'wl_categories (position Category Pages)',
  `banner_image` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'File name in uploaded_files/banner/',
  `banner_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banner_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `status` enum('1','0') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive',
  `banner_added_date` datetime NOT NULL,
  PRIMARY KEY (`banner_id`),
  KEY `idx_banners_position_status` (`banner_position`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_banners`
--

LOCK TABLES `wl_banners` WRITE;
/*!40000 ALTER TABLE `wl_banners` DISABLE KEYS */;
INSERT INTO `wl_banners` (`banner_id`, `banner_position`, `category_id`, `banner_image`, `banner_url`, `banner_title`, `sort_order`, `status`, `banner_added_date`) VALUES (1,'Category Pages',1,'banner-mens-clothing.jpg','','Men\'s Clothing',1,'1','2026-11-13 13:23:50'),(2,'Category Pages',2,'banner-womens-clothing.jpg','','Women\'s Clothing',2,'1','2026-11-13 13:23:50'),(3,'Category Pages',3,'banner-accessories.jpg','','Accessories',3,'1','2026-11-13 13:23:50'),(4,'Online Store',NULL,'banner-online-store.jpg','','Online Store',4,'1','2026-11-13 13:23:50');
/*!40000 ALTER TABLE `wl_banners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_blog_categories`
--

DROP TABLE IF EXISTS `wl_blog_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_blog_categories` (
  `category_id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `friendly_url` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'File name in uploaded_files/blog/',
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `status` enum('1','0') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `uq_blog_categories_friendly_url` (`friendly_url`),
  KEY `idx_blog_categories_status_sort` (`status`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_blog_categories`
--

LOCK TABLES `wl_blog_categories` WRITE;
/*!40000 ALTER TABLE `wl_blog_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `wl_blog_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_blog_comments`
--

DROP TABLE IF EXISTS `wl_blog_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_blog_comments` (
  `comment_id` int unsigned NOT NULL AUTO_INCREMENT,
  `blog_id` int unsigned NOT NULL,
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('1','0') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '1 = approved (public), 0 = pending / hidden',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`comment_id`),
  KEY `idx_blog_comments_public` (`blog_id`,`status`,`created_at`),
  KEY `idx_blog_comments_status` (`status`,`created_at`),
  KEY `idx_blog_comments_ip` (`ip_address`,`created_at`),
  CONSTRAINT `fk_blog_comments_blog` FOREIGN KEY (`blog_id`) REFERENCES `wl_blogs` (`blog_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_blog_comments`
--

LOCK TABLES `wl_blog_comments` WRITE;
/*!40000 ALTER TABLE `wl_blog_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `wl_blog_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_blog_related`
--

DROP TABLE IF EXISTS `wl_blog_related`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_blog_related` (
  `blog_id` int unsigned NOT NULL,
  `related_blog_id` int unsigned NOT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`blog_id`,`related_blog_id`),
  KEY `idx_blog_related_related` (`related_blog_id`),
  CONSTRAINT `fk_blog_related_blog` FOREIGN KEY (`blog_id`) REFERENCES `wl_blogs` (`blog_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_blog_related_related` FOREIGN KEY (`related_blog_id`) REFERENCES `wl_blogs` (`blog_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_blog_related`
--

LOCK TABLES `wl_blog_related` WRITE;
/*!40000 ALTER TABLE `wl_blog_related` DISABLE KEYS */;
/*!40000 ALTER TABLE `wl_blog_related` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_blogs`
--

DROP TABLE IF EXISTS `wl_blogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_blogs` (
  `blog_id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int unsigned NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `friendly_url` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `blog_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Optional link target (http/https); empty = the post page',
  `added_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Author shown as "By: ..." (empty = Admin)',
  `contents_types` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Label above the article title, e.g. Community',
  `posted_on` date DEFAULT NULL COMMENT 'Date shown as "Posted On" (empty = published_at)',
  `excerpt` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Sanitised HTML (Html_sanitizer)',
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'File name in uploaded_files/blog/',
  `tags` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'One tag per line',
  `published_at` datetime NOT NULL,
  `status` enum('1','0') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '1=Published,0=Draft',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`blog_id`),
  UNIQUE KEY `uq_blogs_friendly_url` (`friendly_url`),
  KEY `idx_blogs_status_published` (`status`,`published_at`),
  KEY `idx_blogs_category` (`category_id`),
  CONSTRAINT `fk_blogs_category` FOREIGN KEY (`category_id`) REFERENCES `wl_blog_categories` (`category_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_blogs`
--

LOCK TABLES `wl_blogs` WRITE;
/*!40000 ALTER TABLE `wl_blogs` DISABLE KEYS */;
/*!40000 ALTER TABLE `wl_blogs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_brands`
--

DROP TABLE IF EXISTS `wl_brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_brands` (
  `brand_id` int unsigned NOT NULL AUTO_INCREMENT,
  `brand_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` enum('0','1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive,2=Deleted',
  `brand_date_added` datetime NOT NULL,
  `brand_date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`brand_id`),
  KEY `idx_brand_name` (`brand_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_brands`
--

LOCK TABLES `wl_brands` WRITE;
/*!40000 ALTER TABLE `wl_brands` DISABLE KEYS */;
/*!40000 ALTER TABLE `wl_brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_categories`
--

DROP TABLE IF EXISTS `wl_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_categories` (
  `category_id` int unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int unsigned NOT NULL DEFAULT '0',
  `category_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `friendly_url` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_alt` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_home` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `is_menu` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `status` enum('0','1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive,2=Deleted',
  `date_added` datetime NOT NULL,
  `date_modified` datetime DEFAULT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `uq_category_url` (`friendly_url`),
  KEY `idx_category_parent` (`parent_id`,`status`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_categories`
--

LOCK TABLES `wl_categories` WRITE;
/*!40000 ALTER TABLE `wl_categories` DISABLE KEYS */;
INSERT INTO `wl_categories` (`category_id`, `parent_id`, `category_name`, `friendly_url`, `category_image`, `category_alt`, `category_description`, `is_home`, `is_menu`, `sort_order`, `status`, `date_added`, `date_modified`) VALUES (1,0,'Men\'s Clothing','mens-clothing','mens-clothing.jpg','Men\'s T-shirts and shirts','<p>Everyday essentials for men: soft cotton T-shirts and easy casual shirts, cut for a clean, modern fit.</p>','1','1',1,'1','2026-11-13 13:21:20',NULL),(2,0,'Women\'s Clothing','womens-clothing','womens-clothing.jpg','Women\'s denim jacket','<p>Wardrobe staples for women, led by our premium denim jacket in two washes.</p>','1','1',2,'1','2026-11-13 13:21:20',NULL),(3,0,'Accessories','accessories','accessories.jpg','Leather wallets','<p>Finishing touches in full-grain leather, made to age beautifully.</p>','1','1',3,'1','2026-11-13 13:21:20',NULL);
/*!40000 ALTER TABLE `wl_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_certificates`
--

DROP TABLE IF EXISTS `wl_certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_certificates` (
  `certificate_id` int unsigned NOT NULL AUTO_INCREMENT,
  `cert_type` enum('certificate','brochure') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'certificate',
  `title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cert_image` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cert_file` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`certificate_id`),
  KEY `cert_type_status_sort_order` (`cert_type`,`status`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_certificates`
--

LOCK TABLES `wl_certificates` WRITE;
/*!40000 ALTER TABLE `wl_certificates` DISABLE KEYS */;
INSERT INTO `wl_certificates` (`certificate_id`, `cert_type`, `title`, `description`, `cert_image`, `cert_file`, `sort_order`, `status`, `date_added`, `date_updated`) VALUES (1,'certificate','Sustainable Cotton Sourcing Pledge','Our cotton comes from responsible, traceable mills.','sustainable-cotton-pledge.jpg',NULL,1,'1','2026-11-13 13:21:20',NULL),(2,'certificate','Customer Service Excellence 2026','Recognising fast replies, easy returns and high customer ratings.','customer-service-excellence.jpg',NULL,2,'1','2026-11-13 13:21:20',NULL),(3,'brochure','Lookbook 2026 - Spring / Summer','The Spring / Summer 2026 collection in one PDF.','lookbook-2026-cover.jpg','customcart-lookbook-2026.pdf',3,'1','2026-11-13 13:21:20',NULL),(4,'brochure','Size & Care Guide','Size charts and care instructions for every product.','size-and-care-guide-cover.jpg','size-and-care-guide.pdf',4,'1','2026-11-13 13:21:20',NULL);
/*!40000 ALTER TABLE `wl_certificates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_cities`
--

DROP TABLE IF EXISTS `wl_cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_cities` (
  `city_id` int unsigned NOT NULL AUTO_INCREMENT,
  `state_id` int unsigned NOT NULL,
  `city_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive',
  PRIMARY KEY (`city_id`),
  UNIQUE KEY `uq_cities_state_name` (`state_id`,`city_name`),
  KEY `idx_cities_status` (`status`),
  CONSTRAINT `fk_cities_state` FOREIGN KEY (`state_id`) REFERENCES `wl_states` (`state_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_cities`
--

LOCK TABLES `wl_cities` WRITE;
/*!40000 ALTER TABLE `wl_cities` DISABLE KEYS */;
INSERT INTO `wl_cities` (`city_id`, `state_id`, `city_name`, `status`) VALUES (1,2,'Riyadh','1'),(2,3,'Jeddah','1'),(3,3,'Makkah','1'),(4,4,'Madinah','1'),(5,1,'Dammam','1'),(6,1,'Khobar','1'),(7,1,'Dhahran','1'),(8,1,'Jubail','1'),(9,1,'Qatif','1'),(10,1,'Al Ahsa','1'),(11,1,'Hafr Al Batin','1'),(12,3,'Taif','1'),(13,7,'Tabuk','1'),(14,5,'Buraidah','1'),(15,5,'Unaizah','1'),(16,6,'Abha','1'),(17,6,'Khamis Mushait','1'),(18,8,'Hail','1'),(19,10,'Jazan','1'),(20,11,'Najran','1'),(21,12,'Al Bahah','1'),(22,9,'Arar','1'),(23,13,'Sakaka','1'),(24,4,'Yanbu','1'),(25,2,'Al Kharj','1'),(26,1,'Ras Tanura','1'),(27,1,'Abqaiq','1'),(28,3,'Rabigh','1'),(32,18,'New Delhi','1');
/*!40000 ALTER TABLE `wl_cities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_cms_pages`
--

DROP TABLE IF EXISTS `wl_cms_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_cms_pages` (
  `page_id` int NOT NULL AUTO_INCREMENT,
  `parent_id` int NOT NULL DEFAULT '0',
  `page_name` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  `friendly_url` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `layout` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL COMMENT 'Designed view (Pages::DESIGNED_VIEWS); NULL = the page text',
  `page_description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `page_short_description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `image` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `status` enum('0','1','2') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '1' COMMENT '0=Inactive,1=Active,2=Deleted',
  `page_updated_date` datetime NOT NULL,
  PRIMARY KEY (`page_id`),
  KEY `friendly_url` (`friendly_url`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_cms_pages`
--

LOCK TABLES `wl_cms_pages` WRITE;
/*!40000 ALTER TABLE `wl_cms_pages` DISABLE KEYS */;
INSERT INTO `wl_cms_pages` (`page_id`, `parent_id`, `page_name`, `friendly_url`, `layout`, `page_description`, `page_short_description`, `image`, `status`, `page_updated_date`) VALUES (1,0,'Contact Us','contactus','contact_us','<p>Questions about sizes, an order or a return? Send us a message and our customer care team will reply within one working day.</p>','','','1','2026-11-13 13:22:10'),(2,0,'About CustomCart','aboutus','about_us','<p>CustomCart is a modern online store for premium everyday clothing and accessories. We choose every fabric and fit ourselves, keep our prices honest and deliver across Saudi Arabia. Your Store, Your Way.</p>',NULL,'','1','2026-11-13 13:22:10'),(3,0,'Sitemap','sitemap','sitemap','',NULL,'','2','2026-11-04 17:44:03'),(4,0,'Privacy Policy','privacy-policy',NULL,'<h2>Privacy Policy</h2><p>This policy explains how CustomCart collects, uses and protects your personal information when you visit our website or place an order.</p><h3>What we collect</h3><ul><li>Your name, email address, mobile number and delivery address</li><li>Your orders and returns</li><li>Technical data such as your IP address and browser</li></ul><h3>How we use it</h3><p>We use your information only to process and deliver your orders, answer your messages and, if you agree, send you our newsletter. Card payments are handled by our payment partner; we never store card numbers.</p><h3>Your rights</h3><p>You can view and update your details in My Account, unsubscribe at any time, or contact us to delete your account.</p>',NULL,'','1','2026-11-13 13:22:10'),(5,0,'Terms & Conditions','terms-conditions',NULL,'<h2>Terms &amp; Conditions</h2><p>These terms apply to the use of the CustomCart website and to every order placed through it.</p><h3>Orders and prices</h3><p>Prices are shown in Saudi riyals (SAR). VAT at the current rate is added at checkout and shown before you pay. An order is confirmed when you receive our confirmation email.</p><h3>Delivery</h3><p>We deliver within Saudi Arabia. Delivery times shown at checkout are estimates from the day of dispatch.</p><h3>Returns</h3><p>Returns and refunds follow our Returns and Refunds pages.</p>',NULL,'','1','2026-11-13 13:22:10'),(7,0,'Returns','returns',NULL,'<h2>Returns</h2><p>Changed your mind? You can return unworn items with their original tags within 14 days of delivery.</p><ol><li>Go to My Account &gt; Order History and open the order.</li><li>Choose <strong>Return Item</strong> and tell us why.</li><li>Pack the item; our courier collects it within 2-3 working days.</li></ol><p>For hygiene reasons, items that have been worn or washed cannot be returned.</p>',NULL,'','1','2026-11-13 13:22:10'),(13,0,'Refunds','refunds',NULL,'<h2>Refunds</h2><p>Once your return arrives and passes inspection, we refund the item price to your original payment method within 5-7 working days. Delivery charges are refunded when the item was faulty or incorrect. Cancelled paid items are refunded the same way.</p>',NULL,'','1','2026-11-13 13:22:10'),(14,0,'Corporate Orders','industries',NULL,'<h2>Corporate &amp; Bulk Orders</h2><p>Dress your whole team in CustomCart. We supply everyday essentials in quantity, with consistent sizing and fast delivery across Saudi Arabia.</p><h3 id=\"corporate-teams\">Corporate teams</h3><p>Branded T-shirts and shirts for offices, events and staff uniforms, with your logo on request.</p><h3 id=\"retail-and-hospitality\">Retail &amp; hospitality</h3><p>Smart, easy-care shirts and jackets for front-of-house teams.</p><h3 id=\"gifting\">Corporate gifting</h3><p>Gift-boxed leather wallets and accessories for clients and colleagues.</p><p>Send us an inquiry with your quantities and we will reply with a quotation.</p>',NULL,'','1','2026-11-13 13:22:10');
/*!40000 ALTER TABLE `wl_cms_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_colors`
--

DROP TABLE IF EXISTS `wl_colors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_colors` (
  `color_id` int unsigned NOT NULL AUTO_INCREMENT,
  `color_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `color_code` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '#rrggbb',
  `sort_order` int NOT NULL DEFAULT '0',
  `status` enum('0','1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive,2=Deleted',
  `color_date_added` datetime NOT NULL,
  `color_date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`color_id`),
  KEY `idx_color_name` (`color_name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_colors`
--

LOCK TABLES `wl_colors` WRITE;
/*!40000 ALTER TABLE `wl_colors` DISABLE KEYS */;
INSERT INTO `wl_colors` (`color_id`, `color_name`, `color_code`, `sort_order`, `status`, `color_date_added`, `color_date_updated`) VALUES (1,'Navy','#1F2A44',1,'1','2026-11-13 13:21:20',NULL),(2,'White','#F4F4F2',2,'1','2026-11-13 13:21:20',NULL),(3,'Black','#1C1C1F',3,'1','2026-11-13 13:21:20',NULL),(4,'Sky Blue','#A9C4E4',4,'1','2026-11-13 13:21:20',NULL),(5,'Sand','#D6C3A0',5,'1','2026-11-13 13:21:20',NULL),(6,'Indigo','#33476D',6,'1','2026-11-13 13:21:20',NULL),(7,'Light Wash','#86A3C8',7,'1','2026-11-13 13:21:20',NULL),(8,'Tan','#A3622F',8,'1','2026-11-13 13:21:20',NULL);
/*!40000 ALTER TABLE `wl_colors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_countries`
--

DROP TABLE IF EXISTS `wl_countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_countries` (
  `country_id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `iso_code_2` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `iso_code_3` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive',
  PRIMARY KEY (`country_id`),
  UNIQUE KEY `uq_country_name` (`name`),
  KEY `idx_country_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=239 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_countries`
--

LOCK TABLES `wl_countries` WRITE;
/*!40000 ALTER TABLE `wl_countries` DISABLE KEYS */;
INSERT INTO `wl_countries` (`country_id`, `name`, `iso_code_2`, `iso_code_3`, `status`) VALUES (1,'Afghanistan','AF','AFG','1'),(2,'Albania','AL','ALB','1'),(3,'Algeria','DZ','DZA','1'),(4,'American Samoa','AS','ASM','1'),(5,'Andorra','AD','AND','1'),(6,'Angola','AO','AGO','1'),(7,'Anguilla','AI','AIA','1'),(8,'Antarctica','AQ','ATA','1'),(9,'Antigua and Barbuda','AG','ATG','1'),(10,'Argentina','AR','ARG','1'),(11,'Armenia','AM','ARM','1'),(12,'Aruba','AW','ABW','1'),(13,'Australia','AU','AUS','1'),(14,'Austria','AT','AUT','1'),(15,'Azerbaijan','AZ','AZE','1'),(16,'Bahamas','BS','BHS','1'),(17,'Bahrain','BH','BHR','1'),(18,'Bangladesh','BD','BGD','1'),(19,'Barbados','BB','BRB','1'),(20,'Belarus','BY','BLR','1'),(21,'Belgium','BE','BEL','1'),(22,'Belize','BZ','BLZ','1'),(23,'Benin','BJ','BEN','1'),(24,'Bermuda','BM','BMU','1'),(25,'Bhutan','BT','BTN','1'),(26,'Bolivia','BO','BOL','1'),(27,'Bosnia and Herzegowina','BA','BIH','1'),(28,'Botswana','BW','BWA','1'),(29,'Bouvet Island','BV','BVT','1'),(30,'Brazil','BR','BRA','1'),(31,'British Indian Ocean Territory','IO','IOT','1'),(32,'Brunei Darussalam','BN','BRN','1'),(33,'Bulgaria','BG','BGR','1'),(34,'Burkina Faso','BF','BFA','1'),(35,'Burundi','BI','BDI','1'),(36,'Cambodia','KH','KHM','1'),(37,'Cameroon','CM','CMR','1'),(38,'Canada','CA','CAN','1'),(39,'Cape Verde','CV','CPV','1'),(40,'Cayman Islands','KY','CYM','1'),(41,'Central African Republic','CF','CAF','1'),(42,'Chad','TD','TCD','1'),(43,'Chile','CL','CHL','1'),(44,'China','CN','CHN','1'),(45,'Christmas Island','CX','CXR','1'),(46,'Cocos (Keeling) Islands','CC','CCK','1'),(47,'Colombia','CO','COL','1'),(48,'Comoros','KM','COM','1'),(49,'Congo','CG','COG','1'),(50,'Cook Islands','CK','COK','1'),(51,'Costa Rica','CR','CRI','1'),(52,'Cote D\'Ivoire','CI','CIV','1'),(53,'Croatia','HR','HRV','1'),(54,'Cuba','CU','CUB','1'),(55,'Cyprus','CY','CYP','1'),(56,'Czech Republic','CZ','CZE','1'),(57,'Democratic Republic of Congo','CD','COD','1'),(58,'Denmark','DK','DNK','1'),(59,'Djibouti','DJ','DJI','1'),(60,'Dominica','DM','DMA','1'),(61,'Dominican Republic','DO','DOM','1'),(62,'East Timor','TP','TMP','1'),(63,'Ecuador','EC','ECU','1'),(64,'Egypt','EG','EGY','1'),(65,'El Salvador','SV','SLV','1'),(66,'Equatorial Guinea','GQ','GNQ','1'),(67,'Eritrea','ER','ERI','1'),(68,'Estonia','EE','EST','1'),(69,'Ethiopia','ET','ETH','1'),(70,'Falkland Islands (Malvinas)','FK','FLK','1'),(71,'Faroe Islands','FO','FRO','1'),(72,'Fiji','FJ','FJI','1'),(73,'Finland','FI','FIN','1'),(74,'France','FR','FRA','1'),(75,'France, Metropolitan','FX','FXX','1'),(76,'French Guiana','GF','GUF','1'),(77,'French Polynesia','PF','PYF','1'),(78,'French Southern Territories','TF','ATF','1'),(79,'Gabon','GA','GAB','1'),(80,'Gambia','GM','GMB','1'),(81,'Georgia','GE','GEO','1'),(82,'Germany','DE','DEU','1'),(83,'Ghana','GH','GHA','1'),(84,'Gibraltar','GI','GIB','1'),(85,'Greece','GR','GRC','1'),(86,'Greenland','GL','GRL','1'),(87,'Grenada','GD','GRD','1'),(88,'Guadeloupe','GP','GLP','1'),(89,'Guam','GU','GUM','1'),(90,'Guatemala','GT','GTM','1'),(91,'Guinea','GN','GIN','1'),(92,'Guinea-bissau','GW','GNB','1'),(93,'Guyana','GY','GUY','1'),(94,'Haiti','HT','HTI','1'),(95,'Heard and Mc Donald Islands','HM','HMD','1'),(96,'Hong Kong','HK','HKG','1'),(97,'Hungary','HU','HUN','1'),(98,'Iceland','IS','ISL','1'),(99,'India','IN','IND','1'),(100,'Indonesia','ID','IDN','1'),(101,'Iran (Islamic Republic of)','IR','IRN','1'),(102,'Iraq','IQ','IRQ','1'),(103,'Ireland','IE','IRL','1'),(104,'Israel','IL','ISR','1'),(105,'Italy','IT','ITA','1'),(106,'Jamaica','JM','JAM','1'),(107,'Japan','JP','JPN','1'),(108,'Jordan','JO','JOR','1'),(109,'Kazakhstan','KZ','KAZ','1'),(110,'Kenya','KE','KEN','1'),(111,'Kiribati','KI','KIR','1'),(112,'Korea, Republic of','KR','KOR','1'),(113,'Kuwait','KW','KWT','1'),(114,'Kyrgyzstan','KG','KGZ','1'),(115,'Lao People\'s Democratic Republic','LA','LAO','1'),(116,'Latvia','LV','LVA','1'),(117,'Lebanon','LB','LBN','1'),(118,'Lesotho','LS','LSO','1'),(119,'Liberia','LR','LBR','1'),(120,'Libyan Arab Jamahiriya','LY','LBY','1'),(121,'Liechtenstein','LI','LIE','1'),(122,'Lithuania','LT','LTU','1'),(123,'Luxembourg','LU','LUX','1'),(124,'Macau','MO','MAC','1'),(125,'Macedonia','MK','MKD','1'),(126,'Madagascar','MG','MDG','1'),(127,'Malawi','MW','MWI','1'),(128,'Malaysia','MY','MYS','1'),(129,'Maldives','MV','MDV','1'),(130,'Mali','ML','MLI','1'),(131,'Malta','MT','MLT','1'),(132,'Marshall Islands','MH','MHL','1'),(133,'Martinique','MQ','MTQ','1'),(134,'Mauritania','MR','MRT','1'),(135,'Mauritius','MU','MUS','1'),(136,'Mayotte','YT','MYT','1'),(137,'Mexico','MX','MEX','1'),(138,'Micronesia, Federated States of','FM','FSM','1'),(139,'Moldova, Republic of','MD','MDA','1'),(140,'Monaco','MC','MCO','1'),(141,'Mongolia','MN','MNG','1'),(142,'Montserrat','MS','MSR','1'),(143,'Morocco','MA','MAR','1'),(144,'Mozambique','MZ','MOZ','1'),(145,'Myanmar','MM','MMR','1'),(146,'Namibia','NA','NAM','1'),(147,'Nauru','NR','NRU','1'),(148,'Nepal','NP','NPL','1'),(149,'Netherlands','NL','NLD','1'),(150,'Netherlands Antilles','AN','ANT','1'),(151,'New Caledonia','NC','NCL','1'),(152,'New Zealand','NZ','NZL','1'),(153,'Nicaragua','NI','NIC','1'),(154,'Niger','NE','NER','1'),(155,'Nigeria','NG','NGA','1'),(156,'Niue','NU','NIU','1'),(157,'Norfolk Island','NF','NFK','1'),(158,'North Korea','KP','PRK','1'),(159,'Northern Mariana Islands','MP','MNP','1'),(160,'Norway','NO','NOR','1'),(161,'Oman','OM','OMN','1'),(162,'Pakistan','PK','PAK','1'),(163,'Palau','PW','PLW','1'),(164,'Panama','PA','PAN','1'),(165,'Papua New Guinea','PG','PNG','1'),(166,'Paraguay','PY','PRY','1'),(167,'Peru','PE','PER','1'),(168,'Philippines','PH','PHL','1'),(169,'Pitcairn','PN','PCN','1'),(170,'Poland','PL','POL','1'),(171,'Portugal','PT','PRT','1'),(172,'Puerto Rico','PR','PRI','1'),(173,'Reunion','RE','REU','1'),(174,'Romania','RO','ROM','1'),(175,'Russian Federation','RU','RUS','1'),(176,'Rwanda','RW','RWA','1'),(177,'Saint Kitts and Nevis','KN','KNA','1'),(178,'Saint Lucia','LC','LCA','1'),(179,'Saint Vincent and the Grenadines','VC','VCT','1'),(180,'Samoa','WS','WSM','1'),(181,'San Marino','SM','SMR','1'),(182,'Sao Tome and Principe','ST','STP','1'),(183,'Saudi Arabia','SA','SAU','1'),(184,'Senegal','SN','SEN','1'),(185,'Seychelles','SC','SYC','1'),(186,'Sierra Leone','SL','SLE','1'),(187,'Singapore','SG','SGP','1'),(188,'Slovak Republic','SK','SVK','1'),(189,'Slovenia','SI','SVN','1'),(190,'Solomon Islands','SB','SLB','1'),(191,'Somalia','SO','SOM','1'),(192,'South Africa','ZA','ZAF','1'),(193,'South Georgia &amp; South Sandwich Islands','GS','SGS','1'),(194,'Spain','ES','ESP','1'),(195,'Sri Lanka','LK','LKA','1'),(196,'St. Helena','SH','SHN','1'),(197,'St. Pierre and Miquelon','PM','SPM','1'),(198,'Sudan','SD','SDN','1'),(199,'Suriname','SR','SUR','1'),(200,'Svalbard and Jan Mayen Islands','SJ','SJM','1'),(201,'Swaziland','SZ','SWZ','1'),(202,'Sweden','SE','SWE','1'),(203,'Switzerland','CH','CHE','1'),(204,'Syrian Arab Republic','SY','SYR','1'),(205,'Taiwan','TW','TWN','1'),(206,'Tajikistan','TJ','TJK','1'),(207,'Tanzania, United Republic of','TZ','TZA','1'),(208,'Thailand','TH','THA','1'),(209,'Togo','TG','TGO','1'),(210,'Tokelau','TK','TKL','1'),(211,'Tonga','TO','TON','1'),(212,'Trinidad and Tobago','TT','TTO','1'),(213,'Tunisia','TN','TUN','1'),(214,'Turkey','TR','TUR','1'),(215,'Turkmenistan','TM','TKM','1'),(216,'Turks and Caicos Islands','TC','TCA','1'),(217,'Tuvalu','TV','TUV','1'),(218,'Uganda','UG','UGA','1'),(219,'Ukraine','UA','UKR','1'),(220,'United Arab Emirates','AE','ARE','1'),(221,'United Kingdom','GB','GBR','1'),(222,'United States','US','USA','1'),(223,'United States Minor Outlying Islands','UM','UMI','1'),(224,'Uruguay','UY','URY','1'),(225,'Uzbekistan','UZ','UZB','1'),(226,'Vanuatu','VU','VUT','1'),(227,'Vatican City State (Holy See)','VA','VAT','1'),(228,'Venezuela','VE','VEN','1'),(229,'Viet Nam','VN','VNM','1'),(230,'Virgin Islands (British)','VG','VGB','1'),(231,'Virgin Islands (U.S.)','VI','VIR','1'),(232,'Wallis and Futuna Islands','WF','WLF','1'),(233,'Western Sahara','EH','ESH','1'),(234,'Yemen','YE','YEM','1'),(235,'Yugoslavia','YU','YUG','1'),(236,'Zambia','ZM','ZMB','1'),(237,'Zimbabwe','ZW','ZWE','1');
/*!40000 ALTER TABLE `wl_countries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_coupon_customers`
--

DROP TABLE IF EXISTS `wl_coupon_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_coupon_customers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int unsigned NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `coupon_id` int unsigned NOT NULL,
  `used_count` int unsigned NOT NULL DEFAULT '0',
  `status` enum('0','1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '0=Unused,1=Used,2=Multiple use',
  `receive_on` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_coupon_customer` (`coupon_id`,`customer_id`),
  CONSTRAINT `fk_coupon_customers_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `wl_coupons` (`coupon_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_coupon_customers`
--

LOCK TABLES `wl_coupon_customers` WRITE;
/*!40000 ALTER TABLE `wl_coupon_customers` DISABLE KEYS */;
/*!40000 ALTER TABLE `wl_coupon_customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_coupons`
--

DROP TABLE IF EXISTS `wl_coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_coupons` (
  `coupon_id` int unsigned NOT NULL AUTO_INCREMENT,
  `coupon_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `coupon_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `coupon_type` enum('p','a') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'p' COMMENT 'p=Percentage,a=Amount',
  `coupon_usage` enum('single','multiple') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'single',
  `usage_count` int unsigned NOT NULL DEFAULT '1' COMMENT 'Uses per customer when coupon_usage=multiple',
  `coupon_for` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '0=All,1=Members only',
  `coupon_discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `maximum_coupon_discount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Cap for percentage coupons, 0 = none',
  `minimum_order_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('0','1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive,2=Deleted',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`coupon_id`),
  UNIQUE KEY `uq_coupon_code` (`coupon_code`),
  KEY `idx_coupon_validity` (`status`,`start_date`,`end_date`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_coupons`
--

LOCK TABLES `wl_coupons` WRITE;
/*!40000 ALTER TABLE `wl_coupons` DISABLE KEYS */;
INSERT INTO `wl_coupons` (`coupon_id`, `coupon_title`, `coupon_code`, `coupon_type`, `coupon_usage`, `usage_count`, `coupon_for`, `coupon_discount`, `maximum_coupon_discount`, `minimum_order_amount`, `start_date`, `end_date`, `status`, `date_added`) VALUES (1,'Welcome 10% off','WELCOME10','p','multiple',1,'',10.00,50.00,200.00,'2026-10-14','2027-11-13','1','2026-11-13 13:21:20'),(2,'SAR 50 off orders over SAR 400','SAVE50','a','multiple',0,'',50.00,0.00,400.00,'2026-10-14','2027-05-13','1','2026-11-13 13:21:20');
/*!40000 ALTER TABLE `wl_coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_course_enquiries`
--

DROP TABLE IF EXISTS `wl_course_enquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_course_enquiries` (
  `enquiry_id` int unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int unsigned DEFAULT NULL COMMENT 'NULL once the course is deleted',
  `course_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Title at the time of the enquiry',
  `customers_id` int unsigned DEFAULT NULL COMMENT 'wl_customers; NULL once the member is deleted',
  `customer_name` varchar(101) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_email` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_mobile` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enquiry_source` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'register' COMMENT 'register = Register button of /learning-courses',
  `registration_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Google Form the member was sent to',
  `request_token` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Register form of one page view (duplicate clicks)',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `enquiry_date` datetime NOT NULL,
  PRIMARY KEY (`enquiry_id`),
  UNIQUE KEY `uq_course_enquiry_request` (`request_token`,`course_id`),
  KEY `idx_course_enquiry_course` (`course_id`,`enquiry_date`),
  KEY `idx_course_enquiry_customer` (`customers_id`,`course_id`,`enquiry_date`),
  KEY `idx_course_enquiry_date` (`enquiry_date`),
  CONSTRAINT `fk_course_enquiry_course` FOREIGN KEY (`course_id`) REFERENCES `wl_courses` (`course_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_course_enquiry_customer` FOREIGN KEY (`customers_id`) REFERENCES `wl_customers` (`customers_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_course_enquiries`
--

LOCK TABLES `wl_course_enquiries` WRITE;
/*!40000 ALTER TABLE `wl_course_enquiries` DISABLE KEYS */;
/*!40000 ALTER TABLE `wl_course_enquiries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_courses`
--

DROP TABLE IF EXISTS `wl_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_courses` (
  `course_id` int unsigned NOT NULL AUTO_INCREMENT,
  `course_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Card tag, e.g. Theory Class, Online, Waitlist',
  `category_muted` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '1 = grey tag (e.g. Waitlist)',
  `course_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Plain text',
  `course_image` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'File name in uploads/courses/',
  `course_schedule` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Date / schedule, e.g. 12-13 Oct 2026, Every first Saturday',
  `course_duration` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g. 2 days',
  `course_language` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g. Mandarin, English, Bilingual',
  `course_mode` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'On-site, Online or Hybrid',
  `course_venue` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g. YSD Centre, Zoom',
  `registration_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Google Form address (CoursesModel::registration_url_valid())',
  `sort_order` int NOT NULL DEFAULT '0',
  `status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive',
  `course_date_added` datetime NOT NULL,
  `course_date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`course_id`),
  KEY `idx_courses_status_order` (`status`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_courses`
--

LOCK TABLES `wl_courses` WRITE;
/*!40000 ALTER TABLE `wl_courses` DISABLE KEYS */;
/*!40000 ALTER TABLE `wl_courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_currencies`
--

DROP TABLE IF EXISTS `wl_currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_currencies` (
  `currency_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL DEFAULT '',
  `code` varchar(3) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL DEFAULT '',
  `symbol_left` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `symbol_right` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `curr_image` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `decimal_place` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `value` float(15,8) NOT NULL,
  `is_default` enum('Y','N') CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL DEFAULT 'N',
  `status` int NOT NULL,
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`currency_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_currencies`
--

LOCK TABLES `wl_currencies` WRITE;
/*!40000 ALTER TABLE `wl_currencies` DISABLE KEYS */;
INSERT INTO `wl_currencies` (`currency_id`, `title`, `code`, `symbol_left`, `symbol_right`, `curr_image`, `decimal_place`, `value`, `is_default`, `status`, `date_modified`) VALUES (1,'United Statet Dollars','USD','$','','','2',1.00000000,'N',0,'2026-11-09 14:42:56'),(2,'United Arab Emirates','AED','AED','','','2',1.00000000,'N',0,'2026-11-09 14:42:56'),(3,'INR','INR','&#x20b9;','','','2',1.00000000,'N',0,'2026-11-09 14:42:56'),(4,'Saudi Riyal','SAR','SAR','','','2',1.00000000,'Y',1,'2026-11-09 14:42:56'),(5,'Singapore Dollar','SGD','S$','','','2',1.00000000,'N',0,'2026-11-09 14:42:56');
/*!40000 ALTER TABLE `wl_currencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_customers`
--

DROP TABLE IF EXISTS `wl_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_customers` (
  `customers_id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Email, used to sign in',
  `account_type` enum('user','volunteer') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user' COMMENT 'user = store customer, volunteer = volunteer account',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'password_hash(); empty for guests',
  `first_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mobile_number` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zipcode` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('0','1','2','3') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive,2=Deleted,3=Guest',
  `newsletter` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `account_created_date` datetime NOT NULL,
  `current_login` datetime DEFAULT NULL,
  `last_login_date` datetime DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `number_of_login_try` int NOT NULL DEFAULT '0',
  `block_time` datetime DEFAULT NULL COMMENT 'Sign-in refused until then',
  `reset_token` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'sha256 of the emailed token',
  `reset_expires` datetime DEFAULT NULL,
  PRIMARY KEY (`customers_id`),
  UNIQUE KEY `uq_customer_email` (`user_name`),
  KEY `idx_customer_status` (`status`),
  KEY `idx_customers_type_status` (`account_type`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_customers`
--

LOCK TABLES `wl_customers` WRITE;
/*!40000 ALTER TABLE `wl_customers` DISABLE KEYS */;
INSERT INTO `wl_customers` (`customers_id`, `user_name`, `account_type`, `password`, `first_name`, `last_name`, `mobile_number`, `address`, `city`, `state`, `country`, `zipcode`, `status`, `newsletter`, `account_created_date`, `current_login`, `last_login_date`, `ip_address`, `number_of_login_try`, `block_time`, `reset_token`, `reset_expires`) VALUES (1,'nora@example.com','user','$2y$12$DZUt8cETn9Q47Jc96vqw.OUMGw466VfGyP7k.FJ1eov6yR7FY4HlS','Nora','Al-Qahtani','+966501234567','King Fahd Road, Al Olaya','Riyadh','Riyadh Province','Saudi Arabia','12211','1','1','2026-10-24 13:21:20',NULL,NULL,'',0,NULL,NULL,NULL),(2,'faisal@example.com','user','$2y$12$DZUt8cETn9Q47Jc96vqw.OUMGw466VfGyP7k.FJ1eov6yR7FY4HlS','Faisal','Mansour','+966551234567','Prince Sultan Road, Al Rawdah','Jeddah','Makkah Province','Saudi Arabia','23432','1','0','2026-11-01 13:21:20',NULL,NULL,'',0,NULL,NULL,NULL),(3,'sara@example.com','user','','Sara','Haddad','+966541234567','Prince Mohammed bin Fahd Road, Al Faisaliyah','Dammam','Eastern Province','Saudi Arabia','32272','3','0','2026-11-13 13:21:20',NULL,NULL,'',0,NULL,NULL,NULL);
/*!40000 ALTER TABLE `wl_customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_customers_address_book`
--

DROP TABLE IF EXISTS `wl_customers_address_book`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_customers_address_book` (
  `address_id` int unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int unsigned NOT NULL,
  `first_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `landmark` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `alt_mobile` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zipcode` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_type` enum('Bill','Ship') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Ship',
  `default_status` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N',
  `reciv_date` datetime NOT NULL,
  PRIMARY KEY (`address_id`),
  KEY `idx_address_customer` (`customer_id`,`address_type`,`default_status`),
  CONSTRAINT `fk_address_customer` FOREIGN KEY (`customer_id`) REFERENCES `wl_customers` (`customers_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_customers_address_book`
--

LOCK TABLES `wl_customers_address_book` WRITE;
/*!40000 ALTER TABLE `wl_customers_address_book` DISABLE KEYS */;
INSERT INTO `wl_customers_address_book` (`address_id`, `customer_id`, `first_name`, `last_name`, `address`, `landmark`, `mobile`, `alt_mobile`, `zipcode`, `city`, `state`, `country`, `address_type`, `default_status`, `reciv_date`) VALUES (1,1,'Nora','Al-Qahtani','King Fahd Road, Al Olaya',NULL,'+966501234567',NULL,'12211','Riyadh','Riyadh Province','Saudi Arabia','Ship','Y','2026-10-24 13:21:20'),(2,2,'Faisal','Mansour','Prince Sultan Road, Al Rawdah',NULL,'+966551234567',NULL,'23432','Jeddah','Makkah Province','Saudi Arabia','Ship','Y','2026-11-01 13:21:20');
/*!40000 ALTER TABLE `wl_customers_address_book` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_donations`
--

DROP TABLE IF EXISTS `wl_donations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_donations` (
  `donation_id` int unsigned NOT NULL AUTO_INCREMENT,
  `donation_amount` decimal(12,2) NOT NULL,
  `currency_code` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SGD' COMMENT 'Store currency at the time',
  `programme` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Pages::DONATION_PROGRAMMES key',
  `programme_title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Programme name at the time',
  `full_name` varchar(61) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_anonymous` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '1=Donor asked to stay anonymous',
  `payment_method` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Pages::DONATION_PAYMENTS key',
  `payment_status` enum('pending','paid','failed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `donation_date` datetime NOT NULL,
  `donation_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`donation_id`),
  KEY `idx_donation_date` (`donation_date`),
  KEY `idx_donation_status` (`payment_status`,`donation_date`),
  KEY `idx_donation_ip` (`ip_address`,`donation_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_donations`
--

LOCK TABLES `wl_donations` WRITE;
/*!40000 ALTER TABLE `wl_donations` DISABLE KEYS */;
/*!40000 ALTER TABLE `wl_donations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_enquiry`
--

DROP TABLE IF EXISTS `wl_enquiry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_enquiry` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('1','2','3') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Contact us,2=Service,3=Product',
  `product_service` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manual_hours` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `budget` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Optional first-project budget band',
  `first_name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(220) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reply_status` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N' COMMENT 'Y=Replied,N=Not replied',
  `receive_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_enquiry_type_date` (`type`,`receive_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_enquiry`
--

LOCK TABLES `wl_enquiry` WRITE;
/*!40000 ALTER TABLE `wl_enquiry` DISABLE KEYS */;
/*!40000 ALTER TABLE `wl_enquiry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_event_documents`
--

DROP TABLE IF EXISTS `wl_event_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_event_documents` (
  `document_id` int unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int unsigned NOT NULL,
  `document_title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Name shown on the event page',
  `document_file` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'File name in uploads/event_documents/',
  `file_type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'pdf | doc | docx',
  `file_size` int unsigned NOT NULL DEFAULT '0' COMMENT 'Bytes',
  `sort_order` int NOT NULL DEFAULT '0',
  `status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive',
  `document_date_added` datetime NOT NULL,
  `document_date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`document_id`),
  UNIQUE KEY `uq_event_document_file` (`document_file`),
  KEY `idx_event_documents_event` (`event_id`,`status`,`sort_order`),
  CONSTRAINT `fk_event_document_event` FOREIGN KEY (`event_id`) REFERENCES `wl_events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_event_documents`
--

LOCK TABLES `wl_event_documents` WRITE;
/*!40000 ALTER TABLE `wl_event_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `wl_event_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_event_registrations`
--

DROP TABLE IF EXISTS `wl_event_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_event_registrations` (
  `registration_id` int unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int unsigned DEFAULT NULL COMMENT 'NULL once the event is deleted',
  `event_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Title at the time of registration',
  `customers_id` int unsigned DEFAULT NULL COMMENT 'wl_customers of a signed-in member; NULL = guest (or member deleted)',
  `registration_type` enum('guest','member') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'guest',
  `registration_mode` enum('register','waitlist') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'register' COMMENT 'Register Now or Join Waitlist',
  `full_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `preferred_session` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `participants` tinyint unsigned NOT NULL DEFAULT '1',
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `registration_status` enum('pending','confirmed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `request_token` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Form of one page view (double submits)',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `registration_date` datetime NOT NULL,
  `registration_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`registration_id`),
  UNIQUE KEY `uq_event_registration_request` (`request_token`),
  KEY `idx_event_registration_event` (`event_id`,`registration_status`),
  KEY `idx_event_registration_email` (`event_id`,`email`),
  KEY `idx_event_registration_customer` (`customers_id`),
  KEY `idx_event_registration_date` (`registration_date`),
  CONSTRAINT `fk_event_registration_customer` FOREIGN KEY (`customers_id`) REFERENCES `wl_customers` (`customers_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_event_registration_event` FOREIGN KEY (`event_id`) REFERENCES `wl_events` (`event_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_event_registrations`
--

LOCK TABLES `wl_event_registrations` WRITE;
/*!40000 ALTER TABLE `wl_event_registrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `wl_event_registrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_events`
--

DROP TABLE IF EXISTS `wl_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_events` (
  `event_id` int unsigned NOT NULL AUTO_INCREMENT,
  `event_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `friendly_url` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Page address: <events page>/<friendly_url>',
  `event_category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g. Course, Volunteer, Youth, Talk, Community',
  `event_status` enum('open','waitlist','closed','completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open' COMMENT 'Registration status',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL COMMENT 'NULL = one day',
  `date_note` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g. 2-day class',
  `event_time` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g. 9:30am – 5:00pm',
  `time_note` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g. Both days',
  `venue` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `venue_note` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `short_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Plain text (listing card, detail lead)',
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'HTML (About This Event)',
  `event_image` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'File name in uploads/events/',
  `registration_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Register Now / Join Waitlist link (empty = contact page)',
  `registration_sessions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Preferred Session choices of the registration form, one per line',
  `sort_order` int NOT NULL DEFAULT '0',
  `status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive',
  `event_date_added` datetime NOT NULL,
  `event_date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`event_id`),
  UNIQUE KEY `uq_event_friendly_url` (`friendly_url`),
  KEY `idx_events_status_date` (`status`,`start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_events`
--

LOCK TABLES `wl_events` WRITE;
/*!40000 ALTER TABLE `wl_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `wl_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_faq`
--

DROP TABLE IF EXISTS `wl_faq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_faq` (
  `faq_id` int unsigned NOT NULL AUTO_INCREMENT,
  `faq_question` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `faq_answer` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_general` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '1' COMMENT '1 = shown on the FAQ page',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive',
  `faq_date_added` datetime NOT NULL,
  PRIMARY KEY (`faq_id`),
  KEY `idx_faq_status_order` (`status`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_faq`
--

LOCK TABLES `wl_faq` WRITE;
/*!40000 ALTER TABLE `wl_faq` DISABLE KEYS */;
INSERT INTO `wl_faq` (`faq_id`, `faq_question`, `faq_answer`, `is_general`, `sort_order`, `status`, `faq_date_added`) VALUES (1,'How long does delivery take?','Standard Delivery arrives in 3-5 working days and is free on orders over SAR 250. Express Delivery arrives in 1-2 working days.','1',1,'1','2026-11-13 13:21:20'),(2,'Can I return or exchange an item?','Yes. Unworn items with their tags can be returned within 14 days. Start a return from My Account > My Orders.','1',2,'1','2026-11-13 13:21:20'),(3,'Which payment methods do you accept?','You can pay securely online by card (Mastercard, Visa and more) at checkout. All prices are in Saudi riyals (SAR) and VAT is shown before you pay.','1',3,'1','2026-11-13 13:21:20'),(4,'How do I find my size?','Each product page lists its fit. Our Size & Care Guide (on the Brochures page) has measurements for every size.','1',4,'1','2026-11-13 13:21:20'),(5,'Will the T-shirt shrink after washing?','No. The cotton is pre-shrunk; wash at 30°C inside out to keep the shape and colour.','0',1,'1','2026-11-13 13:21:20'),(6,'Is the shirt easy to iron?','Yes. The cotton poplin is easy-iron: hang it to dry and a quick warm iron is all it needs.','0',1,'1','2026-11-13 13:21:20'),(7,'Does the denim jacket run true to size?','It has a relaxed fit. Take your usual size for room to layer, or one size down for a closer fit.','0',1,'1','2026-11-13 13:21:20'),(8,'Does the wallet block RFID?','Yes. An RFID-blocking lining protects contactless cards in every slot.','0',1,'1','2026-11-13 13:21:20');
/*!40000 ALTER TABLE `wl_faq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_gallery`
--

DROP TABLE IF EXISTS `wl_gallery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_gallery` (
  `gallery_id` int unsigned NOT NULL AUTO_INCREMENT,
  `gallery_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Description of the photo, used as its alt text',
  `gallery_image` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'File name in uploads/gallery/',
  `sort_order` int NOT NULL DEFAULT '0',
  `status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive',
  `gallery_date_added` datetime NOT NULL,
  `gallery_date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`gallery_id`),
  KEY `idx_gallery_status_order` (`status`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_gallery`
--

LOCK TABLES `wl_gallery` WRITE;
/*!40000 ALTER TABLE `wl_gallery` DISABLE KEYS */;
INSERT INTO `wl_gallery` (`gallery_id`, `gallery_title`, `gallery_image`, `sort_order`, `status`, `gallery_date_added`, `gallery_date_updated`) VALUES (1,'Essentials collection','essentials-collection.jpg',1,'1','2026-11-13 13:21:20',NULL),(2,'Denim details','denim-details.jpg',2,'1','2026-11-13 13:21:20',NULL),(3,'Gift-ready packaging','gift-ready-packaging.jpg',3,'1','2026-11-13 13:21:20',NULL),(4,'Accessories flat lay','accessories-flat-lay.jpg',4,'1','2026-11-13 13:21:20',NULL);
/*!40000 ALTER TABLE `wl_gallery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_header_images`
--

DROP TABLE IF EXISTS `wl_header_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_header_images` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `header_image` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'File name in uploads/header_images/',
  `header_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `line_one` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `line_two` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `line_three` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive',
  `added_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_header_images_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_header_images`
--

LOCK TABLES `wl_header_images` WRITE;
/*!40000 ALTER TABLE `wl_header_images` DISABLE KEYS */;
INSERT INTO `wl_header_images` (`id`, `header_image`, `header_url`, `line_one`, `line_two`, `line_three`, `status`, `added_date`) VALUES (1,'hero-new-season-essentials.jpg','http://localhost/customcart/mens-clothing','NEW SEASON 2026','Everyday Essentials | Made to Last','Premium cotton T-shirts and casual shirts with a clean, modern fit. Free delivery on orders over SAR 250.','1','2026-11-13 13:21:20'),(2,'hero-denim-edit.jpg','http://localhost/customcart/premium-denim-jacket','THE DENIM EDIT','Premium Denim | Now in Light Wash','Our signature organic cotton denim jacket, finished with copper buttons and contrast stitching.','1','2026-11-13 13:21:20'),(3,'hero-crafted-accessories.jpg','http://localhost/customcart/accessories','CRAFTED ACCESSORIES','Full-Grain Leather | Gift Ready','Slim designer wallets in tan and black, delivered in a CustomCart gift box.','1','2026-11-13 13:21:20');
/*!40000 ALTER TABLE `wl_header_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_industries`
--

DROP TABLE IF EXISTS `wl_industries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_industries` (
  `industry_id` int unsigned NOT NULL AUTO_INCREMENT,
  `industry_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `friendly_url` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `industry_image` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `short_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `highlights` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'one point per line',
  `sort_order` int NOT NULL DEFAULT '0',
  `status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`industry_id`),
  UNIQUE KEY `uq_industries_url` (`friendly_url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_industries`
--

LOCK TABLES `wl_industries` WRITE;
/*!40000 ALTER TABLE `wl_industries` DISABLE KEYS */;
/*!40000 ALTER TABLE `wl_industries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_marquee`
--

DROP TABLE IF EXISTS `wl_marquee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_marquee` (
  `marquee_id` int unsigned NOT NULL AUTO_INCREMENT,
  `marquee_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `marquee_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`marquee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_marquee`
--

LOCK TABLES `wl_marquee` WRITE;
/*!40000 ALTER TABLE `wl_marquee` DISABLE KEYS */;
INSERT INTO `wl_marquee` (`marquee_id`, `marquee_text`, `marquee_url`, `sort_order`, `status`, `date_added`, `date_updated`) VALUES (1,'Free delivery on orders over SAR 250','http://localhost/customcart/products',1,'1','2026-11-13 13:21:20',NULL),(2,'New: Premium Denim Jacket now in Light Wash','http://localhost/customcart/premium-denim-jacket',2,'1','2026-11-13 13:21:20',NULL),(3,'Use code WELCOME10 for 10% off your first order','http://localhost/customcart/products',3,'1','2026-11-13 13:21:20',NULL);
/*!40000 ALTER TABLE `wl_marquee` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_materials`
--

DROP TABLE IF EXISTS `wl_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_materials` (
  `material_id` int unsigned NOT NULL AUTO_INCREMENT,
  `material_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `material_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` enum('0','1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive,2=Deleted',
  `material_date_added` datetime NOT NULL,
  `material_date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`material_id`),
  KEY `idx_material_name` (`material_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_materials`
--

LOCK TABLES `wl_materials` WRITE;
/*!40000 ALTER TABLE `wl_materials` DISABLE KEYS */;
INSERT INTO `wl_materials` (`material_id`, `material_name`, `material_image`, `sort_order`, `status`, `material_date_added`, `material_date_updated`) VALUES (1,'100% Combed Cotton',NULL,1,'1','2026-11-13 13:21:20',NULL),(2,'Cotton Poplin',NULL,2,'1','2026-11-13 13:21:20',NULL),(3,'Organic Cotton Denim',NULL,3,'1','2026-11-13 13:21:20',NULL),(4,'Full-Grain Leather',NULL,4,'1','2026-11-13 13:21:20',NULL);
/*!40000 ALTER TABLE `wl_materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_meta_tags`
--

DROP TABLE IF EXISTS `wl_meta_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_meta_tags` (
  `meta_id` int NOT NULL AUTO_INCREMENT,
  `is_fixed` enum('Y','N') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'N',
  `entity_type` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL COMMENT 'name of controllers  ',
  `entity_id` int NOT NULL DEFAULT '0',
  `page_url` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `meta_title` varchar(450) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `meta_description` varchar(650) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `meta_keyword` varchar(450) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `status` enum('0','1') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '1',
  PRIMARY KEY (`meta_id`),
  UNIQUE KEY `uq_meta_page_url` (`page_url`),
  KEY `entity_type` (`entity_type`),
  KEY `entity_id` (`entity_id`),
  KEY `idx_meta_entity` (`entity_type`,`entity_id`)
) ENGINE=InnoDB AUTO_INCREMENT=541 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_meta_tags`
--

LOCK TABLES `wl_meta_tags` WRITE;
/*!40000 ALTER TABLE `wl_meta_tags` DISABLE KEYS */;
INSERT INTO `wl_meta_tags` (`meta_id`, `is_fixed`, `entity_type`, `entity_id`, `page_url`, `meta_title`, `meta_description`, `meta_keyword`, `status`) VALUES (1,'Y','home/index',0,'home','CustomCart – Complete E-Commerce Platform','CustomCart is a complete, customizable e-commerce platform to launch, manage and grow your online store. Your Store, Your Way.','CustomCart, e-commerce platform, online store, shopping cart, customizable e-commerce','1'),(4,'Y','testimonials/index',0,'testimonials','Testimonials | CustomCart','','','1'),(6,'Y','pages/sitemap',0,'sitemap','Sitemap | CustomCart','','','1'),(13,'Y','products/index',0,'online-store','Online Store | CustomCart','','','1'),(14,'Y','products/listing',0,'products','All Products | CustomCart','','','1'),(15,'Y','cart/index',0,'cart','Your Cart | CustomCart','','','1'),(16,'Y','cart/add',0,'cart/add','Add to Cart | CustomCart','','','1'),(17,'Y','cart/checkout',0,'checkout','Checkout | CustomCart','','','1'),(19,'Y','cart/payment',0,'payment','Payment | CustomCart','','','1'),(20,'Y','cart/confirmation',0,'order-confirmation','Order Confirmation | CustomCart','','','1'),(21,'Y','users/login',0,'login','Customer Login | CustomCart','','','1'),(22,'Y','users/register',0,'register','Create an Account | CustomCart','','','1'),(23,'Y','users/logout',0,'logout','Logout | CustomCart','','','1'),(24,'Y','users/forgot_password',0,'forgot-password','Forgot Password | CustomCart','','','1'),(25,'Y','users/reset_password',0,'reset-password','Reset Password | CustomCart','','','1'),(26,'Y','members/dashboard',0,'dashboard','My Account | CustomCart','','','1'),(27,'Y','members/orders',0,'acc-order-history','Order History | My Account','','','1'),(28,'Y','members/order',0,'acc-order-invoice','Order Details | My Account','','','1'),(29,'Y','members/invoice_print',0,'invoice-print','Invoice | CustomCart','','','1'),(30,'Y','members/wishlist',0,'acc-wishlist','Wishlist | My Account','','','1'),(31,'Y','members/edit_account',0,'acc-edit','Edit Account | My Account','','','1'),(32,'Y','members/change_password',0,'change-password','Change Password | My Account','','','1'),(347,'Y','gallery/index',0,'images-gallery','Images Gallery | CustomCart','','Gallery','1'),(348,'Y','video/index',0,'videos-gallery','Video Gallery | CustomCart','','Video','1'),(444,'Y','pages/index/contactus',1,'contactus','Contact Us | CustomCart','','','1'),(445,'Y','pages/index/aboutus',2,'aboutus','About CustomCart | CustomCart','','','1'),(446,'Y','pages/index/privacy-policy',4,'privacy-policy','Privacy Policy | CustomCart','','','1'),(447,'Y','pages/index/terms-conditions',5,'terms-conditions','Terms & Conditions | CustomCart','','','1'),(448,'Y','pages/index/returns',7,'returns','Returns | CustomCart','','','1'),(449,'Y','pages/index/refunds',13,'refunds','Refunds | CustomCart','','','1'),(450,'Y','team/index',0,'our-team','Our Team | CustomCart','','','1'),(451,'Y','team/partners',0,'partners','Our Partners | CustomCart','','','1'),(453,'Y','certificates/index',0,'certificates','Certificates | CustomCart','','','1'),(454,'Y','certificates/brochure',0,'brochure','Brochures | CustomCart','','','1'),(455,'Y','news/index',0,'news-and-events','News & Events | CustomCart','','','1'),(456,'Y','pages/faq',0,'faq','Frequently Asked Questions | CustomCart','','','1'),(457,'Y','pages/send_inquiry',0,'send-inquiry','Send Inquiry | CustomCart','','','1'),(458,'Y','pages/refer_friend',0,'refer-a-friend','Refer a Friend | CustomCart','','','1'),(459,'Y','cart/guest',0,'guest-checkout','Guest Checkout | CustomCart','','','1'),(460,'Y','members/address',0,'acc-address','My Address | CustomCart','','','1'),(461,'Y','members/coupons',0,'acc-coupons','My Coupons | CustomCart','','','1'),(462,'Y','members/track_order',0,'track-order','Track Order | CustomCart','','','1'),(463,'Y','members/item_request',0,'item-request','Cancel / Return Item | CustomCart','','','1'),(528,'Y','pages/index/industries',14,'industries','Corporate & Bulk Orders | CustomCart','Branded T-shirts, shirts and gift-boxed accessories in quantity for corporate teams, retail and hospitality.','Corporate Orders','1'),(529,'Y','cart/shipping',0,'cart/shipping','Delivery Option | CustomCart','','','1'),(530,'Y','cart/invoice',0,'order-invoice','Invoice | CustomCart','','','1'),(531,'N','category/index',1,'mens-clothing','Men\'s Clothing | CustomCart','Shop premium cotton T-shirts and classic casual shirts for men at CustomCart.','Men\'s Clothing','1'),(532,'N','category/index',2,'womens-clothing','Women\'s Clothing | CustomCart','Shop women\'s wardrobe staples, including the premium denim jacket, at CustomCart.','Women\'s Clothing','1'),(533,'N','category/index',3,'accessories','Accessories | CustomCart','Designer leather wallets and accessories in full-grain leather at CustomCart.','Accessories','1'),(534,'N','products/detail',1,'premium-cotton-t-shirt','Premium Cotton T-Shirt | CustomCart','Heavyweight 100% combed cotton T-shirt in navy, white and black. Soft, shape-keeping and true to size. SAR 79.','Premium Cotton T-Shirt','1'),(535,'N','products/detail',2,'classic-casual-shirt','Classic Casual Shirt | CustomCart','Smart-casual button-down shirt in easy-iron cotton poplin, in sky blue, white and sand. SAR 149.','Classic Casual Shirt','1'),(536,'N','products/detail',3,'premium-denim-jacket','Premium Denim Jacket | CustomCart','Signature trucker jacket in 13 oz organic cotton denim with copper buttons, in indigo and light wash. SAR 299.','Premium Denim Jacket','1'),(537,'N','products/detail',4,'designer-leather-wallet','Designer Leather Wallet | CustomCart','Slim full-grain leather bifold wallet with RFID-blocking lining, in tan and black. SAR 179.','Designer Leather Wallet','1'),(538,'N','news/detail',1,'news-and-events/spring-summer-2026-collection','The Spring / Summer 2026 Collection Is Here | CustomCart','Fresh colours, lighter fabrics and our new Light Wash denim: discover the Spring / Summer 2026 edit.','News','1'),(539,'N','news/detail',2,'news-and-events/how-to-care-for-denim','How to Care for Your Denim Jacket | CustomCart','Wash less, wash cold and hang to dry: simple habits that keep denim looking new for years.','News','1'),(540,'N','news/detail',3,'news-and-events/free-delivery-over-sar-250','Free Delivery on Orders Over SAR 250 | CustomCart','Standard Delivery is now free on every order over SAR 250, anywhere in Saudi Arabia.','Event','1');
/*!40000 ALTER TABLE `wl_meta_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_news`
--

DROP TABLE IF EXISTS `wl_news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_news` (
  `news_id` int unsigned NOT NULL AUTO_INCREMENT,
  `news_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `friendly_url` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Page address: <news page>/<friendly_url>',
  `news_category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g. Announcement, Community, Course Update',
  `publish_date` date NOT NULL,
  `short_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Plain text (card excerpt)',
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'HTML (the article)',
  `news_image` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'File name in uploads/news/',
  `sort_order` int NOT NULL DEFAULT '0',
  `status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive',
  `news_date_added` datetime NOT NULL,
  `news_date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`news_id`),
  UNIQUE KEY `uq_news_friendly_url` (`friendly_url`),
  KEY `idx_news_status_date` (`status`,`publish_date`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_news`
--

LOCK TABLES `wl_news` WRITE;
/*!40000 ALTER TABLE `wl_news` DISABLE KEYS */;
INSERT INTO `wl_news` (`news_id`, `news_title`, `friendly_url`, `news_category`, `publish_date`, `short_description`, `description`, `news_image`, `sort_order`, `status`, `news_date_added`, `news_date_updated`) VALUES (1,'The Spring / Summer 2026 Collection Is Here','spring-summer-2026-collection','News','2026-11-11','Fresh colours, lighter fabrics and our new Light Wash denim: discover the Spring / Summer 2026 edit.','<p>Our Spring / Summer 2026 collection brings lighter fabrics and fresh colours to the pieces you wear most.</p><ul><li>Premium Cotton T-Shirt in navy, white and black</li><li>Classic Casual Shirt in a new sand colour</li><li>Premium Denim Jacket, now also in Light Wash</li></ul><p>Browse the full collection in the online store.</p>','spring-summer-2026-collection.jpg',1,'1','2026-11-13 13:21:20',NULL),(2,'How to Care for Your Denim Jacket','how-to-care-for-denim','News','2026-11-04','Wash less, wash cold and hang to dry: simple habits that keep denim looking new for years.','<p>Good denim gets better with age when it is cared for well.</p><ol><li>Wash only when needed, cold and inside out.</li><li>Skip the tumble dryer: hang it to dry.</li><li>Spot-clean small marks with a damp cloth.</li></ol><p>Find more tips in our Size &amp; Care Guide on the Brochures page.</p>','how-to-care-for-denim.jpg',2,'1','2026-11-13 13:21:20',NULL),(3,'Free Delivery on Orders Over SAR 250','free-delivery-over-sar-250','Event','2026-10-29','Standard Delivery is now free on every order over SAR 250, anywhere in Saudi Arabia.','<p>From today, Standard Delivery is free on every order over SAR 250. Need it sooner? Choose Express Delivery at checkout for delivery in 1-2 working days.</p>','free-delivery-over-sar-250.jpg',3,'1','2026-11-13 13:21:20',NULL);
/*!40000 ALTER TABLE `wl_news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_newsletters`
--

DROP TABLE IF EXISTS `wl_newsletters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_newsletters` (
  `subscriber_id` int unsigned NOT NULL AUTO_INCREMENT,
  `subscriber_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subscriber_email` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('1','0') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive',
  `subscribe_date` datetime NOT NULL,
  PRIMARY KEY (`subscriber_id`),
  UNIQUE KEY `uq_newsletters_email` (`subscriber_email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_newsletters`
--

LOCK TABLES `wl_newsletters` WRITE;
/*!40000 ALTER TABLE `wl_newsletters` DISABLE KEYS */;
INSERT INTO `wl_newsletters` (`subscriber_id`, `subscriber_name`, `subscriber_email`, `status`, `subscribe_date`) VALUES (1,'Nora Al-Qahtani','nora@example.com','1','2026-10-24 13:21:20'),(2,NULL,'hello.shopper@example.com','1','2026-11-10 13:21:20');
/*!40000 ALTER TABLE `wl_newsletters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_order`
--

DROP TABLE IF EXISTS `wl_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_order` (
  `order_id` int unsigned NOT NULL AUTO_INCREMENT,
  `customers_id` int unsigned NOT NULL DEFAULT '0',
  `customer_type` enum('Member','Guest') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Member',
  `invoice_number` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile_number` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `shipping_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipping_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipping_landmark` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_mobile` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipping_alt_mobile` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_zipcode` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipping_country` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipping_state` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `shipping_id` int unsigned DEFAULT NULL,
  `shipping_method` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_coupon_id` int unsigned DEFAULT NULL,
  `coupon_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coupon_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `shipping_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `delivery_min_days` smallint unsigned DEFAULT NULL COMMENT 'From the delivery method when placed',
  `delivery_max_days` smallint unsigned DEFAULT NULL COMMENT 'From the delivery method when placed',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Items, GST included, before discount and shipping',
  `vat_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'GST included in the payable amount',
  `vat_applied_cent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `price_without_tax` decimal(10,2) NOT NULL DEFAULT '0.00',
  `grand_total` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'total_amount - coupon_discount_amount + shipping_amount',
  `currency_code` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SGD',
  `currency_symbol` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'S$',
  `currency_value` decimal(14,6) NOT NULL DEFAULT '1.000000',
  `order_status` enum('Pending','Ready For Dispatch','Dispatched','Delivered','Canceled','Rejected','Return','Closed','Deleted') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `order_received_date` datetime NOT NULL,
  `order_delivery_date` datetime DEFAULT NULL,
  `payment_method` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_status` enum('Paid','Unpaid') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Unpaid',
  `payment_date` datetime DEFAULT NULL,
  `courier_company_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bill_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tracking number',
  `bill_url` varchar(455) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tracking link',
  `admin_comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `uq_order_invoice` (`invoice_number`),
  KEY `idx_order_customer` (`customers_id`,`order_status`),
  KEY `idx_order_status_date` (`order_status`,`order_received_date`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_order`
--

LOCK TABLES `wl_order` WRITE;
/*!40000 ALTER TABLE `wl_order` DISABLE KEYS */;
INSERT INTO `wl_order` (`order_id`, `customers_id`, `customer_type`, `invoice_number`, `first_name`, `last_name`, `email`, `mobile_number`, `shipping_name`, `shipping_address`, `shipping_landmark`, `shipping_mobile`, `shipping_alt_mobile`, `shipping_zipcode`, `shipping_country`, `shipping_state`, `shipping_city`, `delivery_notes`, `shipping_id`, `shipping_method`, `discount_coupon_id`, `coupon_code`, `coupon_discount_amount`, `shipping_amount`, `delivery_min_days`, `delivery_max_days`, `total_amount`, `vat_amount`, `vat_applied_cent`, `price_without_tax`, `grand_total`, `currency_code`, `currency_symbol`, `currency_value`, `order_status`, `order_received_date`, `order_delivery_date`, `payment_method`, `payment_status`, `payment_date`, `courier_company_name`, `bill_number`, `bill_url`, `admin_comment`, `ip_address`) VALUES (1,1,'Member','CC10001','Nora','Al-Qahtani','nora@example.com','+966501234567','Nora Al-Qahtani','King Fahd Road, Al Olaya',NULL,'+966501234567',NULL,'12211','Saudi Arabia','Riyadh Province','Riyadh',NULL,1,'Standard Delivery',NULL,NULL,0.00,0.00,3,5,378.00,56.70,15.00,378.00,434.70,'SAR','SAR',1.000000,'Delivered','2026-11-03 13:21:20','2026-11-07 13:21:20','online','Paid','2026-11-03 13:21:20','Swiftline Couriers',NULL,NULL,NULL,''),(2,2,'Member','CC10002','Faisal','Mansour','faisal@example.com','+966551234567','Faisal Mansour','Prince Sultan Road, Al Rawdah',NULL,'+966551234567',NULL,'23432','Saudi Arabia','Makkah Province','Jeddah',NULL,1,'Standard Delivery',1,'WELCOME10',29.80,0.00,3,5,298.00,40.23,15.00,268.20,308.43,'SAR','SAR',1.000000,'Dispatched','2026-11-11 13:21:20',NULL,'online','Paid','2026-11-11 13:21:20','Swiftline Couriers',NULL,NULL,NULL,''),(3,3,'Guest','CC10003','Sara','Haddad','sara@example.com','+966541234567','Sara Haddad','Prince Mohammed bin Fahd Road, Al Faisaliyah',NULL,'+966541234567',NULL,'32272','Saudi Arabia','Eastern Province','Dammam',NULL,1,'Standard Delivery',NULL,NULL,0.00,15.00,3,5,179.00,29.10,15.00,194.00,223.10,'SAR','SAR',1.000000,'Pending','2026-11-13 10:21:20',NULL,'online','Unpaid',NULL,NULL,NULL,NULL,NULL,'');
/*!40000 ALTER TABLE `wl_order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_orders_products`
--

DROP TABLE IF EXISTS `wl_orders_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_orders_products` (
  `orders_products_id` int unsigned NOT NULL AUTO_INCREMENT,
  `orders_id` int unsigned NOT NULL,
  `sub_order_id` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `products_id` int unsigned DEFAULT NULL,
  `stock_id` int unsigned DEFAULT NULL,
  `product_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_brand` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_color` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_color_id` int unsigned DEFAULT NULL,
  `product_color_code` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_size` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_size_id` int unsigned DEFAULT NULL,
  `product_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'File in uploads/products',
  `product_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `quantity` int unsigned NOT NULL DEFAULT '1',
  `coupon_discount_percentage` decimal(6,2) NOT NULL DEFAULT '0.00',
  `coupon_discount_amounts` decimal(10,2) NOT NULL DEFAULT '0.00',
  `product_order_status` enum('Pending','Ready For Dispatch','Dispatched','Delivered','Cancelled','Rejected','Returned','Closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `is_cancel` enum('0','1','2','3') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '0=None,1=Requested,2=Accepted,3=Rejected',
  `is_return` enum('0','1','2','3') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '0=None,1=Requested,2=Accepted,3=Rejected',
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason_comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `request_date` datetime DEFAULT NULL,
  `is_stock_back` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '1 = stock given back',
  `refund_status` enum('','Pending','Refunded','Rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `refund_amount` decimal(10,2) DEFAULT '0.00',
  `refund_date` datetime DEFAULT NULL,
  `refund_note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`orders_products_id`),
  KEY `idx_item_order` (`orders_id`),
  KEY `idx_item_product` (`products_id`),
  CONSTRAINT `fk_item_order` FOREIGN KEY (`orders_id`) REFERENCES `wl_order` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_orders_products`
--

LOCK TABLES `wl_orders_products` WRITE;
/*!40000 ALTER TABLE `wl_orders_products` DISABLE KEYS */;
INSERT INTO `wl_orders_products` (`orders_products_id`, `orders_id`, `sub_order_id`, `products_id`, `stock_id`, `product_name`, `product_code`, `product_brand`, `product_color`, `product_color_id`, `product_color_code`, `product_size`, `product_size_id`, `product_image`, `product_price`, `quantity`, `coupon_discount_percentage`, `coupon_discount_amounts`, `product_order_status`, `is_cancel`, `is_return`, `reason`, `reason_comment`, `request_date`, `is_stock_back`, `refund_status`, `refund_amount`, `refund_date`, `refund_note`) VALUES (1,1,'CC10001-1',3,26,'Premium Denim Jacket','CC-DJ-301',NULL,'Indigo',6,'#33476D','M',2,'premium-denim-jacket-indigo.jpg',299.00,1,0.00,0.00,'Delivered','0','0',NULL,NULL,NULL,'0','',0.00,NULL,NULL),(2,1,'CC10001-2',1,2,'Premium Cotton T-Shirt','CC-TS-101',NULL,'Navy',1,'#1F2A44','M',2,'premium-cotton-t-shirt-navy.jpg',79.00,1,0.00,0.00,'Delivered','0','0',NULL,NULL,NULL,'0','',0.00,NULL,NULL),(3,2,'CC10002-1',2,15,'Classic Casual Shirt','CC-SH-201',NULL,'Sky Blue',4,'#A9C4E4','L',3,'classic-casual-shirt-sky-blue.jpg',149.00,2,10.00,29.80,'Dispatched','0','0',NULL,NULL,NULL,'0','',0.00,NULL,NULL),(4,3,'CC10003-1',4,33,'Designer Leather Wallet','CC-WL-401',NULL,'Tan',8,'#A3622F','One Size',5,'designer-leather-wallet-tan.jpg',179.00,1,0.00,0.00,'Pending','0','0',NULL,NULL,NULL,'0','',0.00,NULL,NULL);
/*!40000 ALTER TABLE `wl_orders_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_product_categories`
--

DROP TABLE IF EXISTS `wl_product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_product_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `category_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_category` (`product_id`,`category_id`),
  KEY `idx_product_categories_category` (`category_id`),
  CONSTRAINT `fk_product_categories_category` FOREIGN KEY (`category_id`) REFERENCES `wl_categories` (`category_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_product_categories_product` FOREIGN KEY (`product_id`) REFERENCES `wl_products` (`products_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_product_categories`
--

LOCK TABLES `wl_product_categories` WRITE;
/*!40000 ALTER TABLE `wl_product_categories` DISABLE KEYS */;
INSERT INTO `wl_product_categories` (`id`, `product_id`, `category_id`) VALUES (1,1,1),(2,2,1),(3,3,2),(4,4,3);
/*!40000 ALTER TABLE `wl_product_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_product_colors`
--

DROP TABLE IF EXISTS `wl_product_colors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_product_colors` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `color_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_color` (`product_id`,`color_id`),
  KEY `fk_product_colors_color` (`color_id`),
  CONSTRAINT `fk_product_colors_color` FOREIGN KEY (`color_id`) REFERENCES `wl_colors` (`color_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_product_colors_product` FOREIGN KEY (`product_id`) REFERENCES `wl_products` (`products_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_product_colors`
--

LOCK TABLES `wl_product_colors` WRITE;
/*!40000 ALTER TABLE `wl_product_colors` DISABLE KEYS */;
INSERT INTO `wl_product_colors` (`id`, `product_id`, `color_id`) VALUES (1,1,1),(2,1,2),(3,1,3),(5,2,2),(4,2,4),(6,2,5),(7,3,6),(8,3,7),(10,4,3),(9,4,8);
/*!40000 ALTER TABLE `wl_product_colors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_product_sizes`
--

DROP TABLE IF EXISTS `wl_product_sizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_product_sizes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `size_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_size` (`product_id`,`size_id`),
  KEY `fk_product_sizes_size` (`size_id`),
  CONSTRAINT `fk_product_sizes_product` FOREIGN KEY (`product_id`) REFERENCES `wl_products` (`products_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_product_sizes_size` FOREIGN KEY (`size_id`) REFERENCES `wl_sizes` (`size_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_product_sizes`
--

LOCK TABLES `wl_product_sizes` WRITE;
/*!40000 ALTER TABLE `wl_product_sizes` DISABLE KEYS */;
INSERT INTO `wl_product_sizes` (`id`, `product_id`, `size_id`) VALUES (1,1,1),(2,1,2),(3,1,3),(4,1,4),(5,2,1),(6,2,2),(7,2,3),(8,2,4),(9,3,1),(10,3,2),(11,3,3),(12,3,4),(13,4,5);
/*!40000 ALTER TABLE `wl_product_sizes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_product_stock`
--

DROP TABLE IF EXISTS `wl_product_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_product_stock` (
  `stock_id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `product_color` int unsigned NOT NULL DEFAULT '0' COMMENT 'color_id, 0 = none',
  `product_size` int unsigned NOT NULL DEFAULT '0' COMMENT 'size_id, 0 = none',
  `product_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `product_discounted_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `product_discount_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `product_quantity` int NOT NULL DEFAULT '0' COMMENT 'Available',
  `used_quantity` int NOT NULL DEFAULT '0' COMMENT 'Sold',
  `inventory` int NOT NULL DEFAULT '0' COMMENT 'Low-stock threshold',
  PRIMARY KEY (`stock_id`),
  UNIQUE KEY `uq_stock_variant` (`product_id`,`product_color`,`product_size`),
  CONSTRAINT `fk_stock_product` FOREIGN KEY (`product_id`) REFERENCES `wl_products` (`products_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_product_stock`
--

LOCK TABLES `wl_product_stock` WRITE;
/*!40000 ALTER TABLE `wl_product_stock` DISABLE KEYS */;
INSERT INTO `wl_product_stock` (`stock_id`, `product_id`, `product_color`, `product_size`, `product_price`, `product_discounted_price`, `product_discount_percent`, `product_quantity`, `used_quantity`, `inventory`) VALUES (1,1,1,1,0.00,0.00,0.00,15,0,5),(2,1,1,2,0.00,0.00,0.00,14,1,5),(3,1,1,3,0.00,0.00,0.00,15,0,5),(4,1,1,4,0.00,0.00,0.00,15,0,5),(5,1,2,1,0.00,0.00,0.00,15,0,5),(6,1,2,2,0.00,0.00,0.00,15,0,5),(7,1,2,3,0.00,0.00,0.00,15,0,5),(8,1,2,4,0.00,0.00,0.00,15,0,5),(9,1,3,1,0.00,0.00,0.00,15,0,5),(10,1,3,2,0.00,0.00,0.00,15,0,5),(11,1,3,3,0.00,0.00,0.00,15,0,5),(12,1,3,4,0.00,0.00,0.00,3,0,5),(13,2,4,1,0.00,0.00,0.00,10,0,5),(14,2,4,2,0.00,0.00,0.00,10,0,5),(15,2,4,3,0.00,0.00,0.00,8,2,5),(16,2,4,4,0.00,0.00,0.00,10,0,5),(17,2,2,1,0.00,0.00,0.00,10,0,5),(18,2,2,2,0.00,0.00,0.00,10,0,5),(19,2,2,3,0.00,0.00,0.00,10,0,5),(20,2,2,4,0.00,0.00,0.00,10,0,5),(21,2,5,1,0.00,0.00,0.00,10,0,5),(22,2,5,2,0.00,0.00,0.00,10,0,5),(23,2,5,3,0.00,0.00,0.00,10,0,5),(24,2,5,4,0.00,0.00,0.00,10,0,5),(25,3,6,1,0.00,0.00,0.00,8,0,5),(26,3,6,2,0.00,0.00,0.00,7,1,5),(27,3,6,3,0.00,0.00,0.00,8,0,5),(28,3,6,4,0.00,0.00,0.00,8,0,5),(29,3,7,1,0.00,0.00,0.00,8,0,5),(30,3,7,2,0.00,0.00,0.00,8,0,5),(31,3,7,3,0.00,0.00,0.00,8,0,5),(32,3,7,4,0.00,0.00,0.00,8,0,5),(33,4,8,5,0.00,0.00,0.00,19,1,5),(34,4,3,5,0.00,0.00,0.00,4,0,5);
/*!40000 ALTER TABLE `wl_product_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_products`
--

DROP TABLE IF EXISTS `wl_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_products` (
  `products_id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int unsigned NOT NULL,
  `category_links` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'category_id and its ancestors, comma separated',
  `brand_id` int unsigned DEFAULT NULL,
  `material_id` int unsigned DEFAULT NULL,
  `product_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `friendly_url` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_alt` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `product_discounted_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `product_discount_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `display_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `display_regular_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `products_description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `products_specification` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `product_weight` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_quantity` int NOT NULL DEFAULT '0' COMMENT 'Available stock, sum of wl_product_stock',
  `is_new` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `is_hot` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `is_featured` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `is_popular` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `is_show_button` int DEFAULT '4',
  `products_viewed` int unsigned NOT NULL DEFAULT '0',
  `status` enum('0','1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive,2=Deleted',
  `is_recently_added` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `product_added_date` datetime NOT NULL,
  `product_updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`products_id`),
  UNIQUE KEY `uq_product_url` (`friendly_url`),
  UNIQUE KEY `uq_product_code` (`product_code`),
  KEY `fk_products_category` (`category_id`),
  KEY `fk_products_brand` (`brand_id`),
  KEY `fk_products_material` (`material_id`),
  KEY `idx_product_status_category` (`status`,`category_id`),
  CONSTRAINT `fk_products_brand` FOREIGN KEY (`brand_id`) REFERENCES `wl_brands` (`brand_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `wl_categories` (`category_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_products_material` FOREIGN KEY (`material_id`) REFERENCES `wl_materials` (`material_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_products`
--

LOCK TABLES `wl_products` WRITE;
/*!40000 ALTER TABLE `wl_products` DISABLE KEYS */;
INSERT INTO `wl_products` (`products_id`, `category_id`, `category_links`, `brand_id`, `material_id`, `product_name`, `friendly_url`, `product_code`, `product_alt`, `product_price`, `product_discounted_price`, `product_discount_percent`, `display_price`, `display_regular_price`, `products_description`, `products_specification`, `product_weight`, `product_quantity`, `is_new`, `is_hot`, `is_featured`, `is_popular`, `is_show_button`, `products_viewed`, `status`, `is_recently_added`, `product_added_date`, `product_updated_date`) VALUES (1,1,'1',NULL,1,'Premium Cotton T-Shirt','premium-cotton-t-shirt','CC-TS-101','Premium Cotton T-Shirt in navy',99.00,79.00,20.20,79.00,99.00,'<p>A heavyweight everyday T-shirt in 100% combed cotton. The 220 gsm jersey feels soft from the first wear, keeps its shape wash after wash and drapes with a clean, structured line.</p><ul><li>Ribbed crew neck that stays flat</li><li>Pre-shrunk, double-stitched hems</li><li>Regular fit: true to size</li></ul>','<table><tbody><tr><th>Fabric</th><td>100% combed cotton, 220 gsm</td></tr><tr><th>Fit</th><td>Regular</td></tr><tr><th>Colours</th><td>Navy, White, Black</td></tr><tr><th>Sizes</th><td>S &ndash; XL</td></tr><tr><th>Care</th><td>Machine wash 30&deg;C, inside out</td></tr></tbody></table>',NULL,167,'1','0','1','1',4,186,'1','1','2026-11-10 13:21:20',NULL),(2,1,'1',NULL,2,'Classic Casual Shirt','classic-casual-shirt','CC-SH-201','Classic Casual Shirt in sky blue',179.00,149.00,16.76,149.00,179.00,'<p>A smart-casual long-sleeve shirt in crisp cotton poplin. Wear it buttoned with chinos for the office or open over a T-shirt at the weekend.</p><ul><li>Button-down collar and chest pocket</li><li>Easy-iron cotton poplin</li><li>Curved hem: tucked or untucked</li></ul>','<table><tbody><tr><th>Fabric</th><td>100% cotton poplin</td></tr><tr><th>Fit</th><td>Tailored</td></tr><tr><th>Colours</th><td>Sky Blue, White, Sand</td></tr><tr><th>Sizes</th><td>S &ndash; XL</td></tr><tr><th>Care</th><td>Machine wash 40&deg;C, warm iron</td></tr></tbody></table>',NULL,118,'0','0','1','1',4,123,'1','0','2026-11-07 13:21:20',NULL),(3,2,'2',NULL,3,'Premium Denim Jacket','premium-denim-jacket','CC-DJ-301','Premium Denim Jacket in indigo',349.00,299.00,14.33,299.00,349.00,'<p>Our signature trucker jacket in organic cotton denim with a soft, broken-in feel. Copper buttons, contrast topstitching and two flap chest pockets give it a timeless look.</p><ul><li>13 oz organic cotton denim</li><li>Copper shank buttons</li><li>Adjustable button waist tabs</li></ul>','<table><tbody><tr><th>Fabric</th><td>Organic cotton denim, 13 oz</td></tr><tr><th>Fit</th><td>Relaxed</td></tr><tr><th>Colours</th><td>Indigo, Light Wash</td></tr><tr><th>Sizes</th><td>S &ndash; XL</td></tr><tr><th>Care</th><td>Wash cold, inside out; hang to dry</td></tr></tbody></table>',NULL,63,'1','1','1','0',4,241,'1','1','2026-11-12 13:21:20',NULL),(4,3,'3',NULL,4,'Designer Leather Wallet','designer-leather-wallet','CC-WL-401','Designer Leather Wallet in tan',179.00,0.00,0.00,179.00,179.00,'<p>A slim bifold wallet in full-grain leather, hand-finished with tonal stitching and a debossed CustomCart mark. It holds everything you need without the bulk.</p><ul><li>6 card slots and 2 note compartments</li><li>RFID-blocking lining</li><li>Gift box included</li></ul>','<table><tbody><tr><th>Material</th><td>Full-grain leather</td></tr><tr><th>Size</th><td>11.5 &times; 9 cm (closed)</td></tr><tr><th>Colours</th><td>Tan, Black</td></tr><tr><th>Care</th><td>Wipe clean; condition twice a year</td></tr></tbody></table>',NULL,23,'0','0','1','1',4,99,'1','0','2026-11-03 13:21:20',NULL);
/*!40000 ALTER TABLE `wl_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_products_faq`
--

DROP TABLE IF EXISTS `wl_products_faq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_products_faq` (
  `products_id` int unsigned NOT NULL,
  `faq_id` int unsigned NOT NULL,
  PRIMARY KEY (`products_id`,`faq_id`),
  KEY `faq_id` (`faq_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_products_faq`
--

LOCK TABLES `wl_products_faq` WRITE;
/*!40000 ALTER TABLE `wl_products_faq` DISABLE KEYS */;
INSERT INTO `wl_products_faq` (`products_id`, `faq_id`) VALUES (1,5),(2,6),(3,7),(4,8);
/*!40000 ALTER TABLE `wl_products_faq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_products_media`
--

DROP TABLE IF EXISTS `wl_products_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_products_media` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `products_id` int unsigned NOT NULL,
  `color_id` int unsigned NOT NULL DEFAULT '0' COMMENT '0 = every colour',
  `media_type` enum('photo') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'photo',
  `media` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N',
  `sort_order` int NOT NULL DEFAULT '0',
  `media_date_added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_media_product` (`products_id`,`sort_order`),
  CONSTRAINT `fk_media_product` FOREIGN KEY (`products_id`) REFERENCES `wl_products` (`products_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_products_media`
--

LOCK TABLES `wl_products_media` WRITE;
/*!40000 ALTER TABLE `wl_products_media` DISABLE KEYS */;
INSERT INTO `wl_products_media` (`id`, `products_id`, `color_id`, `media_type`, `media`, `is_default`, `sort_order`, `media_date_added`) VALUES (1,1,1,'photo','premium-cotton-t-shirt-navy.jpg','Y',1,'2026-11-13 13:21:20'),(2,1,2,'photo','premium-cotton-t-shirt-white.jpg','N',2,'2026-11-13 13:21:20'),(3,1,3,'photo','premium-cotton-t-shirt-black.jpg','N',3,'2026-11-13 13:21:20'),(4,1,0,'photo','premium-cotton-t-shirt-detail.jpg','N',4,'2026-11-13 13:21:20'),(5,2,4,'photo','classic-casual-shirt-sky-blue.jpg','Y',1,'2026-11-13 13:21:20'),(6,2,2,'photo','classic-casual-shirt-white.jpg','N',2,'2026-11-13 13:21:20'),(7,2,5,'photo','classic-casual-shirt-sand.jpg','N',3,'2026-11-13 13:21:20'),(8,2,0,'photo','classic-casual-shirt-detail.jpg','N',4,'2026-11-13 13:21:20'),(9,3,6,'photo','premium-denim-jacket-indigo.jpg','Y',1,'2026-11-13 13:21:20'),(10,3,7,'photo','premium-denim-jacket-light-wash.jpg','N',2,'2026-11-13 13:21:20'),(11,3,0,'photo','premium-denim-jacket-detail.jpg','N',3,'2026-11-13 13:21:20'),(12,4,8,'photo','designer-leather-wallet-tan.jpg','Y',1,'2026-11-13 13:21:20'),(13,4,3,'photo','designer-leather-wallet-black.jpg','N',2,'2026-11-13 13:21:20'),(14,4,0,'photo','designer-leather-wallet-detail.jpg','N',3,'2026-11-13 13:21:20');
/*!40000 ALTER TABLE `wl_products_media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_products_related`
--

DROP TABLE IF EXISTS `wl_products_related`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_products_related` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `related_id` int unsigned NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `related_date_added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_related` (`product_id`,`related_id`),
  KEY `fk_related_related` (`related_id`),
  CONSTRAINT `fk_related_product` FOREIGN KEY (`product_id`) REFERENCES `wl_products` (`products_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_related_related` FOREIGN KEY (`related_id`) REFERENCES `wl_products` (`products_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_products_related`
--

LOCK TABLES `wl_products_related` WRITE;
/*!40000 ALTER TABLE `wl_products_related` DISABLE KEYS */;
INSERT INTO `wl_products_related` (`id`, `product_id`, `related_id`, `sort_order`, `related_date_added`) VALUES (1,1,2,0,'0000-00-00 00:00:00'),(2,1,3,0,'0000-00-00 00:00:00'),(3,2,1,0,'0000-00-00 00:00:00'),(4,2,4,0,'0000-00-00 00:00:00'),(5,3,1,0,'0000-00-00 00:00:00'),(6,3,4,0,'0000-00-00 00:00:00'),(7,4,2,0,'0000-00-00 00:00:00'),(8,4,3,0,'0000-00-00 00:00:00');
/*!40000 ALTER TABLE `wl_products_related` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_reasons`
--

DROP TABLE IF EXISTS `wl_reasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_reasons` (
  `reason_id` int unsigned NOT NULL AUTO_INCREMENT,
  `reason_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason_type` enum('1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Cancel,2=Return',
  `sort_order` int NOT NULL DEFAULT '0',
  `status` enum('0','1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive,2=Deleted',
  `reason_date_added` datetime NOT NULL,
  `reason_date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`reason_id`),
  KEY `idx_reason_type` (`reason_type`,`status`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_reasons`
--

LOCK TABLES `wl_reasons` WRITE;
/*!40000 ALTER TABLE `wl_reasons` DISABLE KEYS */;
INSERT INTO `wl_reasons` (`reason_id`, `reason_name`, `reason_type`, `sort_order`, `status`, `reason_date_added`, `reason_date_updated`) VALUES (1,'Ordered by mistake','1',1,'1','2026-11-10 09:48:05',NULL),(2,'Delivery is taking too long','1',2,'1','2026-11-10 09:48:05',NULL),(3,'Wrong size','2',1,'1','2026-11-10 09:48:05',NULL),(4,'Item damaged or defective','2',2,'1','2026-11-10 09:48:05',NULL);
/*!40000 ALTER TABLE `wl_reasons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_referrals`
--

DROP TABLE IF EXISTS `wl_referrals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_referrals` (
  `referral_id` int unsigned NOT NULL AUTO_INCREMENT,
  `sender_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender_email` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `friend_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `friend_email` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `page_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `products_id` int unsigned DEFAULT NULL,
  `customers_id` int unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `referral_date` datetime NOT NULL,
  PRIMARY KEY (`referral_id`),
  KEY `referral_date` (`referral_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_referrals`
--

LOCK TABLES `wl_referrals` WRITE;
/*!40000 ALTER TABLE `wl_referrals` DISABLE KEYS */;
/*!40000 ALTER TABLE `wl_referrals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_review`
--

DROP TABLE IF EXISTS `wl_review`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_review` (
  `review_id` int unsigned NOT NULL AUTO_INCREMENT,
  `rev_type` enum('P') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'P' COMMENT 'P=Product',
  `prod_id` int unsigned NOT NULL,
  `mem_id` int unsigned DEFAULT NULL,
  `poster_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate` tinyint unsigned NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `posted_date` datetime NOT NULL,
  `status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '1=Approved,0=Pending',
  PRIMARY KEY (`review_id`),
  KEY `idx_review_product` (`prod_id`,`status`),
  CONSTRAINT `fk_review_product` FOREIGN KEY (`prod_id`) REFERENCES `wl_products` (`products_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_review`
--

LOCK TABLES `wl_review` WRITE;
/*!40000 ALTER TABLE `wl_review` DISABLE KEYS */;
INSERT INTO `wl_review` (`review_id`, `rev_type`, `prod_id`, `mem_id`, `poster_name`, `email`, `rate`, `comment`, `ip_address`, `posted_date`, `status`) VALUES (1,'P',1,1,'Nora Al-Qahtani','nora@example.com',5,'Thick, soft cotton and the navy hasn\'t faded after many washes. Ordered two more colours.','','2026-11-05 13:21:20','1'),(2,'P',3,2,'Faisal Mansour','faisal@example.com',5,'Great weight of denim and the copper buttons look premium. Fits exactly as described.','','2026-11-08 13:21:20','1'),(3,'P',4,NULL,'Sara Haddad','sara@example.com',4,'Beautiful leather and nicely gift-boxed. Slim enough for a front pocket.','','2026-11-11 13:21:20','1');
/*!40000 ALTER TABLE `wl_review` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_services`
--

DROP TABLE IF EXISTS `wl_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_services` (
  `service_id` int unsigned NOT NULL AUTO_INCREMENT,
  `service_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ServicesModel::TYPES key',
  `service_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'HTML (basic editor)',
  `service_image` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'File name in uploads/services/',
  `box1_heading` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `box1` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'HTML (basic editor)',
  `box2_heading` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `box2` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'HTML (basic editor)',
  `box3_heading` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `box3` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'HTML (basic editor)',
  `box4_heading` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `box4` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'HTML (basic editor)',
  `box5_heading` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `box5` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'HTML (basic editor)',
  `sort_order` int NOT NULL DEFAULT '0',
  `status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive',
  `service_date_added` datetime NOT NULL,
  `service_date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`service_id`),
  KEY `idx_services_type_status_order` (`service_type`,`status`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_services`
--

LOCK TABLES `wl_services` WRITE;
/*!40000 ALTER TABLE `wl_services` DISABLE KEYS */;
/*!40000 ALTER TABLE `wl_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_sessions`
--

DROP TABLE IF EXISTS `wl_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_sessions` (
  `id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` int unsigned NOT NULL DEFAULT '0',
  `data` blob NOT NULL,
  PRIMARY KEY (`id`,`ip_address`),
  KEY `ci_sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_sessions`
--

LOCK TABLES `wl_sessions` WRITE;
/*!40000 ALTER TABLE `wl_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `wl_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_shipping`
--

DROP TABLE IF EXISTS `wl_shipping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_shipping` (
  `shipping_id` int unsigned NOT NULL AUTO_INCREMENT,
  `shipping_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipment_rate` decimal(10,2) NOT NULL DEFAULT '0.00',
  `free_above` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Free when the cart reaches this amount, 0 = never',
  `delivery_min_days` smallint unsigned DEFAULT '3' COMMENT 'Expected delivery: from (days after the order)',
  `delivery_max_days` smallint unsigned DEFAULT '5' COMMENT 'Expected delivery: to (days after the order)',
  `is_default` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `status` enum('0','1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive,2=Deleted',
  `added_date` datetime NOT NULL,
  PRIMARY KEY (`shipping_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_shipping`
--

LOCK TABLES `wl_shipping` WRITE;
/*!40000 ALTER TABLE `wl_shipping` DISABLE KEYS */;
INSERT INTO `wl_shipping` (`shipping_id`, `shipping_type`, `shipment_rate`, `free_above`, `delivery_min_days`, `delivery_max_days`, `is_default`, `sort_order`, `status`, `added_date`) VALUES (1,'Standard Delivery',15.00,250.00,3,5,'1',1,'1','2026-11-12 10:24:50'),(2,'Express Delivery',35.00,0.00,1,2,'0',2,'1','2026-11-12 11:52:59');
/*!40000 ALTER TABLE `wl_shipping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_sizes`
--

DROP TABLE IF EXISTS `wl_sizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_sizes` (
  `size_id` int unsigned NOT NULL AUTO_INCREMENT,
  `size_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` enum('0','1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive,2=Deleted',
  `size_date_added` datetime NOT NULL,
  `size_date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`size_id`),
  KEY `idx_size_name` (`size_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_sizes`
--

LOCK TABLES `wl_sizes` WRITE;
/*!40000 ALTER TABLE `wl_sizes` DISABLE KEYS */;
INSERT INTO `wl_sizes` (`size_id`, `size_name`, `sort_order`, `status`, `size_date_added`, `size_date_updated`) VALUES (1,'S',1,'1','2026-11-13 13:21:20',NULL),(2,'M',2,'1','2026-11-13 13:21:20',NULL),(3,'L',3,'1','2026-11-13 13:21:20',NULL),(4,'XL',4,'1','2026-11-13 13:21:20',NULL),(5,'One Size',5,'1','2026-11-13 13:21:20',NULL);
/*!40000 ALTER TABLE `wl_sizes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_states`
--

DROP TABLE IF EXISTS `wl_states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_states` (
  `state_id` int unsigned NOT NULL AUTO_INCREMENT,
  `country_id` int unsigned NOT NULL,
  `state_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive',
  PRIMARY KEY (`state_id`),
  UNIQUE KEY `uq_states_country_name` (`country_id`,`state_name`),
  KEY `idx_states_status` (`status`),
  CONSTRAINT `fk_states_country` FOREIGN KEY (`country_id`) REFERENCES `wl_countries` (`country_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_states`
--

LOCK TABLES `wl_states` WRITE;
/*!40000 ALTER TABLE `wl_states` DISABLE KEYS */;
INSERT INTO `wl_states` (`state_id`, `country_id`, `state_name`, `status`) VALUES (1,183,'Eastern Province','1'),(2,183,'Riyadh Province','1'),(3,183,'Makkah Province','1'),(4,183,'Madinah Province','1'),(5,183,'Qassim Province','1'),(6,183,'Asir Province','1'),(7,183,'Tabuk Province','1'),(8,183,'Hail Province','1'),(9,183,'Northern Borders Province','1'),(10,183,'Jazan Province','1'),(11,183,'Najran Province','1'),(12,183,'Al Bahah Province','1'),(13,183,'Al Jawf Province','1'),(18,99,'Delhi','1');
/*!40000 ALTER TABLE `wl_states` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_stories`
--

DROP TABLE IF EXISTS `wl_stories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_stories` (
  `story_id` int unsigned NOT NULL AUTO_INCREMENT,
  `story_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Card title',
  `friendly_url` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Page address: <stories page>/<friendly_url>',
  `story_label` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Programme label, e.g. Community Care',
  `story_quote` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Heading of the story page and the featured card (empty = the title)',
  `publish_date` date NOT NULL,
  `short_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Plain text (summary)',
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'HTML (The Story)',
  `highlight_quote` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Plain text (.story-highlight)',
  `highlight_cite` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g. Volunteer, Bedok',
  `author_role` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author_photo` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'File name in uploads/stories/',
  `story_image` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'File name in uploads/stories/',
  `sort_order` int NOT NULL DEFAULT '0',
  `status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive',
  `story_date_added` datetime NOT NULL,
  `story_date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`story_id`),
  UNIQUE KEY `uq_story_friendly_url` (`friendly_url`),
  KEY `idx_stories_status_order` (`status`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_stories`
--

LOCK TABLES `wl_stories` WRITE;
/*!40000 ALTER TABLE `wl_stories` DISABLE KEYS */;
/*!40000 ALTER TABLE `wl_stories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_team`
--

DROP TABLE IF EXISTS `wl_team`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_team` (
  `team_id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `designation` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `team_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Key of TeamModel::TYPES',
  `friendly_url` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Profile address: our-team/<friendly_url>',
  `photo` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'File name in uploads/team/',
  `biography` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'HTML (basic editor)',
  `areas_of_focus` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `experience` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `community_involvement` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `about` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'HTML (basic editor)',
  `responsibilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'One per line',
  `community_contribution` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'HTML (basic editor)',
  `focus_tags` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'One per line',
  `sort_order` int NOT NULL DEFAULT '0',
  `status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive',
  `team_date_added` datetime NOT NULL,
  `team_date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`team_id`),
  UNIQUE KEY `uq_team_friendly_url` (`friendly_url`),
  KEY `idx_team_status_order` (`status`,`sort_order`),
  KEY `idx_team_type` (`team_type`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_team`
--

LOCK TABLES `wl_team` WRITE;
/*!40000 ALTER TABLE `wl_team` DISABLE KEYS */;
INSERT INTO `wl_team` (`team_id`, `name`, `designation`, `team_type`, `friendly_url`, `photo`, `biography`, `areas_of_focus`, `experience`, `community_involvement`, `about`, `responsibilities`, `community_contribution`, `focus_tags`, `sort_order`, `status`, `team_date_added`, `team_date_updated`) VALUES (1,'Omar Haddad','Founder & CEO','team','omar-haddad','omar-haddad.jpg','Omar founded CustomCart to make quality everyday clothing easy to buy online, with honest prices and fast delivery.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'1','2026-11-13 13:21:20',NULL),(2,'Lina Farouk','Head of Merchandising','team','lina-farouk','lina-farouk.jpg','Lina chooses every fabric and fit in the collection, working directly with our mills and workshops.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'1','2026-11-13 13:21:20',NULL),(3,'Yusuf Rahman','Customer Experience Lead','team','yusuf-rahman','yusuf-rahman.jpg','Yusuf and his team answer every message, manage returns and make sure each order arrives on time.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,'1','2026-11-13 13:21:20',NULL),(4,'Swiftline Couriers','Delivery partner','partner','swiftline-couriers','swiftline-couriers.jpg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,4,'1','2026-11-13 13:21:20',NULL),(5,'SilkRoute Payments','Payment partner','partner','silkroute-payments','silkroute-payments.jpg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,5,'1','2026-11-13 13:21:20',NULL),(6,'Atlas Cotton Mills','Fabric partner','partner','atlas-cotton-mills','atlas-cotton-mills.jpg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,6,'1','2026-11-13 13:21:20',NULL);
/*!40000 ALTER TABLE `wl_team` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_testimonial`
--

DROP TABLE IF EXISTS `wl_testimonial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_testimonial` (
  `testimonial_id` int unsigned NOT NULL AUTO_INCREMENT,
  `poster_name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `poster_role` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Company / Role',
  `email` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'File name in uploaded_files/testimonial/',
  `friendly_url` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `testimonial_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `posted_date` datetime NOT NULL,
  `submitted_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Set for public submissions',
  `status` enum('1','0') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive',
  PRIMARY KEY (`testimonial_id`),
  UNIQUE KEY `uq_testimonial_friendly_url` (`friendly_url`),
  KEY `idx_testimonial_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_testimonial`
--

LOCK TABLES `wl_testimonial` WRITE;
/*!40000 ALTER TABLE `wl_testimonial` DISABLE KEYS */;
INSERT INTO `wl_testimonial` (`testimonial_id`, `poster_name`, `poster_role`, `email`, `photo`, `friendly_url`, `testimonial_description`, `posted_date`, `submitted_ip`, `status`) VALUES (1,'Nora Al-Qahtani','Verified customer, Riyadh','nora@example.com','nora-alqahtani.jpg','nora-al-qahtani','Ordering was quick, delivery arrived in two days and the quality is better than I expected. CustomCart is now my go-to for basics.','2026-11-06 13:21:20','','1'),(2,'Faisal Mansour','Verified customer, Jeddah','faisal@example.com','faisal-mansour.jpg','faisal-mansour','The size guide was spot on and tracking my order was easy from my account. Excellent service from start to finish.','2026-11-09 13:21:20','','1'),(3,'Sara Haddad','Verified customer, Dammam','sara@example.com','sara-haddad.jpg','sara-haddad','I bought the leather wallet as a gift. It came beautifully packaged and the leather feels premium.','2026-11-12 13:21:20','','1');
/*!40000 ALTER TABLE `wl_testimonial` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_video`
--

DROP TABLE IF EXISTS `wl_video`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_video` (
  `video_id` int unsigned NOT NULL AUTO_INCREMENT,
  `video_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `video_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'YouTube or Vimeo address as entered',
  `video_category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g. Basics, Technique',
  `video_duration` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'mm:ss or h:mm:ss',
  `video_image` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Thumbnail in uploads/video/ (empty = the YouTube thumbnail)',
  `sort_order` int NOT NULL DEFAULT '0',
  `status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1=Active,0=Inactive',
  `video_date_added` datetime NOT NULL,
  `video_date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`video_id`),
  KEY `idx_video_status_order` (`status`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_video`
--

LOCK TABLES `wl_video` WRITE;
/*!40000 ALTER TABLE `wl_video` DISABLE KEYS */;
/*!40000 ALTER TABLE `wl_video` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_volunteers`
--

DROP TABLE IF EXISTS `wl_volunteers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_volunteers` (
  `volunteer_id` int unsigned NOT NULL AUTO_INCREMENT,
  `customers_id` int unsigned NOT NULL COMMENT 'The volunteer account (wl_customers)',
  `age_group` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `occupation` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interests` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Comma-separated VolunteerModel::INTERESTS keys',
  `training_session` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Config\\Volunteer::$trainingSessions key',
  `experience` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `availability` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Comma-separated VolunteerModel::AVAILABILITY keys',
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `skills` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `photo` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'File name in uploads/volunteers/',
  `application_status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `date_added` datetime NOT NULL,
  `date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`volunteer_id`),
  UNIQUE KEY `uq_volunteer_customer` (`customers_id`),
  KEY `idx_volunteer_application` (`application_status`),
  CONSTRAINT `fk_volunteer_customer` FOREIGN KEY (`customers_id`) REFERENCES `wl_customers` (`customers_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_volunteers`
--

LOCK TABLES `wl_volunteers` WRITE;
/*!40000 ALTER TABLE `wl_volunteers` DISABLE KEYS */;
/*!40000 ALTER TABLE `wl_volunteers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wl_wishlists`
--

DROP TABLE IF EXISTS `wl_wishlists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wl_wishlists` (
  `wishlists_id` int unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `wishlists_date_added` datetime NOT NULL,
  PRIMARY KEY (`wishlists_id`),
  UNIQUE KEY `uq_wishlist` (`customer_id`,`product_id`),
  KEY `fk_wishlist_product` (`product_id`),
  CONSTRAINT `fk_wishlist_customer` FOREIGN KEY (`customer_id`) REFERENCES `wl_customers` (`customers_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_wishlist_product` FOREIGN KEY (`product_id`) REFERENCES `wl_products` (`products_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wl_wishlists`
--

LOCK TABLES `wl_wishlists` WRITE;
/*!40000 ALTER TABLE `wl_wishlists` DISABLE KEYS */;
/*!40000 ALTER TABLE `wl_wishlists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'customcart'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
