# SNAPSHOT — стан між сесіями

> Оновлюється вручну або через Claude після кожної значної зміни.
> Дата останнього оновлення: **2026-07-07** (цей файл частково застарів після сесії 2 — актуальний стан дивись у `CLAUDE.md`)

---

## Поточна версія
- **Plugin version:** 2.0.0
- **Git:** репозиторій на рівень вище (`wp-content/plugins/.git`), гілка `master`
  - Багато незакомічених змін (models/rest-api/admin/lapki.php modified, `class-lapki-cache.php` видалено, `CLAUDE.md`/`CHANGELOG.md`/`.doc/todo.md`/`README.md`/css/js untracked)

---

## Файлова структура

```
lapki/
├── lapki.php                    # Головний клас Lapki_Main
├── inc/
│   ├── class-lapki-models.php   # Lapki_Animal, Lapki_Organization, Lapki_Media, Lapki_Attributes
│   ├── class-lapki-rest-api.php # REST API endpoints (namespace: lapki/v1)
│   ├── class-lapki-admin.php    # Адмін-панель + Lapki_Animals_List_Table
│   └── class-eq-form.php        # Конструктор форм EQ_Form
├── js/
│   └── lapki-admin.js           # JS для адмін-панелі (~53KB: список, форма, атрибути, пагінація)
├── css/
│   └── lapki-admin.css          # CSS для адмін-панелі
├── .doc/
│   ├── lapki.sql                # Повна SQL-схема (не підключена до activate())
│   ├── migrations/001_add_petfinder_fields.sql
│   └── todo.md                  # Пріоритезований план дій
├── CLAUDE.md                    # Архітектурна документація
├── CHANGELOG.md                 # Лог змін
└── .claude/
    └── SNAPSHOT.md              # Цей файл

wp-content/themes/lapki/         # Публічна тема (Bootstrap 5)
├── front-page.php, header.php, footer.php, 404.php, page.php
├── assets/css/lapki.css, assets/js/lapki.js
└── lapki/                       # Тека перевизначення шаблонів плагіна (WooCommerce-подібний патерн)

server/                          # Node.js scaffold для Етапу 2 (Express, mysql2, JWT, jest) — вже досить розвинений
```

**Резервні копії (не чіпати):** `lapki.bak`, `lapki.2.bak`, `inc/class-eq-form.bak`, `inc/class-lapki-admin.bak`, `inc/class-lapki-admin.2.bak`

---

## Стан бекенду (PHP)

| Компонент | Стан |
|---|---|
| REST API endpoints | ✅ Готово |
| Моделі (CRUD) | ✅ Готово |
| Геопошук (Haversine) | ✅ Готово |
| Медіа (upload + thumbnail) | ✅ Готово |
| Атрибути/переклади | ✅ Готово |
| Адмін-панель (список тварин) | ✅ Готово |
| Адмін-панель (форма редагування тварини) | ✅ Готово (Leaflet карта, Dropzone фото) |
| Адмін-панель (редактор атрибутів) | ✅ Готово (CRUD, фільтри, модалка) |
| Адмін-панель (організації) | ❌ Заглушка `<p>Скоро буде...</p>` |
| Пошук + пагінація в адмінці | ✅ Виправлено |
| Авторизація на REST endpoints | ⚠️ Відключена (`__return_true`) |
| Nonce/capability в формах | ⚠️ Закоментовані (`class-eq-form.php:352-360`) |
| Міграції БД (CREATE TABLE при активації) | ❌ Відсутнє (`activate()` лише створює медіа-директорії) |
| Seed дані | ❌ Відсутнє |

## Стан фронтенду

| Компонент | Стан |
|---|---|
| Адмін JS (lapki-admin.js) | ✅ Готово |
| Адмін CSS (lapki-admin.css) | ✅ Готово |
| Тема `themes/lapki/` (Bootstrap 5, header/footer, головна сторінка) | ✅ Готово |
| Локатор шаблонів тема→плагін (`wc_get_template()`-аналог) | ❌ Тека `themes/lapki/lapki/` створена, функція не реалізована |
| Сторінка однієї тварини / архів-пошук / сторінка організації | ❌ Відсутні |
| Форма заявки на усиновлення | ❌ Відсутня |

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

---

## Відомі проблеми / TODO

Повний пріоритезований список — `.doc/todo.md`. Коротко:

### 🔴 Критичні
- [ ] Авторизація відключена на всіх REST endpoints (`__return_true`)
- [ ] Форми: nonce та capability перевірки закоментовані в `class-eq-form.php:352-360`
- [ ] Таблиці БД не створюються автоматично при активації плагіна

### 🟡 Важливі
- [ ] Публічний фронтенд: є тільки тема + головна сторінка, немає сторінки тварини/пошуку/організації
- [ ] Локатор шаблонів тема→плагін не реалізований
- [ ] Сторінка «Організації» в адмінці — заглушка
- [ ] Локалізація (.pot/.po/.mo) відсутня
- [ ] Roles/capabilities (shelter_admin, volunteer, adopter) не реалізовані
- [ ] `get_animal_types` — залежить від `entity_type = 'type'` в seed-даних

### 🔵 Незначні
- [ ] Немає seed-даних для тестування
- [ ] Немає OpenAPI/Swagger документації
- [x] PHPUnit тести для плагіна: `lapki_test` БД підключена, `tests/Test_Lapki_Animal.php` — 13 зелених тестів на `create()`/`get()`/`search()` (сесія 3, 2026-07-08; деталі в `CHANGELOG.md`)
- [ ] Lazy loading зображень на фронтенді

---

## REST API — короткий довідник

Base URL: `/wp-json/lapki/v1`

| Метод | Endpoint | Опис |
|---|---|---|
| GET | `/animals` | Пошук з фільтрами |
| GET | `/animals/{id}` | Деталі тварини |
| POST | `/animals` | Створити тварину |
| PUT | `/animals/{id}` | Оновити тварину |
| DELETE | `/animals/{id}` | Видалити тварину |
| POST | `/animals/{id}/media` | Завантажити фото |
| DELETE | `/media/{id}` | Видалити фото |
| PUT | `/media/{id}/primary` | Встановити головне фото |
| GET | `/types` | Всі типи тварин |
| GET | `/types/all` | Глобальні атрибути (age/gender/size/coat) |
| GET | `/types/{type}` | Атрибути конкретного типу |
| GET | `/organizations` | Пошук організацій |
| GET | `/organizations/{id}` | Деталі організації |
| POST | `/organizations` | Створити організацію |
| GET | `/attributes` | Список атрибутів (з фільтрами) |
| POST | `/attributes` | Створити атрибут |
| PUT | `/attributes/{id}` | Оновити атрибут |
| DELETE | `/attributes/{id}` | Видалити атрибут |
| GET | `/stats` | Статистика |

---

## Наступні кроки (Roadmap)

1. Створити міграції БД (`dbDelta()` в `activate()` + seed-дані)
2. Увімкнути авторизацію на REST endpoints і nonce/capability в формах
3. Реалізувати локатор шаблонів тема→плагін
4. Реалізувати сторінки фронтенду: тварина, архів/пошук, організація
5. Довести сторінку «Організації» в адмінці до робочого стану
6. Roles/capabilities, локалізація (uk/en)

Детальніше — `.doc/todo.md`.
