-- Migration: create news_sources table
CREATE TABLE IF NOT EXISTS news_sources (
  id VARCHAR(64) PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  type VARCHAR(32) NOT NULL,
  url TEXT NOT NULL,
  country VARCHAR(8) NULL,
  language VARCHAR(16) NULL,
  default_image_path TEXT NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX (enabled)
);
