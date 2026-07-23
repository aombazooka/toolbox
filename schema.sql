-- ============================================================
--  QR Studio — Database schema (MySQL / MariaDB)
--  Designed for a single user now, but ready for multi-user:
--  every record carries user_id, and users have a role.
-- ============================================================

CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(64)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  display_name  VARCHAR(100) NULL,
  role          ENUM('admin','staff') NOT NULL DEFAULT 'admin',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  name       VARCHAR(100) NOT NULL,
  color      VARCHAR(20) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cat_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS qrcodes (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  user_id         INT NOT NULL,
  name            VARCHAR(200) NOT NULL,
  category        VARCHAR(100) NULL,
  type            ENUM('static','dynamic') NOT NULL DEFAULT 'static',
  destination_url TEXT NOT NULL,
  short_code      VARCHAR(16) NULL UNIQUE,      -- only for dynamic QR
  style_json      MEDIUMTEXT NULL,              -- colours, dot style, size, ec, logoSize
  logo_data       MEDIUMTEXT NULL,              -- base64 data URL of centre logo
  scan_count      INT NOT NULL DEFAULT 0,
  is_active       TINYINT(1) NOT NULL DEFAULT 1,
  expires_at      DATETIME NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_qr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_qr_user (user_id),
  INDEX idx_qr_short (short_code),
  INDEX idx_qr_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS scans (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  qrcode_id  INT NOT NULL,
  scanned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip_hash    VARCHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  CONSTRAINT fk_scan_qr FOREIGN KEY (qrcode_id) REFERENCES qrcodes(id) ON DELETE CASCADE,
  INDEX idx_scan_qr (qrcode_id),
  INDEX idx_scan_time (scanned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  k VARCHAR(64) PRIMARY KEY,
  v TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS templates (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  name       VARCHAR(100) NOT NULL,
  style_json MEDIUMTEXT NULL,
  logo_data  MEDIUMTEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tpl_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_tpl_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  ip_hash      VARCHAR(64) NULL,
  username     VARCHAR(64) NULL,
  success      TINYINT(1) NOT NULL DEFAULT 0,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_la_ip (ip_hash, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- user_id is nullable and has NO foreign key on purpose: a failed-login row
-- (no authenticated user yet) or a later-deleted user's history must survive.
CREATE TABLE IF NOT EXISTS audit_log (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NULL,
  action     VARCHAR(40) NOT NULL,
  entity     VARCHAR(40) NULL,
  entity_id  INT NULL,
  detail     VARCHAR(255) NULL,
  ip_hash    VARCHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_time (created_at),
  INDEX idx_audit_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- T1: URL shortener. user_id is nullable (guests may shorten links too —
-- see api/shorten/create.php); ON DELETE SET NULL keeps a short link resolving
-- even if its owner account is later removed.
CREATE TABLE IF NOT EXISTS short_links (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  code        VARCHAR(32) NOT NULL UNIQUE,
  target_url  TEXT NOT NULL,
  user_id     INT NULL,
  clicks      INT NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  expires_at  DATETIME NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_short_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_short_user (user_id),
  INDEX idx_short_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS short_clicks (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  link_id    INT NOT NULL,
  clicked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip_hash    VARCHAR(64) NULL,
  referer    VARCHAR(500) NULL,
  ua         VARCHAR(255) NULL,
  CONSTRAINT fk_short_click_link FOREIGN KEY (link_id) REFERENCES short_links(id) ON DELETE CASCADE,
  INDEX idx_short_click_link (link_id),
  INDEX idx_short_click_time (clicked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
