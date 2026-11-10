<?php
/**
 * Полный текст новости.
 */

require __DIR__ . '/inc/db.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$item = db_one('SELECT * FROM news WHERE slug = ? LIMIT 1', [$slug]);

if (!$item) {
    http_response_code(404);
    $pageTitle = 'Новость не найдена';
    require __DIR__ . '/inc/header.php';
    echo '<div class="empty-state" style="margin-top:40px"><h2>Новость не найдена</h2>'
        . '<a class="btn" href="' . url('news.php') . '">Все новости</a></div>';
    require __DIR__ . '/inc/footer.php';
    exit;
}

$others = db_all('SELECT * FROM news WHERE id <> ? ORDER BY published_at DESC LIMIT 3', [$item['id']]);

$pageTitle = $item['title'];
require __DIR__ . '/inc/header.php';
?>

<div class="breadcrumbs">
    <a href="<?= url('index.php') ?>">Главная</a>
    <span><a href="<?= url('news.php') ?>">Новости</a></span>
    <span><?= e($item['title']) ?></span>
</div>

<article class="panel" style="max-width:820px;margin:0 auto">
    <span class="muted" style="font-size:.86rem"><?= e(format_date($item['published_at'], 'd.m.Y H:i')) ?></span>
    <h1 style="margin-top:8px"><?= e($item['title']) ?></h1>
    <?php if ($item['image']): ?>
        <img src="<?= e($item['image']) ?>" alt="<?= e($item['title']) ?>"
             style="border-radius:var(--radius);margin:10px 0 22px;width:100%;max-height:420px;object-fit:cover">
    <?php endif; ?>
    <p class="recipe-lead"><?= e($item['excerpt']) ?></p>
    <div style="white-space:pre-line"><?= e($item['body']) ?></div>
</article>

<?php if ($others): ?>
    <section class="section">
        <div class="section-head">
            <div><h2>Другие новости</h2></div>
            <a class="section-link" href="<?= url('news.php') ?>">Все →</a>
        </div>
        <div class="grid grid-3">
            <?php foreach ($others as $other): ?>
                <article class="card">
                    <div class="card-body">
                        <span class="muted" style="font-size:.82rem"><?= e(format_date($other['published_at'])) ?></span>
                        <h3><a href="<?= url('article.php?slug=' . urlencode($other['slug'])) ?>"><?= e($other['title']) ?></a></h3>
                        <p class="card-desc"><?= e($other['excerpt']) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/inc/footer.php'; ?>
