<?php
/**
 * Шапка сайта: <head>, главное меню, служебные сообщения.
 * Ожидает в области видимости переменную $pageTitle (необязательно).
 */
$pageTitle = $pageTitle ?? '';
$user = current_user();
$nav = [
    'index.php'      => 'Главная',
    'recipes.php'    => 'Рецепты',
    'categories.php' => 'Категории',
    'cuisines.php'   => 'Кухни мира',
    'news.php'       => 'Новости',
    'api.php'        => 'API',
    'about.php'      => 'О проекте',
    'contacts.php'   => 'Контакты',
];
$here = current_page();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ? $pageTitle . ' — ' . SITE_NAME : SITE_NAME . ' — ' . SITE_TAGLINE) ?></title>
    <meta name="description" content="<?= e(SITE_TAGLINE) ?>">
    <link rel="icon" href="<?= asset('img/logo.png') ?>" type="image/png">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="<?= url('index.php') ?>">
            <img src="<?= asset('img/logo.png') ?>" alt="" width="44" height="44">
            <span class="logo-text">
                <strong><?= e(SITE_NAME) ?></strong>
                <small>рецепты со всего мира</small>
            </span>
        </a>

        <button class="nav-toggle" type="button" aria-label="Меню" aria-expanded="false" data-nav-toggle>
            <span></span><span></span><span></span>
        </button>

        <nav class="main-nav" data-nav>
            <?php foreach ($nav as $file => $label): ?>
                <a href="<?= url($file) ?>" class="<?= $here === $file ? 'active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="header-actions">
            <?php if ($user): ?>
                <a class="btn btn-ghost btn-sm" href="<?= url('cabinet.php') ?>">
                    <?= e(mb_substr($user['name'], 0, 18)) ?>
                </a>
                <?php if (is_admin()): ?>
                    <a class="btn btn-line btn-sm" href="<?= url('admin/index.php') ?>">Админка</a>
                <?php endif; ?>
                <a class="btn btn-sm" href="<?= url('logout.php') ?>">Выйти</a>
            <?php else: ?>
                <a class="btn btn-ghost btn-sm" href="<?= url('login.php') ?>">Войти</a>
                <a class="btn btn-sm" href="<?= url('register.php') ?>">Регистрация</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php $flashes = take_flash(); ?>
<?php if ($flashes): ?>
    <div class="container flash-wrap">
        <?php foreach ($flashes as $f): ?>
            <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['text']) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<main class="site-main">
