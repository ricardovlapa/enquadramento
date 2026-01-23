<?php

function load_env(array $paths): void
{
    foreach ($paths as $path) {
        if (!is_readable($path)) {
            continue;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            continue;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (str_starts_with($trimmed, 'export ')) {
                $trimmed = trim(substr($trimmed, 7));
            }

            $parts = explode('=', $trimmed, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            if ($key === '') {
                continue;
            }

            $rawValue = trim($parts[1]);
            $value = parse_env_value($rawValue);

            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

function parse_env_value(string $value): string
{
    if ($value === '') {
        return '';
    }

    $first = $value[0];
    if ($first === '"' || $first === "'") {
        $quote = $first;
        if (str_ends_with($value, $quote)) {
            $value = substr($value, 1, -1);
        } else {
            $value = substr($value, 1);
        }

        $value = str_replace(
            ['\\n', '\\r', '\\t', '\\"', "\\'", '\\\\'],
            ["\n", "\r", "\t", '"', "'", '\\'],
            $value
        );

        return $value;
    }

    $commentPos = strpos($value, ' #');
    if ($commentPos !== false) {
        $value = substr($value, 0, $commentPos);
    }

    return rtrim($value);
}
