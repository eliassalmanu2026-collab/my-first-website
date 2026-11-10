<?php
/**
 * Карточка рецепта в сетке.
 * Ожидает $card: id, title, slug, image, description, prep_minutes, servings,
 * difficulty, category_title, cuisine_title, favorites_count (необязательно).
 */
?>
<article class="card">
    <div class="card-media">
        <a href="<?= url('recipe.php?slug=' . urlencode($card['slug'])) ?>">
            <?php if (!empty($card['image'])): ?>
                <img src="<?= e($card['image']) ?>" alt="<?= e($card['title']) ?>" loading="lazy">
            <?php endif; ?>
        </a>
        <?php if (!empty($card['category_title'])): ?>
            <span class="badge"><?= e($card['category_title']) ?></span>
        <?php endif; ?>
        <?php if (!empty($card['favorites_count'])): ?>
            <span class="badge badge-fav">♥ <?= (int)$card['favorites_count'] ?></span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <h3><a href="<?= url('recipe.php?slug=' . urlencode($card['slug'])) ?>"><?= e($card['title']) ?></a></h3>
        <?php if (!empty($card['description'])): ?>
            <p class="card-desc"><?= e($card['description']) ?></p>
        <?php endif; ?>
        <div class="card-meta">
            <?php if (!empty($card['prep_minutes'])): ?>
                <span>⏱ <?= e(minutes_human((int)$card['prep_minutes'])) ?></span>
            <?php endif; ?>
            <?php if (!empty($card['servings'])): ?>
                <span>🍽 <?= (int)$card['servings'] ?> порц.</span>
            <?php endif; ?>
            <?php if (!empty($card['difficulty'])): ?>
                <span><?= e(difficulty_label($card['difficulty'])) ?></span>
            <?php endif; ?>
            <?php if (!empty($card['cuisine_title'])): ?>
                <span><?= e($card['cuisine_title']) ?></span>
            <?php endif; ?>
        </div>
    </div>
</article>
