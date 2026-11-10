/* ==========================================================================
   Скрипты сайта: мобильное меню, маска телефона, проверка формы,
   демонстрация работы с внешним API TheMealDB.
   ========================================================================== */

(function () {
    'use strict';

    /* --------------------------------------------------- мобильное меню */
    const toggle = document.querySelector('[data-nav-toggle]');
    const header = document.querySelector('.site-header');
    if (toggle && header) {
        toggle.addEventListener('click', function () {
            const open = header.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    /* --------------------------------------------------- маска телефона */
    document.querySelectorAll('input[data-mask="phone"]').forEach(function (input) {
        function format(value) {
            let digits = value.replace(/\D/g, '');
            if (digits.startsWith('8')) { digits = '7' + digits.slice(1); }
            if (!digits.startsWith('7')) { digits = '7' + digits; }
            digits = digits.slice(0, 11);
            let out = '+7';
            if (digits.length > 1) { out += ' (' + digits.slice(1, 4); }
            if (digits.length >= 5) { out += ') ' + digits.slice(4, 7); }
            if (digits.length >= 8) { out += '-' + digits.slice(7, 9); }
            if (digits.length >= 10) { out += '-' + digits.slice(9, 11); }
            return out;
        }
        input.addEventListener('input', function () {
            input.value = format(input.value);
        });
        input.addEventListener('focus', function () {
            if (!input.value) { input.value = '+7 ('; }
        });
        input.addEventListener('blur', function () {
            if (input.value === '+7 (' || input.value === '+7') { input.value = ''; }
        });
    });

    /* ------------------------------------------- подсказки при валидации */
    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            let firstBad = null;
            form.querySelectorAll('[required]').forEach(function (field) {
                const empty = !field.value.trim();
                field.classList.toggle('is-invalid', empty);
                if (empty && !firstBad) { firstBad = field; }
            });

            const pass = form.querySelector('input[name="password"]');
            const pass2 = form.querySelector('input[name="password2"]');
            if (pass && pass.value) {
                if (pass.value.length < 5) {
                    alert('Пароль должен содержать не менее 5 символов.');
                    event.preventDefault();
                    pass.focus();
                    return;
                }
                if (!/[A-Za-zА-Яа-я]/.test(pass.value) || !/\d/.test(pass.value)) {
                    alert('Пароль должен состоять из букв и цифр.');
                    event.preventDefault();
                    pass.focus();
                    return;
                }
            }
            if (pass && pass2 && pass.value !== pass2.value) {
                alert('Пароли не совпадают.');
                event.preventDefault();
                pass2.focus();
                return;
            }

            if (firstBad) {
                event.preventDefault();
                firstBad.focus();
            }
        });
    });

    /* ------------------------------------------------ внешний API TheMealDB */
    const apiRoot = 'https://www.themealdb.com/api/json/v1/1/';
    const apiBlock = document.querySelector('[data-api-demo]');

    if (apiBlock) {
        const status = apiBlock.querySelector('[data-api-status]');
        const result = apiBlock.querySelector('[data-api-result]');

        function setStatus(text) {
            if (status) { status.textContent = text; }
        }

        function card(meal) {
            const title = meal.strMeal || 'Без названия';
            const img = meal.strMealThumb || '';
            const area = meal.strArea || '';
            const cat = meal.strCategory || '';
            const id = meal.idMeal || '';
            return '<article class="card">'
                + '<div class="card-media">'
                + (img ? '<img src="' + img + '" alt="' + title.replace(/"/g, '') + '" loading="lazy">' : '')
                + (cat ? '<span class="badge">' + cat + '</span>' : '')
                + '</div>'
                + '<div class="card-body">'
                + '<h3>' + title + '</h3>'
                + '<p class="card-desc">' + (area ? 'Кухня: ' + area : 'Данные из открытой базы TheMealDB') + '</p>'
                + '<div class="card-meta"><span>id: ' + id + '</span>'
                + '<a class="section-link" target="_blank" rel="noopener" '
                + 'href="https://www.themealdb.com/meal/' + id + '">Открыть на TheMealDB</a></div>'
                + '</div></article>';
        }

        function render(meals, emptyText) {
            if (!result) { return; }
            if (!meals || !meals.length) {
                result.innerHTML = '<div class="empty-state">' + (emptyText || 'Ничего не найдено') + '</div>';
                return;
            }
            result.innerHTML = meals.map(card).join('');
        }

        async function call(endpoint, params) {
            const query = params ? '?' + new URLSearchParams(params).toString() : '';
            const response = await fetch(apiRoot + endpoint + query);
            if (!response.ok) { throw new Error('HTTP ' + response.status); }
            return response.json();
        }

        async function search(term) {
            setStatus('Запрос к API: search.php?s=' + term + ' …');
            try {
                const data = await call('search.php', { s: term });
                render(data.meals, 'По запросу «' + term + '» ничего не найдено');
                setStatus('Ответ получен. Найдено: ' + (data.meals ? data.meals.length : 0)
                    + '. Данные пришли напрямую из TheMealDB.');
            } catch (error) {
                setStatus('Не удалось получить данные: ' + error.message
                    + '. Проверьте подключение к интернету.');
            }
        }

        async function random() {
            setStatus('Запрос к API: random.php …');
            try {
                const data = await call('random.php');
                render(data.meals, 'Случайный рецепт не получен');
                setStatus('Ответ получен: случайный рецепт из базы TheMealDB.');
            } catch (error) {
                setStatus('Не удалось получить данные: ' + error.message);
            }
        }

        async function categories() {
            setStatus('Запрос к API: categories.php …');
            try {
                const data = await call('categories.php');
                const list = (data.categories || []).map(function (c) {
                    return {
                        idMeal: c.idCategory,
                        strMeal: c.strCategory,
                        strMealThumb: c.strCategoryThumb,
                        strCategory: 'категория',
                        strArea: ''
                    };
                });
                render(list, 'Категории не получены');
                setStatus('Ответ получен: ' + list.length + ' категорий из TheMealDB.');
            } catch (error) {
                setStatus('Не удалось получить данные: ' + error.message);
            }
        }

        const form = apiBlock.querySelector('[data-api-form]');
        if (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                const term = form.querySelector('input[name="term"]').value.trim() || 'chicken';
                search(term);
            });
        }
        apiBlock.querySelector('[data-api-random]')?.addEventListener('click', random);
        apiBlock.querySelector('[data-api-categories]')?.addEventListener('click', categories);

        const autorun = apiBlock.dataset.apiAutorun;
        if (autorun === 'random') {
            random();
        } else if (autorun === 'search') {
            search('pasta');
        } else {
            setStatus('Введите запрос или нажмите кнопку — данные придут напрямую из TheMealDB.');
        }
    }

    /* --------------------------- подтверждение удаления в админ-панели */
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!confirm(form.dataset.confirm || 'Удалить запись?')) {
                event.preventDefault();
            }
        });
    });
})();
