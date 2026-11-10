<?php
/**
 * Просмотр таблицы базы данных: список записей, удаление, переход к правке.
 */

require __DIR__ . '/../inc/db.php';
require_admin();

$tables = require __DIR__ . '/tables.php';
$t = (string)($_GET['t'] ?? 'recipes');

if (!isset($tables[$t])) {
    flash('error', 'Неизвестная таблица.');
    redirect('admin/index.php');
}
$meta = $tables[$t];

/* --- удаление ------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!csrf_ok()) {
        flash('error', 'Сессия устарела, повторите удаление.');
        redirect('admin/table.php?t=' . urlencode($t));
    }
    $id = (int)($_POST['id'] ?? 0);
    try {
        db_value("DELETE FROM `$t` WHERE id = ?", [$id]);
        flash('success', 'Запись удалена.');
    } catch (PDOException $e) {
        flash('error', 'Не удалось удалить запись: на неё ссылаются другие таблицы.');
    }
    redirect('admin/table.php?t=' . urlencode($t));
}

/* --- быстрая отметка «прочитано» ---------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_read' && $t === 'messages') {
    if (csrf_ok()) {
        db_value('UPDATE messages SET is_read = 1 - is_read WHERE id = ?', [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Статус сообщения изменён.');
    }
    redirect('admin/table.php?t=' . urlencode($t));
}

/* --- данные -------------------------------------------------------------- */
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$total = (int)db_value("SELECT COUNT(*) FROM `$t`");
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$order = $meta['order'] ?? 'id DESC';
$rows = db_all("SELECT * FROM `$t` ORDER BY $order LIMIT $perPage OFFSET $offset");

/* подписи внешних ключей */
$fkOptions = [];
foreach ($meta['fk'] ?? [] as $column => [$refTable, $refTitle]) {
    $fkOptions[$column] = [];
    foreach (db_all("SELECT id, `$refTitle` AS title FROM `$refTable` ORDER BY title LIMIT 500") as $option) {
        $fkOptions[$column][(int)$option['id']] = $option['title'];
    }
}

$pageTitle = 'Таблица: ' . $meta['label'];
require __DIR__ . '/../inc/header.php';
?>

<div class="breadcrumbs">
    <a href="<?= url('index.php') ?>">Главная</a>
    <span><a href="<?= url('admin/index.php') ?>">Админ-панель</a></span>
    <span><?= e($meta['label']) ?></span>
</div>

<nav class="tabs">
    <a href="<?= url('admin/index.php') ?>">Сводка</a>
    <a href="<?= url('admin/table.php?t=recipes') ?>" class="<?= $t === 'recipes' ? 'active' : '' ?>">Таблицы БД</a>
    <a href="<?= url('admin/views.php') ?>">Представления</a>
</nav>

<div class="section-head">
    <div>
        <h1><?= e($meta['label']) ?></h1>
        <p>
            Таблица <span class="code-inline"><?= e($t) ?></span> ·
            всего записей: <?= $total ?>
        </p>
    </div>
    <a class="btn" href="<?= url('admin/form.php?t=' . urlencode($t)) ?>">Добавить <?= e($meta['single']) ?></a>
</div>

<?php if (!$rows): ?>
    <div class="empty-state">
        <p>В таблице пока нет записей.</p>
        <a class="btn" href="<?= url('admin/form.php?t=' . urlencode($t)) ?>">Добавить первую</a>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>#</th>
                <?php foreach ($meta['list'] as $column): ?>
                    <th><?= e($column) ?></th>
                <?php endforeach; ?>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= (int)$row['id'] ?></td>
                    <?php foreach ($meta['list'] as $column): ?>
                        <?php
                        $value = $row[$column] ?? null;
                        if ($column === 'id') {
                            continue;
                        }
                        if (isset($fkOptions[$column])) {
                            $value = $fkOptions[$column][(int)$value] ?? ('#' . (int)$value);
                        } elseif ($column === 'password_hash') {
                            $value = '••••••';
                        } elseif (is_string($value) && mb_strlen($value) > 90) {
                            $value = mb_substr($value, 0, 90) . '…';
                        }
                        ?>
                        <td>
                            <?php if (in_array($column, ['is_published', 'is_read', 'is_approved'], true)): ?>
                                <span class="pill <?= $value ? 'pill-ok' : 'pill-warn' ?>">
                                    <?= $value ? 'да' : 'нет' ?>
                                </span>
                            <?php elseif ($column === 'status'): ?>
                                <span class="pill <?= $value === 'active' ? 'pill-ok' : 'pill-off' ?>"><?= e((string)$value) ?></span>
                            <?php else: ?>
                                <?= e((string)$value) ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                    <td>
                        <div class="row-actions">
                            <a class="btn btn-sm btn-line"
                               href="<?= url('admin/form.php?t=' . urlencode($t) . '&id=' . (int)$row['id']) ?>">Изменить</a>
                            <?php if ($t === 'messages'): ?>
                                <form method="post" action="<?= url('admin/table.php?t=' . urlencode($t)) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle_read">
                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                    <button class="btn btn-sm btn-line" type="submit">
                                        <?= $row['is_read'] ? 'Вернуть в новые' : 'Прочитано' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="<?= url('admin/table.php?t=' . urlencode($t)) ?>"
                                  data-confirm="Удалить запись #<?= (int)$row['id'] ?>? Действие необратимо.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                <button class="btn btn-sm btn-danger" type="submit">Удалить</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="pagination">
            <?php for ($p = 1; $p <= $pages; $p++): ?>
                <?php if ($p === $page): ?>
                    <span class="current"><?= $p ?></span>
                <?php else: ?>
                    <a href="<?= url('admin/table.php?t=' . urlencode($t) . '&page=' . $p) ?>"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/../inc/footer.php'; ?>
