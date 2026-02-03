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

This reads `news_sources` and writes to `news_items`. It requires a working DB connection.

## Category tooling

- `php app/Console/report_dynamic_categories.php`
- `php app/Console/suggest_category_matches.php`

Both commands read `news_items` from the DB and use `app/Data/category_training.json` for mapping.
