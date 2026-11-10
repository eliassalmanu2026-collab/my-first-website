<?php
/**
 * Подвал сайта.
 */
?>
</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <a class="logo logo-footer" href="<?= url('index.php') ?>">
                <img src="<?= asset('img/logo.png') ?>" alt="" width="38" height="38">
                <strong><?= e(SITE_NAME) ?></strong>
            </a>
            <p class="footer-note">
                Учебный проект: агрегатор рецептов с пошаговыми инструкциями,
                личным кабинетом и подборками по кухням мира.
            </p>
        </div>
        <div>
            <h4>Разделы</h4>
            <ul class="footer-list">
                <li><a href="<?= url('recipes.php') ?>">Все рецепты</a></li>
                <li><a href="<?= url('categories.php') ?>">Категории</a></li>
                <li><a href="<?= url('cuisines.php') ?>">Кухни мира</a></li>
                <li><a href="<?= url('news.php') ?>">Новости</a></li>
            </ul>
        </div>
        <div>
            <h4>Пользователям</h4>
            <ul class="footer-list">
                <li><a href="<?= url('register.php') ?>">Регистрация</a></li>
                <li><a href="<?= url('login.php') ?>">Вход</a></li>
                <li><a href="<?= url('cabinet.php') ?>">Личный кабинет</a></li>
                <li><a href="<?= url('api.php') ?>">Демонстрация API</a></li>
            </ul>
        </div>
        <div>
            <h4>Контакты</h4>
            <ul class="footer-list">
                <li><a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a></li>
                <li><?= e(SITE_PHONE) ?></li>
                <li><a href="<?= url('contacts.php') ?>">Форма обратной связи</a></li>
            </ul>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>© <?= date('Y') ?> <?= e(SITE_NAME) ?>. Учебная практика УП.09.01</span>
        <span>Данные рецептов: <a href="https://www.themealdb.com" target="_blank" rel="noopener">TheMealDB</a></span>
    </div>
</footer>

<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
