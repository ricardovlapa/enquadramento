# Data Model

The database schema lives in `database/*.sql`. The app expects these tables:

## news_sources

Defines RSS sources to fetch.

- `id` (PK)
- `name`, `type`, `url`
- `country`, `language`
- `default_image_path` (stored, not currently used in code)
- `enabled`
- timestamps (`created_at`, `updated_at`)

Used by: `NewsFeedFetcher`, `NewsRepository` (for source names).

## news_items

Normalized news items from RSS feeds.

- `id` (PK, sha1)
- `source_id` (FK to `news_sources.id`)
- `title`, `link`, `summary`, `author`
- `published_at`, `fetched_at`
- `category`, `categories_json`
- `image_url`
- `raw_guid`, `raw_extra_json`

Used by: `NewsRepository` and controllers.

## authors

Opinion authors.

- `id` (PK)
- `name`, `description`, `avatar_path`
- `created_at`

Used by: `OpinionRepository`.

## articles

Opinion articles.

- `id` (PK)
- `author_id` (FK to `authors.id`)
- `slug` (unique)
- `title`, `intro`, `content`
- `published_at`
- `tags_json`

Used by: `OpinionRepository`.

## redirects

Share and redirect tracking.

- `id` (PK)
- `token` (unique)
- `article_id` (optional)
- `source_url`, `source_domain`
- `title`, `image`
- `clicks`, `created_at`, `expire_at`

Used by: `RedirectRepository` and `ShareController`.

## Relationships

- `news_items.source_id` -> `news_sources.id`
- `articles.author_id` -> `authors.id`
- `redirects.article_id` is optional and used for share tracking
