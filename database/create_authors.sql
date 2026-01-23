-- Migration: create authors table
CREATE TABLE IF NOT EXISTS authors (
  id VARCHAR(64) PRIMARY KEY,
  avatar_path TEXT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  created_at VARCHAR(40) NULL
);
