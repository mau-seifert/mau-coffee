<?php

/**
 * Local Markdown post reader.
 *
 * Reads posts from src/content/posts/*.md (YAML frontmatter + Markdown body).
 * The filename (without .md) becomes the slug and URL path component.
 */

define('POSTS_CONTENT_DIR', __DIR__ . '/../content/posts');

/**
 * Parse a Markdown file with YAML frontmatter into a post array.
 *
 * @return array{id:int,slug:string,title:string,summary:string,body:string,published:int,created_at:string,updated_at:string}|null
 */
function parse_post_file(string $filePath): ?array
{
    $raw = @file_get_contents($filePath);
    if ($raw === false) {
        return null;
    }

    $raw = str_replace(["\r\n", "\r"], "\n", $raw);

    if (!str_starts_with($raw, "---\n")) {
        return null;
    }
    $endPos = strpos($raw, "\n---\n", 4);
    if ($endPos === false) {
        return null;
    }

    $frontmatter = substr($raw, 4, $endPos - 4);
    $body = ltrim(substr($raw, $endPos + 5));

    $meta = [];
    foreach (explode("\n", $frontmatter) as $line) {
        if (preg_match('/^(\w+):\s*"?(.+?)"?\s*$/', $line, $m)) {
            $meta[$m[1]] = $m[2];
        }
    }

    $slug = pathinfo($filePath, PATHINFO_FILENAME);

    return [
        'id'         => abs(crc32($slug)),
        'slug'       => $slug,
        'title'      => $meta['title'] ?? $slug,
        'summary'    => $meta['summary'] ?? '',
        'body'       => $body,
        'published'  => 1,
        'created_at' => $meta['created_at'] ?? '',
        'updated_at' => $meta['updated_at'] ?? $meta['created_at'] ?? '',
    ];
}

/**
 * Return an array of published posts ordered by newest first.
 *
 * @return array<int,array>
 */
function get_published_posts(): array
{
    static $posts = null;
    if ($posts !== null) {
        return $posts;
    }

    $dir = POSTS_CONTENT_DIR;
    if (!is_dir($dir)) {
        $posts = [];
        return $posts;
    }

    $files = glob($dir . '/*.md');
    if ($files === false || empty($files)) {
        $posts = [];
        return $posts;
    }

    $posts = [];
    foreach ($files as $file) {
        $post = parse_post_file($file);
        if ($post !== null) {
            $posts[] = $post;
        }
    }

    usort($posts, static fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

    return $posts;
}

/**
 * Return one published post by slug.
 */
function get_published_post_by_slug(string $slug): ?array
{
    $slug = basename($slug);
    $file = POSTS_CONTENT_DIR . '/' . $slug . '.md';
    if (!is_file($file)) {
        return null;
    }
    return parse_post_file($file);
}

/**
 * Render a minimal, safe subset of Markdown to HTML.
 */
function render_basic_markdown(string $markdown): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $markdown);
    $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    $codeBlocks = [];
    $escaped = preg_replace_callback('/```(.*?)```/s', function (array $m) use (&$codeBlocks): string {
        $index = count($codeBlocks);
        $code = trim($m[1], "\n");
        $codeBlocks[] = '<pre class="overflow-x-auto border border-taupe-900/20 bg-taupe-100/50 p-3 text-sm"><code>' . $code . '</code></pre>';
        return "@@CODEBLOCK{$index}@@";
    }, $escaped) ?? $escaped;

    $escaped = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1" class="my-3 max-w-full sm:max-w-lg rounded">', $escaped) ?? $escaped;
    $escaped = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" class="underline underline-offset-4">$1</a>', $escaped) ?? $escaped;
    $escaped = preg_replace('/`([^`]+)`/', '<code class="border border-taupe-900/20 bg-taupe-100/50 px-1">$1</code>', $escaped) ?? $escaped;
    $escaped = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $escaped) ?? $escaped;
    $escaped = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $escaped) ?? $escaped;

    $lines = explode("\n", $escaped);
    $html = [];
    $inList = false;
    $paragraphLines = [];

    $flushParagraph = function () use (&$paragraphLines, &$html): void {
        if (empty($paragraphLines)) {
            return;
        }
        $html[] = '<p>' . implode('<br>', $paragraphLines) . '</p>';
        $paragraphLines = [];
    };

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '') {
            $flushParagraph();
            if ($inList) {
                $html[] = '</ul>';
                $inList = false;
            }
            continue;
        }

        if (preg_match('/^(#{1,3})\s+(.+)$/', $trimmed, $m) === 1) {
            $flushParagraph();
            if ($inList) {
                $html[] = '</ul>';
                $inList = false;
            }
            $level = strlen($m[1]);
            $sizeClass = $level === 1 ? 'text-2xl' : ($level === 2 ? 'text-xl' : 'text-lg');
            $html[] = '<h' . $level . ' class="' . $sizeClass . ' font-semibold tracking-tight">' . $m[2] . '</h' . $level . '>';
            continue;
        }

        if (preg_match('/^[-*]\s+(.+)$/', $trimmed, $m) === 1) {
            $flushParagraph();
            if (!$inList) {
                $html[] = '<ul class="list-disc pl-5 space-y-1">';
                $inList = true;
            }
            $html[] = '<li>' . $m[1] . '</li>';
            continue;
        }

        if ($inList) {
            $html[] = '</ul>';
            $inList = false;
        }

        $paragraphLines[] = $trimmed;
    }

    $flushParagraph();
    if ($inList) {
        $html[] = '</ul>';
    }

    $output = implode("\n", $html);
    foreach ($codeBlocks as $i => $codeHtml) {
        $output = str_replace("@@CODEBLOCK{$i}@@", $codeHtml, $output);
    }

    return $output;
}
