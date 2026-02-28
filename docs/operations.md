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

Retry behavior for transient source failures:

- Retries happen when at least one source fails HTTP fetch or XML parsing.
- Defaults: up to 5 attempts with exponential backoff (`1s`, `2s`, `4s`, `8s` between attempts).
- If failures persist after the last attempt, the command exits with status `1`.

Optional env vars:

```env
FETCH_NEWS_MAX_ATTEMPTS=5
FETCH_NEWS_RETRY_BASE_DELAY_SECONDS=1
```

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
