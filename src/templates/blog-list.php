<?php
$title = 'Blog - Notes by mau';
$metaTitle = 'Posts';
$metaDescription = 'Read the latest posts and ideas.';
?>

<section>
	<header class="flex items-center justify-between gap-4">
		<h1 class="text-3xl tracking-tight sm:text-4xl">Posts</h1>
		<a href="/feed.xml" rel="alternate" type="application/rss+xml" class="group relative inline-flex items-center gap-2 overflow-hidden border border-taupe-900/20 px-3 py-2 text-sm transition-colors dark:border-taupe-100/20" aria-label="Subscribe to the Notes by mau RSS feed">
			<span class="pointer-events-none absolute inset-0 z-0 bg-gradient-to-l from-taupe-200/50 via-taupe-100/30 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100 dark:from-taupe-100/10 dark:via-taupe-100/5"></span>
			<svg class="relative z-10" aria-hidden="true" viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M5 3a2 2 0 1 0 0 4c6.6 0 12 5.4 12 12a2 2 0 1 0 4 0C21 10.2 13.8 3 5 3Zm0 7a2 2 0 1 0 0 4c2.8 0 5 2.2 5 5a2 2 0 1 0 4 0c0-5-4-9-9-9Zm0 7a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/></svg>
			<span class="relative z-10 font-semibold underline-offset-4 group-hover:underline">RSS feed</span>
		</a>
	</header>

	<?php require __DIR__ . '/post-list.php'; ?>
</section>
