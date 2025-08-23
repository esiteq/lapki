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
            'status' => 'adoptable',
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
        
        return $result !== false ? $wpdb->insert_id : false;
    }
    
    /**
     * Оновити тварину
     */
    public static function update($id, $data) {
        global $wpdb;
        
        $data['updated_at'] = current_time('mysql');
        $data = self::prepare_data($data);
        
        return $wpdb->update(
            self::get_table_name(),
            $data,
            ['id' => $id],
            self::get_format_array($data),
            ['%d']
        );
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
}

/**
 * Клас для роботи з медіафайлами
 */
class Lapki_Media extends Lapki_Model {
    protected static $table_name = 'lapki_media';
    
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
     * Отримати URL головного фото (для зворотної сумісності)
     */
    public static function get_primary_photo_url($entity_type, $entity_id, $thumbnail = false) {
        global $wpdb;
        
        $filename = $wpdb->get_var($wpdb->prepare(
            "SELECT file_path FROM " . self::get_table_name() . " 
             WHERE entity_type = %s AND entity_id = %d AND media_type = 'photo' 
             AND is_primary = 1 AND is_active = 1 
             ORDER BY sort_order ASC LIMIT 1",
            $entity_type, $entity_id
        ));
        
        if (!$filename) {
            return '';
        }
        
        return Lapki_Main::get_image_url($filename, $thumbnail);
    }
    
    /**
     * Додати URL до медіафайлу
     */
    private static function add_urls_to_media($media) {
        if (!$media || empty($media['file_path'])) {
            return $media;
        }
        
        $filename = $media['file_path'];
        
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
            'file_path' => '', // Тепер зберігаємо тільки назву файлу
            'title' => '',
            'description' => '',
            'alt_text' => '',
            'sort_order' => 0,
            'is_primary' => 0,
            'is_active' => 1,
            'uploaded_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        // Якщо це головне фото, зробити інші не головними
        if ($data['is_primary'] && $data['media_type'] === 'photo') {
            self::unset_primary_photo($data['entity_type'], $data['entity_id']);
        }
        
        $result = $wpdb->insert(
            self::get_table_name(),
            $data,
            ['%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s']
        );
        
        return $result !== false ? $wpdb->insert_id : false;
    }
    
    /**
     * Завантажити та обробити зображення
     */
    public static function upload_image($file, $entity_type, $entity_id, $animal_name = '', $is_primary = false) {
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
     * Встановити головне фото
     */
    public static function set_primary_photo($media_id) {
        global $wpdb;
        
        // Отримати інформацію про медіафайл
        $media = $wpdb->get_row($wpdb->prepare(
            "SELECT entity_type, entity_id FROM " . self::get_table_name() . " 
             WHERE id = %d AND media_type = 'photo'",
            $media_id
        ), ARRAY_A);
        
        if (!$media) {
            return false;
        }
        
        // Зробити всі інші фото неголовними
        self::unset_primary_photo($media['entity_type'], $media['entity_id']);
        
        // Встановити це фото як головне
        return $wpdb->update(
            self::get_table_name(),
            ['is_primary' => 1],
            ['id' => $media_id],
            ['%d'],
            ['%d']
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
}
/*
class Lapki_Media extends Lapki_Model {
    protected static $table_name = 'lapki_media';
    
    public static function get_by_entity($entity_type, $entity_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . self::get_table_name() . " 
             WHERE entity_type = %s AND entity_id = %d AND is_active = 1 
             ORDER BY is_primary DESC, sort_order ASC",
            $entity_type, $entity_id
        ), ARRAY_A);
    }
    
    public static function get_primary_photo($entity_type, $entity_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::get_table_name() . " 
             WHERE entity_type = %s AND entity_id = %d AND media_type = 'photo' 
             AND is_primary = 1 AND is_active = 1",
            $entity_type, $entity_id
        ), ARRAY_A);
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
*/

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
    
    /**
     * Отримати всі типи тварин
     */
    public static function get_animal_types($lang = 'uk') {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT entity_type as type, attr_display as display_name 
             FROM " . self::get_table_name() . " 
             WHERE entity = 'animal' AND attr_name = 'species' AND lang = %s 
             ORDER BY attr_display",
            $lang
        ), ARRAY_A);
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
?>