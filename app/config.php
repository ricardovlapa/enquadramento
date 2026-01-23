<?php

if (!function_exists('env_string')) {
    function env_string(string $key, string $default = ''): string
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            return $default;
        }

        return $value;
    }
}

if (!function_exists('env_bool')) {
    function env_bool(string $key, bool $default): bool
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

if (!function_exists('env_list')) {
    function env_list(string $key, array $default = []): array
    {
        $value = getenv($key);
        if ($value === false || trim($value) === '') {
            return $default;
        }

        $items = array_map('trim', explode(',', $value));
        return array_values(array_filter($items, fn(string $item): bool => $item !== ''));
    }
}

if (!function_exists('load_json_array')) {
    function load_json_array(string $path): array
    {
        if (!file_exists($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return [];
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}

$appEnv = strtolower((string) getenv('APP_ENV'));
$adSlotsVisible = $appEnv !== 'production';
$adSlotsVisible = env_bool('AD_SLOTS_VISIBLE', $adSlotsVisible);

$siteTitle = env_string('SITE_TITLE', 'enquadramento');
$siteTagline = env_string('SITE_TAGLINE', 'O essencial da atualidade, organizado por tema.');
$siteDescription = env_string('SITE_DESCRIPTION', 'O essencial da atualidade, organizado por tema.');
$siteBaseUrl = env_string('SITE_BASE_URL', '');
$defaultSocialImagePath = '/assets/images/socialImage.jpg';
$siteSocialImage = env_string(
    'SITE_SOCIAL_IMAGE',
    $siteBaseUrl !== '' ? rtrim($siteBaseUrl, '/') . $defaultSocialImagePath : $defaultSocialImagePath
);

$authorName = env_string('AUTHOR_NAME', 'Site Author');
$newsCategoriesFile = __DIR__ . '/Data/categories.json';
$newsCategoryTrainingFile = env_string('NEWS_CATEGORY_TRAINING_DATA', __DIR__ . '/Data/category_training.json');
$newsCategoriesRaw = load_json_array($newsCategoriesFile);
$newsCategories = [];
$newsCategoryGroups = [];
$newsHomeSections = [];
$newsCategorySubsections = [];
$newsCategoryParentMap = [];
$categoryGroupConfig = [
    'featured' => ['includeInNav' => false, 'includeInHome' => true],
    'sections' => ['includeInNav' => true, 'includeInHome' => true],
    'opinion' => ['includeInNav' => true, 'includeInHome' => false],
    'otherCategories' => ['includeInNav' => true, 'includeInHome' => false],
];

foreach ($categoryGroupConfig as $groupKey => $flags) {
    $categoryGroup = $newsCategoriesRaw[$groupKey] ?? null;
    if (!is_array($categoryGroup)) {
        continue;
    }

    $items = $categoryGroup['items'] ?? [];
    if (!is_array($items)) {
        $items = [];
    }

    $groupLabel = trim((string) ($categoryGroup['label'] ?? ''));
    $groupItems = [];

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $slug = trim((string) ($item['slug'] ?? ''));
        $label = trim((string) ($item['label'] ?? ''));
        $subtitle = trim((string) ($item['subtitle'] ?? ''));
        if ($slug === '' || $label === '') {
            continue;
        }
        $aliases = $item['aliases'] ?? [];
        if (!is_array($aliases)) {
            $aliases = [];
        }
        $newsCategories[] = [
            'slug' => $slug,
            'label' => $label,
            'aliases' => $aliases,
            'subtitle' => $subtitle,
        ];

        $groupItem = ['slug' => $slug, 'label' => $label, 'subtitle' => $subtitle];
        $subItemsRaw = $item['sub-categories'] ?? $item['subcategories'] ?? [];
        if (is_array($subItemsRaw) && $subItemsRaw !== []) {
            $subItems = [];
            foreach ($subItemsRaw as $subItem) {
                if (!is_array($subItem)) {
                    continue;
                }
                $subSlug = trim((string) ($subItem['slug'] ?? ''));
                $subLabel = trim((string) ($subItem['label'] ?? ''));
                if ($subSlug === '' || $subLabel === '') {
                    continue;
                }
                $subAliases = $subItem['aliases'] ?? [];
                if (!is_array($subAliases)) {
                    $subAliases = [];
                }
                $subItems[] = ['slug' => $subSlug, 'label' => $subLabel];
                $newsCategories[] = [
                    'slug' => $subSlug,
                    'label' => $subLabel,
                    'aliases' => $subAliases,
                ];
                $newsCategoryParentMap[$subSlug] = $slug;
            }
            if ($subItems !== []) {
                $groupItem['children'] = $subItems;
                $newsCategorySubsections[$slug] = $subItems;
            }
        }
        $groupItems[] = $groupItem;
    }

    if (!empty($flags['includeInNav']) && $groupLabel !== '' && $groupItems !== []) {
        $newsCategoryGroups[] = [
            'label' => $groupLabel,
            'items' => $groupItems,
        ];
    }

    if (!empty($flags['includeInHome']) && $groupItems !== []) {
        foreach ($groupItems as $item) {
            $newsHomeSections[] = $item;
        }
    }
}

return [
    'site' => [
        'title' => $siteTitle,
        'tagline' => $siteTagline,
        'description' => $siteDescription,
        'baseUrl' => $siteBaseUrl,
        'allowedHosts' => env_list('SITE_ALLOWED_HOSTS', []),
        'language' => env_string('SITE_LANGUAGE', 'pt-PT'),
        'ogLocale' => env_string('SITE_OG_LOCALE', 'pt_PT'),
        'socialImage' => $siteSocialImage,
        'adSlotsVisible' => $adSlotsVisible,
        'branding' => [
            'logo' => env_string('SITE_LOGO', '/assets/images/enquadramento_logo_nav.png'),
            'logoAlt' => env_string('SITE_LOGO_ALT', $siteTitle),
            'footerAvatar' => env_string('SITE_FOOTER_AVATAR', '/assets/images/default_image_enquadramento_100x100.png'),
            'footerAvatarAlt' => env_string('SITE_FOOTER_AVATAR_ALT', 'Enquadramento'),
        ],
        'author' => [
            'name' => $authorName,
        ],
        'social' => [
            'facebook' => env_string('SOCIAL_FACEBOOK', ''),
            'instagram' => env_string('SOCIAL_INSTAGRAM', ''),
            'linkedin' => env_string('SOCIAL_LINKEDIN', ''),
        ],
        'analytics' => [
            'googleMeasurementId' => env_string('GOOGLE_ANALYTICS_ID', ''),
        ],
        'navHomeLabel' => env_string('NAV_HOME_LABEL', 'Início'),
        'navigation' => [
            // Add future links here, e.g. ['label' => 'Sobre', 'href' => '/about'].
        ],
        'newsCategories' => $newsCategoryGroups,
        'newsHomeSections' => $newsHomeSections,
        'newsCategorySubsections' => $newsCategorySubsections,
        'newsCategoryParentMap' => $newsCategoryParentMap,
        'ui' => [
            'home' => [
                'description' => env_string(
                    'HOME_DESCRIPTION',
                    'Notícias de várias fontes, reunidas e enquadradas para ajudar a compreender o que está a acontecer.'
                ),
                'ctaLabel' => env_string('HOME_CTA_LABEL', 'Ver notícias'),
                'ctaUrl' => env_string('HOME_CTA_URL', '/todas-as-noticias'),
                'emptyPosts' => env_string('HOME_EMPTY_POSTS', 'Ainda não há artigos. Adicione um a posts.json.'),
                'newsTitle' => env_string('HOME_NEWS_TITLE', 'Últimas notícias'),
                'newsSubtitle' => env_string('HOME_NEWS_SUBTITLE', 'Atualizado continuamente'),
                'categorySubtitle' => env_string('HOME_CATEGORY_SUBTITLE', 'Lugar para subtítulo'),
                'emptyNews' => env_string(
                    'HOME_EMPTY_NEWS',
                    'Ainda não há notícias. Execute o fetch manual para carregar itens.'
                ),
            ],
            'blog' => [
                'title' => env_string('BLOG_TITLE', 'Todos os artigos'),
                'emptyPosts' => env_string('BLOG_EMPTY_POSTS', 'Ainda não há artigos. Adicione um a posts.json.'),
                'emptyTag' => env_string('BLOG_EMPTY_TAG', 'Sem artigos para a tag “%s”.'),
                'tagsLabel' => env_string('BLOG_TAGS_LABEL', 'Tags'),
                'feed' => [
                    'enabled' => env_bool('BLOG_FEED_ENABLED', false),
                    'title' => env_string('BLOG_FEED_TITLE', 'Acompanha as novidades'),
                    'description' => env_string(
                        'BLOG_FEED_DESCRIPTION',
                        'Atualizações mensais, sem ruído. Subscreve no teu leitor de feeds favorito.'
                    ),
                    'ctaLabel' => env_string('BLOG_FEED_CTA_LABEL', 'Abrir feed'),
                    'ctaUrl' => env_string('BLOG_FEED_CTA_URL', '/feed.xml'),
                ],
            ],
            'news' => [
                'title' => env_string('NEWS_TITLE', 'Notícias'),
                'emptyItems' => env_string(
                    'NEWS_EMPTY_ITEMS',
                    'Ainda não há notícias. Execute o fetch manual para carregar itens.'
                ),
                'emptyCategory' => env_string('NEWS_EMPTY_CATEGORY', 'Sem notícias para a categoria “%s”.'),
                'categoriesLabel' => env_string('NEWS_CATEGORIES_LABEL', 'Categorias'),
                'categoriesTitle' => env_string('NEWS_CATEGORIES_TITLE', 'Categorias de notícias'),
                'emptyCategories' => env_string('NEWS_EMPTY_CATEGORIES', 'Ainda não há categorias disponíveis.'),
            ],
            'footer' => [
                'text' => env_string(
                    'FOOTER_TEXT',
                    'O essencial da atualidade, organizado por tema.'
                ),
                'editorialLabel' => env_string('FOOTER_EDITORIAL_LABEL', ''),
                'editorialUrl' => env_string('FOOTER_EDITORIAL_URL', ''),
            ],
            'editorial' => [
                'title' => env_string('EDITORIAL_TITLE', 'Nota editorial e de privacidade'),
                'sections' => [
                    [
                        'title' => env_string('EDITORIAL_SECTION1_TITLE', 'Autoria e responsabilidade editorial'),
                        'paragraphs' => [
                            env_string(
                                'EDITORIAL_SECTION1_P1',
                                'Todos os textos publicados neste site são da autoria de ' . $authorName . ', salvo indicação explícita em contrário.'
                            ),
                            env_string(
                                'EDITORIAL_SECTION1_P2',
                                'As opiniões expressas refletem uma perspetiva pessoal e não representam, por si só, posições institucionais de terceiros.'
                            ),
                        ],
                    ],
                    [
                        'title' => env_string('EDITORIAL_SECTION2_TITLE', 'Imagens e ilustrações'),
                        'paragraphs' => [
                            env_string(
                                'EDITORIAL_SECTION2_P1',
                                'As imagens que acompanham os textos são ilustrações de caráter sugestivo, em muitos casos geradas por ferramentas de inteligência artificial.'
                            ),
                            env_string(
                                'EDITORIAL_SECTION2_P2',
                                'Não representam pessoas reais, acontecimentos concretos ou situações factuais, servindo apenas como apoio visual ao conteúdo escrito.'
                            ),
                        ],
                    ],
                    [
                        'title' => env_string('EDITORIAL_SECTION3_TITLE', 'Privacidade'),
                        'paragraphs' => [
                            env_string(
                                'EDITORIAL_SECTION3_P1',
                                'Este site não recolhe dados pessoais dos visitantes, não utiliza cookies de rastreamento nem sistemas de perfilagem.'
                            ),
                            env_string(
                                'EDITORIAL_SECTION3_P2',
                                'Não são efetuados registos de navegação para fins comerciais ou publicitários.'
                            ),
                        ],
                    ],
                    [
                        'title' => env_string('EDITORIAL_SECTION4_TITLE', 'Contacto'),
                        'paragraphs' => [
                            env_string(
                                'EDITORIAL_SECTION4_P1',
                                'Para qualquer esclarecimento relacionado com este site ou com os seus conteúdos, poderá ser utilizado o contacto disponibilizado na página respetiva.'
                            ),
                        ],
                    ],
                ],
            ],
        ],
    ],
    'newsData' => __DIR__ . '/Data/items.json',
    'newsSourcesData' => __DIR__ . '/Data/newsFeed.json',
    'newsCategoriesData' => $newsCategoriesFile,
    'newsCategoryTrainingData' => $newsCategoryTrainingFile,
    'articlesData' => __DIR__ . '/Data/articles.json',
    'authorsData' => __DIR__ . '/Data/authors.json',
    'newsCategories' => $newsCategories,
];
