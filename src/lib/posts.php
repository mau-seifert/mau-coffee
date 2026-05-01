<?php
require_once __DIR__ . '/leaflet.php';

/**
 * Return an array of published posts ordered by newest first.
 *
 * @return array<int,array>
 */
function get_published_posts(): array
{
    return leaflet_get_all_posts();
}

/**
 * Return one published post by slug.
 */
function get_published_post_by_slug(string $slug): ?array
{
    return leaflet_get_post_by_slug($slug);
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
