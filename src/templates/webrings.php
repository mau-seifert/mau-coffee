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
];
?>

<article class="space-y-8">
    <header class="mb-6">
        <h1 class="text-3xl tracking-tight sm:text-4xl">Webrings</h1>
    </header>

    <section class="space-y-4 border-taupe-900/20">
        <div class="space-y-4">
            <?php foreach ($webrings as $ring): ?>
                <section
                    class="group relative flex items-center overflow-hidden border p-3 pl-12 pr-12 transition-colors sm:pl-14 sm:pr-14 border-(--ring-color)"
                    style="--ring-color: <?= htmlspecialchars($ring['color'], ENT_QUOTES, 'UTF-8') ?>; --ring-color-soft: <?= htmlspecialchars($ring['softColor'], ENT_QUOTES, 'UTF-8') ?>;">
                    <span class="pointer-events-none absolute inset-0 z-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100" style="background: linear-gradient(to left, var(--ring-color-soft), transparent);"></span>

                    <nav aria-label="<?= htmlspecialchars($ring['name'], ENT_QUOTES, 'UTF-8') ?> navigation">
                        <a href="<?= htmlspecialchars($ring['previous'], ENT_QUOTES, 'UTF-8') ?>" class="absolute left-2 top-1/2 z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center text-2xl leading-none text-(--ring-color) no-underline transition-colors hover:bg-(--ring-color) hover:text-taupe-50 sm:left-3" aria-label="Previous site">&#8249;</a>
                        <a href="<?= htmlspecialchars($ring['next'], ENT_QUOTES, 'UTF-8') ?>" class="absolute right-2 top-1/2 z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center text-2xl leading-none text-(--ring-color) no-underline transition-colors hover:bg-(--ring-color) hover:text-taupe-50 sm:right-3" aria-label="Next site">&#8250;</a>
                    </nav>

                    <div class="relative z-10 min-w-0">
                        <h3 class="truncate text-base font-semibold leading-6">
                            <a href="<?= htmlspecialchars($ring['url'], ENT_QUOTES, 'UTF-8') ?>" class="underline-offset-4 hover:underline hover:text-(--ring-color)" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($ring['name'], ENT_QUOTES, 'UTF-8') ?></a>
                        </h3>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    </section>
</article>
