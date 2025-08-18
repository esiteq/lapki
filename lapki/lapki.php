<?php
/**
 * Plugin Name: Lapki
 * Plugin URI: https://esiteq.com/projects/lapki/
 * Description: Pet adoption platform inspired by petfinder.com, localized for Ukraine. Search and adopt dogs, cats, birds and other animals from shelters and rescue organizations.
 * Version: 2.0.0
 * Author: Oleksii Bugrov
 * Author URI: https://esiteq.com/
 * Text Domain: lapki
 * Domain Path: /languages/
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network: false
 * 
 * @package Lapki
 * @author Oleksii Bugrov
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('LAPKI_VERSION', '2.0.0');
define('LAPKI_PLUGIN_FILE', __FILE__);
define('LAPKI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LAPKI_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once LAPKI_PLUGIN_DIR . 'inc/class-eq-form.php';

/**
 * Class Lapki_Main
 *
 * Головний клас для проекту пошуку та прилаштування тварин
 * Інспірований petfinder.com, локалізований для України
 */
class Lapki_Main {
    
    /**
     * Версія плагіна/проекту
     */
    const VERSION = '1.0.0';
    
    /**
     * Підтримувані мови
     */
    const SUPPORTED_LANGS = ['ua', 'en'];
    
    /**
     * Типи сутностей
     */
    const ENTITIES = [
        'animal', 'org', 'user'
    ];
    
    /**
     * Ініціалізація
     */
    public static function init() {
        add_action('init', [__CLASS__, 'setup']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
        
        // Хуки для AJAX
        add_action('wp_ajax_lapki_search', [__CLASS__, 'ajax_search_pets']);
        add_action('wp_ajax_nopriv_lapki_search', [__CLASS__, 'ajax_search_pets']);
    }
    
    /**
     * Налаштування
     */
    public static function setup() {
        // Базові налаштування
    }
    
    /**
     * Завантаження скриптів і стилів
     */
    public static function enqueue_scripts() {
        wp_enqueue_script('lapki-main', plugin_dir_url(__FILE__) . 'js/lapki-main.js', ['jquery'], self::VERSION, true);
        wp_enqueue_style('lapki-main', plugin_dir_url(__FILE__) . 'css/lapki-main.css', [], self::VERSION);
        
        // Локалізація для AJAX
        wp_localize_script('lapki-main', 'lapki_ajax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lapki_nonce')
        ]);
    }
    
    // =======================================================
    // РОБОТА З АТРИБУТАМИ
    // =======================================================
    
    /**
     * Отримати опції для селекта з атрибутів
     */
    public static function get_attribute_options($entity, $entity_type, $attr_name, $lang = 'ua') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'lapki_attributes';
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT attr_value, attr_display 
            FROM {$table_name}
            WHERE entity = %s AND entity_type = %s AND attr_name = %s AND lang = %s
            ORDER BY attr_display
        ", $entity, $entity_type, $attr_name, $lang));
        
        $options = [];
        foreach ($results as $row) {
            $options[$row->attr_value] = $row->attr_display;
        }
        return $options;
    }
    
    /**
     * Отримати відображуване значення атрибута
     */
    public static function get_attribute_display($entity, $entity_type, $attr_name, $attr_value, $lang = 'ua') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'lapki_attributes';
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT attr_display 
            FROM {$table_name}
            WHERE entity = %s AND entity_type = %s AND attr_name = %s AND attr_value = %s AND lang = %s
        ", $entity, $entity_type, $attr_name, $attr_value, $lang));
    }
    
    /**
     * Додати новий атрибут
     */
    public static function add_attribute($entity, $entity_type, $attr_name, $attr_value, $attr_display, $lang = 'ua') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'lapki_attributes';
        
        return $wpdb->insert(
            $table_name,
            [
                'entity' => $entity,
                'entity_type' => $entity_type,
                'attr_name' => $attr_name,
                'attr_value' => $attr_value,
                'attr_display' => $attr_display,
                'lang' => $lang
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s']
        );
    }
    
    /**
     * Отримати всі атрибути для сутності
     */
    public static function get_entity_attributes($entity, $entity_type, $lang = 'ua') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'lapki_attributes';
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT attr_name, attr_value, attr_display 
            FROM {$table_name}
            WHERE entity = %s AND entity_type = %s AND lang = %s
            ORDER BY attr_name, attr_display
        ", $entity, $entity_type, $lang));
        
        $attributes = [];
        foreach ($results as $row) {
            $attributes[$row->attr_name][$row->attr_value] = $row->attr_display;
        }
        return $attributes;
    }
    
    /**
     * Отримати типи тварин
     */
    public static function get_animal_types($lang = 'ua') {
        return self::get_attribute_options('animal', 'type', 'species', $lang);
    }
    
    // =======================================================
    // РОБОТА З БАЗОЮ ДАНИХ
    // =======================================================
    
    /**
     * Створення таблиць при потребі
     */
    private static function maybe_create_tables() {
        global $wpdb;
        
        $attributes_table = $wpdb->prefix . 'lapki_attributes';
        
        // Перевіряємо чи існує таблиця атрибутів
        if ($wpdb->get_var("SHOW TABLES LIKE '{$attributes_table}'") != $attributes_table) {
            self::create_attributes_table();
        }
    }
    
    /**
     * Створення таблиці атрибутів
     */
    private static function create_attributes_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'lapki_attributes';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table_name} (
            id INT(14) AUTO_INCREMENT PRIMARY KEY,
            entity VARCHAR(32) NOT NULL DEFAULT 'animal',
            entity_type VARCHAR(64) NOT NULL,
            attr_name VARCHAR(64) NOT NULL,
            attr_value VARCHAR(128) NOT NULL,
            attr_display VARCHAR(128) NOT NULL,
            lang CHAR(2) NOT NULL DEFAULT 'en',
            INDEX idx_entity_type (entity, entity_type),
            INDEX idx_entity_attr (entity, attr_name),
            INDEX idx_entity_lang (entity, entity_type, lang),
            UNIQUE KEY unique_attr (entity, entity_type, attr_name, attr_value, lang)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    

    
    // =======================================================
    // ДОПОМІЖНІ МЕТОДИ
    // =======================================================
    
    /**
     * Отримати поточну мову
     */
    public static function get_current_lang() {
        // Можна інтегрувати з WPML, Polylang тощо
        return defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'ua';
    }
    
    /**
     * Перевірка валідності мови
     */
    public static function is_valid_lang($lang) {
        return in_array($lang, self::SUPPORTED_LANGS);
    }
    
    /**
     * AJAX пошук тваринок (заглушка)
     */
    public static function ajax_search_pets() {
        check_ajax_referer('lapki_nonce', 'nonce');
        
        // TODO: Реалізувати пошук
        wp_send_json_success(['message' => 'Search functionality coming soon']);
    }
}

// Ініціалізація
if (!function_exists('lapki'))
{
    function lapki()
    {
        global $_lapki;
        if (!isset($_lapki)) $_lapki = new Lapki_Main;
        return $_lapki;
    }
}

lapki();
?>