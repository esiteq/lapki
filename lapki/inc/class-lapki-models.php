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

        // Локація
        if (!empty($params['location'])) {
            $where_clauses[] = "(a.address_city LIKE %s OR a.address_state LIKE %s)";
            $location_param = '%' . $params['location'] . '%';
            $sql_params[] = $location_param;
            $sql_params[] = $location_param;
        }
        
        // Відстань (якщо є координати)
        if ($params['latitude'] && $params['longitude'] && $params['distance']) {
            $where_clauses[] = "(6371 * acos(cos(radians(%f)) * cos(radians(a.latitude)) * 
                              cos(radians(a.longitude) - radians(%f)) + sin(radians(%f)) * 
                              sin(radians(a.latitude)))) <= %d";
            $sql_params[] = $params['latitude'];
            $sql_params[] = $params['longitude'];
            $sql_params[] = $params['latitude'];
            $sql_params[] = $params['distance'];
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
        }
        
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

        if (!empty($params['location'])) {
            $where_clauses[] = "(a.address_city LIKE %s OR a.address_state LIKE %s)";
            $loc = '%' . $params['location'] . '%';
            $sql_params[] = $loc;
            $sql_params[] = $loc;
        }

        foreach (['good_with_children', 'good_with_dogs', 'good_with_cats', 'spayed_neutered', 'special_needs'] as $f) {
            if ($params[$f] !== null) { $where_clauses[] = "a.{$f} = %d"; $sql_params[] = (int)$params[$f]; }
        }

        if ($params['latitude'] && $params['longitude'] && $params['distance']) {
            $where_clauses[] = "(6371 * acos(cos(radians(%f)) * cos(radians(a.latitude)) * cos(radians(a.longitude) - radians(%f)) + sin(radians(%f)) * sin(radians(a.latitude)))) <= %d";
            $sql_params[] = $params['latitude'];
            $sql_params[] = $params['longitude'];
            $sql_params[] = $params['latitude'];
            $sql_params[] = $params['distance'];
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
            if (in_array($key, ['id', 'organization_id'])) {
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
        
        return $wpdb->get_results($wpdb->prepare($sql, $sql_params), ARRAY_A);
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
     * Отримати організацію(ї), до якої прив'язаний WP-користувач як учасник
     * (власник або член) — через таблицю членства, не через legacy-колонку
     * wp_user_id. Один користувач належить не більш ніж до однієї організації,
     * тож масив завжди містить 0 або 1 елемент — сигнатура (масив) лишена
     * такою ж, як і була, щоб не чіпати наявних викликів.
     *
     * @param int $wp_user_id
     * @return array
     */
    public static function get_by_wp_user_id($wp_user_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT o.* FROM " . self::get_table_name() . " o
             JOIN " . Lapki_Organization_Member::get_table_name() . " m ON m.organization_id = o.id
             WHERE m.wp_user_id = %d",
            $wp_user_id
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
 * багато користувачів; один користувач — не більше однієї організації).
 * Замінює стару модель "1 організація = 1 власник" (organizations.wp_user_id),
 * яка лишається в таблиці лише як історичний "хто створив".
 */
class Lapki_Organization_Member extends Lapki_Model {
    protected static $table_name = 'lapki_organization_members';

    const ROLE_OWNER = 'owner';
    const ROLE_MEMBER = 'member';

    /**
     * Членство поточного користувача (з даними організації) або null
     */
    public static function get_by_user($wp_user_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT m.*, o.name as organization_name, o.type as organization_type
             FROM " . self::get_table_name() . " m
             JOIN " . Lapki_Organization::get_table_name() . " o ON o.id = m.organization_id
             WHERE m.wp_user_id = %d",
            $wp_user_id
        ), ARRAY_A);
    }

    /**
     * Роль користувача в конкретній організації ('owner'/'member') або null,
     * якщо не є учасником
     */
    public static function get_role($organization_id, $wp_user_id) {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT role FROM " . self::get_table_name() . " WHERE organization_id = %d AND wp_user_id = %d",
            $organization_id,
            $wp_user_id
        ));
    }

    /**
     * Усі учасники організації (з іменами користувачів)
     */
    public static function get_members($organization_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name, u.user_email
             FROM " . self::get_table_name() . " m
             JOIN {$wpdb->users} u ON u.ID = m.wp_user_id
             WHERE m.organization_id = %d
             ORDER BY m.role ASC, m.created_at ASC",
            $organization_id
        ), ARRAY_A);
    }

    /**
     * Приєднати користувача до організації. Повертає false, якщо користувач
     * вже прив'язаний до будь-якої організації (спершу — leave()).
     */
    public static function join($organization_id, $wp_user_id, $role = self::ROLE_MEMBER) {
        global $wpdb;

        if (self::get_by_user($wp_user_id)) {
            return false;
        }

        $result = $wpdb->insert(self::get_table_name(), [
            'organization_id' => $organization_id,
            'wp_user_id' => $wp_user_id,
            'role' => $role,
            'created_at' => current_time('mysql'),
        ]);

        return $result !== false;
    }

    /**
     * Прибрати користувача з організації (незалежно від ролі)
     */
    public static function leave($wp_user_id) {
        global $wpdb;

        return $wpdb->delete(self::get_table_name(), ['wp_user_id' => $wp_user_id]) !== false;
    }
}

/**
 * Клас для роботи з медіафайлами
 */
class Lapki_Media extends Lapki_Model {
    protected static $table_name = 'lapki_media';

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
        if (!$media || empty($media['file_path'])) {
            return $media;
        }

        $filename = $media['file_path'];

        // Конвертувати is_primary в boolean для JavaScript
        $media['is_primary'] = (bool) $media['is_primary'];

        // Додати URL в залежності від типу медіа
        switch ($media['media_type']) {
            case 'photo':
                $media['url'] = Lapki_Main::get_image_url($filename);
                $media['thumbnail_url'] = Lapki_Main::get_image_url($filename, true);
                $media['has_thumbnail'] = Lapki_Main::image_exists($filename, true);
                break;

            case 'video':
                // Для відео можна додати логіку пізніше
                if (!empty($media['video_url'])) {
                    $media['url'] = $media['video_url'];
                } else {
                    $media['url'] = Lapki_Main::get_media_base_url() . '/videos/' . $filename;
                }
                break;
                
            default:
                $media['url'] = Lapki_Main::get_media_base_url() . '/' . $filename;
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
            return new WP_Error('upload_error', 'Файл не завантажений');
        }
        
        // Валідація зображення
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = wp_check_filetype($file['name']);
        
        if (!in_array($file_type['type'], $allowed_types)) {
            return new WP_Error('invalid_type', 'Невірний тип файлу');
        }
        
        // Створити папки якщо їх немає
        Lapki_Main::create_media_directories();
        
        // Генерувати унікальну назву файлу
        $filename = Lapki_Main::generate_filename($file['name'], $animal_name);
        $destination = Lapki_Main::get_image_path($filename);
        
        // Переміщення файлу
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return new WP_Error('move_error', 'Не вдалося перемістити файл');
        }
        
        // Отримати інформацію про зображення
        $image_info = getimagesize($destination);
        if ($image_info === false) {
            unlink($destination);
            return new WP_Error('invalid_image', 'Файл не є валідним зображенням');
        }
        
        // Створити thumbnail
        Lapki_Main::create_thumbnail($filename);
        
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
            Lapki_Main::delete_image($filename);
            return new WP_Error('db_error', 'Не вдалося створити запис в БД');
        }
        
        return [
            'media_id' => $media_id,
            'filename' => $filename,
            'url' => Lapki_Main::get_image_url($filename),
            'thumbnail_url' => Lapki_Main::get_image_url($filename, true)
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
            Lapki_Main::delete_image($media['file_path']);
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
        foreach ($media_files as $media) {
            if ($media['media_type'] === 'photo' && !empty($media['file_path'])) {
                Lapki_Main::delete_image($media['file_path']);
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
     * Отримати глобальні атрибути (entity_type = 'all'): age, gender, size, coat, status
     */
    public static function get_global_attributes($lang = 'uk') {
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

        return $attributes;
    }

    /**
     * Отримати всі типи тварин
     */
    public static function get_animal_types($lang = 'uk') {
        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT attr_value as type, attr_display as display_name
             FROM " . self::get_table_name() . "
             WHERE entity = 'animal' AND entity_type = 'type' AND attr_name = 'species' AND lang = %s
             ORDER BY attr_display",
            $lang
        ), ARRAY_A);

        return $results;
    }
    
    /**
     * Отримати породи для типу тварини
     */
    public static function get_breeds_by_type($type, $lang = 'uk') {
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
    public static function get_type_attributes($type, $lang = 'uk') {
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
        
        return $attributes;
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
?>