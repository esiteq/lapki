<?php

/**
 * Lapki Admin Animals Table
 * Адмін панель для перегляду тварин з пагінацією і сортуванням
 */

if (! class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Lapki_Admin
{

    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_scripts']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
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
        if (strpos($hook, 'lapki') !== false) {
            // Leaflet CSS and JS (OpenStreetMap)
            wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
            wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);

            // Dropzone CSS and JS (file uploads)
            wp_enqueue_style('dropzone', 'https://unpkg.com/dropzone@5/dist/min/dropzone.min.css', [], '5.9.3');
            wp_enqueue_script('dropzone', 'https://unpkg.com/dropzone@5/dist/min/dropzone.min.js', [], '5.9.3', true);

            wp_enqueue_style('lapki-admin', LAPKI_PLUGIN_URL . 'css/lapki-admin.css', [], LAPKI_VERSION);
            wp_enqueue_script('lapki-admin', LAPKI_PLUGIN_URL . 'js/lapki-admin.js', ['jquery', 'leaflet', 'dropzone'], LAPKI_VERSION, true);

            // Локалізація
            wp_localize_script('lapki-admin', 'lapkiAdmin', [
                'nonce' => wp_create_nonce('wp_rest'),
                'apiBase' => rest_url('lapki/v1')
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
            <h1 class="wp-heading-inline">Тварини</h1>
            <a href="<?php echo admin_url('admin.php?page=lapki-add-animal'); ?>" class="page-title-action">Додати тварину</a>
            <hr class="wp-header-end">

            <div class="tablenav top">
                <div class="alignleft actions">
                    <input type="search" id="animal-search-input" name="s" placeholder="Пошук тварин..." style="width:200px;">
                </div>
            </div>

            <table id="lapki-animals-table" class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" />
                        </td>
                        <th class="manage-column column-photo">Фото</th>
                        <th class="manage-column column-name column-primary">Кличка</th>
                        <th class="manage-column column-type">Тип</th>
                        <th class="manage-column column-breed">Порода</th>
                        <th class="manage-column column-age">Вік/Стать</th>
                        <th class="manage-column column-status">Статус</th>
                        <th class="manage-column column-organization">Організація</th>
                        <th class="manage-column column-dates">Дата</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="9" style="text-align:center;padding:40px;">
                            <div class="spinner is-active" style="float:none;margin:0 auto;"></div>
                            <p>Завантаження...</p>
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
            <h1 class="wp-heading-inline">Організації</h1>
            <a href="<?php echo admin_url('admin.php?page=lapki-add-organization'); ?>" class="page-title-action">Додати організацію</a>
            <hr class="wp-header-end">

            <div class="lapki-attr-filters">
                <input type="search" id="org-filter-search" placeholder="Пошук за назвою..." style="width:220px;">
                <select id="org-filter-type">
                    <option value="">Всі типи</option>
                    <option value="individual">individual</option>
                    <option value="shelter">shelter</option>
                    <option value="rescue">rescue</option>
                    <option value="vet_clinic">vet_clinic</option>
                </select>
                <button id="org-filter-apply" class="button">Фільтрувати</button>
            </div>

            <table id="lapki-organizations-table" class="wp-list-table widefat fixed striped" style="margin-top:15px;">
                <thead>
                    <tr>
                        <th style="width:50px;">ID</th>
                        <th>Назва</th>
                        <th style="width:110px;">Тип</th>
                        <th>Email</th>
                        <th style="width:120px;">Місто</th>
                        <th style="width:90px;">Тварин</th>
                        <th style="width:90px;">Верифіковано</th>
                        <th style="width:130px;">Дії</th>
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
            <h1 class="wp-heading-inline">Атрибути</h1>
            <button id="lapki-attr-add-btn" class="page-title-action">+ Додати атрибут</button>
            <hr class="wp-header-end">

            <div class="lapki-attr-filters">
                <select id="attr-filter-lang">
                    <option value="">Всі мови</option>
                    <option value="uk">uk</option>
                    <option value="en">en</option>
                </select>
                <select id="attr-filter-entity">
                    <option value="">Всі entity</option>
                    <option value="animal">animal</option>
                    <option value="org">org</option>
                    <option value="user">user</option>
                </select>
                <input type="text" id="attr-filter-entity-type" placeholder="entity_type (dog, cat, all...)" style="width:180px;">
                <select id="attr-filter-attr-name">
                    <option value="">Всі attr_name</option>
                    <option value="species">species</option>
                    <option value="breed">breed</option>
                    <option value="age">age</option>
                    <option value="gender">gender</option>
                    <option value="size">size</option>
                    <option value="coat">coat</option>
                    <option value="color">color</option>
                    <option value="status">status</option>
                </select>
                <input type="search" id="attr-filter-search" placeholder="Пошук за значенням..." style="width:200px;">
                <button id="attr-filter-apply" class="button">Фільтрувати</button>
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
                        <th style="width:130px;">Дії</th>
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
                <h2 id="lapki-attr-modal-title">Додати атрибут</h2>
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
                            <p class="description">Для глобальних атрибутів (age/gender/size) — <code>all</code>. Для типів — <code>type</code>. Для порід — назва типу: <code>dog</code>, <code>cat</code>.</p>
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
                        <td><input type="text" id="lapki-attr-display" class="regular-text" placeholder="Собака, Лабрадор, Молодий..."></td>
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
                    <button id="lapki-attr-save" class="button button-primary">Зберегти</button>
                    <button id="lapki-attr-cancel" class="button">Скасувати</button>
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
            <h1><?php echo $is_edit ? 'Редагувати тварину' : 'Додати тварину'; ?></h1>
            <p><a href="<?php echo admin_url('admin.php?page=lapki'); ?>" class="button">← Повернутися до списку</a></p>

            <div class="lapki-animal-edit-layout">
                <div class="lapki-animal-form-column">
                    <form id="lapki-animal-form" class="lapki-form">
                <h2>Основна інформація</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="name">Кличка <span class="required">*</span></label></th>
                        <td><input type="text" id="name" name="name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="organization_id">Організація <span class="required">*</span></label></th>
                        <td>
                            <select id="organization_id" name="organization_id" required>
                                <option value="">Виберіть організацію</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="type">Тип <span class="required">*</span></label></th>
                        <td>
                            <select id="type" name="type" required>
                                <option value="">Виберіть тип</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="status">Статус <span class="required">*</span></label></th>
                        <td>
                            <select id="status" name="status" required>
                                <option value="">Виберіть статус</option>
                                <option value="adoptable">До прилаштування</option>
                                <option value="adopted">Прилаштовано</option>
                                <option value="hold">На утриманні</option>
                                <option value="found">Знайдено</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <h2>Характеристики</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="age">Вік <span class="required">*</span></label></th>
                        <td>
                            <select id="age" name="age" required>
                                <option value="">Виберіть вік</option>
                                <!-- Динамічно завантажується через API -->
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="gender">Стать <span class="required">*</span></label></th>
                        <td>
                            <select id="gender" name="gender" required>
                                <option value="">Виберіть стать</option>
                                <!-- Динамічно завантажується через API -->
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="size">Розмір <span class="required">*</span></label></th>
                        <td>
                            <select id="size" name="size" required>
                                <option value="">Виберіть розмір</option>
                                <!-- Динамічно завантажується через API -->
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="color_primary">Основний колір</label></th>
                        <td>
                            <select id="color_primary" name="color_primary">
                                <option value="">Виберіть колір</option>
                                <!-- Динамічно завантажується через API залежно від типу -->
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="coat">Тип шерсті</label></th>
                        <td>
                            <select id="coat" name="coat">
                                <option value="">Виберіть тип</option>
                                <!-- Динамічно завантажується через API -->
                            </select>
                        </td>
                    </tr>
                </table>

                <h2>Породи</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="breed_primary">Основна порода</label></th>
                        <td>
                            <select id="breed_primary" name="breed_primary">
                                <option value="">Виберіть породу</option>
                                <!-- Динамічно завантажується через API залежно від типу -->
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="breed_secondary">Додаткова порода</label></th>
                        <td>
                            <select id="breed_secondary" name="breed_secondary">
                                <option value="">Виберіть породу</option>
                                <!-- Динамічно завантажується через API залежно від типу -->
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="breed_mixed">Метис</label></th>
                        <td><input type="checkbox" id="breed_mixed" name="breed_mixed" value="1"></td>
                    </tr>
                </table>

                <h2>Опис</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="description">Опис тварини</label></th>
                        <td><textarea id="description" name="description" class="large-text" rows="6" placeholder="Детальний опис характеру, звичок, особливостей..."></textarea></td>
                    </tr>
                </table>

                <h2>Здоров'я та особливості</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="spayed_neutered">Стерилізована/Кастрована</label></th>
                        <td><input type="checkbox" id="spayed_neutered" name="spayed_neutered" value="1"></td>
                    </tr>
                    <tr>
                        <th><label for="shots_current">Щеплення актуальні</label></th>
                        <td><input type="checkbox" id="shots_current" name="shots_current" value="1"></td>
                    </tr>
                    <tr>
                        <th><label for="house_trained">Приучена до лотка/туалету</label></th>
                        <td><input type="checkbox" id="house_trained" name="house_trained" value="1"></td>
                    </tr>
                    <tr>
                        <th><label for="special_needs">Особливі потреби</label></th>
                        <td><input type="checkbox" id="special_needs" name="special_needs" value="1"></td>
                    </tr>
                    <tr>
                        <th><label for="from_war_zone">З зони бойових дій</label></th>
                        <td><input type="checkbox" id="from_war_zone" name="from_war_zone" value="1"></td>
                    </tr>
                </table>

                <h2>Сумісність</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="good_with_children">Добре ладнає з дітьми</label></th>
                        <td><input type="checkbox" id="good_with_children" name="good_with_children" value="1"></td>
                    </tr>
                    <tr>
                        <th><label for="good_with_dogs">Добре ладнає з собаками</label></th>
                        <td><input type="checkbox" id="good_with_dogs" name="good_with_dogs" value="1"></td>
                    </tr>
                    <tr>
                        <th><label for="good_with_cats">Добре ладнає з котами</label></th>
                        <td><input type="checkbox" id="good_with_cats" name="good_with_cats" value="1"></td>
                    </tr>
                </table>

                <h2>Контактна інформація</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="contact_email">Email для контакту</label></th>
                        <td><input type="email" id="contact_email" name="contact_email" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="contact_phone">Телефон</label></th>
                        <td><input type="text" id="contact_phone" name="contact_phone" class="regular-text"></td>
                    </tr>
                </table>

                <h2>Місцезнаходження</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="address1">Вулиця, будинок</label></th>
                        <td><input type="text" id="address1" name="address1" class="regular-text" placeholder="вул. Хрещатик, 1"></td>
                    </tr>
                    <tr>
                        <th><label for="address2">Додаткова адреса</label></th>
                        <td><input type="text" id="address2" name="address2" class="regular-text" placeholder="кв. 10, під'їзд 2"></td>
                    </tr>
                    <tr>
                        <th><label for="address_city">Місто</label></th>
                        <td><input type="text" id="address_city" name="address_city" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="address_state">Область</label></th>
                        <td><input type="text" id="address_state" name="address_state" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="address_postcode">Індекс</label></th>
                        <td><input type="text" id="address_postcode" name="address_postcode" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="latitude">Широта</label></th>
                        <td><input type="number" id="latitude" name="latitude" step="0.00000001" class="regular-text" readonly></td>
                    </tr>
                    <tr>
                        <th><label for="longitude">Довгота</label></th>
                        <td><input type="number" id="longitude" name="longitude" step="0.00000001" class="regular-text" readonly></td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <button type="button" id="geocode-address" class="button">📍 Знайти на карті за адресою</button>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Карта</label></th>
                        <td>
                            <div id="location-map" style="height: 400px; width: 100%; border: 1px solid #ddd; border-radius: 4px;"></div>
                            <p class="description">Клікніть на карті щоб вибрати точне місцезнаходження. Координати оновляться автоматично.</p>
                        </td>
                    </tr>
                </table>

                        <p class="submit">
                            <button type="submit" class="button button-primary">Зберегти</button>
                            <a href="<?php echo admin_url('admin.php?page=lapki'); ?>" class="button">Скасувати</a>
                        </p>
                    </form>
                </div>

                <?php if ($is_edit): ?>
                <div class="lapki-animal-media-column">
                    <h2>Зображення</h2>
                    <div id="animal-media-gallery" class="lapki-media-gallery">
                        <!-- Існуючі зображення завантажуються через AJAX -->
                    </div>

                    <h3 style="margin-top: 20px;">Додати нові зображення</h3>
                    <div id="dropzone-upload" class="lapki-dropzone">
                        <div class="dz-message">
                            Перетягніть файли сюди або клікніть для вибору<br>
                            <span style="font-size: 12px; color: #666;">(JPG, PNG, GIF, WebP, до 10 МБ)</span>
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
            <h1><?php echo $is_edit ? 'Редагувати організацію' : 'Додати організацію'; ?></h1>
            <p><a href="<?php echo admin_url('admin.php?page=lapki-organizations'); ?>" class="button">← Повернутися до списку</a></p>

            <div class="lapki-animal-edit-layout">
                <div class="lapki-animal-form-column">
            <form id="lapki-organization-form" class="lapki-form">
                <input type="hidden" id="id" name="id">

                <h2>Основна інформація</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="name">Назва <span class="required">*</span></label></th>
                        <td><input type="text" id="name" name="name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="type">Тип <span class="required">*</span></label></th>
                        <td>
                            <select id="type" name="type" required>
                                <option value="individual">Приватна особа</option>
                                <option value="shelter">Притулок</option>
                                <option value="rescue">Волонтерська організація</option>
                                <option value="vet_clinic">Ветклініка</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="is_verified">Верифікована</label></th>
                        <td><input type="checkbox" id="is_verified" name="is_verified" value="1"></td>
                    </tr>
                </table>

                <h2>Контактна інформація</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="email">Email</label></th>
                        <td><input type="email" id="email" name="email" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="phone">Телефон</label></th>
                        <td><input type="text" id="phone" name="phone" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="website">Сайт</label></th>
                        <td><input type="url" id="website" name="website" class="regular-text" placeholder="https://"></td>
                    </tr>
                </table>

                <h2>Місцезнаходження</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="city">Місто</label></th>
                        <td><input type="text" id="city" name="city" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="state">Область</label></th>
                        <td><input type="text" id="state" name="state" class="regular-text"></td>
                    </tr>
                </table>

                <h2>Опис</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="mission_statement">Місія</label></th>
                        <td><textarea id="mission_statement" name="mission_statement" class="large-text" rows="4"></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="adoption_policy">Політика усиновлення</label></th>
                        <td><textarea id="adoption_policy" name="adoption_policy" class="large-text" rows="4"></textarea></td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">Зберегти</button>
                    <a href="<?php echo admin_url('admin.php?page=lapki-organizations'); ?>" class="button">Скасувати</a>
                </p>
            </form>
                </div>

                <?php if ($is_edit): ?>
                <div class="lapki-animal-media-column">
                    <h2>Фото притулку</h2>
                    <p class="description">Зберігаються окремо від фото тварин, відображаються на публічній сторінці організації.</p>
                    <div id="organization-media-gallery" class="lapki-media-gallery">
                        <!-- Існуючі фото завантажуються через AJAX -->
                    </div>

                    <h3 style="margin-top: 20px;">Додати нові фото</h3>
                    <div id="organization-dropzone-upload" class="lapki-dropzone">
                        <div class="dz-message">
                            Перетягніть файли сюди або клікніть для вибору<br>
                            <span style="font-size: 12px; color: #666;">(JPG, PNG, GIF, WebP, до 10 МБ)</span>
                        </div>
                    </div>

                    <h2 style="margin-top: 30px;">Відео притулку</h2>
                    <p class="description">Посилання на YouTube/Vimeo або пряме відео — власного відеосховища немає.</p>
                    <div id="organization-video-list" class="lapki-media-gallery">
                        <!-- Існуючі відео завантажуються через AJAX -->
                    </div>
                    <input type="url" id="organization-video-url" class="regular-text" style="width:100%; margin-bottom:8px;" placeholder="https://www.youtube.com/watch?v=...">
                    <input type="text" id="organization-video-title" class="regular-text" style="width:100%; margin-bottom:8px;" placeholder="Назва відео (необов'язково)">
                    <button type="button" id="organization-video-add" class="button">Додати відео</button>
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
