<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Portfolio' ?></title>
    <link rel="stylesheet" href="/fonts.php">
    <link rel="stylesheet" href="/styles.min.css">
</head>

<?php
$currentRoute = trim($_GET['route'] ?? '', '/');
$isHome = $currentRoute === '';
$isBlog = $currentRoute === 'blog' || str_starts_with($currentRoute, 'blog/');
$isShowcase = $currentRoute === 'showcase';
$isPrivacy = $currentRoute === 'privacy';
?>

<body class="min-h-screen bg-taupe-100 text-taupe-900">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <nav class="flex items-center justify-between border-b border-taupe-900/20 py-4 lg:hidden">
            <a href="/" class="inline-flex items-center gap-3">
                <img src="/banner.gif" alt="Logo" height="31" width="81" class="inline-block">
                <span class="hidden text-sm font-medium tracking-wide sm:inline">Mau Seifert</span>
            </a>
            <div class="flex items-center gap-4 text-sm sm:gap-6">
                <a href="/" class="underline-offset-4 hover:underline hover:text-taupe-900/80 <?= $isHome ? 'font-bold' : '' ?>">Home</a>
                <a href="/blog" class="underline-offset-4 hover:underline hover:text-taupe-900/80 <?= $isBlog ? 'font-bold' : '' ?>">Blog</a>
                <a href="/showcase" class="underline-offset-4 hover:underline hover:text-taupe-900/80 <?= $isShowcase ? 'font-bold' : '' ?>">Photos</a>
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
                    </nav>

                    <footer class="border-t border-taupe-900/20 pt-4 text-xs text-taupe-900/70">
                        <p><a href="/privacy" class="<?= $isPrivacy ? 'underline' : 'hover:underline' ?> underline-offset-4">Privacy Policy</a></p>
                    </footer>
                </div>
            </aside>

            <div>
                <main class="py-8 sm:py-10 lg:py-0">
                    <?= $content ?>
                </main>

                <footer class="border-t border-taupe-900/20 py-5 text-xs text-taupe-900/70 lg:hidden">
                    <p><a href="/privacy" class="<?= $isPrivacy ? 'underline' : 'hover:underline' ?> underline-offset-4">Privacy Policy</a></p>
                </footer>
            </div>
        </div>
    </div>
</body>

</html>