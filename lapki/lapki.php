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
define('LAPKI_VERSION', '2.0.10');
define('LAPKI_PLUGIN_FILE', __FILE__);
define('LAPKI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LAPKI_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once LAPKI_PLUGIN_DIR . 'inc/class-lapki-models.php';
require_once LAPKI_PLUGIN_DIR . 'inc/class-lapki-roles.php';
require_once LAPKI_PLUGIN_DIR . 'inc/class-lapki-migrations.php';
require_once LAPKI_PLUGIN_DIR . 'inc/class-lapki-rest-api.php';
require_once LAPKI_PLUGIN_DIR . 'inc/class-lapki-template-loader.php';
require_once LAPKI_PLUGIN_DIR . 'inc/class-lapki-frontend.php';
Lapki_Frontend::init();

if (is_admin()) {
    require_once LAPKI_PLUGIN_DIR . 'inc/class-lapki-admin.php';
    Lapki_Admin::init();
}

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
     * Шляхи до медіа файлів
     */
    const MEDIA_BASE_DIR = 'lapki';
    const MEDIA_IMAGES_DIR = 'images';
    const MEDIA_THUMBNAILS_DIR = 'thumbnails';
    const MEDIA_VIDEOS_DIR = 'videos';
    
    /**
     * Розміри thumbnails
     */
    const THUMB_WIDTH = 300;
    const THUMB_HEIGHT = 300;
    const THUMB_QUALITY = 80;
   
    /**
     * Ініціалізація
     */
    public static function init() {
        add_action('init', [__CLASS__, 'setup']);

        // Автоматичний апгрейд схеми БД/ролей для вже активних інсталяцій
        // (без потреби деактивувати/активувати плагін після оновлення коду)
        add_action('plugins_loaded', ['Lapki_Migrations', 'maybe_migrate']);

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
     * Активація плагіна
     */
    public static function activate()
    {
        self::create_media_directories();
        Lapki_Migrations::install();
        update_option(Lapki_Migrations::DB_VERSION_OPTION, Lapki_Migrations::DB_VERSION);
        flush_rewrite_rules();
    }
    
    /**
     * Деактивація плагіна
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }

    // =======================================================
    // РОБОТА З МЕДІА ФАЙЛАМИ
    // =======================================================
    
    /**
     * Отримати базовий шлях до uploads/lapki
     */
    public static function get_media_base_path() {
        $upload_dir = wp_upload_dir();
        return trailingslashit($upload_dir['basedir']) . self::MEDIA_BASE_DIR;
    }
    
    /**
     * Отримати базовий URL до uploads/lapki
     */
    public static function get_media_base_url() {
        $upload_dir = wp_upload_dir();
        return trailingslashit($upload_dir['baseurl']) . self::MEDIA_BASE_DIR;
    }
    
    /**
     * Отримати шлях до папки зображень
     */
    public static function get_images_path() {
        return trailingslashit(self::get_media_base_path()) . self::MEDIA_IMAGES_DIR;
    }
    
    /**
     * Отримати URL до папки зображень
     */
    public static function get_images_url() {
        return trailingslashit(self::get_media_base_url()) . self::MEDIA_IMAGES_DIR;
    }
    
    /**
     * Отримати шлях до папки thumbnails
     */
    public static function get_thumbnails_path() {
        return trailingslashit(self::get_media_base_path()) . self::MEDIA_THUMBNAILS_DIR;
    }
    
    /**
     * Отримати URL до папки thumbnails
     */
    public static function get_thumbnails_url() {
        return trailingslashit(self::get_media_base_url()) . self::MEDIA_THUMBNAILS_DIR;
    }
    
    /**
     * Отримати повний URL зображення за назвою файлу
     * 
     * @param string $filename Назва файлу (vasya_cat.jpg)
     * @param bool $thumbnail Чи потрібен thumbnail
     * @return string Повний URL
     */
    public static function get_image_url($filename, $thumbnail = false) {
        if (empty($filename)) {
            return '';
        }
        
        if ($thumbnail) {
            return trailingslashit(self::get_thumbnails_url()) . $filename;
        }
        
        return trailingslashit(self::get_images_url()) . $filename;
    }
    
    /**
     * Отримати повний шлях до файлу зображення
     * 
     * @param string $filename Назва файлу (vasya_cat.jpg)
     * @param bool $thumbnail Чи потрібен thumbnail
     * @return string Повний шлях
     */
    public static function get_image_path($filename, $thumbnail = false) {
        if (empty($filename)) {
            return '';
        }
        
        if ($thumbnail) {
            return trailingslashit(self::get_thumbnails_path()) . $filename;
        }
        
        return trailingslashit(self::get_images_path()) . $filename;
    }
    
    /**
     * Створити необхідні папки для медіа
     */
    public static function create_media_directories() {
        $dirs = [
            self::get_media_base_path(),
            self::get_images_path(),
            self::get_thumbnails_path(),
            trailingslashit(self::get_media_base_path()) . self::MEDIA_VIDEOS_DIR
        ];
        
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
                
                // Додати index.php для безпеки
                $index_file = trailingslashit($dir) . 'index.php';
                if (!file_exists($index_file)) {
                    file_put_contents($index_file, "<?php\n// Silence is golden");
                }
            }
        }
        
        // Додати .htaccess для захисту
        $htaccess_file = trailingslashit(self::get_media_base_path()) . '.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "# Lapki Media Protection\n";
            $htaccess_content .= "Options -Indexes\n";
            $htaccess_content .= "<Files *.php>\n";
            $htaccess_content .= "    Deny from all\n";
            $htaccess_content .= "</Files>\n";
            file_put_contents($htaccess_file, $htaccess_content);
        }
    }
    
    /**
     * Перевірити чи існує зображення
     * 
     * @param string $filename Назва файлу
     * @param bool $thumbnail Перевірити thumbnail
     * @return bool
     */
    public static function image_exists($filename, $thumbnail = false) {
        if (empty($filename)) {
            return false;
        }
        
        $filepath = self::get_image_path($filename, $thumbnail);
        return file_exists($filepath);
    }
    
    /**
     * Генерувати унікальну назву файлу
     * 
     * @param string $original_name Оригінальна назва
     * @param string $animal_name Кличка тварини (опціонально)
     * @return string Унікальна назва файлу
     */
    public static function generate_filename($original_name, $animal_name = '') {
        $pathinfo = pathinfo($original_name);
        $extension = strtolower($pathinfo['extension']);
        
        // Санітизація назви тварини
        if (!empty($animal_name)) {
            $clean_name = sanitize_file_name(transliterator_transliterate(
                'Russian-Latin/BGN; Any-Latin; Latin-ASCII',
                $animal_name
            ));
            $clean_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $clean_name);
        } else {
            $clean_name = 'animal';
        }
        
        // Додати timestamp для унікальності
        $timestamp = time();
        $random = wp_generate_password(4, false);
        
        return strtolower($clean_name . '_' . $timestamp . '_' . $random . '.' . $extension);
    }
    
    /**
     * Створити thumbnail зображення
     * 
     * @param string $filename Назва файлу
     * @return bool Успішність створення
     */
    public static function create_thumbnail($filename) {
        $source_path = self::get_image_path($filename);
        $thumb_path = self::get_image_path($filename, true);
        
        if (!file_exists($source_path)) {
            return false;
        }
        
        // Використати WordPress функції для створення thumbnail
        $editor = wp_get_image_editor($source_path);
        
        if (is_wp_error($editor)) {
            return false;
        }
        
        // Resize зображення
        $editor->resize(self::THUMB_WIDTH, self::THUMB_HEIGHT, true);
        
        // Зберегти thumbnail
        $result = $editor->save($thumb_path);
        
        return !is_wp_error($result);
    }
    
    /**
     * Видалити файли зображення (оригінал + thumbnail)
     * 
     * @param string $filename Назва файлу
     * @return bool Успішність видалення
     */
    public static function delete_image($filename) {
        if (empty($filename)) {
            return false;
        }
        
        $deleted = true;
        
        // Видалити оригінал
        $original_path = self::get_image_path($filename);
        if (file_exists($original_path)) {
            $deleted = $deleted && unlink($original_path);
        }
        
        // Видалити thumbnail
        $thumb_path = self::get_image_path($filename, true);
        if (file_exists($thumb_path)) {
            $deleted = $deleted && unlink($thumb_path);
        }
        
        return $deleted;
    }
    
}

// Ініціалізація плагіна
Lapki_Main::init();
