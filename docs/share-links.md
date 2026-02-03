# Share Links and Redirects

Share links are database-backed and tracked in the `redirects` table.

## How it works

1) News cards include a "share" button with the `news_items.id`.
2) The share modal requests `/share/{id}?json=1`.
3) The server uses `RedirectRepository` to find or create a token.
4) The response includes a `share_url` and `redirect_url`.
5) `/s/{token}` renders a share landing page.
6) `/r/{token}` increments clicks and redirects to the original source URL.

```mermaid
sequenceDiagram
  participant UI as Share Modal
  participant App as /share/{id}
  participant DB as redirects table
  participant Share as /s/{token}
  participant Redirect as /r/{token}
  participant Source as Source URL

  UI->>App: GET /share/{id}?json=1
  App->>DB: findOrCreate token
  DB-->>App: token + metadata
  App-->>UI: share_url + redirect_url
  UI->>Share: open share_url
  Share->>Redirect: user clicks "read source"
  Redirect->>DB: increment clicks
  Redirect->>Source: 302 to source_url
```

## Files to know

- `app/routes.php` registers `/share`, `/s`, `/r` routes only when a DB connection exists.
- `app/Model/RedirectRepository.php` handles token creation and click tracking.
- `app/View/share_landing.php` renders the share preview page.
