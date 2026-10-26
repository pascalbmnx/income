-- ============================================================
-- Admin users table for the admin panel login/auth
-- Run with:  mysql -u root electrical_services < admin_users.sql
-- ============================================================

USE electrical_services;

CREATE TABLE IF NOT EXISTS admin_users (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(80)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          VARCHAR(20)  NOT NULL DEFAULT 'viewer',
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed default admin (username: admin / password: admin123)
INSERT INTO admin_users (username, password_hash, role)
SELECT 'admin', '$2y$10$Q.w1AgS8jUR5LuNFe7rRlerxdpZXK6cwS5ExN8JjmnSuAZm0ITvme', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM admin_users WHERE username = 'admin');
