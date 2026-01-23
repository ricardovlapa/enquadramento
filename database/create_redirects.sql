-- Migration: create redirects table
CREATE TABLE IF NOT EXISTS redirects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  token VARCHAR(32) NOT NULL UNIQUE,
  article_id INT NULL,
  source_url TEXT NOT NULL,
  source_domain VARCHAR(255) NULL,
  title VARCHAR(255) NULL,
  image VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expire_at DATETIME NULL,
  clicks INT NOT NULL DEFAULT 0,
  INDEX (article_id),
  INDEX (source_domain(100))
);
