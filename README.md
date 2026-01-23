# Minimal PHP Blog Starter

Small MVC-style PHP blog with JSON-backed posts and tags.

## Setup

1) Install dependencies and autoload:

```sh
composer dump-autoload
```

2) Run locally:

```sh
composer serve
```

Then open `http://localhost:8000`.

## Content

Posts live in `app/Data/posts.json`.
Tags live in `app/Data/tags.json`.
Post images live in `public/uploads`.

## Environment

Create `.env.local` to override settings (example):

```env
APP_ENV=development
AD_SLOTS_VISIBLE=true
SITE_TITLE=Starter Blog
SITE_TAGLINE=Thoughts, notes, and reflections on the present.
SITE_DESCRIPTION=Short posts about culture, community, and the details that shape our days.
SITE_BASE_URL=https://example.com
SITE_ALLOWED_HOSTS=example.com,www.example.com
SITE_SOCIAL_IMAGE=https://example.com/assets/images/socialImage.jpg
AUTHOR_NAME=Site Author
SITE_LOGO=/assets/images/rl_logo.png
SITE_FOOTER_AVATAR=/assets/images/myPhoto.png
SOCIAL_FACEBOOK=
SOCIAL_INSTAGRAM=
SOCIAL_LINKEDIN=
GOOGLE_ANALYTICS_ID=
BLOG_FEED_ENABLED=false
```

## SEO & Social

- Set `SITE_BASE_URL` and `SITE_SOCIAL_IMAGE` in `.env.local` or update `app/config.php`.
- `SITE_SOCIAL_IMAGE` should be an absolute URL to a ~1200x630 image.

## Database (news items, sources, authors, articles)

News items, sources, authors, and articles can be served from MySQL when `DB_DSN` (or `MYSQL_DSN`) is set. JSON remains a fallback.

1) Run migrations:

```sh
php scripts/migrate.php
```

2) Seed from existing JSON (optional):

```sh
php scripts/import_news_items.php
```

3) Import news sources (optional, for DB-backed fetch):

```sh
php scripts/import_news_sources.php
```

4) Import opinion authors and articles (optional):

```sh
php scripts/import_authors.php
php scripts/import_articles.php
```

5) Fetch news into the DB (also writes JSON):

```sh
php app/Console/fetch_news.php
```

## RSS

The feed route is disabled by default. Enable it by setting `BLOG_FEED_ENABLED=true` and re-adding the `/feed.xml` route in `app/routes.php` if you want RSS.

## Starter Checklist

- Update `app/config.php` defaults or override values in `.env.local` (title, description, baseUrl, socialImage, allowedHosts, author, social links).
- Replace the logo and favicon assets in `public/assets`.
- Add or remove tags in `app/Data/tags.json`.
