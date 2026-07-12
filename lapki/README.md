# Lapki — платформа пошуку та прилаштування тварин

WordPress плагін + публічна тема для управління базою тварин, притулків і волонтерів, інспірований petfinder.com, локалізований для України.

Детальна архітектурна документація — [CLAUDE.md](CLAUDE.md). Історія змін по сесіях розробки — [CHANGELOG.md](CHANGELOG.md).

## Опис

Платформа пошуку та прилаштування собак, котів, птахів та інших тварин з притулків. Притулки, ветклініки, ветеринари, волонтери й приватні особи можуть самостійно зареєструватись, вести профіль організації через REST API та (поки лише на перегляд) стежити за своїми тваринами в особистому кабінеті. Публічний фронтенд (архів/пошук тварин, сторінки організацій, картка тварини з формою заявки на усиновлення) працює незалежно від адмін-панелі WordPress.

## Особливості

- 🐕 Управління тваринами (собаки, коти, птахи, кролики та інші)
- 🏢 Управління організаціями: притулок, ветклініка, окремий ветеринар, волонтер, приватна особа
- 📸 Галерея зображень з upload/thumbnail (300×300px), встановлення головного фото
- 🗺️ Геолокація: OpenStreetMap + Leaflet.js в адмінці, геопошук за формулою Haversine в REST API
- 👤 Публічна реєстрація (`/signup/`) + особистий кабінет користувача (`/cabinet/`)
- 📝 Форма заявки на усиновлення з email-нотифікаціями
- 🧩 Embed-віджет (`/js/animals.js`) — картки тварин притулку для вставки на сторонній сайт/блог
- 🔒 Ролі й capabilities (`lapki_shelter_admin`, `lapki_volunteer`), авторизація write-ендпоінтів REST API
- 🌍 Часткова підтримка мультимовності (українська, англійська)
- 🔌 REST API для всіх операцій
- 📱 Адаптивний інтерфейс (Bootstrap 5 — публічна тема; власна адмін-панель)

## Технічний стек

- PHP 7.4+, WordPress 5.0+
- MySQL/MariaDB, схема через `dbDelta()`-міграції з авто-апгрейдом
- REST API (namespace `lapki/v1`)
- Публічна тема на Bootstrap 5 + vanilla JS (без jQuery на фронтенді)
- Leaflet.js + OpenStreetMap Nominatim, Dropzone.js — в адмін-панелі
- PHPUnit (тестова БД `lapki_test`)

## Структура

```
lapki/
├── lapki.php                          # Головний файл плагіна (Lapki_Main)
├── inc/
│   ├── class-lapki-models.php         # Моделі: Animal, Organization, Media, Attributes, Tag, Application
│   ├── class-lapki-rest-api.php       # REST API endpoints (namespace lapki/v1) + signup + embed CORS
│   ├── class-lapki-admin.php          # Адмін-панель (тварини, організації, атрибути, налаштування)
│   ├── class-lapki-roles.php          # Ролі та capabilities
│   ├── class-lapki-migrations.php     # dbDelta()-міграції + seed-дані
│   ├── class-lapki-template-loader.php # Локатор шаблонів (тема → плагін)
│   ├── class-lapki-frontend.php       # Rewrite rules, роутинг фронтенду, шорткоди [lapki_signup]/[lapki_cabinet]
│   └── data/seed-attributes.sql       # Забандлений довідник атрибутів
├── templates/                          # Публічні шаблони (тема може перевизначити в themes/{тема}/lapki/)
│   ├── archive-animals.php            # /animals/ — пошук з фільтрами
│   ├── single-animal.php              # /animals/{id}/ — картка тварини + форма заявки
│   ├── archive-organizations.php      # /organizations/ — + фільтр міст (кольорові бокси)
│   ├── single-organization.php        # /organizations/{id}/
│   ├── shortcode-signup.php           # [lapki_signup] — /signup/
│   ├── shortcode-cabinet.php          # [lapki_cabinet] — /cabinet/
│   └── widget-demo.php                # /widget-demo/ — демо embed-віджета (не в меню)
├── css/lapki-admin.css, js/lapki-admin.js  # Адмін-панель
├── languages/                          # lapki.pot, uk/en_US .po/.mo (часткове покриття)
├── tests/                              # PHPUnit (моделі + REST API авторизація)
└── CHANGELOG.md, CLAUDE.md

wp-content/themes/lapki/                # Публічна тема (Bootstrap 5) — ОКРЕМИЙ від git-репозиторію плагіна
├── header.php, footer.php, front-page.php, page.php, 404.php
└── assets/css/lapki.css, assets/js/lapki.js   # Кабінет, реєстрація, фільтр міст, картки тварин

/var/www/lapki/js/animals.js            # Embed-віджет — поза WP, у корені сайту, теж не під git
```

## Публічні сторінки

| URL | Опис |
|---|---|
| `/animals/` | Архів/пошук тварин з фільтрами (тип, вік, притулок, місто, кличка) |
| `/animals/{id}/` | Картка тварини + форма заявки на усиновлення |
| `/organizations/` | Список організацій + фільтр за містом |
| `/organizations/{id}/` | Картка організації зі списком її тварин |
| `/signup/` | Реєстрація: приватна особа / притулок / ветклініка / ветеринар / волонтер |
| `/cabinet/` | Особистий кабінет: профіль, мої тварини (перегляд), вихід |
| `/widget-demo/` | Демонстрація embed-скрипта (не в меню) |

## REST API Endpoints

Base URL: `/wp-json/lapki/v1`

### Тварини (Animals)
- `GET /animals` — пошук з фільтрами (тип, вік, стать, розмір, статус, місто, геопошук тощо)
- `GET /animals/{id}` — картка тварини (з медіа й тегами)
- `POST /animals` — створити (capability `lapki_manage_animals`)
- `PUT /animals/{id}` / `DELETE /animals/{id}` — власник організації або адмін

### Організації (Organizations)
- `GET /organizations` — пошук (name/type/city/state/location/verified_only)
- `GET /organizations/{id}` — картка організації
- `POST /organizations` — створити (capability `lapki_manage_organizations`)
- `PUT /organizations/{id}` / `DELETE /organizations/{id}` — власник або адмін

### Довідники (Types/Attributes)
- `GET /types`, `GET /types/all`, `GET /types/{type}`, `GET /types/{type}/breeds`
- `GET /attributes`, `POST /attributes`, `PUT /attributes/{id}`, `DELETE /attributes/{id}` (тільки адмін)

### Медіа
- `POST /animals/{id}/media`, `DELETE /media/{id}`, `PUT /media/{id}/primary`

### Заявки на усиновлення (Applications)
- `POST /applications` — публічний (як контактна форма)
- `GET /applications?organization_id=` — власник організації або адмін
- `PUT /applications/{id}` — зміна статусу

### Реєстрація
- `POST /signup` — публічна реєстрація нового користувача + організації (детально в [CLAUDE.md](CLAUDE.md))

### Інше
- `GET /locations` — автодоповнення міст
- `GET /stats` — загальна статистика тварин

**Авторизація:** усі `GET` — публічні. Write-операції вимагають capability + (де застосовно) перевірку власності організації.
**CORS:** `GET /animals` і `GET /organizations` віддають `Access-Control-Allow-Origin: *` (потрібно для embed-віджета); решта ендпоінтів — дефолтна CORS-поведінка WordPress.

## Ролі

- `lapki_shelter_admin` — притулок / ветклініка / окремий ветеринар: `lapki_manage_animals` + `lapki_manage_organizations`
- `lapki_volunteer` — приватна особа / волонтер: тільки `lapki_manage_animals`
- Адміністратор сайту отримує всі capability (включно з `lapki_manage_attributes`, лише для нього)

## Embed-віджет

Показати картки тварин конкретного притулку на сторонньому сайті чи в блозі:

```html
<script src="https://lapki.help/js/animals.js?organization_id=1"></script>
```

Необов'язкові параметри: `limit` (дефолт 24), `status` (дефолт `adoptable`). Скрипт самодостатній (без залежностей), стилі й розмітку ін'єктує сам, на всю ширину контейнера. Демо — `/widget-demo/`.

## Структура БД

- `wp_lapki_animals` — тварини
- `wp_lapki_organizations` — організації (притулок/ветклініка/ветеринар/волонтер/приватна особа)
- `wp_lapki_media` — медіафайли (`file_path` зберігає лише назву файлу; URL генерується динамічно)
- `wp_lapki_attributes` — довідник перекладів/атрибутів
- `wp_lapki_tags` — теги тварин
- `wp_lapki_applications` — заявки на усиновлення

Усі таблиці створюються автоматично через `dbDelta()` при активації плагіна, з авто-апгрейдом схеми для вже активних сайтів (без потреби деактивувати/активувати плагін).

Зображення зберігаються в `/wp-content/uploads/lapki/` (`images/` — оригінали, `thumbnails/` — 300×300px).

## Установка

1. Завантажити плагін в `/wp-content/plugins/lapki/`
2. Активувати в адмін-панелі WordPress — автоматично створить таблиці, ролі, медіа-директорії й виконає `flush_rewrite_rules()`
3. Активувати тему `wp-content/themes/lapki/` для публічного фронтенду

## Тестування

PHPUnit, тестова БД `lapki_test`, `phpunit/phpunit` зафіксовано на `^9.6` (несумісність `wp-phpunit` з PHPUnit 10). Покриття: моделі (Animal/Organization/Media/Tag/Attributes), авторизація write-ендпоінтів REST API. Деталі — [CLAUDE.md](CLAUDE.md#тестування) і [CHANGELOG.md](CHANGELOG.md).

## Розробка

### Архітектурні принципи
1. **Separation of Concerns**: Адмін-панель (HTML) → JavaScript → REST API → Моделі → БД
2. **No Direct DB Queries**: Вся робота з БД тільки через класи-моделі
3. **REST-First**: Всі операції через REST API endpoints
4. **Validation**: Валідація на всіх рівнях (JS → API → Models)

### Додавання нових атрибутів

```sql
INSERT INTO wp_lapki_attributes (entity, entity_type, attr_name, attr_value, attr_display, lang)
VALUES ('animal', 'dog', 'color', 'black', 'Чорний', 'uk');
```

## Відомі обмеження

- Кабінет користувача (`/cabinet/`) поки дозволяє лише переглядати своїх тварин — форми додавання/редагування з фронтенду ще немає
- Git-репозиторій плагіна не покриває тему (`wp-content/themes/lapki/`) і кореневий `js/animals.js` — зміни там не потрапляють у пуші на GitHub
- Повний список — розділ Roadmap у [CLAUDE.md](CLAUDE.md)

## Історія змін

Докладний лог по сесіях розробки — [CHANGELOG.md](CHANGELOG.md).

### 2.0.9 — 2025-10-08

#### Додано
- Галерея зображень з Dropzone.js (drag & drop, thumbnails, модалка, головне фото)
- OpenStreetMap інтеграція (Leaflet.js, геокодування через Nominatim)
- REST API для медіа (`POST /animals/{id}/media`, `DELETE /media/{id}`, `PUT /media/{id}/primary`)

#### Виправлено
- Форма редагування тварини (послідовне завантаження опцій → дані, прибрано дубль поля "species")
- Селекти атрибутів (колір/порода) — динамічні замість текстових полів
- Головне зображення — автопризначення при видаленні/першому завантаженні

### 2.0.0 — Попередні версії
- Базова функціональність плагіну, REST API endpoints, адмін-панель, атрибути/довідники

## Автор

**Oleksii Bugrov**
Website: [esiteq.com](https://esiteq.com/)

## Ліцензія

GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

## Підтримка

Для звітів про помилки та пропозицій: [esiteq.com](https://esiteq.com/)
