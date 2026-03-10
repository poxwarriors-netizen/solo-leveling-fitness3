<?php
// PHP built-in server router for solo-leveling-fitness
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Strip /solo-leveling-fitness/ prefix if present
if (strpos($uri, '/solo-leveling-fitness') === 0) {
    $uri = substr($uri, strlen('/solo-leveling-fitness'));
    if ($uri === '' || $uri === false) $uri = '/';
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['SCRIPT_NAME'] = $uri;
    $_SERVER['PHP_SELF'] = $uri;
}

$file = __DIR__ . $uri;

// If file exists
if (is_file($file)) {
    $ext = pathinfo($file, PATHINFO_EXTENSION);

    if ($ext === 'php') {
        chdir(dirname($file));
        include $file;
        return true;
    }

    // Serve static files manually
    $mime_types = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf',
        'eot'  => 'application/vnd.ms-fontobject',
    ];

    $mime = $mime_types[$ext] ?? 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($file));
    readfile($file);
    return true;
}

// Default to index.php
if (is_dir($file)) {
    if (is_file($file . '/index.php')) {
        chdir($file);
        include $file . '/index.php';
        return true;
    }
}

// Root
if ($uri === '/' || $uri === '') {
    chdir(__DIR__);
    include __DIR__ . '/index.php';
    return true;
}

http_response_code(404);
echo '404 Not Found: ' . htmlspecialchars($uri);
return true;
