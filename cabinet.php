<?php
/**
 * Личный кабинет: профиль, избранное, отзывы.
 */

require __DIR__ . '/inc/db.php';

require_login();
$user = current_user();

$tab = (string)($_GET['tab'] ?? 'profile');
if (!in_array($tab, ['profile', 'favorites', 'reviews'], true)) {
    $tab = 'profile';
}

$errors = [];

/* --- обновление профиля -------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'profile') {
    if (!csrf_ok()) {
        flash('error', 'Сессия устарела, повторите сохранение.');
        redirect('cabinet.php');
    }

    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $newPass = (string)($_POST['new_password'] ?? '');

    if (mb_strlen($name) < 2) {
        $errors[] = 'Имя должно содержать минимум 2 символа.';
    }
    if (!valid_email($email)) {
        $errors[] = 'Проверьте адрес электронной почты.';
    } elseif (db_value('SELECT id FROM users WHERE email = ? AND id <> ?', [$email, $user['id']])) {
        $errors[] = 'Эта почта уже занята другим аккаунтом.';
    }
    if (!valid_phone($phone)) {
        $errors[] = 'Телефон должен быть в формате +7 (999) 123-45-67.';
    }
    if ($newPass !== '' && !valid_password($newPass)) {
        $errors[] = 'Новый пароль: не короче 5 символов, буквы и цифры.';
    }

    if (!$errors) {
        if ($newPass !== '') {
            db_value('UPDATE users SET name = ?, email = ?, phone = ?, password_hash = ? WHERE id = ?',
                [$name, $email, $phone ?: null, password_hash($newPass, PASSWORD_DEFAULT), $user['id']]);
        } else {
            db_value('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?',
                [$name, $email, $phone ?: null, $user['id']]);
        }
        flash('success', 'Профиль обновлён.');
        redirect('cabinet.php');
    }
    $tab = 'profile';
}

/* --- удаление из избранного --------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'unfav') {
    if (csrf_ok()) {
        db_value('DELETE FROM favorites WHERE user_id = ? AND recipe_id = ?',
            [$user['id'], (int)($_POST['recipe_id'] ?? 0)]);
        flash('info', 'Рецепт убран из избранного.');
    }
    redirect('cabinet.php?tab=favorites');
}

/* --- данные вкладок ------------------------------------------------------ */
$favorites = db_all('SELECT r.id, r.title, r.slug, r.image, r.description, r.prep_minutes, r.servings,
                            r.difficulty, c.title AS category_title, cu.title AS cuisine_title,
                            f.created_at AS saved_at
                     FROM favorites f
                              JOIN recipes r ON r.id = f.recipe_id
                              LEFT JOIN categories c ON c.id = r.category_id
                              LEFT JOIN cuisines cu ON cu.id = r.cuisine_id
                     WHERE f.user_id = ?
                     ORDER BY f.created_at DESC', [$user['id']]);

$myReviews = db_all('SELECT rv.*, r.title, r.slug
                     FROM reviews rv JOIN recipes r ON r.id = rv.recipe_id
                     WHERE rv.user_id = ?
                     ORDER BY rv.created_at DESC', [$user['id']]);

$favCount = count($favorites);
$reviewCount = count($myReviews);
$initial = mb_strtoupper(mb_substr($user['name'], 0, 1));

$pageTitle = 'Личный кабинет';
require __DIR__ . '/inc/header.php';
?>

<div class="cabinet-head">
    <div class="avatar"><?= e($initial) ?></div>
    <div>
        <h1 style="margin-bottom:2px"><?= e($user['name']) ?></h1>
        <p class="muted" style="margin:0">
            <?= e($user['role_title']) ?> · на сайте с <?= e(format_date($user['created_at'])) ?>
        </p>
    </div>
    <?php if (is_admin()): ?>
        <a class="btn btn-line" style="margin-left:auto" href="<?= url('admin/index.php') ?>">Админ-панель</a>
    <?php endif; ?>
</div>

<nav class="tabs">
    <a href="<?= url('cabinet.php?tab=profile') ?>" class="<?= $tab === 'profile' ? 'active' : '' ?>">Профиль</a>
    <a href="<?= url('cabinet.php?tab=favorites') ?>" class="<?= $tab === 'favorites' ? 'active' : '' ?>">
        Избранное (<?= $favCount ?>)
    </a>
    <a href="<?= url('cabinet.php?tab=reviews') ?>" class="<?= $tab === 'reviews' ? 'active' : '' ?>">
        Мои отзывы (<?= $reviewCount ?>)
    </a>
</nav>

<?php if ($tab === 'profile'): ?>
    <div class="panel" style="max-width:620px">
        <h2>Данные профиля</h2>

        <?php if ($errors): ?>
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="post" action="<?= url('cabinet.php') ?>" data-validate class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="profile">
            <div class="field">
                <label for="name">Имя</label>
                <input type="text" id="name" name="name" value="<?= e($user['name']) ?>" required>
            </div>
            <div class="field">
                <label for="email">Электронная почта</label>
                <input type="email" id="email" name="email" value="<?= e($user['email']) ?>" required>
            </div>
            <div class="field">
                <label for="phone">Телефон</label>
                <input type="tel" id="phone" name="phone" value="<?= e($user['phone'] ?? '') ?>"
                       placeholder="+7 (999) 123-45-67" data-mask="phone">
            </div>
            <div class="field">
                <label for="new_password">Новый пароль</label>
                <input type="password" id="new_password" name="new_password" minlength="5"
                       placeholder="Оставьте пустым, чтобы не менять">
                <span class="form-hint">От 5 символов, буквы и цифры</span>
            </div>
            <button class="btn btn-lg" type="submit">Сохранить изменения</button>
        </form>
    </div>
<?php endif; ?>

<?php if ($tab === 'favorites'): ?>
    <?php if (!$favorites): ?>
        <div class="empty-state">
            <p><strong>В избранном пока пусто.</strong></p>
            <p>Откройте любой рецепт и нажмите «В избранное» — он появится здесь.</p>
            <a class="btn" href="<?= url('recipes.php') ?>">Перейти в каталог</a>
        </div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($favorites as $card): ?>
                <div>
                    <?php require __DIR__ . '/inc/recipe-card.php'; ?>
                    <form method="post" action="<?= url('cabinet.php') ?>" style="margin-top:8px"
                          data-confirm="Убрать рецепт из избранного?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="unfav">
                        <input type="hidden" name="recipe_id" value="<?= (int)$card['id'] ?>">
                        <button class="btn btn-sm btn-line" type="submit">Убрать из избранного</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($tab === 'reviews'): ?>
    <?php if (!$myReviews): ?>
        <div class="empty-state">
            <p><strong>Вы ещё не оставляли отзывов.</strong></p>
            <a class="btn" href="<?= url('recipes.php') ?>">Выбрать рецепт</a>
        </div>
    <?php else: ?>
        <div class="panel">
            <?php foreach ($myReviews as $review): ?>
                <div class="review">
                    <div class="review-head">
                        <span class="review-author">
                            <a href="<?= url('recipe.php?slug=' . urlencode($review['slug'])) ?>"><?= e($review['title']) ?></a>
                        </span>
                        <span class="stars"><?= str_repeat('★', (int)$review['rating']) ?><?= str_repeat('<span class="empty">★</span>', 5 - (int)$review['rating']) ?></span>
                        <span class="review-date"><?= e(format_date($review['created_at'])) ?></span>
                    </div>
                    <p><?= e($review['comment']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/inc/footer.php'; ?>
