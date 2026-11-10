<?php
/**
 * Кухни мира: список кухонь и количество рецептов в каждой.
 */

require __DIR__ . '/inc/db.php';

$cuisines = db_all('SELECT cu.*,
                           COUNT(r.id) AS recipes_count,
                           COALESCE(ROUND(AVG((SELECT AVG(rv.rating) FROM reviews rv WHERE rv.recipe_id = r.id)), 1), 0) AS avg_rating
                    FROM cuisines cu
                             LEFT JOIN recipes r ON r.cuisine_id = cu.id AND r.is_published = 1
                    GROUP BY cu.id, cu.title, cu.slug, cu.api_name
                    HAVING recipes_count > 0
                    ORDER BY recipes_count DESC, cu.title');

$pageTitle = 'Кухни мира';
require __DIR__ . '/inc/header.php';
?>

<div class="breadcrumbs">
    <a href="<?= url('index.php') ?>">Главная</a><span>Кухни мира</span>
</div>

<div class="section-head">
    <div>
        <h1>Кухни мира</h1>
        <p>Рецепты из <?= count($cuisines) ?> кулинарных традиций</p>
    </div>
</div>

<div class="grid grid-3">
    <?php foreach ($cuisines as $cuisine): ?>
        <a class="card" href="<?= url('recipes.php?cuisine=' . urlencode($cuisine['slug'])) ?>">
            <div class="card-body">
                <h3><?= e($cuisine['title']) ?> кухня</h3>
                <p class="card-desc">
                    <?= (int)$cuisine['recipes_count'] ?>
                    <?= plural((int)$cuisine['recipes_count'], 'рецепт', 'рецепта', 'рецептов') ?>
                    в каталоге
                </p>
                <div class="card-meta">
                    <span class="stars">★ <?= number_format((float)$cuisine['avg_rating'], 1, ',', ' ') ?></span>
                    <span>Смотреть подборку →</span>
                </div>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
