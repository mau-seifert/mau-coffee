<?php

require_once __DIR__ . '/posts.php';
require_once __DIR__ . '/site.php';

function feed_xml_escape(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function feed_cdata(string $value): string
{
    return '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $value) . ']]>';
}

function feed_date(string $value): ?string
{
    if (trim($value) === '') {
        return null;
    }

    try {
        return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('UTC'))->format(DATE_RSS);
    } catch (Exception) {
        return null;
    }
}

function feed_absolute_html(string $html): string
{
    return preg_replace_callback(
        '/\b(href|src)="\/(?!\/)([^"]*)"/i',
        static fn(array $match): string => $match[1] . '="' . site_url($match[2]) . '"',
        $html
    ) ?? $html;
}

/**
 * Serve the canonical RSS 2.0 feed and exit.
 */
function serve_feed(): void
{
    $posts = get_published_posts();
    $feedUrl = site_url('/feed.xml');
    $blogUrl = site_url('/blog');

    $latestTimestamp = 0;
    foreach ($posts as $post) {
        $timestamp = strtotime((string) ($post['updated_at'] ?? $post['created_at'] ?? '')) ?: 0;
        $latestTimestamp = max($latestTimestamp, $timestamp);
    }

    $etag = '"' . sha1(serialize($posts)) . '"';
    header('Content-Type: application/rss+xml; charset=utf-8');
    header('Cache-Control: public, max-age=3600');
    header('ETag: ' . $etag);
    header('X-Content-Type-Options: nosniff');
    if ($latestTimestamp > 0) {
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $latestTimestamp) . ' GMT');
    }

    $requestEtag = trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
    $modifiedSince = strtotime((string) ($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '')) ?: 0;
    $etagMatches = $requestEtag !== '' && in_array($etag, array_map('trim', explode(',', $requestEtag)), true);
    $notModified = $requestEtag !== ''
        ? $etagMatches
        : ($latestTimestamp > 0 && $modifiedSince >= $latestTimestamp);
    if ($notModified) {
        http_response_code(304);
        exit;
    }

    $out = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $out .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">' . "\n";
    $out .= "  <channel>\n";
    $out .= '    <title>' . feed_xml_escape(SITE_NAME) . "</title>\n";
    $out .= '    <link>' . feed_xml_escape($blogUrl) . "</link>\n";
    $out .= '    <description>' . feed_xml_escape(SITE_DESCRIPTION) . "</description>\n";
    $out .= '    <language>en</language>' . "\n";
    $out .= '    <atom:link href="' . feed_xml_escape($feedUrl) . '" rel="self" type="application/rss+xml" />' . "\n";
    if ($latestTimestamp > 0) {
        $out .= '    <lastBuildDate>' . gmdate(DATE_RSS, $latestTimestamp) . "</lastBuildDate>\n";
    }

    foreach ($posts as $post) {
        $postUrl = site_url('/blog/' . rawurlencode((string) $post['slug']));
        $published = feed_date((string) ($post['created_at'] ?? ''));
        $content = feed_absolute_html(render_basic_markdown((string) ($post['body'] ?? '')));

        $out .= "    <item>\n";
        $out .= '      <title>' . feed_xml_escape((string) ($post['title'] ?? '')) . "</title>\n";
        $out .= '      <link>' . feed_xml_escape($postUrl) . "</link>\n";
        $out .= '      <guid isPermaLink="true">' . feed_xml_escape($postUrl) . "</guid>\n";
        if ($published !== null) {
            $out .= '      <pubDate>' . $published . "</pubDate>\n";
        }
        $out .= '      <description>' . feed_xml_escape((string) ($post['summary'] ?? '')) . "</description>\n";
        $out .= '      <content:encoded>' . feed_cdata($content) . "</content:encoded>\n";
        $out .= "    </item>\n";
    }

    $out .= "  </channel>\n";
    $out .= "</rss>\n";

    echo $out;
    exit;
}
