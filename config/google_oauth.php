<?php
function load_env_file() {
    static $loaded = false;

    if ($loaded) {
        return;
    }

    $env_file = dirname(__DIR__) . '/.env';
    if (!is_file($env_file)) {
        $loaded = true;
        return;
    }

    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        $loaded = true;
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (strpos($line, '=') === false) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (($value !== '' && ($value[0] === '"' || $value[0] === "'")) && substr($value, -1) === $value[0]) {
            $value = substr($value, 1, -1);
        }

        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }

        if (getenv($name) === false) {
            putenv($name . '=' . $value);
        }
    }

    $loaded = true;
}

function get_google_oauth_config() {
    load_env_file();

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $project_base = trim(project_url('auth/google_callback.php'), '/');
    $redirect_uri = getenv('GOOGLE_REDIRECT_URI') ?: $scheme . '://' . $host . '/' . $project_base;

    return [
        'client_id' => getenv('GOOGLE_CLIENT_ID') ?: '',
        'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
        'redirect_uri' => $redirect_uri,
        'auth_endpoint' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_endpoint' => 'https://oauth2.googleapis.com/token',
        'userinfo_endpoint' => 'https://www.googleapis.com/oauth2/v2/userinfo',
    ];
}
