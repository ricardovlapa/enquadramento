<?php
/** @var array $article */
/** @var array $authors */
/** @var array|null $author */
/** @var array $related */
?>
<main class="container">
  <section class="section opinion-section">
    <div class="layout opinion-layout">
      <article class="card opinion-article">
        <div class="opinion-author">
          <div class="opinion-author__avatar">
            <?php if (is_array($author) && !empty($author['avatar_path'])): ?>
              <img src="<?= image_src($author['avatar_path'] ?? '') ?>" alt="<?= e($author['name'] ?? 'Autor') ?>">
            <?php else: ?>
              <span><?= e(substr((string) ($author['name'] ?? 'A'), 0, 1)) ?></span>
            <?php endif; ?>
          </div>
          <div class="opinion-author__info">
            <?php if (is_array($author) && !empty($author['name'])): ?>
              <strong><?= e($author['name'] ?? '') ?></strong>
            <?php endif; ?>
            <?php if (is_array($author) && !empty($author['description'])): ?>
              <p><?= nl2br(e($author['description'] ?? '')) ?></p>
            <?php endif; ?>
            <?php if (!empty($article['published_at'])): ?>
              <span class="opinion-author__date"><?= e(format_date($article['published_at'] ?? '')) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <h1><?= e($article['title'] ?? 'Untitled') ?></h1>
        <?php if (!empty($article['intro'])): ?>
          <div class="opinion-intro"><?= markdown($article['intro'] ?? '') ?></div>
        <?php endif; ?>
        <?php if (!empty($article['content'])): ?>
          <div class="opinion-body"><?= markdown($article['content'] ?? '') ?></div>
        <?php endif; ?>
      </article>
      <aside class="sidebar opinion-sidebar">
        <div class="related-sidebar">
          <h3>Do mesmo autor</h3>
          <?php if (!empty($related)): ?>
            <div class="related-sidebar__list">
              <?php foreach ($related as $item): ?>
                <?php if (empty($item['slug'])): ?>
                  <?php continue; ?>
                <?php endif; ?>
                <a class="related-sidebar__item" href="/opiniao-enquadramento/<?= e($item['slug'] ?? '') ?>">
                  <span class="related-sidebar__title"><?= e($item['title'] ?? 'Untitled') ?></span>
                  <?php if (!empty($item['published_at'])): ?>
                    <span class="related-sidebar__date"><?= e(format_date($item['published_at'] ?? '')) ?></span>
                  <?php endif; ?>
                </a>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="related-sidebar__empty">Sem artigos adicionais.</p>
          <?php endif; ?>
        </div>
      </aside>
    </div>
  </section>
</main>
