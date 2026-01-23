<?php

namespace App\Controller;

use App\Model\NewsRepository;
use App\Model\OpinionRepository;

class HomeController extends BaseController
{
    private NewsRepository $news;
    private OpinionRepository $opinions;

    public function __construct(array $site, NewsRepository $news, OpinionRepository $opinions)
    {
        parent::__construct($site);
        $this->news = $news;
        $this->opinions = $opinions;
    }

    public function show(): void
    {
        $items = $this->news->all();
        $featured = array_slice($items, 0, 3);
        $latest = array_slice($items, 3, 8);
        $opinionLatest = array_slice($this->opinions->allArticles(), 0, 3);

        $otherOpinions = array_values(array_filter($items, function (array $item): bool {
            return ($item['category_slug'] ?? '') === 'opiniao-outras-fontes';
        }));
        $otherOpinions = array_slice($otherOpinions, 0, 3);

        $grouped = [];
        foreach ($items as $item) {
            $slug = (string) ($item['category_slug'] ?? '');
            if ($slug === '') {
                continue;
            }
            $grouped[$slug][] = $item;
        }

        $homeSections = $this->site['newsHomeSections'] ?? [];
        if ($homeSections === []) {
            $homeSections = $this->news->categories();
        }

        $sections = [];
        foreach ($homeSections as $category) {
            $slug = (string) ($category['slug'] ?? '');
            if ($slug === '' || $slug === 'opiniao-outras-fontes') {
                continue;
            }
            $sectionItems = $grouped[$slug] ?? [];
            if ($sectionItems === []) {
                continue;
            }
            $sections[] = [
                'slug' => $slug,
                'label' => (string) ($category['label'] ?? ''),
                'subtitle' => (string) ($category['subtitle'] ?? ''),
                'items' => array_slice($sectionItems, 0, 6),
            ];
        }

        $this->render('home', [
            'featured' => $featured,
            'latest' => $latest,
            'opinionLatest' => $opinionLatest,
            'otherOpinions' => $otherOpinions,
            'sections' => $sections,
        ], 'Início', [
            'description' => $this->site['description'] ?? ($this->site['tagline'] ?? ''),
        ]);
    }
}
