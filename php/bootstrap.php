<?php

declare(strict_types=1);

/**
 * Initialize Twig with shared filters and functions.
 */
function buildTwigEnvironment(string $viewsPath, bool $debug = false): \Twig\Environment
{
    $loader = new \Twig\Loader\FilesystemLoader($viewsPath);
    $twig = new \Twig\Environment($loader, [
        'cache' => __DIR__ . '/../tmp',
        'debug' => $debug,
        'auto_reload' => true,
    ]);

    $twig->addFilter(new \Twig\TwigFilter('lang', 'lang'));
    $twig->addFilter(new \Twig\TwigFilter('showCurrency', 'showCurrency'));
    $twig->addFunction(new \Twig\TwigFunction('path', 'path'));
    $twig->addFunction(new \Twig\TwigFunction('generateToken', 'generateToken'));

    return $twig;
}

/**
 * Normalize request path and remove trailing slash.
 */
function normalizeRequestPath(?string $path): string
{
    if ($path === null || $path === '') {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url((string) $requestUri, PHP_URL_PATH) ?: '/';
    }

    $path = trim((string)$path);
    $path = parse_url($path, PHP_URL_PATH) ?: '';
    $path = rtrim($path, '/');

    return $path === '' ? '/' : $path;
}

/**
 * Split request path into normalized segments.
 */
function getPathParts(string $path): array
{
    $trimmed = trim($path, '/');
    if ($trimmed === '') {
        return [];
    }

    return array_values(array_filter(explode('/', $trimmed), strlen(...)));
}

/**
 * Resolve the active frontend controller from path segments.
 */
function resolveController(array $links, array $pathParts): string
{
    if (empty($pathParts)) {
        return 'index';
    }

    $firstSegment = $pathParts[0];
    $controller = array_search($firstSegment, $links, true);
    if ($controller === false && isset($links[$firstSegment])) {
        $controller = $firstSegment;
    }

    if ($controller === false) {
        $slugParts = explode(',', (string) $firstSegment);
        if (count($slugParts) === 2 && (int)$slugParts[0] > 0 && $slugParts[1] !== '') {
            $_GET['id'] = $slugParts[0];
            $_GET['slug'] = $slugParts[1];
            return 'offer';
        }
    } elseif ($controller === 'profile' && isset($pathParts[1])) {
        $_GET['slug'] = $pathParts[1];
    } elseif (isset($pathParts[1])) {
        $slugParts = explode(',', $pathParts[1]);
        if (count($slugParts) === 2 && (int)$slugParts[0] > 0 && $slugParts[1] !== '') {
            $_GET['id'] = $slugParts[0];
            $_GET['slug'] = $slugParts[1];
        }
    }

    if ($controller === false && (
        str_starts_with((string) $firstSegment, _PREFIX_STATE_) ||
        str_starts_with((string) $firstSegment, _PREFIX_CATEGORY_) ||
        str_starts_with((string) $firstSegment, _PREFIX_TYPE_)
    )) {
        return 'offers';
    }

    if ($controller === false) {
        throw new noFoundException();
    }

    return $controller;
}

/**
 * Start a secure session with cookie parameters and timeout logic.
 */
function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_path', '/');
        ini_set('session.cookie_samesite', 'Lax');
        if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1 || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
            ini_set('session.cookie_secure', '1');
        }
        session_start();
    }

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['last_activity'] = time();
}
