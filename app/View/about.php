<?php
$about = $site['ui']['about'] ?? [];
$title = (string) ($about['title'] ?? '');
$text = (string) ($about['text'] ?? '');
$logo = (string) ($about['logo'] ?? '');
$logoAlt = (string) ($about['logoAlt'] ?? ($site['title'] ?? ''));
$paragraphs = [];
if ($text !== '') {
    $paragraphs = preg_split('/\R{2,}/', trim($text)) ?: [];
}
?>
<main class="container">
  <section class="section">
    <article class="card">
      <header>
        <h1><?= e($title) ?></h1>
      </header>
      <section>
        <?php foreach ($paragraphs as $paragraph): ?>
          <p><?= e($paragraph) ?></p>
        <?php endforeach; ?>
      </section>
      <?php if ($logo !== ''): ?>
        <div class="card-media">
          <img src="<?= image_src($logo) ?>" alt="<?= e($logoAlt) ?>">
        </div>
      <?php endif; ?>
    </article>
  </section>
</main>
