<?php
/**
 * Конфигурация административной панели: какие таблицы показывать,
 * как подписывать поля и какие из них являются внешними ключами.
 */

return [
    'recipes' => [
        'label'    => 'Рецепты',
        'single'   => 'рецепт',
        'order'    => 'id DESC',
        'list'     => ['id', 'title', 'category_id', 'cuisine_id', 'prep_minutes', 'servings', 'difficulty', 'is_published'],
        'skip'     => ['created_at'],
        'fk'       => [
            'category_id' => ['categories', 'title'],
            'cuisine_id'  => ['cuisines', 'title'],
            'author_id'   => ['users', 'name'],
        ],
        'types'    => [
            'description'  => 'textarea',
            'instructions' => 'textarea',
            'is_published' => 'bool',
            'difficulty'   => 'enum:easy,medium,hard',
            'slug'         => 'slug',
        ],
        'required' => ['title', 'slug'],
    ],

    'categories' => [
        'label'    => 'Категории',
        'single'   => 'категорию',
        'order'    => 'id ASC',
        'list'     => ['id', 'title', 'slug', 'api_name'],
        'fk'       => [],
        'types'    => ['description' => 'textarea'],
        'required' => ['title', 'slug'],
    ],

    'cuisines' => [
        'label'    => 'Кухни',
        'single'   => 'кухню',
        'order'    => 'title ASC',
        'list'     => ['id', 'title', 'slug', 'api_name'],
        'fk'       => [],
        'types'    => [],
        'required' => ['title', 'slug'],
    ],

    'ingredients' => [
        'label'    => 'Ингредиенты',
        'single'   => 'ингредиент',
        'order'    => 'title ASC',
        'list'     => ['id', 'title', 'kind'],
        'fk'       => [],
        'types'    => [],
        'required' => ['title'],
    ],

    'recipe_ingredients' => [
        'label'  => 'Состав рецептов',
        'single' => 'строку состава',
        'order'  => 'id DESC',
        'list'   => ['id', 'recipe_id', 'ingredient_id', 'amount'],
        'fk'     => [
            'recipe_id'     => ['recipes', 'title'],
            'ingredient_id' => ['ingredients', 'title'],
        ],
        'types'    => [],
        'required' => ['recipe_id', 'ingredient_id'],
    ],

    'users' => [
        'label'  => 'Пользователи',
        'single' => 'пользователя',
        'order'  => 'id ASC',
        'list'   => ['id', 'name', 'email', 'phone', 'role_id', 'status', 'created_at'],
        'fk'     => ['role_id' => ['roles', 'title']],
        'types'  => [
            'status'        => 'enum:active,blocked',
            'password_hash' => 'password',
            'email'         => 'email',
            'created_at'    => 'datetime',
        ],
        'required' => ['name', 'email'],
    ],

    'roles' => [
        'label'  => 'Роли',
        'single' => 'роль',
        'order'  => 'id ASC',
        'list'   => ['id', 'code', 'title'],
        'fk'     => [],
        'types'  => [],
        'required' => ['code', 'title'],
    ],

    'reviews' => [
        'label'  => 'Отзывы',
        'single' => 'отзыв',
        'order'  => 'id DESC',
        'list'   => ['id', 'recipe_id', 'user_id', 'rating', 'comment', 'created_at'],
        'fk'     => [
            'recipe_id' => ['recipes', 'title'],
            'user_id'   => ['users', 'name'],
        ],
        'types'  => [
            'comment'       => 'textarea',
            'rating'        => 'number',
            'is_approved'   => 'bool',
            'created_at'    => 'datetime',
        ],
        'required' => ['recipe_id', 'user_id', 'rating'],
    ],

    'favorites' => [
        'label'  => 'Избранное',
        'single' => 'запись избранного',
        'order'  => 'id DESC',
        'list'   => ['id', 'user_id', 'recipe_id', 'created_at'],
        'fk'     => [
            'user_id'   => ['users', 'name'],
            'recipe_id' => ['recipes', 'title'],
        ],
        'types'    => [],
        'required' => ['user_id', 'recipe_id'],
    ],

    'news' => [
        'label'  => 'Новости',
        'single' => 'новость',
        'order'  => 'id DESC',
        'list'   => ['id', 'title', 'slug', 'author_id', 'published_at'],
        'fk'     => ['author_id' => ['users', 'name']],
        'types'  => [
            'body'         => 'textarea',
            'excerpt'      => 'textarea',
            'published_at' => 'datetime',
            'image'        => 'url',
            'slug'         => 'slug',
        ],
        'required' => ['title', 'slug', 'body'],
    ],

    'messages' => [
        'label'  => 'Сообщения',
        'single' => 'сообщение',
        'order'  => 'is_read ASC, id DESC',
        'list'   => ['id', 'name', 'email', 'subject', 'user_id', 'is_read', 'created_at'],
        'fk'     => ['user_id' => ['users', 'name']],
        'types'  => [
            'body'      => 'textarea',
            'is_read'   => 'bool',
            'email'     => 'email',
            'created_at' => 'datetime',
        ],
        'required' => ['name', 'email', 'subject', 'body'],
    ],
];
