<?php $editorial = $site['ui']['editorial'] ?? []; ?>
<main class="container">
  <section class="section">
    <article class="card">
      <header>
        <h1><?= e($editorial['title'] ?? '') ?></h1>
      </header>
      <section>
        <?php foreach (($editorial['sections'] ?? []) as $section): ?>
          <h3><?= e($section['title'] ?? '') ?></h3>
          <?php foreach (($section['paragraphs'] ?? []) as $paragraph): ?>
            <p><?= e($paragraph) ?></p>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </section>
    </article>
  </section>
</main>
