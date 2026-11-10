<?php
/**
 * Список категорий с количеством рецептов и средним рейтингом.
 */

require __DIR__ . '/inc/db.php';

$categories = db_all('SELECT c.*, s.recipes_count, s.avg_rating
                      FROM categories c
                               JOIN v_category_stats s ON s.id = c.id
                      ORDER BY s.recipes_count DESC');

$pageTitle = 'Категории';
require __DIR__ . '/inc/header.php';
?>

<div class="breadcrumbs">
    <a href="<?= url('index.php') ?>">Главная</a><span>Категории</span>
</div>

<div class="section-head">
    <div>
        <h1>Категории блюд</h1>
        <p>Рецепты сгруппированы по основному продукту или типу блюда</p>
    </div>
</div>

<div class="grid grid-3">
    <?php foreach ($categories as $category): ?>
        <a class="cat-card" href="<?= url('category.php?slug=' . urlencode($category['slug'])) ?>">
            <?php if ($category['image']): ?>
                <img src="<?= e($category['image']) ?>" alt="" loading="lazy">
            <?php endif; ?>
            <div>
                <h3><?= e($category['title']) ?></h3>
                <p><?= e($category['description']) ?></p>
                <span class="cat-count">
                    <?= (int)$category['recipes_count'] ?>
                    <?= plural((int)$category['recipes_count'], 'рецепт', 'рецепта', 'рецептов') ?>
                    · ★ <?= number_format((float)$category['avg_rating'], 1, ',', ' ') ?>
                </span>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
