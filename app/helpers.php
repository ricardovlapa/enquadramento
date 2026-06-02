<?php
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function markdown(?string $value): string
{
    $escaped = e($value);
    if ($escaped === '') {
        return '';
    }

    $escaped = preg_replace('/\\*\\*(.+?)\\*\\*/s', '<strong>$1</strong>', $escaped);
    $escaped = preg_replace('/\\*(.+?)\\*/s', '<em>$1</em>', $escaped);
    $escaped = preg_replace('/\\^(.+?)\\^/s', '<small>$1</small>', $escaped);
    $escaped = str_replace("\n", "<br>\n", $escaped);

    return $escaped;
}

function format_date(?string $value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $date = DateTime::createFromFormat('Y-m-d', $value);
    if ($date instanceof DateTime) {
        return $date->format('d-m-Y');
    }

    return $value;
}

function format_datetime(?string $value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }

    return date('d-m-Y H:i', $timestamp);
}

function image_src(?string $value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    if (!preg_match('#^(https?://|/|data:)#', $value)) {
        $value = '/' . $value;
    }

    return e($value);
}

function news_card_actions(array $item, string $variant = ''): string
{
    $link = trim((string) ($item['link'] ?? ''));
    if ($link === '') {
        return '';
    }

    $title = trim((string) ($item['title'] ?? 'Ler notícia'));
    $classes = trim('news-card-actions ' . $variant);
    $html = '<a class="news-card-overlay" href="' . e($link) . '" target="_blank" rel="noopener noreferrer" aria-label="' . e($title) . '"></a>';
    $html .= '<div class="' . e($classes) . '">';
    $html .= '<button class="news-card-actions__toggle" type="button" aria-label="Opções" aria-expanded="false" data-card-menu-toggle>&hellip;</button>';
    $html .= '<div class="news-card-actions__menu" data-card-menu>';

    if (!empty($item['id'])) {
        $id = e((string) $item['id']);
        $html .= '<a class="news-card-actions__item js-share-trigger" href="/share/' . $id . '" data-share-id="' . $id . '" data-share-title="' . e($title) . '">Partilhar</a>';
    }

    $html .= '<a class="news-card-actions__item" href="' . e($link) . '" target="_blank" rel="noopener noreferrer">Ler notícia</a>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function opinion_card_actions(array $article, string $variant = ''): string
{
    $slug = trim((string) ($article['slug'] ?? ''));
    if ($slug === '') {
        return '';
    }

    $link = '/opiniao-enquadramento/' . rawurlencode($slug);
    $title = trim((string) ($article['title'] ?? 'Ler artigo'));
    $classes = trim('news-card-actions ' . $variant);
    $html = '<a class="news-card-overlay" href="' . e($link) . '" aria-label="' . e($title) . '"></a>';
    $html .= '<div class="' . e($classes) . '">';
    $html .= '<button class="news-card-actions__toggle" type="button" aria-label="Opções" aria-expanded="false" data-card-menu-toggle>&hellip;</button>';
    $html .= '<div class="news-card-actions__menu" data-card-menu>';
    $html .= '<a class="news-card-actions__item" href="' . e($link) . '">Ler artigo</a>';
    $html .= '<a class="news-card-actions__item js-share-trigger" href="/share/opiniao/' . e($slug) . '" data-share-url="/share/opiniao/' . e($slug) . '" data-share-title="' . e($title) . '">Partilhar</a>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}
