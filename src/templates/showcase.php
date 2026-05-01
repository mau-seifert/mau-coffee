<?php
$title = 'Showcase - Notes by mau';

$imageNames = get_showcase_image_filenames();

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 54;
$offset = ($page - 1) * $perPage;
$pagedImages = array_slice($imageNames, $offset, $perPage);
$count = count($imageNames);

$pagedItems = [];
foreach ($pagedImages as $idx => $name) {
    $pagedItems[] = [
        'index' => $offset + $idx,
        'encoded' => rawurlencode($name),
        'priority' => $idx < 3 ? 'high' : 'auto',
        'loading' => $idx < 3 ? 'eager' : 'lazy',
        'delay' => $idx < 12 ? $idx * 40 : 0,
    ];
}
?>

<section id="photos" class="relative">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="max-w-2xl">
            <h1 class="text-3xl tracking-tight sm:text-4xl">Photos</h1>
            <p class="mt-3 text-sm text-taupe-900/75">A selection of photos taken with a Canon AE-1 film camera.</p>
        </div>
    </div>
</section>

<?php if (!empty($imageNames)): ?>
    <section class="mt-8 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($pagedItems as $item): ?>
            <?php
            $i = $item['index'];
            $encoded = $item['encoded'];
            $priority = $item['priority'];
            $loading = $item['loading'];
            $delay = $item['delay'];
            $delayStyle = $delay > 0 ? "animation-delay:{$delay}ms;" : '';
            ?>
            <a href="#lb-<?= $i ?>" class="group block overflow-hidden border border-taupe-900/25 cursor-zoom-in animate-fade-up motion-reduce:animate-none" style="aspect-ratio:3/2;<?= $delayStyle ?>">
                <img src="/img/showcase/<?= $encoded ?>" alt="" width="900" height="600" loading="<?= $loading ?>" fetchpriority="<?= $priority ?>" decoding="async" class="showcase-img w-full h-full object-cover transition duration-300 group-hover:scale-105 group-hover:brightness-90">
            </a>
        <?php endforeach; ?>
    </section>

    <?php
    $totalPages = (int)ceil($count / $perPage);
    if ($totalPages > 1):
    ?>
        <nav class="mt-4 flex items-center gap-2 text-sm" aria-label="Pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>#photos" class="inline-flex items-center gap-2 px-3 py-1 rounded border border-taupe-900/20 hover:bg-taupe-100 no-underline">&larr; Prev</a>
            <?php else: ?>
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded text-taupe-900/40">&larr; Prev</span>
            <?php endif; ?>

            <?php
            $pagesToShow = [1, $totalPages];
            for ($p = max(2, $page - 1); $p <= min($totalPages - 1, $page + 1); $p++) {
                $pagesToShow[] = $p;
            }
            $pagesToShow = array_values(array_unique($pagesToShow));
            sort($pagesToShow);

            $lastRendered = 0;
            foreach ($pagesToShow as $p):
                if ($lastRendered !== 0 && $p > $lastRendered + 1):
            ?>
                    <span class="px-2 py-1 text-taupe-900/60" aria-hidden="true">...</span>
                <?php
                endif;

                if ($p === $page):
                ?>
                    <span class="px-3 py-1 rounded font-semibold bg-taupe-100"><?= $p ?></span>
                <?php else: ?>
                    <a href="?page=<?= $p ?>#photos" class="px-3 py-1 rounded border border-taupe-900/20 hover:bg-taupe-100 no-underline"><?= $p ?></a>
            <?php
                endif;
                $lastRendered = $p;
            endforeach;
            ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>#photos" class="inline-flex items-center gap-2 px-3 py-1 rounded border border-taupe-900/20 hover:bg-taupe-100 no-underline">Next &rarr;</a>
            <?php else: ?>
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded text-taupe-900/40">Next &rarr;</span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

    <a href="#photos" class="lightbox-bg fixed inset-0 z-49 bg-[rgba(18,13,8,0.88)] backdrop-blur-[6px] cursor-zoom-out no-underline" aria-label="Close lightbox"></a>

    <?php
    foreach ($pagedItems as $item):
        $i = $item['index'];
        $encoded = $item['encoded'];
        $prev = (($i - 1) + $count) % $count;
        $next = ($i + 1) % $count;
        $prevPage = intdiv($prev, $perPage) + 1;
        $nextPage = intdiv($next, $perPage) + 1;
        $prevHref = $prevPage === $page ? "#lb-{$prev}" : "?page={$prevPage}&lbDir=prev#lb-{$prev}";
        $nextHref = $nextPage === $page ? "#lb-{$next}" : "?page={$nextPage}&lbDir=next#lb-{$next}";
    ?>
        <div id="lb-<?= $i ?>" class="lightbox fixed inset-0 z-50 items-center justify-center pointer-events-none">
            <div class="lightbox-inner relative flex items-center justify-center pointer-events-auto max-w-[min(calc(100vw-7rem),1200px)] max-h-[90svh]">
                <a href="#photos" class="absolute -top-9 right-0 text-white/75 text-xl leading-none no-underline transition-colors duration-100 hover:text-white" aria-label="Close lightbox">✕</a>
                <img src="/img/showcase/<?= $encoded ?>" alt="" decoding="async" loading="lazy" class="showcase-img block max-w-full max-h-[90svh] object-contain">
            </div>
            <?php if ($count > 1): ?>
                <a href="<?= $prevHref ?>" class="lightbox-prev absolute top-1/2 left-3 -translate-y-1/2 z-2 pointer-events-auto text-white/60 text-[2.5rem] leading-none no-underline py-4 px-5 transition-colors duration-100 hover:text-white select-none cursor-pointer" aria-label="Previous photo">&#8249;</a>
                <a href="<?= $nextHref ?>" class="lightbox-next absolute top-1/2 right-3 -translate-y-1/2 z-2 pointer-events-auto text-white/60 text-[2.5rem] leading-none no-underline py-4 px-5 transition-colors duration-100 hover:text-white select-none cursor-pointer" aria-label="Next photo">&#8250;</a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <script>
        (function() {
            var count = <?= $count ?>;
            var page = <?= $page ?>;
            var perPage = <?= $perPage ?>;
            var html = document.documentElement;

            try {
                var params = new URLSearchParams(window.location.search);
                var initDir = params.get('lbDir');
                if (initDir === 'next' || initDir === 'prev') html.dataset.lbDir = initDir;
            } catch (e) {}

            function parseIdxFromHash(hash) {
                var m = String(hash).match(/^#?lb-(\d+)$/);
                return m ? parseInt(m[1], 10) : NaN;
            }

            function go(dir, hash) {
                html.dataset.lbDir = dir;
                var idx = parseIdxFromHash(hash);
                if (!Number.isFinite(idx)) {
                    location.hash = hash;
                    return;
                }
                var targetPage = Math.floor(idx / perPage) + 1;
                if (targetPage !== page) {
                    location.href = '?page=' + targetPage + '#' + hash;
                } else {
                    location.hash = hash;
                }
            }

            document.addEventListener('click', function(e) {
                if (e.target.closest('.lightbox-next')) html.dataset.lbDir = 'next';
                else if (e.target.closest('.lightbox-prev')) html.dataset.lbDir = 'prev';
                else delete html.dataset.lbDir;
            }, true);

            document.addEventListener('keydown', function(e) {
                var h = location.hash;
                if (!h.startsWith('#lb-')) return;
                var idx = parseInt(h.slice(4), 10);
                if (e.key === 'Escape') {
                    delete html.dataset.lbDir;
                    location.hash = 'photos';
                } else if (e.key === 'ArrowRight') {
                    go('next', 'lb-' + ((idx + 1) % count));
                } else if (e.key === 'ArrowLeft') {
                    go('prev', 'lb-' + ((idx - 1 + count) % count));
                }
            });
        }());
    </script>

<?php else: ?>
    <p class="mt-8 text-sm text-taupe-900/70">No images found.</p>
<?php endif; ?>