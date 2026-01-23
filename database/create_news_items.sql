-- Migration: create news_items table
CREATE TABLE IF NOT EXISTS news_items (
  id VARCHAR(64) PRIMARY KEY,
  source_id VARCHAR(64) NOT NULL,
  title TEXT NULL,
  link TEXT NULL,
  summary TEXT NULL,
  published_at VARCHAR(40) NULL,
  author VARCHAR(255) NULL,
  category VARCHAR(255) NULL,
  categories_json TEXT NULL,
  image_url TEXT NULL,
  raw_guid TEXT NULL,
  raw_extra_json TEXT NULL,
  fetched_at VARCHAR(40) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX (source_id),
  INDEX (category),
  INDEX (published_at),
  INDEX (fetched_at)
);
