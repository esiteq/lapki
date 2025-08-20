<?php

/**
 * Lapki Redis Cache Manager (Simplified)
 * Кешування атрибутів через Redis
 * 
 * @package Lapki
 * @author Oleksii Bugrov
 */

class Lapki_Cache {
    
    /**
     * Redis instance
     */
    private static $redis = null;
    
    /**
     * Cache prefixes
     */
    const PREFIX_ATTRIBUTES = 'lapki_attr_';
    
    /**
     * Cache TTL (seconds)
     */
    const TTL_ATTRIBUTES = 86400;  // 24 години
    
    /**
     * Ініціалізація Redis
     */
    public static function init() {
        if (self::$redis === null && extension_loaded('redis')) {
            try {
                self::$redis = new Redis();
                $connected = self::$redis->connect('127.0.0.1', 6379);
                
                if (!$connected) {
                    error_log('Lapki Cache: Cannot connect to Redis');
                    self::$redis = null;
                    return false;
                }
                
                return true;
            } catch (Exception $e) {
                error_log('Lapki Cache Redis Error: ' . $e->getMessage());
                self::$redis = null;
                return false;
            }
        }
        
        return self::$redis !== null;
    }
    
    /**
     * Перевірити чи Redis доступний
     */
    public static function is_enabled() {
        return self::init();
    }
    
    /**
     * Отримати значення з кешу
     */
    public static function get($key, $prefix = '') {
        if (!self::is_enabled()) {
            return false;
        }
        
        try {
            $cache_key = $prefix . $key;
            $value = self::$redis->get($cache_key);
            
            if ($value === false) {
                return false;
            }
            
            $decoded = json_decode($value, true);
            return $decoded !== null ? $decoded : $value;
            
        } catch (Exception $e) {
            error_log('Lapki Cache GET Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Зберегти значення в кеш
     */
    public static function set($key, $value, $ttl = 300, $prefix = '') {
        if (!self::is_enabled()) {
            return false;
        }
        
        try {
            $cache_key = $prefix . $key;
            $encoded_value = is_array($value) || is_object($value) 
                ? json_encode($value) 
                : $value;
            
            return self::$redis->setex($cache_key, $ttl, $encoded_value);
            
        } catch (Exception $e) {
            error_log('Lapki Cache SET Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Видалити ключ з кешу
     */
    public static function delete($key, $prefix = '') {
        if (!self::is_enabled()) {
            return false;
        }
        
        try {
            $cache_key = $prefix . $key;
            return self::$redis->del($cache_key);
            
        } catch (Exception $e) {
            error_log('Lapki Cache DELETE Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Очистити кеш по патерну
     */
    public static function flush_pattern($pattern) {
        if (!self::is_enabled()) {
            return false;
        }
        
        try {
            $keys = self::$redis->keys($pattern);
            if (!empty($keys)) {
                return self::$redis->del($keys);
            }
            return true;
            
        } catch (Exception $e) {
            error_log('Lapki Cache FLUSH Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Завантажити всю таблицю атрибутів в кеш
     */
    public static function warm_up_attributes() {
        if (!self::is_enabled()) {
            return false;
        }
        
        global $wpdb;
        
        try {
            $table_name = $wpdb->prefix . 'lapki_attributes';
            
            // Отримати всі атрибути одним запитом
            $attributes = $wpdb->get_results("
                SELECT entity, entity_type, attr_name, attr_value, attr_display, lang 
                FROM {$table_name} 
                ORDER BY entity, entity_type, attr_name, attr_value, lang
            ", ARRAY_A);
            
            if (empty($attributes)) {
                error_log('Lapki Cache: No attributes found in database');
                return false;
            }
            
            // Структурувати дані
            $structured = [];
            foreach ($attributes as $attr) {
                $structured[$attr['entity']][$attr['entity_type']][$attr['attr_name']][$attr['attr_value']][$attr['lang']] = $attr['attr_display'];
            }
            
            // Зберегти в кеш на довгий час (24 години)
            $cache_key = 'all_attributes';
            $result = self::set($cache_key, $structured, self::TTL_ATTRIBUTES, self::PREFIX_ATTRIBUTES);
            
            if ($result) {
                error_log("Lapki Cache: Warmed up " . count($attributes) . " attributes");
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('Lapki Cache WARM_UP Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Отримати всі атрибути з кешу
     */
    public static function get_all_attributes() {
        $cache_key = 'all_attributes';
        $attributes = self::get($cache_key, self::PREFIX_ATTRIBUTES);
        
        // Якщо немає в кеші - завантажити
        if ($attributes === false) {
            if (self::warm_up_attributes()) {
                $attributes = self::get($cache_key, self::PREFIX_ATTRIBUTES);
            }
        }
        
        return $attributes ?: [];
    }
    
    /**
     * Швидке отримання атрибута з кешу
     */
    public static function get_attribute_display_fast($entity, $entity_type, $attr_name, $attr_value, $lang = 'uk') {
        $all_attributes = self::get_all_attributes();
        
        if (isset($all_attributes[$entity][$entity_type][$attr_name][$attr_value][$lang])) {
            return $all_attributes[$entity][$entity_type][$attr_name][$attr_value][$lang];
        }
        
        return null;
    }
    
    /**
     * Швидке отримання опцій атрибута з кешу
     */
    public static function get_attribute_options_fast($entity, $entity_type, $attr_name, $lang = 'uk') {
        $all_attributes = self::get_all_attributes();
        
        $options = [];
        if (isset($all_attributes[$entity][$entity_type][$attr_name])) {
            foreach ($all_attributes[$entity][$entity_type][$attr_name] as $attr_value => $langs) {
                if (isset($langs[$lang])) {
                    $options[$attr_value] = $langs[$lang];
                }
            }
        }
        
        return $options;
    }
    
    /**
     * Інвалідація кешу атрибутів
     */
    public static function invalidate_attributes() {
        // Очистити весь кеш атрибутів
        self::flush_pattern(self::PREFIX_ATTRIBUTES . '*');
    }
    
    /**
     * Дебаг інформація
     */
    public static function debug_info() {
        if (!self::is_enabled()) {
            return ['error' => 'Redis not available'];
        }
        
        try {
            $info = self::$redis->info();
            $cache_key = 'all_attributes';
            $has_cache = self::get($cache_key, self::PREFIX_ATTRIBUTES) !== false;
            
            return [
                'redis_version' => $info['redis_version'] ?? 'unknown',
                'used_memory' => $info['used_memory_human'] ?? 'unknown',
                'attributes_cached' => $has_cache,
                'cache_key' => self::PREFIX_ATTRIBUTES . $cache_key
            ];
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

?>