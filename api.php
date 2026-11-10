<?php
/**
 * Демонстрация работы с внешним API TheMealDB.
 * Запросы выполняются из браузера (fetch), поэтому страница работает
 * даже если серверный PHP не имеет доступа в интернет.
 */

require __DIR__ . '/inc/db.php';

$pageTitle = 'Работа с API';
require __DIR__ . '/inc/header.php';
?>

<div class="breadcrumbs">
    <a href="<?= url('index.php') ?>">Главная</a><span>API</span>
</div>

<div class="section-head">
    <div>
        <h1>Работа с внешним API</h1>
        <p>Сайт обращается к открытому REST API TheMealDB и показывает полученные данные</p>
    </div>
</div>

<div class="panel mb-24">
    <h2>Что такое API в этом проекте</h2>
    <p>
        <strong>API</strong> (программный интерфейс) — это способ, которым одна программа запрашивает данные
        у другой. Наш сайт сам хранит рецепты в собственной базе MySQL, но дополнительно умеет
        обращаться к внешнему сервису <a href="https://www.themealdb.com/api.php" target="_blank" rel="noopener">TheMealDB</a>,
        чтобы показать то, чего ещё нет в локальном каталоге.
    </p>
    <p>
        Запрос выполняется из браузера через <span class="code-inline">fetch()</span> — сервер получает JSON,
        а JavaScript превращает его в карточки. Используются три метода:
    </p>
    <div class="table-wrap">
        <table>
            <thead>
            <tr><th>Метод API</th><th>Адрес</th><th>Что возвращает</th><th>Где на сайте</th></tr>
            </thead>
            <tbody>
            <tr>
                <td>Поиск по названию</td>
                <td><span class="code-inline">search.php?s=…</span></td>
                <td>список блюд с фото, кухней и категорией</td>
                <td>форма ниже, главная страница</td>
            </tr>
            <tr>
                <td>Случайный рецепт</td>
                <td><span class="code-inline">random.php</span></td>
                <td>одно случайное блюдо</td>
                <td>блок «Случайный рецепт» на главной</td>
            </tr>
            <tr>
                <td>Категории</td>
                <td><span class="code-inline">categories.php</span></td>
                <td>14 категорий с иконками и описанием</td>
                <td>кнопка «Показать категории»</td>
            </tr>
            </tbody>
        </table>
    </div>
    <p class="form-hint mt-24">
        Если интернет недоступен, страница не ломается: выводится сообщение об ошибке,
        а локальный каталог продолжает работать — он читает данные из своей базы.
    </p>
</div>

<div data-api-demo data-api-autorun="search">
    <form class="api-search" data-api-form>
        <input type="search" name="term" value="pasta" placeholder="Например: chicken, beef, cake…"
               aria-label="Поисковый запрос к API">
        <button class="btn" type="submit">Найти через API</button>
        <button class="btn btn-line" type="button" data-api-random>Случайный рецепт</button>
        <button class="btn btn-line" type="button" data-api-categories>Показать категории</button>
    </form>

    <div class="api-status" data-api-status>Готовим запрос…</div>
    <div class="grid" data-api-result></div>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
