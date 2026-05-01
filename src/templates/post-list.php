<?php
/**
 * Shared post list partial.
 *
 * Expected variables:
 * - $posts: array of post rows
 * Optional variables:
 * - $listClass
 * - $itemCardClass
 * - $titleClass
 * - $titleTag (h2|h3)
 * - $dateClass
 * - $emptyMessage
 */

$listClass = $listClass ?? 'mt-6 space-y-3';
$itemCardClass = $itemCardClass ?? 'group relative flex flex-col gap-2 overflow-hidden border border-taupe-900/20 p-3 transition-colors sm:flex-row sm:items-center sm:justify-between';
$titleClass = $titleClass ?? 'relative z-10 min-w-0 truncate text-base font-semibold underline-offset-4 group-hover:underline';
$dateClass = $dateClass ?? 'relative z-10 text-sm text-taupe-900/70';
$emptyMessage = $emptyMessage ?? 'No posts yet. Coming soon.';
$titleTag = ($titleTag ?? 'h2') === 'h3' ? 'h3' : 'h2';
?>

<?php if (!empty($posts)): ?>
    <ul class="<?= htmlspecialchars((string)$listClass, ENT_QUOTES, 'UTF-8') ?>">
        <?php foreach ($posts as $post): ?>
            <?php
            $ts = strtotime((string)($post['created_at'] ?? ''));
            $dateValue = $ts ? date('Y-m-d', $ts) : '';
            $dateLabel = $ts ? date('F j, Y', $ts) : '';
            ?>
            <li>
                <a href="/blog/<?= htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8') ?>" class="<?= htmlspecialchars((string)$itemCardClass, ENT_QUOTES, 'UTF-8') ?>">
                    <span class="pointer-events-none absolute inset-0 z-0 bg-gradient-to-l from-taupe-200/50 via-taupe-100/30 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></span>
                    <<?= $titleTag ?> class="<?= htmlspecialchars((string)$titleClass, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string)($post['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </<?= $titleTag ?>>
                    <?php if ($dateLabel !== ''): ?>
                        <time datetime="<?= htmlspecialchars($dateValue, ENT_QUOTES, 'UTF-8') ?>" class="<?= htmlspecialchars((string)$dateClass, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8') ?>
                        </time>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php elseif ($emptyMessage !== ''): ?>
    <p class="mt-3 text-sm text-taupe-900/75"><?= htmlspecialchars((string)$emptyMessage, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
