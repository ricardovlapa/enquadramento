<?php
$home = $site['ui']['home'] ?? [];
$featuredLead = $featured[0] ?? null;
$featuredSide = array_slice($featured, 1, 2);
?>
<main class="news-home">
  <section class="hero-news">
    <div class="container">
      <div class="hero-news__content">
        <h1 class="hero-title"><?= e($site['tagline']) ?></h1>
        <p class="hero-text"><?= e($home['description'] ?? '') ?></p>
      </div>
    </div>
  </section>

  <section class="featured-section">
    <div class="container">
      <?php if ($featuredLead === null): ?>
        <div class="card"><p><?= e($home['emptyNews'] ?? '') ?></p></div>
      <?php else: ?>
        <div class="featured-grid">
          <article class="featured-card featured-card--lead">
            <?php if (!empty($featuredLead['image_url'])): ?>
              <img src="<?= image_src($featuredLead['image_url']) ?>" alt="<?= e($featuredLead['title'] ?? 'Imagem da notícia') ?>">
            <?php else: ?>
              <img src="<?= image_src('/assets/images/default_image_enquadramento.png') ?>" alt="<?= e($featuredLead['title'] ?? 'Imagem da notícia') ?>">
            <?php endif; ?>
            <div class="featured-card__content">
              <p class="post-meta">
                <?php if (!empty($featuredLead['category_label']) && !empty($featuredLead['category_slug'])): ?>
                  <a class="tag tag--light" href="/noticias/categoria/<?= e($featuredLead['category_slug'] ?? '') ?>">#<?= e($featuredLead['category_label'] ?? '') ?></a>
                <?php endif; ?>
                <?php if (!empty($featuredLead['published_at'])): ?>
                  <time datetime="<?= e($featuredLead['published_at'] ?? '') ?>"><?= e(format_datetime($featuredLead['published_at'] ?? '')) ?></time>
                <?php endif; ?>
              </p>
              <h2><?= e($featuredLead['title'] ?? 'Untitled') ?></h2>
              <?php if (!empty($featuredLead['summary'])): ?>
                <p><?= e($featuredLead['summary'] ?? '') ?></p>
              <?php endif; ?>
              <?php if (!empty($featuredLead['link'])): ?>
                <a class="button button--light" href="<?= e($featuredLead['link'] ?? '') ?>" target="_blank" rel="noopener noreferrer">Ler fonte</a>
              <?php endif; ?>
            </div>
          </article>
          <div class="featured-side">
            <?php foreach ($featuredSide as $item): ?>
              <article class="featured-card featured-card--side">
                <?php if (!empty($item['image_url'])): ?>
                  <img src="<?= image_src($item['image_url']) ?>" alt="<?= e($item['title'] ?? 'Imagem da notícia') ?>">
                <?php else: ?>
                  <img src="<?= image_src('/assets/images/default_image_enquadramento.png') ?>" alt="<?= e($item['title'] ?? 'Imagem da notícia') ?>">
                <?php endif; ?>
                <div class="featured-card__content">
                  <p class="post-meta">
                    <?php if (!empty($item['category_label']) && !empty($item['category_slug'])): ?>
                      <a class="tag tag--light" href="/noticias/categoria/<?= e($item['category_slug'] ?? '') ?>">#<?= e($item['category_label'] ?? '') ?></a>
                    <?php endif; ?>
                  </p>
                  <h3><?= e($item['title'] ?? 'Untitled') ?></h3>
                  <?php if (!empty($item['link'])): ?>
                    <a class="button button--light" href="<?= e($item['link'] ?? '') ?>" target="_blank" rel="noopener noreferrer">Ler fonte</a>
                  <?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="section latest-section">
    <div class="container">
      <div class="section-heading">
        <div class="section-heading__title">
          <h2><?= e($home['newsTitle'] ?? 'Últimas notícias') ?></h2>
          <span class="section-subtitle"><?= e($home['newsSubtitle'] ?? 'Atualizado continuamente') ?></span>
        </div>
        <span class="section-heading__line" aria-hidden="true"></span>
      </div>
      <?php if (count($latest) === 0): ?>
        <div class="card"><p><?= e($home['emptyNews'] ?? '') ?></p></div>
      <?php else: ?>
        <div class="layout latest-layout">
          <div class="latest-grid">
            <?php foreach ($latest as $item): ?>
            <article class="card post-card">
              <?php if (!empty($item['image_url'])): ?>
                <div class="post-image post-image--thumb">
                  <img src="<?= image_src($item['image_url']) ?>" alt="<?= e($item['title'] ?? 'Imagem da notícia') ?>">
                </div>
              <?php else: ?>
                <div class="post-image post-image--thumb">
                  <img src="<?= image_src('/assets/images/default_image_enquadramento.png') ?>" alt="<?= e($item['title'] ?? 'Imagem da notícia') ?>">
                </div>
              <?php endif; ?>
              <div class="post-card__body">
                  <p class="post-meta">
                    <?php if (!empty($item['category_label']) && !empty($item['category_slug'])): ?>
                      <a class="tag" href="/noticias/categoria/<?= e($item['category_slug'] ?? '') ?>">#<?= e($item['category_label'] ?? '') ?></a>
                    <?php endif; ?>
                    <?php if (!empty($item['published_at'])): ?>
                      <time datetime="<?= e($item['published_at'] ?? '') ?>"><?= e(format_datetime($item['published_at'] ?? '')) ?></time>
                    <?php endif; ?>
                  </p>
                  <h3><?= e($item['title'] ?? 'Untitled') ?></h3>
                  <?php if (!empty($item['summary'])): ?>
                    <p><?= e($item['summary'] ?? '') ?></p>
                  <?php endif; ?>
                  <?php if (!empty($item['link'])): ?>
                    <a class="button" href="<?= e($item['link'] ?? '') ?>" target="_blank" rel="noopener noreferrer">Ler fonte</a>
                  <?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
          <aside class="category-list opinion-latest">
            <div class="opinion-latest__section">
              <h3 class="opinion-latest__title">Opinião Enquadramento</h3>
              <?php if (!empty($opinionLatest)): ?>
                <?php foreach ($opinionLatest as $article): ?>
                  <?php if (empty($article['slug'])): ?>
                    <?php continue; ?>
                  <?php endif; ?>
                  <a class="opinion-latest__item" href="/opiniao-enquadramento/<?= e($article['slug'] ?? '') ?>">
                    <span><?= e($article['title'] ?? 'Untitled') ?></span>
                    <?php if (!empty($article['published_at'])): ?>
                      <time datetime="<?= e($article['published_at'] ?? '') ?>"><?= e(format_date($article['published_at'] ?? '')) ?></time>
                    <?php endif; ?>
                  </a>
                <?php endforeach; ?>
              <?php else: ?>
                <p class="opinion-latest__empty">Sem artigos disponíveis.</p>
              <?php endif; ?>
            </div>
            <div class="opinion-latest__section">
              <h3 class="opinion-latest__title">Opinião outras fontes</h3>
              <?php if (!empty($otherOpinions)): ?>
                <?php foreach ($otherOpinions as $item): ?>
                  <?php if (empty($item['link'])): ?>
                    <?php continue; ?>
                  <?php endif; ?>
                  <a class="opinion-latest__item" href="<?= e($item['link'] ?? '') ?>" target="_blank" rel="noopener noreferrer">
                    <span><?= e($item['title'] ?? 'Untitled') ?></span>
                    <?php if (!empty($item['published_at'])): ?>
                      <time datetime="<?= e($item['published_at'] ?? '') ?>"><?= e(format_datetime($item['published_at'] ?? '')) ?></time>
                    <?php endif; ?>
                  </a>
                <?php endforeach; ?>
              <?php else: ?>
                <p class="opinion-latest__empty">Sem artigos disponíveis.</p>
              <?php endif; ?>
            </div>
          </aside>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <?php foreach ($sections as $section): ?>
    <?php $sectionItems = $section['items'] ?? []; ?>
    <?php $featureItems = array_slice($sectionItems, 0, 2); ?>
    <?php $sectionList = array_slice($sectionItems, 2); ?>
    <section class="section category-section">
      <div class="container">
        <div class="section-heading section-heading--line">
          <div class="section-heading__title">
            <h2><?= e($section['label'] ?? '') ?></h2>
            <span class="section-subtitle"><?= e($section['subtitle'] ?? ($home['categorySubtitle'] ?? '')) ?></span>
          </div>
          <span class="section-heading__line" aria-hidden="true"></span>
          <a class="section-link" href="/noticias/categoria/<?= e($section['slug'] ?? '') ?>">Ver mais</a>
        </div>
        <?php if ($featureItems !== []): ?>
          <div class="category-grid">
            <div class="category-feature-grid">
              <?php foreach ($featureItems as $feature): ?>
                <article class="category-feature-card">
                  <?php if (!empty($feature['image_url'])): ?>
                    <div class="category-feature-card__media">
                      <img src="<?= image_src($feature['image_url']) ?>" alt="<?= e($feature['title'] ?? 'Imagem da notícia') ?>">
                    </div>
                  <?php else: ?>
                    <div class="category-feature-card__media">
                      <img src="<?= image_src('/assets/images/default_image_enquadramento.png') ?>" alt="<?= e($feature['title'] ?? 'Imagem da notícia') ?>">
                    </div>
                  <?php endif; ?>
                  <div class="category-feature-card__content">
                    <p class="post-meta">
                      <?php if (!empty($feature['category_label']) && !empty($feature['category_slug'])): ?>
                        <a class="tag" href="/noticias/categoria/<?= e($feature['category_slug'] ?? '') ?>">#<?= e($feature['category_label'] ?? '') ?></a>
                      <?php endif; ?>
                      <?php if (!empty($feature['published_at'])): ?>
                        <time datetime="<?= e($feature['published_at'] ?? '') ?>"><?= e(format_datetime($feature['published_at'] ?? '')) ?></time>
                      <?php endif; ?>
                    </p>
                    <h3><?= e($feature['title'] ?? 'Untitled') ?></h3>
                    <?php if (!empty($feature['summary'])): ?>
                      <p><?= e($feature['summary'] ?? '') ?></p>
                    <?php endif; ?>
                    <?php if (!empty($feature['link'])): ?>
                      <a class="button button--line" href="<?= e($feature['link'] ?? '') ?>" target="_blank" rel="noopener noreferrer">Ler fonte</a>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
            <div class="category-list">
              <?php foreach ($sectionList as $item): ?>
                <article class="compact-card">
                  <?php if (!empty($item['image_url'])): ?>
                    <div class="compact-card__media">
                      <img src="<?= image_src($item['image_url']) ?>" alt="<?= e($item['title'] ?? 'Imagem da notícia') ?>">
                    </div>
                  <?php else: ?>
                    <div class="compact-card__media">
                      <img src="<?= image_src('/assets/images/default_image_enquadramento_100x100.png') ?>" alt="<?= e($item['title'] ?? 'Imagem da notícia') ?>">
                    </div>
                  <?php endif; ?>
                  <div class="compact-card__body">
                    <p class="post-meta">
                      <?php if (!empty($item['published_at'])): ?>
                        <time datetime="<?= e($item['published_at'] ?? '') ?>"><?= e(format_datetime($item['published_at'] ?? '')) ?></time>
                      <?php endif; ?>
                    </p>
                    <h4><?= e($item['title'] ?? 'Untitled') ?></h4>
                    <?php if (!empty($item['link'])): ?>
                      <a class="button button--ghost" href="<?= e($item['link'] ?? '') ?>" target="_blank" rel="noopener noreferrer">Ler fonte</a>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </section>
  <?php endforeach; ?>

  <div class="container">
    <div class="ad-slot<?= $site['adSlotsVisible'] ? '' : ' ad-slot--silent' ?>">Espaço reservado para Google Ads (hero)</div>
  </div>
</main>
