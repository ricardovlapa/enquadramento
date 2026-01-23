<?php require_once dirname(__DIR__) . '/helpers.php'; ?>
<!doctype html>
<html lang="<?= e($site['language'] ?? 'pt-PT') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon/favicon-16x16.png">
  <link rel="manifest" href="/assets/favicon/site.webmanifest">
  <title><?= e($pageTitle) ?></title>
  <?php if (!empty($meta['description'])): ?>
    <meta name="description" content="<?= e($meta['description']) ?>">
  <?php endif; ?>
  <?php if (!empty($meta['robots'])): ?>
    <meta name="robots" content="<?= e($meta['robots']) ?>">
  <?php endif; ?>
  <?php if (!empty($meta['canonical'])): ?>
    <link rel="canonical" href="<?= e($meta['canonical']) ?>">
  <?php endif; ?>
  <?php if (!empty($meta['rss'])): ?>
    <link rel="alternate" type="application/rss+xml" title="<?= e($site['title'] ?? 'Feed') ?>" href="<?= e($meta['rss']) ?>">
  <?php endif; ?>
  <?php if (!empty($meta['og'])): ?>
    <meta property="og:title" content="<?= e($meta['og']['title'] ?? '') ?>">
    <meta property="og:description" content="<?= e($meta['og']['description'] ?? '') ?>">
    <meta property="og:type" content="<?= e($meta['og']['type'] ?? 'website') ?>">
    <meta property="og:url" content="<?= e($meta['og']['url'] ?? '') ?>">
    <meta property="og:site_name" content="<?= e($site['title'] ?? '') ?>">
    <?php if (!empty($meta['og']['locale'])): ?>
      <meta property="og:locale" content="<?= e($meta['og']['locale']) ?>">
    <?php endif; ?>
    <?php if (!empty($meta['og']['image'])): ?>
      <meta property="og:image" content="<?= e($meta['og']['image']) ?>">
    <?php endif; ?>
  <?php endif; ?>
  <?php if (!empty($meta['twitter'])): ?>
    <meta name="twitter:card" content="<?= e($meta['twitter']['card'] ?? 'summary') ?>">
    <meta name="twitter:title" content="<?= e($meta['twitter']['title'] ?? '') ?>">
    <meta name="twitter:description" content="<?= e($meta['twitter']['description'] ?? '') ?>">
    <?php if (!empty($meta['twitter']['image'])): ?>
      <meta name="twitter:image" content="<?= e($meta['twitter']['image']) ?>">
    <?php endif; ?>
  <?php endif; ?>
  <?php if (!empty($meta['jsonLd'])): ?>
    <script type="application/ld+json">
      <?= json_encode($meta['jsonLd'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    </script>
  <?php endif; ?>
  <?php if (!empty($site['analytics']['googleMeasurementId'])): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($site['analytics']['googleMeasurementId']) ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '<?= e($site['analytics']['googleMeasurementId']) ?>');
    </script>
  <?php endif; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Source+Sans+3:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <header class="site-header">
    <div class="container">
      <nav class="nav">
        <div class="brand">
          <a href="/" aria-label="<?= e($site['title']) ?> | Início">
            <img class="image-logo" alt="<?= e($site['branding']['logoAlt'] ?? ($site['title'] ?? '')) ?>" src="<?= image_src($site['branding']['logo'] ?? '') ?>">
          </a>
        </div>
        <div class="nav-links">
          <?php $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/'; ?>
          <?php $mainNav = []; ?>
          <?php $mainNav[] = ['label' => $site['navHomeLabel'] ?? 'Início', 'href' => '/']; ?>
          <?php $mainNav[] = ['label' => 'Todas as Notícias', 'href' => '/todas-as-noticias']; ?>
          <?php foreach (($site['newsCategories'] ?? []) as $category): ?>
            <?php if (!is_array($category)): ?>
              <?php continue; ?>
            <?php endif; ?>
            <?php if (!empty($category['items']) && is_array($category['items']) && !empty($category['label'])): ?>
              <?php
                $groupItems = [];
                foreach ($category['items'] as $item) {
                  if (empty($item['slug']) || empty($item['label'])) {
                    continue;
                  }
                  $href = ($item['slug'] ?? '') === 'opiniao-enquadramento'
                    ? '/opiniao-enquadramento'
                    : '/noticias/categoria/' . $item['slug'];
                  $children = [];
                  if (!empty($item['children']) && is_array($item['children'])) {
                    foreach ($item['children'] as $child) {
                      if (empty($child['slug']) || empty($child['label'])) {
                        continue;
                      }
                      $childHref = ($child['slug'] ?? '') === 'opiniao-enquadramento'
                        ? '/opiniao-enquadramento'
                        : '/noticias/categoria/' . $child['slug'];
                      $children[] = [
                        'label' => $child['label'],
                        'href' => $childHref,
                      ];
                    }
                  }
                  $groupItems[] = [
                    'label' => $item['label'],
                    'href' => $href,
                    'children' => $children,
                  ];
                }
              ?>
              <?php if ($groupItems !== []): ?>
                <?php $mainNav[] = ['label' => $category['label'], 'items' => $groupItems]; ?>
              <?php endif; ?>
            <?php elseif (!empty($category['slug']) && !empty($category['label'])): ?>
              <?php
                $href = ($category['slug'] ?? '') === 'opiniao-enquadramento'
                  ? '/opiniao-enquadramento'
                  : '/noticias/categoria/' . $category['slug'];
              ?>
              <?php $mainNav[] = ['label' => $category['label'], 'href' => $href]; ?>
            <?php endif; ?>
          <?php endforeach; ?>
          <?php foreach (($site['navigation'] ?? []) as $navItem): ?>
            <?php if (!empty($navItem['label']) && !empty($navItem['href'])): ?>
              <?php $mainNav[] = $navItem; ?>
            <?php endif; ?>
          <?php endforeach; ?>
          <?php foreach ($mainNav as $navItem): ?>
            <?php if (!empty($navItem['items']) && is_array($navItem['items'])): ?>
              <?php
                $groupActive = false;
                foreach ($navItem['items'] as $childItem) {
                  $childHref = (string) ($childItem['href'] ?? '');
                  $childIsInternal = ($childHref !== '' && !preg_match('#^https?://#', $childHref));
                  $childIsActive = $childIsInternal && ($childHref === '/' ? $currentPath === '/' : strpos($currentPath, $childHref) === 0);
                  if (!$childIsActive && !empty($childItem['children']) && is_array($childItem['children'])) {
                    foreach ($childItem['children'] as $grandItem) {
                      $grandHref = (string) ($grandItem['href'] ?? '');
                      $grandIsInternal = ($grandHref !== '' && !preg_match('#^https?://#', $grandHref));
                      $grandIsActive = $grandIsInternal && ($grandHref === '/' ? $currentPath === '/' : strpos($currentPath, $grandHref) === 0);
                      if ($grandIsActive) {
                        $childIsActive = true;
                        break;
                      }
                    }
                  }
                  if ($childIsActive) {
                    $groupActive = true;
                    break;
                  }
                }
                $groupClass = $groupActive ? ' nav-item--has-children is-active' : ' nav-item--has-children';
              ?>
              <div class="nav-item<?= $groupClass ?>">
                <button class="nav-link" type="button" aria-haspopup="true"><?= e($navItem['label'] ?? '') ?></button>
                <div class="nav-dropdown">
                  <?php foreach ($navItem['items'] as $childItem): ?>
                    <?php
                      $childHref = (string) ($childItem['href'] ?? '');
                      $childIsInternal = ($childHref !== '' && !preg_match('#^https?://#', $childHref));
                      $childIsActive = $childIsInternal && ($childHref === '/' ? $currentPath === '/' : strpos($currentPath, $childHref) === 0);
                      $childChildren = $childItem['children'] ?? [];
                      if (!$childIsActive && !empty($childChildren) && is_array($childChildren)) {
                        foreach ($childChildren as $grandItem) {
                          $grandHref = (string) ($grandItem['href'] ?? '');
                          $grandIsInternal = ($grandHref !== '' && !preg_match('#^https?://#', $grandHref));
                          $grandIsActive = $grandIsInternal && ($grandHref === '/' ? $currentPath === '/' : strpos($currentPath, $grandHref) === 0);
                          if ($grandIsActive) {
                            $childIsActive = true;
                            break;
                          }
                        }
                      }
                      $childClass = $childIsActive ? ' class="nav-link nav-link--parent is-active"' : ' class="nav-link nav-link--parent"';
                    ?>
                    <a href="<?= e($childHref) ?>"<?= $childClass ?>><?= e($childItem['label'] ?? '') ?></a>
                    <?php if (!empty($childChildren) && is_array($childChildren)): ?>
                      <div class="nav-dropdown-children">
                        <?php foreach ($childChildren as $grandItem): ?>
                          <?php
                            $grandHref = (string) ($grandItem['href'] ?? '');
                            $grandIsInternal = ($grandHref !== '' && !preg_match('#^https?://#', $grandHref));
                            $grandIsActive = $grandIsInternal && ($grandHref === '/' ? $currentPath === '/' : strpos($currentPath, $grandHref) === 0);
                            $grandClass = $grandIsActive ? ' class="nav-link is-active"' : ' class="nav-link"';
                          ?>
                          <a href="<?= e($grandHref) ?>"<?= $grandClass ?>><?= e($grandItem['label'] ?? '') ?></a>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php else: ?>
              <?php
                $href = (string) ($navItem['href'] ?? '');
                $isInternal = ($href !== '' && !preg_match('#^https?://#', $href));
                $isActive = $isInternal && ($href === '/' ? $currentPath === '/' : strpos($currentPath, $href) === 0);
                $classAttr = $isActive ? ' class="nav-link is-active"' : ' class="nav-link"';
              ?>
              <a href="<?= e($href) ?>"<?= $classAttr ?>><?= e($navItem['label'] ?? '') ?></a>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </nav>
    </div>
  </header>

  <?= $content ?>

  <footer class="site-footer">
    <div class="container">
      <div class="footer-main">
        <img class="footer-avatar" src="<?= image_src($site['branding']['footerAvatar'] ?? '') ?>" alt="<?= e($site['branding']['footerAvatarAlt'] ?? '') ?>">
        <h2 class="footer-name"><?= e($site['title']) ?></h2>
        <p class="footer-text"><?= e($site['ui']['footer']['text'] ?? '') ?></p>
        <div class="footer-social">
          <?php if (!empty($site['social']['facebook'])): ?>
            <a href="<?= e($site['social']['facebook']) ?>" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
              <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H8v3h2v6h3v-6h3l1-3h-4v-2c0-.6.4-1 1-1z" />
              </svg>
            </a>
          <?php endif; ?>
          <?php if (!empty($site['social']['instagram'])): ?>
            <a href="<?= e($site['social']['instagram']) ?>" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
              <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4z" fill="none" stroke="currentColor" stroke-width="1.6" />
                <circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.6" />
                <circle cx="16.5" cy="7.5" r="1.2" fill="currentColor" />
              </svg>
            </a>
          <?php endif; ?>
          <?php if (!empty($site['social']['linkedin'])): ?>
            <a href="<?= e($site['social']['linkedin']) ?>" aria-label="LinkedIn" target="_blank" rel="noopener noreferrer">
              <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M6 9H3v12h3z" />
                <path d="M4.5 3.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z" />
                <path d="M14.5 9a4.5 4.5 0 0 0-3.5 1.7V9H8v12h3v-6.2a2.2 2.2 0 0 1 2.2-2.2c1.3 0 1.8.8 1.8 2.1V21h3v-7.1C18 10.8 16.6 9 14.5 9z" />
              </svg>
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <div class="container">
        <p>
          Copyright © <?= date('Y') ?> <?= e($site['title']) ?>. Todos os direitos reservados.
          <?php if (!empty($site['ui']['footer']['editorialLabel']) && !empty($site['ui']['footer']['editorialUrl'])): ?>
            <span class="footer-sep">|</span>
            <a href="<?= e($site['ui']['footer']['editorialUrl']) ?>"><?= e($site['ui']['footer']['editorialLabel']) ?></a>
          <?php endif; ?>
        </p>
      </div>
    </div>
  </footer>
  <div class="share-modal" data-share-modal aria-hidden="true">
    <div class="share-modal__backdrop" data-share-close></div>
    <div class="share-modal__panel" role="dialog" aria-modal="true" aria-labelledby="share-modal-title">
      <button class="share-modal__close" type="button" aria-label="Fechar" data-share-close>×</button>
      <p class="tag">Partilhar</p>
      <h2 id="share-modal-title">Partilhar notícia</h2>
      <p class="share-modal__hint">Escolhe uma plataforma ou copia o link.</p>
      <div class="share-modal__actions">
        <a class="share-modal__link" data-share-platform="whatsapp" href="#" target="_blank" rel="noopener noreferrer">WhatsApp</a>
        <a class="share-modal__link" data-share-platform="facebook" href="#" target="_blank" rel="noopener noreferrer">Facebook</a>
        <a class="share-modal__link" data-share-platform="x" href="#" target="_blank" rel="noopener noreferrer">X</a>
        <a class="share-modal__link" data-share-platform="linkedin" href="#" target="_blank" rel="noopener noreferrer">LinkedIn</a>
        <a class="share-modal__link" data-share-platform="email" href="#" target="_blank" rel="noopener noreferrer">Email</a>
      </div>
      <div class="share-modal__copy">
        <input class="share-modal__input" type="text" value="" readonly>
        <button class="button share-modal__copy-btn" type="button" data-share-copy>Copiar link</button>
      </div>
      <p class="share-modal__status" role="status" aria-live="polite"></p>
    </div>
  </div>
  <script>
    const header = document.querySelector('.site-header');
    if (header) {
      const offset = header.offsetTop;
      const addAt = offset + 24;
      const removeAt = offset + 12;
      let isScrolled = false;
      let ticking = false;
      const updateHeader = () => {
        const y = window.scrollY;
        if (!isScrolled && y > addAt) {
          header.classList.add('site-header--scrolled');
          isScrolled = true;
        } else if (isScrolled && y < removeAt) {
          header.classList.remove('site-header--scrolled');
          isScrolled = false;
        }
        ticking = false;
      };
      const onScroll = () => {
        if (!ticking) {
          ticking = true;
          window.requestAnimationFrame(updateHeader);
        }
      };
      updateHeader();
      window.addEventListener('scroll', onScroll, { passive: true });
    }
  </script>
  <script>
    (function () {
      const modal = document.querySelector('[data-share-modal]');
      if (!modal) {
        return;
      }

      const panel = modal.querySelector('.share-modal__panel');
      const input = modal.querySelector('.share-modal__input');
      const status = modal.querySelector('.share-modal__status');
      const copyBtn = modal.querySelector('[data-share-copy]');
      const platformLinks = modal.querySelectorAll('[data-share-platform]');
      let lastFocused = null;

      const platformMap = {
        whatsapp: (url, title) => 'https://wa.me/?text=' + encodeURIComponent(title + ' ' + url),
        facebook: (url) => 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url),
        x: (url, title) => 'https://twitter.com/intent/tweet?url=' + encodeURIComponent(url) + '&text=' + encodeURIComponent(title),
        linkedin: (url) => 'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(url),
        email: (url, title) => 'mailto:?subject=' + encodeURIComponent(title) + '&body=' + encodeURIComponent(url),
      };

      const setShareLinks = (url, title) => {
        platformLinks.forEach((link) => {
          const key = link.getAttribute('data-share-platform') || '';
          if (!platformMap[key]) {
            return;
          }
          link.setAttribute('href', platformMap[key](url, title));
        });
      };

      const openModal = () => {
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('is-open');
      };

      const closeModal = () => {
        modal.setAttribute('aria-hidden', 'true');
        modal.classList.remove('is-open');
        if (lastFocused) {
          lastFocused.focus();
        }
      };

      document.addEventListener('click', (event) => {
        const trigger = event.target.closest('.js-share-trigger');
        if (!trigger) {
          return;
        }
        event.preventDefault();
        const id = trigger.getAttribute('data-share-id');
        if (!id) {
          window.location.href = trigger.getAttribute('href');
          return;
        }

        lastFocused = trigger;
        status.textContent = '';
        input.value = 'A gerar link...';
        setShareLinks('#', document.title);
        openModal();

        fetch('/share/' + encodeURIComponent(id) + '?json=1', {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
          .then((response) => response.ok ? response.json() : null)
          .then((data) => {
            if (!data || !data.share_url) {
              input.value = '';
              status.textContent = 'Não foi possível gerar o link.';
              return;
            }
            input.value = data.share_url;
            setShareLinks(data.share_url, document.title);
          })
          .catch(() => {
            input.value = '';
            status.textContent = 'Não foi possível gerar o link.';
          });
      });

      modal.addEventListener('click', (event) => {
        if (event.target.matches('[data-share-close]')) {
          closeModal();
        }
      });

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
          closeModal();
        }
      });

      if (copyBtn) {
        copyBtn.addEventListener('click', () => {
          if (!input.value) {
            return;
          }
          const copyValue = () => {
            input.select();
            input.setSelectionRange(0, 99999);
            document.execCommand('copy');
          };

          if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(input.value)
              .then(() => {
                status.textContent = 'Link copiado.';
              })
              .catch(() => {
                copyValue();
                status.textContent = 'Link copiado.';
              });
          } else {
            copyValue();
            status.textContent = 'Link copiado.';
          }
        });
      }

      modal.addEventListener('click', (event) => {
        if (event.target === modal) {
          closeModal();
        }
      });
    })();
  </script>
</body>
</html>
