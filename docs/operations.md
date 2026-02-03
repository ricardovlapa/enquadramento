# Operations

## Environment

The app loads `.env` and `.env.local` via `app/dotenv.php`. For DB access you must set:

```
DB_DSN=mysql:host=127.0.0.1;port=3306;dbname=enquadramento;charset=utf8mb4
DB_USER=your_user
DB_PASS=your_pass
```

## Migrations

```sh
php scripts/migrate.php
```

This applies all SQL files under `database/`.

## Fetching news

```sh
php app/Console/fetch_news.php
```

This reads `news_sources` and upserts into `news_items`. It only updates rows when an item changed and preserves the original `fetched_at`. It requires a working DB connection.

## Cleanup

```sh
php app/Console/cleanup_news.php
```

Deletes items older than 7 days (using `published_at`, or `fetched_at` when `published_at` is missing). This can run independently from the fetch job.

## Cron examples

```cron
*/10 * * * * php /path/to/app/Console/fetch_news.php >> /var/log/enquadramento/fetch_news.log 2>&1
15 3 * * * php /path/to/app/Console/cleanup_news.php >> /var/log/enquadramento/cleanup_news.log 2>&1
```

## Category tooling

- `php app/Console/report_dynamic_categories.php`
- `php app/Console/suggest_category_matches.php`

Both commands read `news_items` from the DB and use `app/Data/category_training.json` for mapping.
