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
