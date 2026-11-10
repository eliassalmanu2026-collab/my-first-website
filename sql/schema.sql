-- ---------------------------------------------------------------------------
-- Агрегатор рецептов «Вкусно и просто» — схема базы данных
-- СУБД: MariaDB / MySQL 5.7+, кодировка utf8mb4
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP VIEW IF EXISTS v_unread_messages;
DROP VIEW IF EXISTS v_user_activity;
DROP VIEW IF EXISTS v_category_stats;
DROP VIEW IF EXISTS v_top_recipes;
DROP VIEW IF EXISTS v_recipe_catalog;

DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS favorites;
DROP TABLE IF EXISTS recipe_ingredients;
DROP TABLE IF EXISTS ingredients;
DROP TABLE IF EXISTS recipes;
DROP TABLE IF EXISTS cuisines;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS news;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- Роли пользователей
-- ---------------------------------------------------------------------------
CREATE TABLE roles (
    id    TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code  VARCHAR(32)      NOT NULL COMMENT 'машинный код роли',
    title VARCHAR(64)      NOT NULL COMMENT 'название для интерфейса',
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_code (code)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Пользователи
-- ---------------------------------------------------------------------------
CREATE TABLE users (
    id            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    role_id       TINYINT UNSIGNED NOT NULL,
    name          VARCHAR(100)     NOT NULL,
    email         VARCHAR(150)     NOT NULL,
    phone         VARCHAR(20)               DEFAULT NULL,
    password_hash VARCHAR(255)     NOT NULL,
    status        ENUM ('active','blocked') NOT NULL DEFAULT 'active',
    created_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role_id),
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Категории блюд (соответствуют категориям TheMealDB)
-- ---------------------------------------------------------------------------
CREATE TABLE categories (
    id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    api_name    VARCHAR(50)              DEFAULT NULL COMMENT 'название в TheMealDB (Beef, Dessert...)',
    title       VARCHAR(100)      NOT NULL,
    slug        VARCHAR(100)      NOT NULL,
    description TEXT,
    image       VARCHAR(255)             DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug),
    UNIQUE KEY uq_categories_api (api_name)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Кухни мира (соответствуют полю strArea в TheMealDB)
-- ---------------------------------------------------------------------------
CREATE TABLE cuisines (
    id       SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    api_name VARCHAR(50)              DEFAULT NULL,
    title    VARCHAR(100)      NOT NULL,
    slug     VARCHAR(100)      NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cuisines_slug (slug),
    UNIQUE KEY uq_cuisines_api (api_name)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Новости проекта
-- ---------------------------------------------------------------------------
CREATE TABLE news (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title        VARCHAR(200) NOT NULL,
    slug         VARCHAR(200) NOT NULL,
    excerpt      VARCHAR(400)          DEFAULT NULL,
    body         MEDIUMTEXT   NOT NULL,
    image        VARCHAR(255)          DEFAULT NULL,
    published_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_news_slug (slug),
    KEY idx_news_published (published_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Рецепты
-- ---------------------------------------------------------------------------
CREATE TABLE recipes (
    id           INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    api_id       INT UNSIGNED               DEFAULT NULL COMMENT 'idMeal из TheMealDB',
    title        VARCHAR(200)      NOT NULL,
    slug         VARCHAR(220)      NOT NULL,
    category_id  SMALLINT UNSIGNED          DEFAULT NULL,
    cuisine_id   SMALLINT UNSIGNED          DEFAULT NULL,
    description  TEXT,
    instructions MEDIUMTEXT,
    image        VARCHAR(255)               DEFAULT NULL,
    video        VARCHAR(255)               DEFAULT NULL,
    prep_minutes SMALLINT UNSIGNED          DEFAULT NULL,
    servings     TINYINT UNSIGNED           DEFAULT NULL,
    difficulty   ENUM ('easy','medium','hard') NOT NULL DEFAULT 'medium',
    calories     SMALLINT UNSIGNED          DEFAULT NULL,
    is_published TINYINT (1)       NOT NULL DEFAULT 1,
    author_id    INT UNSIGNED               DEFAULT NULL,
    created_at   DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_recipes_slug (slug),
    UNIQUE KEY uq_recipes_api (api_id),
    KEY idx_recipes_category (category_id),
    KEY idx_recipes_cuisine (cuisine_id),
    CONSTRAINT fk_recipes_category FOREIGN KEY (category_id) REFERENCES categories (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_recipes_cuisine FOREIGN KEY (cuisine_id) REFERENCES cuisines (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_recipes_author FOREIGN KEY (author_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Справочник ингредиентов и связь «рецепт — ингредиент»
-- ---------------------------------------------------------------------------
CREATE TABLE ingredients (
    id    SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(120)      NOT NULL,
    kind  VARCHAR(50)               DEFAULT NULL COMMENT 'группа: овощи, мясо, специи...',
    PRIMARY KEY (id),
    UNIQUE KEY uq_ingredients_title (title)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE recipe_ingredients (
    id            INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    recipe_id     INT UNSIGNED      NOT NULL,
    ingredient_id SMALLINT UNSIGNED NOT NULL,
    amount        VARCHAR(60)               DEFAULT NULL COMMENT 'количество с единицей измерения',
    PRIMARY KEY (id),
    UNIQUE KEY uq_recipe_ingredient (recipe_id, ingredient_id),
    KEY idx_ri_ingredient (ingredient_id),
    CONSTRAINT fk_ri_recipe FOREIGN KEY (recipe_id) REFERENCES recipes (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_ri_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Избранное и отзывы
-- ---------------------------------------------------------------------------
CREATE TABLE favorites (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    recipe_id  INT UNSIGNED NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_fav_pair (user_id, recipe_id),
    KEY idx_fav_recipe (recipe_id),
    CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_fav_recipe FOREIGN KEY (recipe_id) REFERENCES recipes (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE reviews (
    id         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    recipe_id  INT UNSIGNED  NOT NULL,
    user_id    INT UNSIGNED  NOT NULL,
    rating     TINYINT       NOT NULL,
    comment    TEXT,
    is_approved TINYINT (1)  NOT NULL DEFAULT 1,
    created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_reviews_recipe (recipe_id),
    KEY idx_reviews_user (user_id),
    CONSTRAINT fk_reviews_recipe FOREIGN KEY (recipe_id) REFERENCES recipes (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Сообщения из формы обратной связи
-- ---------------------------------------------------------------------------
CREATE TABLE messages (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL,
    phone      VARCHAR(20)           DEFAULT NULL,
    subject    VARCHAR(200) NOT NULL,
    body       TEXT         NOT NULL,
    is_read    TINYINT (1)  NOT NULL DEFAULT 0,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_messages_read (is_read)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- ПРЕДСТАВЛЕНИЯ (VIEW) — 5 штук
-- ---------------------------------------------------------------------------

-- 1. Каталог рецептов: полная карточка с категорией, кухней, рейтингом
CREATE OR REPLACE VIEW v_recipe_catalog AS
SELECT r.id,
       r.title,
       r.slug,
       r.image,
       r.prep_minutes,
       r.servings,
       r.difficulty,
       c.title  AS category_title,
       cu.title AS cuisine_title,
       COUNT(DISTINCT ri.id)                     AS ingredients_count,
       COALESCE(ROUND(AVG(rv.rating), 1), 0)     AS avg_rating,
       COUNT(DISTINCT rv.id)                     AS reviews_count,
       (SELECT COUNT(*) FROM favorites f WHERE f.recipe_id = r.id) AS favorites_count
FROM recipes r
         LEFT JOIN categories c ON c.id = r.category_id
         LEFT JOIN cuisines cu ON cu.id = r.cuisine_id
         LEFT JOIN recipe_ingredients ri ON ri.recipe_id = r.id
         LEFT JOIN reviews rv ON rv.recipe_id = r.id
WHERE r.is_published = 1
GROUP BY r.id, r.title, r.slug, r.image, r.prep_minutes, r.servings, r.difficulty,
         c.title, cu.title;

-- 2. Популярные рецепты: сортировка по числу добавлений в избранное
CREATE OR REPLACE VIEW v_top_recipes AS
SELECT r.id,
       r.title,
       r.image,
       c.title AS category_title,
       (SELECT COUNT(*) FROM favorites f WHERE f.recipe_id = r.id) AS favorites_count,
       (SELECT COALESCE(ROUND(AVG(rv.rating), 1), 0) FROM reviews rv WHERE rv.recipe_id = r.id) AS avg_rating
FROM recipes r
         LEFT JOIN categories c ON c.id = r.category_id
WHERE r.is_published = 1
ORDER BY favorites_count DESC, avg_rating DESC;

-- 3. Статистика по категориям
CREATE OR REPLACE VIEW v_category_stats AS
SELECT c.id,
       c.title,
       COUNT(r.id)                       AS recipes_count,
       COALESCE(ROUND(AVG(rv.rating), 2), 0) AS avg_rating
FROM categories c
         LEFT JOIN recipes r ON r.category_id = c.id AND r.is_published = 1
         LEFT JOIN reviews rv ON rv.recipe_id = r.id
GROUP BY c.id, c.title
ORDER BY recipes_count DESC;

-- 4. Активность пользователей
CREATE OR REPLACE VIEW v_user_activity AS
SELECT u.id,
       u.name,
       u.email,
       ro.title AS role_title,
       (SELECT COUNT(*) FROM favorites f WHERE f.user_id = u.id) AS favorites_count,
       (SELECT COUNT(*) FROM reviews rv WHERE rv.user_id = u.id)  AS reviews_count,
       (SELECT MAX(f2.created_at) FROM favorites f2 WHERE f2.user_id = u.id) AS last_favorite_at,
       u.created_at
FROM users u
         JOIN roles ro ON ro.id = u.role_id;

-- 5. Непрочитанные сообщения с ожиданием
CREATE OR REPLACE VIEW v_unread_messages AS
SELECT m.id,
       m.name,
       m.email,
       m.subject,
       m.created_at,
       DATEDIFF(NOW(), m.created_at) AS days_waiting
FROM messages m
WHERE m.is_read = 0
ORDER BY m.created_at ASC;
