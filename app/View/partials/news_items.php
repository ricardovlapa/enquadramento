<?php
/** @var array $items */
?>
<?php foreach ($items as $item): ?>
  <?php $hideImage = ($item['category_slug'] ?? '') === 'opiniao-outras-fontes'; ?>
  <article class="card post-card">
    <?php if (!$hideImage): ?>
      <div class="post-image post-image--thumb">
        <img src="<?= image_src(!empty($item['image_url']) ? $item['image_url'] : '/assets/images/default_image_enquadramento.png') ?>" alt="<?= e($item['title'] ?? 'Imagem da notícia') ?>">
      </div>
    <?php endif; ?>
    <div class="post-card__body">
      <p class="post-meta">
        <?php if (!empty($item['published_at'])): ?>
          <time datetime="<?= e($item['published_at'] ?? '') ?>"><?= e(format_datetime($item['published_at'] ?? '')) ?></time>
        <?php endif; ?>
        <?php if (!empty($item['source_name'])): ?>
          <span> · <?= e($item['source_name'] ?? '') ?></span>
        <?php endif; ?>
      </p>
      <h3>
        <?php if (!empty($item['link'])): ?>
          <a href="<?= e($item['link'] ?? '') ?>" target="_blank" rel="noopener noreferrer">
            <?= e($item['title'] ?? 'Untitled') ?>
          </a>
        <?php else: ?>
          <?= e($item['title'] ?? 'Untitled') ?>
        <?php endif; ?>
      </h3>
      <?php if (!empty($item['summary'])): ?>
        <p><?= e($item['summary'] ?? '') ?></p>
      <?php endif; ?>
      <?php if (!empty($item['category_label']) && !empty($item['category_slug'])): ?>
        <p>
          <a class="tag" href="/noticias/categoria/<?= e($item['category_slug'] ?? '') ?>">#<?= e($item['category_label'] ?? '') ?></a>
        </p>
      <?php endif; ?>
      <?php if (!empty($item['link'])): ?>
        <a class="button" href="<?= e($item['link'] ?? '') ?>" target="_blank" rel="noopener noreferrer">Ler fonte</a>
        <?php if (!empty($item['id'])): ?>
          <a class="button js-share-trigger" href="/share/<?= e($item['id']) ?>" data-share-id="<?= e($item['id']) ?>">Partilhar</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </article>
<?php endforeach; ?>
