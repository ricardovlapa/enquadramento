<?php
/** @var array $items */
/** @var array $categories */
/** @var string $activeCategory */
/** @var string $activeCategoryLabel */
/** @var array $subcategories */
/** @var string $subcategoriesParent */
/** @var string $sourceFilter */
?>
<?php $newsUi = $site['ui']['news'] ?? []; ?>
<main class="container">
  <section class="section">
    <h1><?= e($activeCategory !== '' ? $activeCategoryLabel : ($newsUi['title'] ?? 'Notícias')) ?></h1>
    <?php if (!empty($subcategories)): ?>
      <div class="tag-filter tag-filter--sub">
        <span class="tag-filter__label">Subcategorias</span>
        <div class="tag-filter__list">
          <?php $parentHref = '/noticias/categoria/' . ($subcategoriesParent ?? ''); ?>
          <a class="tag-filter__item<?= ($activeCategory === $subcategoriesParent ? ' is-active' : '') ?>" href="<?= e($parentHref) ?>">Todos</a>
          <?php foreach ($subcategories as $subcategory): ?>
            <?php $isActive = ($subcategory['slug'] ?? '') === $activeCategory; ?>
            <a class="tag-filter__item<?= $isActive ? ' is-active' : '' ?>" href="/noticias/categoria/<?= e($subcategory['slug'] ?? '') ?>">
              <?= e($subcategory['label'] ?? '') ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
    <div class="layout">
      <div class="post-list">
        <?php if (count($items) === 0): ?>
          <div class="card">
            <?php if ($activeCategory !== ''): ?>
              <p><?= e(sprintf($newsUi['emptyCategory'] ?? 'Sem notícias para a categoria “%s”.', $activeCategoryLabel)) ?></p>
            <?php else: ?>
              <p><?= e($newsUi['emptyItems'] ?? 'Ainda não há notícias. Execute o fetch manual para carregar itens.') ?></p>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <?php require dirname(__DIR__) . '/View/partials/news_items.php'; ?>
          <?php if (!empty($hasMore)): ?>
            <div class="news-loader" data-news-loader="1" data-next-page="<?= e((string) $nextPage) ?>" data-base-url="<?= e($baseUrl ?? '/todas-as-noticias') ?>"></div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
      <aside class="sidebar">
        <div class="card tag-filter">
          <span class="tag-filter__label"><?= e($newsUi['categoriesLabel'] ?? 'Categorias') ?></span>
          <div class="tag-filter__list">
            <a class="tag-filter__item<?= $activeCategory === '' ? ' is-active' : '' ?>" href="/todas-as-noticias">Todas</a>
            <?php foreach ($categories as $category): ?>
              <?php $isActive = ($category['slug'] ?? '') === $activeCategory; ?>
              <a class="tag-filter__item<?= $isActive ? ' is-active' : '' ?>" href="/noticias/categoria/<?= e($category['slug'] ?? '') ?>">
                <?= e($category['label'] ?? '') ?> (<?= e((string) ($category['count'] ?? 0)) ?>)
              </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php if ($sourceFilter !== ''): ?>
          <div class="card">
            <p>Filtro de origem ativo: <?= e($sourceFilter) ?></p>
            <a class="button" href="/todas-as-noticias">Limpar filtro</a>
          </div>
        <?php endif; ?>
        <div class="ad-slot<?= $site['adSlotsVisible'] ? '' : ' ad-slot--silent' ?>" style="margin-top: 20px;">Espaço reservado para Google Ads (sidebar)</div>
      </aside>
    </div>
  </section>
</main>

<?php if (!empty($hasMore)): ?>
  <script>
    (function () {
      var loader = document.querySelector('[data-news-loader]');
      if (!loader) {
        return;
      }

      var list = document.querySelector('.post-list');
      var nextPage = parseInt(loader.getAttribute('data-next-page') || '2', 10);
      var baseUrl = loader.getAttribute('data-base-url') || '/todas-as-noticias';
      var loading = false;
      var hasMore = true;

      var loadMore = function () {
        if (loading || !hasMore) {
          return;
        }
        loading = true;
        loader.classList.add('is-loading');

        var separator = baseUrl.indexOf('?') >= 0 ? '&' : '?';
        var url = baseUrl + separator + 'page=' + nextPage + '&partial=1';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
          .then(function (response) {
            if (!response.ok) {
              hasMore = false;
              return '';
            }
            return response.text();
          })
          .then(function (html) {
            if (!html || html.trim() === '') {
              hasMore = false;
              loader.remove();
              return;
            }
            var wrapper = document.createElement('div');
            wrapper.innerHTML = html;
            while (wrapper.firstChild) {
              list.insertBefore(wrapper.firstChild, loader);
            }
            nextPage += 1;
            loader.setAttribute('data-next-page', String(nextPage));
          })
          .catch(function () {
            hasMore = false;
            loader.remove();
          })
          .finally(function () {
            loading = false;
            loader.classList.remove('is-loading');
          });
      };

      if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              loadMore();
            }
          });
        }, { rootMargin: '120px' });
        observer.observe(loader);
      } else {
        var onScroll = function () {
          if (!hasMore) {
            window.removeEventListener('scroll', onScroll);
            return;
          }
          var rect = loader.getBoundingClientRect();
          if (rect.top < window.innerHeight + 120) {
            loadMore();
          }
        };
        window.addEventListener('scroll', onScroll);
        onScroll();
      }
    })();
  </script>
<?php endif; ?>
