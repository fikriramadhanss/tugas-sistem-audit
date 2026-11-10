-- ===================================================
-- QUERY SUNTIK / INJEKSI AKUN SUPERADMIN
-- Jalankan query ini di Tab SQL phpMyAdmin
-- ===================================================

USE `ecommerce_parfum`;

-- 1. Pastikan kolom role mendukung 'superadmin'
ALTER TABLE `users` 
MODIFY COLUMN `role` ENUM('user', 'admin', 'superadmin') NOT NULL DEFAULT 'user';

-- 2. Suntik akun Superadmin (Jika sudah ada, di-update password dan role-nya)
INSERT INTO `users` (
  `id`, 
  `email`, 
  `password`, 
  `full_name`, 
  `phone`, 
  `city`, 
  `province`, 
  `role`
) VALUES (
  'usr-superadmin-000', 
  'superadmin@parfum.com', 
  '$2y$10$28GGcHNTlAjKEiraf8r3huuOZcNZ2pCmSQxOE4BGe3bK9kOIukBva', 
  'Super Administrator', 
  '081122334455', 
  'Jakarta Pusat', 
  'DKI Jakarta', 
  'superadmin'
)
ON DUPLICATE KEY UPDATE 
  `password` = VALUES(`password`),
  `role` = 'superadmin',
  `full_name` = 'Super Administrator';

