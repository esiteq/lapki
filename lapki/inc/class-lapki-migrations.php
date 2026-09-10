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
    const DB_VERSION = '2.18.0';

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
        self::drop_legacy_unique_wp_user();
        self::drop_legacy_unique_member_user();
        self::migrate_koatuu_to_katottg();
        Lapki_Roles::install();
        self::maybe_seed();
        self::maybe_backfill_organization_members();
        self::maybe_backfill_city_display();
    }

    /**
     * dbDelta() вміє лише ДОДАВАТИ стовпці/індекси — не прибирає застарілі.
     * unique_wp_user (1 організація = 1 користувач) знято з CREATE TABLE вище
     * (замінено на членство many-to-many через lapki_organization_members),
     * тож для вже встановлених сайтів індекс потрібно прибрати вручну.
     */
    private static function drop_legacy_unique_wp_user() {
        global $wpdb;

        $table = $wpdb->prefix . 'lapki_organizations';

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.statistics
             WHERE table_schema = %s AND table_name = %s AND index_name = 'unique_wp_user'",
            DB_NAME,
            $table
        ));

        if ((int) $exists > 0) {
            $wpdb->query("ALTER TABLE {$table} DROP INDEX unique_wp_user");
        }
    }

    /**
     * unique_member_user (1 користувач = 1 організація) знято з CREATE TABLE
     * нижче — користувач тепер може подавати заявки й бути учасником кількох
     * організацій одночасно. Замінено на unique_org_user (organization_id,
     * wp_user_id), яка лише не дає подати другу заявку в ТУ САМУ організацію.
     */
    private static function drop_legacy_unique_member_user() {
        global $wpdb;

        $table = $wpdb->prefix . 'lapki_organization_members';

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.statistics
             WHERE table_schema = %s AND table_name = %s AND index_name = 'unique_member_user'",
            DB_NAME,
            $table
        ));

        if ((int) $exists > 0) {
            $wpdb->query("ALTER TABLE {$table} DROP INDEX unique_member_user");
        }
    }

    /**
     * КОАТУУ — скасований 2020 року адмінкод, замінений на актуальний КАТОТТГ
     * (wp_lapki_geo.katottg_code — 100% покриття довідника, на відміну від
     * колишніх 71% для координат за КОАТУУ, див. .doc/geo.md). Ідентифікатор
     * обраного населеного пункту на тваринах/організаціях більше не потрібно
     * тримати у форматі КОАТУУ — переносимо в address_city_katottg/city_katottg
     * (нові колонки, вже створені create_tables() вище) і прибираємо старі.
     * Ідемпотентно: перевірка "стара колонка ще існує" сама є вартовим —
     * після першого прогону колонки нема, і метод одразу виходить.
     */
    private static function migrate_koatuu_to_katottg() {
        global $wpdb;

        $geo_table = $wpdb->prefix . 'lapki_geo';

        $map = [
            $wpdb->prefix . 'lapki_animals'       => ['old' => 'address_city_koatuu', 'new' => 'address_city_katottg'],
            $wpdb->prefix . 'lapki_organizations' => ['old' => 'city_koatuu', 'new' => 'city_katottg'],
        ];

        foreach ($map as $table => $cols) {
            $old_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = %s AND table_name = %s AND column_name = %s",
                DB_NAME,
                $table,
                $cols['old']
            ));

            if ((int) $old_exists === 0) {
                continue;
            }

            $wpdb->query(
                "UPDATE {$table} t
                 JOIN {$geo_table} g ON g.koatuu_code COLLATE utf8mb4_unicode_ci = t.{$cols['old']}
                 SET t.{$cols['new']} = g.katottg_code
                 WHERE t.{$cols['old']} IS NOT NULL AND t.{$cols['old']} != '' AND t.{$cols['new']} IS NULL"
            );

            $wpdb->query("ALTER TABLE {$table} DROP COLUMN {$cols['old']}");
        }
    }

    /**
     * Одноразовий backfill: якщо таблиця членства порожня, а організації вже
     * є (сайт оновлюється зі старої 1-до-1 схеми) — перенести існуючих
     * власників (organizations.wp_user_id) у членство з роллю 'owner'.
     */
    private static function maybe_backfill_organization_members() {
        global $wpdb;

        $members_table = $wpdb->prefix . 'lapki_organization_members';
        $members_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$members_table}");

        if ($members_count > 0) {
            return;
        }

        $organizations_table = $wpdb->prefix . 'lapki_organizations';

        $wpdb->query(
            "INSERT INTO {$members_table} (organization_id, wp_user_id, role, created_at)
             SELECT id, wp_user_id, 'owner', COALESCE(created_at, NOW())
             FROM {$organizations_table}
             WHERE wp_user_id IS NOT NULL AND wp_user_id > 0"
        );
    }

    /**
     * Одноразовий backfill: тварини/організації, у яких вже є address_city_katottg/
     * city_katottg (обрані зі списку до появи кешованого підпису
     * address_city_display/city_display), але кеш ще порожній — досипати
     * його. Ідемпотентно (умова "display IS NULL" сама є вартовим — після
     * прогону порожніх рядків не лишається, крім тих, де katottg взагалі
     * не привʼязаний до жодного запису довідника). Обчислення (3-рівнева
     * логіка обласний/районний центр/громада) робить PHP
     * (Lapki_Main::format_city_location_from_geo()), не SQL.
     */
    private static function maybe_backfill_city_display() {
        global $wpdb;

        $animals_table = $wpdb->prefix . 'lapki_animals';
        $orgs_table = $wpdb->prefix . 'lapki_organizations';

        $animals = $wpdb->get_results(
            "SELECT id, address_city_katottg FROM {$animals_table}
             WHERE address_city_katottg IS NOT NULL AND address_city_katottg != ''
             AND (address_city_display IS NULL OR address_city_display = '')",
            ARRAY_A
        );
        foreach ($animals as $row) {
            $geo = Lapki_Geo::get_by_katottg_code($row['address_city_katottg']);
            if (!$geo) {
                continue;
            }
            $wpdb->update(
                $animals_table,
                ['address_city_display' => Lapki_Main::format_city_location_from_geo($geo)],
                ['id' => $row['id']],
                ['%s'],
                ['%d']
            );
        }

        $orgs = $wpdb->get_results(
            "SELECT id, city_katottg FROM {$orgs_table}
             WHERE city_katottg IS NOT NULL AND city_katottg != ''
             AND (city_display IS NULL OR city_display = '')",
            ARRAY_A
        );
        foreach ($orgs as $row) {
            $geo = Lapki_Geo::get_by_katottg_code($row['city_katottg']);
            if (!$geo) {
                continue;
            }
            $wpdb->update(
                $orgs_table,
                ['city_display' => Lapki_Main::format_city_location_from_geo($geo)],
                ['id' => $row['id']],
                ['%s'],
                ['%d']
            );
        }
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
  created_by_user_id bigint(20) unsigned DEFAULT NULL,
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
  additional_attributes text DEFAULT NULL,
  description text,
  spayed_neutered tinyint(1) DEFAULT NULL,
  house_trained tinyint(1) DEFAULT NULL,
  declawed tinyint(1) DEFAULT NULL,
  special_needs tinyint(1) DEFAULT NULL,
  shots_current tinyint(1) DEFAULT NULL,
  from_war_zone tinyint(1) DEFAULT NULL,
  good_with_children tinyint(1) DEFAULT NULL,
  good_with_dogs tinyint(1) DEFAULT NULL,
  good_with_cats tinyint(1) DEFAULT NULL,
  contact_email varchar(255) DEFAULT NULL,
  contact_phone varchar(50) DEFAULT NULL,
  address1 varchar(255) DEFAULT NULL,
  address2 varchar(255) DEFAULT NULL,
  address_city varchar(100) DEFAULT NULL,
  address_city_katottg varchar(20) DEFAULT NULL,
  address_city_display varchar(255) DEFAULT NULL,
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
  KEY idx_created_by (created_by_user_id),
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
  city_katottg varchar(20) DEFAULT NULL,
  city_display varchar(255) DEFAULT NULL,
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
  KEY idx_wp_user (wp_user_id),
  KEY idx_location (state,city),
  KEY idx_type (type),
  KEY idx_verified (is_verified)
) $charset_collate;";

        // Хто до якої організації прив'язаний — багато-до-багатьох: один
        // користувач може бути учасником/власником кількох організацій
        // одночасно (unique_org_user не дає подати ДРУГУ заявку в ТУ САМУ
        // організацію, але не обмежує кількість різних організацій).
        // status: 'pending' (заявка очікує підтвердження власником) або
        // 'approved' (активне членство — дає права керування тваринами/організацією).
        $tables[] = "CREATE TABLE {$p}lapki_organization_members (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  organization_id bigint(20) unsigned NOT NULL,
  wp_user_id bigint(20) unsigned NOT NULL,
  role varchar(20) NOT NULL DEFAULT 'member',
  status varchar(20) NOT NULL DEFAULT 'approved',
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY unique_org_user (organization_id, wp_user_id),
  KEY idx_organization (organization_id),
  KEY idx_role (role),
  KEY idx_status (status)
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
  wp_user_id bigint(20) unsigned DEFAULT NULL,
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
  KEY idx_status (status),
  KEY idx_wp_user (wp_user_id)
) $charset_collate;";

        // Довідник населених пунктів України (КАТОТТГ) — для автодоповнення
        // міста/громади. Пласка структура один-в-один з джерелом (settlements.csv,
        // katottg.net.ua, CC BY 4.0) — область/район/громада зберігаються як
        // прості рядки на кожному записі, без окремих нормалізованих таблиць
        // (немає потреби в JOIN для простого пошуку за назвою).
        $tables[] = "CREATE TABLE {$p}lapki_geo (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  katottg_code varchar(20) NOT NULL,
  koatuu_code varchar(15) DEFAULT NULL,
  name varchar(255) NOT NULL,
  type enum('місто','селище міського типу','селище','село') NOT NULL,
  oblast varchar(100) NOT NULL DEFAULT '',
  raion varchar(100) NOT NULL DEFAULT '',
  hromada varchar(150) NOT NULL DEFAULT '',
  latitude decimal(10,7) DEFAULT NULL,
  longitude decimal(10,7) DEFAULT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY katottg_code (katottg_code),
  KEY idx_name (name),
  KEY idx_oblast (oblast),
  KEY idx_koatuu (koatuu_code)
) $charset_collate;";

        // Лог кожного виклику "Покращити за допомогою ШІ" — для сторінки
        // статистики (Lapki → Статистика): кількість запитів за період,
        // успішні/невдалі, по провайдеру, + токени з відповіді API (те, що
        // реально повернув провайдер у usageMetadata — не оцінка на клієнті)
        // + код/текст помилки для невдалих викликів (розбивка по типу
        // помилки в статистиці, а не лише загальне число "Помилка").
        $tables[] = "CREATE TABLE {$p}lapki_ai_usage_log (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  provider varchar(50) NOT NULL,
  success tinyint(1) NOT NULL DEFAULT 0,
  wp_user_id bigint(20) unsigned DEFAULT NULL,
  prompt_tokens int(10) unsigned NOT NULL DEFAULT 0,
  completion_tokens int(10) unsigned NOT NULL DEFAULT 0,
  total_tokens int(10) unsigned NOT NULL DEFAULT 0,
  error_code varchar(50) DEFAULT NULL,
  error_message text,
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY idx_created (created_at),
  KEY idx_provider (provider),
  KEY idx_error_code (error_code)
) $charset_collate;";

        // Шаблони email-повідомлень, що надсилає сайт автоматично (Lapki →
        // Email-шаблони). slug — стабільний код для пошуку конкретного
        // шаблону з коду (send_application_emails() тощо), незалежний від
        // назви/subject, які адмін може згодом відредагувати в UI.
        // placeholders — довідкова інформація для адмінки (список доступних
        // {міток} для конкретного шаблону), не використовується при рендері.
        $tables[] = "CREATE TABLE {$p}lapki_email (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  slug varchar(100) NOT NULL,
  name varchar(255) NOT NULL,
  subject varchar(255) NOT NULL,
  body text NOT NULL,
  placeholders varchar(500) DEFAULT NULL,
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY slug (slug)
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

        $geo_table = $wpdb->prefix . 'lapki_geo';
        $geo_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$geo_table}");

        if ($geo_count === 0) {
            self::seed_geo();
        }

        $geo_coords_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$geo_table} WHERE latitude IS NOT NULL");

        if ($geo_coords_count === 0) {
            self::seed_geo_coordinates();
        }

        // Разовий фолбек-крок (не прив'язаний до geo_coords_count === 0, бо
        // після нього завжди лишається один непокритий населений пункт —
        // інакше цей UPDATE ганяв би 31 тис. рядків на кожній міграції)
        if (!get_option('lapki_geo_katottg_backfilled')) {
            self::seed_geo_coordinates_katottg_fallback();
            update_option('lapki_geo_katottg_backfilled', 1);
        }

        $email_table = $wpdb->prefix . 'lapki_email';
        $email_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$email_table}");

        if ($email_count === 0) {
            self::seed_email_templates();
        }
    }

    /**
     * Засіяти єдиний наразі шаблон email-сповіщення власника тварини
     * (притулку або приватної особи) про нову заявку на прилаштування.
     */
    private static function seed_email_templates() {
        global $wpdb;

        $table = $wpdb->prefix . 'lapki_email';

        $wpdb->insert($table, [
            'slug' => 'application_owner_notification',
            'name' => 'Заявка на прилаштування',
            'subject' => 'Нова заявка на усиновлення: {animal_name}',
            'body' => "Отримано нову заявку на усиновлення тварини \"{animal_name}\".\n\nІм'я: {applicant_name}\nEmail: {applicant_email}\nТелефон: {applicant_phone}\nПовідомлення: {applicant_message}\n\nПереглянути заявки: {applications_url}",
            'placeholders' => 'animal_name,applicant_name,applicant_email,applicant_phone,applicant_message,applications_url',
        ]);
    }

    /**
     * Імпортувати довідник населених пунктів України з бандл-файлу
     * inc/data/settlements.csv (КАТОТТГ, id;koatuu;name;type;oblast;raion;hromada).
     * Вставка пачками по 500 рядків — набагато швидше за ~30 тис. окремих INSERT.
     */
    private static function seed_geo() {
        global $wpdb;

        $file = LAPKI_PLUGIN_DIR . 'inc/data/settlements.csv';

        if (!file_exists($file)) {
            return;
        }

        $handle = fopen($file, 'r');
        if (!$handle) {
            return;
        }

        $table = $wpdb->prefix . 'lapki_geo';
        $batch_size = 500;
        $rows = [];

        fgetcsv($handle, 0, ';', '"', '\\'); // заголовок

        while (($data = fgetcsv($handle, 0, ';', '"', '\\')) !== false) {
            if (count($data) < 7) {
                continue;
            }

            [$katottg_code, $koatuu_code, $name, $type, $oblast, $raion, $hromada] = $data;

            $rows[] = $wpdb->prepare(
                '(%s, %s, %s, %s, %s, %s, %s)',
                $katottg_code, $koatuu_code, $name, $type, $oblast, $raion, $hromada
            );

            if (count($rows) >= $batch_size) {
                self::insert_geo_batch($table, $rows);
                $rows = [];
            }
        }

        if (!empty($rows)) {
            self::insert_geo_batch($table, $rows);
        }

        fclose($handle);
    }

    private static function insert_geo_batch($table, $rows) {
        global $wpdb;

        $wpdb->query(
            "INSERT INTO {$table} (katottg_code, koatuu_code, name, type, oblast, raion, hromada) VALUES " . implode(',', $rows)
        );
    }

    /**
     * Наповнити довідник координатами (lat/lon) з бандл-файлу
     * inc/data/settlements-coords.csv (koatuu;latitude;longitude) — витягнуто
     * з Wikidata (властивості P1077 "КОАТУУ" + P625 "координати", дані CC0)
     * одноразовим SPARQL-запитом, дослідження в .doc/geo.md. Покриває ~71%
     * усіх населених пунктів довідника (93%+ міст і смт, менше — дрібних сіл,
     * бо в частини статей Wikidata просто немає властивості P1077, а не через
     * помилку зіставлення). Матчиться за koatuu_code — прямий, точний збіг,
     * без нечіткого порівняння назв.
     */
    private static function seed_geo_coordinates() {
        global $wpdb;

        $file = LAPKI_PLUGIN_DIR . 'inc/data/settlements-coords.csv';

        if (!file_exists($file)) {
            return;
        }

        $handle = fopen($file, 'r');
        if (!$handle) {
            return;
        }

        $table = $wpdb->prefix . 'lapki_geo';

        fgetcsv($handle, 0, ';', '"', '\\'); // заголовок

        while (($data = fgetcsv($handle, 0, ';', '"', '\\')) !== false) {
            if (count($data) < 3) {
                continue;
            }

            [$koatuu_code, $latitude, $longitude] = $data;

            $wpdb->query($wpdb->prepare(
                "UPDATE {$table} SET latitude = %f, longitude = %f WHERE koatuu_code = %s",
                $latitude, $longitude, $koatuu_code
            ));
        }

        fclose($handle);
    }

    /**
     * Фолбек-донаповнення координат за katottg_code — з бандл-файлу
     * inc/data/settlements-coords-katottg.csv (katottg;latitude;longitude),
     * витягнуто з Wikidata (властивість P9435 "КАТОТТГ" + P625 "координати",
     * CC0). КОАТУУ (P1077) — застарілий, офіційно скасований 2020 року
     * кодифікатор, тому частина новіших/дрібних населених пунктів має в
     * Wikidata тільки актуальний КАТОТТГ-код без КОАТУУ. Комбінація обох
     * джерел покриває 29710/29711 (99.997%) довідника — лишається 1 село
     * (Ільківка, Вінницька обл.), відсутнє в Wikidata за обома кодами.
     * `AND latitude IS NULL` — тільки донаповнення прогалин, ніколи не
     * перезаписує вже заповнене з koatuu-джерела (seed_geo_coordinates()).
     */
    private static function seed_geo_coordinates_katottg_fallback() {
        global $wpdb;

        $file = LAPKI_PLUGIN_DIR . 'inc/data/settlements-coords-katottg.csv';

        if (!file_exists($file)) {
            return;
        }

        $handle = fopen($file, 'r');
        if (!$handle) {
            return;
        }

        $table = $wpdb->prefix . 'lapki_geo';

        fgetcsv($handle, 0, ';', '"', '\\'); // заголовок

        while (($data = fgetcsv($handle, 0, ';', '"', '\\')) !== false) {
            if (count($data) < 3) {
                continue;
            }

            [$katottg_code, $latitude, $longitude] = $data;

            $wpdb->query($wpdb->prepare(
                "UPDATE {$table} SET latitude = %f, longitude = %f WHERE katottg_code = %s AND latitude IS NULL",
                $latitude, $longitude, $katottg_code
            ));
        }

        fclose($handle);
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
