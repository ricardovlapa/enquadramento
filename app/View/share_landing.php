<?php
/** @var string $title */
/** @var string $desc */
/** @var string $redirectUrl */
/** @var string $ctaLabel */
/** @var string $note */
?>
<main class="share-landing">
  <div class="container">
    <article class="share-landing__card">
      <p class="tag">Pré-visualização</p>
      <h1 class="share-landing__title"><?= e($title) ?></h1>
      <p class="share-landing__desc"><?= e($desc) ?></p>
      <div class="share-landing__actions">
        <a class="button share-landing__cta" href="<?= e($redirectUrl) ?>"><?= e($ctaLabel ?? 'Ler notícia') ?></a>
      </div>
      <p class="share-landing__note"><?= e($note ?? 'A pré-visualização não redireciona automaticamente. Clique no botão para abrir a fonte original.') ?></p>
    </article>
  </div>
</main>
