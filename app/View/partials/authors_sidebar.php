<?php
/** @var array $authors */
?>
<div class="author-sidebar">
  <h3>Os nossos autores.</h3>
  <div class="author-list">
    <?php if (count($authors) === 0): ?>
      <p class="author-empty">Sem autores registados.</p>
    <?php else: ?>
      <?php foreach ($authors as $author): ?>
        <div class="author-card">
          <div class="author-avatar">
            <?php if (!empty($author['avatar_path'])): ?>
              <img src="<?= image_src($author['avatar_path'] ?? '') ?>" alt="<?= e($author['name'] ?? 'Autor') ?>">
            <?php else: ?>
              <span><?= e(substr((string) ($author['name'] ?? 'A'), 0, 1)) ?></span>
            <?php endif; ?>
          </div>
          <div class="author-info">
            <strong><?= e($author['name'] ?? '') ?></strong>
            <?php if (!empty($author['description'])): ?>
              <p><?= nl2br(e($author['description'] ?? '')) ?></p>
            <?php endif; ?>
            <span class="author-count">
              <?= e((string) count($author['articles'] ?? [])) ?> artigos
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
