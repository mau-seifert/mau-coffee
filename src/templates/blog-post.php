<?php
$title = (string)($post['title'] ?? 'Post') . ' - Notes by mau';
$metaTitle = (string)($post['title'] ?? 'Post');
$metaDescription = (string)($post['summary'] ?? '');
$ts = strtotime((string)($post['created_at'] ?? ''));
$dateValue = $ts ? date('Y-m-d', $ts) : '';
$dateLabel = $ts ? date('F j, Y', $ts) : '';
?>

<article class="space-y-4">
    <header class="space-y-2">
        <h1 class="text-3xl tracking-tight sm:text-4xl"><?= htmlspecialchars($post['title'] ?? '', ENT_QUOTES) ?></h1>
        <div class="flex items-center gap-2 text-sm text-taupe-900/70 dark:text-taupe-100/70">
            <?php if ($dateLabel !== ''): ?>
                <time datetime="<?= htmlspecialchars($dateValue, ENT_QUOTES) ?>"><?= htmlspecialchars($dateLabel, ENT_QUOTES) ?></time>
            <?php endif; ?>
        </div>
    </header>

    <div class="space-y-3 text-taupe-900 dark:text-taupe-100">
        <?= render_basic_markdown((string)($post['body'] ?? '')) ?>
    </div>
</article>
