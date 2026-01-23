<?php
/** @var array $categories */
?>
<?php $newsUi = $site['ui']['news'] ?? []; ?>
<main class="container">
  <section class="section">
    <h1><?= e($newsUi['categoriesTitle'] ?? 'Categorias de notícias') ?></h1>
    <?php if (count($categories) === 0): ?>
      <div class="card">
        <p><?= e($newsUi['emptyCategories'] ?? 'Ainda não há categorias disponíveis.') ?></p>
      </div>
    <?php else: ?>
      <div class="post-list">
        <?php foreach ($categories as $category): ?>
          <article class="card post-card">
            <div class="post-card__body">
              <h3><?= e($category['label'] ?? '') ?></h3>
              <p><?= e((string) ($category['count'] ?? 0)) ?> notícias</p>
              <a class="button" href="/noticias/categoria/<?= e($category['slug'] ?? '') ?>">Abrir</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>
