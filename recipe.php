<?php
/**
 * Страница рецепта: состав, шаги, отзывы, избранное.
 */

require __DIR__ . '/inc/db.php';

$slug = trim((string)($_GET['slug'] ?? ''));
if ($slug === '') {
    redirect('recipes.php');
}

$recipe = db_one('SELECT r.*, c.title AS category_title, c.slug AS category_slug,
                         cu.title AS cuisine_title, cu.slug AS cuisine_slug
                  FROM recipes r
                           LEFT JOIN categories c ON c.id = r.category_id
                           LEFT JOIN cuisines cu ON cu.id = r.cuisine_id
                  WHERE r.slug = ? AND r.is_published = 1 LIMIT 1', [$slug]);

if (!$recipe) {
    http_response_code(404);
    $pageTitle = 'Рецепт не найден';
    require __DIR__ . '/inc/header.php';
    echo '<div class="empty-state" style="margin-top:40px">'
        . '<h2>Рецепт не найден</h2>'
        . '<p>Возможно, он был удалён или ссылка устарела.</p>'
        . '<a class="btn" href="' . url('recipes.php') . '">Вернуться в каталог</a></div>';
    require __DIR__ . '/inc/footer.php';
    exit;
}

$user = current_user();

/* --- обработка форм ------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        flash('error', 'Сессия устарела, повторите действие.');
        redirect('recipe.php?slug=' . urlencode($slug));
    }

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'favorite') {
        require_login();
        $exists = db_value('SELECT id FROM favorites WHERE user_id = ? AND recipe_id = ?',
            [$user['id'], $recipe['id']]);
        if ($exists) {
            db_value('DELETE FROM favorites WHERE id = ?', [$exists]);
            flash('info', 'Рецепт убран из избранного.');
        } else {
            db_value('INSERT INTO favorites (user_id, recipe_id) VALUES (?, ?)',
                [$user['id'], $recipe['id']]);
            flash('success', 'Рецепт добавлен в избранное.');
        }
        redirect('recipe.php?slug=' . urlencode($slug));
    }

    if ($action === 'review') {
        require_login();
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim((string)($_POST['comment'] ?? ''));

        if ($rating < 1 || $rating > 5) {
            flash('error', 'Поставьте оценку от 1 до 5.');
        } elseif (mb_strlen($comment) < 5) {
            flash('error', 'Комментарий слишком короткий — напишите хотя бы несколько слов.');
        } else {
            $now = date('Y-m-d H:i:s');
            db_value('INSERT INTO reviews (recipe_id, user_id, rating, comment, created_at)
                      VALUES (?, ?, ?, ?, ?)',
                [$recipe['id'], $user['id'], $rating, $comment, $now]);
            flash('success', 'Спасибо! Отзыв опубликован.');
        }
        redirect('recipe.php?slug=' . urlencode($slug) . '#reviews');
    }
}

/* --- данные страницы ----------------------------------------------------- */
$ingredients = db_all('SELECT i.title, ri.amount
                       FROM recipe_ingredients ri
                                JOIN ingredients i ON i.id = ri.ingredient_id
                       WHERE ri.recipe_id = ?
                       ORDER BY ri.id', [$recipe['id']]);

$stats = db_one('SELECT COALESCE(ROUND(AVG(rating), 1), 0) AS avg_rating, COUNT(*) AS cnt
                 FROM reviews WHERE recipe_id = ?', [$recipe['id']]);

$reviews = db_all('SELECT rv.*, u.name
                   FROM reviews rv JOIN users u ON u.id = rv.user_id
                   WHERE rv.recipe_id = ? ORDER BY rv.created_at DESC', [$recipe['id']]);

$isFavorite = $user
    ? (bool)db_value('SELECT id FROM favorites WHERE user_id = ? AND recipe_id = ?',
        [$user['id'], $recipe['id']])
    : false;

$similar = db_all('SELECT r.id, r.title, r.slug, r.image, r.description, r.prep_minutes,
                          r.servings, r.difficulty, c.title AS category_title, cu.title AS cuisine_title
                   FROM recipes r
                            LEFT JOIN categories c ON c.id = r.category_id
                            LEFT JOIN cuisines cu ON cu.id = r.cuisine_id
                   WHERE r.is_published = 1 AND r.id <> ? AND r.category_id = ?
                   ORDER BY RAND() LIMIT 3', [$recipe['id'], $recipe['category_id']]);

$steps = array_values(array_filter(array_map('trim', explode("\n", (string)$recipe['instructions']))));

$pageTitle = $recipe['title'];
require __DIR__ . '/inc/header.php';
?>

<div class="breadcrumbs">
    <a href="<?= url('index.php') ?>">Главная</a>
    <span><a href="<?= url('recipes.php') ?>">Рецепты</a></span>
    <?php if ($recipe['category_title']): ?>
        <span><a href="<?= url('category.php?slug=' . urlencode($recipe['category_slug'])) ?>"><?= e($recipe['category_title']) ?></a></span>
    <?php endif; ?>
    <span><?= e($recipe['title']) ?></span>
</div>

<div class="recipe-top">
    <div class="recipe-photo">
        <?php if ($recipe['image']): ?>
            <img src="<?= e($recipe['image']) ?>" alt="<?= e($recipe['title']) ?>">
        <?php endif; ?>
    </div>

    <div class="recipe-head">
        <h1><?= e($recipe['title']) ?></h1>
        <p class="recipe-lead"><?= e($recipe['description']) ?></p>

        <div class="stars" aria-label="Средний рейтинг">
            <?php $avg = (float)$stats['avg_rating']; ?>
            <?= str_repeat('★', (int)round($avg)) ?><?= str_repeat('<span class="empty">★</span>', 5 - (int)round($avg)) ?>
            <span class="muted" style="letter-spacing:0;font-size:.86rem">
                <?= number_format($avg, 1, ',', ' ') ?> · <?= (int)$stats['cnt'] ?>
                <?= plural((int)$stats['cnt'], 'отзыв', 'отзыва', 'отзывов') ?>
            </span>
        </div>

        <div class="meta-grid">
            <div class="meta-item"><small>Время</small><b><?= e(minutes_human((int)$recipe['prep_minutes'])) ?></b></div>
            <div class="meta-item"><small>Порции</small><b><?= (int)$recipe['servings'] ?></b></div>
            <div class="meta-item"><small>Сложность</small><b><?= e(difficulty_label($recipe['difficulty'])) ?></b></div>
            <div class="meta-item"><small>Кухня</small><b><?= e($recipe['cuisine_title'] ?: '—') ?></b></div>
            <?php if ($recipe['calories']): ?>
                <div class="meta-item"><small>Калории</small><b><?= (int)$recipe['calories'] ?> ккал</b></div>
            <?php endif; ?>
        </div>

        <div class="recipe-actions">
            <form method="post" action="<?= url('recipe.php?slug=' . urlencode($recipe['slug'])) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="favorite">
                <button class="btn <?= $isFavorite ? 'btn-line' : '' ?>" type="submit">
                    <?= $isFavorite ? '♥ В избранном' : '♡ В избранное' ?>
                </button>
            </form>
            <?php if (!$user): ?>
                <a class="btn btn-line" href="<?= url('login.php') ?>">Войти, чтобы сохранить</a>
            <?php endif; ?>
            <?php if ($recipe['api_id']): ?>
                <a class="btn btn-ghost" target="_blank" rel="noopener"
                   href="https://www.themealdb.com/meal/<?= (int)$recipe['api_id'] ?>">Источник в TheMealDB ↗</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="recipe-columns">
    <div>
        <div class="panel">
            <h2>Ингредиенты</h2>
            <?php if ($ingredients): ?>
                <ul class="ingredients">
                    <?php foreach ($ingredients as $ing): ?>
                        <li><span><?= e($ing['title']) ?></span><b><?= e($ing['amount'] ?: '—') ?></b></li>
                    <?php endforeach; ?>
                </ul>
                <p class="form-hint mt-24">Состав рассчитан на <?= (int)$recipe['servings'] ?>
                    <?= plural((int)$recipe['servings'], 'порцию', 'порции', 'порций') ?>.</p>
            <?php else: ?>
                <p class="muted">Состав пока не заполнен.</p>
            <?php endif; ?>
        </div>

        <div class="panel">
            <h2>Как готовить</h2>
            <?php if ($steps): ?>
                <ol class="steps">
                    <?php foreach ($steps as $step): ?>
                        <li><?= e(preg_replace('/^\d+\.\s*/', '', $step)) ?></li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p class="muted">Инструкция пока не заполнена.</p>
            <?php endif; ?>
        </div>
    </div>

    <div id="reviews">
        <div class="panel">
            <h2>Отзывы (<?= (int)$stats['cnt'] ?>)</h2>
            <?php if ($reviews): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review">
                        <div class="review-head">
                            <span class="review-author"><?= e($review['name']) ?></span>
                            <span class="stars"><?= str_repeat('★', (int)$review['rating']) ?><?= str_repeat('<span class="empty">★</span>', 5 - (int)$review['rating']) ?></span>
                            <span class="review-date"><?= e(format_date($review['created_at'])) ?></span>
                        </div>
                        <p><?= e($review['comment']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="muted">Отзывов пока нет — станьте первым.</p>
            <?php endif; ?>

            <?php if ($user): ?>
                <hr style="border:none;border-top:1px solid var(--line);margin:18px 0">
                <h3>Оставить отзыв</h3>
                <form method="post" action="<?= url('recipe.php?slug=' . urlencode($recipe['slug'])) ?>" data-validate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="review">
                    <div class="field">
                        <label for="rating">Оценка</label>
                        <select id="rating" name="rating" required>
                            <option value="5">5 — отлично</option>
                            <option value="4">4 — хорошо</option>
                            <option value="3">3 — нормально</option>
                            <option value="2">2 — так себе</option>
                            <option value="1">1 — не понравилось</option>
                        </select>
                    </div>
                    <div class="field" style="margin-top:12px">
                        <label for="comment">Комментарий</label>
                        <textarea id="comment" name="comment" required
                                  placeholder="Что получилось, что изменили в рецепте…"></textarea>
                    </div>
                    <button class="btn mt-24" type="submit">Отправить отзыв</button>
                </form>
            <?php else: ?>
                <p class="form-hint mt-24">
                    <a href="<?= url('login.php') ?>">Войдите</a> или
                    <a href="<?= url('register.php') ?>">зарегистрируйтесь</a>, чтобы оставить отзыв.
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($similar): ?>
    <section class="section">
        <div class="section-head">
            <div>
                <h2>Похожие рецепты</h2>
                <p>Из той же категории</p>
            </div>
        </div>
        <div class="grid">
            <?php foreach ($similar as $card): ?>
                <?php require __DIR__ . '/inc/recipe-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/inc/footer.php'; ?>
