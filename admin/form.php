<?php
/**
 * Добавление и редактирование записи любой таблицы.
 * Поля строятся автоматически по описанию из tables.php.
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

$id = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

$columns = [];
foreach (db_all("SHOW COLUMNS FROM `$t`") as $column) {
    if ($column['Field'] === 'id') {
        continue;
    }
    $columns[$column['Field']] = $column;
}

$record = [];
if ($isEdit) {
    $record = db_one("SELECT * FROM `$t` WHERE id = ? LIMIT 1", [$id]);
    if (!$record) {
        flash('error', 'Запись не найдена.');
        redirect('admin/table.php?t=' . urlencode($t));
    }
} else {
    foreach ($columns as $name => $column) {
        $record[$name] = $column['Default'];
    }
}

$fkOptions = [];
foreach ($meta['fk'] ?? [] as $column => [$refTable, $refTitle]) {
    $fkOptions[$column] = db_all("SELECT id, `$refTitle` AS title FROM `$refTable` ORDER BY title LIMIT 500");
}

$errors = [];

/* --- сохранение ---------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        $errors[] = 'Сессия устарела — сохраните ещё раз.';
    }

    $data = [];
    foreach ($columns as $name => $column) {
        $type = $meta['types'][$name] ?? 'text';

        if ($name === 'created_at') {
            continue;
        }
        if ($type === 'bool') {
            $data[$name] = isset($_POST[$name]) ? 1 : 0;
            continue;
        }

        $raw = trim((string)($_POST[$name] ?? ''));

        if ($type === 'password') {
            if ($raw === '') {
                if (!$isEdit) {
                    $errors[] = 'Пароль обязателен для нового пользователя.';
                }
                continue;
            }
            if (!valid_password($raw)) {
                $errors[] = 'Пароль должен быть не короче 5 символов и содержать буквы и цифры.';
            }
            $data[$name] = password_hash($raw, PASSWORD_DEFAULT);
            continue;
        }

        if ($type === 'slug' && $raw === '') {
            $raw = slugify((string)($_POST['title'] ?? ''));
        }

        $data[$name] = $raw === '' ? null : $raw;
    }

    foreach ($meta['required'] ?? [] as $required) {
        if ($required === 'password_hash' && $isEdit) {
            continue;
        }
        if (empty($data[$required])) {
            $errors[] = 'Поле «' . $required . '» обязательно для заполнения.';
        }
    }

    if (!$errors) {
        try {
            if ($isEdit) {
                $set = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($data)));
                $params = array_values($data);
                $params[] = $id;
                db_value("UPDATE `$t` SET $set WHERE id = ?", $params);
                flash('success', 'Запись обновлена.');
            } else {
                if (isset($columns['created_at'])) {
                    $data['created_at'] = date('Y-m-d H:i:s');
                }
                $cols = implode(', ', array_map(fn($c) => "`$c`", array_keys($data)));
                $ph = implode(', ', array_fill(0, count($data), '?'));
                db_value("INSERT INTO `$t` ($cols) VALUES ($ph)", array_values($data));
                flash('success', 'Запись добавлена.');
            }
            redirect('admin/table.php?t=' . urlencode($t));
        } catch (PDOException $e) {
            $errors[] = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }

    $record = array_merge($record, $data);
}

$pageTitle = ($isEdit ? 'Изменить' : 'Добавить') . ': ' . $meta['label'];
require __DIR__ . '/../inc/header.php';
?>

<div class="breadcrumbs">
    <a href="<?= url('index.php') ?>">Главная</a>
    <span><a href="<?= url('admin/index.php') ?>">Админ-панель</a></span>
    <span><a href="<?= url('admin/table.php?t=' . urlencode($t)) ?>"><?= e($meta['label']) ?></a></span>
    <span><?= $isEdit ? 'изменение' : 'новая запись' ?></span>
</div>

<div class="section-head">
    <div>
        <h1><?= $isEdit ? 'Изменить запись' : 'Новая запись' ?></h1>
        <p>Таблица <span class="code-inline"><?= e($t) ?></span><?= $isEdit ? ' · запись #' . $id : '' ?></p>
    </div>
    <a class="btn btn-line" href="<?= url('admin/table.php?t=' . urlencode($t)) ?>">← К списку</a>
</div>

<div class="panel" style="max-width:820px">
    <?php if ($errors): ?>
        <ul class="error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="<?= url('admin/form.php?t=' . urlencode($t) . ($isEdit ? '&id=' . $id : '')) ?>"
          class="form-grid">
        <?= csrf_field() ?>

        <?php foreach ($columns as $name => $column): ?>
            <?php
            if ($name === 'created_at') {
                continue;
            }
            $type = $meta['types'][$name] ?? 'text';
            $value = $record[$name] ?? '';
            $label = $name;
            ?>
            <div class="field">
                <label for="f_<?= e($name) ?>"><?= e($label) ?></label>

                <?php if (isset($fkOptions[$name])): ?>
                    <select id="f_<?= e($name) ?>" name="<?= e($name) ?>">
                        <option value="">— не выбрано —</option>
                        <?php foreach ($fkOptions[$name] as $option): ?>
                            <option value="<?= (int)$option['id'] ?>"
                                <?= (int)$value === (int)$option['id'] ? 'selected' : '' ?>>
                                <?= e($option['title']) ?> (#<?= (int)$option['id'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                <?php elseif ($type === 'textarea'): ?>
                    <textarea id="f_<?= e($name) ?>" name="<?= e($name) ?>" rows="6"><?= e((string)$value) ?></textarea>

                <?php elseif ($type === 'bool'): ?>
                    <label style="display:flex;gap:8px;align-items:center;font-weight:400">
                        <input type="checkbox" name="<?= e($name) ?>" value="1" style="width:auto"
                            <?= !empty($value) ? 'checked' : '' ?>>
                        <span>включено</span>
                    </label>

                <?php elseif (str_starts_with($type, 'enum:')): ?>
                    <?php $variants = explode(',', substr($type, 5)); ?>
                    <select id="f_<?= e($name) ?>" name="<?= e($name) ?>">
                        <?php foreach ($variants as $variant): ?>
                            <option value="<?= e($variant) ?>" <?= (string)$value === $variant ? 'selected' : '' ?>>
                                <?= e($variant) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                <?php elseif ($type === 'password'): ?>
                    <input type="password" id="f_<?= e($name) ?>" name="<?= e($name) ?>"
                           placeholder="<?= $isEdit ? 'оставьте пустым, чтобы не менять' : 'минимум 5 символов, буквы и цифры' ?>">
                    <span class="form-hint">Хранится в виде хеша, в списке не отображается</span>

                <?php elseif ($type === 'number'): ?>
                    <input type="number" id="f_<?= e($name) ?>" name="<?= e($name) ?>" value="<?= e((string)$value) ?>">

                <?php elseif ($type === 'datetime'): ?>
                    <input type="text" id="f_<?= e($name) ?>" name="<?= e($name) ?>"
                           value="<?= e((string)$value) ?>" placeholder="2026-11-10 14:00:00">

                <?php elseif ($type === 'email'): ?>
                    <input type="email" id="f_<?= e($name) ?>" name="<?= e($name) ?>" value="<?= e((string)$value) ?>">

                <?php else: ?>
                    <input type="text" id="f_<?= e($name) ?>" name="<?= e($name) ?>" value="<?= e((string)$value) ?>">
                <?php endif; ?>

                <?php if (!$column['Null'] && $column['Default'] === null && !in_array($name, $meta['required'] ?? [], true) && $type !== 'bool'): ?>
                    <span class="form-hint">обязательное поле в базе</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="row-actions">
            <button class="btn btn-lg" type="submit"><?= $isEdit ? 'Сохранить' : 'Добавить' ?></button>
            <a class="btn btn-lg btn-line" href="<?= url('admin/table.php?t=' . urlencode($t)) ?>">Отмена</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../inc/footer.php'; ?>
