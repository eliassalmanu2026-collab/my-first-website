<?php
/**
 * Рецепты одной категории.
 */

require __DIR__ . '/inc/db.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$category = db_one('SELECT * FROM categories WHERE slug = ? LIMIT 1', [$slug]);

if (!$category) {
    http_response_code(404);
    $pageTitle = 'Категория не найдена';
    require __DIR__ . '/inc/header.php';
    echo '<div class="empty-state" style="margin-top:40px"><h2>Категория не найдена</h2>'
        . '<a class="btn" href="' . url('categories.php') . '">Все категории</a></div>';
    require __DIR__ . '/inc/footer.php';
    exit;
}

$cards = db_all('SELECT r.id, r.title, r.slug, r.image, r.description, r.prep_minutes, r.servings,
                        r.difficulty, c.title AS category_title, cu.title AS cuisine_title,
                        (SELECT COUNT(*) FROM favorites f WHERE f.recipe_id = r.id) AS favorites_count
                 FROM recipes r
                          LEFT JOIN categories c ON c.id = r.category_id
                          LEFT JOIN cuisines cu ON cu.id = r.cuisine_id
                 WHERE r.is_published = 1 AND r.category_id = ?
                 ORDER BY r.created_at DESC', [$category['id']]);

$pageTitle = $category['title'];
require __DIR__ . '/inc/header.php';
?>

<div class="breadcrumbs">
    <a href="<?= url('index.php') ?>">Главная</a>
    <span><a href="<?= url('categories.php') ?>">Категории</a></span>
    <span><?= e($category['title']) ?></span>
</div>

<div class="section-head">
    <div>
        <h1><?= e($category['title']) ?></h1>
        <p><?= e($category['description']) ?></p>
    </div>
    <a class="section-link" href="<?= url('recipes.php?cat=' . urlencode($category['slug'])) ?>">С фильтрами →</a>
</div>

<?php if ($cards): ?>
    <div class="grid">
        <?php foreach ($cards as $card): ?>
            <?php require __DIR__ . '/inc/recipe-card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-state">
        <p>В этой категории пока нет рецептов.</p>
        <a class="btn btn-line" href="<?= url('recipes.php') ?>">Смотреть все рецепты</a>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/inc/footer.php'; ?>
