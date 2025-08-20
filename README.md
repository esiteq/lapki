# Lapki - Платформа пошуку та прилаштування тварин для України 🐾

> Українська версія Petfinder.com - система для пошуку безпритульних тварин та з'єднання їх з люблячими сім'ями.

[![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)](https://github.com/esiteq/lapki)
[![License](https://img.shields.io/badge/license-GPL--2.0-green.svg)](LICENSE)
[![WordPress](https://img.shields.io/badge/WordPress-5.0+-blue.svg)](https://wordpress.org)

## 📋 Зміст

- [Огляд проекту](#огляд-проекту)
- [Архітектура](#архітектура)
- [Встановлення](#встановлення)
- [API Документація](#api-документація)
- [Структура БД](#структура-бд)
- [Використання](#використання)
- [Розробка](#розробка)

## 🎯 Огляд проекту

**Lapki** - це WordPress плагін для створення платформи пошуку та прилаштування тварин, інспірований Petfinder.com але адаптований для України та Європи.

### Основний функціонал:
- 🔍 Розширений пошук тварин з фільтрами
- 🏢 Управління притулками та організаціями
- 🌍 Мультимовна підтримка (українська/англійська)
- 📍 Геолокація та пошук за відстанню
- 📱 REST API для мобільних додатків
- 📊 Статистика та аналітика

### Технології:
- **Frontend:** WordPress (поки що), пізніше Node.js
- **Backend:** WordPress + MySQL
- **API:** WordPress REST API
- **Мови:** PHP 7.4+, JavaScript

## 🏗️ Архітектура

```
WordPress (Admin Interface)
    ↓
MySQL Database (wp_lapki_*)
    ↓
PHP Models (Lapki_Animal, Lapki_Organization)
    ↓
REST API (/wp-json/lapki/v1/*)
    ↓
Frontend (React/Vue - майбутнє)
```

### Файлова структура:

```
lapki/
├── lapki.php                 # Головний файл плагіна
├── inc/
│   ├── class-eq-form.php     # Універсальний конструктор форм
│   ├── class-lapki-models.php # Моделі для роботи з БД
│   └── class-lapki-rest-api.php # REST API ендпоінти
├── js/
│   └── lapki-main.js         # JavaScript функціонал
├── css/
│   └── lapki-main.css        # Стилі
├── languages/                # Файли локалізації
└── .doc/
    └── lapki.sql             # Структура БД
```

## 🚀 Встановлення

### Вимоги:
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.7+

### Кроки встановлення:

1. **Завантажити плагін:**
   ```bash
   git clone https://github.com/esiteq/lapki.git
   cd lapki
   ```

2. **Встановити в WordPress:**
   ```bash
   cp -r lapki/ /path/to/wordpress/wp-content/plugins/
   ```

3. **Активувати плагін** в адмін панелі WordPress

4. **Імпортувати структуру БД:**
   ```sql
   -- Виконати .doc/lapki.sql в phpMyAdmin
   ```

5. **Додати тестові дані** (опціонально):
   ```sql
   -- Виконати тестові дані SQL
   ```

6. **Перевірити API:**
   ```bash
   curl "https://yoursite.com/wp-json/lapki/v1/types"
   ```

## 📡 API Документація

### База URL: `/wp-json/lapki/v1/`

### Тварини

#### Пошук тварин
```http
GET /animals
```

**Параметри:**
- `type` - тип тварини (dog, cat, bird...)
- `breed` - порода
- `age` - вік (baby, young, adult, senior)
- `gender` - стать (male, female, unknown)
- `size` - розмір (small, medium, large, xlarge)
- `location` - локація для пошуку
- `distance` - радіус пошуку (км)
- `good_with_children` - підходить для дітей (true/false)
- `spayed_neutered` - стерилізована (true/false)
- `limit` - кількість результатів (макс 100)
- `offset` - зсув для пагінації

**Приклад:**
```bash
curl "https://lapki.esiteq.com/wp-json/lapki/v1/animals?type=cat&size=medium&good_with_children=true"
```

#### Конкретна тварина
```http
GET /animals/{id}
```

#### Створити тварину
```http
POST /animals
```

**Тіло запиту:**
```json
{
    "organization_id": 1,
    "name": "Мурчик",
    "type": "cat",
    "species": "cat",
    "age": "young",
    "gender": "male",
    "size": "medium",
    "description": "Дуже добрий котик"
}
```

### Типи тварин

#### Всі типи
```http
GET /types?lang=uk
```

#### Деталі типу
```http
GET /types/{type}?lang=uk
```

#### Породи типу
```http
GET /types/{type}/breeds?lang=uk
```

### Організації

#### Пошук організацій
```http
GET /organizations
```

#### Конкретна організація
```http
GET /organizations/{id}
```

### Статистика

#### Загальна статистика
```http
GET /stats
```

## 🗄️ Структура БД

### Основні таблиці:

#### `wp_lapki_animals`
Головна таблиця тварин з усіма характеристиками:
- Базова інформація (name, type, species, age, gender, size)
- Породи (breed_primary, breed_secondary, breed_mixed)
- Кольори (color_primary, color_secondary, color_tertiary)
- Медичні дані (spayed_neutered, shots_current, special_needs)
- Сумісність (good_with_children, good_with_dogs, good_with_cats)
- Локація (address_*, latitude, longitude)

#### `wp_lapki_organizations`
Притулки та організації:
- Інформація про організацію (name, type, email, phone)
- Адреса та геолокація
- Зв'язок з WordPress користувачами

#### `wp_lapki_attributes`
Мультимовні атрибути:
- Типи тварин, породи, кольори
- Локалізація (uk/en)
- Гнучка структура для розширення

#### `wp_lapki_media`
Медіафайли (фото/відео):
- Зв'язок з будь-якою сутністю
- Підтримка різних типів медіа
- Сортування та основні фото

#### `wp_lapki_tags`
Теги для пошуку:
- Додаткові характеристики
- Пошукові терміни

### Індекси для продуктивності:
- Композитні індекси для пошуку
- Геолокаційні індекси
- Індекси для фільтрації

## 💻 Використання

### EQ_Form - Універсальний конструктор форм

```php
// Форма для WordPress опцій
$form = EQ_Form_Builder::options('lapki_settings');
$form->add_field(['id' => 'api_key', 'label' => 'API ключ']);
$form->display();

// Форма для кастомної таблиці
$form = EQ_Form_Builder::table('lapki_animals', 123);
$form->add_field(['id' => 'name', 'label' => 'Кличка', 'required' => true]);
$form->display();

// Змішана форма (різні джерела даних)
$form = EQ_Form_Builder::mixed();
$form->add_field(['id' => 'title', 'save_to' => 'post:post_title']);
$form->add_field(['id' => 'meta', 'save_to' => 'meta:custom_field']);
$form->add_field(['id' => 'animal_name', 'save_to' => 'table:name']);
$form->display();
```

### Моделі для роботи з даними

```php
// Пошук тварин
$animals = Lapki_Animal::search([
    'type' => 'dog',
    'size' => 'medium',
    'good_with_children' => true,
    'limit' => 20
]);

// Отримати конкретну тварину
$animal = Lapki_Animal::get(123);

// Створити тварину
$animal_id = Lapki_Animal::create([
    'name' => 'Бобік',
    'type' => 'dog',
    'organization_id' => 1
]);

// Пошук організацій
$orgs = Lapki_Organization::search(['type' => 'shelter']);

// Отримати атрибути
$breeds = Lapki_Attributes::get_breeds_by_type('dog', 'uk');
```

## 🛠️ Розробка

### Налаштування середовища:

1. **Локальна розробка:**
   ```bash
   # WordPress + плагін
   # MySQL база даних
   # PHP 7.4+
   ```

2. **Тестування API:**
   ```bash
   # Встановити Postman або використовувати curl
   curl -X GET "https://lapki.esiteq.com/wp-json/lapki/v1/animals"
   ```

### Додавання нових полів:

1. **Оновити структуру БД:**
   ```sql
   ALTER TABLE wp_lapki_animals ADD COLUMN new_field VARCHAR(255);
   ```

2. **Оновити модель:**
   ```php
   // В Lapki_Animal::search() додати новий фільтр
   if (!empty($params['new_field'])) {
       $where_clauses[] = "a.new_field = %s";
       $sql_params[] = $params['new_field'];
   }
   ```

3. **Оновити API:**
   ```php
   // В get_animals_search_args() додати новий параметр
   'new_field' => [
       'type' => 'string',
       'description' => 'Опис нового поля'
   ]
   ```

### Додавання нових ендпоінтів:

```php
// В Lapki_REST_API::register_routes()
register_rest_route($namespace, '/custom-endpoint', [
    'methods' => WP_REST_Server::READABLE,
    'callback' => [__CLASS__, 'custom_endpoint'],
    'permission_callback' => '__return_true'
]);
```

### Конфігурація для різних середовищ:

```php
// Розробка (відключена авторизація)
'permission_callback' => '__return_true'

// Продакшн (увімкнута авторизація)
'permission_callback' => [__CLASS__, 'check_permission']
```

## 🌍 Мультимовність

### Підтримувані мови:
- `uk` - українська (за замовчуванням)
- `en` - англійська

### Використання:
```bash
# Українська
curl "https://lapki.esiteq.com/wp-json/lapki/v1/types?lang=uk"

# Англійська  
curl "https://lapki.esiteq.com/wp-json/lapki/v1/types?lang=en"
```

### Додавання нової мови:

1. **Оновити константу:**
   ```php
   const SUPPORTED_LANGS = ['uk', 'en', 'pl']; // додали польську
   ```

2. **Додати переклади в БД:**
   ```sql
   INSERT INTO wp_lapki_attributes (entity, entity_type, attr_name, attr_value, attr_display, lang) 
   VALUES ('animal', 'dog', 'gender', 'male', 'Mężczyzna', 'pl');
   ```

## 🔒 Безпека

### Поточний стан:
- ⚠️ Авторизація відключена для тестування
- ✅ Санітизація вводу через WordPress функції
- ✅ Prepared statements для БД запитів
- ✅ Nonce захист форм (відключений для тестування)

### Продакшн налаштування:
```php
// Увімкнути авторизацію
'permission_callback' => [__CLASS__, 'check_permission']

// Увімкнути nonce перевірку
if (!$this->verify_nonce()) {
    return false;
}
```

## 📊 Продуктивність

### Оптимізації БД:
- Композитні індекси для популярних запитів
- Геопросторові індекси для location-based пошуку
- Кешування результатів (планується Redis)

### API оптимізації:
- Пагінація (макс 100 записів за запит)
- Мінімальні дані в списках, повні в деталях
- ETags для кешування (планується)

## 🚧 Roadmap

### Поточна версія (2.0.0):
- ✅ WordPress плагін з REST API
- ✅ Базовий пошук та фільтрація
- ✅ Мультимовність
- ✅ Адмін інтерфейс через EQ_Form

### Наступні версії:
- 🔄 Node.js бекенд для API
- 🔄 React фронтенд
- 🔄 Redis кешування
- 🔄 Мобільний додаток
- 🔄 Інтеграція з картами
- 🔄 Email нотифікації
- 🔄 Платіжна система

## 🤝 Контрибьюція

### Як допомогти:
1. Форкнути репозиторій
2. Створити feature гілку
3. Зробити зміни
4. Написати тести
5. Створити Pull Request

### Кодстайл:
- WordPress Coding Standards
- PHPDoc коментарі
- Осмислені назви змінних
- Україномовні коментарі в коді

## 📞 Контакти

- **Веб-сайт:** [lapki.esiteq.com](https://lapki.esiteq.com)
- **Email:** info@esiteq.com
- **GitHub:** [github.com/esiteq/lapki](https://github.com/esiteq/lapki)

---

**Made with ❤️ for Ukrainian animals** 🇺🇦🐾