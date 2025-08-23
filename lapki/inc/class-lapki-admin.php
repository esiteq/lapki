<?php

/**
 * Lapki Admin Animals Table
 * Адмін панель для перегляду тварин з пагінацією і сортуванням
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Lapki_Admin {
    
    public static function init() {
        add_action('admin_init', [__CLASS__, 'load_dependencies']);
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_scripts']);
    }
    
    /**
     * Завантажити залежності
     */
    public static function load_dependencies() {
        if (!class_exists('WP_List_Table')) {
            require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
        }
    }
    
    /**
     * Додати пункти меню в адмінці
     */
    public static function add_admin_menu() {
        // Головне меню Lapki
        add_menu_page(
            'Lapki - Тварини',
            'Lapki',
            'manage_options',
            'lapki',
            [__CLASS__, 'animals_page'],
            'dashicons-pets',
            30
        );
        
        // Підменю Animals
        add_submenu_page(
            'lapki',
            'Тварини',
            'Тварини',
            'manage_options',
            'lapki',
            [__CLASS__, 'animals_page']
        );
        
        // Підменю Organizations (для майбутнього)
        add_submenu_page(
            'lapki',
            'Організації',
            'Організації', 
            'manage_options',
            'lapki-organizations',
            [__CLASS__, 'organizations_page']
        );
    }
    
    /**
     * Завантажити скрипти для адміна
     */
    public static function admin_scripts($hook) {
        if (strpos($hook, 'lapki') !== false) {
            wp_enqueue_style('lapki-admin', LAPKI_PLUGIN_URL . 'css/lapki-admin.css', [], LAPKI_VERSION);
        }
    }
    
    /**
     * Сторінка зі списком тварин
     */
    public static function animals_page() {
        $animals_table = new Lapki_Animals_List_Table();
        $animals_table->prepare_items();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Тварини</h1>
            <a href="<?php echo admin_url('admin.php?page=lapki-add-animal'); ?>" class="page-title-action">Додати тварину</a>
            <hr class="wp-header-end">
            
            <?php $animals_table->views(); ?>
            
            <form method="get">
                <input type="hidden" name="page" value="lapki" />
                <?php 
                $animals_table->search_box('Пошук тварин', 'animal');
                $animals_table->display(); 
                ?>
            </form>
        </div>
        
        <style>
        .wp-list-table .column-photo {
            width: 80px;
        }
        .wp-list-table .column-photo img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        .wp-list-table .column-status {
            width: 100px;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
        }
        .status-adoptable {
            background: #d1f2d1;
            color: #2e7d2e;
        }
        .status-adopted {
            background: #e0e0e0;
            color: #666;
        }
        .status-hold {
            background: #fff3cd;
            color: #856404;
        }
        .animal-name {
            font-weight: 600;
        }
        .animal-details {
            color: #666;
            font-size: 13px;
            margin-top: 2px;
        }
        .organization-name {
            color: #0073aa;
        }
        </style>
        <?php
    }
    
    /**
     * Сторінка організацій (заглушка)
     */
    public static function organizations_page() {
        echo '<div class="wrap"><h1>Організації</h1><p>Скоро буде...</p></div>';
    }
}

/**
 * Клас для відображення таблиці тварин
 */
class Lapki_Animals_List_Table extends WP_List_Table {
    
    public function __construct() {
        // Перевірити що WP_List_Table завантажений
        if (!class_exists('WP_List_Table')) {
            require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
        }
        
        parent::__construct([
            'singular' => 'animal',
            'plural'   => 'animals',
            'ajax'     => false
        ]);
    }
    
    /**
     * Колонки таблиці
     */
    public function get_columns() {
        return [
            'cb'           => '<input type="checkbox" />',
            'photo'        => 'Фото',
            'name'         => 'Кличка',
            'type_species' => 'Тип/Вид',
            'breed'        => 'Порода',
            'age_gender'   => 'Вік/Стать',
            'status'       => 'Статус',
            'organization' => 'Організація',
            'published_at' => 'Дата додавання'
        ];
    }
    
    /**
     * Сортовані колонки
     */
    public function get_sortable_columns() {
        return [
            'name'         => ['name', false],
            'type_species' => ['type', false],
            'age_gender'   => ['age', false],
            'status'       => ['status', false],
            'published_at' => ['published_at', true] // за замовчуванням сортування
        ];
    }
    
    /**
     * Чекбокс для масових дій
     */
    protected function column_cb($item) {
        return sprintf('<input type="checkbox" name="animals[]" value="%d" />', $item['id']);
    }
    
    /**
     * Колонка з фото
     */
    protected function column_photo($item) {
        $photo_url = $this->get_primary_photo($item['id']);
        
        if ($photo_url) {
            return sprintf(
                '<img src="%s" alt="%s" style="width:60px;height:60px;object-fit:cover;border-radius:4px;" />',
                esc_url($photo_url),
                esc_attr($item['name'])
            );
        }
        
        // Плейсхолдер якщо немає фото
        return '<div style="width:60px;height:60px;background:#f0f0f0;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#999;font-size:24px;">📷</div>';
    }
    
    /**
     * Колонка з кличкою (головна)
     */
    protected function column_name($item) {
        $actions = [
            'view' => sprintf('<a href="#">Переглянути</a>'),
            'edit' => sprintf('<a href="#">Редагувати</a>'),
            'delete' => sprintf('<a href="#" style="color:#a00;">Видалити</a>')
        ];
        
        $name = sprintf('<strong class="animal-name">%s</strong>', esc_html($item['name']));
        
        if (!empty($item['description'])) {
            $description = wp_trim_words($item['description'], 15);
            $name .= sprintf('<div class="animal-details">%s</div>', esc_html($description));
        }
        
        return $name . $this->row_actions($actions);
    }
    
    /**
     * Колонка тип/вид
     */
    protected function column_type_species($item) {
        $type_display = $this->get_attribute_display('animal', 'type', 'species', $item['type'], 'uk');
        $species_display = !empty($item['species']) ? $item['species'] : '';
        
        $output = $type_display ?: ucfirst($item['type']);
        if ($species_display && $species_display !== $type_display) {
            $output .= '<br><small style="color:#666;">' . esc_html($species_display) . '</small>';
        }
        
        return $output;
    }
    
    /**
     * Колонка порода
     */
    protected function column_breed($item) {
        $breeds = [];
        
        if (!empty($item['breed_primary'])) {
            $breed_display = $this->get_attribute_display('animal', $item['type'], 'breed', $item['breed_primary'], 'uk');
            $breeds[] = $breed_display ?: $item['breed_primary'];
        }
        
        if (!empty($item['breed_secondary'])) {
            $breed_display = $this->get_attribute_display('animal', $item['type'], 'breed', $item['breed_secondary'], 'uk');
            $breeds[] = $breed_display ?: $item['breed_secondary'];
        }
        
        if (empty($breeds)) {
            return $item['breed_mixed'] ? 'Метис' : 'Невідомо';
        }
        
        $result = implode(' + ', $breeds);
        if ($item['breed_mixed']) {
            $result .= ' <small>(метис)</small>';
        }
        
        return $result;
    }
    
    /**
     * Колонка вік/стать
     */
    protected function column_age_gender($item) {
        $age_display = $this->get_attribute_display('animal', 'age', 'age', $item['age'], 'uk');
        $gender_display = $this->get_attribute_display('animal', 'all', 'gender', $item['gender'], 'uk');
        
        $age = $age_display ?: ucfirst($item['age']);
        $gender = $gender_display ?: ucfirst($item['gender']);
        
        return $age . '<br><small style="color:#666;">' . $gender . '</small>';
    }
    
    /**
     * Колонка статус
     */
    protected function column_status($item) {
        $status_map = [
            'adoptable' => ['Доступна', 'status-adoptable'],
            'adopted'   => ['Прилаштована', 'status-adopted'],
            'hold'      => ['На утриманні', 'status-hold'],
            'found'     => ['Знайдена', 'status-hold']
        ];
        
        $status_info = $status_map[$item['status']] ?? ['Невідомо', 'status-adoptable'];
        
        return sprintf(
            '<span class="status-badge %s">%s</span>',
            $status_info[1],
            $status_info[0]
        );
    }
    
    /**
     * Колонка організація
     */
    protected function column_organization($item) {
        if (!empty($item['organization_name'])) {
            return sprintf('<span class="organization-name">%s</span>', esc_html($item['organization_name']));
        }
        return '<span style="color:#999;">Не вказано</span>';
    }
    
    /**
     * Колонка дата
     */
    protected function column_published_at($item) {
        if (!empty($item['published_at']) && $item['published_at'] !== '0000-00-00 00:00:00') {
            $date = new DateTime($item['published_at']);
            return $date->format('d.m.Y H:i');
        }
        return '-';
    }
    
    /**
     * Дефолтна колонка
     */
    protected function column_default($item, $column_name) {
        return $item[$column_name] ?? '-';
    }
    
    /**
     * Підготувати дані для таблиці
     */
    public function prepare_items() {
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;
        
        // Параметри пошуку і сортування
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $orderby = isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'published_at';
        $order = isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'desc';
        
        // Отримати дані через модель
        $search_params = [
            'search' => $search,
            'limit' => $per_page,
            'offset' => $offset,
            'order_by' => $orderby,
            'order' => strtoupper($order)
        ];
        
        $animals = Lapki_Animal::search($search_params);
        $total_items = $this->get_total_animals($search);
        
        $this->items = $animals;
        
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
        
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];
    }
    
    /**
     * Отримати загальну кількість тварин
     */
    private function get_total_animals($search = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'lapki_animals';
        $where = "WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $where .= " AND (a.name LIKE %s OR a.description LIKE %s OR a.breed_primary LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        $sql = "SELECT COUNT(*) FROM {$table_name} a {$where}";
        
        if (!empty($params)) {
            return $wpdb->get_var($wpdb->prepare($sql, $params));
        }
        
        return $wpdb->get_var($sql);
    }
    
    /**
     * Отримати головне фото тварини
     */
    /*
    private function get_primary_photo($animal_id) {
        global $wpdb;
        
        $media_table = $wpdb->prefix . 'lapki_media';
        
        $photo = $wpdb->get_var($wpdb->prepare("
            SELECT file_path 
            FROM {$media_table} 
            WHERE entity_type = 'animal' 
            AND entity_id = %d 
            AND media_type = 'photo' 
            AND is_active = 1 
            ORDER BY is_primary DESC, sort_order ASC 
            LIMIT 1
        ", $animal_id));
        
        return $photo ? wp_upload_dir()['baseurl'] . $photo : '';
    }
    */
    /**
 * Отримати головне фото тварини (оновлений метод)
 */
private function get_primary_photo($animal_id) {
    // Використовувати новий метод з Lapki_Media
    return Lapki_Media::get_primary_photo_url('animal', $animal_id, true); // true = thumbnail
}

/**
 * Альтернативно, якщо хочеш більше контролю:
 */
private function get_primary_photo_advanced($animal_id) {
    $photo = Lapki_Media::get_primary_photo('animal', $animal_id);
    
    if ($photo && !empty($photo['file_path'])) {
        // Повернути thumbnail URL
        return $photo['thumbnail_url'];
    }
    
    return '';
}

    /**
     * Отримати відображуване значення атрибута
     */
    private function get_attribute_display($entity, $entity_type, $attr_name, $attr_value, $lang = 'uk') {
        // 🔥 ВИКОРИСТОВУВАТИ КЕШ СПОЧАТКУ
        if (class_exists('Lapki_Cache')) {
            $cached_value = Lapki_Cache::get_attribute_display_fast($entity, $entity_type, $attr_name, $attr_value, $lang);
            if ($cached_value !== null) {
                return $cached_value;
            }
        }
        
        // Fallback через Lapki_Main
        return Lapki_Main::get_attribute_display($entity, $entity_type, $attr_name, $attr_value, $lang);
    }
    
    /**
     * Масові дії (поки пусто)
     */
    protected function get_bulk_actions() {
        return [
            'delete' => 'Видалити'
        ];
    }
    
    /**
     * Відображення коли немає даних
     */
    public function no_items() {
        echo 'Тварини не знайдено. <a href="#">Додати першу тварину</a>';
    }
}

// Ініціалізація адмін панелі
Lapki_Admin::init();

?>