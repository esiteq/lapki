<?php
/**
 * Plugin Name: Lapki
 * Plugin URI: https://esiteq.com/projects/lapki/
 * Description: Платформа пошуку та прилаштування тварин, інспірована petfinder.com, локалізована для України. Пошук та прилаштування собак, котів, птахів та інших тварин з притулків.
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
require_once LAPKI_PLUGIN_DIR . 'inc/class-lapki-models.php';
require_once LAPKI_PLUGIN_DIR . 'inc/class-lapki-rest-api.php';
if (is_admin()) {
    require_once LAPKI_PLUGIN_DIR . 'inc/class-lapki-admin.php';
    Lapki_Admin::init();
}
require_once LAPKI_PLUGIN_DIR . 'inc/class-lapki-cache.php';

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
    const VERSION = '2.0.0';
    
    /**
     * Підтримувані мови
     */
    const SUPPORTED_LANGS = ['uk', 'en'];
    
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
        
        // Активація/деактивація плагіна
        register_activation_hook(__FILE__, [__CLASS__, 'activate']);
        register_deactivation_hook(__FILE__, [__CLASS__, 'deactivate']);
    }
    
    /**
     * Налаштування
     */
    public static function setup()
    {
        // Базові налаштування
        load_plugin_textdomain('lapki', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    /**
     * Завантаження скриптів і стилів
     */
    public static function enqueue_scripts() {
        wp_enqueue_script('lapki-main', LAPKI_PLUGIN_URL . 'js/lapki-main.js', ['jquery'], self::VERSION, true);
        wp_enqueue_style('lapki-main', LAPKI_PLUGIN_URL . 'css/lapki-main.css', [], self::VERSION);
        
        // Локалізація для AJAX
        wp_localize_script('lapki-main', 'lapki_ajax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lapki_nonce')
        ]);
    }
    
    /**
     * Активація плагіна
     */
    public static function activate()
    {
        //
    }
    
    /**
     * Деактивація плагіна
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }
    
    // =======================================================
    // РОБОТА З АТРИБУТАМИ
    // =======================================================

/**
 * 🔥 Отримати відображуване значення атрибута (З КЕШУВАННЯМ!)
 */
public static function get_attribute_display($entity, $entity_type, $attr_name, $attr_value, $lang = 'uk') {
    // СПОЧАТКУ СПРОБУВАТИ КЕШ
    if (class_exists('Lapki_Cache')) {
        $cached_value = Lapki_Cache::get_attribute_display_fast($entity, $entity_type, $attr_name, $attr_value, $lang);
        if ($cached_value !== null) {
            return $cached_value;
        }
    }
    
    // Fallback на базу даних якщо немає в кеші
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'lapki_attributes';
    
    $result = $wpdb->get_var($wpdb->prepare("
        SELECT attr_display 
        FROM {$table_name}
        WHERE entity = %s AND entity_type = %s AND attr_name = %s AND attr_value = %s AND lang = %s
    ", $entity, $entity_type, $attr_name, $attr_value, $lang));
    
    return $result;
}
/**
 * 🔥 Отримати опції для селекта з атрибутів (З КЕШУВАННЯМ!)
 */
public static function get_attribute_options($entity, $entity_type, $attr_name, $lang = 'uk') {
    // СПОЧАТКУ СПРОБУВАТИ КЕШ
    if (class_exists('Lapki_Cache')) {
        $cached_options = Lapki_Cache::get_attribute_options_fast($entity, $entity_type, $attr_name, $lang);
        if (!empty($cached_options)) {
            return $cached_options;
        }
    }
    
    // Fallback на базу даних якщо немає в кеші
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
 * 🔥 Додати новий атрибут (З ІНВАЛІДАЦІЄЮ КЕШУ!)
 */
public static function add_attribute($entity, $entity_type, $attr_name, $attr_value, $attr_display, $lang = 'uk') {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'lapki_attributes';
    
    $result = $wpdb->insert(
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
    
    // ІНВАЛІДУВАТИ КЕШ АТРИБУТІВ
    if ($result && class_exists('Lapki_Cache')) {
        Lapki_Cache::invalidate_attributes();
        // Перезавантажити кеш
        Lapki_Cache::warm_up_attributes();
    }
    
    return $result;
}

/**
 * Отримати типи тварин
 */
public static function get_animal_types($lang = 'uk') {
    return self::get_attribute_options('animal', 'type', 'species', $lang);
}

    /**
     * Отримати всі атрибути для сутності
     */
    public static function get_entity_attributes($entity, $entity_type, $lang = 'uk') {
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
    
    // =======================================================
    // ДОПОМІЖНІ МЕТОДИ
    // =======================================================
    
    /**
     * Отримати поточну мову
     */
    public static function get_current_lang() {
        // Можна інтегрувати з WPML, Polylang тощо
        return defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'uk';
    }
    
    /**
     * Перевірка валідності мови
     */
    public static function is_valid_lang($lang) {
        return in_array($lang, self::SUPPORTED_LANGS);
    }
    
    /**
     * AJAX пошук тваринок
     */
    public static function ajax_search_pets() {
        check_ajax_referer('lapki_nonce', 'nonce');
        
        $search_params = [
            'species' => sanitize_text_field($_POST['species'] ?? ''),
            'location' => sanitize_text_field($_POST['location'] ?? ''),
            'size' => sanitize_text_field($_POST['size'] ?? ''),
            'gender' => sanitize_text_field($_POST['gender'] ?? ''),
            'age' => sanitize_text_field($_POST['age'] ?? ''),
        ];
        
        // TODO: Реалізувати пошук через API або локальну базу
        $results = self::search_pets($search_params);
        
        wp_send_json_success([
            'pets' => $results,
            'count' => count($results)
        ]);
    }
    public static function init_cache() {
        if (class_exists('Lapki_Cache')) {
            // Перевірити чи є кеш атрибутів, якщо ні - завантажити
            $cached_attrs = Lapki_Cache::get('all_attributes', Lapki_Cache::PREFIX_ATTRIBUTES);
            if ($cached_attrs === false) {
                Lapki_Cache::warm_up_attributes();
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Lapki: Attributes cache warmed up on init');
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Lapki: Attributes cache already exists');
                }
            }
        }
    }
}

// Ініціалізація плагіна
Lapki_Main::init();

// Глобальна функція для доступу до плагіна
if (!function_exists('lapki')) {
    function lapki() {
        return Lapki_Main::class;
    }
}
?>