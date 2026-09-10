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
define('LAPKI_VERSION', '2.0.45');
define('LAPKI_PLUGIN_FILE', __FILE__);
define('LAPKI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LAPKI_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once LAPKI_PLUGIN_DIR . 'inc/class-lapki-i18n.php';
Lapki_I18n::init();

require_once LAPKI_PLUGIN_DIR . 'inc/class-lapki-models.php';
require_once LAPKI_PLUGIN_DIR . 'inc/class-lapki-roles.php';
require_once LAPKI_PLUGIN_DIR . 'inc/class-lapki-migrations.php';
require_once LAPKI_PLUGIN_DIR . 'inc/class-lapki-rest-api.php';
require_once LAPKI_PLUGIN_DIR . 'inc/class-lapki-ai.php';
Lapki_AI_Manager::init();

require_once LAPKI_PLUGIN_DIR . 'inc/class-lapki-template-loader.php';
require_once LAPKI_PLUGIN_DIR . 'inc/class-lapki-frontend.php';
Lapki_Frontend::init();

require_once LAPKI_PLUGIN_DIR . 'inc/class-lapki-sitemaps.php';
Lapki_Sitemaps::init();

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
    const MEDIA_ORG_DIR = 'org';
    const MEDIA_USER_DIR = 'user';
    
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
     * Отримати шлях до папки зображень. $scope='organization' веде в
     * окрему підпапку uploads/lapki/org/ — фото притулків не змішуються
     * з фото тварин (uploads/lapki/images/, дефолтна поведінка, як і раніше).
     */
    private static function get_scope_dir($scope) {
        if ($scope === 'organization') {
            return self::MEDIA_ORG_DIR . '/';
        }
        if ($scope === 'user') {
            return self::MEDIA_USER_DIR . '/';
        }
        return '';
    }

    public static function get_images_path($scope = 'animal') {
        $base = trailingslashit(self::get_media_base_path());
        return $base . self::get_scope_dir($scope) . self::MEDIA_IMAGES_DIR;
    }

    /**
     * Отримати URL до папки зображень
     */
    public static function get_images_url($scope = 'animal') {
        $base = trailingslashit(self::get_media_base_url());
        return $base . self::get_scope_dir($scope) . self::MEDIA_IMAGES_DIR;
    }

    /**
     * Отримати шлях до папки thumbnails
     */
    public static function get_thumbnails_path($scope = 'animal') {
        $base = trailingslashit(self::get_media_base_path());
        return $base . self::get_scope_dir($scope) . self::MEDIA_THUMBNAILS_DIR;
    }

    /**
     * Отримати URL до папки thumbnails
     */
    public static function get_thumbnails_url($scope = 'animal') {
        $base = trailingslashit(self::get_media_base_url());
        return $base . self::get_scope_dir($scope) . self::MEDIA_THUMBNAILS_DIR;
    }

    /**
     * Отримати повний URL зображення за назвою файлу
     *
     * @param string $filename Назва файлу (vasya_cat.jpg)
     * @param bool $thumbnail Чи потрібен thumbnail
     * @param string $scope 'animal' (дефолт), 'organization' або 'user' — окремі теки
     * @return string Повний URL
     */
    public static function get_image_url($filename, $thumbnail = false, $scope = 'animal') {
        if (empty($filename)) {
            return '';
        }

        if ($thumbnail) {
            return trailingslashit(self::get_thumbnails_url($scope)) . $filename;
        }

        return trailingslashit(self::get_images_url($scope)) . $filename;
    }

    /**
     * Отримати повний шлях до файлу зображення
     *
     * @param string $filename Назва файлу (vasya_cat.jpg)
     * @param bool $thumbnail Чи потрібен thumbnail
     * @param string $scope 'animal' (дефолт), 'organization' або 'user' — окремі теки
     * @return string Повний шлях
     */
    public static function get_image_path($filename, $thumbnail = false, $scope = 'animal') {
        if (empty($filename)) {
            return '';
        }

        if ($thumbnail) {
            return trailingslashit(self::get_thumbnails_path($scope)) . $filename;
        }

        return trailingslashit(self::get_images_path($scope)) . $filename;
    }

    /**
     * Створити необхідні папки для медіа (тварини + окремо організації)
     */
    public static function create_media_directories() {
        $dirs = [
            self::get_media_base_path(),
            self::get_images_path(),
            self::get_thumbnails_path(),
            self::get_images_path('organization'),
            self::get_thumbnails_path('organization'),
            self::get_images_path('user'),
            self::get_thumbnails_path('user'),
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
    public static function image_exists($filename, $thumbnail = false, $scope = 'animal') {
        if (empty($filename)) {
            return false;
        }

        $filepath = self::get_image_path($filename, $thumbnail, $scope);
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
    public static function create_thumbnail($filename, $scope = 'animal') {
        $source_path = self::get_image_path($filename, false, $scope);
        $thumb_path = self::get_image_path($filename, true, $scope);

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
    public static function delete_image($filename, $scope = 'animal') {
        if (empty($filename)) {
            return false;
        }

        $deleted = true;

        // Видалити оригінал
        $original_path = self::get_image_path($filename, false, $scope);
        if (file_exists($original_path)) {
            $deleted = $deleted && unlink($original_path);
        }

        // Видалити thumbnail
        $thumb_path = self::get_image_path($filename, true, $scope);
        if (file_exists($thumb_path)) {
            $deleted = $deleted && unlink($thumb_path);
        }
        
        return $deleted;
    }

    /**
     * Назва типу тварини для показу. Для котів — "кіт"/"кішка" залежно
     * від статі (українською звучить природно), для решти типів — єдина
     * назва незалежно від статі ("пес"/"сука" звучали б неприродно).
     */
    public static function get_animal_type_label($type, $gender = '', $capitalize = false) {
        if ($type === 'cat') {
            $label = ($gender === 'female') ? __('кішка', 'lapki') : __('кіт', 'lapki');
        } else {
            $labels = [
                'dog'    => __('собака', 'lapki'),
                'bird'   => __('птах', 'lapki'),
                'rabbit' => __('кролик', 'lapki'),
                'other'  => __('інше', 'lapki'),
            ];
            $label = $labels[$type] ?? $type;
        }

        if ($capitalize) {
            $label = mb_strtoupper(mb_substr($label, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($label, 1, null, 'UTF-8');
        }

        return $label;
    }

    /**
     * Область (wp_lapki_geo.oblast, а звідти — address_state/state) зберігається
     * у прикметниковій формі без іменника ("Запорізька"), крім АР Крим (уже
     * повна назва) — на відображенні дописуємо "область" для завершеної фрази.
     */
    public static function format_oblast($oblast) {
        if (empty($oblast)) {
            return $oblast;
        }
        if (mb_strpos($oblast, 'область') !== false || mb_strpos($oblast, 'Республіка') !== false) {
            return $oblast;
        }
        return $oblast . ' область';
    }

    /**
     * Обласний центр кожної області (+АР Крим) — фіксований, стабільний
     * список 24 міст (не виводиться алгоритмічно: назва обласного центру не
     * завжди морфологічно споріднена з назвою області, напр. Кропивницький
     * у Кіровоградській). Київ і Севастополь — міста зі спеціальним статусом,
     * не входять до жодної області (у wp_lapki_geo їхнє поле oblast порожнє),
     * тому в цей список не додаються — обробляються окремо нижче.
     */
    private const OBLAST_CENTERS = [
        'Вінницька' => 'Вінниця',
        'Волинська' => 'Луцьк',
        'Дніпропетровська' => 'Дніпро',
        'Донецька' => 'Донецьк',
        'Житомирська' => 'Житомир',
        'Закарпатська' => 'Ужгород',
        'Запорізька' => 'Запоріжжя',
        'Івано-Франківська' => 'Івано-Франківськ',
        'Кіровоградська' => 'Кропивницький',
        'Луганська' => 'Луганськ',
        'Львівська' => 'Львів',
        'Миколаївська' => 'Миколаїв',
        'Одеська' => 'Одеса',
        'Полтавська' => 'Полтава',
        'Рівненська' => 'Рівне',
        'Сумська' => 'Суми',
        'Тернопільська' => 'Тернопіль',
        'Харківська' => 'Харків',
        'Херсонська' => 'Херсон',
        'Хмельницька' => 'Хмельницький',
        'Черкаська' => 'Черкаси',
        'Чернівецька' => 'Чернівці',
        'Чернігівська' => 'Чернігів',
        'Автономна Республіка Крим' => 'Сімферополь',
    ];

    /**
     * Населений пункт — обласний центр? Звіряємо ОДНОЧАСНО назву, область і
     * тип 'місто' (не лише назву+область) — у довіднику є села-омоніми
     * (напр. два села "Запоріжжя" в самій Запорізькій області, крім міста).
     */
    private static function is_oblast_center($name, $oblast, $type) {
        return $type === 'місто' && ($oblast === '' || (self::OBLAST_CENTERS[$oblast] ?? null) === $name);
    }

    /**
     * Український топонім (назва населеного пункту) → можливі "основи" для
     * зіставлення з прикметниковою формою назви громади. Враховує типові
     * чергування приголосних перед суфіксом -ськ- (класична палаталізація
     * к↔ц, г↔з, х↔с: Кропивницький/Кропивницька, Запоріжжя/Запорізька).
     */
    private static function toponym_stems($name) {
        $stems = [$name];
        foreach (['ий', 'а', 'я', 'о', 'ів', 'е', 'ь'] as $suffix) {
            $suffix_len = mb_strlen($suffix, 'UTF-8');
            if (mb_substr($name, -$suffix_len, null, 'UTF-8') === $suffix
                && mb_strlen($name, 'UTF-8') > $suffix_len + 2) {
                $stems[] = mb_substr($name, 0, -$suffix_len, 'UTF-8');
            }
        }

        $alternations = [];
        foreach ($stems as $stem) {
            $last = mb_substr($stem, -1, null, 'UTF-8');
            $rest = mb_substr($stem, 0, -1, 'UTF-8');
            if ($last === 'к') $alternations[] = $rest . 'ц';
            if ($last === 'г') $alternations[] = $rest . 'з';
            if ($last === 'х') $alternations[] = $rest . 'с';
            if ($last === 'й') $alternations[] = $rest;
        }

        return array_unique(array_merge($stems, $alternations));
    }

    /**
     * UTF-8-безпечна відстань Левенштейна (вбудований levenshtein() у PHP
     * рахує байти, а не символи — для кирилиці це майже вдвічі завищує
     * відстань і ламає поріг збігу нижче).
     */
    private static function mb_levenshtein($a, $b) {
        $a = mb_str_split(mb_strtolower($a, 'UTF-8'));
        $b = mb_str_split(mb_strtolower($b, 'UTF-8'));
        $la = count($a);
        $lb = count($b);
        if ($la === 0) return $lb;
        if ($lb === 0) return $la;

        $prev = range(0, $lb);
        for ($i = 1; $i <= $la; $i++) {
            $cur = [$i];
            for ($j = 1; $j <= $lb; $j++) {
                $cost = ($a[$i - 1] === $b[$j - 1]) ? 0 : 1;
                $cur[$j] = min($prev[$j] + 1, $cur[$j - 1] + 1, $prev[$j - 1] + $cost);
            }
            $prev = $cur;
        }
        return $prev[$lb];
    }

    /**
     * Населений пункт — адміністративний центр СВОЄЇ громади (районний
     * центр — після реформи 2020 року громаду завжди називають за її
     * центром)? Довідник не містить явного прапорця "центр громади", тож
     * визначаємо зіставленням назви населеного пункту з прикметниковою
     * формою назви громади (з урахуванням чергувань приголосних вище) —
     * зважений поріг 3 підібраний і перевірений на всіх 29711 записах
     * довідника: 872/924 громад з міським населеним пунктом розпізнаються
     * однозначно, решта (переважно складені назви на кшталт "Кривий Ріг" →
     * "Криворізька", які прямий підрахунок стемів не покриває, і кілька
     * історичних кримських назв) — безпечно деградують до відображення й
     * назви громади (тобто просто трохи докладніший підпис, не помилка).
     */
    private static function is_hromada_center($settlement_name, $hromada) {
        if (empty($hromada)) {
            return false;
        }

        $hromada_stems = [$hromada];
        foreach (['ська', 'цька', 'зька', 'ка', 'а'] as $suffix) {
            $suffix_len = mb_strlen($suffix, 'UTF-8');
            if (mb_substr($hromada, -$suffix_len, null, 'UTF-8') === $suffix) {
                $hromada_stems[] = mb_substr($hromada, 0, -$suffix_len, 'UTF-8');
            }
        }

        $best = PHP_INT_MAX;
        foreach (self::toponym_stems($settlement_name) as $candidate) {
            foreach ($hromada_stems as $hromada_stem) {
                $best = min($best, self::mb_levenshtein($candidate, $hromada_stem));
            }
        }

        return $best <= 3;
    }

    /**
     * Підпис місцезнаходження тварини/організації за трьома рівнями:
     * обласний центр — лише назва; районний центр — назва + область;
     * інше (звичайний населений пункт громади) — назва + область + громада.
     * Потребує довідникового katottg (щоб знати область/громаду/тип) — для
     * старих записів без нього (вільний текст до впровадження довідника)
     * лишається стара поведінка: назва (+ область, якщо відома окремо).
     */
    public static function format_city_location($city_name, $katottg_code, $fallback_oblast = '') {
        if (empty($city_name)) {
            return '';
        }

        $geo = !empty($katottg_code) ? Lapki_Geo::get_by_katottg_code($katottg_code) : null;

        if (!$geo) {
            return !empty($fallback_oblast)
                ? $city_name . ', ' . self::format_oblast($fallback_oblast)
                : $city_name;
        }

        return self::format_city_location_from_geo($geo);
    }

    /**
     * Те саме, що format_city_location(), але без повторного SQL-запиту до
     * wp_lapki_geo — для викликів, де рядок довідника вже під рукою (напр.
     * Lapki_Animal::maybe_fill_coordinates_from_city(), яка й так підтягує
     * $geo для координат/області).
     */
    public static function format_city_location_from_geo($geo) {
        if (self::is_oblast_center($geo['name'], $geo['oblast'], $geo['type'])) {
            return $geo['name'];
        }

        if (self::is_hromada_center($geo['name'], $geo['hromada'])) {
            return $geo['name'] . ', ' . self::format_oblast($geo['oblast']);
        }

        return $geo['name'] . ', ' . self::format_oblast($geo['oblast']) . ', ' . $geo['hromada'] . ' громада';
    }

}

// Ініціалізація плагіна
Lapki_Main::init();
