<?php
/**
 * Главная страница: витрина проекта.
 */

require __DIR__ . '/inc/db.php';

$totalRecipes = (int)db_value('SELECT COUNT(*) FROM recipes WHERE is_published = 1');
$totalCategories = (int)db_value('SELECT COUNT(*) FROM categories');
$totalCuisines = (int)db_value('SELECT COUNT(*) FROM cuisines');

$categories = db_all('SELECT * FROM v_category_stats ORDER BY recipes_count DESC');
$popular = db_all('SELECT r.id, r.title, r.slug, r.image, r.description, r.prep_minutes,
                          r.servings, r.difficulty, c.title AS category_title, cu.title AS cuisine_title,
                          (SELECT COUNT(*) FROM favorites f WHERE f.recipe_id = r.id) AS favorites_count
                   FROM recipes r
                            LEFT JOIN categories c ON c.id = r.category_id
                            LEFT JOIN cuisines cu ON cu.id = r.cuisine_id
                   WHERE r.is_published = 1
                   ORDER BY favorites_count DESC, r.created_at DESC
                   LIMIT 6');
$latest = db_all('SELECT r.id, r.title, r.slug, r.image, r.description, r.prep_minutes,
                         r.servings, r.difficulty, c.title AS category_title, cu.title AS cuisine_title
                  FROM recipes r
                           LEFT JOIN categories c ON c.id = r.category_id
                           LEFT JOIN cuisines cu ON cu.id = r.cuisine_id
                  WHERE r.is_published = 1
                  ORDER BY r.created_at DESC, r.id DESC
                  LIMIT 6');
$news = db_all('SELECT * FROM news ORDER BY published_at DESC LIMIT 3');

$pageTitle = '';
require __DIR__ . '/inc/header.php';
?>

<section class="hero">
    <div class="hero-inner">
        <h1>Готовьте с удовольствием: <?= $totalRecipes ?> проверенных рецептов</h1>
        <p>
            Агрегатор собирает домашние блюда со всего мира: пошаговые инструкции,
            полный состав ингредиентов и подборки по кухням. Сохраняйте любимое в личный кабинет.
        </p>
        <form class="hero-search" action="<?= url('recipes.php') ?>" method="get" role="search">
            <input type="search" name="q" placeholder="Например: бефстроганов, карри, чизкейк…"
                   aria-label="Поиск рецепта">
            <button class="btn btn-lg" type="submit">Найти рецепт</button>
        </form>
    </div>
    <div class="hero-stats">
        <div><b><?= $totalRecipes ?></b><span>рецептов в каталоге</span></div>
        <div><b><?= $totalCategories ?></b><span>категории блюд</span></div>
        <div><b><?= $totalCuisines ?></b><span>кухонь мира</span></div>
    </div>
</section>

<section class="section">
    <div class="section-head">
        <div>
            <h2>Категории</h2>
            <p>Начните с того, из чего будет блюдо</p>
        </div>
        <a class="section-link" href="<?= url('categories.php') ?>">Все категории →</a>
    </div>
    <div class="grid grid-3">
        <?php foreach ($categories as $category): ?>
            <a class="cat-card" href="<?= url('category.php?slug=' . urlencode(slugify($category['title']))) ?>">
                <?php if (!empty($category['image'])): ?>
                    <img src="<?= e($category['image']) ?>" alt="" loading="lazy">
                <?php endif; ?>
                <div>
                    <h3><?= e($category['title']) ?></h3>
                    <p><?= (int)$category['recipes_count'] ?> <?= plural((int)$category['recipes_count'], 'рецепт', 'рецепта', 'рецептов') ?>
                        · средний рейтинг <?= number_format((float)$category['avg_rating'], 1, ',', ' ') ?></p>
                    <span class="cat-count">Смотреть подборку</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="section">
    <div class="section-head">
        <div>
            <h2>Популярные рецепты</h2>
            <p>Чаще всего добавляют в избранное</p>
        </div>
        <a class="section-link" href="<?= url('recipes.php?sort=popular') ?>">Весь каталог →</a>
    </div>
    <div class="grid">
        <?php foreach ($popular as $card): ?>
            <?php require __DIR__ . '/inc/recipe-card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>

<section class="section">
    <div class="section-head">
        <div>
            <h2>Новое в каталоге</h2>
            <p>Свежие поступления</p>
        </div>
        <a class="section-link" href="<?= url('recipes.php') ?>">Смотреть все →</a>
    </div>
    <div class="grid">
        <?php foreach ($latest as $card): ?>
            <?php require __DIR__ . '/inc/recipe-card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>

<section class="section">
    <div class="section-head">
        <div>
            <h2>Случайный рецепт из TheMealDB</h2>
            <p>Живой запрос к внешнему API — каждое нажатие даёт новый рецепт</p>
        </div>
        <a class="section-link" href="<?= url('api.php') ?>">Как работает API →</a>
    </div>
    <div data-api-demo data-api-autorun="random">
        <div class="api-status" data-api-status>Готовим запрос к внешнему API…</div>
        <div class="grid" data-api-result></div>
        <div class="mt-24">
            <button class="btn btn-line" type="button" data-api-random>Ещё случайный рецепт</button>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-head">
        <div>
            <h2>Новости проекта</h2>
            <p>Что изменилось на сайте</p>
        </div>
        <a class="section-link" href="<?= url('news.php') ?>">Все новости →</a>
    </div>
    <div class="grid grid-3">
        <?php foreach ($news as $item): ?>
            <article class="card">
                <div class="card-media">
                    <a href="<?= url('article.php?slug=' . urlencode($item['slug'])) ?>">
                        <?php if ($item['image']): ?>
                            <img src="<?= e($item['image']) ?>" alt="<?= e($item['title']) ?>" loading="lazy">
                        <?php endif; ?>
                    </a>
                </div>
                <div class="card-body">
                    <span class="muted" style="font-size:.82rem"><?= e(format_date($item['published_at'])) ?></span>
                    <h3><a href="<?= url('article.php?slug=' . urlencode($item['slug'])) ?>"><?= e($item['title']) ?></a></h3>
                    <p class="card-desc"><?= e($item['excerpt']) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="cta">
    <div>
        <h2>Сохраняйте рецепты в избранное</h2>
        <p>Зарегистрируйтесь, чтобы собирать собственные подборки и оставлять отзывы о блюдах.</p>
    </div>
    <?php if (is_logged_in()): ?>
        <a class="btn btn-lg btn-outline" href="<?= url('cabinet.php') ?>">Открыть кабинет</a>
    <?php else: ?>
        <a class="btn btn-lg btn-outline" href="<?= url('register.php') ?>">Создать аккаунт</a>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/inc/footer.php'; ?>
