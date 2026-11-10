-- ===================================================
-- DATABASE E-COMMERCE PARFUM (PHP & MySQL / phpMyAdmin)
-- ===================================================

CREATE DATABASE IF NOT EXISTS `ecommerce_parfum` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `ecommerce_parfum`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `shipments`; 
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `carts`;
DROP TABLE IF EXISTS `wishlist`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `banners`;
DROP TABLE IF EXISTS `store_settings`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------
-- 1. Tabel Users (Menggantikan Supabase Auth & Profiles)
-- ---------------------------------------------------
CREATE TABLE `users` (
  `id` VARCHAR(36) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) NULL,
  `phone` VARCHAR(50) NULL,
  `address` TEXT NULL,
  `city` VARCHAR(100) NULL,
  `province` VARCHAR(100) NULL,
  `postal_code` VARCHAR(20) NULL,
  `avatar_url` TEXT NULL,
  `role` ENUM('user', 'admin', 'superadmin') NOT NULL DEFAULT 'user',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 2. Tabel Categories
-- ---------------------------------------------------
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `image_url` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 3. Tabel Products
-- ---------------------------------------------------
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount_percent` INT NOT NULL DEFAULT 0,
  `stock` INT NOT NULL DEFAULT 0,
  `brand` VARCHAR(255) NULL,
  `aroma_notes` TEXT NULL,
  `volume` VARCHAR(50) NULL,
  `status` ENUM('ready', 'preorder', 'out_of_stock') NOT NULL DEFAULT 'ready',
  `category_id` INT NULL,
  `images` LONGTEXT NULL COMMENT 'JSON Array of image URLs or string',
  `is_featured` BOOLEAN NOT NULL DEFAULT FALSE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_products_slug` (`slug`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_featured` (`is_featured`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 4. Tabel Banners
-- ---------------------------------------------------
CREATE TABLE `banners` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `image_url` LONGTEXT NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_banners_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 5. Tabel Orders
-- ---------------------------------------------------
CREATE TABLE `orders` (
  `id` VARCHAR(50) NOT NULL,
  `user_id` VARCHAR(36) NULL,
  `status` ENUM('pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `shipping_name` VARCHAR(255) NULL,
  `shipping_phone` VARCHAR(50) NULL,
  `shipping_address` TEXT NULL,
  `shipping_city` VARCHAR(100) NULL,
  `shipping_province` VARCHAR(100) NULL,
  `shipping_postal_code` VARCHAR(20) NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_orders_user` (`user_id`),
  KEY `idx_orders_status` (`status`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 6. Tabel Order Items
-- ---------------------------------------------------
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `order_id` VARCHAR(50) NOT NULL,
  `product_id` INT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `product_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `quantity` INT NOT NULL DEFAULT 1,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  KEY `idx_order_items_product` (`product_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 7. Tabel Shipments
-- ---------------------------------------------------
CREATE TABLE `shipments` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `order_id` VARCHAR(50) NOT NULL,
  `courier` VARCHAR(100) NULL,
  `tracking_number` VARCHAR(100) NULL,
  `shipped_at` DATETIME NULL,
  `delivered_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_shipments_order` (`order_id`),
  CONSTRAINT `fk_shipments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 8. Tabel Carts
-- ---------------------------------------------------
CREATE TABLE `carts` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `user_id` VARCHAR(36) NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_cart_user_product` (`user_id`, `product_id`),
  KEY `idx_cart_product` (`product_id`),
  CONSTRAINT `fk_carts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_carts_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 9. Tabel Wishlist
-- ---------------------------------------------------
CREATE TABLE `wishlist` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `user_id` VARCHAR(36) NOT NULL,
  `product_id` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_wishlist_user_product` (`user_id`, `product_id`),
  KEY `idx_wishlist_product` (`product_id`),
  CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_wishlist_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 10. Tabel Reviews
-- ---------------------------------------------------
CREATE TABLE `reviews` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `product_id` INT NOT NULL,
  `user_id` VARCHAR(36) NOT NULL,
  `rating` INT NOT NULL DEFAULT 5,
  `comment` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reviews_product` (`product_id`),
  KEY `idx_reviews_user` (`user_id`),
  CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 11. Tabel Store Settings
-- ---------------------------------------------------
CREATE TABLE `store_settings` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `key` VARCHAR(100) NOT NULL,
  `value` LONGTEXT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_settings_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- SEED DATA AWAL (DEFAULT USERS, PRODUK, KATEGORI)
-- ===================================================

-- Akun Default:
-- Superadmin: superadmin@parfum.com | Password: superadmin123
-- Admin:      admin@parfum.com      | Password: admin123
-- User:       user@parfum.com       | Password: user123
INSERT INTO `users` (`id`, `email`, `password`, `full_name`, `phone`, `city`, `province`, `role`) VALUES
('usr-superadmin-000', 'superadmin@parfum.com', '$2y$10$28GGcHNTlAjKEiraf8r3huuOZcNZ2pCmSQxOE4BGe3bK9kOIukBva', 'Super Administrator', '081122334455', 'Jakarta Pusat', 'DKI Jakarta', 'superadmin'),
('usr-admin-001', 'admin@parfum.com', '$2y$10$nSwyLgWkWXuL5YzVUTC9n.xIFSwsZhkCXjrMzZ6RbYTj0.zZiLVMC', 'Admin Perfume Store', '081234567890', 'Jakarta Selatan', 'DKI Jakarta', 'admin'),
('usr-demo-002', 'user@parfum.com', '$2y$10$VLllGt61hgeHnL7TmMIktetAhaZ0j.PdSGk/KxwkPosr00jUWDTDe', 'Customer Demo', '089876543210', 'Bandung', 'Jawa Barat', 'user');

-- Kategori Default
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image_url`) VALUES
(1, 'Floral & Romantic', 'floral-romantic', 'Aroma bunga segar yang lembut, memancarkan pesona anggun dan romantis.', 'https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?auto=format&fit=crop&q=80&w=800'),
(2, 'Woody & Earthy', 'woody-earthy', 'Sentuhan kayu hangat seperti sandalwood dan cedarwood yang maskulin dan berwibawa.', 'https://images.unsplash.com/photo-1523293182086-7651a899d37f?auto=format&fit=crop&q=80&w=800'),
(3, 'Fresh & Citrus', 'fresh-citrus', 'Sensasi kesegaran lemon, bergamot, dan dedaunan yang membangkitkan energi.', 'https://images.unsplash.com/photo-1547887537-6158d64c35b3?auto=format&fit=crop&q=80&w=800'),
(4, 'Oriental & Spicy', 'oriental-spicy', 'Kombinasi vanila, amber, dan rempah eksotis yang mewah dan memikat.', 'https://images.unsplash.com/photo-1616949755610-8c9bbc08f138?auto=format&fit=crop&q=80&w=800');

-- Produk Default
INSERT INTO `products` (`id`, `name`, `slug`, `description`, `price`, `discount_percent`, `stock`, `brand`, `aroma_notes`, `volume`, `status`, `category_id`, `images`, `is_featured`) VALUES
(1, 'Rose Velvet Eau de Parfum', 'rose-velvet-eau-de-parfum', 'Perpaduan mewah bunga mawar Damask dengan sentuhan lembut vanila bourbon dan amber hangat. Memberikan keharuman sensual dan tahan hingga 12 jam.', 450000.00, 10, 25, 'Maison De Aroma', 'Top: Turkish Rose | Mid: Vanilla Bourbon | Base: Amberwood', '100ml', 'ready', 1, '["https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?auto=format&fit=crop&q=80&w=800"]', 1),
(2, 'Oud Noir Intense', 'oud-noir-intense', 'Kekuatan kayu gaharu pilihan yang dipadukan dengan spicy cardamom dan smokey leather. Wangi yang sangat cocok untuk acara formal malam hari.', 680000.00, 0, 15, 'L\'Atelier Luxe', 'Top: Cardamom, Bergamot | Mid: Agarwood (Oud), Cedar | Base: Leather, Tonka Bean', '100ml', 'ready', 2, '["https://images.unsplash.com/photo-1523293182086-7651a899d37f?auto=format&fit=crop&q=80&w=800"]', 1),
(3, 'Citrus Breeze Cologne', 'citrus-breeze-cologne', 'Ledakan kesegaran jeruk mandarin Sisilia dan sea salt notes yang menyegarkan hari Anda dalam sekejap.', 320000.00, 15, 40, 'Acqua Fresca', 'Top: Sicilian Mandarin, Mint | Mid: Sea Salt, Jasmine | Base: White Musk', '50ml', 'ready', 3, '["https://images.unsplash.com/photo-1547887537-6158d64c35b3?auto=format&fit=crop&q=80&w=800"]', 1),
(4, 'Amber Sunset Extrait', 'amber-sunset-extrait', 'Komposisi oriental kaya dengan resin amber murni, kayu manis, dan vanila Madagaskar yang adiktif.', 550000.00, 5, 20, 'Maison De Aroma', 'Top: Cinnamon Bark | Mid: Golden Amber | Base: Madagascar Vanilla, Benzoin', '50ml', 'ready', 4, '["https://images.unsplash.com/photo-1616949755610-8c9bbc08f138?auto=format&fit=crop&q=80&w=800"]', 1);

-- Banner Default
INSERT INTO `banners` (`id`, `image_url`, `sort_order`) VALUES
(1, 'https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?auto=format&fit=crop&q=80&w=1600', 1),
(2, 'https://images.unsplash.com/photo-1523293182086-7651a899d37f?auto=format&fit=crop&q=80&w=1600', 2);

-- Pengaturan Toko Default
INSERT INTO `store_settings` (`key`, `value`) VALUES
('store_name', 'Maison Perfume Store'),
('store_description', 'Toko parfum original premium dengan koleksi wewangian terbaik untuk pria dan wanita.'),
('whatsapp', '081234567890'),
('email', 'support@parfumstore.com'),
('address', 'Jl. Kemang Raya No. 12, Jakarta Selatan, 12730'),
('open_hours', 'Senin - Minggu: 09:00 - 21:00 WIB'),
('instagram', 'https://instagram.com/parfumstore'),
('tiktok', 'https://tiktok.com/@parfumstore'),
('facebook', 'https://facebook.com/parfumstore'),
('twitter', 'https://twitter.com/parfumstore');

