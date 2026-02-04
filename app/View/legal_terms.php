<?php
$legal = $site['ui']['terms'] ?? [];
$title = (string) ($legal['title'] ?? '');
$sections = is_array($legal['sections'] ?? null) ? $legal['sections'] : [];
require __DIR__ . '/partials/legal_page.php';
?>
