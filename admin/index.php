<?php
/**
 * Административная панель: сводка и список таблиц базы данных.
 */

require __DIR__ . '/../inc/db.php';
require_admin();

$tables = require __DIR__ . '/tables.php';

$counters = [
    'recipes'   => (int)db_value('SELECT COUNT(*) FROM recipes'),
    'users'     => (int)db_value('SELECT COUNT(*) FROM users'),
    'reviews'   => (int)db_value('SELECT COUNT(*) FROM reviews'),
    'favorites' => (int)db_value('SELECT COUNT(*) FROM favorites'),
    'messages'  => (int)db_value('SELECT COUNT(*) FROM messages'),
    'unread'    => (int)db_value('SELECT COUNT(*) FROM messages WHERE is_read = 0'),
];

$tableStats = [];
foreach ($tables as $name => $meta) {
    $tableStats[$name] = [
        'meta'  => $meta,
        'count' => (int)db_value("SELECT COUNT(*) FROM `$name`"),
    ];
}

$latestMessages = db_all('SELECT * FROM v_unread_messages LIMIT 5');
$topRecipes = db_all('SELECT * FROM v_top_recipes LIMIT 5');

$pageTitle = 'Админ-панель';
require __DIR__ . '/../inc/header.php';
?>

<div class="breadcrumbs">
    <a href="<?= url('index.php') ?>">Главная</a>
    <span><a href="<?= url('cabinet.php') ?>">Кабинет</a></span>
    <span>Админ-панель</span>
</div>

<nav class="tabs">
    <a href="<?= url('admin/index.php') ?>" class="active">Сводка</a>
    <a href="<?= url('admin/table.php?t=recipes') ?>">Таблицы БД</a>
    <a href="<?= url('admin/views.php') ?>">Представления</a>
    <a href="<?= url('index.php') ?>">На сайт →</a>
</nav>

<div class="section-head">
    <div>
        <h1>Административная панель</h1>
        <p>Управление содержимым базы данных «<?= e(DB_NAME) ?>»</p>
    </div>
</div>

<div class="grid grid-3" style="margin-bottom:26px">
    <div class="panel">
        <small class="muted">Рецепты</small>
        <h2 style="margin:2px 0 0"><?= $counters['recipes'] ?></h2>
        <p class="muted" style="margin:0;font-size:.86rem">в каталоге</p>
    </div>
    <div class="panel">
        <small class="muted">Пользователи</small>
        <h2 style="margin:2px 0 0"><?= $counters['users'] ?></h2>
        <p class="muted" style="margin:0;font-size:.86rem">зарегистрировано</p>
    </div>
    <div class="panel">
        <small class="muted">Отзывы</small>
        <h2 style="margin:2px 0 0"><?= $counters['reviews'] ?></h2>
        <p class="muted" style="margin:0;font-size:.86rem">опубликовано</p>
    </div>
    <div class="panel">
        <small class="muted">Избранное</small>
        <h2 style="margin:2px 0 0"><?= $counters['favorites'] ?></h2>
        <p class="muted" style="margin:0;font-size:.86rem">сохранений рецептов</p>
    </div>
    <div class="panel">
        <small class="muted">Сообщения</small>
        <h2 style="margin:2px 0 0"><?= $counters['messages'] ?></h2>
        <p class="muted" style="margin:0;font-size:.86rem">
            из них новых: <strong><?= $counters['unread'] ?></strong>
        </p>
    </div>
    <div class="panel">
        <small class="muted">Таблиц в базе</small>
        <h2 style="margin:2px 0 0"><?= count($tableStats) ?></h2>
        <p class="muted" style="margin:0;font-size:.86rem">+ 5 представлений</p>
    </div>
</div>

<div class="panel">
    <h2>Таблицы базы данных</h2>
    <p class="form-hint">Для каждой таблицы доступны просмотр, добавление, изменение и удаление записей.</p>
    <div class="table-wrap mt-24">
        <table>
            <thead>
            <tr>
                <th>Таблица</th>
                <th>Назначение</th>
                <th>Записей</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($tableStats as $name => $stat): ?>
                <tr>
                    <td><span class="code-inline"><?= e($name) ?></span></td>
                    <td><?= e($stat['meta']['label']) ?></td>
                    <td><?= $stat['count'] ?></td>
                    <td>
                        <div class="row-actions">
                            <a class="btn btn-sm btn-line" href="<?= url('admin/table.php?t=' . urlencode($name)) ?>">Открыть</a>
                            <a class="btn btn-sm" href="<?= url('admin/form.php?t=' . urlencode($name)) ?>">Добавить</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="recipe-columns" style="margin-top:22px">
    <div class="panel">
        <h2>Новые сообщения</h2>
        <?php if ($latestMessages): ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Кто</th><th>Тема</th><th>Ждёт</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($latestMessages as $message): ?>
                        <tr>
                            <td><?= e($message['name']) ?></td>
                            <td><?= e($message['subject']) ?></td>
                            <td><?= (int)$message['days_waiting'] ?> дн.</td>
                            <td><a class="btn btn-sm btn-line" href="<?= url('admin/table.php?t=messages') ?>">Открыть</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="muted">Непрочитанных сообщений нет.</p>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h2>Популярные рецепты</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Рецепт</th><th>В избранном</th><th>Рейтинг</th></tr></thead>
                <tbody>
                <?php foreach ($topRecipes as $top): ?>
                    <tr>
                        <td><?= e($top['title']) ?></td>
                        <td><?= (int)$top['favorites_count'] ?></td>
                        <td>★ <?= number_format((float)$top['avg_rating'], 1, ',', ' ') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../inc/footer.php'; ?>
