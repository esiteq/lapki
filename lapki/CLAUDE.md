# CLAUDE.md - Lapki WordPress Plugin

## 📋 Огляд проєкту

**Lapki** - це WordPress плагін для пошуку та прилаштування домашніх тварин, інспірований petfinder.com та адаптований для України. Платформа дозволяє притулкам, організаціям та приватним особам публікувати інформацію про тварин, що шукають дім.

### Основна інформація
- **Версія**: 2.0.0
- **Автор**: Oleksii Bugrov (esiteq.com)
- **WordPress**: 5.0+
- **PHP**: 7.4+
- **Ліцензія**: GPL v2 or later
- **Text Domain**: lapki

---

## 🏗️ Архітектура проєкту

### Структура файлів
```
lapki/
├── lapki.php                          # Головний файл плагіна
├── inc/                               # Класи та функціонал
│   ├── class-lapki-models.php         # Моделі: Animal, Organization, Media, Attributes, Tag, Application
│   ├── class-lapki-rest-api.php       # REST API endpoints
│   ├── class-lapki-admin.php          # Адмін панель (тварини, організації, атрибути, налаштування)
│   ├── class-lapki-roles.php          # Ролі та capabilities
│   ├── class-lapki-migrations.php     # dbDelta() міграції + seed-дані
│   ├── class-lapki-template-loader.php # Локатор шаблонів (тема → плагін)
│   ├── class-lapki-frontend.php       # Rewrite rules і роутинг фронтенду
│   ├── class-eq-form.php              # Універсальний конструктор форм
│   ├── data/seed-attributes.sql       # Забандлений довідник атрибутів (965 рядків uk/en)
│   └── index.php                      # Захист директорії
├── templates/                         # Публічні шаблони (дефолти; тема може перевизначити)
│   ├── archive-animals.php            # /animals/ — пошук з фільтрами
│   ├── single-animal.php              # /animals/{id}/ — картка тварини + форма заявки
│   ├── archive-organizations.php      # /organizations/ — + рядок фільтрів-боксів по містах
│   ├── single-organization.php        # /organizations/{id}/
│   ├── shortcode-signup.php           # [lapki_signup] — публічна реєстрація (/signup/)
│   ├── shortcode-cabinet.php          # [lapki_cabinet] — кабінет користувача (/cabinet/)
│   ├── widget-demo.php                # /widget-demo/ — тестова сторінка для js/animals.js (не в меню)
│   └── admin/                         # Порожньо (адмінка рендериться з class-lapki-admin.php)
├── css/lapki-admin.css                # Стилі адмін-панелі
├── js/lapki-admin.js                  # JS адмін-панелі (AJAX, форми, пагінація, організації)
├── languages/                         # lapki.pot, lapki-uk.po/mo, lapki-en_US.po/mo
├── tests/, bin/, phpunit.xml.dist, composer.json  # Скелет PHPUnit (тести не запускались — потрібна тестова БД)
├── .doc/                              # SQL-довідники, todo.md
├── .claude/SNAPSHOT.md                # Стан між сесіями
├── CLAUDE.md                          # Ця документація
└── CHANGELOG.md                       # Лог змін

wp-content/themes/lapki/               # Публічна тема (Bootstrap 5)
├── front-page.php, header.php, footer.php, 404.php, page.php
├── assets/css/lapki.css, assets/js/lapki.js  # Виправлено під реальну форму відповіді REST API;
│                                              # тут же стилі/JS кабінету, форми реєстрації, фільтра міст
└── lapki/                             # Тека перевизначення шаблонів плагіна (WooCommerce-подібний патерн)

/var/www/lapki/js/animals.js           # Статичний embed-віджет карток тварин для СТОРОННІХ сайтів/блогів
                                        # (поза WordPress, лежить у корені сайту — Apache віддає напряму,
                                        # без завантаження wp-load.php; div. розділ «Embed-віджет» нижче)

server/                                # Node.js scaffold для Етапу 2 — НЕ чіпати до завершення Етапу 1
```

---

## 🗄️ Структура бази даних

### Таблиці

#### 1. `wp_lapki_animals` - Тварини
Основна таблиця для зберігання інформації про тварин.

**✅ 100% сумісність з Petfinder.com API**

**Ключові поля:**
- `id` (int) - PRIMARY KEY
- `organization_id` (int) - FK до wp_lapki_organizations
- `name` (varchar) - Кличка тварини
- `type` (varchar) - Тип тварини (dog, cat, bird, etc.)
- `species` (varchar) - Вид тварини
- `breed_primary` (varchar) - Основна порода
- `breed_secondary` (varchar) - Додаткова порода (для метисів)
- `breed_mixed` (boolean) - Чи є метисом
- `breed_unknown` (boolean) - Порода невідома
- `color_primary`, `color_secondary`, `color_tertiary` (varchar) - Кольори
- `age` (varchar) - Вік: baby, young, adult, senior
- `gender` (varchar) - Стать: male, female, unknown
- `size` (varchar) - Розмір: small, medium, large, xlarge
- `coat` (varchar) - Тип шерсті: short, medium, long, hairless
- `status` (varchar) - Статус: adoptable, adopted, hold, found
- `description` (text) - Опис тварини
- `spayed_neutered`, `house_trained`, `declawed`, `special_needs`, `shots_current` (boolean) - Атрибути здоров'я
- `good_with_children`, `good_with_dogs`, `good_with_cats` (boolean) - Сумісність
- `contact_email`, `contact_phone` (varchar) - Контакти
- `address1`, `address2` (varchar) - Вулиця і додаткова адреса ⭐ NEW
- `address_city`, `address_state`, `address_postcode`, `address_country` (varchar) - Адреса
- `latitude`, `longitude` (decimal) - Координати для геопошуку
- `url` (varchar) - Посилання на Petfinder або власний сайт ⭐ NEW
- `published_at`, `created_at`, `updated_at` (datetime)

#### 2. `wp_lapki_organizations` - Організації
Притулки, організації, волонтери.

**✅ 100% сумісність з Petfinder.com API + додаткові поля**

**Ключові поля:**
- `id` (int) - PRIMARY KEY
- `wp_user_id` (int) - Зв'язок з користувачем WordPress
- `name` (varchar) - Назва організації
- `type` (varchar) - Тип: individual, shelter, rescue, vet_clinic
- `email`, `phone`, `website` (varchar) - Контакти
- `hours` (text) - Графік роботи ⭐ NEW
- `mission_statement` (text) - Місія організації ⭐ NEW
- `adoption_policy` (text) - Політика усиновлення ⭐ NEW
- `adoption_url` (varchar) - URL для подачі заявки ⭐ NEW
- `social_media` (json) - Соціальні мережі (facebook, instagram, twitter, youtube) ⭐ NEW
- `address1`, `address2`, `city`, `state`, `postcode`, `country` (varchar) - Адреса
- `latitude`, `longitude` (decimal) - Координати
- `url` (varchar) - Посилання на Petfinder профіль ⭐ NEW
- `is_verified` (boolean) - Чи верифікована
- `created_at`, `updated_at` (datetime)

#### 3. `wp_lapki_media` - Медіафайли
Зображення та відео тварин/організацій.

**Ключові поля:**
- `id` (int) - PRIMARY KEY
- `entity_type` (varchar) - animal, organization, user
- `entity_id` (int) - ID сутності
- `media_type` (varchar) - photo, video
- `filename` (varchar) - Оригінальна назва файлу
- `file_path` (varchar) - Шлях до файлу (тільки назва!)
- `width`, `height` (int) - Розміри
- `file_size` (int) - Розмір у байтах
- `is_primary` (boolean) - Головне фото
- `sort_order` (int) - Порядок відображення
- `is_active` (boolean)
- `uploaded_at`, `updated_at` (datetime)

#### 4. `wp_lapki_attributes` - Атрибути
Довідник перекладів та атрибутів.

**Структура:**
- `id` (int) - PRIMARY KEY
- `entity` (varchar) - animal, org, user
- `entity_type` (varchar) - dog, cat, bird, all
- `attr_name` (varchar) - species, breed, age, gender, size
- `attr_value` (varchar) - Значення (dog, cat, labrador)
- `attr_display` (varchar) - Відображуване значення (Собака, Кіт, Лабрадор)
- `lang` (varchar) - uk, en

**Приклад:**
```
entity: animal
entity_type: dog
attr_name: breed
attr_value: labrador
attr_display: Лабрадор
lang: uk
```

#### 5. `wp_lapki_tags` - Теги
Додаткові мітки для тварин.

#### 6. `wp_lapki_applications` - Заявки на усиновлення ⭐ NEW
- `id` (int) - PRIMARY KEY
- `animal_id`, `organization_id` (int) - FK
- `applicant_name`, `applicant_email`, `applicant_phone` (varchar)
- `message` (text)
- `status` (varchar) - new, contacted, approved, rejected
- `created_at`, `updated_at` (datetime)

**Усі таблиці тепер створюються автоматично через `dbDelta()`** (`inc/class-lapki-migrations.php`) при активації плагіна і при виявленні зміни версії схеми (`Lapki_Migrations::DB_VERSION`) — без потреби деактивувати/активувати плагін вручну.

---

## 🔧 Основні класи та функціонал

### 1. Lapki_Main (lapki.php:50-579)

Головний клас плагіна.

**Константи:**
```php
VERSION = '2.0.0'
SUPPORTED_LANGS = ['uk', 'en']
ENTITIES = ['animal', 'org', 'user']
MEDIA_BASE_DIR = 'lapki'
THUMB_WIDTH = 300
THUMB_HEIGHT = 300
```

**Ключові методи:**

#### Робота з атрибутами
- `get_attribute_display($entity, $entity_type, $attr_name, $attr_value, $lang)` - Отримати відображуване значення атрибута
- `get_attribute_options($entity, $entity_type, $attr_name, $lang)` - Отримати опції для селекта
- `add_attribute($entity, $entity_type, $attr_name, $attr_value, $attr_display, $lang)` - Додати атрибут
- `get_animal_types($lang)` - Отримати типи тварин

#### Робота з медіа
- `get_media_base_path()` - Базовий шлях: `/wp-content/uploads/lapki`
- `get_images_path()` - Шлях до зображень: `/wp-content/uploads/lapki/images`
- `get_thumbnails_path()` - Шлях до мініатюр: `/wp-content/uploads/lapki/thumbnails`
- `get_image_url($filename, $thumbnail)` - Отримати URL зображення
- `generate_filename($original_name, $animal_name)` - Генерувати унікальну назву файлу
- `create_thumbnail($filename)` - Створити мініатюру (300x300px)
- `delete_image($filename)` - Видалити зображення (оригінал + thumbnail)
- `create_media_directories()` - Створити необхідні папки

---

### 2. Моделі (class-lapki-models.php)

#### Lapki_Animal
**Методи:**
- `get($id)` - Отримати тварину за ID (з медіа та тегами)
- `search($params)` - Пошук з фільтрами та пагінацією
- `create($data)` - Створити тварину
- `update($id, $data)` - Оновити тварину
- `delete($id)` - Видалити тварину (з медіа та тегами)
- `get_stats()` - Статистика (total, adoptable, adopted, dogs, cats)

**Параметри пошуку:**
```php
[
    'type' => '',           // dog, cat
    'species' => '',
    'breed' => '',
    'age' => '',           // baby, young, adult, senior
    'gender' => '',        // male, female, unknown
    'size' => '',          // small, medium, large, xlarge
    'status' => 'adoptable',
    'location' => '',
    'distance' => 50,      // км
    'latitude' => null,
    'longitude' => null,
    'good_with_children' => null,
    'good_with_dogs' => null,
    'good_with_cats' => null,
    'spayed_neutered' => null,
    'special_needs' => null,
    'organization_id' => null,
    'limit' => 20,
    'offset' => 0,
    'order_by' => 'published_at', // name, age, distance, updated_at
    'order' => 'DESC'
]
```

**Геопошук:**
Використовується формула Haversine для обчислення відстані між координатами.

#### Lapki_Organization
- `get($id)` - Отримати організацію (з кількістю **adoptable**-тварин у `animals_count` — не плутати з повною кількістю; на `/organizations/{id}/` заголовок рахує `count($animals)` окремо, див. виправлення нижче)
- `search($params)` - Пошук організацій (підтримує `city` — точний збіг, для фільтра-боксів на `/organizations/`)
- `create($data)` - Створити організацію
- `get_cities_with_counts()` ⭐ NEW - Міста з кількістю організацій (для рядка кольорових фільтрів-боксів над списком `/organizations/`, стиль `.lapki-card__tag`)

#### Lapki_Media
- `get_by_entity($entity_type, $entity_id)` - Отримати медіафайли (з URL)
- `get_primary_photo($entity_type, $entity_id)` - Отримати головне фото
- `get_primary_photo_url($entity_type, $entity_id, $thumbnail)` - Отримати URL головного фото
- `create($data)` - Створити запис медіафайлу
- `upload_image($file, $entity_type, $entity_id, $animal_name, $is_primary)` - Завантажити зображення
- `delete($media_id)` - Видалити медіафайл (файл + запис БД)
- `set_primary_photo($media_id)` - Встановити головне фото
- `delete_by_entity($entity_type, $entity_id)` - Видалити всі медіафайли сутності

**Важливо:** `file_path` зберігає тільки назву файлу, URL генерується динамічно через `Lapki_Main::get_image_url()`

#### Lapki_Attributes
- `get_animal_types($lang)` - Типи тварин
- `get_breeds_by_type($type, $lang)` - Породи за типом
- `get_type_attributes($type, $lang)` - Всі атрибути типу

---

### 3. REST API (class-lapki-rest-api.php)

Базовий namespace: `lapki/v1`

#### Animals Endpoints

**GET** `/wp-json/lapki/v1/animals`
- Пошук тварин з фільтрами
- Параметри: type, species, breed, age, gender, size, status, location, distance, latitude, longitude, good_with_*, organization_id, limit, offset, order_by, order
- Відповідь: `{ data: [], pagination: {} }`

**GET** `/wp-json/lapki/v1/animals/{id}`
- Отримати тварину за ID
- Відповідь: об'єкт тварини з медіа та тегами

**POST** `/wp-json/lapki/v1/animals`
- Створити тварину
- Обов'язкові поля: organization_id, name, type, species, age, gender, size

**PUT** `/wp-json/lapki/v1/animals/{id}`
- Оновити тварину

**DELETE** `/wp-json/lapki/v1/animals/{id}`
- Видалити тварину

#### Types Endpoints

**GET** `/wp-json/lapki/v1/types?lang=uk`
- Отримати всі типи тварин

**GET** `/wp-json/lapki/v1/types/{type}?lang=uk`
- Отримати атрибути типу (породи, віки, розміри тощо)

**GET** `/wp-json/lapki/v1/types/{type}/breeds?lang=uk`
- Отримати породи конкретного типу

#### Organizations Endpoints

**GET** `/wp-json/lapki/v1/organizations`
- Пошук організацій
- Параметри: name, type, location, state, city, verified_only, limit, offset

**GET** `/wp-json/lapki/v1/organizations/{id}`
- Отримати організацію за ID

**POST** `/wp-json/lapki/v1/organizations`
- Створити організацію

**PUT** `/wp-json/lapki/v1/organizations/{id}`
- Оновити організацію (власник або адмін)

**DELETE** `/wp-json/lapki/v1/organizations/{id}`
- Видалити організацію (власник або адмін)

#### Applications Endpoints (заявки на усиновлення) ⭐ NEW

**POST** `/wp-json/lapki/v1/applications`
- Публічний (без авторизації, як контактна форма). Обов'язкові поля: animal_id, applicant_name, applicant_email
- Надсилає email організації + лист-підтвердження заявнику через `wp_mail()`

**GET** `/wp-json/lapki/v1/applications?organization_id=`
- Власник організації (авто-скоуп на свою організацію) або адмін (може передати будь-який organization_id)

**PUT** `/wp-json/lapki/v1/applications/{id}`
- Змінити статус (new/contacted/approved/rejected) — власник організації або адмін

#### Signup Endpoint (публічна реєстрація) ⭐ NEW

**POST** `/wp-json/lapki/v1/signup`
- Публічний. Обов'язкові поля: `user_type` (`individual`/`shelter`/`vet_clinic`/`vet`/`volunteer`), `name`, `email`, `password` (мін. 6 символів)
- Валідує тип/email/пароль, перевіряє унікальність email (`email_exists()`), генерує `user_login` з локальної частини email (дедуплікація через `username_exists()`)
- Створює WP-користувача (`wp_insert_user`) + `Lapki_Organization` (`wp_user_id` → новий користувач, `type` = обраний тип), зберігає `lapki_user_type`/`lapki_phone` як user meta
- Роль: `shelter`/`vet_clinic`/`vet` → `lapki_shelter_admin`; `individual`/`volunteer` → `lapki_volunteer` (організація створюється для **всіх** типів — `organization_id` обов'язковий для будь-якої тварини)
- Автологін (`wp_set_auth_cookie`), відповідь містить `redirect` → `/cabinet/`

#### Stats Endpoint

**GET** `/wp-json/lapki/v1/stats`
- Статистика тварин (загальна кількість, доступні, прилаштовані, собаки, коти)

**🔒 Авторизація:** `GET`-endpoints лишаються публічними (це основна функція платформи — публічний пошук, як на petfinder.com). Всі write-операції (`POST`/`PUT`/`DELETE`) вимагають capability (`lapki_manage_animals` / `lapki_manage_organizations` / `lapki_manage_attributes`) і, де застосовно, перевірку власності організації — див. розділ «Безпека».

**🌐 CORS:** `GET /animals` і `GET /organizations` (+ `/organizations/{id}`) відповідають з `Access-Control-Allow-Origin: *` (`Lapki_REST_API::allow_embed_cors()`, хук `rest_pre_serve_request`, пріоритет 20 — перекриває дефолтний `rest_send_cors_headers()` WP, який обмежує CORS тим самим origin). Потрібно для embed-віджета `js/animals.js`, що виконує `fetch()` зі сторонніх доменів. Інші (write) endpoints лишаються з дефолтною CORS-поведінкою WordPress.

---

### 4. Адмін панель (class-lapki-admin.php)

**Меню в адмінці:**
- `Lapki → Тварини` (список, `WP_List_Table`)
- `Lapki → Додати тварину` (форма створення/редагування — готова: Leaflet карта для координат, Dropzone для фото)
- `Lapki → Організації` (таблиця + модалка створення/редагування — аналогічно атрибутам)
- `Lapki → Атрибути` (CRUD-редактор атрибутів/перекладів: фільтри, таблиця, модалка, пагінація)
- `Lapki → Налаштування` (дефолтна дистанція пошуку, розмір сторінки, email для нотифікацій — Settings API)

#### Lapki_Animals_List_Table

Таблиця тварин в адмінці з використанням `WP_List_Table`.

**Колонки:**
- Фото (мініатюра 60x60px)
- Кличка (з діями: Переглянути, Редагувати, Видалити)
- Тип/Вид
- Порода (з підтримкою метисів)
- Вік/Стать
- Статус (з кольоровими бейджами)
- Організація
- Дата додавання

**Функціонал:**
- Пагінація (20 на сторінку)
- Пошук
- Сортування за колонками
- Масові дії (видалення)

---

### 5. Конструктор форм (class-eq-form.php)

**EQ_Form** - універсальний конструктор форм для WordPress.

#### Режими збереження
```php
MODE_OPTIONS   // WordPress options
MODE_POST      // WordPress post
MODE_POSTMETA  // Post meta
MODE_TABLE     // Кастомна таблиця
MODE_MIXED     // Комбінований режим
```

#### Типи полів
- `text`, `textarea`, `email`, `url`, `number`, `password`, `date`
- `select`, `radio-group`, `checkbox-group`, `checkbox`
- `file`
- `table` (вкладена таблиця з динамічними рядками)
- `section_title` (розділювач)
- `hidden`

#### Валідація
- `required` - обов'язкове поле
- `min_length`, `max_length` - довжина
- `pattern` - регулярний вираз
- `callback` - власна функція

**Приклад використання:**
```php
// Форма для кастомної таблиці
$form = EQ_Form_Builder::table('lapki_animals', 123);

$form->add_section('Основна інформація', [
    [
        'id' => 'name',
        'label' => 'Кличка',
        'type' => 'text',
        'required' => true
    ],
    [
        'id' => 'type',
        'label' => 'Тип',
        'type' => 'select',
        'values' => Lapki_Main::get_animal_types('uk'),
        'required' => true
    ]
]);

$form->display();
```

**Змішаний режим:**
```php
$form = EQ_Form_Builder::mixed(['post_id' => 123, 'record_id' => 456]);
$form->add_field(['id' => 'title', 'save_to' => 'post:post_title']);
$form->add_field(['id' => 'meta_key', 'save_to' => 'meta:my_meta']);
$form->add_field(['id' => 'table_field', 'save_to' => 'table:custom_field']);
```

---

### 6. Ролі та capabilities (class-lapki-roles.php) ⭐ NEW

**Capabilities:** `lapki_manage_animals`, `lapki_manage_organizations`, `lapki_manage_attributes` (останнє — лише адміністратор сайту).

**Ролі:** `lapki_shelter_admin` (притулок/ветклініка/ветеринар: тварини + організація), `lapki_volunteer` (приватна особа/волонтер: тільки тварини). Адміністратор сайту отримує всі три capability автоматично при активації/апгрейді. Мапінг типу реєстрації → роль — див. `Lapki_REST_API::get_signup_types()`.

`Lapki_Roles::user_owns_organization($organization_id, $wp_user_id)` — перевірка власності (або `manage_options`), використовується в permission callbacks REST API.

### 7. Локатор шаблонів (class-lapki-template-loader.php) ⭐ NEW

Аналог `wc_get_template()`: `Lapki_Template_Loader::locate('single-animal.php')` спочатку шукає `wp-content/themes/{активна тема}/lapki/single-animal.php`, і лише якщо не знайшов — підвантажує `plugins/lapki/templates/single-animal.php`.

### 8. Фронтенд-роутинг і шорткоди (class-lapki-frontend.php) ⭐ NEW

**Rewrite rules:** `/animals/`, `/animals/{id}/`, `/organizations/`, `/organizations/{id}/`, `/widget-demo/` → відповідні шаблони через `template_include`. Після зміни rewrite-правил потрібен `wp rewrite flush` (виконується автоматично при активації плагіна; при ручному додаванні нового правила — виконати вручну одноразово).

**Шорткоди:**
- `[lapki_signup]` (`render_signup_shortcode()` → `templates/shortcode-signup.php`) — публічна форма реєстрації, розміщена на сторінці `/signup/`. Поля: тип (individual/shelter/vet_clinic/vet/volunteer), ім'я, назва організації (тільки для непри­ватних типів), email, пароль, телефон, місто. Сабміт іде через JS (`assets/js/lapki.js::initSignupForm()`) на `POST /wp-json/lapki/v1/signup`. **Тимчасово (на прохання)** форма показується навіть залогіненим користувачам (з попередженням, що реєстрація нового акаунта увійде під новим користувачем) — позначено коментарем `ТИМЧАСОВО` в шаблоні для легкого відкату.
- `[lapki_cabinet]` (`render_cabinet_shortcode()` → `templates/shortcode-cabinet.php`) — особистий кабінет залогіненого користувача на сторінці `/cabinet/`, бокове меню:
  - **Головна** (`?tab=home`, дефолт) — ім'я/email/телефон/тип користувача + картка організації (назва, тип, місто, контакти, бейдж верифікації, посилання на публічну сторінку)
  - **Мої тварини** (`?tab=animals`) — усі тварини організації користувача (`Lapki_Animal::search(['organization_id' => ...])`, без фільтра статусу), фото + бейдж статусу, клік → публічна сторінка тварини. **Лише перегляд** — форми додавання/редагування тварини з кабінету ще немає (окрема задача, див. Roadmap)
  - **Вихід** — `wp_logout_url()`
  - Незалогінених користувачів шорткод перенаправляє на посилання увійти/зареєструватись замість кабінету
  - Посилання «Кабінет» у шапці теми (`header.php`) показується лише залогіненим; `POST /signup` після успіху редиректить сюди

**`/widget-demo/`** (`templates/widget-demo.php`) — тестова сторінка для embed-віджета `js/animals.js` (код для вставки + живий приклад у імітованому «чужому» блоці). **Навмисно не в меню.**

### 9. Embed-віджет тварин притулку (`/var/www/lapki/js/animals.js`) ⭐ NEW

Самодостатній vanilla JS-файл (без залежностей) для вставки на **сторонній** сайт/блог:

```html
<script src="https://lapki.help/js/animals.js?organization_id=1"></script>
```

- Лежить у **корені сайту** (`/var/www/lapki/js/`), а не в темі/плагіні — WP-рерайт (`RewriteCond %{REQUEST_FILENAME} !-f`) віддає його напряму через Apache, без завантаження `wp-load.php`
- Параметри рядка запиту власного `<script src>`: `organization_id` (обов'язковий), `limit` (дефолт 24), `status` (дефолт `adoptable`) — читає через `document.currentScript` (з fallback-пошуком по `<script>` тегах, якщо `currentScript` недоступний)
- Вставляє контейнер одразу після свого `<script>` тега, тягне дані з `GET /animals` + `GET /organizations/{id}` (публічного REST API), рендерить сітку карток (фото/кличка/тип/вік/місто) на всю ширину контейнера-батька (CSS Grid `auto-fill`)
- Стилі ін'єктує сам у `<head>` один раз (перевірка на `#lapki-embed-styles`) під власними класами `lapki-embed-*` — **не залежить від CSS/Bootstrap хост-сайту**
- Потребує CORS-виключення на бекенді (`allow_embed_cors()`, див. вище) — без нього fetch з чужого домену блокується браузером
- **НЕ під git-контролем** — репозиторій плагіна (`wp-content/plugins/.git`) не покриває корінь сайту; зміни в цьому файлі не потрапляють у пуші на GitHub (аналогічно до теми, див. «Git status» нижче)

---

## 🚀 Використання

### Приклади роботи з API

#### 1. Пошук собак у Києві
```bash
curl "https://example.com/wp-json/lapki/v1/animals?type=dog&location=Київ&limit=10"
```

#### 2. Геопошук в радіусі 50км
```bash
curl "https://example.com/wp-json/lapki/v1/animals?latitude=50.4501&longitude=30.5234&distance=50"
```

#### 3. Створення тварини
```bash
curl -X POST "https://example.com/wp-json/lapki/v1/animals" \
  -H "Content-Type: application/json" \
  -d '{
    "organization_id": 1,
    "name": "Барсик",
    "type": "cat",
    "species": "cat",
    "age": "young",
    "gender": "male",
    "size": "medium",
    "description": "Веселий та грайливий котик"
  }'
```

### Програмне використання

#### Пошук тварин
```php
$animals = Lapki_Animal::search([
    'type' => 'dog',
    'age' => 'young',
    'good_with_children' => true,
    'limit' => 10
]);

foreach ($animals as $animal) {
    echo $animal['name'] . ' - ' . $animal['primary_photo']['thumbnail_url'];
}
```

#### Завантаження зображення
```php
$result = Lapki_Media::upload_image(
    $_FILES['animal_photo'],
    'animal',
    $animal_id,
    'Барсик',
    true // primary photo
);

if (!is_wp_error($result)) {
    echo 'URL: ' . $result['url'];
    echo 'Thumbnail: ' . $result['thumbnail_url'];
}
```

#### Робота з атрибутами
```php
// Отримати породи собак
$breeds = Lapki_Main::get_attribute_options('animal', 'dog', 'breed', 'uk');

// Додати нову породу
Lapki_Main::add_attribute('animal', 'dog', 'breed', 'husky', 'Хаскі', 'uk');

// Отримати відображуване значення
$display = Lapki_Main::get_attribute_display('animal', 'dog', 'breed', 'labrador', 'uk');
// Результат: "Лабрадор"
```

---

## ⚙️ Налаштування та встановлення

### Системні вимоги
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.2+

### Активація плагіна

При активації (`Lapki_Main::activate()`) виконується:
1. Створення медіа директорій (`/wp-content/uploads/lapki/`) + `.htaccess`/`index.php`
2. `Lapki_Migrations::install()` — `dbDelta()` створює/оновлює всі таблиці + `Lapki_Roles::install()` (ролі, capabilities) + seed-дані атрибутів (тільки якщо таблиця порожня)
3. `flush_rewrite_rules()` — щоб запрацювали `/animals/`, `/organizations/` тощо

**Автоапгрейд без реактивації:** `Lapki_Migrations::maybe_migrate()` на хуку `plugins_loaded` порівнює `Lapki_Migrations::DB_VERSION` з опцією `lapki_db_version` — якщо код плагіна оновили без деактивації/активації (типовий деплой), міграція все одно виконається на наступному завантаженні сторінки.

---

## 🔒 Безпека

### Поточний стан (з 2026-07-07 — авторизація увімкнена)

- **REST API:** `GET`-endpoints публічні (навмисно — публічний пошук тварин). `POST`/`PUT`/`DELETE` вимагають capability:
  - Тварини/медіа: `lapki_manage_animals` + власник організації (або `manage_options`)
  - Організації: `lapki_manage_organizations` + власник (або `manage_options`)
  - Атрибути (глобальний довідник): `lapki_manage_attributes` (тільки адмін)
  - Заявки на усиновлення (`POST /applications`): публічний за задумом (як контактна форма); `GET`/`PUT` — власник організації або адмін
- **Forms:** Nonce (`verify_nonce()`) і capability-перевірка в `class-eq-form.php::save()` увімкнені (клас наразі ніде фактично не використовується в адмінці — форми тварин/атрибутів/організацій ідуть напряму через REST API + JS)

#### 3. File uploads
- Валідація типів файлів (lapki-models.php:581-586)
- Обмеження розміру файлів
- Scan на віруси (рекомендовано)

#### 4. SQL Injection
- Всі запити використовують `$wpdb->prepare()` ✅
- Параметри санітизуються через `sanitize_text_field()` ✅

#### 5. XSS Protection
- Використовується `esc_html()`, `esc_attr()`, `esc_url()` ✅

---

## 📊 Продуктивність

### Оптимізації

#### Геопошук
- Використання формули Haversine безпосередньо в SQL
- Індекси на `latitude` та `longitude` (рекомендовано)

#### Медіа
- Автоматичне створення thumbnails (300x300px)
- Оптимізація якості (THUMB_QUALITY = 80)
- Lazy loading зображень (рекомендовано на фронтенді)

### Рекомендації

#### Індекси бази даних
```sql
-- Для швидкого пошуку
CREATE INDEX idx_animals_type_status ON wp_lapki_animals(type, status);
CREATE INDEX idx_animals_species ON wp_lapki_animals(species);
CREATE INDEX idx_animals_published ON wp_lapki_animals(published_at);
CREATE INDEX idx_media_entity ON wp_lapki_media(entity_type, entity_id, is_primary);
CREATE INDEX idx_attributes_lookup ON wp_lapki_attributes(entity, entity_type, attr_name, attr_value, lang);

-- Для геопошуку
CREATE INDEX idx_animals_location ON wp_lapki_animals(latitude, longitude);
```

#### WordPress кешування
- Використовувати WordPress Transients API для кешування атрибутів
- Або встановити плагін для об'єктного кешу (Redis Object Cache, Memcached)

---

## 🎯 Roadmap

### Етап 1: Завершення WordPress плагіна (Поточний)

#### Що зроблено (2026-07-07, сесія 2)
- [x] **Безпека:** авторизація на write-endpoints REST API, nonce/capability в формах, ролі/capabilities (`lapki_shelter_admin`, `lapki_volunteer`) + перевірка власності організації
- [x] **Адмін панель:** форма тварини, upload фото, редактор атрибутів, **сторінка організацій** (більше не заглушка), **сторінка налаштувань**
- [x] **Фронтенд:** локатор шаблонів тема→плагін, головна сторінка, **архів/пошук тварин, сторінка тварини, список і сторінка організації**, форма заявки на усиновлення
- [x] **Функціонал:** email-нотифікації про заявки на усиновлення (`wp_mail()`)
- [x] **База даних:** автоматичні міграції через `dbDelta()` (+ авто-апгрейд для вже активних сайтів), seed-дані довідника атрибутів
- [x] **Локалізація:** `.pot` згенеровано, `uk`/`en_US` `.po`/`.mo` скомпільовано і перевірено — **але обгорнуто в `__()` лише ~40 рядків** (меню, помилки REST API, налаштування); адмін-HTML, фронтенд-шаблони і JS все ще хардкод українською
- [x] **Тестування:** тестова БД `lapki_test` підключена, PHPUnit-оточення робоче (`phpunit/phpunit` зафіксовано на `^9.6` через несумісність `wp-phpunit` з PHPUnit 10). 53 тести / 120 assertions, всі проходять: `Test_Lapki_Animal.php` (create/get/search), `Test_Lapki_Organization.php` (create/get/update/delete/search + власність), `Test_Lapki_Media.php` (create/get/primary photo/delete), `Test_Lapki_Rest_Api.php` (авторизація write-endpoints `/animals` і `/organizations` — анонім/без capability/власник/чужа організація/адмін/volunteer). Модель `Lapki_Tag` і REST endpoints для медіа/атрибутів/заявок поки без тестів (сесія 3, 2026-07-08; деталі в `CHANGELOG.md`)

#### Що зроблено (2026-07-11, сесії 6-10)
- [x] **Публічна реєстрація:** сторінка `/signup/` + `[lapki_signup]`, `POST /wp-json/lapki/v1/signup` — 5 типів (приватна особа/притулок/ветклініка/ветеринар/волонтер) → роль + запис організації, автологін. Тимчасово доступна й залогіненим користувачам (позначено коментарем `ТИМЧАСОВО`)
- [x] **Кабінет користувача:** сторінка `/cabinet/` + `[lapki_cabinet]` — Головна (профіль + організація), Мої тварини (перегляд, усі статуси), Вихід; посилання «Кабінет» у шапці для залогінених
- [x] **Фільтр міст на `/organizations/`:** рядок кольорових боксів (стиль карток тварин), клік фільтрує організації за точним містом
- [x] **Embed-віджет `js/animals.js`:** самодостатній JS без залежностей для вставки на сторонні сайти/блоги, + CORS-виключення для `GET /animals`/`/organizations`, + тестова сторінка `/widget-demo/` (не в меню)
- [x] **Виправлено:** заголовок кількості тварин на `/organizations/{id}/` показував лише `adoptable`-тварин замість повного списку під ним
- [x] **GitHub:** репозиторій `esiteq/lapki` запушено й зроблено публічним

#### Що лишилось
- [ ] Wishlist (список улюблених тварин)
- [ ] Відео (завантаження й відображення)
- [ ] Історія прилаштувань, відгуки про притулки, соціальний шеринг
- [ ] Публічна frontend-форма самостійного додавання/редагування тварини — досі немає навіть після появи кабінету (`/add-animal/` в темі нікуди не веде, «Мої тварини» в кабінеті — лише перегляд, без CRUD)
- [ ] Backup/restore БД
- [ ] Повне покриття локалізації (усі UI-рядки), OpenAPI/Swagger документація, User/Developer guide
- [ ] **Дрібна неузгодженість:** масиви `$type_labels` для типу організації різняться між шаблонами — `archive-organizations.php`/`single-organization.php` не знають про типи `vet`/`volunteer` (з'явились у сесії 6), а всі троє мають легасі `rescue` (не використовується `[lapki_signup]`, лишився від старих демо-даних); організація типу `vet`/`volunteer` показуватиме на публічних сторінках сирий слаг замість перекладу — варто винести список типів в одне спільне місце
- [ ] **Git-покриття:** репозиторій плагіна (`wp-content/plugins/.git`) не покриває ні тему (`wp-content/themes/lapki/`), ні кореневий `js/animals.js` — зміни там не потрапляють у пуші на GitHub, поки для них не створено окремий репозиторій
- [x] Запустити PHPUnit (тестова БД `lapki_test` надана, базовий тест-сьют для `Lapki_Animal` готовий і зелений; інші моделі/REST API поки без тестів)

Детальний, пріоритезований план дій і повний список того, що зроблено в останній сесії — див. `.doc/todo.md`.

### Етап 2: Міграція на Node.js + Express (Майбутнє)

**Почати ТІЛЬКИ після 100% завершення WordPress плагіна!**

⚠️ Попри це, `server/` вже містить розгорнутий scaffold (Express, mysql2, JWT, multer, sharp, node-geocoder, helmet, express-rate-limit, jest+supertest) — випереджає власний roadmap.

#### Технічний стек (попередньо)
- Node.js + Express
- TypeScript
- PostgreSQL або MongoDB
- Redis (опціонально)
- JWT авторизація
- GraphQL або REST API
- Docker + Docker Compose

#### Переваги міграції
- Краща продуктивність
- Сучасний технічний стек
- Легше масштабування
- Відокремлення фронтенду від бекенду
- Можливість SPA (React/Vue/Next.js)

#### Етапи міграції
1. [ ] Дизайн API (OpenAPI spec)
2. [ ] Налаштування Node.js проєкту
3. [ ] Міграція бази даних
4. [ ] Реалізація endpoints
5. [ ] Тестування та порівняння з WordPress версією
6. [ ] Поступова міграція фронтенду
7. [ ] Deployment та моніторинг

---

## 📝 Git status

Репозиторій знаходиться на рівень вище — `wp-content/plugins/.git` (не в корені `/var/www/lapki`), відстежує лише вміст `lapki/` (плагін). Публічний на GitHub: **https://github.com/esiteq/lapki** (зроблено публічним і перевірено 2026-07-11 — доступний без авторизації через API/raw/`git clone`).

**⚠️ Не покрито git взагалі:**
- `wp-content/themes/lapki/` (публічна тема) — окремого репозиторію немає
- `/var/www/lapki/js/animals.js` (embed-віджет) — лежить у корені сайту, поза `wp-content/plugins`

Зміни в цих двох місцях (а це вся тема: `header.php`, `functions.php`, `assets/css/lapki.css`, `assets/js/lapki.js`, + сам `animals.js`) **не потрапляють** у пуші на GitHub, доки для них не буде створено окремий репозиторій/submodule.

**Поточна гілка:** `master`

**Останній запушений коміт (2026-07-11, сесія 8):** `2650931` — "Add DB migrations, roles/auth, public frontend, signup and user cabinet" (squash-коміт сесій 2-8, до пуша репозиторій довго стояв не закомічений).

**Незакомічені зміни станом на сесію 10 (2026-07-11, після пуша):**
- Modified: `CHANGELOG.md`, `inc/class-lapki-frontend.php`, `inc/class-lapki-rest-api.php`, `templates/single-organization.php`
- Untracked: `templates/widget-demo.php`
- Тобто: CORS-виключення для embed-віджета, rewrite-правило `/widget-demo/`, і виправлення лічильника тварин на `/organizations/{id}/` — усе з сесій 9-10 — **ще не запушено**.

**Останні коміти:**
```
2650931 Add DB migrations, roles/auth, public frontend, signup and user cabinet
ff6e176 Update class-lapki-admin.php
df19659 sql
49b4649 cache
9ed2420 Update lapki.php
```

---

## ✅ TODO

### 🔴 Критичні баги

- [x] **Подвійне видалення файлів** (`class-lapki-rest-api.php:779` + `class-lapki-models.php:673`)
  - `DELETE /media/{id}` видаляє файли вручну, а потім `Lapki_Media::delete()` видаляє їх ще раз
  - Виправлення: прибрати ручне видалення файлів з `Lapki_REST_API::delete_media()`, залишити тільки виклик `Lapki_Media::delete()`

- [x] **Media endpoints мають авторизацію** (`class-lapki-rest-api.php:148,156,163`)
  - `POST /animals/{id}/media`, `DELETE /media/{id}`, `PUT /media/{id}/primary` використовують `current_user_can('manage_options')` замість `__return_true` як всі інші endpoints
  - Завантаження/видалення фото не спрацює без авторизованої сесії
  - Виправлення: поставити `'permission_callback' => '__return_true'` (тимчасово, до впровадження авторизації)

### 🟡 Відсутній функціонал

- [x] **Пошук тварин в адмінці не працює** (`js/lapki-admin.js:235`, `inc/class-lapki-rest-api.php`)
  - JS посилає `&search=term`, але параметр `search` відсутній в `get_animals()` і `get_animals_search_args()`
  - Виправлення: додати `search` параметр в REST API, реалізувати `WHERE a.name LIKE %s` в `Lapki_Animal::search()`

- [x] **Пагінація в JS не реалізована** (`js/lapki-admin.js:313`)
  - `renderPagination()` містить тільки `console.log` — кнопки сторінок відсутні
  - Виправлення: реалізувати рендер кнопок пагінації і передачу `page` в `loadAnimals()`

- [x] **Список тварин в адмінці показує тільки `adoptable`** (`inc/class-lapki-rest-api.php:185`)
  - `GET /animals` за замовчуванням фільтрує `status = 'adoptable'`, JS не передає `status=all`
  - Виправлення: в `loadAnimals()` не передавати `status` взагалі (або передавати всі статуси), в REST API не встановлювати дефолтний статус якщо він не переданий

- [x] **Endpoint `/types/all` не існує явно**
  - JS викликає `/types/all?lang=uk` для заповнення age/gender/size/coat/status селектів і кешу перекладів
  - Зараз це потрапляє під маршрут `/types/{type}` з `type='all'`, тобто шукає `entity_type = 'all'` в таблиці атрибутів
  - Якщо таких записів немає в БД — всі ці селекти будуть порожніми
  - Виправлення: зареєструвати явний маршрут `/types/all` який повертає атрибути для `entity_type = 'all'`, АБО засіяти БД записами з `entity_type = 'all'` для age, gender, size, coat, status

- [x] **Статус у формі залежить від атрибутів в БД**
  - Статуси (adoptable/adopted/hold/found) підтягуються через `/types/all` → `attributes.status`
  - Якщо в `wp_lapki_attributes` немає записів зі статусами — поле "Статус" у формі буде порожнім
  - Виправлення: або засіяти БД статусами, або захардкодити статуси в JS/PHP

### 🔵 Незначні проблеми

- [x] **Неефективний підрахунок для пагінації** (`inc/class-lapki-rest-api.php:218`)
  - Робить другий SQL запит з `LIMIT 999999` і рахує `count()` в PHP
  - Виправлення: використати `SELECT COUNT(*)` окремим запитом

- [ ] **`get_animal_types` — нестандартна SQL умова** (`inc/class-lapki-models.php:913`)
  - Шукає `entity_type = 'type'` — повністю залежить від структури seed-даних
  - Якщо типи записані інакше — `GET /types` поверне порожній масив

---

## 🤝 Контакти та підтримка

**Автор:** Oleksii Bugrov
**Website:** https://esiteq.com/
**Plugin URI:** https://esiteq.com/projects/lapki/
**GitHub Issues:** (не вказано)

---

## 📜 Ліцензія

GPL v2 or later
https://www.gnu.org/licenses/gpl-2.0.html

---

**Документація створена:** 2025-10-08
**Востаннє оновлена:** 2026-07-11 (сесії 6-10 — публічна реєстрація, кабінет користувача, фільтр міст, embed-віджет + CORS, виправлення лічильника тварин)
**Версія документації:** 1.3
**Для плагіна версії:** 2.0.0 (`LAPKI_VERSION` константа в `lapki.php` — 2.0.10; версії docblock/константи розійшлись, не виправлялось)
