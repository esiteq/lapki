# SNAPSHOT — стан між сесіями

> Оновлюється вручну або через Claude після кожної значної зміни.
> Дата останнього оновлення: **2026-07-11** (сесія 11 — цей файл, `CLAUDE.md`, `README.md` синхронізовано з реальним станом коду)

---

## Поточна версія

- **Plugin version:** 2.0.0 (docblock у `lapki.php`) / `LAPKI_VERSION` константа — `2.0.10` (розбіжність існує давно, не виправлялась)
- **Git:** репозиторій на рівень вище (`wp-content/plugins/.git`), гілка `master`, покриває лише `lapki/` (плагін)
  - **Публічний на GitHub:** https://github.com/esiteq/lapki (перевірено 2026-07-11 — доступний без авторизації через API/raw/`git clone`)
  - Останній **запушений** коміт: `2650931` — "Add DB migrations, roles/auth, public frontend, signup and user cabinet" (squash сесій 2-8; до цього репозиторій довго стояв не закомічений)
  - **Незакомічені зміни станом на сесію 10:** `CHANGELOG.md`, `inc/class-lapki-frontend.php`, `inc/class-lapki-rest-api.php`, `templates/single-organization.php` (modified), `templates/widget-demo.php` (untracked) — CORS для embed-віджета, rewrite `/widget-demo/`, виправлення лічильника тварин
  - **⚠️ Зовсім не під git:** `wp-content/themes/lapki/` (тема) і `/var/www/lapki/js/animals.js` (embed-віджет, корінь сайту) — окремих репозиторіїв для них немає

---

## Файлова структура

```
lapki/
├── lapki.php                      # Головний клас Lapki_Main
├── inc/
│   ├── class-lapki-models.php     # Animal, Organization (+get_cities_with_counts), Media, Attributes, Tag, Application
│   ├── class-lapki-rest-api.php   # REST API (namespace lapki/v1) + signup + allow_embed_cors()
│   ├── class-lapki-admin.php      # Адмін-панель (тварини/організації/атрибути/налаштування) — manage_options-only
│   ├── class-lapki-roles.php      # lapki_shelter_admin, lapki_volunteer
│   ├── class-lapki-migrations.php # dbDelta() + авто-апгрейд + seed
│   ├── class-lapki-template-loader.php # тема → плагін
│   └── class-lapki-frontend.php   # rewrite rules + шорткоди [lapki_signup]/[lapki_cabinet]
├── templates/
│   ├── archive-animals.php, single-animal.php
│   ├── archive-organizations.php  # + фільтр міст (кольорові бокси)
│   ├── single-organization.php    # заголовок кількості тварин виправлено (сесія 10)
│   ├── shortcode-signup.php       # /signup/
│   ├── shortcode-cabinet.php      # /cabinet/
│   └── widget-demo.php            # /widget-demo/ (не в меню)
├── js/lapki-admin.js, css/lapki-admin.css   # адмінка
├── tests/                         # PHPUnit
├── CLAUDE.md, CHANGELOG.md, README.md
└── .claude/SNAPSHOT.md             # цей файл

wp-content/themes/lapki/            # Bootstrap 5, НЕ під git
├── header.php  # посилання «Кабінет» для залогінених
└── assets/css/lapki.css, assets/js/lapki.js  # стилі/JS кабінету, реєстрації, фільтра міст, форми заявки

/var/www/lapki/js/animals.js        # embed-віджет, НЕ під git, віддається Apache напряму

server/                             # Node.js scaffold для Етапу 2 — не чіпати
```

**Резервні копії (не чіпати, gitignored):** `lapki.bak`, `lapki.2.bak`, `inc/class-eq-form.bak`, `inc/class-lapki-admin.bak`, `inc/class-lapki-admin.2.bak`

---

## Стан бекенду (PHP)

| Компонент | Стан |
|---|---|
| REST API endpoints (animals/organizations/media/attributes/applications/signup) | ✅ Готово |
| Моделі (CRUD) | ✅ Готово |
| Геопошук (Haversine) | ✅ Готово |
| Медіа (upload + thumbnail) | ✅ Готово |
| Атрибути/переклади | ✅ Готово |
| Ролі/capabilities (`lapki_shelter_admin`, `lapki_volunteer`) | ✅ Готово |
| Авторизація на write-ендпоінтах REST API | ✅ Увімкнена (capability + ownership) |
| CORS для публічного embed-віджета | ✅ Готово (`allow_embed_cors()`, лише GET animals/organizations) |
| Публічна реєстрація (`POST /signup`) | ✅ Готово |
| Міграції БД (`dbDelta()` при активації + авто-апгрейд) | ✅ Готово |
| Seed дані атрибутів | ✅ Готово |
| Адмін-панель (тварини, форма, атрибути, організації, налаштування) | ✅ Готово (**лише для `manage_options`** — `lapki_shelter_admin`/`lapki_volunteer` не мають доступу до wp-admin взагалі) |
| Nonce/capability в `class-eq-form.php` | ⚠️ Клас ніде фактично не використовується (форми йдуть напряму через REST API + JS) |

## Стан фронтенду

| Компонент | Стан |
|---|---|
| Адмін JS/CSS (`lapki-admin.js/css`) | ✅ Готово |
| Тема `themes/lapki/` (Bootstrap 5) | ✅ Готово (НЕ під git) |
| Локатор шаблонів тема→плагін | ✅ Готово |
| Архів/пошук тварин, картка тварини, список/картка організації | ✅ Готово |
| Форма заявки на усиновлення | ✅ Готово (email-нотифікації) |
| Фільтр міст на `/organizations/` | ✅ Готово (кольорові бокси, `?city=`) |
| Публічна реєстрація `/signup/` | ✅ Готово (тимчасово доступна й залогіненим) |
| Кабінет користувача `/cabinet/` | ✅ Готово, але **лише перегляд** — немає форми додавання/редагування тварини |
| Embed-віджет `/js/animals.js` + `/widget-demo/` | ✅ Готово |
| Публічна форма самостійного додавання тварини (`/add-animal/`) | ❌ Досі нікуди не веде |

---

## Виправлені баги (історія)

1. ✅ Подвійне видалення файлів у `delete_media()`
2. ✅ Media endpoints мали авторизацію замість `__return_true`
3. ✅ Пошук тварин в адмінці не передавав `search` параметр
4. ✅ Список тварин показував тільки `adoptable` (дефолт скинуто на `''`)
5. ✅ Endpoint `/types/all` не існував явно
6. ✅ Статус у формі залежав від БД — захардкоджено
7. ✅ Пагінація в JS була заглушкою — реалізовано
8. ✅ Підрахунок пагінації через `SELECT COUNT(*)` замість `LIMIT 999999`
9. ✅ **(сесія 10)** `/organizations/{id}/` — заголовок кількості тварин рахував лише `adoptable` (`animals_count` з моделі), а сітка карток під ним рендерила всіх тварин незалежно від статусу → число не збігалось зі списком. Виправлено на `count($animals)`.

---

## Відомі проблеми / TODO

Повний пріоритезований список — `.doc/todo.md`, повний Roadmap — `CLAUDE.md`. Коротко:

### 🟡 Важливі
- [ ] Кабінет користувача — тільки перегляд тварин, немає форми додавання/редагування з фронтенду
- [ ] Адмін-панель доступна лише `manage_options` — щойно зареєстровані `lapki_shelter_admin`/`lapki_volunteer` не мають туди доступу (кабінет частково закриває цю прогалину, але без CRUD тварин)
- [ ] Локалізація (.pot/.po/.mo) — покриття часткове (~40 рядків), решта хардкод українською
- [ ] Wishlist, відео, історія прилаштувань, відгуки, соціальний шеринг — не реалізовано

### 🔵 Незначні
- [ ] `$type_labels` для типу організації неузгоджені між шаблонами: `archive-organizations.php`/`single-organization.php` не знають про `vet`/`volunteer` (з'явились у `[lapki_signup]`), усі троє мають легасі `rescue` (не використовується новою реєстрацією) — варто винести в одне спільне місце
- [ ] Немає OpenAPI/Swagger документації
- [ ] Git не покриває тему й кореневий `js/animals.js` — окремий репозиторій ще не створено
- [x] PHPUnit: `lapki_test` БД підключена, 87+ тестів (моделі + REST API авторизація), деталі в `CHANGELOG.md`

---

## REST API — короткий довідник

Base URL: `/wp-json/lapki/v1`

| Метод | Endpoint | Опис |
|---|---|---|
| GET | `/animals` | Пошук з фільтрами (CORS: `*`) |
| GET | `/animals/{id}` | Деталі тварини |
| POST | `/animals` | Створити тварину |
| PUT / DELETE | `/animals/{id}` | Оновити/видалити (власник або адмін) |
| POST | `/animals/{id}/media` | Завантажити фото |
| DELETE | `/media/{id}` | Видалити фото |
| PUT | `/media/{id}/primary` | Встановити головне фото |
| GET | `/types`, `/types/all`, `/types/{type}`, `/types/{type}/breeds` | Довідники |
| GET | `/locations` | Автодоповнення міст |
| GET | `/organizations` | Пошук організацій (CORS: `*`), підтримує `city` (точний збіг) |
| GET | `/organizations/{id}` | Деталі організації (CORS: `*`) |
| POST | `/organizations` | Створити організацію |
| PUT / DELETE | `/organizations/{id}` | Оновити/видалити (власник або адмін) |
| GET / POST | `/attributes` | Довідник атрибутів (write — лише адмін) |
| PUT / DELETE | `/attributes/{id}` | Оновити/видалити атрибут |
| POST | `/applications` | Заявка на усиновлення (публічний) |
| GET | `/applications?organization_id=` | Власник організації або адмін |
| PUT | `/applications/{id}` | Змінити статус заявки |
| POST | `/signup` | ⭐ Публічна реєстрація (individual/shelter/vet_clinic/vet/volunteer) |
| GET | `/stats` | Статистика тварин |

---

## Наступні кроки (Roadmap)

1. Форма додавання/редагування тварини з кабінету користувача (`/cabinet/?tab=animals`) — закриє основну прогалину self-service
2. Синхронізувати `$type_labels` організацій між шаблонами (`vet`/`volunteer`/легасі `rescue`)
3. Створити git-репозиторій для теми (`wp-content/themes/lapki/`) і/або кореневого `js/animals.js`, якщо потрібна історія змін
4. Wishlist, відео, історія прилаштувань, відгуки, соціальний шеринг
5. Повне покриття локалізації, OpenAPI/Swagger документація

Детальніше — `.doc/todo.md` і розділ Roadmap у `CLAUDE.md`.
