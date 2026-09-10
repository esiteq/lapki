<?php

/**
 * Lapki Admin Animals Table
 * Адмін панель для перегляду тварин з пагінацією і сортуванням
 */

if (! class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Таблиця шаблонів email-повідомлень (Lapki → Email-шаблони). Дані
 * рендеряться сервером напряму з БД (без AJAX-завантаження) — subject/body/
 * placeholders кожного рядка кладуться в data-* атрибути посилання
 * "Редагувати", щоб JS міг заповнити модалку без додаткового REST-запиту.
 */
class Lapki_Email_Templates_List_Table extends WP_List_Table
{
    public function __construct()
    {
        parent::__construct([
            'singular' => 'email_template',
            'plural'   => 'email_templates',
            'ajax'     => false,
        ]);
    }

    public function get_columns()
    {
        return [
            'name'       => __('Призначення', 'lapki'),
            'subject'    => __('Тема (Subject)', 'lapki'),
            'updated_at' => __('Дата редагування', 'lapki'),
        ];
    }

    /**
     * Дані для таблиці йдуть через той самий REST-контролер, що й JS
     * (GET /lapki/v1/email-templates), диспетчеризований у процесі через
     * rest_do_request() — без прямого запиту до моделі й без зайвого
     * HTTP round-trip. Це тримає єдину точку доступу до даних (REST) навіть
     * для серверного рендеру WP_List_Table.
     */
    public function prepare_items()
    {
        $this->_column_headers = [$this->get_columns(), [], []];

        $request  = new WP_REST_Request('GET', '/lapki/v1/email-templates');
        $response = rest_do_request($request);

        $this->items = !is_wp_error($response) ? ($response->get_data()['data'] ?? []) : [];
    }

    public function no_items()
    {
        esc_html_e('Немає шаблонів', 'lapki');
    }

    protected function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'subject':
                return esc_html($item['subject']);
            case 'updated_at':
                return !empty($item['updated_at'])
                    ? esc_html(mysql2date('d.m.Y H:i', $item['updated_at']))
                    : '—';
            default:
                return '';
        }
    }

    protected function column_name($item)
    {
        $edit_link = sprintf(
            '<a href="#" class="lapki-email-edit" data-id="%1$d" data-name="%2$s" data-subject="%3$s" data-body="%4$s" data-placeholders="%5$s">%6$s</a>',
            $item['id'],
            esc_attr($item['name']),
            esc_attr($item['subject']),
            esc_attr($item['body']),
            esc_attr($item['placeholders']),
            esc_html__('Редагувати', 'lapki')
        );

        return sprintf('%1$s %2$s', esc_html($item['name']), $this->row_actions(['edit' => $edit_link]));
    }
}

class Lapki_Admin
{

    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_scripts']);
        add_action('admin_init', [__CLASS__, 'register_settings']);

        // Метабокс "Мова" на записах блогу (post_type=post) — постмета 'lang'
        // ('uk'/'en'), за нею фільтрується список на /blog/ (templates/blog.php)
        add_action('add_meta_boxes_post', [__CLASS__, 'add_blog_lang_meta_box']);
        add_action('save_post_post', [__CLASS__, 'save_blog_lang_meta']);
        add_filter('manage_post_posts_columns', [__CLASS__, 'add_blog_lang_column']);
        add_action('manage_post_posts_custom_column', [__CLASS__, 'render_blog_lang_column'], 10, 2);
    }

    /**
     * Метабокс "Мова" на записах блогу — визначає, під яким UA/EN-перемикачем
     * (/blog/) з'явиться запис (Lapki_I18n::get_lang() + postmeta 'lang').
     */
    public static function add_blog_lang_meta_box()
    {
        add_meta_box(
            'lapki_blog_lang',
            __('Мова (блог)', 'lapki'),
            [__CLASS__, 'render_blog_lang_meta_box'],
            'post',
            'side',
            'default'
        );
    }

    public static function render_blog_lang_meta_box($post)
    {
        wp_nonce_field('lapki_blog_lang_save', 'lapki_blog_lang_nonce');
        $lang = get_post_meta($post->ID, 'lang', true);
        if ($lang !== 'en') {
            $lang = 'uk'; // дефолт — записи спершу пишуться українською
        }
        ?>
        <label style="display:block;margin-bottom:6px;">
            <input type="radio" name="lapki_blog_lang" value="uk" <?php checked($lang, 'uk'); ?>> <?php esc_html_e('Українська', 'lapki'); ?>
        </label>
        <label style="display:block;">
            <input type="radio" name="lapki_blog_lang" value="en" <?php checked($lang, 'en'); ?>> <?php esc_html_e('English', 'lapki'); ?>
        </label>
        <p class="description" style="margin-top:8px;">
            <?php esc_html_e('Визначає, на якій мовній версії /blog/ з\'явиться цей запис.', 'lapki'); ?>
        </p>
        <?php
        // Пов'язаний запис іншою мовою — за ним перемикач UA/EN на самій
        // сторінці запису (single-blog.php) веде саме на "той самий" пост
        // іншою мовою, а не на архів блогу. Зв'язок необов'язковий: без
        // нього перемикач і далі веде на архів (Lapki_Frontend).
        $pair_id = (int) get_post_meta($post->ID, 'lapki_blog_pair_id', true);
        $other_posts = get_posts([
            'post_type' => 'post',
            'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
            'posts_per_page' => -1,
            'exclude' => [$post->ID],
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
        ?>
        <hr style="margin:12px 0;">
        <label for="lapki_blog_pair_id" style="display:block;margin-bottom:4px;">
            <?php esc_html_e('Пов\'язаний запис (переклад)', 'lapki'); ?>
        </label>
        <select name="lapki_blog_pair_id" id="lapki_blog_pair_id" style="width:100%;">
            <option value="0"><?php esc_html_e('— немає —', 'lapki'); ?></option>
            <?php foreach ($other_posts as $other) :
                $other_lang = get_post_meta($other->ID, 'lang', true) === 'en' ? 'EN' : 'UA';
            ?>
                <option value="<?php echo (int) $other->ID; ?>" <?php selected($pair_id, $other->ID); ?>>
                    [<?php echo esc_html($other_lang); ?>] <?php echo esc_html($other->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description" style="margin-top:8px;">
            <?php esc_html_e('Виберіть цей самий допис іншою мовою — зв\'язок встановлюється в обидва боки автоматично.', 'lapki'); ?>
        </p>
        <?php
    }

    public static function save_blog_lang_meta($post_id)
    {
        if (!isset($_POST['lapki_blog_lang_nonce']) || !wp_verify_nonce($_POST['lapki_blog_lang_nonce'], 'lapki_blog_lang_save')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $lang = isset($_POST['lapki_blog_lang']) && $_POST['lapki_blog_lang'] === 'en' ? 'en' : 'uk';
        update_post_meta($post_id, 'lang', $lang);

        $new_pair_id = isset($_POST['lapki_blog_pair_id']) ? absint($_POST['lapki_blog_pair_id']) : 0;
        $old_pair_id = (int) get_post_meta($post_id, 'lapki_blog_pair_id', true);

        // Якщо зв'язок змінили/прибрали — розірвати зворотнє посилання на
        // старому парі (інакше він і далі "думав" би, що пов'язаний із цим
        // записом, хоча цей запис уже вказує на когось іншого/нікого).
        if ($old_pair_id && $old_pair_id !== $new_pair_id) {
            $reverse = (int) get_post_meta($old_pair_id, 'lapki_blog_pair_id', true);
            if ($reverse === $post_id) {
                delete_post_meta($old_pair_id, 'lapki_blog_pair_id');
            }
        }

        if ($new_pair_id && get_post($new_pair_id)) {
            update_post_meta($post_id, 'lapki_blog_pair_id', $new_pair_id);
            update_post_meta($new_pair_id, 'lapki_blog_pair_id', $post_id); // зв'язок в обидва боки
        } else {
            delete_post_meta($post_id, 'lapki_blog_pair_id');
        }
    }

    public static function add_blog_lang_column($columns)
    {
        // Одразу після "Автор" — до дати, як зазвичай очікує око в списку
        $new = [];
        foreach ($columns as $key => $label) {
            $new[$key] = $label;
            if ($key === 'author') {
                $new['lapki_lang'] = __('Мова', 'lapki');
            }
        }
        return $new;
    }

    public static function render_blog_lang_column($column, $post_id)
    {
        if ($column !== 'lapki_lang') {
            return;
        }
        $lang = get_post_meta($post_id, 'lang', true);
        echo $lang === 'en' ? 'EN' : 'UA';
    }
    /**
     * Додати пункти меню в адмінці
     */
    public static function add_admin_menu()
    {
        // Головне меню Lapki
        add_menu_page(
            __('Lapki - Тварини', 'lapki'),
            __('Lapki', 'lapki'),
            'manage_options',
            'lapki',
            [__CLASS__, 'animals_page'],
            'dashicons-pets',
            30
        );

        // Підменю Animals
        add_submenu_page(
            'lapki',
            __('Тварини', 'lapki'),
            __('Тварини', 'lapki'),
            'manage_options',
            'lapki',
            [__CLASS__, 'animals_page']
        );

        // Підменю Add Animal
        add_submenu_page(
            'lapki',
            __('Додати тварину', 'lapki'),
            __('Додати тварину', 'lapki'),
            'manage_options',
            'lapki-add-animal',
            [__CLASS__, 'add_edit_animal_page']
        );

        // Підменю Organizations
        add_submenu_page(
            'lapki',
            __('Організації', 'lapki'),
            __('Організації', 'lapki'),
            'manage_options',
            'lapki-organizations',
            [__CLASS__, 'organizations_page']
        );

        // Підменю Add Organization
        add_submenu_page(
            'lapki',
            __('Додати організацію', 'lapki'),
            __('Додати організацію', 'lapki'),
            'manage_options',
            'lapki-add-organization',
            [__CLASS__, 'add_edit_organization_page']
        );

        // Підменю Attributes
        add_submenu_page(
            'lapki',
            __('Атрибути', 'lapki'),
            __('Атрибути', 'lapki'),
            'manage_options',
            'lapki-attributes',
            [__CLASS__, 'attributes_page']
        );

        // Підменю Email-шаблони
        add_submenu_page(
            'lapki',
            __('Email-шаблони Lapki', 'lapki'),
            __('Email-шаблони', 'lapki'),
            'manage_options',
            'lapki-email-templates',
            [__CLASS__, 'email_templates_page']
        );

        // Підменю Статистика
        add_submenu_page(
            'lapki',
            __('Статистика Lapki', 'lapki'),
            __('Статистика', 'lapki'),
            'manage_options',
            'lapki-stats',
            [__CLASS__, 'stats_page']
        );

        // Підменю Налаштування
        add_submenu_page(
            'lapki',
            __('Налаштування Lapki', 'lapki'),
            __('Налаштування', 'lapki'),
            'manage_options',
            'lapki-settings',
            [__CLASS__, 'settings_page']
        );
    }

    /**
     * Сторінка статистики: тварини (загалом, по видах, додано за
     * день/тиждень/місяць) + виклики ШІ (за день/тиждень/місяць) +
     * довільний період "з...по..." для обох блоків одразу.
     */
    public static function stats_page()
    {
        $today_ts = current_time('timestamp');
        $today = date('Y-m-d', $today_ts);
        $week_start = date('Y-m-d', strtotime('monday this week', $today_ts));
        $month_start = date('Y-m-01', $today_ts);

        $animal_periods = [
            __('Сьогодні', 'lapki') => Lapki_Animal::count_added_between($today, $today),
            __('Цей тиждень', 'lapki') => Lapki_Animal::count_added_between($week_start, $today),
            __('Цей місяць', 'lapki') => Lapki_Animal::count_added_between($month_start, $today),
        ];

        $ai_periods = [
            __('Сьогодні', 'lapki') => Lapki_AI_Usage_Log::count_between($today, $today),
            __('Цей тиждень', 'lapki') => Lapki_AI_Usage_Log::count_between($week_start, $today),
            __('Цей місяць', 'lapki') => Lapki_AI_Usage_Log::count_between($month_start, $today),
        ];

        $type_counts = Lapki_Animal::count_by_type();
        $type_labels = [];
        foreach (Lapki_Attributes::get_animal_types(Lapki_I18n::get_lang()) as $t) {
            $type_labels[$t['type']] = $t['display_name'];
        }

        $total_animals = array_sum(array_column($type_counts, 'cnt'));

        $month_error_breakdown = Lapki_AI_Usage_Log::get_error_breakdown($month_start, $today);

        // Довільний період "з...по..." — валідні дати з $_GET, інакше без діапазону.
        $range_from = isset($_GET['from']) ? sanitize_text_field(wp_unslash($_GET['from'])) : '';
        $range_to = isset($_GET['to']) ? sanitize_text_field(wp_unslash($_GET['to'])) : '';
        $range_valid = (bool) (strtotime($range_from) && strtotime($range_to));
        $range_animals = null;
        $range_ai = null;
        $range_error_breakdown = null;

        if ($range_valid) {
            $range_animals = Lapki_Animal::count_added_between($range_from, $range_to);
            $range_ai = Lapki_AI_Usage_Log::count_between($range_from, $range_to);
            $range_error_breakdown = Lapki_AI_Usage_Log::get_error_breakdown($range_from, $range_to);
        }
?>
        <div class="wrap">
            <h1><?php esc_html_e('Статистика Lapki', 'lapki'); ?></h1>

            <h2><?php esc_html_e('Тварини', 'lapki'); ?></h2>
            <table class="wp-list-table widefat fixed striped" style="max-width:600px;">
                <tbody>
                    <tr>
                        <th style="width:60%;"><?php esc_html_e('Всього в базі', 'lapki'); ?></th>
                        <td><strong><?php echo esc_html($total_animals); ?></strong></td>
                    </tr>
                    <?php foreach ($animal_periods as $label => $count) : ?>
                    <tr>
                        <th><?php echo esc_html(sprintf(
                            /* translators: %s: period label (e.g. "Today", "This week") */
                            __('Додано: %s', 'lapki'),
                            $label
                        )); ?></th>
                        <td><?php echo esc_html($count); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3><?php esc_html_e('По видах', 'lapki'); ?></h3>
            <table class="wp-list-table widefat fixed striped" style="max-width:600px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Вид', 'lapki'); ?></th>
                        <th><?php esc_html_e('Кількість', 'lapki'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($type_counts)) : ?>
                    <tr><td colspan="2"><?php esc_html_e('Немає даних.', 'lapki'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($type_counts as $row) : ?>
                        <tr>
                            <td><?php echo esc_html($type_labels[$row['type']] ?? $row['type']); ?></td>
                            <td><?php echo esc_html($row['cnt']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2><?php esc_html_e('Виклики ШІ ("Покращити за допомогою ШІ")', 'lapki'); ?></h2>
            <table class="wp-list-table widefat fixed striped" style="max-width:900px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Період', 'lapki'); ?></th>
                        <th><?php esc_html_e('Всього', 'lapki'); ?></th>
                        <th><?php esc_html_e('Успішно', 'lapki'); ?></th>
                        <th><?php esc_html_e('Помилка', 'lapki'); ?></th>
                        <th><?php esc_html_e('Токени (запит)', 'lapki'); ?></th>
                        <th><?php esc_html_e('Токени (відповідь)', 'lapki'); ?></th>
                        <th><?php esc_html_e('Токени всього', 'lapki'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ai_periods as $label => $stats) : ?>
                    <tr>
                        <th><?php echo esc_html($label); ?></th>
                        <td><?php echo esc_html($stats['total']); ?></td>
                        <td><?php echo esc_html($stats['success']); ?></td>
                        <td><?php echo esc_html($stats['failed']); ?></td>
                        <td><?php echo esc_html($stats['prompt_tokens']); ?></td>
                        <td><?php echo esc_html($stats['completion_tokens']); ?></td>
                        <td><strong><?php echo esc_html($stats['total_tokens']); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="description"><?php esc_html_e('Кількість токенів — те, що реально повернув API провайдера (usageMetadata у відповіді Gemini), не оцінка на клієнті. Для невдалих викликів токени зазвичай 0 (запит не дійшов до генерації відповіді).', 'lapki'); ?></p>

            <h3><?php esc_html_e('Помилки ШІ за цей місяць — по типу', 'lapki'); ?></h3>
            <table class="wp-list-table widefat fixed striped" style="max-width:600px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Тип помилки', 'lapki'); ?></th>
                        <th><?php esc_html_e('Кількість', 'lapki'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($month_error_breakdown)) : ?>
                    <tr><td colspan="2"><?php esc_html_e('Помилок не було.', 'lapki'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($month_error_breakdown as $row) : ?>
                        <tr>
                            <td><?php echo esc_html(self::ai_error_label($row['error_code'])); ?></td>
                            <td><?php echo esc_html($row['cnt']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2><?php esc_html_e('Довільний період', 'lapki'); ?></h2>
            <form method="get" style="margin-bottom:16px;">
                <input type="hidden" name="page" value="lapki-stats">
                <label>
                    <?php esc_html_e('З', 'lapki'); ?>
                    <input type="date" name="from" value="<?php echo esc_attr($range_from); ?>">
                </label>
                <label>
                    <?php esc_html_e('По', 'lapki'); ?>
                    <input type="date" name="to" value="<?php echo esc_attr($range_to); ?>">
                </label>
                <?php submit_button(__('Показати', 'lapki'), 'secondary', '', false); ?>
            </form>

            <?php if ($range_from !== '' || $range_to !== '') : ?>
                <?php if (!$range_valid) : ?>
                    <p class="notice notice-error" style="padding:8px 12px;"><?php esc_html_e('Вкажіть коректні дати "з" і "по".', 'lapki'); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped" style="max-width:600px;">
                        <tbody>
                            <tr>
                                <th style="width:60%;"><?php esc_html_e('Тварин додано за період', 'lapki'); ?></th>
                                <td><strong><?php echo esc_html($range_animals); ?></strong></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Викликів ШІ за період', 'lapki'); ?></th>
                                <td><?php echo esc_html($range_ai['total']); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('— успішно', 'lapki'); ?></th>
                                <td><?php echo esc_html($range_ai['success']); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('— помилка', 'lapki'); ?></th>
                                <td><?php echo esc_html($range_ai['failed']); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Токени (запит)', 'lapki'); ?></th>
                                <td><?php echo esc_html($range_ai['prompt_tokens']); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Токени (відповідь)', 'lapki'); ?></th>
                                <td><?php echo esc_html($range_ai['completion_tokens']); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Токени всього', 'lapki'); ?></th>
                                <td><strong><?php echo esc_html($range_ai['total_tokens']); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>

                    <?php if (!empty($range_error_breakdown)) : ?>
                        <h3><?php esc_html_e('Помилки за період — по типу', 'lapki'); ?></h3>
                        <table class="wp-list-table widefat fixed striped" style="max-width:600px;">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Тип помилки', 'lapki'); ?></th>
                                    <th><?php esc_html_e('Кількість', 'lapki'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($range_error_breakdown as $row) : ?>
                                <tr>
                                    <td><?php echo esc_html(self::ai_error_label($row['error_code'])); ?></td>
                                    <td><?php echo esc_html($row['cnt']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
<?php
    }

    /**
     * Людяна українська назва коду помилки ШІ (для сторінки статистики).
     * Коди — з WP_Error, який повертає Lapki_AI_Provider::improve_text()
     * (див. inc/class-lapki-ai-providers.php). Невідомий код — показуємо
     * як є (сирий код), щоб новий провайдер не "губив" помилки мовчки.
     */
    private static function ai_error_label($code)
    {
        $labels = [
            'ai_timeout' => __('Тайм-аут з\'єднання (API не відповів вчасно)', 'lapki'),
            'ai_connection_failed' => __('Помилка з\'єднання з API', 'lapki'),
            'ai_quota_exceeded' => __('Вичерпано безкоштовний ліміт запитів (HTTP 429)', 'lapki'),
            'ai_overloaded' => __('Модель тимчасово перевантажена (HTTP 503)', 'lapki'),
            'ai_request_failed' => __('Провайдер повернув іншу помилку (напр. невалідний ключ, недоступна модель)', 'lapki'),
            'ai_blocked' => __('Заблоковано фільтром безпеки провайдера', 'lapki'),
            'ai_empty_response' => __('Провайдер повернув порожню відповідь', 'lapki'),
        ];

        return $labels[$code] ?? ($code ?: __('(невідома помилка)', 'lapki'));
    }

    /**
     * Зареєструвати налаштування плагіна (Settings API)
     */
    public static function register_settings()
    {
        register_setting('lapki_settings_group', 'lapki_default_distance', [
            'type' => 'integer',
            'default' => 50,
            'sanitize_callback' => 'absint',
        ]);

        register_setting('lapki_settings_group', 'lapki_default_page_size', [
            'type' => 'integer',
            'default' => 20,
            'sanitize_callback' => 'absint',
        ]);

        register_setting('lapki_settings_group', 'lapki_notification_email', [
            'type' => 'string',
            'default' => get_option('admin_email'),
            'sanitize_callback' => 'sanitize_email',
        ]);

        // ID вкладення (медіатека) для фонового фото головного банера (.lapki-hero).
        // 0 / порожньо — банер лишається зі стандартним зеленим градієнтом теми.
        register_setting('lapki_settings_group', 'lapki_hero_bg_image_id', [
            'type' => 'integer',
            'default' => 0,
            'sanitize_callback' => 'absint',
        ]);

        // ID вкладення (медіатека) для лого сайту в шапці. 0 / порожньо —
        // лишається WP Customizer (Site Identity) або дефолтний logo-small.png теми.
        // Виводиться повнорозмірним ('full'), без генерації обрізаних thumbnail-версій —
        // на відміну від нативного custom-logo, який змушує кроп під фіксовані розміри.
        register_setting('lapki_settings_group', 'lapki_site_logo_id', [
            'type' => 'integer',
            'default' => 0,
            'sanitize_callback' => 'absint',
        ]);

        // ШІ-провайдери — кожен сам оголошує свої поля (Lapki_AI_Provider::get_settings_fields()),
        // тут лише реєструємо опцію для кожного з них, без хардкоду під конкретного провайдера.
        $providers = Lapki_AI_Manager::get_providers();

        foreach ($providers as $provider) {
            foreach ($provider->get_settings_fields() as $field) {
                register_setting('lapki_settings_group', $provider->get_option_name($field['id']), [
                    'type' => 'string',
                    'default' => $field['default'] ?? '',
                    'sanitize_callback' => 'sanitize_text_field',
                ]);
            }
        }

        if (count($providers) > 1) {
            register_setting('lapki_settings_group', 'lapki_ai_default_provider', [
                'type' => 'string',
                'default' => Lapki_AI_Manager::get_default_provider_id(),
                'sanitize_callback' => 'sanitize_text_field',
            ]);
        }
    }

    /**
     * Сторінка налаштувань плагіна
     */
    public static function settings_page()
    {
?>
        <div class="wrap">
            <h1><?php esc_html_e('Налаштування Lapki', 'lapki'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('lapki_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="lapki_default_distance"><?php esc_html_e('Дистанція пошуку за замовчуванням (км)', 'lapki'); ?></label></th>
                        <td>
                            <input type="number" min="1" id="lapki_default_distance" name="lapki_default_distance"
                                   value="<?php echo esc_attr(get_option('lapki_default_distance', 50)); ?>" class="small-text">
                            <p class="description"><?php esc_html_e('Використовується в геопошуку тварин, якщо клієнт не вказав свою відстань.', 'lapki'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="lapki_default_page_size"><?php esc_html_e('Тварин на сторінці за замовчуванням', 'lapki'); ?></label></th>
                        <td>
                            <input type="number" min="1" max="100" id="lapki_default_page_size" name="lapki_default_page_size"
                                   value="<?php echo esc_attr(get_option('lapki_default_page_size', 20)); ?>" class="small-text">
                            <p class="description"><?php esc_html_e('Дефолтний розмір сторінки в REST API (GET /animals) та на фронтенді. Максимум — 100.', 'lapki'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="lapki_notification_email"><?php esc_html_e('Email для нотифікацій', 'lapki'); ?></label></th>
                        <td>
                            <input type="email" id="lapki_notification_email" name="lapki_notification_email"
                                   value="<?php echo esc_attr(get_option('lapki_notification_email', get_option('admin_email'))); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e('Резервна адреса для сповіщень про нові заявки на усиновлення, якщо в організації не вказано email.', 'lapki'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Фонове фото головного банера', 'lapki'); ?></th>
                        <td>
                            <?php
                            $hero_bg_id  = (int) get_option('lapki_hero_bg_image_id', 0);
                            $hero_bg_url = $hero_bg_id ? wp_get_attachment_image_url($hero_bg_id, 'large') : '';
                            ?>
                            <input type="hidden" id="lapki_hero_bg_image_id" name="lapki_hero_bg_image_id" value="<?php echo esc_attr($hero_bg_id); ?>">
                            <div id="lapki-hero-bg-preview" style="margin-bottom:8px;<?php echo $hero_bg_url ? '' : 'display:none;'; ?>">
                                <img src="<?php echo esc_url($hero_bg_url); ?>" alt="" style="max-width:360px;height:auto;display:block;border:1px solid #ccd0d4;border-radius:4px;">
                            </div>
                            <button type="button" class="button" id="lapki-hero-bg-select"><?php esc_html_e('Обрати зображення', 'lapki'); ?></button>
                            <button type="button" class="button" id="lapki-hero-bg-remove" style="<?php echo $hero_bg_id ? '' : 'display:none;'; ?>"><?php esc_html_e('Прибрати', 'lapki'); ?></button>
                            <p class="description"><?php esc_html_e('Якщо задано — банер на головній сторінці показує саме це фото на всю ширину, без затемнення чи оверлея. Якщо порожньо — використовується стандартний зелений градієнт теми. Рекомендована ширина — від 1920 пікселів.', 'lapki'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Лого сайту', 'lapki'); ?></th>
                        <td>
                            <?php
                            $logo_id  = (int) get_option('lapki_site_logo_id', 0);
                            $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : '';
                            ?>
                            <input type="hidden" id="lapki_site_logo_id" name="lapki_site_logo_id" value="<?php echo esc_attr($logo_id); ?>">
                            <div id="lapki-site-logo-preview" style="margin-bottom:8px;<?php echo $logo_url ? '' : 'display:none;'; ?>">
                                <img src="<?php echo esc_url($logo_url); ?>" alt="" style="max-height:88px;width:auto;display:block;border:1px solid #ccd0d4;border-radius:4px;">
                            </div>
                            <button type="button" class="button" id="lapki-site-logo-select"><?php esc_html_e('Обрати зображення', 'lapki'); ?></button>
                            <button type="button" class="button" id="lapki-site-logo-remove" style="<?php echo $logo_id ? '' : 'display:none;'; ?>"><?php esc_html_e('Прибрати', 'lapki'); ?></button>
                            <p class="description"><?php esc_html_e('Показується в шапці сайту в оригінальному розмірі файлу (лише вписується у висоту шапки, без обрізки/ресайзу WordPress). Якщо порожньо — використовується лого з Налаштування зовнішнього вигляду → Оформлення сайту, або дефолтне лого теми.', 'lapki'); ?></p>
                        </td>
                    </tr>
                </table>

                <script>
                jQuery(function ($) {
                    var frame;
                    $('#lapki-hero-bg-select').on('click', function (e) {
                        e.preventDefault();
                        if (frame) { frame.open(); return; }
                        frame = wp.media({
                            title: <?php echo wp_json_encode(__('Оберіть фонове фото банера', 'lapki')); ?>,
                            button: { text: <?php echo wp_json_encode(__('Використати це зображення', 'lapki')); ?> },
                            library: { type: 'image' },
                            multiple: false
                        });
                        frame.on('select', function () {
                            var att = frame.state().get('selection').first().toJSON();
                            var url = (att.sizes && att.sizes.large) ? att.sizes.large.url : att.url;
                            $('#lapki_hero_bg_image_id').val(att.id);
                            $('#lapki-hero-bg-preview').show().find('img').attr('src', url);
                            $('#lapki-hero-bg-remove').show();
                        });
                        frame.open();
                    });
                    $('#lapki-hero-bg-remove').on('click', function (e) {
                        e.preventDefault();
                        $('#lapki_hero_bg_image_id').val('');
                        $('#lapki-hero-bg-preview').hide();
                        $(this).hide();
                    });

                    var logoFrame;
                    $('#lapki-site-logo-select').on('click', function (e) {
                        e.preventDefault();
                        if (logoFrame) { logoFrame.open(); return; }
                        logoFrame = wp.media({
                            title: <?php echo wp_json_encode(__('Оберіть лого сайту', 'lapki')); ?>,
                            button: { text: <?php echo wp_json_encode(__('Використати це зображення', 'lapki')); ?> },
                            library: { type: 'image' },
                            multiple: false
                        });
                        logoFrame.on('select', function () {
                            // Оригінальний файл (att.url), не згенерований WP розмір —
                            // лого має показуватись без ресайзу/обрізки.
                            var att = logoFrame.state().get('selection').first().toJSON();
                            $('#lapki_site_logo_id').val(att.id);
                            $('#lapki-site-logo-preview').show().find('img').attr('src', att.url);
                            $('#lapki-site-logo-remove').show();
                        });
                        logoFrame.open();
                    });
                    $('#lapki-site-logo-remove').on('click', function (e) {
                        e.preventDefault();
                        $('#lapki_site_logo_id').val('');
                        $('#lapki-site-logo-preview').hide();
                        $(this).hide();
                    });
                });
                </script>

                <h2><?php esc_html_e('ШІ-інтеграції', 'lapki'); ?></h2>
                <p class="description"><?php esc_html_e('Використовуються для кнопки "Покращити за допомогою ШІ" (напр. біля поля "Опис" тварини). Ключ спільний для сайту — платить/лімітує його власник сайту, не окремий користувач.', 'lapki'); ?></p>
                <table class="form-table">
                    <?php $ai_providers = Lapki_AI_Manager::get_providers(); ?>

                    <?php if (count($ai_providers) > 1) : ?>
                    <tr>
                        <th><label for="lapki_ai_default_provider"><?php esc_html_e('Дефолтний ШІ-провайдер', 'lapki'); ?></label></th>
                        <td>
                            <select id="lapki_ai_default_provider" name="lapki_ai_default_provider">
                                <?php foreach ($ai_providers as $provider) : ?>
                                    <option value="<?php echo esc_attr($provider->get_id()); ?>" <?php selected(Lapki_AI_Manager::get_default_provider_id(), $provider->get_id()); ?>>
                                        <?php echo esc_html($provider->get_label()); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php foreach ($ai_providers as $provider) : ?>
                        <tr><th colspan="2"><h3 style="margin-bottom:0;"><?php echo esc_html($provider->get_label()); ?></h3></th></tr>
                        <?php foreach ($provider->get_settings_fields() as $field) :
                            $option_name = $provider->get_option_name($field['id']);
                            $current_value = get_option($option_name, $field['default'] ?? '');
                        ?>
                        <tr>
                            <th><label for="<?php echo esc_attr($option_name); ?>"><?php echo esc_html($field['label']); ?></label></th>
                            <td>
                                <?php if ($field['type'] === 'select') : ?>
                                    <select id="<?php echo esc_attr($option_name); ?>" name="<?php echo esc_attr($option_name); ?>">
                                        <?php foreach ($field['options'] ?? [] as $option) : ?>
                                            <option value="<?php echo esc_attr($option['value']); ?>" <?php selected($current_value, $option['value']); ?>>
                                                <?php echo esc_html($option['label']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else :
                                    $input_type = $field['type'] === 'password' ? 'password' : 'text';
                                ?>
                                    <input type="<?php echo esc_attr($input_type); ?>" id="<?php echo esc_attr($option_name); ?>" name="<?php echo esc_attr($option_name); ?>"
                                           value="<?php echo esc_attr($current_value); ?>"
                                           class="regular-text" autocomplete="off">
                                <?php endif; ?>
                                <?php if (!empty($field['help'])) : ?>
                                    <p class="description"><?php echo wp_kses_post($field['help']); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </table>

                <?php submit_button(__('Зберегти налаштування', 'lapki')); ?>
            </form>
        </div>
<?php
    }

    /**
     * Завантажити скрипти для адміна
     */
    public static function admin_scripts($hook)
    {
        // Медіатека для вибору фонового фото банера на сторінці налаштувань
        if (strpos($hook, 'lapki-settings') !== false) {
            wp_enqueue_media();
        }

        if (strpos($hook, 'lapki') !== false) {
            // Leaflet CSS and JS (OpenStreetMap)
            wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
            wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);

            // Dropzone CSS and JS (file uploads)
            wp_enqueue_style('dropzone', 'https://unpkg.com/dropzone@5/dist/min/dropzone.min.css', [], '5.9.3');
            wp_enqueue_script('dropzone', 'https://unpkg.com/dropzone@5/dist/min/dropzone.min.js', [], '5.9.3', true);

            // Tom Select — автодоповнення поля "Місто" з довідника КАТОТТГ (wp_lapki_geo)
            wp_enqueue_style('tom-select', 'https://cdn.jsdelivr.net/npm/tom-select@2.6.2/dist/css/tom-select.default.min.css', [], '2.6.2');
            wp_enqueue_script('tom-select', 'https://cdn.jsdelivr.net/npm/tom-select@2.6.2/dist/js/tom-select.complete.min.js', [], '2.6.2', true);

            wp_enqueue_style('lapki-admin', LAPKI_PLUGIN_URL . 'css/lapki-admin.css', [], LAPKI_VERSION);
            wp_enqueue_script('lapki-admin', LAPKI_PLUGIN_URL . 'js/lapki-admin.js', ['jquery', 'leaflet', 'dropzone', 'tom-select'], LAPKI_VERSION, true);

            // Локалізація
            wp_localize_script('lapki-admin', 'lapkiAdmin', [
                'nonce' => wp_create_nonce('wp_rest'),
                'apiBase' => rest_url('lapki/v1'),
                'lang' => Lapki_I18n::get_lang()
            ]);
        }
    }

    /**
     * Сторінка зі списком тварин
     */
    public static function animals_page()
    {
?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Тварини', 'lapki'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=lapki-add-animal'); ?>" class="page-title-action"><?php esc_html_e('Додати тварину', 'lapki'); ?></a>
            <hr class="wp-header-end">

            <div class="tablenav top">
                <div class="alignleft actions">
                    <input type="search" id="animal-search-input" name="s" placeholder="<?php esc_attr_e('Пошук тварин...', 'lapki'); ?>" style="width:200px;">
                </div>
            </div>

            <table id="lapki-animals-table" class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" />
                        </td>
                        <th class="manage-column column-photo"><?php esc_html_e('Фото', 'lapki'); ?></th>
                        <th class="manage-column column-name column-primary"><?php esc_html_e('Кличка', 'lapki'); ?></th>
                        <th class="manage-column column-type"><?php esc_html_e('Вид', 'lapki'); ?></th>
                        <th class="manage-column column-breed"><?php esc_html_e('Порода', 'lapki'); ?></th>
                        <th class="manage-column column-age"><?php esc_html_e('Вік/Стать', 'lapki'); ?></th>
                        <th class="manage-column column-status"><?php esc_html_e('Статус', 'lapki'); ?></th>
                        <th class="manage-column column-organization"><?php esc_html_e('Організація', 'lapki'); ?></th>
                        <th class="manage-column column-dates"><?php esc_html_e('Дата', 'lapki'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="9" style="text-align:center;padding:40px;">
                            <div class="spinner is-active" style="float:none;margin:0 auto;"></div>
                            <p><?php esc_html_e('Завантаження...', 'lapki'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div id="lapki-pagination" class="tablenav bottom"></div>
        </div>

        <style>
            .wp-list-table td.check-column,
            .wp-list-table th.check-column {
                text-align: center;
                vertical-align: middle;
            }

            .wp-list-table td.check-column input[type="checkbox"] {
                margin: 0;
                vertical-align: middle;
            }

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
                white-space: nowrap;
            }

            .wp-list-table .column-dates {
                width: 130px;
            }

            .animal-dates {
                font-size: 12px;
                line-height: 1.6;
                color: #666;
                white-space: nowrap;
            }

            .animal-dates strong {
                color: #1d2327;
                font-weight: 500;
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

            .status-found {
                background: #e0e0e0;
                color: #666;
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
     * Сторінка організацій
     */
    public static function organizations_page()
    {
?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Організації', 'lapki'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=lapki-add-organization'); ?>" class="page-title-action"><?php esc_html_e('Додати організацію', 'lapki'); ?></a>
            <hr class="wp-header-end">

            <div class="lapki-attr-filters">
                <input type="search" id="org-filter-search" placeholder="<?php esc_attr_e('Пошук за назвою...', 'lapki'); ?>" style="width:220px;">
                <select id="org-filter-type">
                    <option value=""><?php esc_html_e('Всі типи', 'lapki'); ?></option>
                    <option value="individual">individual</option>
                    <option value="shelter">shelter</option>
                    <option value="rescue">rescue</option>
                    <option value="vet_clinic">vet_clinic</option>
                </select>
                <button id="org-filter-apply" class="button"><?php esc_html_e('Фільтрувати', 'lapki'); ?></button>
            </div>

            <table id="lapki-organizations-table" class="wp-list-table widefat fixed striped" style="margin-top:15px;">
                <thead>
                    <tr>
                        <th style="width:50px;">ID</th>
                        <th><?php esc_html_e('Назва', 'lapki'); ?></th>
                        <th style="width:110px;"><?php esc_html_e('Тип', 'lapki'); ?></th>
                        <th>Email</th>
                        <th style="width:120px;"><?php esc_html_e('Місто', 'lapki'); ?></th>
                        <th style="width:90px;"><?php esc_html_e('Тварин', 'lapki'); ?></th>
                        <th style="width:90px;"><?php esc_html_e('Верифіковано', 'lapki'); ?></th>
                        <th style="width:130px;"><?php esc_html_e('Дії', 'lapki'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="8" style="text-align:center;padding:30px;">
                            <div class="spinner is-active" style="float:none;margin:0 auto;"></div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div id="lapki-org-pagination" class="tablenav bottom" style="margin-top:10px;"></div>
        </div>
<?php
    }

    /**
     * Сторінка редактора атрибутів
     */
    public static function attributes_page()
    {
?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Атрибути', 'lapki'); ?></h1>
            <button id="lapki-attr-add-btn" class="page-title-action">+ <?php esc_html_e('Додати атрибут', 'lapki'); ?></button>
            <hr class="wp-header-end">

            <div class="lapki-attr-filters">
                <select id="attr-filter-lang">
                    <option value=""><?php esc_html_e('Всі мови', 'lapki'); ?></option>
                    <option value="uk">uk</option>
                    <option value="en">en</option>
                </select>
                <select id="attr-filter-entity">
                    <option value=""><?php esc_html_e('Всі entity', 'lapki'); ?></option>
                    <option value="animal">animal</option>
                    <option value="org">org</option>
                    <option value="user">user</option>
                </select>
                <input type="text" id="attr-filter-entity-type" placeholder="entity_type (dog, cat, all...)" style="width:180px;">
                <select id="attr-filter-attr-name">
                    <option value=""><?php esc_html_e('Всі attr_name', 'lapki'); ?></option>
                    <option value="species">species</option>
                    <option value="breed">breed</option>
                    <option value="age">age</option>
                    <option value="gender">gender</option>
                    <option value="size">size</option>
                    <option value="coat">coat</option>
                    <option value="color">color</option>
                    <option value="status">status</option>
                </select>
                <input type="search" id="attr-filter-search" placeholder="<?php esc_attr_e('Пошук за значенням...', 'lapki'); ?>" style="width:200px;">
                <button id="attr-filter-apply" class="button"><?php esc_html_e('Фільтрувати', 'lapki'); ?></button>
            </div>

            <table id="lapki-attributes-table" class="wp-list-table widefat fixed striped" style="margin-top:15px;">
                <thead>
                    <tr>
                        <th style="width:50px;">ID</th>
                        <th style="width:80px;">entity</th>
                        <th style="width:100px;">entity_type</th>
                        <th style="width:100px;">attr_name</th>
                        <th style="width:120px;">attr_value</th>
                        <th>attr_display</th>
                        <th style="width:50px;">lang</th>
                        <th style="width:130px;"><?php esc_html_e('Дії', 'lapki'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="8" style="text-align:center;padding:30px;">
                            <div class="spinner is-active" style="float:none;margin:0 auto;"></div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div id="lapki-attr-pagination" class="tablenav bottom" style="margin-top:10px;"></div>
        </div>

        <!-- Модальне вікно редагування -->
        <div id="lapki-attr-modal" style="display:none;">
            <div class="lapki-attr-modal-backdrop"></div>
            <div class="lapki-attr-modal-box">
                <h2 id="lapki-attr-modal-title"><?php esc_html_e('Додати атрибут', 'lapki'); ?></h2>
                <input type="hidden" id="lapki-attr-id">
                <table class="form-table">
                    <tr>
                        <th><label for="lapki-attr-entity">entity <span class="required">*</span></label></th>
                        <td>
                            <select id="lapki-attr-entity" required>
                                <option value="animal">animal</option>
                                <option value="org">org</option>
                                <option value="user">user</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="lapki-attr-entity-type">entity_type <span class="required">*</span></label></th>
                        <td>
                            <input type="text" id="lapki-attr-entity-type" class="regular-text" placeholder="dog, cat, all, type...">
                            <p class="description">
                                <?php
                                echo wp_kses(
                                    sprintf(
                                        /* translators: %1$s: "all" code tag, %2$s: "type" code tag, %3$s: "dog" code tag, %4$s: "cat" code tag */
                                        __('Для глобальних атрибутів (age/gender/size) — %1$s. Для типів — %2$s. Для порід — назва типу: %3$s, %4$s.', 'lapki'),
                                        '<code>all</code>',
                                        '<code>type</code>',
                                        '<code>dog</code>',
                                        '<code>cat</code>'
                                    ),
                                    ['code' => []]
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="lapki-attr-name">attr_name <span class="required">*</span></label></th>
                        <td>
                            <select id="lapki-attr-name">
                                <option value="species">species</option>
                                <option value="breed">breed</option>
                                <option value="age">age</option>
                                <option value="gender">gender</option>
                                <option value="size">size</option>
                                <option value="coat">coat</option>
                                <option value="color">color</option>
                                <option value="status">status</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="lapki-attr-value">attr_value <span class="required">*</span></label></th>
                        <td><input type="text" id="lapki-attr-value" class="regular-text" placeholder="dog, labrador, young..."></td>
                    </tr>
                    <tr>
                        <th><label for="lapki-attr-display">attr_display <span class="required">*</span></label></th>
                        <td><input type="text" id="lapki-attr-display" class="regular-text" placeholder="<?php esc_attr_e('Собака, Лабрадор, Молодий...', 'lapki'); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="lapki-attr-lang">lang <span class="required">*</span></label></th>
                        <td>
                            <select id="lapki-attr-lang">
                                <option value="uk">uk</option>
                                <option value="en">en</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button id="lapki-attr-save" class="button button-primary"><?php esc_html_e('Зберегти', 'lapki'); ?></button>
                    <button id="lapki-attr-cancel" class="button"><?php esc_html_e('Скасувати', 'lapki'); ?></button>
                </p>
            </div>
        </div>
<?php
    }

    /**
     * Сторінка шаблонів email-повідомлень (Lapki → Email-шаблони).
     * Лише перегляд + редагування Subject/тіла — без додавання/видалення.
     */
    public static function email_templates_page()
    {
        $list_table = new Lapki_Email_Templates_List_Table();
        $list_table->prepare_items();
?>
        <div class="wrap" id="lapki-email-templates-page">
            <h1 class="wp-heading-inline"><?php esc_html_e('Email-шаблони', 'lapki'); ?></h1>
            <hr class="wp-header-end">
            <p class="description"><?php esc_html_e('Шаблони листів, які сайт надсилає автоматично. У тексті можна використовувати мітки у фігурних дужках — вони підставляються реальними значеннями перед відправкою.', 'lapki'); ?></p>

            <?php $list_table->display(); ?>
        </div>

        <!-- Модальне вікно редагування шаблону -->
        <div id="lapki-email-modal" style="display:none;">
            <div class="lapki-email-modal-backdrop"></div>
            <div class="lapki-email-modal-box">
                <h2 id="lapki-email-modal-title"><?php esc_html_e('Редагувати шаблон', 'lapki'); ?></h2>
                <input type="hidden" id="lapki-email-id">
                <table class="form-table">
                    <tr>
                        <th><label for="lapki-email-subject"><?php esc_html_e('Тема (Subject)', 'lapki'); ?> <span class="required">*</span></label></th>
                        <td><input type="text" id="lapki-email-subject" class="large-text"></td>
                    </tr>
                    <tr>
                        <th><label for="lapki-email-body"><?php esc_html_e('Текст листа', 'lapki'); ?> <span class="required">*</span></label></th>
                        <td>
                            <textarea id="lapki-email-body" rows="10" class="large-text"></textarea>
                            <p class="description" id="lapki-email-tags-hint"></p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button id="lapki-email-save" class="button button-primary"><?php esc_html_e('Зберегти', 'lapki'); ?></button>
                    <button id="lapki-email-cancel" class="button"><?php esc_html_e('Скасувати', 'lapki'); ?></button>
                </p>
            </div>
        </div>
<?php
    }

    /**
     * Сторінка додавання/редагування тварини (через AJAX)
     */
    public static function add_edit_animal_page()
    {
        $animal_id = isset($_GET['id']) ? absint($_GET['id']) : null;
        $is_edit = !empty($animal_id);
?>
        <div class="wrap">
            <h1><?php echo $is_edit ? esc_html__('Редагувати тварину', 'lapki') : esc_html__('Додати тварину', 'lapki'); ?></h1>
            <p><a href="<?php echo admin_url('admin.php?page=lapki'); ?>" class="button">← <?php esc_html_e('Повернутися до списку', 'lapki'); ?></a></p>

            <div class="lapki-animal-edit-layout">
                <div class="lapki-animal-form-column">
                    <form id="lapki-animal-form" class="lapki-form">
                <h2><?php esc_html_e('Основна інформація', 'lapki'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="name"><?php esc_html_e('Кличка', 'lapki'); ?> <span class="required">*</span></label></th>
                        <td><input type="text" id="name" name="name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="organization_id"><?php esc_html_e('Організація', 'lapki'); ?> <span class="required">*</span></label></th>
                        <td>
                            <select id="organization_id" name="organization_id" required>
                                <option value=""><?php esc_html_e('Виберіть організацію', 'lapki'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="type"><?php esc_html_e('Вид', 'lapki'); ?> <span class="required">*</span></label></th>
                        <td>
                            <select id="type" name="type" required>
                                <option value=""><?php esc_html_e('Виберіть вид', 'lapki'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="status"><?php esc_html_e('Статус', 'lapki'); ?> <span class="required">*</span></label></th>
                        <td>
                            <select id="status" name="status" required>
                                <option value=""><?php esc_html_e('Виберіть статус', 'lapki'); ?></option>
                                <option value="adoptable"><?php esc_html_e('До прилаштування', 'lapki'); ?></option>
                                <option value="adopted"><?php esc_html_e('Прилаштовано', 'lapki'); ?></option>
                                <option value="hold"><?php esc_html_e('На утриманні', 'lapki'); ?></option>
                                <option value="found"><?php esc_html_e('Знайдено', 'lapki'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Характеристики', 'lapki'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="age"><?php esc_html_e('Вік', 'lapki'); ?> <span class="required">*</span></label></th>
                        <td>
                            <select id="age" name="age" required>
                                <option value=""><?php esc_html_e('Виберіть вік', 'lapki'); ?></option>
                                <!-- Динамічно завантажується через API -->
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="gender"><?php esc_html_e('Стать', 'lapki'); ?> <span class="required">*</span></label></th>
                        <td>
                            <select id="gender" name="gender" required>
                                <option value=""><?php esc_html_e('Виберіть стать', 'lapki'); ?></option>
                                <!-- Динамічно завантажується через API -->
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="size"><?php esc_html_e('Розмір', 'lapki'); ?> <span class="required">*</span></label></th>
                        <td>
                            <select id="size" name="size" required>
                                <option value=""><?php esc_html_e('Виберіть розмір', 'lapki'); ?></option>
                                <!-- Динамічно завантажується через API -->
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="color_primary"><?php esc_html_e('Основний колір', 'lapki'); ?></label></th>
                        <td>
                            <select id="color_primary" name="color_primary">
                                <option value=""><?php esc_html_e('Виберіть колір', 'lapki'); ?></option>
                                <!-- Динамічно завантажується через API залежно від типу -->
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="coat"><?php esc_html_e('Тип шерсті', 'lapki'); ?></label></th>
                        <td>
                            <select id="coat" name="coat">
                                <option value=""><?php esc_html_e('Виберіть тип', 'lapki'); ?></option>
                                <!-- Динамічно завантажується через API -->
                            </select>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Породи', 'lapki'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="breed_primary"><?php esc_html_e('Основна порода', 'lapki'); ?></label></th>
                        <td>
                            <select id="breed_primary" name="breed_primary">
                                <option value=""><?php esc_html_e('Виберіть породу', 'lapki'); ?></option>
                                <!-- Динамічно завантажується через API залежно від типу -->
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="breed_secondary"><?php esc_html_e('Додаткова порода', 'lapki'); ?></label></th>
                        <td>
                            <select id="breed_secondary" name="breed_secondary">
                                <option value=""><?php esc_html_e('Виберіть породу', 'lapki'); ?></option>
                                <!-- Динамічно завантажується через API залежно від типу -->
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="breed_mixed"><?php esc_html_e('Метис', 'lapki'); ?></label></th>
                        <td><input type="checkbox" id="breed_mixed" name="breed_mixed" value="1"></td>
                    </tr>
                </table>

                <div id="animal-additional-attrs-section" style="display:none;">
                    <h2><?php esc_html_e('Додаткова інформація', 'lapki'); ?></h2>
                    <table class="form-table" id="animal-additional-attrs-table"></table>
                </div>

                <h2><?php esc_html_e('Опис', 'lapki'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="description"><?php esc_html_e('Опис тварини', 'lapki'); ?></label></th>
                        <td>
                            <textarea id="description" name="description" class="large-text lapki-ai-improve-target" rows="6" placeholder="<?php esc_attr_e('Детальний опис характеру, звичок, особливостей...', 'lapki'); ?>"></textarea>
                            <p class="lapki-ai-improve-controls">
                                <button type="button" class="button lapki-ai-improve-btn"><?php esc_html_e('Покращити за допомогою ШІ', 'lapki'); ?></button>
                                <button type="button" class="button lapki-ai-undo-btn" style="display:none;"><?php esc_html_e('Скасувати зміни', 'lapki'); ?></button>
                                <span class="lapki-ai-status description"></span>
                            </p>
                        </td>
                    </tr>
                </table>

                <div class="lapki-checkbox-group">
                    <label for="spayed_neutered"><input type="checkbox" id="spayed_neutered" name="spayed_neutered" value="1"> <?php esc_html_e('Стерилізована/Кастрована', 'lapki'); ?></label>
                    <label for="shots_current"><input type="checkbox" id="shots_current" name="shots_current" value="1"> <?php esc_html_e('Щеплення актуальні', 'lapki'); ?></label>
                    <label for="house_trained"><input type="checkbox" id="house_trained" name="house_trained" value="1"> <?php esc_html_e('Приучена до лотка/туалету', 'lapki'); ?></label>
                    <label for="special_needs"><input type="checkbox" id="special_needs" name="special_needs" value="1"> <?php esc_html_e('Особливі потреби', 'lapki'); ?></label>
                    <label for="from_war_zone"><input type="checkbox" id="from_war_zone" name="from_war_zone" value="1"> <?php esc_html_e('З зони бойових дій', 'lapki'); ?></label>
                    <label for="good_with_children"><input type="checkbox" id="good_with_children" name="good_with_children" value="1"> <?php esc_html_e('Добре ладнає з дітьми', 'lapki'); ?></label>
                    <label for="good_with_dogs"><input type="checkbox" id="good_with_dogs" name="good_with_dogs" value="1"> <?php esc_html_e('Добре ладнає з собаками', 'lapki'); ?></label>
                    <label for="good_with_cats"><input type="checkbox" id="good_with_cats" name="good_with_cats" value="1"> <?php esc_html_e('Добре ладнає з котами', 'lapki'); ?></label>
                </div>

                <h2><?php esc_html_e('Контактна інформація', 'lapki'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="contact_email"><?php esc_html_e('Email для контакту', 'lapki'); ?></label></th>
                        <td><input type="email" id="contact_email" name="contact_email" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="contact_phone"><?php esc_html_e('Телефон', 'lapki'); ?></label></th>
                        <td><input type="text" id="contact_phone" name="contact_phone" class="regular-text"></td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Місцезнаходження', 'lapki'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="address1"><?php esc_html_e('Вулиця, будинок', 'lapki'); ?></label></th>
                        <td><input type="text" id="address1" name="address1" class="regular-text" placeholder="<?php esc_attr_e('вул. Хрещатик, 1', 'lapki'); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="address2"><?php esc_html_e('Додаткова адреса', 'lapki'); ?></label></th>
                        <td><input type="text" id="address2" name="address2" class="regular-text" placeholder="<?php esc_attr_e("кв. 10, під'їзд 2", 'lapki'); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="address_city_select"><?php esc_html_e('Населений пункт', 'lapki'); ?> *</label></th>
                        <td>
                            <select id="address_city_select" class="lapki-city-select" style="width:100%;max-width:25em;"></select>
                            <input type="hidden" id="address_city" name="address_city">
                            <input type="hidden" id="address_city_katottg" name="address_city_katottg">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="latitude"><?php esc_html_e('Широта', 'lapki'); ?></label></th>
                        <td><input type="number" id="latitude" name="latitude" step="0.00000001" class="regular-text" readonly></td>
                    </tr>
                    <tr>
                        <th><label for="longitude"><?php esc_html_e('Довгота', 'lapki'); ?></label></th>
                        <td><input type="number" id="longitude" name="longitude" step="0.00000001" class="regular-text" readonly></td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <button type="button" id="geocode-address" class="button">📍 <?php esc_html_e('Знайти на карті за адресою', 'lapki'); ?></button>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Карта', 'lapki'); ?></label></th>
                        <td>
                            <div id="location-map" style="height: 400px; width: 100%; border: 1px solid #ddd; border-radius: 4px;"></div>
                            <p class="description"><?php esc_html_e('Клікніть на карті щоб вибрати точне місцезнаходження. Координати оновляться автоматично.', 'lapki'); ?></p>
                        </td>
                    </tr>
                </table>

                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php esc_html_e('Зберегти', 'lapki'); ?></button>
                            <a href="<?php echo admin_url('admin.php?page=lapki'); ?>" class="button"><?php esc_html_e('Скасувати', 'lapki'); ?></a>
                        </p>
                    </form>
                </div>

                <?php if ($is_edit): ?>
                <div class="lapki-animal-media-column">
                    <h2><?php esc_html_e('Зображення', 'lapki'); ?></h2>
                    <div id="animal-media-gallery" class="lapki-media-gallery">
                        <!-- Існуючі зображення завантажуються через AJAX -->
                    </div>

                    <h3 style="margin-top: 20px;"><?php esc_html_e('Додати нові зображення', 'lapki'); ?></h3>
                    <div id="dropzone-upload" class="lapki-dropzone">
                        <div class="dz-message">
                            <?php esc_html_e('Перетягніть файли сюди або клікніть для вибору', 'lapki'); ?><br>
                            <span style="font-size: 12px; color: #666;">(<?php esc_html_e('JPG, PNG, GIF, WebP, до 10 МБ', 'lapki'); ?>)</span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Модальне вікно для збільшення фото -->
        <div id="photo-modal" class="lapki-photo-modal" style="display: none;">
            <span class="lapki-modal-close">&times;</span>
            <img class="lapki-modal-content" id="modal-image">
        </div>

        <style>
            .required { color: #d63384; }
            .lapki-form h2 { margin-top: 30px; }

            .lapki-checkbox-group {
                display: flex;
                flex-wrap: wrap;
                gap: 10px 24px;
                margin: 15px 0 30px;
            }

            .lapki-checkbox-group label {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-weight: 400;
                white-space: nowrap;
            }

            .lapki-animal-edit-layout {
                display: flex;
                gap: 30px;
                margin-top: 20px;
            }

            .lapki-animal-form-column {
                flex: 1;
                min-width: 0;
            }

            .lapki-animal-media-column {
                width: 350px;
                flex-shrink: 0;
            }

            .lapki-media-gallery {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-bottom: 20px;
            }

            .lapki-media-item {
                position: relative;
                width: 100px;
                height: 100px;
                border: 2px solid #ddd;
                border-radius: 4px;
                overflow: hidden;
                cursor: pointer;
                transition: all 0.2s;
            }

            .lapki-media-item:hover {
                border-color: #0073aa;
                transform: scale(1.05);
            }

            .lapki-media-item.is-primary {
                border-color: #00a32a;
                border-width: 3px;
            }

            .lapki-media-item img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .lapki-media-item-actions {
                position: absolute;
                top: 0;
                right: 0;
                display: flex;
                gap: 2px;
                padding: 4px;
                background: rgba(0,0,0,0.6);
                opacity: 0;
                transition: opacity 0.2s;
            }

            .lapki-media-item:hover .lapki-media-item-actions {
                opacity: 1;
            }

            .lapki-media-item-actions button {
                background: white;
                border: none;
                width: 24px;
                height: 24px;
                border-radius: 3px;
                cursor: pointer;
                font-size: 14px;
                line-height: 1;
                padding: 0;
            }

            .lapki-media-item-actions button:hover {
                background: #f0f0f0;
            }

            .lapki-media-primary-badge {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                background: #00a32a;
                color: white;
                font-size: 10px;
                text-align: center;
                padding: 2px;
                font-weight: bold;
            }

            .lapki-dropzone {
                border: 2px dashed #ddd;
                border-radius: 4px;
                padding: 20px;
                text-align: center;
                cursor: pointer;
                transition: all 0.2s;
            }

            .lapki-dropzone:hover,
            .lapki-dropzone.dz-drag-hover {
                border-color: #0073aa;
                background: #f0f6fc;
            }

            .lapki-photo-modal {
                display: none;
                position: fixed;
                z-index: 100000;
                padding-top: 50px;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0,0,0,0.9);
            }

            .lapki-modal-content {
                margin: auto;
                display: block;
                max-width: 90%;
                max-height: 90%;
            }

            .lapki-modal-close {
                position: absolute;
                top: 15px;
                right: 35px;
                color: #f1f1f1;
                font-size: 40px;
                font-weight: bold;
                cursor: pointer;
            }

            .lapki-modal-close:hover {
                color: #bbb;
            }
        </style>
<?php
    }

    /**
     * Сторінка додавання/редагування організації
     */
    public static function add_edit_organization_page()
    {
        $org_id = isset($_GET['id']) ? absint($_GET['id']) : null;
        $is_edit = !empty($org_id);
?>
        <div class="wrap">
            <h1><?php echo $is_edit ? esc_html__('Редагувати організацію', 'lapki') : esc_html__('Додати організацію', 'lapki'); ?></h1>
            <p><a href="<?php echo admin_url('admin.php?page=lapki-organizations'); ?>" class="button">← <?php esc_html_e('Повернутися до списку', 'lapki'); ?></a></p>

            <div class="lapki-animal-edit-layout">
                <div class="lapki-animal-form-column">
            <form id="lapki-organization-form" class="lapki-form">
                <input type="hidden" id="id" name="id">

                <h2><?php esc_html_e('Основна інформація', 'lapki'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="name"><?php esc_html_e('Назва', 'lapki'); ?> <span class="required">*</span></label></th>
                        <td><input type="text" id="name" name="name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="type"><?php esc_html_e('Тип', 'lapki'); ?> <span class="required">*</span></label></th>
                        <td>
                            <select id="type" name="type" required>
                                <option value="individual"><?php esc_html_e('Приватна особа', 'lapki'); ?></option>
                                <option value="shelter"><?php esc_html_e('Притулок', 'lapki'); ?></option>
                                <option value="rescue"><?php esc_html_e('Волонтерська організація', 'lapki'); ?></option>
                                <option value="vet_clinic"><?php esc_html_e('Ветклініка', 'lapki'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="is_verified"><?php esc_html_e('Верифікована', 'lapki'); ?></label></th>
                        <td><input type="checkbox" id="is_verified" name="is_verified" value="1"></td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Контактна інформація', 'lapki'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="email">Email</label></th>
                        <td><input type="email" id="email" name="email" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="phone"><?php esc_html_e('Телефон', 'lapki'); ?></label></th>
                        <td><input type="text" id="phone" name="phone" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="website"><?php esc_html_e('Сайт', 'lapki'); ?></label></th>
                        <td><input type="url" id="website" name="website" class="regular-text" placeholder="https://"></td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Місцезнаходження', 'lapki'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="city_select"><?php esc_html_e('Населений пункт', 'lapki'); ?> *</label></th>
                        <td>
                            <select id="city_select" class="lapki-city-select" style="width:100%;max-width:25em;"></select>
                            <input type="hidden" id="city" name="city">
                            <input type="hidden" id="city_katottg" name="city_katottg">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="state"><?php esc_html_e('Область', 'lapki'); ?></label></th>
                        <td><input type="text" id="state" name="state" class="regular-text"></td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Опис', 'lapki'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="mission_statement"><?php esc_html_e('Місія', 'lapki'); ?></label></th>
                        <td><textarea id="mission_statement" name="mission_statement" class="large-text" rows="4"></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="adoption_policy"><?php esc_html_e('Політика усиновлення', 'lapki'); ?></label></th>
                        <td><textarea id="adoption_policy" name="adoption_policy" class="large-text" rows="4"></textarea></td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary"><?php esc_html_e('Зберегти', 'lapki'); ?></button>
                    <a href="<?php echo admin_url('admin.php?page=lapki-organizations'); ?>" class="button"><?php esc_html_e('Скасувати', 'lapki'); ?></a>
                </p>
            </form>
                </div>

                <?php if ($is_edit): ?>
                <div class="lapki-animal-media-column">
                    <h2><?php esc_html_e('Фото притулку', 'lapki'); ?></h2>
                    <p class="description"><?php esc_html_e('Зберігаються окремо від фото тварин, відображаються на публічній сторінці організації. Перше завантажене фото стає обкладинкою — саме воно показується в картці організації, зокрема в результатах пошуку на сторінці «Організації»; позначку «головне» (⭐) можна перенести на інше фото.', 'lapki'); ?></p>
                    <div id="organization-media-gallery" class="lapki-media-gallery">
                        <!-- Існуючі фото завантажуються через AJAX -->
                    </div>

                    <h3 style="margin-top: 20px;"><?php esc_html_e('Додати нові фото', 'lapki'); ?></h3>
                    <div id="organization-dropzone-upload" class="lapki-dropzone">
                        <div class="dz-message">
                            <?php esc_html_e('Перетягніть файли сюди або клікніть для вибору', 'lapki'); ?><br>
                            <span style="font-size: 12px; color: #666;">(<?php esc_html_e('JPG, PNG, GIF, WebP, до 10 МБ', 'lapki'); ?>)</span>
                        </div>
                    </div>

                    <h2 style="margin-top: 30px;"><?php esc_html_e('Відео притулку', 'lapki'); ?></h2>
                    <p class="description"><?php esc_html_e('Посилання на YouTube, Vimeo, TikTok, Instagram, Facebook або Dailymotion — власного відеосховища немає, файли не приймаються, лише зовнішні посилання. Можна вставити одразу декілька — розділювач не важливий (пробіл, кома, з нового рядка).', 'lapki'); ?></p>
                    <div id="organization-video-list" class="lapki-media-gallery">
                        <!-- Існуючі відео завантажуються через AJAX -->
                    </div>
                    <textarea id="organization-video-urls" class="regular-text" rows="3" style="width:100%; margin-bottom:8px;" placeholder="https://www.youtube.com/watch?v=... https://vimeo.com/..."></textarea>
                    <button type="button" id="organization-video-add" class="button"><?php esc_html_e('Додати відео', 'lapki'); ?></button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Модальне вікно для збільшення фото -->
        <div id="photo-modal" class="lapki-photo-modal" style="display: none;">
            <span class="lapki-modal-close">&times;</span>
            <img class="lapki-modal-content" id="modal-image">
        </div>

        <style>
            .required { color: #d63384; }
            .lapki-form h2 { margin-top: 30px; }

            .lapki-animal-edit-layout {
                display: flex;
                gap: 30px;
                margin-top: 20px;
            }

            .lapki-animal-form-column {
                flex: 1;
                min-width: 0;
            }

            .lapki-animal-media-column {
                width: 350px;
                flex-shrink: 0;
            }

            .lapki-media-gallery {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-bottom: 20px;
            }

            .lapki-media-item {
                position: relative;
                width: 100px;
                height: 100px;
                border: 2px solid #ddd;
                border-radius: 4px;
                overflow: hidden;
                cursor: pointer;
                transition: all 0.2s;
            }

            .lapki-media-item:hover {
                border-color: #0073aa;
                transform: scale(1.05);
            }

            .lapki-media-item.is-primary {
                border-color: #00a32a;
                border-width: 3px;
            }

            .lapki-media-item img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .lapki-media-item-actions {
                position: absolute;
                top: 0;
                right: 0;
                display: flex;
                gap: 2px;
                padding: 4px;
                background: rgba(0,0,0,0.6);
                opacity: 0;
                transition: opacity 0.2s;
            }

            .lapki-media-item:hover .lapki-media-item-actions {
                opacity: 1;
            }

            .lapki-media-item-actions button {
                background: white;
                border: none;
                width: 24px;
                height: 24px;
                border-radius: 3px;
                cursor: pointer;
                font-size: 14px;
                line-height: 1;
                padding: 0;
            }

            .lapki-media-item-actions button:hover {
                background: #f0f0f0;
            }

            .lapki-media-primary-badge {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                background: #00a32a;
                color: white;
                font-size: 10px;
                text-align: center;
                padding: 2px;
                font-weight: bold;
            }

            .lapki-media-item.is-video {
                background: #222;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 28px;
            }

            .lapki-dropzone {
                border: 2px dashed #ddd;
                border-radius: 4px;
                padding: 20px;
                text-align: center;
                cursor: pointer;
                transition: all 0.2s;
            }

            .lapki-dropzone:hover,
            .lapki-dropzone.dz-drag-hover {
                border-color: #0073aa;
                background: #f0f6fc;
            }

            .lapki-photo-modal {
                display: none;
                position: fixed;
                z-index: 100000;
                padding-top: 50px;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0,0,0,0.9);
            }

            .lapki-modal-content {
                margin: auto;
                display: block;
                max-width: 90%;
                max-height: 90%;
            }

            .lapki-modal-close {
                position: absolute;
                top: 15px;
                right: 35px;
                color: #f1f1f1;
                font-size: 40px;
                font-weight: bold;
                cursor: pointer;
            }

            .lapki-modal-close:hover {
                color: #bbb;
            }
        </style>
<?php
    }
}

// Ініціалізація адмін панелі
Lapki_Admin::init();
