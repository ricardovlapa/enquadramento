<?php

namespace App\Controller;

use App\Model\OpinionRepository;

class OpinionController extends BaseController
{
    private OpinionRepository $opinions;

    public function __construct(array $site, OpinionRepository $opinions)
    {
        parent::__construct($site);
        $this->opinions = $opinions;
    }

    public function index(): void
    {
        $articles = $this->opinions->allArticles();
        $authors = $this->opinions->authors();

        $this->render('opinion_index', [
            'articles' => $articles,
            'authors' => $authors,
        ], 'Opinião Enquadramento', [
            'description' => 'Artigos de opinião assinados pelos nossos autores.',
        ]);
    }

    public function show(string $slug): void
    {
        $article = $this->opinions->findArticleBySlug($slug);
        if ($article === null) {
            (new NotFoundController($this->site))->show();
            return;
        }

        $authors = $this->opinions->authors();
        $author = $article['author'] ?? null;
        $related = [];

        if (is_array($author) && !empty($author['id'])) {
            $related = $this->opinions->articlesByAuthor((string) $author['id'], (string) ($article['id'] ?? ''));
        }

        $description = trim((string) ($article['intro'] ?? ''));
        if ($description === '') {
            $description = trim((string) ($article['title'] ?? ''));
        }

        $this->render('opinion_article', [
            'article' => $article,
            'authors' => $authors,
            'author' => $author,
            'related' => $related,
        ], (string) ($article['title'] ?? 'Opinião'), [
            'description' => $description,
        ]);
    }
}
