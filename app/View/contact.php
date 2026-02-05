<?php
$contact = $site['ui']['contact'] ?? [];
$title = (string) ($contact['title'] ?? '');
$sections = is_array($contact['sections'] ?? null) ? $contact['sections'] : [];
require __DIR__ . '/partials/legal_page.php';
?>
