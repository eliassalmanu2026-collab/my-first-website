<?php
/**
 * Новости проекта.
 */

require __DIR__ . '/inc/db.php';

$news = db_all('SELECT * FROM news ORDER BY published_at DESC');

$pageTitle = 'Новости';
require __DIR__ . '/inc/header.php';
?>

<div class="breadcrumbs">
    <a href="<?= url('index.php') ?>">Главная</a><span>Новости</span>
</div>

<div class="section-head">
    <div>
        <h1>Новости проекта</h1>
        <p>Обновления каталога и полезные материалы</p>
    </div>
</div>

<div class="grid grid-3">
    <?php foreach ($news as $item): ?>
        <article class="card">
            <div class="card-media">
                <a href="<?= url('article.php?slug=' . urlencode($item['slug'])) ?>">
                    <?php if ($item['image']): ?>
                        <img src="<?= e($item['image']) ?>" alt="<?= e($item['title']) ?>" loading="lazy">
                    <?php endif; ?>
                </a>
            </div>
            <div class="card-body">
                <span class="muted" style="font-size:.82rem"><?= e(format_date($item['published_at'], 'd.m.Y H:i')) ?></span>
                <h3><a href="<?= url('article.php?slug=' . urlencode($item['slug'])) ?>"><?= e($item['title']) ?></a></h3>
                <p class="card-desc"><?= e($item['excerpt']) ?></p>
                <div class="card-meta">
                    <a class="section-link" href="<?= url('article.php?slug=' . urlencode($item['slug'])) ?>">Читать →</a>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
