# Routing and Views

Routes are defined in `app/routes.php` and mapped to controllers in `app/Controller/*`.

## Routes

- `/` -> `HomeController::show` (home page)
- `/todas-as-noticias` -> `NewsController::index` (all news)
- `/noticias/categoria/{category}` -> `NewsController::category`
- `/noticias/categoria/opiniao-enquadramento` -> `OpinionController::index` (alias route)
- `/opiniao-enquadramento` -> `OpinionController::index`
- `/opiniao-enquadramento/{slug}` -> `OpinionController::show`
- `/sobre` -> `AboutController::show`
- `/nota-editorial` -> `LegalEditorialController::editorial`
- `/termos-de-utilizacao` -> `LegalEditorialController::terms`
- `/politica-de-privacidade` -> `LegalEditorialController::privacy`
- `/share/{id}`, `/s/{token}`, `/r/{token}` -> share and redirect flow (only registered when DB is available)

## Views

- `app/View/layout.php` is the shared layout (header, nav, footer, share modal).
- `app/View/home.php` renders featured items, latest items, and category sections.
- `app/View/news.php` renders the news list, filters, and infinite scroll loader.
- `app/View/partials/news_items.php` renders a list of news cards.
- `app/View/opinion_index.php` and `app/View/opinion_article.php` render the opinion section.
- `app/View/about.php` renders the about page.
- `app/View/legal_editorial.php`, `app/View/legal_terms.php`, and `app/View/legal_privacy.php` render the legal pages.
- `app/View/share_landing.php` renders share previews.

## Partial loading

`/todas-as-noticias` supports partial loading. When `?partial=1` or `X-Requested-With: XMLHttpRequest` is set, `NewsController` returns only the list markup (via `partials/news_items.php`). `app/View/news.php` includes the client-side loader that fetches subsequent pages.
