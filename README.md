# Enquadramento

Enquadramento is a PHP news aggregation and editorial site. It ingests RSS feeds into a MySQL database, normalizes and groups news categories, and renders news and opinion sections using a lightweight MVC router.

## Features

- News ingestion from RSS into `news_sources` and `news_items`
- Curated category groups with training mappings for normalization
- Opinion articles and authors served from the database
- Share links with redirect tracking

## Requirements

- PHP 8.x
- Composer
- MySQL (or compatible) database

## Setup

1) Install dependencies (only needed once):

```sh
composer install
composer dump-autoload
```

2) Configure environment:

Create `.env.local` with the database and site settings.

```env
APP_ENV=development
DB_DSN=mysql:host=127.0.0.1;port=3306;dbname=enquadramento;charset=utf8mb4
DB_USER=your_user
DB_PASS=your_pass

SITE_TITLE=Enquadramento
SITE_TAGLINE=O essencial da atualidade, organizado por tema.
SITE_DESCRIPTION=O essencial da atualidade, organizado por tema.
SITE_BASE_URL=https://example.com
SITE_ALLOWED_HOSTS=example.com,www.example.com
SITE_SOCIAL_IMAGE=https://example.com/assets/images/socialImage.jpg
AUTHOR_NAME=Site Author
```

3) Run migrations:

```sh
php scripts/migrate.php
```

4) Start the local server:

```sh
composer serve
```

Then open `http://localhost:8000`.

## Data Sources

The app uses the database for news items, sources, authors, and articles. These JSON files remain for category management:

- `app/Data/categories.json` for curated category groups
- `app/Data/category_training.json` for raw-to-slug mapping overrides

## News Fetching

Fetch and store news items using the database-backed fetcher:

```sh
php app/Console/fetch_news.php
```

This reads sources from `news_sources` and upserts into `news_items` (only when items changed).

Clean up items older than 7 days:

```sh
php app/Console/cleanup_news.php
```

Cron examples:

```cron
*/10 * * * * php /path/to/app/Console/fetch_news.php >> /var/log/enquadramento/fetch_news.log 2>&1
15 3 * * * php /path/to/app/Console/cleanup_news.php >> /var/log/enquadramento/cleanup_news.log 2>&1
```

## Category Tools

- `php app/Console/report_dynamic_categories.php` to inspect dynamic categories from news items
- `php app/Console/suggest_category_matches.php` to suggest training mappings

## SEO & Social

- Set `SITE_BASE_URL` and `SITE_SOCIAL_IMAGE` in `.env.local`.
- `SITE_SOCIAL_IMAGE` should be an absolute URL to a ~1200x630 image.

## RSS

The feed route is disabled by default. Enable it by setting `BLOG_FEED_ENABLED=true` and re-adding the `/feed.xml` route in `app/routes.php`.
