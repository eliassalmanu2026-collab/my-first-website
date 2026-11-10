<?php
/**
 * Вход в аккаунт.
 */

require __DIR__ . '/inc/db.php';

if (is_logged_in()) {
    redirect('cabinet.php');
}

$errors = [];
$email = '';
$next = (string)($_GET['next'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $next = (string)($_POST['next'] ?? '');

    if (!csrf_ok()) {
        $errors[] = 'Сессия устарела — попробуйте снова.';
    }
    if (!valid_email($email)) {
        $errors[] = 'Введите корректный адрес электронной почты.';
    }
    if ($password === '') {
        $errors[] = 'Введите пароль.';
    }

    if (!$errors) {
        $user = db_one('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Неверная почта или пароль.';
        } elseif ($user['status'] !== 'active') {
            $errors[] = 'Аккаунт заблокирован. Обратитесь к администратору.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            flash('success', 'Вы вошли как ' . $user['name'] . '.');
            if ($next !== '' && str_starts_with($next, '/')) {
                header('Location: ' . $next);
                exit;
            }
            redirect($user['role_id'] == 1 ? 'admin/index.php' : 'cabinet.php');
        }
    }
}

$pageTitle = 'Вход';
require __DIR__ . '/inc/header.php';
?>

<div class="auth-wrap">
    <h1>Вход</h1>
    <p class="muted">Войдите, чтобы открыть личный кабинет и избранное.</p>

    <?php if ($errors): ?>
        <ul class="error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="<?= url('login.php') ?>" data-validate class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <div class="field">
            <label for="email">Электронная почта</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
        </div>
        <div class="field">
            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button class="btn btn-lg btn-block" type="submit">Войти</button>
    </form>

    <div class="form-hint mt-24" style="background:var(--sand);padding:12px 14px;border-radius:10px">
        <strong>Тестовые аккаунты</strong><br>
        администратор: admin@vkusno.ru / admin123<br>
        пользователь: anna@mail.ru / user123
    </div>

    <p class="muted mt-24" style="margin-bottom:0">
        Нет аккаунта? <a href="<?= url('register.php') ?>">Зарегистрироваться</a>
    </p>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
