<?php
$currentRoute = trim($_GET['route'] ?? '', '/');
$isHome = $currentRoute === '';
$isBlog = $currentRoute === 'blog' || str_starts_with($currentRoute, 'blog/');
$isShowcase = $currentRoute === 'showcase';
$isWebrings = $currentRoute === 'webrings';
$isPrivacy = $currentRoute === 'privacy';

$siteDescription = 'Make yourself at home, pour a cup, and linger for a moment.';
$pageTitle = (string) ($title ?? 'Notes by mau');
$pageMetaTitle = (string) ($metaTitle ?? $pageTitle);
$pageMetaDescription = (string) ($metaDescription ?? $siteDescription);

$shortOgTitle = function_exists('og_truncate_text') ? og_truncate_text($pageMetaTitle, 32) : $pageMetaTitle;
$shortOgDescription = function_exists('og_truncate_text') ? og_truncate_text($pageMetaDescription, 72) : $pageMetaDescription;

$origin = 'https://mau.coffee';
$canonicalPath = $currentRoute === '' ? '/' : '/' . $currentRoute;
$canonicalUrl = $origin . $canonicalPath;

$ogImageParams = [
    'type' => $isShowcase ? 'showcase' : 'page',
    'title' => $shortOgTitle,
    'description' => $shortOgDescription,
];

if ($isShowcase) {
    $images = get_showcase_image_filenames();
    $totalImages = count($images);
    if ($totalImages > 0) {
        $timeBucket = (int) floor(time() / 21600);
        $ogImageParams['image'] = $images[$timeBucket % $totalImages];
    }
}

$ogImageUrl = $origin . '/og-image.png?' . http_build_query($ogImageParams, '', '&', PHP_QUERY_RFC3986);
$carbonResult = function_exists('website_carbon_result') ? website_carbon_result() : null;
$carbonLabel = (string) ($carbonResult['label'] ?? 'report');
$carbonTitle = (string) ($carbonResult['title'] ?? 'Website Carbon report for mau.coffee');
$carbonValueClass = isset($carbonResult['label']) ? 'whitespace-nowrap opacity-70' : 'whitespace-nowrap';
$externalLinkIcon = '<span class="whitespace-nowrap opacity-70" aria-hidden="true">↗</span>';

?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($shortOgDescription, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:type" content="<?= isset($post) && is_array($post) ? 'article' : 'website' ?>">
    <meta property="og:site_name" content="Notes by mau">
    <meta property="og:title" content="<?= htmlspecialchars($shortOgTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($shortOgDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImageUrl, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image:secure_url" content="<?= htmlspecialchars($ogImageUrl, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($shortOgTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($shortOgDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImageUrl, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="/fonts.php">
    <link rel="stylesheet" href="/styles.min.css">
    <link rel="icon" type="image/png" href="/manifest/favicon-96x96.png?v=20260604" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/manifest/favicon.svg?v=20260604" />
    <link rel="shortcut icon" href="/manifest/favicon.ico?v=20260604" />
    <link rel="apple-touch-icon" sizes="180x180" href="/manifest/apple-touch-icon.png?v=20260604" />
    <meta name="apple-mobile-web-app-title" content="Mau" />
    <link rel="manifest" href="/manifest/site.webmanifest?v=20260604" />
</head>

<body class="min-h-screen bg-taupe-100 text-taupe-900">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <nav class="flex items-center justify-between border-b border-taupe-900/20 py-4 lg:hidden">
            <a href="/" class="inline-flex items-center gap-3">
                <img src="/banner.gif" alt="Logo" height="31" width="81" class="inline-block">
                <span class="hidden text-sm font-medium tracking-wide sm:inline">Mau Seifert</span>
            </a>
            <div class="flex items-center gap-3 text-xs sm:gap-5 sm:text-sm">
                <a href="/" class="underline-offset-4 hover:underline hover:text-taupe-900/80 <?= $isHome ? 'font-bold' : '' ?>">Home</a>
                <a href="/blog" class="underline-offset-4 hover:underline hover:text-taupe-900/80 <?= $isBlog ? 'font-bold' : '' ?>">Blog</a>
                <a href="/showcase" class="underline-offset-4 hover:underline hover:text-taupe-900/80 <?= $isShowcase ? 'font-bold' : '' ?>">Photos</a>
                <a href="/webrings" class="underline-offset-4 hover:underline hover:text-taupe-900/80 <?= $isWebrings ? 'font-bold' : '' ?>">Rings</a>
            </div>
        </nav>

        <div class="lg:grid lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-12 lg:py-10">
            <aside class="hidden lg:block self-start sticky top-10">
                <div class="space-y-8 pr-8">
                    <a href="/" class="inline-flex items-center gap-3">
                        <img src="/banner.gif" alt="Logo" height="31" width="81" class="inline-block">
                    </a>

                    <nav class="space-y-3 text-sm">
                        <a href="/" class="block underline-offset-4 hover:underline hover:text-taupe-900/80 <?= $isHome ? 'font-bold' : '' ?>"><?= $isHome ? '• ' : '' ?>Home</a>
                        <a href="/blog" class="block underline-offset-4 hover:underline hover:text-taupe-900/80 <?= $isBlog ? 'font-bold' : '' ?>"><?= $isBlog ? '• ' : '' ?>Blog</a>
                        <a href="/showcase" class="block underline-offset-4 hover:underline hover:text-taupe-900/80 <?= $isShowcase ? 'font-bold' : '' ?>"><?= $isShowcase ? '• ' : '' ?>Photos</a>
                        <a href="/webrings" class="block underline-offset-4 hover:underline hover:text-taupe-900/80 <?= $isWebrings ? 'font-bold' : '' ?>"><?= $isWebrings ? '• ' : '' ?>Webrings</a>
                    </nav>

                    <footer class="border-t border-taupe-900/20 pt-4 text-xs text-taupe-900/70">
                        <p class="flex flex-wrap items-center gap-4">
                            <a href="/privacy" class="<?= $isPrivacy ? 'underline' : 'hover:underline' ?> underline-offset-4">Privacy</a>
                            <a href="https://github.com/mau-seifert/mau-coffee" class="inline-flex items-baseline gap-[0.35rem] text-taupe-900/70 hover:underline" target="_blank" rel="noopener noreferrer" aria-label="Source opens an external website"><span>Source</span><?= $externalLinkIcon ?></a>
                            <span>
                                <a class="inline-flex whitespace-nowrap bg-left-bottom bg-no-repeat [background-image:linear-gradient(currentColor,currentColor)] [background-size:0_1px] items-baseline gap-[0.35rem] text-inherit hover:[background-size:100%_1px]" href="<?= htmlspecialchars(WEBSITE_CARBON_REPORT_URL, ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($carbonTitle, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= htmlspecialchars($carbonTitle . ' opens an external website', ENT_QUOTES, 'UTF-8') ?>">
                                    <span>Carbon</span>
                                    <span class="<?= htmlspecialchars($carbonValueClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($carbonLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?= $externalLinkIcon ?>
                                </a>
                            </span>
                        </p>
                    </footer>
                </div>
            </aside>

            <div>
                <main class="py-8 sm:py-10 lg:py-0">
                    <?= $content ?>
                </main>

                <footer class="border-t border-taupe-900/20 py-5 text-xs text-taupe-900/70 lg:hidden">
                    <p class="flex flex-wrap items-center gap-4">
                        <a href="/privacy" class="<?= $isPrivacy ? 'underline' : 'hover:underline' ?> underline-offset-4">Privacy</a>
                        <a href="https://github.com/mau-seifert/mau-coffee" class="inline-flex items-baseline gap-[0.35rem] text-taupe-900/70 hover:underline" target="_blank" rel="noopener noreferrer" aria-label="Source opens an external website"><span>Source</span><?= $externalLinkIcon ?></a>
                        <span>
                            <a class="inline-flex whitespace-nowrap bg-bottom-left bg-no-repeat bg-[linear-gradient(currentColor,currentColor)] bg-size-[0_1px] items-baseline gap-[0.35rem] text-inherit hover:bg-size-[100%_1px]" href="<?= htmlspecialchars(WEBSITE_CARBON_REPORT_URL, ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($carbonTitle, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= htmlspecialchars($carbonTitle . ' opens an external website', ENT_QUOTES, 'UTF-8') ?>">
                                <span>Carbon</span>
                                <span class="<?= htmlspecialchars($carbonValueClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($carbonLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                <?= $externalLinkIcon ?>
                            </a>
                        </span>
                    </p>
                </footer>
            </div>
        </div>
    </div>
</body>

</html>