-- Migration: create articles table
CREATE TABLE IF NOT EXISTS articles (
  id VARCHAR(64) PRIMARY KEY,
  author_id VARCHAR(64) NULL,
  slug VARCHAR(255) NOT NULL,
  title TEXT NULL,
  published_at VARCHAR(40) NULL,
  intro TEXT NULL,
  content LONGTEXT NULL,
  tags_json TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_articles_slug (slug),
  INDEX (author_id),
  INDEX (published_at)
);
