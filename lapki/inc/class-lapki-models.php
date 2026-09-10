<?php

/**
 * Lapki Models - Класи для роботи з базою даних
 * 
 * @package Lapki
 * @author Oleksii Bugrov
 */

// Базовий клас для всіх моделей
abstract class Lapki_Model {
    protected static $table_name = '';
    
    protected static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . static::$table_name;
    }
    
    protected static function prepare_data($data, $format = []) {
        $prepared = [];
        foreach ($data as $key => $value) {
            if ($value !== null) {
                $prepared[$key] = $value;
            }
        }
        return $prepared;
    }
}

/**
 * Клас для роботи з тваринами
 */
class Lapki_Animal extends Lapki_Model {
    protected static $table_name = 'lapki_animals';

    const LOCATIONS_CACHE_KEY = 'lapki_locations_cache';
    const LOCATIONS_CACHE_TTL = 12 * HOUR_IN_SECONDS;

    /**
     * Отримати тварину за ID
     */
    public static function get($id) {
        global $wpdb;
        
        $sql = "SELECT a.*, o.name as organization_name, o.type as organization_type 
                FROM " . self::get_table_name() . " a 
                LEFT JOIN " . Lapki_Organization::get_table_name() . " o ON a.organization_id = o.id 
                WHERE a.id = %d";
        
        $animal = $wpdb->get_row($wpdb->prepare($sql, $id), ARRAY_A);

        if ($animal) {
            // Додаємо медіафайли
            $animal['media'] = Lapki_Media::get_by_entity('animal', $id);
            // Додаємо теги
            $animal['tags'] = Lapki_Tag::get_by_entity('animal', $id);
            $animal = self::decode_additional_attributes($animal);
        }

        return $animal;
    }

    /**
     * "Додаткова інформація" — значення attr_name з довідника атрибутів,
     * яких немає серед стандартних полів тварини (breed/age/gender/size/coat/
     * color/status/species). Зберігається одним JSON-стовпцем `additional_attributes`,
     * бо ці атрибути динамічні (задаються через редактор атрибутів в адмінці) і не
     * мають власних колонок у таблиці.
     */
    private static function decode_additional_attributes($animal) {
        if (!empty($animal['additional_attributes']) && is_string($animal['additional_attributes'])) {
            $decoded = json_decode($animal['additional_attributes'], true);
            $animal['additional_attributes'] = is_array($decoded) ? $decoded : [];
        } else {
            $animal['additional_attributes'] = [];
        }
        return $animal;
    }
    
    /**
     * Пошук тварин з фільтрами
     */
    public static function search($params = []) {
        global $wpdb;
        
        $defaults = [
            'type' => '',
            'species' => '',
            'breed' => '',
            'age' => '',
            'gender' => '',
            'size' => '',
            'status' => '',
            'location' => '',
            'distance' => 50,
            'latitude' => null,
            'longitude' => null,
            'good_with_children' => null,
            'good_with_dogs' => null,
            'good_with_cats' => null,
            'spayed_neutered' => null,
            'special_needs' => null,
            'organization_id' => null,
            'search' => '',
            'limit' => 20,
            'offset' => 0,
            'order_by' => 'published_at',
            'order' => 'DESC'
        ];
        
        $params = wp_parse_args($params, $defaults);
        
        $where_clauses = [];
        $sql_params = [];
        
        // Базова таблиця
        $sql = "SELECT a.*, o.name as organization_name, o.type as organization_type";
        
        // Додаємо відстань якщо є координати
        if ($params['latitude'] && $params['longitude']) {
            $sql .= ", (6371 * acos(cos(radians(%f)) * cos(radians(a.latitude)) * 
                     cos(radians(a.longitude) - radians(%f)) + sin(radians(%f)) * 
                     sin(radians(a.latitude)))) as distance";
            array_unshift($sql_params, $params['latitude'], $params['longitude'], $params['latitude']);
        }
        
        $sql .= " FROM " . self::get_table_name() . " a 
                  LEFT JOIN " . Lapki_Organization::get_table_name() . " o ON a.organization_id = o.id";
        
        // Фільтри
        if (!empty($params['type'])) {
            $where_clauses[] = "a.type = %s";
            $sql_params[] = $params['type'];
        }
        
        if (!empty($params['species'])) {
            $where_clauses[] = "a.species = %s";
            $sql_params[] = $params['species'];
        }
        
        if (!empty($params['breed'])) {
            $where_clauses[] = "(a.breed_primary = %s OR a.breed_secondary = %s)";
            $sql_params[] = $params['breed'];
            $sql_params[] = $params['breed'];
        }
        
        if (!empty($params['age'])) {
            $where_clauses[] = "a.age = %s";
            $sql_params[] = $params['age'];
        }
        
        if (!empty($params['gender'])) {
            $where_clauses[] = "a.gender = %s";
            $sql_params[] = $params['gender'];
        }
        
        if (!empty($params['size'])) {
            $where_clauses[] = "a.size = %s";
            $sql_params[] = $params['size'];
        }
        
        if (!empty($params['status'])) {
            $where_clauses[] = "a.status = %s";
            $sql_params[] = $params['status'];
        }
        
        if (!empty($params['organization_id'])) {
            $where_clauses[] = "a.organization_id = %d";
            $sql_params[] = $params['organization_id'];
        }
        
        // Булеві фільтри
        $boolean_filters = ['good_with_children', 'good_with_dogs', 'good_with_cats', 'spayed_neutered', 'special_needs'];
        foreach ($boolean_filters as $filter) {
            if ($params[$filter] !== null) {
                $where_clauses[] = "a.{$filter} = %d";
                $sql_params[] = (int)$params[$filter];
            }
        }
        
        // Пошук за кличкою
        if (!empty($params['search'])) {
            $where_clauses[] = "a.name LIKE %s";
            $sql_params[] = '%' . $wpdb->esc_like($params['search']) . '%';
        }

        // Локація (текст) та/або радіус від гео-координат — при наявності
        // обох об'єднуємо через OR: гео-радіус ДОПОВНЮЄ пошук за назвою
        // міста ("також показувати тварин в радіусі"), а не звужує його ще
        // сильніше — інакше типовий кейс "місто Х" + увімкнений гео-чекбокс,
        // де реальне розташування користувача не збігається з цим містом,
        // завжди повертав би 0 результатів.
        $location_or_parts = [];

        if (!empty($params['location'])) {
            $location_or_parts[] = "(a.address_city LIKE %s OR a.address_state LIKE %s)";
            $location_param = '%' . $params['location'] . '%';
            $sql_params[] = $location_param;
            $sql_params[] = $location_param;
        }

        if ($params['latitude'] && $params['longitude'] && $params['distance']) {
            $location_or_parts[] = "(6371 * acos(cos(radians(%f)) * cos(radians(a.latitude)) *
                              cos(radians(a.longitude) - radians(%f)) + sin(radians(%f)) *
                              sin(radians(a.latitude)))) <= %d";
            $sql_params[] = $params['latitude'];
            $sql_params[] = $params['longitude'];
            $sql_params[] = $params['latitude'];
            $sql_params[] = $params['distance'];
        }

        if (!empty($location_or_parts)) {
            $where_clauses[] = '(' . implode(' OR ', $location_or_parts) . ')';
        }

        // Додаємо WHERE
        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        // Сортування
        $allowed_order_by = ['published_at', 'name', 'age', 'distance', 'updated_at'];
        $order_by = in_array($params['order_by'], $allowed_order_by) ? $params['order_by'] : 'published_at';
        $order = strtoupper($params['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        if ($order_by === 'distance' && (!$params['latitude'] || !$params['longitude'])) {
            $order_by = 'published_at';
        }
        
        $sql .= " ORDER BY {$order_by} {$order}";
        
        // Пагінація
        $sql .= " LIMIT %d OFFSET %d";
        $sql_params[] = (int)$params['limit'];
        $sql_params[] = (int)$params['offset'];
        
        $prepared_sql = $wpdb->prepare($sql, $sql_params);
        $results = $wpdb->get_results($prepared_sql, ARRAY_A);
        
        // Додаємо медіа для кожної тварини
        foreach ($results as &$animal) {
            $animal['primary_photo'] = Lapki_Media::get_primary_photo('animal', $animal['id']);
            $animal = self::decode_additional_attributes($animal);
        }
        unset($animal);

        return $results;
    }
    
    /**
     * Підрахувати кількість тварин з фільтрами
     */
    public static function count($params = []) {
        global $wpdb;

        $defaults = [
            'type' => '', 'species' => '', 'breed' => '', 'age' => '',
            'gender' => '', 'size' => '', 'status' => '', 'location' => '',
            'latitude' => null, 'longitude' => null, 'distance' => 50,
            'good_with_children' => null, 'good_with_dogs' => null,
            'good_with_cats' => null, 'spayed_neutered' => null,
            'special_needs' => null, 'organization_id' => null, 'search' => ''
        ];

        $params = wp_parse_args($params, $defaults);

        $where_clauses = [];
        $sql_params = [];

        if (!empty($params['type']))            { $where_clauses[] = "a.type = %s";            $sql_params[] = $params['type']; }
        if (!empty($params['species']))         { $where_clauses[] = "a.species = %s";         $sql_params[] = $params['species']; }
        if (!empty($params['age']))             { $where_clauses[] = "a.age = %s";             $sql_params[] = $params['age']; }
        if (!empty($params['gender']))          { $where_clauses[] = "a.gender = %s";          $sql_params[] = $params['gender']; }
        if (!empty($params['size']))            { $where_clauses[] = "a.size = %s";            $sql_params[] = $params['size']; }
        if (!empty($params['status']))          { $where_clauses[] = "a.status = %s";          $sql_params[] = $params['status']; }
        if (!empty($params['organization_id'])) { $where_clauses[] = "a.organization_id = %d"; $sql_params[] = $params['organization_id']; }

        if (!empty($params['breed'])) {
            $where_clauses[] = "(a.breed_primary = %s OR a.breed_secondary = %s)";
            $sql_params[] = $params['breed'];
            $sql_params[] = $params['breed'];
        }

        if (!empty($params['search'])) {
            $where_clauses[] = "a.name LIKE %s";
            $sql_params[] = '%' . $wpdb->esc_like($params['search']) . '%';
        }

        foreach (['good_with_children', 'good_with_dogs', 'good_with_cats', 'spayed_neutered', 'special_needs'] as $f) {
            if ($params[$f] !== null) { $where_clauses[] = "a.{$f} = %d"; $sql_params[] = (int)$params[$f]; }
        }

        // Локація (текст) та/або радіус — те саме об'єднання через OR, що й
        // у search() (гео-радіус доповнює пошук за містом, а не звужує).
        // Обидві половини мають лишатись поруч (без коду між ними, що
        // дописує $sql_params) — інакше порядок параметрів розійдеться з
        // порядком плейсхолдерів у зібраному запиті.
        $location_or_parts = [];

        if (!empty($params['location'])) {
            $location_or_parts[] = "(a.address_city LIKE %s OR a.address_state LIKE %s)";
            $loc = '%' . $params['location'] . '%';
            $sql_params[] = $loc;
            $sql_params[] = $loc;
        }

        if ($params['latitude'] && $params['longitude'] && $params['distance']) {
            $location_or_parts[] = "(6371 * acos(cos(radians(%f)) * cos(radians(a.latitude)) * cos(radians(a.longitude) - radians(%f)) + sin(radians(%f)) * sin(radians(a.latitude)))) <= %d";
            $sql_params[] = $params['latitude'];
            $sql_params[] = $params['longitude'];
            $sql_params[] = $params['latitude'];
            $sql_params[] = $params['distance'];
        }

        if (!empty($location_or_parts)) {
            $where_clauses[] = '(' . implode(' OR ', $location_or_parts) . ')';
        }

        $sql = "SELECT COUNT(*) FROM " . self::get_table_name() . " a";
        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }

        if (!empty($sql_params)) {
            return (int) $wpdb->get_var($wpdb->prepare($sql, $sql_params));
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Якщо задано address_city_katottg — підтягнути з довідника wp_lapki_geo
     * координати (якщо їх немає в даних), область і готовий підпис
     * місцезнаходження (address_city_display — "Запоріжжя" для обласного
     * центру, "Привільне, Запорізька область, Широківська громада" для
     * звичайного села, див. Lapki_Main::format_city_location_from_geo()), і
     * записати прямо в рядок тварини — а не JOIN/повторне обчислення на
     * кожен показ картки чи кожен пошуковий запит (радіус-пошук і так уже
     * читає latitude/longitude напряму з wp_lapki_animals, без JOIN).
     * Використовується лише для ВІДОБРАЖЕННЯ — пошук і надалі йде за
     * address_city_katottg (точний код), не за цим кешованим текстом.
     *
     * Ручні координати (наприклад, точна мітка на карті в адмінці) мають
     * пріоритет і не перезаписуються. Довідник покриває 99.997% населених
     * пунктів координатами (див. .doc/geo.md) — якщо координат для цього
     * КАТОТТГ немає (1 виняток на всю Україну), поле просто лишається
     * порожнім.
     *
     * address_state і address_city_display, на відміну від координат,
     * синхронізуються завжди, коли є katottg — сам населений пункт
     * обирається зі списку .lapki-city-field (katottg — актуальний
     * офіційний код, унікальний ключ довідника; КОАТУУ скасовано 2020 року
     * й ніде в застосунку більше не використовується), тож кешовані поля не
     * повинні лишатись застарілими після зміни міста.
     */
    private static function maybe_fill_geo_from_city($data) {
        if (empty($data['address_city_katottg'])) {
            return $data;
        }

        $geo = Lapki_Geo::get_by_katottg_code($data['address_city_katottg']);
        if (!$geo) {
            return $data;
        }

        if (empty($data['latitude']) && empty($data['longitude']) && $geo['latitude'] !== null && $geo['longitude'] !== null) {
            $data['latitude'] = $geo['latitude'];
            $data['longitude'] = $geo['longitude'];
        }

        if (!empty($geo['oblast'])) {
            $data['address_state'] = $geo['oblast'];
        }

        $data['address_city_display'] = Lapki_Main::format_city_location_from_geo($geo);

        return $data;
    }

    /**
     * Створити нову тварину
     */
    public static function create($data) {
        global $wpdb;
        
        $defaults = [
            'organization_id' => 0,
            'name' => '',
            'type' => '',
            'species' => '',
            'status' => 'adoptable',
            'age' => '',
            'gender' => '',
            'size' => '',
            'address_country' => 'UA',
            'published_at' => current_time('mysql'),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        $data = wp_parse_args($data, $defaults);
        $data = self::maybe_fill_geo_from_city($data);
        $data = self::prepare_data($data);

        $result = $wpdb->insert(
            self::get_table_name(),
            $data,
            self::get_format_array($data)
        );

        if ($result !== false && !empty($data['address_city'])) {
            self::maybe_bust_locations_cache($data['address_city']);
        }

        return $result !== false ? $wpdb->insert_id : false;
    }
    
    /**
     * Оновити тварину
     */
    public static function update($id, $data) {
        global $wpdb;

        $data['updated_at'] = current_time('mysql');
        $data = self::maybe_fill_geo_from_city($data);
        $data = self::prepare_data($data);

        $result = $wpdb->update(
            self::get_table_name(),
            $data,
            ['id' => $id],
            self::get_format_array($data),
            ['%d']
        );

        if ($result !== false && !empty($data['address_city'])) {
            self::maybe_bust_locations_cache($data['address_city']);
        }

        return $result;
    }
    
    /**
     * Видалити тварину
     */
    public static function delete($id) {
        global $wpdb;
        
        // Спочатку видаляємо медіа та теги
        Lapki_Media::delete_by_entity('animal', $id);
        Lapki_Tag::delete_by_entity('animal', $id);
        
        return $wpdb->delete(
            self::get_table_name(),
            ['id' => $id],
            ['%d']
        );
    }
    
    /**
     * Отримати статистику
     */
    public static function get_stats() {
        global $wpdb;
        
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'adoptable' THEN 1 ELSE 0 END) as adoptable,
                    SUM(CASE WHEN status = 'adopted' THEN 1 ELSE 0 END) as adopted,
                    SUM(CASE WHEN type = 'dog' THEN 1 ELSE 0 END) as dogs,
                    SUM(CASE WHEN type = 'cat' THEN 1 ELSE 0 END) as cats
                FROM " . self::get_table_name();
        
        return $wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * Кількість тварин по типу (виду) — для сторінки статистики.
     * Повертає [['type' => 'cat', 'cnt' => 22], ...], відсортовано за спаданням.
     */
    public static function count_by_type() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT type, COUNT(*) as cnt FROM " . self::get_table_name() . "
             GROUP BY type ORDER BY cnt DESC",
            ARRAY_A
        );
    }

    /**
     * Кількість тварин, доданих у діапазоні дат [$from, $to] включно
     * (формат 'Y-m-d', порівняння за DATE(created_at)).
     */
    public static function count_added_between($from, $to) {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::get_table_name() . "
             WHERE DATE(created_at) BETWEEN %s AND %s",
            $from,
            $to
        ));
    }

    /**
     * Підказки міст для автодоповнення пошуку.
     * Фільтрація йде по кешованому повному списку міст (transient), а не окремим
     * SQL-запитом на кожен запит користувача — див. get_all_locations().
     */
    public static function search_locations($query, $limit = 10) {
        $query = mb_strtolower($query, 'UTF-8');
        $matches = [];

        foreach (self::get_all_locations() as $city) {
            if (mb_strpos(mb_strtolower($city, 'UTF-8'), $query) !== false) {
                $matches[] = $city;
                if (count($matches) >= $limit) {
                    break;
                }
            }
        }

        return $matches;
    }

    /**
     * Повний список унікальних міст (з кешу; SQL-запит виконується лише
     * при "холодному" кеші — після TTL або явного скидання).
     */
    public static function get_all_locations() {
        $cached = get_transient(self::LOCATIONS_CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        global $wpdb;

        $cities = $wpdb->get_col(
            "SELECT DISTINCT address_city
             FROM " . self::get_table_name() . "
             WHERE address_city IS NOT NULL AND address_city != ''
             ORDER BY address_city ASC"
        );

        set_transient(self::LOCATIONS_CACHE_KEY, $cities, self::LOCATIONS_CACHE_TTL);

        return $cities;
    }

    /**
     * Скидає кеш міст, але тільки якщо перелічене місто дійсно нове —
     * додавання тварини в уже відоме місто не має сенсу перебудовувати кеш.
     */
    private static function maybe_bust_locations_cache($city) {
        $city = trim((string) $city);
        if ($city === '') {
            return;
        }

        $cached = get_transient(self::LOCATIONS_CACHE_KEY);
        if (!is_array($cached)) {
            // Кеш і так холодний — наступне читання підхопить нове місто без скидання
            return;
        }

        $known = array_map('mb_strtolower', $cached);
        if (!in_array(mb_strtolower($city), $known, true)) {
            delete_transient(self::LOCATIONS_CACHE_KEY);
        }
    }

    private static function get_format_array($data) {
        $format = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ['id', 'organization_id', 'created_by_user_id'])) {
                $format[] = '%d';
            } elseif (in_array($key, ['latitude', 'longitude'])) {
                $format[] = '%f';
            } elseif (is_bool($value) || in_array($key, ['spayed_neutered', 'house_trained', 'declawed', 'special_needs', 'shots_current', 'good_with_children', 'good_with_dogs', 'good_with_cats', 'breed_mixed', 'breed_unknown'])) {
                $format[] = '%d';
            } else {
                $format[] = '%s';
            }
        }
        return $format;
    }
}

/**
 * Клас для роботи з організаціями
 */
class Lapki_Organization extends Lapki_Model {
    protected static $table_name = 'lapki_organizations';
    
    public static function get($id) {
        global $wpdb;
        
        $organization = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::get_table_name() . " WHERE id = %d",
            $id
        ), ARRAY_A);
        
        if ($organization) {
            // Додаємо медіафайли
            $organization['media'] = Lapki_Media::get_by_entity('organization', $id);
            $organization['primary_photo'] = Lapki_Media::get_primary_photo('organization', $id);
            // Додаємо кількість тварин
            $organization['animals_count'] = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM " . Lapki_Animal::get_table_name() . " WHERE organization_id = %d AND status = 'adoptable'",
                $id
            ));
        }
        
        return $organization;
    }
    
    public static function search($params = []) {
        global $wpdb;
        
        $defaults = [
            'name' => '',
            'type' => '',
            'location' => '',
            'state' => '',
            'city' => '',
            'verified_only' => false,
            'limit' => 20,
            'offset' => 0
        ];
        
        $params = wp_parse_args($params, $defaults);
        
        $where_clauses = [];
        $sql_params = [];
        
        $sql = "SELECT *, (SELECT COUNT(*) FROM " . Lapki_Animal::get_table_name() . " 
                WHERE organization_id = o.id AND status = 'adoptable') as animals_count 
                FROM " . self::get_table_name() . " o";
        
        if (!empty($params['name'])) {
            $where_clauses[] = "name LIKE %s";
            $sql_params[] = '%' . $params['name'] . '%';
        }
        
        if (!empty($params['type'])) {
            $where_clauses[] = "type = %s";
            $sql_params[] = $params['type'];
        }
        
        if (!empty($params['state'])) {
            $where_clauses[] = "state = %s";
            $sql_params[] = $params['state'];
        }
        
        if (!empty($params['city'])) {
            $where_clauses[] = "city = %s";
            $sql_params[] = $params['city'];
        }
        
        if (!empty($params['location'])) {
            $where_clauses[] = "(city LIKE %s OR state LIKE %s OR address1 LIKE %s)";
            $location_param = '%' . $params['location'] . '%';
            $sql_params[] = $location_param;
            $sql_params[] = $location_param;
            $sql_params[] = $location_param;
        }
        
        if ($params['verified_only']) {
            $where_clauses[] = "is_verified = 1";
        }
        
        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        $sql .= " ORDER BY name ASC";
        $sql .= " LIMIT %d OFFSET %d";
        $sql_params[] = (int)$params['limit'];
        $sql_params[] = (int)$params['offset'];

        $organizations = $wpdb->get_results($wpdb->prepare($sql, $sql_params), ARRAY_A);

        foreach ($organizations as &$organization) {
            $organization['primary_photo'] = Lapki_Media::get_primary_photo('organization', $organization['id']);
        }

        return $organizations;
    }

    /**
     * Міста, в яких є організації, з кількістю організацій у кожному
     * (для рядка фільтрів-боксів над списком організацій)
     */
    public static function get_cities_with_counts() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT city, COUNT(*) as count FROM " . self::get_table_name() . "
             WHERE city IS NOT NULL AND city != ''
             GROUP BY city
             ORDER BY count DESC, city ASC",
            ARRAY_A
        );
    }

    /**
     * Те саме, що Lapki_Animal::maybe_fill_geo_from_city() — координати
     * (якщо їх немає), область (завжди) і кешований підпис місцезнаходження
     * (city_display) з довідника wp_lapki_geo за katottg. Поле міста тут
     * зветься city_katottg/state/city_display, а не
     * address_city_katottg/address_state/address_city_display.
     * Ручні координати мають пріоритет і не перезаписуються.
     */
    private static function maybe_fill_geo_from_city($data) {
        if (empty($data['city_katottg'])) {
            return $data;
        }

        $geo = Lapki_Geo::get_by_katottg_code($data['city_katottg']);
        if (!$geo) {
            return $data;
        }

        if (empty($data['latitude']) && empty($data['longitude']) && $geo['latitude'] !== null && $geo['longitude'] !== null) {
            $data['latitude'] = $geo['latitude'];
            $data['longitude'] = $geo['longitude'];
        }

        if (!empty($geo['oblast'])) {
            $data['state'] = $geo['oblast'];
        }

        $data['city_display'] = Lapki_Main::format_city_location_from_geo($geo);

        return $data;
    }

    public static function create($data) {
        global $wpdb;

        $defaults = [
            'type' => 'individual',
            'country' => 'UA',
            'is_verified' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        $data = wp_parse_args($data, $defaults);
        $data = self::maybe_fill_geo_from_city($data);
        $data = self::prepare_data($data);

        $result = $wpdb->insert(
            self::get_table_name(),
            $data
        );

        return $result !== false ? $wpdb->insert_id : false;
    }

    public static function update($id, $data) {
        global $wpdb;

        $data['updated_at'] = current_time('mysql');
        $data = self::maybe_fill_geo_from_city($data);
        $data = self::prepare_data($data);
        unset($data['id']);

        $result = $wpdb->update(
            self::get_table_name(),
            $data,
            ['id' => $id]
        );

        return $result !== false;
    }

    public static function delete($id) {
        global $wpdb;

        return $wpdb->delete(self::get_table_name(), ['id' => $id]) !== false;
    }

    /**
     * Організації, до яких ПІДТВЕРДЖЕНО прив'язаний WP-користувач (власник
     * або член) — через таблицю членства, не через legacy-колонку wp_user_id.
     * Користувач тепер може належати до кількох організацій одночасно, тож
     * масив може містити більше одного елемента. Заявки, що очікують
     * підтвердження (status='pending'), сюди навмисно не потрапляють —
     * інакше користувач отримав би доступ (напр. до заявок на усиновлення
     * організації) ще до схвалення власником.
     *
     * @param int $wp_user_id
     * @return array
     */
    public static function get_by_wp_user_id($wp_user_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT o.* FROM " . self::get_table_name() . " o
             JOIN " . Lapki_Organization_Member::get_table_name() . " m ON m.organization_id = o.id
             WHERE m.wp_user_id = %d AND m.status = %s",
            $wp_user_id,
            Lapki_Organization_Member::STATUS_APPROVED
        ), ARRAY_A);
    }

    /**
     * Перевірити, чи належить організація вказаному WP-користувачу
     * (через членство — власник або учасник, будь-яка роль)
     *
     * @param int $organization_id
     * @param int $wp_user_id
     * @return bool
     */
    public static function belongs_to_user($organization_id, $wp_user_id) {
        return Lapki_Organization_Member::get_role($organization_id, $wp_user_id) !== null;
    }
}

/**
 * Членство користувачів в організаціях (many-to-many: одна організація —
 * багато користувачів; один користувач може бути учасником кількох
 * організацій одночасно). Приєднання до вже існуючої організації йде через
 * заявку (status='pending'), яку підтверджує чи відхиляє власник —
 * реєстрація власної нової організації дає membership одразу 'approved'.
 */
class Lapki_Organization_Member extends Lapki_Model {
    protected static $table_name = 'lapki_organization_members';

    const ROLE_OWNER = 'owner';
    const ROLE_MEMBER = 'member';

    const STATUS_APPROVED = 'approved';
    const STATUS_PENDING = 'pending';

    /**
     * Усі організації користувача (з даними організації), опційно
     * відфільтровані за статусом. Один користувач тепер може мати кілька
     * рядків одночасно (на відміну від старого get_by_user(), що повертав одну).
     */
    public static function get_all_by_user($wp_user_id, $status = null) {
        global $wpdb;

        $sql = "SELECT m.*, o.name as organization_name, o.type as organization_type
                FROM " . self::get_table_name() . " m
                JOIN " . Lapki_Organization::get_table_name() . " o ON o.id = m.organization_id
                WHERE m.wp_user_id = %d";
        $params = [$wp_user_id];

        if ($status !== null) {
            $sql .= " AND m.status = %s";
            $params[] = $status;
        }

        $sql .= " ORDER BY m.created_at ASC";

        return $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
    }

    /**
     * Рядок членства (будь-якого статусу) для конкретної пари
     * організація+користувач, або null. Для дедуплікації заявок — не можна
     * подати другу заявку в ту саму організацію, поки перша не скасована.
     */
    public static function get_membership($organization_id, $wp_user_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::get_table_name() . " WHERE organization_id = %d AND wp_user_id = %d",
            $organization_id,
            $wp_user_id
        ), ARRAY_A);
    }

    /**
     * Роль користувача в конкретній організації ('owner'/'member') або null,
     * якщо не є ПІДТВЕРДЖЕНИМ учасником — заявка, що очікує підтвердження,
     * жодних прав керування не дає.
     */
    public static function get_role($organization_id, $wp_user_id) {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT role FROM " . self::get_table_name() . " WHERE organization_id = %d AND wp_user_id = %d AND status = %s",
            $organization_id,
            $wp_user_id,
            self::STATUS_APPROVED
        ));
    }

    /**
     * Учасники організації (з іменами користувачів), за замовчуванням лише підтверджені
     */
    public static function get_members($organization_id, $status = self::STATUS_APPROVED) {
        global $wpdb;

        $sql = "SELECT m.*, u.display_name, u.user_email
                FROM " . self::get_table_name() . " m
                JOIN {$wpdb->users} u ON u.ID = m.wp_user_id
                WHERE m.organization_id = %d";
        $params = [$organization_id];

        if ($status !== null) {
            $sql .= " AND m.status = %s";
            $params[] = $status;
        }

        $sql .= " ORDER BY m.role ASC, m.created_at ASC";

        return $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
    }

    /**
     * Заявки на приєднання, що очікують підтвердження власником організації
     */
    public static function get_pending_requests($organization_id) {
        return self::get_members($organization_id, self::STATUS_PENDING);
    }

    /**
     * Приєднати користувача до організації — 'approved' одразу (власник щойно
     * створеної організації) або 'pending' (заявка на приєднання до чужої,
     * очікує підтвердження). Повертає false, якщо для цієї пари
     * організація+користувач вже є рядок (будь-якого статусу) — не блокує
     * заявки в ІНШІ організації.
     */
    public static function join($organization_id, $wp_user_id, $role = self::ROLE_MEMBER, $status = self::STATUS_APPROVED) {
        global $wpdb;

        if (self::get_membership($organization_id, $wp_user_id)) {
            return false;
        }

        $result = $wpdb->insert(self::get_table_name(), [
            'organization_id' => $organization_id,
            'wp_user_id' => $wp_user_id,
            'role' => $role,
            'status' => $status,
            'created_at' => current_time('mysql'),
        ]);

        return $result !== false;
    }

    /**
     * Підтвердити заявку на приєднання (лише власник організації) —
     * pending → approved, і піднімає WP-роль заявника до волонтера, якщо в
     * нього ще немає жодної ролі керування тваринами.
     */
    public static function approve_request($organization_id, $wp_user_id) {
        global $wpdb;

        $updated = $wpdb->update(
            self::get_table_name(),
            ['status' => self::STATUS_APPROVED],
            ['organization_id' => $organization_id, 'wp_user_id' => $wp_user_id, 'status' => self::STATUS_PENDING]
        );

        if (!$updated) {
            return false;
        }

        $user = get_userdata($wp_user_id);
        if ($user && !array_intersect([Lapki_Roles::ROLE_SHELTER_ADMIN, Lapki_Roles::ROLE_VOLUNTEER, 'administrator'], $user->roles)) {
            $user->set_role(Lapki_Roles::ROLE_VOLUNTEER);
        }

        return true;
    }

    /**
     * Відхилити заявку на приєднання (лише власник організації) — рядок
     * видаляється, заявник може подати нову заявку пізніше.
     */
    public static function reject_request($organization_id, $wp_user_id) {
        global $wpdb;

        return $wpdb->delete(self::get_table_name(), [
            'organization_id' => $organization_id,
            'wp_user_id' => $wp_user_id,
            'status' => self::STATUS_PENDING,
        ]) !== false;
    }

    /**
     * Гарантувати, що користувач має організацію — додавати тварину може
     * будь-хто залогінений, а не лише зареєстровані притулки/ГО. Якщо
     * підтвердженого членства ще немає, автоматично створює мінімальну
     * організацію типу 'individual' з даних акаунта (ім'я, email, телефон) і
     * робить користувача її власником — той самий шлях, що й самостійна
     * реєстрація організації через POST /organizations, просто без ручного
     * кроку користувача.
     */
    public static function ensure_membership($wp_user_id) {
        $existing = self::get_all_by_user($wp_user_id, self::STATUS_APPROVED);
        if (!empty($existing)) {
            return $existing[0];
        }

        $user = get_userdata($wp_user_id);
        if (!$user) {
            return null;
        }

        $name = trim($user->first_name . ' ' . $user->last_name);
        if (empty($name)) {
            $name = $user->display_name;
        }

        $org_id = Lapki_Organization::create([
            'name' => $name,
            'type' => 'individual',
            'email' => $user->user_email,
            'phone' => get_user_meta($wp_user_id, 'lapki_phone', true),
            'wp_user_id' => $wp_user_id,
        ]);

        if (!$org_id || !self::join($org_id, $wp_user_id, self::ROLE_OWNER, self::STATUS_APPROVED)) {
            return null;
        }

        if (!in_array('administrator', $user->roles, true)) {
            $user->set_role(Lapki_Roles::ROLE_SHELTER_ADMIN);
        }

        $memberships = self::get_all_by_user($wp_user_id, self::STATUS_APPROVED);
        return $memberships[0] ?? null;
    }

    /**
     * Прибрати користувача з КОНКРЕТНОЇ організації — незалежно від статусу
     * (працює і для скасування власної заявки, що очікує підтвердження, і
     * для виходу з активного членства).
     */
    public static function leave($organization_id, $wp_user_id) {
        global $wpdb;

        return $wpdb->delete(self::get_table_name(), [
            'organization_id' => $organization_id,
            'wp_user_id' => $wp_user_id,
        ]) !== false;
    }

    /**
     * Передати право власності іншому підтвердженому учаснику ТІЄЇ Ж
     * організації — поточний власник стає 'member', обраний — 'owner'.
     * Повертає false, якщо новий власник не є підтвердженим учасником цієї
     * організації. Оновлення навмисно прив'язане і до organization_id, і до
     * wp_user_id — користувач може мати рядки в ІНШИХ організаціях, які не
     * повинні зачіпатись.
     */
    public static function transfer_owner($organization_id, $current_owner_id, $new_owner_id) {
        global $wpdb;

        if (self::get_role($organization_id, $new_owner_id) === null) {
            return false;
        }

        $wpdb->update(
            self::get_table_name(),
            ['role' => self::ROLE_MEMBER],
            ['organization_id' => $organization_id, 'wp_user_id' => $current_owner_id]
        );
        $wpdb->update(
            self::get_table_name(),
            ['role' => self::ROLE_OWNER],
            ['organization_id' => $organization_id, 'wp_user_id' => $new_owner_id]
        );

        return true;
    }
}

/**
 * Клас для роботи з медіафайлами
 */
class Lapki_Media extends Lapki_Model {
    protected static $table_name = 'lapki_media';

    /**
     * Файлова "теки"-область (scope) для Lapki_Main::get_image_*() за entity_type —
     * фото тварин, організацій і аватарів користувачів зберігаються в окремих
     * підпапках uploads/lapki/{images,org,user}/, щоб не змішувались.
     */
    private static function get_file_scope($entity_type) {
        if ($entity_type === 'organization') {
            return 'organization';
        }
        if ($entity_type === 'user') {
            return 'user';
        }
        return 'animal';
    }

    /**
     * Отримати один медіафайл за ID
     */
    public static function get($id) {
        global $wpdb;

        $media = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::get_table_name() . " WHERE id = %d",
            $id
        ), ARRAY_A);

        if ($media) {
            $media = self::add_urls_to_media($media);
        }

        return $media;
    }

    /**
     * Отримати медіафайли по сутності з URL
     */
    public static function get_by_entity($entity_type, $entity_id) {
        global $wpdb;
        
        $media = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . self::get_table_name() . " 
             WHERE entity_type = %s AND entity_id = %d AND is_active = 1 
             ORDER BY is_primary DESC, sort_order ASC",
            $entity_type, $entity_id
        ), ARRAY_A);
        
        // Додати URL до кожного медіафайлу
        foreach ($media as &$item) {
            $item = self::add_urls_to_media($item);
        }
        
        return $media;
    }
    
    /**
     * Отримати головне фото з URL
     */
    public static function get_primary_photo($entity_type, $entity_id) {
        global $wpdb;
        
        $media = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::get_table_name() . " 
             WHERE entity_type = %s AND entity_id = %d AND media_type = 'photo' 
             AND is_primary = 1 AND is_active = 1",
            $entity_type, $entity_id
        ), ARRAY_A);
        
        if ($media) {
            $media = self::add_urls_to_media($media);
        }
        
        return $media;
    }
    
    /**
     * Додати URL до медіафайлу
     */
    private static function add_urls_to_media($media) {
        if (!$media) {
            return $media;
        }

        // Конвертувати is_primary в boolean для JavaScript
        $media['is_primary'] = (bool) $media['is_primary'];

        // Фото організацій зберігаються в окремій теці (uploads/lapki/org/),
        // не змішуючись з фото тварин (uploads/lapki/images/)
        $scope = self::get_file_scope($media['entity_type']);
        $filename = $media['file_path'] ?? '';

        // Додати URL в залежності від типу медіа
        switch ($media['media_type']) {
            case 'photo':
                if (empty($filename)) {
                    break;
                }
                $media['url'] = Lapki_Main::get_image_url($filename, false, $scope);
                $media['thumbnail_url'] = Lapki_Main::get_image_url($filename, true, $scope);
                $media['has_thumbnail'] = Lapki_Main::image_exists($filename, true, $scope);
                break;

            case 'video':
                // Відео — завжди зовнішнє посилання (YouTube/Vimeo/пряме), без завантаженого файлу
                $media['url'] = $media['video_url'] ?? '';
                break;

            default:
                if (!empty($filename)) {
                    $media['url'] = Lapki_Main::get_media_base_url() . '/' . $filename;
                }
        }

        return $media;
    }
    
    /**
     * Створити новий медіафайл
     */
    public static function create($data) {
        global $wpdb;

        // Перевірити обов'язкові поля
        if (empty($data['entity_type']) || empty($data['entity_id']) || empty($data['media_type'])) {
            return false;
        }

        $defaults = [
            'filename' => '',
            'file_path' => '',
            'title' => '',
            'description' => '',
            'alt_text' => '',
            'sort_order' => 0,
            'is_primary' => 0,
            'is_active' => 1,
            'uploaded_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        $data = array_merge($defaults, $data);

        // Якщо це головне фото, зробити інші не головними
        if ($data['is_primary'] && $data['media_type'] === 'photo') {
            self::unset_primary_photo($data['entity_type'], $data['entity_id']);
        }

        $result = $wpdb->insert(self::get_table_name(), $data);

        return $result !== false ? $wpdb->insert_id : false;
    }
    
    /**
     * Завантажити та обробити зображення
     */
    public static function upload_image($file, $entity_type, $entity_id, $animal_name = '', $is_primary = false, $sort_order = null) {
        // Перевірити файл
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return new WP_Error('upload_error', __('Файл не завантажений', 'lapki'));
        }
        
        // Валідація зображення
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = wp_check_filetype($file['name']);
        
        if (!in_array($file_type['type'], $allowed_types)) {
            return new WP_Error('invalid_type', __('Невірний тип файлу', 'lapki'));
        }
        
        // Фото організацій/аватари користувачів зберігаються в окремих
        // теках від фото тварин (uploads/lapki/org/, uploads/lapki/user/)
        $scope = self::get_file_scope($entity_type);

        // Створити папки якщо їх немає
        Lapki_Main::create_media_directories();

        // Генерувати унікальну назву файлу
        $filename = Lapki_Main::generate_filename($file['name'], $animal_name);
        $destination = Lapki_Main::get_image_path($filename, false, $scope);

        // Переміщення файлу
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return new WP_Error('move_error', __('Не вдалося перемістити файл', 'lapki'));
        }

        // Отримати інформацію про зображення
        $image_info = getimagesize($destination);
        if ($image_info === false) {
            unlink($destination);
            return new WP_Error('invalid_image', __('Файл не є валідним зображенням', 'lapki'));
        }

        // Створити thumbnail
        Lapki_Main::create_thumbnail($filename, $scope);

        // Створити запис в БД
        $media_data = [
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'media_type' => 'photo',
            'filename' => $file['name'],
            'file_path' => $filename, // Зберігаємо тільки назву файлу!
            'width' => $image_info[0],
            'height' => $image_info[1],
            'file_size' => filesize($destination),
            'is_primary' => $is_primary ? 1 : 0
        ];

        if ($sort_order !== null) {
            $media_data['sort_order'] = $sort_order;
        }

        $media_id = self::create($media_data);

        if (!$media_id) {
            // Видалити файли якщо не вдалося створити запис
            Lapki_Main::delete_image($filename, $scope);
            return new WP_Error('db_error', __('Не вдалося створити запис в БД', 'lapki'));
        }

        return [
            'media_id' => $media_id,
            'filename' => $filename,
            'url' => Lapki_Main::get_image_url($filename, false, $scope),
            'thumbnail_url' => Lapki_Main::get_image_url($filename, true, $scope)
        ];
    }
    
    /**
     * Видалити медіафайл
     */
    public static function delete($media_id) {
        global $wpdb;
        
        // Отримати інформацію про файл
        $media = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::get_table_name() . " WHERE id = %d",
            $media_id
        ), ARRAY_A);
        
        if (!$media) {
            return false;
        }
        
        // Видалити файли якщо це фото
        if ($media['media_type'] === 'photo' && !empty($media['file_path'])) {
            $scope = self::get_file_scope($media['entity_type']);
            Lapki_Main::delete_image($media['file_path'], $scope);
        }

        // Видалити запис з БД
        return $wpdb->delete(
            self::get_table_name(),
            ['id' => $media_id],
            ['%d']
        );
    }
    
    /**
     * Зробити фото неголовним
     */
    private static function unset_primary_photo($entity_type, $entity_id) {
        global $wpdb;
        
        return $wpdb->update(
            self::get_table_name(),
            ['is_primary' => 0],
            [
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'media_type' => 'photo',
                'is_active' => 1
            ],
            ['%d'],
            ['%s', '%d', '%s', '%d']
        );
    }
    
    /**
     * Видалити всі медіафайли сутності
     */
    public static function delete_by_entity($entity_type, $entity_id) {
        global $wpdb;

        // Отримати всі медіафайли
        $media_files = $wpdb->get_results($wpdb->prepare(
            "SELECT id, file_path, media_type FROM " . self::get_table_name() . "
             WHERE entity_type = %s AND entity_id = %d",
            $entity_type, $entity_id
        ), ARRAY_A);

        // Видалити файли
        $scope = self::get_file_scope($entity_type);
        foreach ($media_files as $media) {
            if ($media['media_type'] === 'photo' && !empty($media['file_path'])) {
                Lapki_Main::delete_image($media['file_path'], $scope);
            }
        }

        // Видалити записи з БД
        return $wpdb->delete(
            self::get_table_name(),
            [
                'entity_type' => $entity_type,
                'entity_id' => $entity_id
            ],
            ['%s', '%d']
        );
    }

    /**
     * Те саме, що delete_by_entity(), але лишає один вказаний медіафайл —
     * для заміни аватара: спершу завантажуємо нове фото, тоді приберемо
     * все старе, щоб користувач не лишився без аватара, якщо завантаження не вдасться.
     */
    public static function delete_by_entity_except($entity_type, $entity_id, $except_media_id) {
        global $wpdb;

        $media_files = $wpdb->get_results($wpdb->prepare(
            "SELECT id, file_path, media_type FROM " . self::get_table_name() . "
             WHERE entity_type = %s AND entity_id = %d AND id != %d",
            $entity_type, $entity_id, $except_media_id
        ), ARRAY_A);

        $scope = self::get_file_scope($entity_type);
        foreach ($media_files as $media) {
            if ($media['media_type'] === 'photo' && !empty($media['file_path'])) {
                Lapki_Main::delete_image($media['file_path'], $scope);
            }
        }

        return $wpdb->query($wpdb->prepare(
            "DELETE FROM " . self::get_table_name() . " WHERE entity_type = %s AND entity_id = %d AND id != %d",
            $entity_type, $entity_id, $except_media_id
        ));
    }

    /**
     * Встановити медіафайл головним (автоматично знімає is_primary з інших)
     */
    public static function set_primary($media_id) {
        global $wpdb;

        $media = self::get($media_id);
        if (!$media) {
            return false;
        }

        // Зняти is_primary з усіх медіа цієї сутності
        $wpdb->update(
            self::get_table_name(),
            ['is_primary' => 0],
            [
                'entity_type' => $media['entity_type'],
                'entity_id' => $media['entity_id']
            ],
            ['%d'],
            ['%s', '%d']
        );

        // Встановити is_primary для цього медіа
        return $wpdb->update(
            self::get_table_name(),
            ['is_primary' => 1, 'updated_at' => current_time('mysql')],
            ['id' => $media_id],
            ['%d', '%s'],
            ['%d']
        );
    }

    /**
     * Встановити перше фото головним якщо головного немає
     */
    public static function ensure_primary($entity_type, $entity_id) {
        global $wpdb;

        // Перевірити чи є головне фото
        $has_primary = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::get_table_name() . "
             WHERE entity_type = %s AND entity_id = %d AND media_type = 'photo' AND is_primary = 1 AND is_active = 1",
            $entity_type, $entity_id
        ));

        if ($has_primary) {
            return true; // Головне фото вже є
        }

        // Знайти перше фото
        $first_media_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . self::get_table_name() . "
             WHERE entity_type = %s AND entity_id = %d AND media_type = 'photo' AND is_active = 1
             ORDER BY sort_order ASC
             LIMIT 1",
            $entity_type, $entity_id
        ));

        if (!$first_media_id) {
            return false; // Немає фото взагалі
        }

        // Встановити перше фото головним
        return $wpdb->update(
            self::get_table_name(),
            ['is_primary' => 1, 'updated_at' => current_time('mysql')],
            ['id' => $first_media_id],
            ['%d', '%s'],
            ['%d']
        );
    }

    /**
     * Отримати наступний sort_order для сутності
     */
    public static function get_next_sort_order($entity_type, $entity_id) {
        global $wpdb;

        $max_order = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(MAX(sort_order), 0) FROM " . self::get_table_name() . "
             WHERE entity_type = %s AND entity_id = %d",
            $entity_type, $entity_id
        ));

        return $max_order + 1;
    }

    /**
     * Хости популярних відеосервісів, з яких приймаємо посилання на відео —
     * власного відеосховища й завантаження файлів немає (свідомо, див.
     * parse_video_urls()), лише зовнішні посилання.
     */
    const VIDEO_HOST_WHITELIST = [
        'youtube.com', 'youtu.be', 'vimeo.com', 'tiktok.com',
        'dailymotion.com', 'facebook.com', 'fb.watch', 'instagram.com',
    ];

    /**
     * Розібрати довільний текст на список посилань на відео з білого списку
     * хостів — шукає URL-подібні токени за regex-маскою, а не ділить рядок
     * по конкретному роздільнику, тож користувачу байдуже, чим розділяти
     * (пробіл, кома, крапка з комою, новий рядок).
     *
     * @param string $raw_text
     * @return string[] Унікальні валідні URL, порядок збережено
     */
    public static function parse_video_urls($raw_text) {
        if (empty($raw_text)) {
            return [];
        }

        preg_match_all('/https?:\/\/[^\s,;]+/i', (string) $raw_text, $matches);

        $valid = [];
        foreach ($matches[0] as $token) {
            $url = esc_url_raw(rtrim($token, ".,;)]}'\""));
            if (empty($url)) {
                continue;
            }

            $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
            $host = preg_replace('/^www\./', '', $host);

            foreach (self::VIDEO_HOST_WHITELIST as $allowed_host) {
                if ($host === $allowed_host || substr($host, -strlen('.' . $allowed_host)) === '.' . $allowed_host) {
                    $valid[$url] = true;
                    break;
                }
            }
        }

        return array_keys($valid);
    }

    /**
     * Перевірити чи є головне фото
     */
    public static function has_primary($entity_type, $entity_id) {
        global $wpdb;

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::get_table_name() . "
             WHERE entity_type = %s AND entity_id = %d AND media_type = 'photo' AND is_primary = 1 AND is_active = 1",
            $entity_type, $entity_id
        ));
    }
}

/**
 * Клас для роботи з тегами
 */
class Lapki_Tag extends Lapki_Model {
    protected static $table_name = 'lapki_tags';
    
    public static function get_by_entity($entity_type, $entity_id) {
        global $wpdb;
        
        return $wpdb->get_col($wpdb->prepare(
            "SELECT tag FROM " . self::get_table_name() . " 
             WHERE entity_type = %s AND entity_id = %d",
            $entity_type, $entity_id
        ));
    }
    
    public static function delete_by_entity($entity_type, $entity_id) {
        global $wpdb;

        return $wpdb->delete(
            self::get_table_name(),
            [
                'entity_type' => $entity_type,
                'entity_id' => $entity_id
            ],
            ['%s', '%d']
        );
    }
}

/**
 * Довідник населених пунктів України (КАТОТТГ) — wp_lapki_geo, засіяний з
 * inc/data/settlements.csv (див. Lapki_Migrations::seed_geo()).
 */
class Lapki_Geo extends Lapki_Model {
    protected static $table_name = 'lapki_geo';

    /**
     * Пошук населених пунктів для автодоповнення. Запит розбивається на
     * токени по комі/пробілу («Привільне, Зап» → ["Привільне","Зап"]) — це
     * дозволяє уточнювати вибір серед однойменних населених пунктів (той-таки
     * «Привільне» існує 22 рази в різних областях), дописуючи фрагмент
     * області/громади через кому, як у самому відображуваному форматі
     * підказки. Кожен токен незалежно шукається в назві, області АБО громаді
     * (LIKE), а рядок має задовольняти ВСІ токени одночасно (AND) — порядок
     * токенів і те, в яке саме поле він потрапляє, значення не має.
     * Порівняння регістронезалежне (колонки на `_ci`-колейшені).
     *
     * Населені пункти, чия ВЛАСНА назва починається з першого токена, завжди
     * спливають першими (незалежно від типу — інакше запит, що збігається з
     * реальною назвою села/селища, тонув би серед незв'язаних рядків, що
     * просто містять цей самий підрядок десь у назві громади), місто серед
     * них — ще вище (найімовірніший намір користувача — великий/обласний
     * центр); решта збігів — за алфавітом назви, потім області/громади.
     */
    public static function search($query, $limit = 20) {
        global $wpdb;

        $tokens = preg_split('/[\s,]+/u', trim($query), -1, PREG_SPLIT_NO_EMPTY);
        $tokens = array_slice($tokens, 0, 5);

        if (empty($tokens)) {
            return [];
        }

        $where_parts = [];
        $params = [];

        foreach ($tokens as $token) {
            $like = '%' . $wpdb->esc_like($token) . '%';
            $where_parts[] = '(name LIKE %s OR oblast LIKE %s OR hromada LIKE %s)';
            array_push($params, $like, $like, $like);
        }

        $params[] = $wpdb->esc_like($tokens[0]) . '%';
        $params[] = $limit;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT katottg_code, koatuu_code, name, type, oblast, raion, hromada, latitude, longitude
             FROM " . self::get_table_name() . "
             WHERE " . implode(' AND ', $where_parts) . "
             ORDER BY (name LIKE %s) DESC, (type = 'місто') DESC, name ASC, oblast ASC, hromada ASC
             LIMIT %d",
            $params
        ), ARRAY_A);
    }

    public static function get_by_katottg_code($katottg_code) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::get_table_name() . " WHERE katottg_code = %s",
            $katottg_code
        ), ARRAY_A);
    }

}

/**
 * Клас для роботи з атрибутами (розширений)
 */
class Lapki_Attributes extends Lapki_Model {
    protected static $table_name = 'lapki_attributes';

    public static function get($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::get_table_name() . " WHERE id = %d",
            $id
        ), ARRAY_A);
    }

    public static function get_all($filters = []) {
        global $wpdb;

        $where = [];
        $values = [];

        if (!empty($filters['lang'])) {
            $where[] = 'lang = %s';
            $values[] = $filters['lang'];
        }
        if (!empty($filters['entity'])) {
            $where[] = 'entity = %s';
            $values[] = $filters['entity'];
        }
        if (!empty($filters['entity_type'])) {
            $where[] = 'entity_type = %s';
            $values[] = $filters['entity_type'];
        }
        if (!empty($filters['attr_name'])) {
            $where[] = 'attr_name = %s';
            $values[] = $filters['attr_name'];
        }
        if (!empty($filters['search'])) {
            $where[] = '(attr_value LIKE %s OR attr_display LIKE %s)';
            $like = '%' . $wpdb->esc_like($filters['search']) . '%';
            $values[] = $like;
            $values[] = $like;
        }

        $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $limit = isset($filters['limit']) ? intval($filters['limit']) : 50;
        $offset = isset($filters['offset']) ? intval($filters['offset']) : 0;

        $values[] = $limit;
        $values[] = $offset;

        $sql = "SELECT * FROM " . self::get_table_name() . " $where_sql ORDER BY entity, entity_type, attr_name, attr_display LIMIT %d OFFSET %d";

        return $wpdb->get_results($wpdb->prepare($sql, $values), ARRAY_A);
    }

    public static function count($filters = []) {
        global $wpdb;

        $where = [];
        $values = [];

        if (!empty($filters['lang'])) {
            $where[] = 'lang = %s';
            $values[] = $filters['lang'];
        }
        if (!empty($filters['entity'])) {
            $where[] = 'entity = %s';
            $values[] = $filters['entity'];
        }
        if (!empty($filters['entity_type'])) {
            $where[] = 'entity_type = %s';
            $values[] = $filters['entity_type'];
        }
        if (!empty($filters['attr_name'])) {
            $where[] = 'attr_name = %s';
            $values[] = $filters['attr_name'];
        }
        if (!empty($filters['search'])) {
            $where[] = '(attr_value LIKE %s OR attr_display LIKE %s)';
            $like = '%' . $wpdb->esc_like($filters['search']) . '%';
            $values[] = $like;
            $values[] = $like;
        }

        $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT COUNT(*) FROM " . self::get_table_name() . " $where_sql";

        return (int) (!empty($values) ? $wpdb->get_var($wpdb->prepare($sql, $values)) : $wpdb->get_var($sql));
    }

    public static function create($data) {
        global $wpdb;
        $result = $wpdb->insert(self::get_table_name(), $data);
        return $result ? $wpdb->insert_id : false;
    }

    public static function update($id, $data) {
        global $wpdb;
        return $wpdb->update(self::get_table_name(), $data, ['id' => $id]);
    }

    public static function delete($id) {
        global $wpdb;
        return $wpdb->delete(self::get_table_name(), ['id' => $id]);
    }

    /**
     * Деякі атрибути мають природний логічний порядок, який не збігається з
     * алфавітним сортуванням українських назв (напр. "Дорослий" < "Малюк"
     * алфавітно, хоча за віком малюк молодший) — тут явно задаємо потрібний
     * порядок value, решта (якщо є) лишається в кінці в довільному порядку.
     */
    private static $attr_value_order = [
        'age' => ['baby', 'young', 'adult', 'senior'],
        'size' => ['small', 'medium', 'large', 'xlarge'],
    ];

    /**
     * Відсортувати значення атрибутів за self::$attr_value_order там, де він
     * заданий для конкретного attr_name; решта атрибутів лишається як є.
     */
    private static function sort_attribute_values($attributes) {
        foreach (self::$attr_value_order as $attr_name => $order) {
            if (empty($attributes[$attr_name])) {
                continue;
            }

            usort($attributes[$attr_name], function ($a, $b) use ($order) {
                $pos_a = array_search($a['value'], $order, true);
                $pos_b = array_search($b['value'], $order, true);
                $pos_a = $pos_a === false ? count($order) : $pos_a;
                $pos_b = $pos_b === false ? count($order) : $pos_b;
                return $pos_a <=> $pos_b;
            });
        }

        return $attributes;
    }

    /**
     * Отримати глобальні атрибути (entity_type = 'all'): age, gender, size, coat, status
     */
    public static function get_global_attributes($lang = null) {
        $lang = $lang !== null ? $lang : Lapki_I18n::get_lang();
        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT attr_name, attr_value, attr_display
             FROM " . self::get_table_name() . "
             WHERE entity = 'animal' AND entity_type = 'all' AND lang = %s
             ORDER BY attr_name, attr_display",
            $lang
        ), ARRAY_A);

        $attributes = [];
        foreach ($results as $row) {
            $attributes[$row['attr_name']][] = [
                'value' => $row['attr_value'],
                'display_name' => $row['attr_display']
            ];
        }

        return self::sort_attribute_values($attributes);
    }

    /**
     * Типи тварин, які варто показувати першими (у цьому порядку) — кіт і
     * собака явно найпоширеніші тварини для прилаштування в Україні, решта
     * (кінь, кролик, пташка, інша) лишається за ними в алфавітному порядку.
     */
    private static $animal_type_order = ['cat', 'dog'];

    /**
     * Отримати всі типи тварин
     */
    public static function get_animal_types($lang = null) {
        $lang = $lang !== null ? $lang : Lapki_I18n::get_lang();
        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT attr_value as type, attr_display as display_name
             FROM " . self::get_table_name() . "
             WHERE entity = 'animal' AND entity_type = 'type' AND attr_name = 'species' AND lang = %s
             ORDER BY attr_display",
            $lang
        ), ARRAY_A);

        $order = self::$animal_type_order;
        usort($results, function ($a, $b) use ($order) {
            $pos_a = array_search($a['type'], $order, true);
            $pos_b = array_search($b['type'], $order, true);
            $pos_a = $pos_a === false ? count($order) : $pos_a;
            $pos_b = $pos_b === false ? count($order) : $pos_b;
            return $pos_a <=> $pos_b;
        });

        return $results;
    }
    
    /**
     * Отримати породи для типу тварини
     */
    public static function get_breeds_by_type($type, $lang = null) {
        $lang = $lang !== null ? $lang : Lapki_I18n::get_lang();
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT attr_value as value, attr_display as display_name 
             FROM " . self::get_table_name() . " 
             WHERE entity = 'animal' AND entity_type = %s AND attr_name = 'breed' AND lang = %s 
             ORDER BY attr_display",
            $type, $lang
        ), ARRAY_A);
    }
    
    /**
     * Отримати всі атрибути для типу
     */
    public static function get_type_attributes($type, $lang = null) {
        $lang = $lang !== null ? $lang : Lapki_I18n::get_lang();
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT attr_name, attr_value, attr_display 
             FROM " . self::get_table_name() . " 
             WHERE entity = 'animal' AND (entity_type = %s OR entity_type = 'all') AND lang = %s 
             ORDER BY attr_name, attr_display",
            $type, $lang
        ), ARRAY_A);
        
        $attributes = [];
        foreach ($results as $row) {
            $attributes[$row['attr_name']][] = [
                'value' => $row['attr_value'],
                'display_name' => $row['attr_display']
            ];
        }

        return self::sort_attribute_values($attributes);
    }

    /**
     * Людяне значення ОДНОГО attr_value з довідника (для показу "Додаткової
     * інформації" тварини на публічній сторінці) — той самий пошук, що і
     * get_type_attributes(), але для конкретної пари attr_name/attr_value.
     * Якщо перекладу немає — повертає сире значення як є.
     */
    public static function get_attribute_display($type, $attr_name, $attr_value, $lang = null) {
        $lang = $lang !== null ? $lang : Lapki_I18n::get_lang();
        global $wpdb;

        $display = $wpdb->get_var($wpdb->prepare(
            "SELECT attr_display
             FROM " . self::get_table_name() . "
             WHERE entity = 'animal' AND (entity_type = %s OR entity_type = 'all')
               AND attr_name = %s AND attr_value = %s AND lang = %s
             ORDER BY (entity_type = %s) DESC
             LIMIT 1",
            $type, $attr_name, $attr_value, $lang, $type
        ));

        return $display !== null ? $display : $attr_value;
    }
}

/**
 * Клас для роботи із заявками на усиновлення
 */
class Lapki_Application extends Lapki_Model {
    protected static $table_name = 'lapki_applications';

    const STATUS_NEW = 'new';
    const STATUS_CONTACTED = 'contacted';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public static function get($id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, an.name as animal_name
             FROM " . self::get_table_name() . " a
             LEFT JOIN " . Lapki_Animal::get_table_name() . " an ON a.animal_id = an.id
             WHERE a.id = %d",
            $id
        ), ARRAY_A);
    }

    public static function get_by_organization($organization_id, $status = '') {
        global $wpdb;

        $sql = "SELECT a.*, an.name as animal_name
                FROM " . self::get_table_name() . " a
                LEFT JOIN " . Lapki_Animal::get_table_name() . " an ON a.animal_id = an.id
                WHERE a.organization_id = %d";
        $params = [$organization_id];

        if (!empty($status)) {
            $sql .= " AND a.status = %s";
            $params[] = $status;
        }

        $sql .= " ORDER BY a.created_at DESC";

        return $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
    }

    /**
     * Заявки, подані конкретним залогіненим користувачем (як заявником) —
     * незалежно від організації/тварини. Для вкладки "Заявки на прилаштування"
     * в кабінеті /profile/. Анонімні заявки (wp_user_id IS NULL, подані без
     * входу в акаунт) сюди не потрапляють — прив'язати їх нема до кого.
     */
    public static function get_by_user($wp_user_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, an.name as animal_name, o.name as organization_name
             FROM " . self::get_table_name() . " a
             LEFT JOIN " . Lapki_Animal::get_table_name() . " an ON a.animal_id = an.id
             LEFT JOIN " . Lapki_Organization::get_table_name() . " o ON a.organization_id = o.id
             WHERE a.wp_user_id = %d
             ORDER BY a.created_at DESC",
            $wp_user_id
        ), ARRAY_A);
    }

    /**
     * Легкий COUNT для бейджа кількості заявок у навігації /profile/ —
     * рахується на кожному завантаженні кабінету (не лише на вкладці
     * "Заявки на прилаштування"), тож без JOIN і без вибірки самих рядків.
     */
    public static function count_by_user($wp_user_id) {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::get_table_name() . " WHERE wp_user_id = %d",
            $wp_user_id
        ));
    }

    public static function create($data) {
        global $wpdb;

        $defaults = [
            'status' => self::STATUS_NEW,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $data = wp_parse_args($data, $defaults);
        $data = self::prepare_data($data);

        $result = $wpdb->insert(self::get_table_name(), $data);

        return $result !== false ? $wpdb->insert_id : false;
    }

    public static function update_status($id, $status) {
        global $wpdb;

        return $wpdb->update(
            self::get_table_name(),
            ['status' => $status, 'updated_at' => current_time('mysql')],
            ['id' => $id]
        ) !== false;
    }

    public static function delete($id) {
        global $wpdb;

        return $wpdb->delete(self::get_table_name(), ['id' => $id]) !== false;
    }
}

/**
 * Лог викликів "Покращити за допомогою ШІ" — для сторінки статистики
 * (Lapki → Статистика).
 */
class Lapki_AI_Usage_Log extends Lapki_Model {
    protected static $table_name = 'lapki_ai_usage_log';

    /**
     * $usage — те, що повернув Lapki_AI_Provider::improve_text() у ['usage'],
     * напр. ['prompt_tokens' => .., 'completion_tokens' => .., 'total_tokens' => ..].
     * Порожній масив (провал виклику, або провайдер не звітує токени) —
     * записується як нулі.
     */
    public static function log($provider_id, $success, $wp_user_id = null, $usage = [], $error_code = null, $error_message = null) {
        global $wpdb;

        return $wpdb->insert(self::get_table_name(), [
            'provider' => $provider_id,
            'success' => $success ? 1 : 0,
            'wp_user_id' => $wp_user_id ?: null,
            'prompt_tokens' => (int) ($usage['prompt_tokens'] ?? 0),
            'completion_tokens' => (int) ($usage['completion_tokens'] ?? 0),
            'total_tokens' => (int) ($usage['total_tokens'] ?? 0),
            'error_code' => $error_code ?: null,
            'error_message' => $error_message ?: null,
            'created_at' => current_time('mysql'),
        ], ['%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s']) !== false;
    }

    /**
     * Кількість викликів і токенів у діапазоні дат [$from, $to] включно
     * (формат 'Y-m-d'). Повертає ['total' => int, 'success' => int,
     * 'failed' => int, 'prompt_tokens' => int, 'completion_tokens' => int,
     * 'total_tokens' => int].
     */
    public static function count_between($from, $to) {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as total, SUM(success) as success,
                    SUM(prompt_tokens) as prompt_tokens,
                    SUM(completion_tokens) as completion_tokens,
                    SUM(total_tokens) as total_tokens
             FROM " . self::get_table_name() . "
             WHERE DATE(created_at) BETWEEN %s AND %s",
            $from,
            $to
        ), ARRAY_A);

        $total = (int) ($row['total'] ?? 0);
        $success = (int) ($row['success'] ?? 0);

        return [
            'total' => $total,
            'success' => $success,
            'failed' => $total - $success,
            'prompt_tokens' => (int) ($row['prompt_tokens'] ?? 0),
            'completion_tokens' => (int) ($row['completion_tokens'] ?? 0),
            'total_tokens' => (int) ($row['total_tokens'] ?? 0),
        ];
    }

    /**
     * Розбивка невдалих викликів по коду помилки (ai_timeout,
     * ai_connection_failed, ai_request_failed, ...) у діапазоні дат
     * [$from, $to] включно. Повертає [['error_code' => ..., 'cnt' => ...], ...],
     * відсортовано за спаданням кількості.
     */
    public static function get_error_breakdown($from, $to) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT error_code, COUNT(*) as cnt
             FROM " . self::get_table_name() . "
             WHERE success = 0 AND DATE(created_at) BETWEEN %s AND %s
             GROUP BY error_code ORDER BY cnt DESC",
            $from,
            $to
        ), ARRAY_A);
    }
}

/**
 * Шаблони email-повідомлень, що надсилає сайт автоматично (Lapki →
 * Email-шаблони). Редагувати можна лише subject/body — призначення (name),
 * slug і placeholders задаються при засіванні і в UI не змінюються.
 */
class Lapki_Email_Template extends Lapki_Model {
    protected static $table_name = 'lapki_email';

    const SLUG_APPLICATION_OWNER_NOTIFICATION = 'application_owner_notification';

    public static function get_all() {
        global $wpdb;

        return $wpdb->get_results("SELECT * FROM " . self::get_table_name() . " ORDER BY id ASC", ARRAY_A);
    }

    public static function get($id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::get_table_name() . " WHERE id = %d",
            $id
        ), ARRAY_A);
    }

    public static function get_by_slug($slug) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::get_table_name() . " WHERE slug = %s",
            $slug
        ), ARRAY_A);
    }

    public static function update($id, $data) {
        global $wpdb;

        $allowed = array_intersect_key($data, array_flip(['subject', 'body']));
        $allowed['updated_at'] = current_time('mysql');

        return $wpdb->update(self::get_table_name(), $allowed, ['id' => $id]) !== false;
    }

    /**
     * Підставити {мітки} у subject/body шаблону за slug. $tags — асоціативний
     * масив без фігурних дужок у ключах, напр. ['animal_name' => 'Барсик'].
     * Повертає ['subject' => .., 'body' => ..] або null, якщо шаблону немає.
     */
    public static function render($slug, $tags) {
        $template = self::get_by_slug($slug);

        if (!$template) {
            return null;
        }

        $replace_pairs = [];
        foreach ($tags as $key => $value) {
            $replace_pairs['{' . $key . '}'] = $value;
        }

        return [
            'subject' => strtr($template['subject'], $replace_pairs),
            'body' => strtr($template['body'], $replace_pairs),
        ];
    }
}
?>