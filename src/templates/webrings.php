<?php
$title = 'Webrings - Notes by mau';
$metaTitle = 'Webrings';
$metaDescription = 'Small doors to neighboring websites.';

$webrings = [
    [
        'name' => 'Hotline Webring',
        'url' => 'https://hotlinewebring.club/',
        'previous' => 'https://hotlinewebring.club/mau/previous',
        'next' => 'https://hotlinewebring.club/mau/next',
        'color' => '#fa9aba',
        'softColor' => 'rgba(250, 154, 186, 0.22)',
    ],
    [
        'name' => 'Retronaut',
        'url' => 'https://webring.dinhe.net/',
        'previous' => 'https://webring.dinhe.net/prev/https://mau.coffee/webrings',
        'next' => 'https://webring.dinhe.net/next/https://mau.coffee/webrings',
        'color' => '#111111',
        'softColor' => 'rgba(17, 17, 17, 0.14)',
    ],
    [
        'name' => 'Bucket Webring',
        'url' => 'https://webring.bucketfish.me',
        'previous' => 'https://webring.bucketfish.me/redirect.html?to=prev&name=MAU',
        'next' => 'https://webring.bucketfish.me/redirect.html?to=next&name=MAU',
        'color' => '#cdb4ff',
        'softColor' => 'rgba(205, 180, 255, 0.22)',
        'gradient' => 'linear-gradient(90deg, #cdb4ff 0%, #a9cfff 18%, #b6ead7 36%, #d5ffbd 54%, #fff9ae 72%, #ffd6bd 86%, #f0b4d8 100%)',
        'softGradient' => 'linear-gradient(to left, rgba(205, 180, 255, 0.24), rgba(169, 207, 255, 0.18), rgba(213, 255, 189, 0.16), rgba(255, 249, 174, 0.16), transparent)',
    ],
];
?>

<article class="space-y-8">
    <header class="mb-6">
        <h1 class="text-3xl tracking-tight sm:text-4xl">Webrings</h1>
    </header>

    <section class="space-y-4 border-taupe-900/20 dark:border-taupe-100/20">
        <div class="space-y-4">
            <?php foreach ($webrings as $ring): ?>
                <?php
                    $isGradient = isset($ring['gradient'], $ring['softGradient']);
                    $ringStyles = [
                        '--ring-color: ' . $ring['color'],
                        '--ring-color-soft: ' . $ring['softColor'],
                    ];

                    if ($isGradient) {
                        $ringStyles[] = '--ring-gradient: ' . $ring['gradient'];
                        $ringStyles[] = '--ring-gradient-soft: ' . $ring['softGradient'];
                    }
                ?>
                <section
                    class="webring-card <?= $isGradient ? 'webring-card--gradient' : '' ?> group relative flex items-center overflow-hidden border p-3 pl-12 pr-12 transition-colors sm:pl-14 sm:pr-14"
                    style="<?= htmlspecialchars(implode('; ', $ringStyles), ENT_QUOTES, 'UTF-8') ?>;">
                    <span class="webring-gradient pointer-events-none absolute inset-0 z-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></span>

                    <nav aria-label="<?= htmlspecialchars($ring['name'], ENT_QUOTES, 'UTF-8') ?> navigation">
                        <a href="<?= htmlspecialchars($ring['previous'], ENT_QUOTES, 'UTF-8') ?>" class="webring-link webring-nav-link absolute left-2 top-1/2 z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center text-2xl leading-none no-underline transition-colors sm:left-3" aria-label="Previous site">&#8249;</a>
                        <a href="<?= htmlspecialchars($ring['next'], ENT_QUOTES, 'UTF-8') ?>" class="webring-link webring-nav-link absolute right-2 top-1/2 z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center text-2xl leading-none no-underline transition-colors sm:right-3" aria-label="Next site">&#8250;</a>
                    </nav>

                    <div class="relative z-10 min-w-0">
                        <h3 class="truncate text-base font-semibold leading-6">
                            <a href="<?= htmlspecialchars($ring['url'], ENT_QUOTES, 'UTF-8') ?>" class="webring-link underline-offset-4 hover:underline" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($ring['name'], ENT_QUOTES, 'UTF-8') ?></a>
                        </h3>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    </section>
</article>
