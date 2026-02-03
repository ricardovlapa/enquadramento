# Feed Flow

The news pipeline is database-first. Sources are defined in the `news_sources` table, and `fetch_news.php` pulls from those sources and writes normalized items to `news_items`.

## Where sources are defined

- Primary source definition is in the database table `news_sources`.
- Each source has `id`, `name`, `type`, `url`, and an `enabled` flag.
- `app/Service/NewsFeedFetcher.php` reads sources via `readSourcesFromDb()`.

Sources should be managed directly in the `news_sources` table (or via your own tooling). The legacy JSON import script has been removed.

## Ingestion pipeline

```mermaid
sequenceDiagram
  participant Cron as Scheduler/Cron
  participant Fetch as app/Console/fetch_news.php
  participant DB as Database
  participant Fetcher as NewsFeedFetcher
  participant RSS as RSS feed

  Cron->>Fetch: run command
  Fetch->>DB: load existing news_items
  Fetch->>Fetcher: fetch()
  Fetcher->>DB: read news_sources
  loop enabled sources
    Fetcher->>RSS: HTTP GET url
    RSS-->>Fetcher: XML
    Fetcher-->>Fetch: normalized items
  end
  Fetch->>DB: replace news_items
```

## Normalization notes

`NewsFeedFetcher`:

- extracts title, link, summary, published date, author, and categories
- normalizes image URLs for some sources
- generates a stable `id` using `sha1(source_id|guid/link/title)`

`fetch_news.php`:

- merges new fetch results with existing items to preserve `fetched_at`
- keeps only items from the last 7 days
- replaces the `news_items` table in a single transaction
