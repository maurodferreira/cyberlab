-- Executar como administrador do MariaDB no banco cyberlab.
-- ATENÇÃO: versão DIDÁTICA. A senha é salva em texto puro de propósito (exigência do professor).
-- Nunca usar este modelo em produção nem com senhas reais.
CREATE DATABASE IF NOT EXISTS cyberlab
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cyberlab;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(32) NOT NULL,
  email VARCHAR(254) NOT NULL,
  password_plain VARCHAR(255) NOT NULL COMMENT 'senha',
  role ENUM('member', 'admin') NOT NULL DEFAULT 'member',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_username (username),
  UNIQUE KEY uniq_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS auth_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NULL,
  event_type ENUM('register', 'login_success', 'login_failed', 'rate_limited', 'logout') NOT NULL,
  ip_address VARCHAR(45) NULL,
  occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_event_when (occurred_at),
  KEY idx_ip_event_when (ip_address, event_type, occurred_at),
  CONSTRAINT fk_events_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;