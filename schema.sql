-- MariaDB dump 10.19  Distrib 10.11.1-MariaDB, for osx10.15 (x86_64)
--
-- Host: localhost    Database: support
-- ------------------------------------------------------
-- Server version	10.11.1-MariaDB

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
-- Table structure for table `admin_logs`
--

DROP TABLE IF EXISTS `admin_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `admin_id` bigint(20) NOT NULL COMMENT 'Admin ID',
  `name` varchar(255) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `value` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `[index:relation][admin_logs.admin_id][admins.id]` (`admin_id`),
  KEY `[index][name:admin_id:type:created_at:updated_at]` (`name`,`admin_id`,`type`,`created_at`,`updated_at`) USING BTREE,
  KEY `[index][name:admin_id:type]` (`name`,`admin_id`,`type`) USING BTREE,
  CONSTRAINT `[index:relation][admin_logs.admin_id][admins.id]` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Admin Logs';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `admin_metas`
--

DROP TABLE IF EXISTS `admin_metas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_metas` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `admin_id` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` longtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `[unique][name:admin_id]` (`name`,`admin_id`) USING BTREE COMMENT 'Unique name & admin_id',
  KEY `[index:relation][admin_metas.admin_id][admins.id]` (`admin_id`) USING BTREE COMMENT 'Index Relational admins.id',
  CONSTRAINT `[index:relation][admin_metas.admin_id][admins.id]` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Admin Metadata By Admin ID';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'Admin ID',
  `username` varchar(255) NOT NULL COMMENT 'Admin Username',
  `email` varchar(320) NOT NULL COMMENT 'Admin Email',
  `password` varchar(255) NOT NULL COMMENT 'User password password_has(hash_hmac(''sha256'', admins.security_key)), PASSWORD_BCRYPT)',
  `type` varchar(255) NOT NULL COMMENT 'User type / role /permissions',
  `status` varchar(255) DEFAULT NULL COMMENT 'User status, null will be set as unknown',
  `first_name` varchar(255) NOT NULL COMMENT 'User first name',
  `last_name` varchar(255) DEFAULT NULL COMMENT 'User last name',
  `security_key` varchar(255) NOT NULL COMMENT 'Security key to used as various method',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Created At Time',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Updated at',
  `deleted_at` datetime DEFAULT NULL COMMENT 'User deleted at',
  PRIMARY KEY (`id`),
  UNIQUE KEY `[unique][username]` (`username`),
  UNIQUE KEY `[unique][email]` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Admin Users';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `carts`
--

DROP TABLE IF EXISTS `carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `carts` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL COMMENT 'Users id',
  `product_variant_id` bigint(20) NOT NULL COMMENT 'Product ID',
  `quantity` bigint(20) NOT NULL COMMENT 'Product Quantity',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Cart created at',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `[unique][member_id:product_id]` (`user_id`,`product_variant_id`) USING BTREE COMMENT 'Determine to show on cart',
  KEY `[index:relation][carts.product_variant_id][product_variants.id]` (`product_variant_id`) USING BTREE COMMENT 'Index Relational products.id',
  KEY `[index:relation][carts.user_id][users.id]` (`user_id`) USING BTREE COMMENT 'Index relational members.id',
  KEY `[index][created_at:product_id:user_id:updated_at:quantity]` (`created_at`,`product_variant_id`,`user_id`,`updated_at`,`quantity`) USING BTREE COMMENT 'For sorting data carts',
  CONSTRAINT `[index:relation][carts.user_id][users.id]` FOREIGN KEY (`user_id`) REFERENCES `site_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Carts For Members';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `currencies`
--

DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `currencies` (
  `code` varchar(5) NOT NULL,
  `display_code` varchar(255) DEFAULT NULL,
  `as_prefix` tinyint(1) NOT NULL DEFAULT 1,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`code`),
  KEY `[index][name]` (`name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Currency List';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `currency_rates`
--

DROP TABLE IF EXISTS `currency_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `currency_rates` (
  `from_currency` varchar(5) NOT NULL,
  `to_currency` varchar(5) NOT NULL,
  `rates` float NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`from_currency`,`to_currency`) COMMENT 'Primary Key (from_currency & to_currency)',
  UNIQUE KEY `[unique][from_currency:to_currency]` (`from_currency`,`to_currency`) USING BTREE COMMENT 'Unique Currency Rates',
  KEY `[index:relation][currency_rates:from_currency][currency.code]` (`from_currency`) USING BTREE COMMENT 'Index Relational currency_rates.from_currency to currencies.code',
  KEY `[index:relation][currency_rates:to_currency][currency.code]` (`to_currency`) USING BTREE COMMENT 'Index Relational currency_rates.to_currency to currencies.code',
  KEY `[index][rates]` (`rates`) COMMENT 'Index Rates',
  CONSTRAINT `[index:relation][currency_rates:from_currency][currency.code]` FOREIGN KEY (`from_currency`) REFERENCES `currencies` (`code`) ON UPDATE CASCADE,
  CONSTRAINT `[index:relation][currency_rates:to_currency][currency.code]` FOREIGN KEY (`to_currency`) REFERENCES `currencies` (`code`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Currency Rates';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `channel` varchar(255) NOT NULL DEFAULT 'default',
  `level` varchar(20) NOT NULL,
  `message` mediumtext NOT NULL,
  `context` mediumtext DEFAULT NULL,
  `extra` mediumtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `[index][level]` (`level`) COMMENT 'Log Level',
  KEY `[index][level:created_at]` (`level`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logging';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `options`
--

DROP TABLE IF EXISTS `options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `options` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Option Unique Name',
  `value` text NOT NULL,
  `autoload` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `[unique][name]` (`name`) USING BTREE,
  KEY `[index][name:autoload]` (`name`,`autoload`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sistem Wide Options';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_list`
--

DROP TABLE IF EXISTS `order_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_list` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) NOT NULL COMMENT 'Order ID',
  `product_variant_id` bigint(20) NOT NULL COMMENT 'Product Variant ID',
  `quantity` bigint(20) NOT NULL COMMENT 'Item Quantity',
  `price_amount` float NOT NULL COMMENT 'Price per order',
  `discount` float NOT NULL COMMENT 'Discount per product',
  `notes` text NOT NULL COMMENT 'Per-product notes',
  PRIMARY KEY (`id`),
  UNIQUE KEY `[unique][order_id:product_variant_id]` (`order_id`) COMMENT 'Unique order by product order to prevent duplication',
  KEY `[index:relation][order_list.order_id][orders.id]` (`order_id`) USING BTREE COMMENT 'Index Relational orders.id',
  KEY `[index:relation][order_list.product_variant_id][pv.id]` (`product_variant_id`) COMMENT 'Index Relational product_variants.id',
  CONSTRAINT `[index:relation][order_list.order_id][orders.id]` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `[index:relation][order_list.product_variant_id][pv.id]` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Order item lists';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL COMMENT 'Unique Invoice Code',
  `user_id` bigint(20) NOT NULL COMMENT 'User ID',
  `name` varchar(255) NOT NULL COMMENT 'Invoice Name',
  `status` varchar(255) NOT NULL DEFAULT 'unpaid' COMMENT 'Order Status',
  `coupon_code` varchar(255) DEFAULT NULL COMMENT 'Coupon code used for purchase order',
  `order_amount_currency_code` varchar(5) DEFAULT NULL COMMENT 'The currency used by order amounts null used product name',
  `order_amount` bigint(20) DEFAULT NULL COMMENT 'Null will be calculate order list',
  `paid_amount` float DEFAULT 0 COMMENT 'Paid Ammount By Currency Code',
  `discount_amount` float DEFAULT 0,
  `metadata` text DEFAULT NULL COMMENT 'Order metadata (eg: shipment)',
  `last_payment_amount` float DEFAULT 0 COMMENT 'Last Payment Amount',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Order Time',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_payment_at` datetime DEFAULT NULL COMMENT 'Last Payment Time',
  `cancelled_at` datetime DEFAULT NULL COMMENT 'Cancelled Time',
  PRIMARY KEY (`id`),
  UNIQUE KEY `[unique][code]` (`code`) COMMENT 'Unique Code',
  KEY `[index][name]` (`name`),
  KEY `[index][status:user_id]` (`status`,`user_id`) USING BTREE,
  KEY `[index:relation][orders.user_id][users.id]` (`user_id`) USING BTREE,
  CONSTRAINT `[index:relation][orders.user_id][users.id]` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Order Lists';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `post_metas`
--

DROP TABLE IF EXISTS `post_metas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `post_metas` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `[unique][name:post_id]` (`name`,`post_id`) COMMENT 'unique name by post_id',
  KEY `[index:relation][post_metas.post_id][posts.id]` (`post_id`),
  CONSTRAINT `[index:relation][post_metas.post_id][posts.id]` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Post Metadata';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `posts` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT 'Site title',
  `slug` varchar(255) NOT NULL COMMENT 'Site Slug',
  `author` bigint(20) DEFAULT NULL COMMENT 'Posts Admin Author',
  `type` varchar(255) NOT NULL COMMENT 'Posts type',
  `status` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `parent_id` bigint(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `published_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `[unique][slug]` (`slug`) COMMENT 'Posts Slug',
  KEY `[index:relation][posts.author][admins.id]` (`author`) USING BTREE COMMENT 'Index Relational posts.author -> admins.id',
  KEY `[index:relation][posts.parent_id][posts.id]` (`parent_id`) USING BTREE COMMENT 'Index Relational posts.id',
  KEY `[index][type:status:author:created_at:published_at]` (`type`,`status`,`author`,`created_at`,`published_at`) USING BTREE COMMENT 'Render Indexing Posts',
  KEY `[index][title:type:status]` (`title`,`type`,`status`),
  CONSTRAINT `[index:relation][posts.author][admins.id]` FOREIGN KEY (`author`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `[index:relation][posts.parent_id][posts.id]` FOREIGN KEY (`parent_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Posts';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_attachments`
--

DROP TABLE IF EXISTS `product_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_attachments` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `product_variant_id` bigint(20) NOT NULL COMMENT 'The product variants ID',
  `name` varchar(255) NOT NULL,
  `path` varchar(2048) NOT NULL,
  `mime_type` varchar(255) NOT NULL,
  `size` bigint(20) NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `metadata` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `[unique][path]` (`path`) USING HASH,
  KEY `[index:relation][product_attachments.created_by][admins.id]` (`created_by`),
  KEY `[index][name]` (`name`),
  KEY `[index][name:pv_id:mime_type:size:created_by:created_at]` (`name`,`product_variant_id`,`mime_type`,`size`,`created_by`,`created_at`),
  KEY `[index:relation][pv.product_id][pv.id]` (`product_variant_id`),
  CONSTRAINT `[index:relation][product_attachments.created_by][admins.id]` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `[index:relation][pv.product_id][pv.id]` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='The product variants atttachments';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_categories` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) DEFAULT NULL COMMENT 'Product Parent ID',
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `[unique][slug]` (`slug`) COMMENT 'Product Category Slug',
  KEY `[index][name]` (`name`) USING BTREE COMMENT 'Product Category Display Name',
  KEY `[index:relation][pc.parent_id][pc.id]` (`parent_id`),
  CONSTRAINT `[index:relation][pc.parent_id][pc.id]` FOREIGN KEY (`parent_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Product Categories';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_metas`
--

DROP TABLE IF EXISTS `product_metas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_metas` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `product_id` bigint(20) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `[unique][name:product_id]` (`name`,`product_id`) USING BTREE COMMENT 'unique name by product_id',
  KEY `[index:relation][product_metas.product_id][products.id]` (`product_id`),
  CONSTRAINT `[index:relation][product_metas.product_id][products.id]` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Product Metas By Product ID';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_types`
--

DROP TABLE IF EXISTS `product_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_types` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'The product type name',
  `description` text NOT NULL COMMENT 'Product Type Description',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `[index][name]` (`name`) USING BTREE COMMENT 'Unique product type name'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Product Types';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_variant_metas`
--

DROP TABLE IF EXISTS `product_variant_metas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_variant_metas` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` int(11) NOT NULL,
  `product_variant_id` bigint(20) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `[unique][name:product_variant_id]` (`name`,`product_variant_id`) COMMENT 'unique name by product_variant_id',
  KEY `[index:relation][pvm.pvid][pv.id]` (`product_variant_id`),
  CONSTRAINT `[index:relation][pvm.pvid][pv.id]` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Product Variants Metadata';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_variants` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) NOT NULL COMMENT 'Product ID',
  `sku` varchar(255) NOT NULL COMMENT 'Unique Stock Keeping Units',
  `name` varchar(255) NOT NULL COMMENT 'The product name',
  `type` bigint(20) NOT NULL COMMENT 'Product Types',
  `description` text NOT NULL COMMENT 'Product Description',
  `initial_price` float NOT NULL COMMENT 'The initial prices',
  `price` float NOT NULL COMMENT 'Current  Product Price By Currency',
  `initial_stocks` bigint(20) NOT NULL COMMENT 'Initial Stocks',
  `stocks` bigint(20) NOT NULL COMMENT 'The stocks, Null is always available',
  `unit` varchar(255) NOT NULL COMMENT 'The units',
  `unit_value` varchar(255) NOT NULL COMMENT 'The unit value',
  `minimum_order` bigint(20) DEFAULT NULL COMMENT 'Minimum orders (This must be less or equal than maximum orders)',
  `maximum_order` bigint(20) DEFAULT NULL COMMENT 'Maximum orders (This must be greater or equal than minimum orders)',
  `in_stock` tinyint(1) DEFAULT NULL COMMENT 'Even stocks & ordered avilable & in_stock is unavailable it will be unavailable',
  `status` varchar(255) NOT NULL DEFAULT 'draft' COMMENT 'Status only published, draft, pending, review, deleted',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Product created at',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp() COMMENT 'Product updated at',
  `deleted_at` datetime DEFAULT NULL COMMENT 'Product Deleted At',
  `expired_at` datetime DEFAULT NULL COMMENT 'Null will always available',
  PRIMARY KEY (`id`),
  UNIQUE KEY `[unique][sku]` (`sku`) USING BTREE COMMENT 'Unique Stock Keeping Unit',
  KEY `[index][product_id:status:in_(stock(s)):(expired|created):type]` (`product_id`,`status`,`in_stock`,`stocks`,`expired_at`,`created_at`,`type`),
  KEY `[index:relation][product_variants.type][product_types.id]` (`type`) USING BTREE,
  KEY `[index:relation][product_variants.product_id][products.id]` (`product_id`) USING BTREE,
  CONSTRAINT `[index:relation][product_variants.product_id][products.id]` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `[index:relation][product_variants.type][product_types.id]` FOREIGN KEY (`type`) REFERENCES `product_types` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Product Variants';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'Product ID',
  `sku` varchar(255) NOT NULL COMMENT 'Unique Stock Keeping Units',
  `name` varchar(255) NOT NULL COMMENT 'The product name',
  `description` text DEFAULT NULL COMMENT 'Product Description',
  `category_id` bigint(20) NOT NULL COMMENT 'Product Category',
  `price_currency` varchar(5) NOT NULL COMMENT 'Determine Currency Code for pricing',
  `accepted_currencies` text DEFAULT NULL COMMENT 'List of accepted currencies. Null will accepted all currencies',
  `status` varchar(255) NOT NULL COMMENT 'Status only published, draft, pending, review, deleted',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Product created at',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp() COMMENT 'Product updated at',
  `deleted_at` datetime DEFAULT NULL COMMENT 'Product deleted at',
  `expired_at` datetime DEFAULT NULL COMMENT 'Null will always available',
  PRIMARY KEY (`id`),
  UNIQUE KEY `[unique][sku]` (`sku`),
  KEY `[index:relation][products.price_currency][currencies.code]` (`price_currency`),
  KEY `[index][status:created_at:expired_at]` (`status`,`created_at`,`expired_at`),
  KEY `[index:relation][products.category_id][product_categories.id]` (`category_id`),
  CONSTRAINT `[index:relation][products.category_id][product_categories.id]` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `[index:relation][products.price_currency][currencies.code]` FOREIGN KEY (`price_currency`) REFERENCES `currencies` (`code`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Product List';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL COMMENT 'The session_id()',
  `entry_point` varchar(255) DEFAULT NULL COMMENT 'Additional Information about entry point (eg: regenerated)',
  `previous_id` varchar(255) NOT NULL COMMENT 'Session previous_id for regenerated (best for tracking session_regenerate_id())',
  `data` text NOT NULL COMMENT 'The session data',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Session created at',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Session updated_at',
  PRIMARY KEY (`id`),
  UNIQUE KEY `[unique][previous_id]` (`previous_id`) USING BTREE COMMENT 'Unique Previous ID',
  KEY `[index][created_at:updated_at]` (`created_at`,`updated_at`) USING BTREE,
  KEY `[index][entry_point:previous_id]` (`entry_point`,`previous_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sessions Storage';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `site_metas`
--

DROP TABLE IF EXISTS `site_metas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_metas` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'Site ID',
  `name` varchar(255) NOT NULL COMMENT 'Site meta name',
  `site_id` bigint(20) NOT NULL COMMENT 'Site ID',
  `value` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `[unique][name:site_id]` (`name`,`site_id`),
  KEY `[index:relation][site_metas.site_id][sites.id]` (`site_id`) USING BTREE,
  CONSTRAINT `[index:relation][site_metas.site_id][sites.id]` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Site Meta By Site Id';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `site_options`
--

DROP TABLE IF EXISTS `site_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_options` (
  `name` varchar(255) NOT NULL COMMENT 'options site name',
  `site_id` bigint(20) NOT NULL COMMENT 'options site id',
  `value` text NOT NULL COMMENT 'options value',
  `autoload` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'autoload status',
  PRIMARY KEY (`name`,`site_id`) USING BTREE COMMENT 'Primary Key (name & site_id)',
  UNIQUE KEY `[unique][name:site_id]` (`name`,`site_id`) USING BTREE COMMENT 'name by site id',
  KEY `[index][name:site_id:autoload]` (`name`,`site_id`,`autoload`) USING BTREE COMMENT 'Index for autoloading data',
  KEY `[index:relation][site_options.site_id][sites.id]` (`site_id`) USING BTREE COMMENT 'Index relational sites.id',
  CONSTRAINT `[index:relation][site_options.site_id][sites.id]` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Options By Site Id';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `site_post_metas`
--

DROP TABLE IF EXISTS `site_post_metas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_post_metas` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `[unique][name:post_id]` (`name`,`post_id`) COMMENT 'unique name by post_id',
  KEY `[index:relation][site_post_metas.post_id][site_posts.id]` (`post_id`) USING BTREE,
  CONSTRAINT `[index:relation][site_post_metas.post_id][site_posts.id]` FOREIGN KEY (`post_id`) REFERENCES `site_posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Site Post Metadata';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `site_posts`
--

DROP TABLE IF EXISTS `site_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_posts` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `site_id` bigint(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `author` bigint(20) DEFAULT NULL COMMENT 'Posts Admin Author',
  `type` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `parent_id` bigint(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `published_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `[unique][slug]` (`slug`) COMMENT 'Posts Slug',
  KEY `[index:relation][site_posts.parent_id][site_posts.id]` (`parent_id`) USING BTREE COMMENT 'Index Relational site_posts.id',
  KEY `[index][site_id:type:status:author:created_at:published_at]` (`site_id`,`type`,`status`,`author`,`created_at`,`published_at`) USING BTREE COMMENT 'Render Indexing Posts',
  KEY `[index][title:type:status]` (`title`,`type`,`status`),
  KEY `[index:relation][site_posts.author][site_users.id]` (`author`) USING BTREE COMMENT 'Index Relational posts.author -> users.id',
  CONSTRAINT `[index:relation][site_posts.author][site_users.id]` FOREIGN KEY (`author`) REFERENCES `site_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `[index:relation][site_posts.parent_id][site_posts.id]` FOREIGN KEY (`parent_id`) REFERENCES `site_posts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Site Posts';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `site_use_roles`
--

DROP TABLE IF EXISTS `site_use_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_use_roles` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `site_id` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `access` text DEFAULT NULL,
  `created_by` bigint(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `[unique][site_id:name]` (`site_id`,`name`) USING BTREE COMMENT 'Site Role',
  KEY `[index:relation][user_roles.site_id][sites.id]` (`site_id`) USING BTREE COMMENT 'Index relational sites.id',
  KEY `[index:relation][site_user_roles.created_by][site_users.id]` (`created_by`) USING BTREE COMMENT 'Index relational site_users.id',
  CONSTRAINT `[index:relation][site_user_roles.created_by][site_users.id]` FOREIGN KEY (`created_by`) REFERENCES `site_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `[index:relation][user_roles.site_id][sites.id]` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Role Lists By Site Id';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `site_user_chats`
--

DROP TABLE IF EXISTS `site_user_chats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_user_chats` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'Chat ID',
  `from_user_id` bigint(20) NOT NULL COMMENT 'User id sender',
  `to_user_id` bigint(20) NOT NULL COMMENT 'User id target',
  `message` text NOT NULL COMMENT 'Chat message (max: 2048 characters) but encoded by security from_user_id.security_key',
  `read_at` datetime DEFAULT NULL COMMENT 'Chat readed at, indicates that chat has been opened',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Chat send at',
  PRIMARY KEY (`id`),
  KEY `[index:relation][suc.from_user_id][site_users.id]` (`from_user_id`),
  KEY `[index:relation][suc.to_user_id][site_users.id]` (`to_user_id`),
  CONSTRAINT `[index:relation][suc.from_user_id][site_users.id]` FOREIGN KEY (`from_user_id`) REFERENCES `site_users` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `[index:relation][suc.to_user_id][site_users.id]` FOREIGN KEY (`to_user_id`) REFERENCES `site_users` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Site User Chat list';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `site_user_logs`
--

DROP TABLE IF EXISTS `site_user_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_user_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL COMMENT 'Admin ID',
  `name` varchar(255) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `value` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `[index][name:user_id:type]` (`name`,`user_id`,`type`) USING BTREE,
  KEY `[index][name:user_id:type:created_at:updated_at]` (`name`,`user_id`,`type`,`created_at`,`updated_at`) USING BTREE,
  KEY `[index:relation][site_user_logs.user_id][site_users.id]` (`user_id`) USING BTREE,
  CONSTRAINT `[index:relation][site_user_logs.user_id][site_users.id]` FOREIGN KEY (`user_id`) REFERENCES `site_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Site Users Logs';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `site_user_metas`
--

DROP TABLE IF EXISTS `site_user_metas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_user_metas` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` longtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `[unique][name:user_id]` (`name`,`user_id`) USING BTREE COMMENT 'Unique name & user_id',
  KEY `[index:relation][site_user_metas.user_id][site_users.id]` (`user_id`) USING BTREE COMMENT 'Index Relational site_users.id',
  CONSTRAINT `[index:relation][site_user_metas.user_id][site_users.id]	` FOREIGN KEY (`user_id`) REFERENCES `site_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User Metadata By User Id';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `site_users`
--

DROP TABLE IF EXISTS `site_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_users` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'User ID',
  `username` varchar(255) NOT NULL COMMENT 'Username',
  `email` varchar(320) NOT NULL COMMENT 'Email address - 64 username & 255 domain name + 1 @',
  `password` varchar(255) DEFAULT NULL COMMENT 'User password password_has(hash_hmac(''sha256'', users.security_key)), PASSWORD_BCRYPT)',
  `role` bigint(20) DEFAULT NULL COMMENT 'User role',
  `status` varchar(255) NOT NULL DEFAULT 'pending' COMMENT 'User status',
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `site_id` bigint(20) NOT NULL COMMENT 'Site id',
  `security_key` varchar(255) NOT NULL COMMENT 'Security key to used as various method',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` datetime DEFAULT NULL COMMENT 'user deletion time',
  PRIMARY KEY (`id`) USING BTREE COMMENT 'Primary Key',
  UNIQUE KEY `[unique][username:site_id]` (`username`,`site_id`) USING BTREE COMMENT 'unique username by site id',
  UNIQUE KEY `[unique][email:site_id]` (`email`,`site_id`) USING BTREE COMMENT 'unique email by site id',
  UNIQUE KEY `[unique][id]` (`id`) USING BTREE COMMENT 'unique id',
  KEY `[index][status]` (`status`) USING BTREE COMMENT 'Index status',
  KEY `[index:relation][site_users.role][site_user_roles.id]` (`role`),
  KEY `[unique:relation][site_users.site_id][sites.id]` (`site_id`) USING BTREE COMMENT 'Unique index relational sites.id',
  CONSTRAINT `[index:relation][site_users.role][site_user_roles.id]	` FOREIGN KEY (`role`) REFERENCES `site_use_roles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `[unique:relation][site_users.site_id][sites.id]` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User Lists By Site Id';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sites`
--

DROP TABLE IF EXISTS `sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sites` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'Site ID',
  `owned_by` bigint(20) NOT NULL COMMENT 'Site Owner',
  `name` varchar(255) NOT NULL COMMENT 'Site Name',
  `domain` varchar(255) NOT NULL COMMENT 'Site Domain',
  `subdomain` varchar(255) DEFAULT NULL COMMENT 'Subdomain for domain',
  `status` varchar(255) NOT NULL COMMENT 'Site status',
  `security_key` varchar(255) NOT NULL COMMENT 'Site security key',
  `description` text DEFAULT NULL COMMENT 'Site description',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Site created at',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Site Updated At',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `[unique][domain]` (`domain`) USING BTREE,
  KEY `[index][name]` (`name`) USING BTREE,
  KEY `[index:concat][subdomain:domain]` (`subdomain`,`domain`) USING BTREE,
  KEY `[index][status]` (`status`) USING BTREE,
  KEY `[index:relation][sites.owned_by][users.id]` (`owned_by`) USING BTREE,
  CONSTRAINT `[index:relation][sites.owned_by][users.id]` FOREIGN KEY (`owned_by`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Site Lists';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_logs`
--

DROP TABLE IF EXISTS `user_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL COMMENT 'Admin ID',
  `name` varchar(255) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `value` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `[index][name:user_id:type:created_at:updated_at]` (`name`,`user_id`,`type`,`created_at`,`updated_at`) USING BTREE,
  KEY `[index][name:user_id:type]` (`name`,`user_id`,`type`) USING BTREE,
  KEY `[index:relation][user_logs.user_id][users.id]` (`user_id`) USING BTREE,
  CONSTRAINT `[index:relation][user_logs.user_id][users.id]` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User Logs';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_metas`
--

DROP TABLE IF EXISTS `user_metas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_metas` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL COMMENT 'User ID',
  `name` varchar(255) NOT NULL,
  `value` longtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `[unique][name:user]` (`name`,`user_id`) USING BTREE COMMENT 'Unique name & user_id',
  KEY `[index:relation][user_metas.member_id][users.id]` (`user_id`) USING BTREE COMMENT 'Index Relational users.id',
  CONSTRAINT `[index:relation][user_metas.member_id][users.id]	` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Users Metadata By User Id';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_roles` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `access` text DEFAULT NULL,
  `created_by` bigint(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `[unique][name]` (`name`) USING BTREE COMMENT 'unique name',
  KEY `[index:relation][user_roles.created_by][users.id]` (`created_by`) USING BTREE COMMENT 'Index relational users.id',
  CONSTRAINT `[index:relation][user_roles.created_by][users.id]` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User roles';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'User ID',
  `username` varchar(255) NOT NULL COMMENT 'Username',
  `email` varchar(320) NOT NULL COMMENT '	Email address - 64 username & 255 domain name + 1 @',
  `password` varchar(255) NOT NULL COMMENT 'User password password_has(hash_hmac(''sha256'', members.security_key)), PASSWORD_BCRYPT)',
  `role` bigint(20) DEFAULT NULL COMMENT 'Members type / role',
  `status` varchar(255) NOT NULL COMMENT 'User status',
  `first_name` varchar(255) NOT NULL COMMENT 'User First Name',
  `last_name` varchar(255) DEFAULT NULL COMMENT 'User Lastname',
  `security_key` varchar(255) NOT NULL COMMENT 'Security key to used as various method	',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'User created at',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'User updated at',
  `deleted_at` datetime DEFAULT NULL COMMENT 'User deletion time	',
  PRIMARY KEY (`id`) USING BTREE COMMENT 'Autoincrement as primary key',
  UNIQUE KEY `[unique][username]` (`username`) USING BTREE,
  UNIQUE KEY `[unique][email]` (`email`) USING BTREE,
  KEY `[index][status]` (`status`),
  KEY `[index:relation][users.role][user_roles.id]` (`role`),
  CONSTRAINT `[index:relation][users.role][user_roles.id]` FOREIGN KEY (`role`) REFERENCES `user_roles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Users of main site users';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2023-04-19 11:32:04
