<?php
/**
 * Каталог рецептов: поиск, фильтры, сортировка, постраничный вывод.
 */

require __DIR__ . '/inc/db.php';

$q        = trim((string)($_GET['q'] ?? ''));
$cat      = trim((string)($_GET['cat'] ?? ''));
$cuisine  = trim((string)($_GET['cuisine'] ?? ''));
$diff     = trim((string)($_GET['diff'] ?? ''));
$sort     = (string)($_GET['sort'] ?? 'new');
$page     = max(1, (int)($_GET['page'] ?? 1));

$where  = ['r.is_published = 1'];
$params = [];

if ($q !== '') {
    $where[] = '(r.title LIKE ? OR r.description LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($cat !== '') {
    $where[] = 'c.slug = ?';
    $params[] = $cat;
}
if ($cuisine !== '') {
    $where[] = 'cu.slug = ?';
    $params[] = $cuisine;
}
if (in_array($diff, ['easy', 'medium', 'hard'], true)) {
    $where[] = 'r.difficulty = ?';
    $params[] = $diff;
}

$orderBy = match ($sort) {
    'popular' => '(SELECT COUNT(*) FROM favorites f WHERE f.recipe_id = r.id) DESC, r.title ASC',
    'fast'    => 'r.prep_minutes ASC',
    'title'   => 'r.title ASC',
    default   => 'r.created_at DESC, r.id DESC',
};

$whereSql = implode(' AND ', $where);

$total = (int)db_value("SELECT COUNT(*)
                        FROM recipes r
                                 LEFT JOIN categories c ON c.id = r.category_id
                                 LEFT JOIN cuisines cu ON cu.id = r.cuisine_id
                        WHERE $whereSql", $params);

$perPage  = PER_PAGE;
$pages    = max(1, (int)ceil($total / $perPage));
$page     = min($page, $pages);
$offset   = ($page - 1) * $perPage;

$cards = db_all("SELECT r.id, r.title, r.slug, r.image, r.description, r.prep_minutes, r.servings,
                        r.difficulty, c.title AS category_title, cu.title AS cuisine_title,
                        (SELECT COUNT(*) FROM favorites f WHERE f.recipe_id = r.id) AS favorites_count
                 FROM recipes r
                          LEFT JOIN categories c ON c.id = r.category_id
                          LEFT JOIN cuisines cu ON cu.id = r.cuisine_id
                 WHERE $whereSql
                 ORDER BY $orderBy
                 LIMIT $perPage OFFSET $offset", $params);

$allCategories = db_all('SELECT title, slug FROM categories ORDER BY title');
$allCuisines   = db_all('SELECT title, slug FROM cuisines ORDER BY title');

$pageTitle = 'Рецепты';

/** Сборка ссылки с сохранением фильтров. */
function catalog_url(array $override = []): string
{
    $base = [
        'q' => $_GET['q'] ?? '', 'cat' => $_GET['cat'] ?? '',
        'cuisine' => $_GET['cuisine'] ?? '', 'diff' => $_GET['diff'] ?? '',
        'sort' => $_GET['sort'] ?? '', 'page' => $_GET['page'] ?? '',
    ];
    $query = array_filter(array_merge($base, $override), fn($v) => $v !== '' && $v !== null);
    return url('recipes.php' . ($query ? '?' . http_build_query($query) : ''));
}

require __DIR__ . '/inc/header.php';
?>

<div class="breadcrumbs">
    <a href="<?= url('index.php') ?>">Главная</a><span>Рецепты</span>
</div>

<div class="section-head">
    <div>
        <h1>Каталог рецептов</h1>
        <p>Фильтруйте по категории, кухне и сложности — или ищите по названию</p>
    </div>
</div>

<form class="filters" method="get" action="<?= url('recipes.php') ?>">
    <div class="field">
        <label for="q">Поиск по названию</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="бефстроганов, карри…">
    </div>
    <div class="field">
        <label for="cat">Категория</label>
        <select id="cat" name="cat">
            <option value="">Все категории</option>
            <?php foreach ($allCategories as $c): ?>
                <option value="<?= e($c['slug']) ?>" <?= $cat === $c['slug'] ? 'selected' : '' ?>>
                    <?= e($c['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="cuisine">Кухня</label>
        <select id="cuisine" name="cuisine">
            <option value="">Все кухни</option>
            <?php foreach ($allCuisines as $cu): ?>
                <option value="<?= e($cu['slug']) ?>" <?= $cuisine === $cu['slug'] ? 'selected' : '' ?>>
                    <?= e($cu['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="diff">Сложность</label>
        <select id="diff" name="diff">
            <option value="">Любая</option>
            <option value="easy" <?= $diff === 'easy' ? 'selected' : '' ?>>Просто</option>
            <option value="medium" <?= $diff === 'medium' ? 'selected' : '' ?>>Средне</option>
            <option value="hard" <?= $diff === 'hard' ? 'selected' : '' ?>>Сложно</option>
        </select>
    </div>
    <div class="field">
        <label for="sort">Сортировка</label>
        <select id="sort" name="sort">
            <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Сначала новые</option>
            <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Популярные</option>
            <option value="fast" <?= $sort === 'fast' ? 'selected' : '' ?>>Быстрые сначала</option>
            <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>По алфавиту</option>
        </select>
    </div>
    <div class="field">
        <label>&nbsp;</label>
        <button class="btn" type="submit">Применить</button>
    </div>
</form>

<div class="result-line">
    <span>
        Найдено <?= $total ?> <?= plural($total, 'рецепт', 'рецепта', 'рецептов') ?>
        <?php if ($q !== ''): ?> по запросу «<?= e($q) ?>»<?php endif; ?>
    </span>
    <?php if ($q !== '' || $cat !== '' || $cuisine !== '' || $diff !== ''): ?>
        <a href="<?= url('recipes.php') ?>">Сбросить фильтры</a>
    <?php endif; ?>
</div>

<?php if (!$cards): ?>
    <div class="empty-state">
        <p><strong>Ничего не найдено.</strong></p>
        <p>Попробуйте изменить запрос или сбросить фильтры.</p>
        <a class="btn btn-line" href="<?= url('recipes.php') ?>">Показать все рецепты</a>
    </div>
<?php else: ?>
    <div class="grid">
        <?php foreach ($cards as $card): ?>
            <?php require __DIR__ . '/inc/recipe-card.php'; ?>
        <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="Страницы каталога">
            <?php if ($page > 1): ?>
                <a href="<?= e(catalog_url(['page' => $page - 1])) ?>">← Назад</a>
            <?php else: ?>
                <span class="disabled">← Назад</span>
            <?php endif; ?>

            <?php for ($p = 1; $p <= $pages; $p++): ?>
                <?php if ($p === $page): ?>
                    <span class="current"><?= $p ?></span>
                <?php else: ?>
                    <a href="<?= e(catalog_url(['page' => $p])) ?>"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $pages): ?>
                <a href="<?= e(catalog_url(['page' => $page + 1])) ?>">Вперёд →</a>
            <?php else: ?>
                <span class="disabled">Вперёд →</span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/inc/footer.php'; ?>
