<?php

namespace App\Controller;

use App\Model\NewsRepository;

class NewsController extends BaseController
{
    private NewsRepository $news;
    private const PER_PAGE = 10;

    public function __construct(array $site, NewsRepository $news)
    {
        parent::__construct($site);
        $this->news = $news;
    }

    public function index(): void
    {
        $categorySlug = trim((string) ($_GET['category'] ?? ''));
        $sourceId = trim((string) ($_GET['source'] ?? ''));

        $this->renderNewsList($categorySlug, $sourceId);
    }

    public function categories(): void
    {
        $categories = $this->news->categories();
        $this->render('news_categories', [
            'categories' => $categories,
        ], 'Categorias');
    }

    public function category(string $slug): void
    {
        $slug = trim($slug);
        $this->renderNewsList($slug, '');
    }

    private function renderNewsList(string $categorySlug, string $sourceId): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $items = $this->news->all();
        if ($categorySlug !== '') {
            $items = array_values(array_filter($items, function (array $item) use ($categorySlug): bool {
                return ($item['category_slug'] ?? '') === $categorySlug;
            }));
        }

        if ($sourceId !== '') {
            $items = array_values(array_filter($items, function (array $item) use ($sourceId): bool {
                return ($item['source_id'] ?? '') === $sourceId;
            }));
        }

        $totalItems = count($items);
        $offset = ($page - 1) * self::PER_PAGE;
        $pagedItems = array_slice($items, $offset, self::PER_PAGE);
        $hasMore = $totalItems > ($offset + self::PER_PAGE);

        if ($this->isPartialRequest()) {
            $this->renderItemsPartial($pagedItems);
            return;
        }

        $categories = $this->news->categories();
        $activeCategoryLabel = $this->findCategoryLabel($categories, $categorySlug);

        $subsections = $this->site['newsCategorySubsections'] ?? [];
        $parentMap = $this->site['newsCategoryParentMap'] ?? [];
        $subcategories = [];
        $subcategoriesParent = '';
        if ($categorySlug !== '') {
            if (isset($subsections[$categorySlug]) && is_array($subsections[$categorySlug])) {
                $subcategoriesParent = $categorySlug;
                $subcategories = $subsections[$categorySlug];
            } elseif (isset($parentMap[$categorySlug])) {
                $parent = (string) $parentMap[$categorySlug];
                if ($parent !== '' && isset($subsections[$parent]) && is_array($subsections[$parent])) {
                    $subcategoriesParent = $parent;
                    $subcategories = $subsections[$parent];
                }
            }
        }

        $title = $categorySlug !== '' ? ('Notícias: ' . $activeCategoryLabel) : 'Notícias';
        $description = $categorySlug !== ''
            ? ('Notícias sobre ' . $activeCategoryLabel . '.')
            : ($this->site['description'] ?? ($this->site['tagline'] ?? ''));

        $this->render('news', [
            'items' => $pagedItems,
            'categories' => $categories,
            'activeCategory' => $categorySlug,
            'activeCategoryLabel' => $activeCategoryLabel,
            'subcategories' => $subcategories,
            'subcategoriesParent' => $subcategoriesParent,
            'sourceFilter' => $sourceId,
            'hasMore' => $hasMore,
            'nextPage' => $page + 1,
            'baseUrl' => $this->buildPaginationBaseUrl(),
        ], $title, [
            'description' => $description,
        ]);
    }

    private function findCategoryLabel(array $categories, string $slug): string
    {
        if ($slug === '') {
            return '';
        }

        foreach ($categories as $category) {
            if (($category['slug'] ?? '') === $slug) {
                return (string) ($category['label'] ?? $slug);
            }
        }

        return $slug;
    }

    private function isPartialRequest(): bool
    {
        $partial = (string) ($_GET['partial'] ?? '');
        if ($partial === '1') {
            return true;
        }

        $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        return $requestedWith === 'xmlhttprequest';
    }

    private function buildPaginationBaseUrl(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/todas-as-noticias', PHP_URL_PATH) ?? '/todas-as-noticias';
        $query = $_GET;
        unset($query['page'], $query['partial']);
        $queryString = http_build_query($query);
        return $queryString !== '' ? ($path . '?' . $queryString) : $path;
    }

    private function renderItemsPartial(array $items): void
    {
        require_once dirname(__DIR__) . '/helpers.php';
        $site = $this->site;
        require dirname(__DIR__) . '/View/partials/news_items.php';
    }
}
