# TODO — Lapki плагін

> Складено: 2026-07-07. Оновлено: 2026-07-09 (сесія 4) — дописано тести для `Lapki_Tag`, `Lapki_Attributes` і REST endpoints media/attributes/applications (пробіл, зафіксований у сесії 3). Тест-сьют: 87 тестів, 199 assertions, всі проходять.

## Виконано в цій сесії

1. ✅ **Авторизація REST API** — write-операції (`POST`/`PUT`/`DELETE`) тепер вимагають capability (`lapki_manage_animals`/`lapki_manage_organizations`/`lapki_manage_attributes`) і перевірку власності організації; `GET` лишився публічним (це петфайндер-подібний публічний пошук). Перевірено анонімно (401) і адміном (201/200).
2. ✅ **Nonce/capability в `class-eq-form.php`** розкоментовано (`save()`).
3. ✅ **Ролі/capabilities** — `inc/class-lapki-roles.php`: `lapki_shelter_admin`, `lapki_volunteer`, три capability, автоматично видані ролі `administrator`.
4. ✅ **Міграції БД через `dbDelta()`** — `inc/class-lapki-migrations.php`, викликається при активації і автоматично (version-gate на `plugins_loaded`) для вже активних інсталяцій. Створює всі таблиці, включно з новою `wp_lapki_applications`.
5. ✅ **Seed-дані** — довідник атрибутів (965 рядків: age/gender/size/coat/color/breed для dog/cat/bird/rabbit, uk+en) забандлено в `inc/data/seed-attributes.sql` і завантажується лише якщо таблиця порожня (нова інсталяція); демо-організація+тварина створюються так само за умовою.
6. ✅ **Локатор шаблонів** — `inc/class-lapki-template-loader.php` (аналог `wc_get_template()`): тема → плагін.
7. ✅ **Фронтенд-шаблони** — `templates/archive-animals.php`, `templates/single-animal.php`, `templates/archive-organizations.php`, `templates/single-organization.php` + rewrite rules у `inc/class-lapki-frontend.php` (`/animals/`, `/animals/{id}/`, `/organizations/`, `/organizations/{id}/`). Тему `lapki.js` виправлено під реальну форму відповіді REST API (`data.data`, `primary_photo.thumbnail_url`, `organization_name`).
8. ✅ **Заявка на усиновлення + email** — таблиця `wp_lapki_applications`, `POST /lapki/v1/applications` (публічний, як контактна форма), `GET/PUT` (власник організації або адмін), email організації + підтвердження заявнику через `wp_mail()`.
9. ✅ **Адмінка «Організації»** — повноцінна таблиця + модалка створення/редагування (аналогічно атрибутам), додано `PUT`/`DELETE /organizations/{id}` в REST API і `update()`/`delete()` в моделі.
10. ✅ **Сторінка налаштувань** — `Lapki → Налаштування`: дефолтна дистанція пошуку, розмір сторінки, email для нотифікацій. Підключено до REST API і email-логіки.
11. ✅ **Локалізація** — `.pot` згенеровано (`wp i18n make-pot`), `lapki-uk.po/mo` (passthrough) і `lapki-en_US.po/mo` (реальний переклад) скомпільовано й перевірено (`__('Тварини','lapki')` → `Animals` при locale `en_US`). **Обсяг обмежений**: обгорнуто лише назви пунктів меню, повідомлення помилок REST API та сторінку налаштувань (~40 рядків) — тіла адмін-сторінок, фронтенд-шаблони і JS-рядки все ще хардкод українською.
12. ✅ **PHPUnit тести** (сесія 3, 2026-07-08) — тестову БД `lapki_test` надано, `composer install` виконано (`phpunit/phpunit` зафіксовано на `^9.6` — `wp-phpunit` 6.9.4 ще не сумісний з PHPUnit 10 в `expectDeprecated()`). `svn` на сервері відсутній — WP core і тестовий фреймворк розгорнуто вручну через `git sparse-checkout` у `/tmp/wordpress` і `/tmp/wordpress-tests-lib`. Виправлено `phpunit.xml.dist` (директорійний фільтр `prefix="test-"` ламав пошук тестів під PHPUnit 10/`TestSuiteLoader`). Написано `tests/Test_Lapki_Animal.php` — 13 тестів на `create()`/`get()`/`search()`, всі проходять. Деталі й відомі підводні камені (демо-seed поза транзакцією, `UNIQUE KEY unique_wp_user`) — див. `CHANGELOG.md`.
13. ✅ **Документація** — цей файл, `CLAUDE.md`, `.claude/SNAPSHOT.md`, `CHANGELOG.md` оновлені.
14. ✅ **Дописано тест-сьют** (сесія 4, 2026-07-09) — закрито пробіл, зафіксований у п.12 сесії 3 (`Lapki_Tag`, `Lapki_Attributes` і REST endpoints media/attributes/applications без тестів). Додано `tests/Test_Lapki_Tag.php` (5), `tests/Test_Lapki_Attributes.php` (13), розширено `tests/Test_Lapki_Rest_Api.php` (+22: media ownership, attributes admin-only, applications public-create + owner-scoped GET/PUT). Разом: **87 тестів, 199 assertions, всі проходять**. Деталі — `CHANGELOG.md`.

## Свідомі обмеження обсягу / відомі пробіли

- **Локалізація не 100%**: покриті тільки нові/найбільш «дороті» рядки (меню, помилки API, налаштування). Повне обгортання адмін-HTML, фронтенд-шаблонів і JS в `__()`/`wp.i18n` — окрема велика задача.
- **Не покрито тестами**: успішне завантаження файлу через `POST /animals/{id}/media` (`upload_animal_media` викликає `move_uploaded_file()`, який завжди повертає `false` поза справжнім HTTP-запитом; перевірено лише межу авторизації endpoint'у, 403 без capability).
- **Не реалізовано**: публічна фронтенд-форма самостійного додавання тварини (`/add-animal/`, посилання в темі поки веде в нікуди — рендерить дефолтний `page.php` теми), сторінка "Про нас"/"Контакти"/"Privacy policy" з футера — не було в explicit todo-скоупі цієї сесії.
- **Node.js `server/`** — свідомо не чіпали (за прямою вказівкою користувача: міграція на Node.js відбудеться тільки після повного тестування і готовності WP-плагіна до продакшену).
- Email надсилається через стандартний `wp_mail()` — залежить від налаштованого SMTP на сервері; не перевірялося з реальною доставкою (лише що виклик відбувається без помилок).

## Швидка перевірка (як я тестував зміни)

- REST API: `wp eval` з `rest_do_request()` для анонімного/адмінського доступу (create/update/delete тварин, організацій, атрибутів, заявок).
- Фронтенд: `curl` по `/animals/`, `/animals/1/`, `/organizations/`, `/organizations/1/` — усі 200, неіснуюча тварина — 404.
- Адмінка: рендер `organizations_page()`/`settings_page()` напряму через `wp eval` (WP-CLI не встановлює `is_admin()`), перевірено на відсутність PHP-помилок.
- `php -l` і `node --check` на всіх змінених PHP/JS файлах.
- `tail` логів Apache після тестів — нових помилок не з'явилось.
