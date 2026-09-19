<?php
$title = 'About - Notes by mau';
$metaTitle = 'About';
$metaDescription = 'A little about me.';
$markdown = @file_get_contents(__DIR__ . '/../content/about.md') ?: '';
?>

<article class="space-y-6">
    <header>
        <h1 class="text-3xl tracking-tight sm:text-4xl">About</h1>
    </header>

    <div class="space-y-4 text-taupe-900 dark:text-taupe-100">
        <?= render_basic_markdown($markdown) ?>
    </div>
</article>