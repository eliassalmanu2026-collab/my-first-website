<?php
/**
 * Представления (VIEW): просмотр результатов сохранённых запросов.
 */

require __DIR__ . '/../inc/db.php';
require_admin();

$views = [
    'v_recipe_catalog' => [
        'title' => 'Каталог рецептов',
        'sql'   => 'SELECT ... FROM recipes LEFT JOIN categories ... GROUP BY r.id',
        'desc'  => 'Карточка рецепта: категория, кухня, число ингредиентов, средний рейтинг и количество добавлений в избранное.',
    ],
    'v_top_recipes' => [
        'title' => 'Популярные рецепты',
        'sql'   => 'SELECT ... FROM recipes ORDER BY favorites_count DESC',
        'desc'  => 'Рецепты, отсортированные по количеству сохранений в избранное.',
    ],
    'v_category_stats' => [
        'title' => 'Статистика категорий',
        'sql'   => 'SELECT c.title, COUNT(r.id), AVG(rv.rating) ... GROUP BY c.id',
        'desc'  => 'Сколько рецептов в каждой категории и каков средний рейтинг блюд.',
    ],
    'v_user_activity' => [
        'title' => 'Активность пользователей',
        'sql'   => 'SELECT u.name, COUNT(f.id), COUNT(rv.id) ... FROM users u',
        'desc'  => 'Сколько рецептов сохранил и сколько отзывов оставил каждый пользователь.',
    ],
    'v_unread_messages' => [
        'title' => 'Непрочитанные сообщения',
        'sql'   => 'SELECT ... FROM messages WHERE is_read = 0',
        'desc'  => 'Обращения из формы обратной связи, ожидающие ответа, с числом дней ожидания.',
    ],
];

$pageTitle = 'Представления';
require __DIR__ . '/../inc/header.php';
?>

<div class="breadcrumbs">
    <a href="<?= url('index.php') ?>">Главная</a>
    <span><a href="<?= url('admin/index.php') ?>">Админ-панель</a></span>
    <span>Представления</span>
</div>

<nav class="tabs">
    <a href="<?= url('admin/index.php') ?>">Сводка</a>
    <a href="<?= url('admin/table.php?t=recipes') ?>">Таблицы БД</a>
    <a href="<?= url('admin/views.php') ?>" class="active">Представления</a>
</nav>

<div class="section-head">
    <div>
        <h1>Представления базы данных</h1>
        <p>Пять сохранённых запросов (VIEW) — только просмотр, без редактирования</p>
    </div>
</div>

<?php foreach ($views as $name => $info): ?>
    <?php
    try {
        $rows = db_all("SELECT * FROM `$name` LIMIT 20");
        $error = null;
    } catch (PDOException $e) {
        $rows = [];
        $error = $e->getMessage();
    }
    ?>
    <div class="panel" style="margin-bottom:22px">
        <h2><?= e($info['title']) ?></h2>
        <p class="muted" style="font-size:.92rem"><?= e($info['desc']) ?></p>
        <p class="form-hint">
            <span class="code-inline"><?= e($name) ?></span>
            <?= e($info['sql']) ?>
        </p>

        <?php if ($error): ?>
            <p class="flash flash-error">Ошибка представления: <?= e($error) ?></p>
        <?php elseif (!$rows): ?>
            <p class="muted">Представление вернуло 0 строк.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <?php foreach (array_keys($rows[0]) as $column): ?>
                            <th><?= e($column) ?></th>
                        <?php endforeach; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($row as $value): ?>
                                <td><?= e(is_null($value) ? '—' : (string)$value) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="form-hint" style="margin-top:10px">Показано строк: <?= count($rows) ?>, лимит выборки — 20.</p>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../inc/footer.php'; ?>
