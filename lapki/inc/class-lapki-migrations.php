<?php

/**
 * Lapki DB Migrations
 *
 * Створює таблиці плагіна через dbDelta() (ідемпотентно — безпечно
 * викликати повторно) та засіює довідник атрибутів на порожній БД.
 *
 * @package Lapki
 * @author Oleksii Bugrov
 */

class Lapki_Migrations {

    const DB_VERSION_OPTION = 'lapki_db_version';

    /**
     * Версія схеми. Змінюйте це число, коли додаєте/змінюєте таблиці —
     * тоді maybe_migrate() автоматично перезапустить dbDelta() навіть
     * без деактивації/активації плагіна (для вже встановлених сайтів).
     */
    const DB_VERSION = '2.1.0';

    /**
     * Викликати на init/plugins_loaded — виконує міграцію лише якщо
     * версія схеми змінилась з часу останнього запуску.
     */
    public static function maybe_migrate() {
        if (get_option(self::DB_VERSION_OPTION) !== self::DB_VERSION) {
            self::install();
            update_option(self::DB_VERSION_OPTION, self::DB_VERSION);
        }
    }

    /**
     * Повна установка: таблиці + ролі/capabilities + seed-дані (якщо таблиці порожні)
     */
    public static function install() {
        self::create_tables();
        Lapki_Roles::install();
        self::maybe_seed();
    }

    /**
     * Створити/оновити таблиці плагіна через dbDelta()
     */
    private static function create_tables() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $p = $wpdb->prefix;

        $tables = [];

        $tables[] = "CREATE TABLE {$p}lapki_animals (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  organization_id bigint(20) unsigned NOT NULL,
  name varchar(255) NOT NULL,
  type varchar(50) NOT NULL,
  species varchar(50) NOT NULL,
  status varchar(50) DEFAULT 'adoptable',
  breed_primary varchar(100) DEFAULT NULL,
  breed_secondary varchar(100) DEFAULT NULL,
  breed_mixed tinyint(1) DEFAULT 0,
  breed_unknown tinyint(1) DEFAULT 0,
  color_primary varchar(100) DEFAULT NULL,
  color_secondary varchar(100) DEFAULT NULL,
  color_tertiary varchar(100) DEFAULT NULL,
  age varchar(50) NOT NULL,
  gender varchar(50) NOT NULL,
  size varchar(50) NOT NULL,
  coat varchar(50) DEFAULT NULL,
  description text,
  spayed_neutered tinyint(1) DEFAULT NULL,
  house_trained tinyint(1) DEFAULT NULL,
  declawed tinyint(1) DEFAULT NULL,
  special_needs tinyint(1) DEFAULT NULL,
  shots_current tinyint(1) DEFAULT NULL,
  good_with_children tinyint(1) DEFAULT NULL,
  good_with_dogs tinyint(1) DEFAULT NULL,
  good_with_cats tinyint(1) DEFAULT NULL,
  contact_email varchar(255) DEFAULT NULL,
  contact_phone varchar(50) DEFAULT NULL,
  address1 varchar(255) DEFAULT NULL,
  address2 varchar(255) DEFAULT NULL,
  address_city varchar(100) DEFAULT NULL,
  address_state varchar(100) DEFAULT NULL,
  address_postcode varchar(20) DEFAULT NULL,
  address_country varchar(2) DEFAULT 'UA',
  latitude decimal(10,8) DEFAULT NULL,
  longitude decimal(11,8) DEFAULT NULL,
  published_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  url varchar(500) DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY idx_type_status (type,status),
  KEY idx_location (address_state,address_city),
  KEY idx_coords (latitude,longitude),
  KEY idx_published (published_at),
  KEY idx_age_gender_size (age,gender,size),
  KEY idx_breed_primary (breed_primary),
  KEY idx_color_primary (color_primary),
  KEY idx_organization (organization_id),
  KEY idx_attributes (spayed_neutered,house_trained,special_needs),
  KEY idx_compatibility (good_with_children,good_with_dogs,good_with_cats)
) $charset_collate;";

        $tables[] = "CREATE TABLE {$p}lapki_organizations (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  wp_user_id bigint(20) unsigned NOT NULL,
  name varchar(255) NOT NULL,
  type varchar(50) DEFAULT 'individual',
  email varchar(255) DEFAULT NULL,
  phone varchar(50) DEFAULT NULL,
  website varchar(255) DEFAULT NULL,
  hours text,
  mission_statement text,
  adoption_policy text,
  adoption_url varchar(500) DEFAULT NULL,
  social_media json DEFAULT NULL,
  address1 varchar(255) DEFAULT NULL,
  address2 varchar(255) DEFAULT NULL,
  city varchar(100) DEFAULT NULL,
  state varchar(100) DEFAULT NULL,
  postcode varchar(20) DEFAULT NULL,
  country varchar(2) DEFAULT 'UA',
  latitude decimal(10,8) DEFAULT NULL,
  longitude decimal(11,8) DEFAULT NULL,
  is_verified tinyint(1) DEFAULT 0,
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  url varchar(500) DEFAULT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY unique_wp_user (wp_user_id),
  KEY idx_location (state,city),
  KEY idx_type (type),
  KEY idx_verified (is_verified)
) $charset_collate;";

        $tables[] = "CREATE TABLE {$p}lapki_media (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  entity_type varchar(50) NOT NULL,
  entity_id bigint(20) unsigned NOT NULL,
  media_type varchar(20) NOT NULL,
  filename varchar(255) DEFAULT NULL,
  file_path varchar(500) DEFAULT NULL,
  embed_code text,
  video_url varchar(500) DEFAULT NULL,
  title varchar(255) DEFAULT NULL,
  description text,
  alt_text varchar(255) DEFAULT NULL,
  sort_order tinyint(4) DEFAULT 0,
  is_primary tinyint(1) DEFAULT 0,
  is_active tinyint(1) DEFAULT 1,
  file_size int(10) unsigned DEFAULT NULL,
  width int(10) unsigned DEFAULT NULL,
  height int(10) unsigned DEFAULT NULL,
  uploaded_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY idx_entity (entity_type,entity_id),
  KEY idx_entity_order (entity_type,entity_id,sort_order),
  KEY idx_media_type (media_type),
  KEY idx_primary (entity_type,entity_id,is_primary),
  KEY idx_active (is_active)
) $charset_collate;";

        $tables[] = "CREATE TABLE {$p}lapki_attributes (
  id int(11) NOT NULL AUTO_INCREMENT,
  entity varchar(32) NOT NULL DEFAULT 'animal',
  entity_type varchar(64) NOT NULL,
  attr_name varchar(64) NOT NULL,
  attr_value varchar(128) NOT NULL,
  attr_display varchar(128) NOT NULL,
  lang char(2) NOT NULL DEFAULT 'en',
  PRIMARY KEY  (id),
  UNIQUE KEY unique_attr (entity,entity_type,attr_name,attr_value,lang),
  KEY idx_entity_type (entity,entity_type),
  KEY idx_entity_attr (entity,attr_name),
  KEY idx_entity_lang (entity,entity_type,lang)
) $charset_collate;";

        $tables[] = "CREATE TABLE {$p}lapki_tags (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  entity_type varchar(50) NOT NULL,
  entity_id bigint(20) unsigned NOT NULL,
  tag varchar(100) NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY unique_entity_tag (entity_type,entity_id,tag),
  KEY idx_entity (entity_type,entity_id),
  KEY idx_tag (tag)
) $charset_collate;";

        $tables[] = "CREATE TABLE {$p}lapki_applications (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  animal_id bigint(20) unsigned NOT NULL,
  organization_id bigint(20) unsigned NOT NULL,
  applicant_name varchar(255) NOT NULL,
  applicant_email varchar(255) NOT NULL,
  applicant_phone varchar(50) DEFAULT NULL,
  message text,
  status varchar(20) NOT NULL DEFAULT 'new',
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY idx_animal (animal_id),
  KEY idx_organization (organization_id),
  KEY idx_status (status)
) $charset_collate;";

        foreach ($tables as $sql) {
            dbDelta($sql);
        }
    }

    /**
     * Засіяти довідники, якщо таблиці порожні (тільки для нових інсталяцій)
     */
    private static function maybe_seed() {
        global $wpdb;

        $attributes_table = $wpdb->prefix . 'lapki_attributes';
        $attributes_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$attributes_table}");

        if ($attributes_count === 0) {
            self::seed_attributes();
        }

        $organizations_table = $wpdb->prefix . 'lapki_organizations';
        $organizations_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$organizations_table}");

        if ($organizations_count === 0) {
            self::seed_demo_content();
        }
    }

    /**
     * Завантажити довідник атрибутів (age/gender/size/coat/status/breed/color
     * для dog/cat/bird/rabbit, uk+en) з бандл-файлу
     */
    private static function seed_attributes() {
        global $wpdb;

        $file = LAPKI_PLUGIN_DIR . 'inc/data/seed-attributes.sql';

        if (!file_exists($file)) {
            return;
        }

        $sql = file_get_contents($file);
        $sql = str_replace('%PREFIX%', $wpdb->prefix, $sql);

        $wpdb->query($sql);
    }

    /**
     * Створити демо-організацію та демо-тварину для нової інсталяції,
     * щоб адмінка і фронтенд одразу мали з чим працювати.
     */
    private static function seed_demo_content() {
        $owner_id = get_current_user_id();

        if (!$owner_id) {
            $admins = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
            $owner_id = !empty($admins) ? $admins[0] : 1;
        }

        $org_id = Lapki_Organization::create([
            'wp_user_id' => $owner_id,
            'name' => 'Демо притулок Lapki',
            'type' => 'shelter',
            'email' => get_option('admin_email'),
            'city' => 'Київ',
            'state' => 'Київська область',
            'country' => 'UA',
            'is_verified' => 1,
        ]);

        if (!$org_id) {
            return;
        }

        Lapki_Animal::create([
            'organization_id' => $org_id,
            'name' => 'Демо тварина',
            'type' => 'cat',
            'species' => 'cat',
            'age' => 'young',
            'gender' => 'male',
            'size' => 'medium',
            'coat' => 'short',
            'status' => 'adoptable',
            'description' => 'Демонстраційний запис, створений автоматично при активації плагіна. Можна відредагувати або видалити.',
        ]);
    }
}
