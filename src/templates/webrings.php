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
        'color' => '#7651b8',
        'softColor' => 'rgba(118, 81, 184, 0.18)',
        'gradient' => 'linear-gradient(90deg, #cdb4ff 0%, #a9cfff 18%, #b6ead7 36%, #d5ffbd 54%, #fff9ae 72%, #ffd6bd 86%, #f0b4d8 100%)',
        'softGradient' => 'linear-gradient(to left, rgba(160, 120, 225, 0.38), rgba(110, 165, 220, 0.32), rgba(135, 205, 165, 0.28), rgba(225, 205, 105, 0.28), transparent)',
        'status' => 'Pending',
    ],
    [
        'name' => 'Webmaster Webring',
        'url' => 'https://webmasterwebring.netlify.app',
        'previous' => 'https://webmasterwebring.netlify.app?mau-previous',
        'next' => 'https://webmasterwebring.netlify.app?mau-next',
        'color' => '#89bbe3',
        'softColor' => 'rgba(137, 187, 227, 0.22)',
        'status' => 'Pending',
    ],
];
?>

<article class="space-y-8">
    <header class="mb-6">
        <h1 class="text-3xl tracking-tight sm:text-4xl">Webrings</h1>
    </header>

    <section class="space-y-4 border-taupe-900/20 dark:border-taupe-100/20">
        <div class="space-y-4">
            <?php foreach ($webrings as $index => $ring): ?>
                <?php
                $isGradient = isset($ring['gradient'], $ring['softGradient']);

                $ringStyles = [
                    '--ring-color: ' . $ring['color'],
                    '--ring-color-soft: ' . $ring['softColor'],
                ];

                $delay = min((int) $index, 11) * 40;

                if ($isGradient) {
                    $ringStyles[] = '--ring-gradient: ' . $ring['gradient'];
                    $ringStyles[] = '--ring-gradient-soft: ' . $ring['softGradient'];
                }

                if ($delay > 0) {
                    $ringStyles[] = 'animation-delay: ' . $delay . 'ms';
                }
                ?>

                <section
                    class="webring-card <?= $isGradient ? 'webring-card--gradient' : '' ?> group relative flex items-center overflow-hidden border p-3 pl-12 pr-12 transition-colors animate-fade-up motion-reduce:animate-none sm:pl-14 sm:pr-14"
                    style="<?= htmlspecialchars(implode('; ', $ringStyles), ENT_QUOTES, 'UTF-8') ?>;">
                    <span class="webring-gradient pointer-events-none absolute inset-0 z-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></span>

                    <nav aria-label="<?= htmlspecialchars($ring['name'], ENT_QUOTES, 'UTF-8') ?> navigation">
                        <a
                            href="<?= htmlspecialchars($ring['previous'], ENT_QUOTES, 'UTF-8') ?>"
                            class="webring-link webring-nav-link absolute left-2 top-1/2 z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center text-2xl leading-none no-underline transition-colors sm:left-3"
                            aria-label="Previous site">&#8249;</a>

                        <a
                            href="<?= htmlspecialchars($ring['next'], ENT_QUOTES, 'UTF-8') ?>"
                            class="webring-link webring-nav-link absolute right-2 top-1/2 z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center text-2xl leading-none no-underline transition-colors sm:right-3"
                            aria-label="Next site">&#8250;</a>
                    </nav>

                    <div class="relative z-10 flex min-w-0 items-center gap-2">
                        <h3 class="min-w-0 truncate text-base font-semibold leading-6">
                            <a
                                href="<?= htmlspecialchars($ring['url'], ENT_QUOTES, 'UTF-8') ?>"
                                class="webring-link block truncate underline-offset-4 hover:underline"
                                target="_blank"
                                rel="noopener noreferrer">
                                <?= htmlspecialchars($ring['name'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </h3>
                        <?php if (isset($ring['status'])): ?>
                            <span class="shrink-0 text-sm italic text-taupe-900/50 dark:text-taupe-100/50">
                                (<?= htmlspecialchars($ring['status'], ENT_QUOTES, 'UTF-8') ?>)
                            </span>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    </section>
</article>