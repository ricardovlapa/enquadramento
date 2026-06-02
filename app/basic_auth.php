<?php

function enforce_basic_auth_from_env(): void
{
    $enabled = strtolower((string) getenv('BASIC_AUTH_ENABLED'));
    if (!in_array($enabled, ['1', 'true', 'yes', 'on'], true)) {
        return;
    }

    $expectedUser = (string) getenv('BASIC_AUTH_USER');
    $expectedPass = (string) getenv('BASIC_AUTH_PASS');

    if ($expectedUser === '' || $expectedPass === '') {
        http_response_code(500);
        exit('Basic auth is enabled but not configured.');
    }

    $user = $_SERVER['PHP_AUTH_USER'] ?? '';
    $pass = $_SERVER['PHP_AUTH_PW'] ?? '';

    if (hash_equals($expectedUser, $user) && hash_equals($expectedPass, $pass)) {
        return;
    }

    header('WWW-Authenticate: Basic realm="Enquadramento Dev"');
    header('HTTP/1.1 401 Unauthorized');
    exit('Unauthorized');
}
