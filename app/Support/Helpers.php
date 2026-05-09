<?php

declare(strict_types=1);

namespace App\Support;

function base_url(string $path = ''): string
{
    $appUrl = rtrim(Env::get('APP_URL', 'http://localhost'), '/');
    $suffix = ltrim($path, '/');

    return $suffix === '' ? $appUrl : $appUrl . '/' . $suffix;
}

function asset(string $path): string
{
    return base_url($path);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
