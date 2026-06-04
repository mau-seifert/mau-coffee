<?php
$title = 'Notes by mau';
$metaTitle = 'Notes by mau';
$homeIntro = 'Make yourself at home, pour a cup, and linger for a moment. You’ll find little notes, snapshots, and half-brewed ideas tucked onto the shelves.';
$metaDescription = $homeIntro;

$images = get_showcase_image_filenames();
$previewImages = [];
$totalImages = count($images);
$previewCount = min(5, $totalImages);
if ($previewCount > 0) {
    $timeBucket = (int) floor(time() / 21600);
    $start = $timeBucket % $totalImages;
    $step = max(1, intdiv($totalImages, $previewCount) + 1);

    for ($i = 0; $i < $previewCount; $i++) {
        $idx = ($start + ($i * $step)) % $totalImages;
        $orig = $images[$idx];
        $thumb = showcase_home_thumbnail_or_original($orig);
        if ($thumb === $orig) {
            continue;
        }
        $previewImages[] = $thumb;
    }
}
?>

<section class="space-y-6">
    <div>
        <h1 class="text-3xl tracking-tight sm:text-4xl">Notes by mau</h1>
        <p class="mt-3 text-sm text-taupe-900/75"><?= htmlspecialchars($homeIntro, ENT_QUOTES, 'UTF-8') ?></p>
    </div>

    <div>
        <h2 class="text-2xl font-semibold tracking-tight">Blog</h2>
        <?php require __DIR__ . '/post-list.php'; ?>
        <a href="/blog" class="mt-3 inline-block text-sm underline underline-offset-4">All posts</a>
    </div>
    <div>
        <h2 class="text-2xl font-semibold tracking-tight">Photos</h2>
        <?php if (!empty($previewImages)): ?>
            <div class="mt-3 overflow-hidden py-3">
                <div class="flex items-center pb-1">
                    <?php foreach ($previewImages as $index => $imagePath): ?>
                        <?php
                        $name = $imagePath;

                        $visibilityClass = '';
                        if ($index === 3) {
                            $visibilityClass = 'hidden sm:block';
                        } elseif ($index === 4) {
                            $visibilityClass = 'hidden lg:block';
                        }

                        $zClass = match ($index) {
                            0 => 'z-40',
                            1 => 'z-30',
                            2 => 'z-20',
                            3 => 'z-10',
                            4 => 'z-[9]',
                            default => 'z-[8]',
                        };

                        $offsetClass = $index === 0 ? 'ml-1' : '-ml-6 sm:-ml-8';
                        $homeImageName = showcase_home_thumbnail_or_original($name);
                        ?>
                        <a
                            href="/showcase"
                            class="<?= trim('relative block w-32 sm:w-36 md:w-40 lg:w-44 aspect-[3/2] overflow-hidden border border-taupe-900/25 bg-taupe-100 flex-none shadow-sm rotate-2 -translate-y-0.5 ' . $offsetClass . ' ' . $zClass . ' ' . $visibilityClass) ?>">
                            <img src="/img/showcase/<?= rawurlencode($homeImageName) ?>" alt="" width="900" height="600" loading="lazy" decoding="async" class="showcase-img w-full h-full object-cover">
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <p class="mt-3 text-sm text-taupe-900/70">No images found.</p>
        <?php endif; ?>
        <a href="/showcase" class="inline-block text-sm underline underline-offset-4">View all photos</a>
    </div>
</section>
