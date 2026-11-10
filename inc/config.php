<?php
/**
 * Конфигурация проекта.
 * Значения можно переопределить переменными окружения при развёртывании.
 */

declare(strict_types=1);

// --- База данных -----------------------------------------------------------
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'recipe_aggregator');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// --- Сайт ------------------------------------------------------------------
define('SITE_NAME', 'Вкусно и просто');
define('SITE_TAGLINE', 'Агрегатор проверенных рецептов со всего мира');
define('SITE_EMAIL', 'hello@vkusno.ru');
define('SITE_PHONE', '+7 (900) 000-00-01');

// --- Внешний API -----------------------------------------------------------
define('MEALDB_BASE', 'https://www.themealdb.com/api/json/v1/1');

// --- Прочее ----------------------------------------------------------------
define('PER_PAGE', 9);
define('DEBUG', true);

date_default_timezone_set('Europe/Moscow');

if (DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}
