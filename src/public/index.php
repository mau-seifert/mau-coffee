<?php
require_once __DIR__ . '/../lib/posts.php';
require_once __DIR__ . '/../lib/images.php';
require_once __DIR__ . '/../lib/sitemap.php';

$route = trim($_GET['route'] ?? '/', '/');


if ($route === 'sitemap.xml' || $route === 'sitemap') {
    serve_sitemap();
}

if ($route === 'og-image.svg' || $route === 'og-image') {
    serve_open_graph_image();
}

if (str_starts_with($route, 'img/showcase/')) {
    serve_showcase_image(substr($route, strlen('img/showcase/')));
}

/**
 * Render a template with the given variables and include it in the main layout.
 * 
 * @param string $template The name of the template to render (without .php extension).
 * @param array $vars An associative array of variables to extract for use in the template.
 * @return void
 */
function render(string $template, array $vars = [], ?int $statusCode = null): void
{
    if ($statusCode !== null && !headers_sent()) {
        http_response_code($statusCode);
    }

    extract($vars);

    $content = null;
    if ($template === 'showcase') {
        $showcaseDir = showcase_image_base_dir();
        $dirMtime = $showcaseDir !== '' ? (int) (filemtime($showcaseDir) ?: 0) : 0;
        $templatePath = __DIR__ . '/../templates/showcase.php';
        $templateMtime = (int) (filemtime($templatePath) ?: 0);
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $cacheDir = showcase_cache_dir() . '/pages';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        $cacheKey = sha1('showcase|' . $page . '|' . $dirMtime . '|' . $templateMtime);
        $cacheFile = $cacheDir . '/' . $cacheKey . '.html';

        if (is_file($cacheFile)) {
            $cached = @file_get_contents($cacheFile);
            if ($cached !== false) {
                $content = $cached;
            }
        }

        if ($content === null) {
            ob_start();
            require $templatePath;
            $content = (string) ob_get_clean();

            $tmp = $cacheFile . '.tmp.' . getmypid() . '.' . uniqid('', true);
            @file_put_contents($tmp, $content);
            @rename($tmp, $cacheFile);
        }
    } else {
        ob_start();
        require __DIR__ . '/../templates/' . $template . '.php';
        $content = (string) ob_get_clean();
    }

    require __DIR__ . '/../templates/layout.php';
}

$matchResult = match (true) {
    $route === ''           => ['home', [], 200],
    $route === 'blog'       => ['blog-list', [], 200],
    $route === 'showcase'   => ['showcase', [], 200],
    $route === 'privacy'    => ['privacy', [], 200],
    str_starts_with($route, 'blog/') => ['blog-post', [
        'slug' => explode('/', $route)[1]
    ], 200],
    default                 => ['404', [], 404],
};

[$template, $vars, $statusCode] = $matchResult;
$vars['route'] = $route;

if ($template === 'blog-list') {
    $vars['posts'] = get_published_posts();
}

if ($template === 'home') {
    $vars['posts'] = array_slice(get_published_posts(), 0, 3);
}

if ($template === 'blog-post') {
    $slug = (string)($vars['slug'] ?? '');
    $post = $slug !== '' ? get_published_post_by_slug($slug) : null;
    if ($post === null) {
        $template = '404';
        $vars = [];
        $statusCode = 404;
    } else {
        $vars['post'] = $post;
    }
}

render($template, $vars, $statusCode);
