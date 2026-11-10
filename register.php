<?php
/**
 * Регистрация нового пользователя.
 * Валидация: обязательные поля, корректный email, маска телефона,
 * пароль не короче 5 символов и обязательно содержит буквы и цифры.
 */

require __DIR__ . '/inc/db.php';

if (is_logged_in()) {
    redirect('cabinet.php');
}

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'name'  => trim((string)($_POST['name'] ?? '')),
        'email' => trim((string)($_POST['email'] ?? '')),
        'phone' => trim((string)($_POST['phone'] ?? '')),
    ];
    $password  = (string)($_POST['password'] ?? '');
    $password2 = (string)($_POST['password2'] ?? '');

    if (!csrf_ok()) {
        $errors[] = 'Сессия устарела — заполните форму ещё раз.';
    }
    if (mb_strlen($old['name']) < 2) {
        $errors[] = 'Имя должно содержать минимум 2 символа.';
    }
    if (!valid_email($old['email'])) {
        $errors[] = 'Введите корректный адрес электронной почты.';
    } elseif (db_value('SELECT id FROM users WHERE email = ?', [$old['email']])) {
        $errors[] = 'Пользователь с такой почтой уже зарегистрирован.';
    }
    if (!valid_phone($old['phone'])) {
        $errors[] = 'Телефон должен быть в формате +7 (999) 123-45-67.';
    }
    if (!valid_password($password)) {
        $errors[] = 'Пароль должен быть не короче 5 символов и состоять из букв и цифр.';
    }
    if ($password !== $password2) {
        $errors[] = 'Пароли не совпадают.';
    }

    if (!$errors) {
        db_value('INSERT INTO users (role_id, name, email, phone, password_hash, created_at)
                  VALUES ((SELECT id FROM roles WHERE code = "user"), ?, ?, ?, ?, ?)',
            [$old['name'], $old['email'], $old['phone'] ?: null,
             password_hash($password, PASSWORD_DEFAULT), date('Y-m-d H:i:s')]);

        $_SESSION['user_id'] = (int)$pdo->lastInsertId();
        flash('success', 'Добро пожаловать! Аккаунт создан, теперь можно сохранять рецепты.');
        redirect('cabinet.php');
    }
}

$pageTitle = 'Регистрация';
require __DIR__ . '/inc/header.php';
?>

<div class="auth-wrap">
    <h1>Регистрация</h1>
    <p class="muted">Аккаунт нужен, чтобы сохранять рецепты в избранное и оставлять отзывы.</p>

    <?php if ($errors): ?>
        <ul class="error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="<?= url('register.php') ?>" data-validate class="form-grid" novalidate>
        <?= csrf_field() ?>
        <div class="field">
            <label for="name">Имя *</label>
            <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" required minlength="2">
        </div>
        <div class="field">
            <label for="email">Электронная почта *</label>
            <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>
        </div>
        <div class="field">
            <label for="phone">Телефон</label>
            <input type="tel" id="phone" name="phone" value="<?= e($old['phone']) ?>"
                   placeholder="+7 (999) 123-45-67" data-mask="phone">
            <span class="form-hint">Маска подставляется автоматически, поле необязательное</span>
        </div>
        <div class="field">
            <label for="password">Пароль *</label>
            <input type="password" id="password" name="password" required minlength="5">
            <span class="form-hint">Не короче 5 символов, обязательно буквы и цифры</span>
        </div>
        <div class="field">
            <label for="password2">Повторите пароль *</label>
            <input type="password" id="password2" name="password2" required>
        </div>
        <button class="btn btn-lg btn-block" type="submit">Создать аккаунт</button>
    </form>

    <p class="muted mt-24" style="margin-bottom:0">
        Уже есть аккаунт? <a href="<?= url('login.php') ?>">Войти</a>
    </p>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
