<?php
/** @var array $articles */
/** @var array $authors */
?>
<main class="container">
  <section class="section opinion-section">
    <h1>Opinião Enquadramento</h1>
    <div class="layout opinion-layout">
      <div class="post-list">
        <?php if (count($articles) === 0): ?>
          <div class="card">
            <p>Sem artigos publicados nesta secção.</p>
          </div>
        <?php else: ?>
          <?php foreach ($articles as $article): ?>
            <?php $articleAuthor = $article['author'] ?? null; ?>
            <article class="card post-card opinion-card">
              <div class="post-card__body">
                <p class="post-meta">
                  <?php if (is_array($articleAuthor) && !empty($articleAuthor['name'])): ?>
                    <?= e($articleAuthor['name'] ?? '') ?>
                  <?php endif; ?>
                  <?php if (is_array($articleAuthor) && !empty($articleAuthor['name']) && !empty($article['published_at'])): ?>
                    <span class="post-meta__sep">|</span>
                  <?php endif; ?>
                  <?php if (!empty($article['published_at'])): ?>
                    <time datetime="<?= e($article['published_at'] ?? '') ?>">
                      <?= e(format_date($article['published_at'] ?? '')) ?>
                    </time>
                  <?php endif; ?>
                </p>
                <h3><?= e($article['title'] ?? 'Untitled') ?></h3>
                <?php if (!empty($article['intro'])): ?>
                  <p class="opinion-intro"><?= markdown($article['intro'] ?? '') ?></p>
                <?php endif; ?>
                <?php if (!empty($article['slug'])): ?>
                  <a class="button" href="/opiniao-enquadramento/<?= e($article['slug'] ?? '') ?>">Ler artigo</a>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <aside class="sidebar opinion-sidebar">
        <?php require dirname(__DIR__) . '/View/partials/authors_sidebar.php'; ?>
      </aside>
    </div>
  </section>
</main>
