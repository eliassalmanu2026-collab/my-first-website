<?php
/**
 * Контакты и форма обратной связи.
 * Сообщения сохраняются в таблицу messages и видны в административной панели.
 */

require __DIR__ . '/inc/db.php';

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'body' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'name'    => trim((string)($_POST['name'] ?? '')),
        'email'   => trim((string)($_POST['email'] ?? '')),
        'phone'   => trim((string)($_POST['phone'] ?? '')),
        'subject' => trim((string)($_POST['subject'] ?? '')),
        'body'    => trim((string)($_POST['body'] ?? '')),
    ];

    if (!csrf_ok()) {
        $errors[] = 'Сессия устарела — отправьте форму ещё раз.';
    }
    if (mb_strlen($old['name']) < 2) {
        $errors[] = 'Укажите имя — не короче двух символов.';
    }
    if (!valid_email($old['email'])) {
        $errors[] = 'Проверьте адрес электронной почты.';
    }
    if (!valid_phone($old['phone'])) {
        $errors[] = 'Телефон должен быть в формате +7 (999) 123-45-67.';
    }
    if (mb_strlen($old['subject']) < 3) {
        $errors[] = 'Укажите тему обращения.';
    }
    if (mb_strlen($old['body']) < 10) {
        $errors[] = 'Сообщение слишком короткое — опишите вопрос подробнее.';
    }

    if (!$errors) {
        $currentUser = current_user();
        db_value('INSERT INTO messages (user_id, name, email, phone, subject, body, created_at)
                  VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$currentUser['id'] ?? null, $old['name'], $old['email'], $old['phone'] ?: null,
             $old['subject'], $old['body'], date('Y-m-d H:i:s')]);

        flash('success', 'Спасибо! Сообщение отправлено — администратор увидит его в панели.');
        redirect('contacts.php');
    }
}

$pageTitle = 'Контакты';
require __DIR__ . '/inc/header.php';
?>

<div class="breadcrumbs">
    <a href="<?= url('index.php') ?>">Главная</a><span>Контакты</span>
</div>

<div class="section-head">
    <div>
        <h1>Контакты</h1>
        <p>Напишите нам — предложения, ошибки в рецептах, сотрудничество</p>
    </div>
</div>

<div class="recipe-columns">
    <div class="panel">
        <?php if ($errors): ?>
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="post" action="<?= url('contacts.php') ?>" data-validate class="form-grid">
            <?= csrf_field() ?>
            <div class="form-row">
                <div class="field">
                    <label for="name">Имя *</label>
                    <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" required>
                </div>
                <div class="field">
                    <label for="email">Электронная почта *</label>
                    <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="field">
                    <label for="phone">Телефон</label>
                    <input type="tel" id="phone" name="phone" value="<?= e($old['phone']) ?>"
                           placeholder="+7 (999) 123-45-67" data-mask="phone">
                    <span class="form-hint">Необязательно, маска подставится сама</span>
                </div>
                <div class="field">
                    <label for="subject">Тема *</label>
                    <input type="text" id="subject" name="subject" value="<?= e($old['subject']) ?>" required>
                </div>
            </div>
            <div class="field">
                <label for="body">Сообщение *</label>
                <textarea id="body" name="body" required
                          placeholder="Опишите вопрос — минимум 10 символов"><?= e($old['body']) ?></textarea>
            </div>
            <button class="btn btn-lg" type="submit">Отправить сообщение</button>
        </form>
    </div>

    <div>
        <div class="panel">
            <h2>Как с нами связаться</h2>
            <ul class="ingredients">
                <li><span>Электронная почта</span><b><a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a></b></li>
                <li><span>Телефон</span><b><?= e(SITE_PHONE) ?></b></li>
                <li><span>Время ответа</span><b>до 2 рабочих дней</b></li>
            </ul>
        </div>
        <div class="panel">
            <h2>Частые вопросы</h2>
            <p><strong>Как сохранить рецепт?</strong><br>Зарегистрируйтесь, войдите и нажмите «В избранное» на странице рецепта.</p>
            <p><strong>Откуда берутся рецепты?</strong><br>Каталог собран на основе открытой базы TheMealDB, тексты адаптированы.</p>
            <p style="margin-bottom:0"><strong>Нашли ошибку?</strong><br>Напишите через форму — укажите название рецепта и что не сходится.</p>
        </div>
    </div>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
