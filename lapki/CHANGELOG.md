# CHANGELOG

## [Unreleased] — 2026-07-12 (сесія 14)

### Нова функція — атрибут тварини "З зони бойових дій"

- **Новий стовпець `from_war_zone`** у `wp_lapki_animals` (`tinyint(1) DEFAULT NULL`, поруч з `special_needs`/`shots_current`) — через `dbDelta()`-міграцію (`class-lapki-migrations.php`, `DB_VERSION` піднято до `2.2.0`, авто-апгрейд без реактивації плагіна, як і для попередніх змін схеми). Спільний для всіх типів тварин (не залежить від `type`/`species`), як і `good_with_*`.
- **`/animals/{id}/`** (`templates/single-animal.php`) — додано до списку сумісності поруч з "Добре з дітьми"/"Добре з собаками"/"Добре з котами": "З зони бойових дій" (✅/❌ через Font Awesome, як і решта пунктів списку).
- **Адмін-панель** (`class-lapki-admin.php`) — чекбокс `from_war_zone` у розділі "Здоров'я та особливості" форми тварини; `js/lapki-admin.js` вже читає/записує чекбокси генерично за `id`/`name`, тож JS-змін не знадобилось.
- **REST API** — жодних змін не було потрібно: `Lapki_Animal::get()`/`search()` роблять `SELECT a.*`, тож нове поле автоматично з'явилось у відповіді `GET /animals/{id}` і `GET /animals`.
- **Тестові дані**: рандомно проставлено `from_war_zone = 1` п'яти тваринам з продакшн-демо-бази (id 4 "Барон", 9 "Байт", 18 "Річі", 20 "Багіра", 30 "Кузя") — через прямий `UPDATE`, вибір через `ORDER BY RAND() LIMIT 5`.
- Перевірено: колонка з'явилась автоматично при першому запиті до сайту (`maybe_migrate()` на `plugins_loaded`), `php -l` без помилок, усі 5 тварин показують новий пункт на публічній сторінці й у відповіді REST API, тварини без встановленого прапорця (`NULL`) пункт не показують (цикл `$compat` пропускає `null`).

## [Unreleased] — 2026-07-12 (сесія 13)

### Нова функція — реcкін під тему SB Admin 2

- **Повний рескін кольорів/кнопок/полів/іконок сайту під завантажену тему `.doc/startbootstrap-sb-admin-2-gh-pages`** (startbootstrap SB Admin 2), за проханням — "ігноруючи поточні кастомні" кольори.
  - **Палітра** (`assets/css/lapki.css`): `:root`-змінні перевизначено на офіційні кольори SB Admin 2 — primary `#4e73df`, success `#1cc88a`, info `#36b9cc`, warning `#f6c23e`, danger `#e74a3b`, сіра шкала `gray-100…gray-900`. Стара "зелена" гілка токенів (`--lapki-green*`) тепер вказує на `success`, стара "помаранчева" (`--lapki-orange*`) і окремий токен `--lapki-btn-green` (раніше teal `#00A36C`, використовувався для кнопок/фокус-кілець/активних станів) — обидва тепер вказують на `primary`. Імена змінних навмисно лишили старими (щоб не чіпати кожен селектор), значення — нові.
  - **Border-radius/тіні**: замість розмаїття 8–20px радіусів і довільних `rgba()`-тіней — єдиний `--sb-radius: .35rem` (кнопки, картки, дропдауни, форма реєстрації) і формула тіні SB Admin 2 (`--sb-shadow`/`--sb-shadow-sm`).
  - **Шрифт**: Nunito (Google Fonts, підключено в `functions.php`) замість системного стеку.
  - **Іконки**: підключено Font Awesome 5.15.3 (та сама версія, що бандлена в темі, CDN cdnjs) і замінено емодзі-іконки (🐕🐈🐦🐇🐾📍☎✉🌐✅❌✓❤️) на `<i class="fas fa-*">` по всій темі й шаблонах плагіна (`footer.php`, `front-page.php`, `404.php`, `index.php`, `assets/js/lapki.js`, `single-animal.php`, `single-organization.php`, `archive-organizations.php`, `shortcode-cabinet.php`). Логіка кастомного дропдауна (`initCustomDropdowns()`) переведена з `textContent` на `innerHTML` при синхронізації вибраного значення — інакше іконка губилась при виборі опції в тумблері.
  - **Виняток — нативні `<select><option>`** (`archive-animals.php`): емодзі там **лишились навмисно** — Font Awesome не може рендеритись усередині нативних `<option>`, це технічне обмеження HTML, а не стилістичний вибір.
  - **Виняток — `js/animals.js` (embed-віджет)**: замінено емодзі 🐾/📍 на inline SVG (не Font Awesome) — віджет свідомо позиціонований як "без залежностей" для вставки на сторонні сайти, тож CDN-залежність від Font Awesome туди свідомо не додавалась; палітра й радіус/тіні всередині власного `injectStyles()` так само перефарбовані під SB Admin 2. Емодзі-мітки типу тварини (🐕🐈🐦🐇) в самому віджеті лишені як є — це єдиний по-справжньому нульова-залежність спосіб показати кольорову іконку без підключення шрифтів/бібліотек.
- Перевірено вручну: `php -l`/`node --check` на всіх змінених файлах; curl на `/`, `/animals/`, `/animals/{id}/`, `/organizations/`, `/organizations/{id}/`, `/signup/`, `/widget-demo/` (усі 200, нові іконки й кольори на місці); CORS-заголовки на `/animals`/`/organizations` не зламані; повторна симуляція `js/animals.js` в Node (`vm`-мок DOM) проти реального API підтвердила нову палітру й inline SVG в рендері.

## [Unreleased] — 2026-07-12 (сесія 12)

### Дрібна правка — форма реєстрації

- **Форма `/signup/` виглядала занадто блідо** — стандартні дефолтні стилі Bootstrap 5 без жодного кастомного оформлення (`templates/shortcode-signup.php`, `assets/css/lapki.css`, `assets/js/lapki.js`).
  - `.lapki-signup` тепер картка (білий фон, заокруглення 20px, тінь, паддінг), чіткіші рамки полів (`1.5px solid #c7cdd4` замість блідого дефолту), фокус підсвічується брендовим зеленим (`--lapki-btn-green`) замість дефолтного синього Bootstrap.
  - **Валідація через стокові класи Bootstrap 5** (`is-invalid`/`invalid-feedback`/`was-validated`) — червона рамка й іконка оклику на полі додаються самим Bootstrap (нічого кастомного малювати не треба, лише не перекривати `background-image`). Кожне обов'язкове поле отримало `.invalid-feedback` з текстом помилки.
  - JS (`initSignupForm()`): на сабміт — `form.classList.add('was-validated')` + `form.checkValidity()`, без відправки запиту, якщо є порожні/некоректні обов'язкові поля (без нативних браузерних тултипів — `checkValidity()`, не `reportValidity()`). Серверні помилки (`email_exists` → підсвітити email, `invalid_type` → підсвітити тип реєстрації) мапляться на конкретне поле через `setFieldError()`; `is-invalid` знімається одразу, як тільки користувач редагує поле.
- Перевірено: `php -l`/`node --check` без помилок, CSS/JS роздаються з продакшна з новим вмістом (`was-validated`, `.lapki-signup` у деплойнутих файлах).

## [Unreleased] — 2026-07-11 (сесія 10)

### Виправлення

- **`/organizations/{id}/` показував неправильну кількість тварин у заголовку** (`templates/single-organization.php:92`) — заголовок брав `$organization['animals_count']`, який рахує лише `status = 'adoptable'` (у `Lapki_Organization::get()`), тоді як сітка карток під заголовком рендерить **усіх** тварин організації незалежно від статусу (`Lapki_Animal::search(['status' => ''])`). Приклад: організація id=1 має 7 тварин (3 adoptable + 3 found + 1 hold) — заголовок показував "(3)", хоча карток під ним було 7. Виправлено: заголовок тепер рахує `count($animals)` — той самий масив, що й рендериться нижче, тож число завжди відповідає видимому списку.

## [Unreleased] — 2026-07-11 (сесія 9)

### Віджет вбудовування тварин притулку

- **`/js/animals.js`** — самодостатній vanilla JS-скрипт (без залежностей) для вставки в чужий блог/сайт: `<script src="https://lapki.help/js/animals.js?organization_id=1"></script>`. Читає `organization_id` (обов'язковий), `limit` (за замовчуванням 24) і `status` (за замовчуванням `adoptable`) з рядка запиту власного `<script src>` через `document.currentScript`, вставляє контейнер одразу після тега скрипта, тягне дані з `GET /wp-json/lapki/v1/animals` + `/organizations/{id}` і рендерить сітку карток (фото/кличка/тип/вік/місто) на всю ширину контейнера (CSS Grid `auto-fill`), зі стилями, ін'єктованими один раз у `<head>` під унікальними класами `lapki-embed-*` — не залежить від CSS/Bootstrap хост-сайту. Файл лежить у корені сайту (`/var/www/lapki/js/`), тому Apache віддає його напряму, без завантаження WordPress.
- **CORS** (`class-lapki-rest-api.php::allow_embed_cors()`, хук `rest_pre_serve_request`) — без цього браузер на сторонньому домені блокував би `fetch()` до API (дефолтний `rest_send_cors_headers()` WordPress дозволяє лише той самий origin). Додано `Access-Control-Allow-Origin: *` **лише** для публічних `GET /animals` і `GET /organizations` — інші (write) ендпоінти лишаються з дефолтною поведінкою WP.
- **`/widget-demo/`** — тестова сторінка для перевірки скрипта (нова rewrite-route + `templates/widget-demo.php`, за патерном `/animals/`, `/organizations/`). Показує код для вставки і живий приклад у "чужому" блоці. **Навмисно не додана в меню**, за проханням користувача.
- Перевірено: `curl` (статичний файл віддається напряму, CORS-заголовки коректні для GET `/animals`/`/organizations` і відсутні на write-ендпоінтах), і повна симуляція виконання скрипта в Node (`vm` з мінімальним DOM-мок) проти реального API — рендерить картки з фото/тегами і посиланням на організацію без помилок.

## [Unreleased] — 2026-07-11 (сесія 8)

### Кабінет користувача

- **Сторінка `/cabinet/` + шорткод `[lapki_cabinet]`** (`templates/shortcode-cabinet.php`, реєстрація шорткода в `class-lapki-frontend.php`) — особистий кабінет для залогінених користувачів з боковим меню: **Головна** (ім'я, email, телефон, тип реєстрації користувача + картка організації: назва, тип, місто, контакти, бейдж верифікації, посилання на публічну сторінку), **Мої тварини** (`?tab=animals` — всі тварини організації користувача через `Lapki_Animal::search(['organization_id' => ...])`, без фільтра за статусом, з фото/кличкою/бейджем статусу, клік веде на публічну сторінку тварини), **Вихід** (`wp_logout_url()`).
- Незалогінених користувачів шорткод перенаправляє на посилання "увійти"/"зареєструйтесь" замість кабінету.
- Заповнює прогалину, позначену в `CLAUDE.md` (адмінка WP обмежена `manage_options`, тож `lapki_shelter_admin`/`lapki_volunteer` після реєстрації не мали де побачити свій профіль чи тварин) — поки що лише перегляд, без форм додавання/редагування тварини (окрема задача, `/add-animal/` в шапці теми досі нікуди не веде).
- `POST /wp-json/lapki/v1/signup` тепер редиректить на `/cabinet/` замість головної сторінки.
- В шапці теми (`header.php`) додано посилання "Кабінет" для залогінених користувачів.
- CSS для бокового меню (`.lapki-cabinet-nav`) — активний пункт підсвічується брендовим зеленим (`--lapki-btn-green`).
- Перевірено вручну (`wp eval` з підміною поточного користувача + `curl` для незалогіненого стану): обидві вкладки рендерять реальні дані (організація "Притулок Добра Лапа", 6 тварин з різними статусами), логаут-посилання з коректним nonce, гостьовий стан показує запрошення увійти/зареєструватись.

## [Unreleased] — 2026-07-11 (сесія 7)

### Фільтр міст на сторінці організацій

- **Рядок кольорових боксів-фільтрів над списком `/organizations/`** — показує всі міста, де є організації, з кількістю в кожному (`Lapki_Organization::get_cities_with_counts()`, `templates/archive-organizations.php`). Клік по боксу міста фільтрує список організацій за цим містом (`?city=`, точний збіг через уже наявний параметр `city` у `Lapki_Organization::search()`); "Всі міста" скидає фільтр.
- Візуально перевикористано стилі тегів картки тварини (`.lapki-card__tag`, кольорова палітра `--dog/--cat/--bird/--rabbit/--age/--org/--location`), додано модифікатор `.lapki-card__tag--filter` (крупніший розмір для окремого рядка фільтрів) і `.is-active` (виділення обраного міста) у `assets/css/lapki.css`.
- Реалізовано через звичайний GET-параметр + серверний рендер (сторінка організацій і так повністю PHP, без AJAX-сітки як у `/animals/`) — без нового JS.
- Перевірено вручну через `curl`: рядок міст рендериться з правильними лічильниками, фільтрація за конкретним містом (Львів) повертає лише відповідну організацію.

## [Unreleased] — 2026-07-11 (сесія 6)

### Публічна реєстрація

- **Сторінка `/signup/` + шорткод `[lapki_signup]`** — публічна форма реєстрації нового користувача з вибором типу: приватна особа, притулок, ветеринарна клініка, окремий ветеринар, волонтер (`inc/class-lapki-frontend.php` — `add_shortcode`, `templates/shortcode-signup.php`). Сторінку `signup` створено як звичайний WP `page` з цим шорткодом у контенті.
- **`POST /wp-json/lapki/v1/signup`** (публічний, `class-lapki-rest-api.php`) — валідує тип/ім'я/email/пароль (мін. 6 символів), перевіряє унікальність email, створює WP-користувача (`wp_insert_user`, `user_login` генерується з локальної частини email з дедуплікацією) + `Lapki_Organization` (`wp_user_id` → новий користувач; поле `type` = обраний тип реєстрації), автологін (`wp_set_auth_cookie`). Притулок/ветклініка/ветеринар отримують роль `lapki_shelter_admin`, приватна особа/волонтер — `lapki_volunteer` (перевикористано наявні ролі з `class-lapki-roles.php`, нових capability не додавалось). Організація створюється для всіх типів, бо `wp_lapki_animals.organization_id` — обов'язковий зв'язок для будь-якої публікації тварини.
- **JS** (`assets/js/lapki.js` — `initSignupForm()`) — та сама модель, що й форма заявки на усиновлення: `fetch` на REST endpoint, показ/приховування поля "Назва організації" залежно від типу, alert-повідомлення, редирект на головну після успіху.
- Перевірено вручну через `curl`/`wp-cli`: успішна реєстрація (притулок + приватна особа), дублікат email → 409, невідомий тип → 400, короткий пароль → 400; тестових користувачів/організації видалено після перевірки.

## [Unreleased] — 2026-07-09 (сесія 5)

### Тестові дані

- **Додано 20 собак і 20 котів** у продакшн БД (`lapki_test` тут не чіпалась — дані пішли в живу `lapki` на `lapki.esiteq.com`, прив'язані до демо-організації `Притулок Lapki`, id=1). Кожна тварина має рандомізовані атрибути (порода, вік, стать, розмір, тип шерсті, колір, статус, сумісність з дітьми/собаками/котами, стерилізація тощо — значення взяті з реального довідника `wp_lapki_attributes`, щоб коректно відображались у формах/фільтрах) і рандомне українське місто з координатами (для перевірки геопошуку).
- **Обов'язкове фото для кожної тварини** — реальні зображення завантажено з `placedog.net` (собаки) і `cataas.com` (коти) через `wp_remote_get()`, збережено в `wp-content/uploads/lapki/images/` з мініатюрами 300×300, записи `wp_lapki_media` (`is_primary=1`). Усі 41 тварина в базі (40 нових + попередня демо-тварина) мають головне фото.
- Скрипт (`wp eval-file`, одноразовий, не закомічений у репозиторій) використовував існуючі публічні методи (`Lapki_Animal::create()`, `Lapki_Media::create()`, `Lapki_Main::generate_filename()/create_thumbnail()`) — без прямих SQL-обходів моделі.

## [Unreleased] — 2026-07-09 (сесія 4)

### Тестування

- **Закрито пробіл у тест-сьюті, зафіксований у сесії 3**: додано тести для `Lapki_Tag`, `Lapki_Attributes` і REST endpoints media/attributes/applications.
- **`tests/Test_Lapki_Tag.php`** (5 тестів) — `Lapki_Tag` не має власного `create()` (функціонал тегів у плагіні ще без writer'а), тому тести засівають `wp_lapki_tags` напряму через `$wpdb->insert()` і перевіряють `get_by_entity()`/`delete_by_entity()`: повернення тегів, порожній масив за відсутності тегів, ізоляція між різними сутностями (видалення/вибірка тегів однієї тварини не чіпає теги іншої).
- **`tests/Test_Lapki_Attributes.php`** (13 тестів) — `create()`/`get()`/`get_all()` (фільтри `attr_name`/`lang`/`search`, пагінація limit+offset)/`count()`/`update()`/`delete()`, а також довідникові методи `get_global_attributes()`, `get_animal_types()`, `get_breeds_by_type()`, `get_type_attributes()` (об'єднання type-специфічних і глобальних (`entity_type = 'all'`) атрибутів). Усі створювані в тестах значення атрибутів мають випадковий суфікс (`wp_generate_password()`), щоб не зіткнутися з `UNIQUE KEY unique_attr` і не змішатися з 965-рядковим seed-довідником, який (як і демо-дані) живе в `lapki_test` поза транзакціями окремих тестів.
- **Розширено `tests/Test_Lapki_Rest_Api.php`** (+22 тести):
  - Media: `POST /animals/{id}/media` (403 без capability), `DELETE /media/{id}` і `PUT /media/{id}/primary` (403 для не-власника організації тварини, 200 для власника). Сам факт завантаження файлу (`upload_animal_media`) не тестувався через REST — `move_uploaded_file()` в контролері вимагає справжнього HTTP-завантаження і завжди повертає `false` поза реальним запитом; перевірено лише межу авторизації.
  - Attributes (глобальний довідник): `GET` публічний, `POST`/`PUT`/`DELETE` — 403 для `lapki_shelter_admin` (немає `CAP_MANAGE_ATTRIBUTES`, яка видається лише `administrator`), 200/201 для адміністратора сайту.
  - Applications: `POST` публічний для анонімів (валідний і невалідний email), `GET` — 401 для анонімів, авто-скоуп на організацію власника (перевірено ізоляцію між двома організаціями), `PUT` — 403 для чужого власника, 200 для власника (зміна статусу).
- **Разом: 87 тестів, 199 assertions, всі проходять** (`vendor/bin/phpunit`), включно з попередніми 53 тестами з сесії 3.

## [Unreleased] — 2026-07-08 (сесія 3)

### Тестування

- **Тестова БД `lapki_test`** отримана й підключена (окремий MySQL-користувач `lapki_test`, `ALL PRIVILEGES` лише на цю БД) — знімає блокер, описаний у сесії 2 (`.doc/todo.md`, п.12).
- **PHPUnit-оточення розгорнуто**: `composer install` (PHPUnit + `wp-phpunit/wp-phpunit` + Yoast Polyfills), WP core і тестовий фреймворк встановлено вручну через `git sparse-checkout` (сервер не має `svn`, який вимагає штатний `bin/install-wp-tests.sh`) у стандартні шляхи `/tmp/wordpress` і `/tmp/wordpress-tests-lib` (дефолт `tests/bootstrap.php`, якщо `WP_TESTS_DIR` не задано).
- **Виправлено `phpunit.xml.dist`**: під PHPUnit 10 `<directory prefix="test-" suffix=".php">` ламає пошук тестів — `PHPUnit\Runner\TestSuiteLoader` вимагає, щоб ім'я класу (без урахування регістру) закінчувалося на ім'я файлу без розширення, а дефіси в назві файлу роблять це неможливим для валідного PHP-ідентифікатора (тому й `test-sample.php` був раніше виключений — той самий баг, обійдений виключенням, а не виправленням). Замінено на `<directory suffix=".php">` + явні `<exclude>` для `bootstrap.php` і `test-sample.php`.
- **Зафіксовано `phpunit/phpunit` на `^9.6`** (`composer.json`) — WP-тестовий фреймворк (`abstract-testcase.php::expectDeprecated()`) використовує `PHPUnit\Util\Test::parseTestMethodAnnotations()`, прибраний у PHPUnit 10; `wp-phpunit/wp-phpunit` 6.9.4 ще не сумісний з PHPUnit 10 у цій частині.
- **Нові тести** — `tests/Test_Lapki_Animal.php` (13 тестів, 30 assertions, усі проходять): `create()` (валідний id, дефолти, явні перевизначення), `get()` (join з організацією, media/tags як порожні масиви, `null` для неіснуючого id), `search()` (фільтр за типом/статусом/іменем/булевим атрибутом/organization_id, пагінація limit+offset, ключ `primary_photo`).
- **Виявлено і враховано**: демо-дані (`Демо тварина`/`Демо притулок Lapki`), які `Lapki_Migrations` засіює лише на порожніх таблицях, потрапляють у БД під час першого бутстрапу WP-тестового фреймворку — **поза** транзакцією конкретного тесту, тож `ROLLBACK` після кожного тесту (WP_UnitTestCase) їх не прибирає, і вони лишаються назавжди в `lapki_test`. Тести, чутливі до точної кількості записів, тепер явно фільтрують за `organization_id` тестового прогону, щоб не залежати від цього стану.
- **Виявлено**: `wp_user_id` в `lapki_organizations` має `UNIQUE KEY unique_wp_user`, а `Lapki_Organization::create()` не підставляє значення за замовчуванням — створення двох організацій без явного `wp_user_id` в одній транзакції валить `INSERT` через дублікат `0`. Не баг (організація за задумом завжди прив'язана до реального WP-користувача), але тести тепер явно передають унікальний `wp_user_id` через `self::factory()->user->create()`.
- **Розширено тест-сьют** — `tests/Test_Lapki_Organization.php` (13 тестів): `create()`/`get()`/`update()`/`delete()`, унікальність `wp_user_id`, `search()` (name/type/verified_only), `get_by_wp_user_id()`, `belongs_to_user()`. `tests/Test_Lapki_Media.php` (13 тестів): `create()` з валідацією обов'язкових полів, дефолти, `get()`/`get_by_entity()` (фільтр `is_active`, сортування primary-first), логіка "тільки одне головне фото" (`create()` з `is_primary`, `set_primary_photo()`), `get_primary_photo()`, `delete()`/`delete_by_entity()`. `tests/Test_Lapki_Rest_Api.php` (14 тестів, через `WP_Test_REST_TestCase` + `WP_REST_Server`): авторизація write-endpoints для `/animals` і `/organizations` — анонім → 401, залогінений без capability → 403, власник (`lapki_shelter_admin`) → дозволено, чужа організація → 403, адміністратор сайту → дозволено незалежно від власності, `lapki_volunteer` (нема `CAP_MANAGE_ORGANIZATIONS`) → 403 на організаціях; `GET`-endpoints лишаються публічними навіть для анонімів. Разом: **53 тести, 120 assertions, всі проходять**.

## [Unreleased] — 2026-07-07 (сесія 2)

### Безпека

- **Авторизація REST API** (`class-lapki-rest-api.php`) — усі write-endpoints (`POST`/`PUT`/`DELETE` для animals, organizations, media, attributes, applications) тепер вимагають capability замість `__return_true`. `GET` лишився публічним (основна функція платформи — публічний пошук тварин).
- **Ролі й capabilities** (новий `inc/class-lapki-roles.php`) — `lapki_manage_animals`, `lapki_manage_organizations`, `lapki_manage_attributes`; ролі `lapki_shelter_admin` і `lapki_volunteer`; адміністратор сайту отримує всі три автоматично.
- **Перевірка власності** — редагувати/видаляти тварину чи медіа можна тільки якщо організація належить поточному користувачу (`Lapki_Roles::user_owns_organization()`) або якщо це адміністратор сайту.
- **`class-eq-form.php`** — розкоментовано nonce (`verify_nonce()`) і capability-перевірку в `save()`.

### Додано

- **Автоматичні міграції БД** (новий `inc/class-lapki-migrations.php`) — `dbDelta()` створює всі таблиці плагіна (включно з новою `wp_lapki_applications`) при активації і автоматично для вже активних сайтів (version-gate на `plugins_loaded`, без потреби деактивувати/активувати плагін).
- **Seed-дані** — довідник атрибутів (965 рядків uk/en для dog/cat/bird/rabbit) забандлено в `inc/data/seed-attributes.sql`, завантажується тільки на порожній таблиці; демо-організація і демо-тварина створюються так само для нових інсталяцій.
- **Локатор шаблонів** (`inc/class-lapki-template-loader.php`) — аналог `wc_get_template()`: тема (`themes/{active}/lapki/{файл}`) → плагін (`templates/{файл}`).
- **Публічний фронтенд** (`inc/class-lapki-frontend.php` + `templates/*.php`) — нові сторінки: архів/пошук тварин (`/animals/`), сторінка тварини (`/animals/{id}/`), список організацій (`/organizations/`), сторінка організації (`/organizations/{id}/`). Тему `assets/js/lapki.js` виправлено під реальну форму відповіді REST API (`response.data`, `primary_photo.thumbnail_url`, `organization_name`), додано фільтри+пагінацію архіву та відкриття фотогалереї.
- **Заявка на усиновлення** — нова таблиця `wp_lapki_applications`, модель `Lapki_Application`, `POST /lapki/v1/applications` (публічний, як контактна форма), `GET`/`PUT /applications/{id}` (власник організації/адмін). Email-нотифікація організації і лист-підтвердження заявнику через `wp_mail()`. Форма інтегрована в модалку на сторінці тварини.
- **Адмінка «Організації»** — повноцінна сторінка (таблиця + модалка створення/редагування) замість заглушки; додано `Lapki_Organization::update()`/`delete()` і `PUT`/`DELETE /organizations/{id}` в REST API.
- **Сторінка налаштувань плагіна** (`Lapki → Налаштування`) — дефолтна дистанція геопошуку, розмір сторінки, email для нотифікацій; підключено до REST API (`get_animals`) і до email-логіки заявок.
- **Локалізація** — згенеровано `languages/lapki.pot`, скомпільовано `lapki-uk.mo` (passthrough) і `lapki-en_US.mo` (реальний переклад ~40 рядків: меню, помилки REST API, сторінка налаштувань). Перевірено: `__('Тварини','lapki')` → `Animals` при локалі сайту `en_US`. Повне покриття рядків (адмін-HTML, шаблони, JS) — не входило в обсяг цієї сесії.

### Відкладено

- **PHPUnit тести** — за рішенням користувача. Потрібна окрема тестова БД (користувач MySQL `lapki` має права лише на БД `lapki`, а WP-тестовий бутстрап небезпечно запускати на живій базі). Скелет (`composer.json`, `phpunit.xml.dist`, `tests/bootstrap.php`, `bin/install-wp-tests.sh`) залишено готовим — після надання доступу до `lapki_test` можна одразу `composer install && vendor/bin/phpunit`.

### Документація

- Оновлено `CLAUDE.md` і `.claude/SNAPSHOT.md` до фактичного стану коду (документація датувалась 2025-10-08 і не відображала прогрес з `CHANGELOG.md`):
  - Структура файлів: реальний вміст `css/`, `js/`, теки `.doc/`, `.claude/`, тема `themes/lapki/`, scaffold `server/`
  - Адмін-меню: додано форму тварини, редактор атрибутів; зафіксовано, що сторінка «Організації» досі заглушка
  - Roadmap/TODO: позначено виконані пункти (форма тварини, upload фото, редактор атрибутів, головна сторінка теми), додано пункт про відсутній `dbDelta()` при активації та нереалізований локатор шаблонів
  - Git status: замінено застарілий список на актуальний (гілка `master`, репозиторій — `wp-content/plugins/.git`)
- Створено `.doc/todo.md` — пріоритезований список наступних дій (безпека, БД-міграції, фронтенд, адмінка, локалізація, тести)

## [Unreleased] — 2026-07-07

### Документація

- Оновлено `CLAUDE.md` і `.claude/SNAPSHOT.md` до фактичного стану коду (документація датувалась 2025-10-08 і не відображала прогрес з `CHANGELOG.md`):
  - Структура файлів: реальний вміст `css/`, `js/`, теки `.doc/`, `.claude/`, тема `themes/lapki/`, scaffold `server/`
  - Адмін-меню: додано форму тварини, редактор атрибутів; зафіксовано, що сторінка «Організації» досі заглушка
  - Roadmap/TODO: позначено виконані пункти (форма тварини, upload фото, редактор атрибутів, головна сторінка теми), додано пункт про відсутній `dbDelta()` при активації та нереалізований локатор шаблонів
  - Git status: замінено застарілий список на актуальний (гілка `master`, репозиторій — `wp-content/plugins/.git`)
- Створено `.doc/todo.md` — пріоритезований список наступних дій (безпека, БД-міграції, фронтенд, адмінка, локалізація, тести)

## [Unreleased] — 2026-05-12

### Додано

- **Тека для перевизначення шаблонів плагіна в темі** (`wp-content/themes/lapki/lapki/`)
  - Створено теку `themes/lapki/lapki/` за прикладом WooCommerce (`themes/{theme}/woocommerce/`)
  - Призначення: тут тема перевизначає шаблони плагіна Lapki (тварини, організації, пошук тощо)
  - Додано `index.php` для захисту від directory listing
  - **Наступний крок:** реалізувати в плагіні функцію-локатор шаблонів (аналог `wc_get_template()`), яка спочатку шукає шаблон у темі (`themes/{active}/lapki/`), а якщо не знайде — підвантажує дефолтний з плагіна (`plugins/lapki/templates/`)

## [Unreleased] — 2026-04-15

### Додано

- **Тема WordPress** (`wp-content/themes/lapki/`)
  - Bootstrap 5 через CDN, кольори `#39ca36` / `#ff5e00`
  - `functions.php` — enqueue Bootstrap + lapki.css/js, `LapkiData` локалізація
  - `header.php` — navbar з логотипом, навігацією, кнопкою CTA
  - `footer.php` — темний футер з 4 колонками
  - `front-page.php` — hero, пошукова форма, статистика, грід тварин, CTA
  - `assets/css/lapki.css` — повний custom CSS (CSS змінні, картки, скелетон, чіпи)
  - `assets/js/lapki.js` — завантаження статистики, грід тварин, live-фільтрація

- **Редактор атрибутів** (`class-lapki-admin.php`, `class-lapki-rest-api.php`, `class-lapki-models.php`, `js/lapki-admin.js`, `css/lapki-admin.css`)
  - Нова сторінка `Lapki → Атрибути` в адмін-панелі
  - Фільтри: мова, entity, entity_type, attr_name, текстовий пошук
  - Таблиця зі всіма атрибутами + пагінація
  - Модальне вікно для додавання та редагування атрибутів
  - Видалення атрибутів з підтвердженням
  - REST API endpoints: `GET/POST /lapki/v1/attributes`, `PUT/DELETE /lapki/v1/attributes/{id}`
  - `Lapki_Attributes::get()`, `get_all()`, `count()`, `create()`, `update()`, `delete()` в моделі

---

## [Unreleased] — 2026-03-24

### Виправлено — критичні баги

- **Подвійне видалення файлів** (`class-lapki-rest-api.php`)
  `Lapki_REST_API::delete_media()` видаляв файли вручну через `unlink`, а потім викликав `Lapki_Media::delete()` який робив те саме. Прибрано ручне видалення — тепер тільки `Lapki_Media::delete()`.

- **Авторизація на media endpoints** (`class-lapki-rest-api.php`)
  `POST /animals/{id}/media`, `DELETE /media/{id}`, `PUT /media/{id}/primary` мали `current_user_can('manage_options')` замість `__return_true` як решта endpoints. Завантаження і видалення фото не працювало без авторизованої сесії. Виправлено на `__return_true` (тимчасово, до впровадження повноцінної авторизації).

### Виправлено — жовті баги

- **Пошук тварин в адмінці не працював** (`class-lapki-rest-api.php`, `class-lapki-models.php`, `js/lapki-admin.js`)
  JS надсилав `&search=term`, але REST API ігнорував параметр. Додано `search` до `get_animals_search_args()`, `get_animals()`, `Lapki_Animal::search()` (WHERE `a.name LIKE %s` через `$wpdb->esc_like()`).

- **Список тварин в адмінці показував тільки `adoptable`** (`class-lapki-rest-api.php`, `class-lapki-models.php`)
  `GET /animals` дефолтив `status = 'adoptable'`. Адмін не міг бачити всі тварини. Дефолт змінено на `''` (порожньо = без фільтру по статусу) і в REST API, і в `Lapki_Animal::search()`.

- **Endpoint `/types/all` не існував явно** (`class-lapki-rest-api.php`, `class-lapki-models.php`)
  JS викликав `/types/all?lang=uk` для заповнення age/gender/size/coat селектів і кешу перекладів. Запит потрапляв під загальний маршрут `/types/{type}` і міг повертати 404 або порожній результат. Додано явний маршрут `GET /types/all` (зареєстрований перед `/types/{type}`), новий метод `Lapki_REST_API::get_all_type_attributes()` і `Lapki_Attributes::get_global_attributes()` (запит по `entity_type = 'all'`).

- **Статус у формі залежав від атрибутів в БД** (`class-lapki-admin.php`, `js/lapki-admin.js`)
  Поле "Статус" у формі тварини заповнювалось через `/types/all` → `attributes.status`. Якщо в БД не було відповідних записів — dropdown був порожнім. Статуси захардкоджено в HTML формі (`adoptable`, `adopted`, `hold`, `found`). З JS прибрано код завантаження статусу через API.

- **Пагінація в JS не була реалізована** (`js/lapki-admin.js`, `class-lapki-admin.php`)
  `renderPagination()` містив тільки `console.log`. Реалізовано рендер: кнопки ← / →, лічильник `сторінка / всього`, загальна кількість записів. Додано `<div id="lapki-pagination">` в HTML адмінки.

### Покращено

- **Ефективний підрахунок для пагінації** (`class-lapki-rest-api.php`, `class-lapki-models.php`)
  Раніше для підрахунку загальної кількості виконувався другий `SELECT *` з `LIMIT 999999` і `count()` в PHP. Замінено на окремий метод `Lapki_Animal::count()` що виконує `SELECT COUNT(*)`.

### Документація

- Додано секцію `## ✅ TODO` в `CLAUDE.md` з описом всіх знайдених багів, причин і способів виправлення.
- Позначено виправлені пункти як `[x]` в `CLAUDE.md`.
- Створено `CHANGELOG.md`.
