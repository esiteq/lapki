<?php

/**
 * Lapki Frontend
 *
 * Реєструє публічні URL (архів/пошук тварин, сторінка тварини,
 * список і сторінка організації) і віддає їх через Lapki_Template_Loader
 * (тема може перевизначити будь-який шаблон в lapki/{ім'я}.php).
 *
 * @package Lapki
 * @author Oleksii Bugrov
 */

class Lapki_Frontend {

    public static function init() {
        add_action('init', [__CLASS__, 'add_rewrite_rules']);
        add_filter('query_vars', [__CLASS__, 'add_query_vars']);
        add_filter('template_include', [__CLASS__, 'template_include']);
        add_shortcode('lapki_signup', [__CLASS__, 'render_signup_shortcode']);

        // SEO: кастомні route'и (не справжні WP-записи) інакше показують
        // однакову дефолтну назву сайту й жодного meta description
        add_filter('pre_get_document_title', [__CLASS__, 'filter_document_title']);
        add_action('wp_head', [__CLASS__, 'output_meta_description'], 1);
        add_action('wp_head', [__CLASS__, 'output_canonical_url'], 1);
        add_action('wp_head', [__CLASS__, 'output_open_graph_tags'], 1);
        add_filter('wp_robots', [__CLASS__, 'filter_wp_robots']);

        // Приховати архів автора (світить логін адміна, немає цінності для пошуку)
        add_action('template_redirect', [__CLASS__, 'maybe_redirect_author_archive']);
    }

    public static function add_rewrite_rules() {
        add_rewrite_rule('^animals/?$', 'index.php?lapki_page=animals_archive', 'top');
        add_rewrite_rule('^animals/([0-9]+)/?$', 'index.php?lapki_page=animal_single&lapki_animal_id=$matches[1]', 'top');
        add_rewrite_rule('^organizations/?$', 'index.php?lapki_page=organizations_archive', 'top');
        add_rewrite_rule('^organizations/([0-9]+)/?$', 'index.php?lapki_page=organization_single&lapki_org_id=$matches[1]', 'top');
        add_rewrite_rule('^widget-demo/?$', 'index.php?lapki_page=widget_demo', 'top');
        add_rewrite_rule('^profile/?$', 'index.php?lapki_page=profile', 'top');
        add_rewrite_rule('^donate/?$', 'index.php?lapki_page=donate', 'top');
    }

    public static function add_query_vars($vars) {
        $vars[] = 'lapki_page';
        $vars[] = 'lapki_animal_id';
        $vars[] = 'lapki_org_id';
        return $vars;
    }

    public static function template_include($template) {
        $page = get_query_var('lapki_page');

        if (!$page) {
            return $template;
        }

        $map = [
            'animals_archive' => 'archive-animals.php',
            'animal_single' => 'single-animal.php',
            'organizations_archive' => 'archive-organizations.php',
            'organization_single' => 'single-organization.php',
            'widget_demo' => 'widget-demo.php',
            'profile' => 'profile.php',
            'donate' => 'donate.php',
        ];

        if (empty($map[$page])) {
            return $template;
        }

        $located = Lapki_Template_Loader::locate($map[$page]);

        if (!$located) {
            return $template;
        }

        status_header(200);

        return $located;
    }

    /**
     * ID тварини для поточного запиту (query var уже провалідовано regex \d+)
     */
    public static function get_current_animal_id() {
        return absint(get_query_var('lapki_animal_id'));
    }

    /**
     * ID організації для поточного запиту
     */
    public static function get_current_organization_id() {
        return absint(get_query_var('lapki_org_id'));
    }

    /**
     * Дані поточної тварини — кешовані на запит (title/description/canonical/OG
     * усі хуки wp_head інакше окремо смикали б Lapki_Animal::get() кожен)
     */
    private static function get_current_animal_data() {
        static $cache = [];
        $id = self::get_current_animal_id();

        if (!$id) {
            return null;
        }

        if (!array_key_exists($id, $cache)) {
            $cache[$id] = Lapki_Animal::get($id);
        }

        return $cache[$id];
    }

    /**
     * Дані поточної організації — так само кешовані на запит
     */
    private static function get_current_organization_data() {
        static $cache = [];
        $id = self::get_current_organization_id();

        if (!$id) {
            return null;
        }

        if (!array_key_exists($id, $cache)) {
            $cache[$id] = Lapki_Organization::get($id);
        }

        return $cache[$id];
    }

    /**
     * Шорткод [lapki_signup] — форма реєстрації нового користувача
     * (приватна особа, притулок, ветклініка, ветеринар, волонтер).
     */
    public static function render_signup_shortcode() {
        ob_start();
        include LAPKI_PLUGIN_DIR . 'templates/shortcode-signup.php';
        return ob_get_clean();
    }

    /**
     * Унікальна <title> для кожного кастомного route'у — без цього
     * wp_get_document_title() не має за що зачепитись (це не справжні
     * WP-записи) і завжди повертає лише назву сайту для всіх них.
     */
    public static function filter_document_title($title) {
        $page = get_query_var('lapki_page');

        if (!$page) {
            return $title;
        }

        $site_name = get_bloginfo('name');

        switch ($page) {
            case 'animals_archive':
                return 'Тварини, що шукають дім — ' . $site_name;

            case 'animal_single':
                $animal = self::get_current_animal_data();
                if (!$animal) {
                    return $title;
                }
                $bits = array_filter([
                    $animal['name'],
                    Lapki_Main::get_animal_type_label($animal['type'], $animal['gender'] ?? ''),
                    $animal['address_city'] ?? '',
                ]);
                return implode(', ', $bits) . ' — шукає дім | ' . $site_name;

            case 'organizations_archive':
                return 'Притулки та організації — ' . $site_name;

            case 'organization_single':
                $organization = self::get_current_organization_data();
                return $organization ? $organization['name'] . ' | ' . $site_name : $title;

            case 'donate':
                return 'Підтримати грошима — ' . $site_name;

            case 'profile':
                return 'Особистий кабінет — ' . $site_name;

            case 'widget_demo':
                return 'Демонстрація embed-віджета — ' . $site_name;
        }

        return $title;
    }

    /**
     * Опис поточного route'у — спільний для <meta description> і og:description
     * /twitter:description, щоб не дублювати цю логіку в трьох місцях.
     */
    private static function get_page_description() {
        $page = get_query_var('lapki_page');

        switch ($page) {
            case 'animals_archive':
                return 'Пошук собак, котів та інших тварин з притулків України, які шукають дім.';

            case 'animal_single':
                $animal = self::get_current_animal_data();
                if (!$animal) {
                    return '';
                }
                return !empty($animal['description'])
                    ? wp_trim_words(wp_strip_all_tags($animal['description']), 30)
                    : sprintf("%s шукає дім. Дізнайтесь більше — можливо, саме ви станете новою родиною.", $animal['name']);

            case 'organizations_archive':
                return 'Притулки, ветклініки та волонтерські організації, які допомагають тваринам знайти дім.';

            case 'organization_single':
                $organization = self::get_current_organization_data();
                if (!$organization) {
                    return '';
                }
                return !empty($organization['mission_statement'])
                    ? wp_trim_words(wp_strip_all_tags($organization['mission_statement']), 30)
                    : sprintf('%s — притулок/організація на платформі Lapki.', $organization['name']);

            case 'donate':
                return 'Підтримайте притулок, волонтера або конкретну тварину — оберіть спосіб допомогти грошима.';
        }

        return '';
    }

    /**
     * <meta name="description"> для кастомних route'ів — на сайті немає
     * SEO-плагіна, а без цього тегу жодна сторінка його взагалі не має.
     */
    public static function output_meta_description() {
        $description = self::get_page_description();

        if ($description) {
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        }
    }

    /**
     * <link rel="canonical"> для кастомних route'ів — без цього WordPress
     * (rel_canonical(), який працює лише для справжніх singular/archive
     * об'єктів) взагалі не виводить canonical для цих сторінок. Критично
     * для дзеркала lapki.esiteq.com → без canonical кожна тварина/організація
     * індексувалась би як дублікат під двома доменами; home_url() завжди
     * резолвиться в canonical-домен (опція siteurl/home = lapki.help)
     * незалежно від Host-заголовка запиту.
     */
    public static function output_canonical_url() {
        $url = self::get_page_canonical_url();

        if ($url) {
            echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";
        }
    }

    /**
     * Канонічний URL поточного route'у — спільний для <link rel="canonical">
     * і og:url (обидва мають вказувати на те саме, canonical-домен lapki.help).
     */
    private static function get_page_canonical_url() {
        $page = get_query_var('lapki_page');

        switch ($page) {
            case 'animals_archive':
                return home_url('/animals/');

            case 'animal_single':
                $id = self::get_current_animal_id();
                return $id ? home_url('/animals/' . $id . '/') : '';

            case 'organizations_archive':
                return home_url('/organizations/');

            case 'organization_single':
                $id = self::get_current_organization_id();
                return $id ? home_url('/organizations/' . $id . '/') : '';

            case 'donate':
                return home_url('/donate/');
        }

        return '';
    }

    /**
     * Open Graph + Twitter Card — щоб посилання на тварину/організацію
     * красиво розгорталось у Facebook/Telegram/Viber/Twitter тощо: велике
     * фото, назва, короткий опис замість голого URL.
     */
    public static function output_open_graph_tags() {
        $page = get_query_var('lapki_page');

        if (!$page) {
            return;
        }

        $site_name = get_bloginfo('name');
        $url = self::get_page_canonical_url();
        $description = self::get_page_description();
        $title = '';
        $image = null; // ['url' => ..., 'width' => ..., 'height' => ..., 'alt' => ...]

        switch ($page) {
            case 'animals_archive':
                $title = 'Тварини, що шукають дім';
                break;

            case 'animal_single':
                $animal = self::get_current_animal_data();
                if (!$animal) {
                    return;
                }
                $bits = array_filter([
                    $animal['name'],
                    Lapki_Main::get_animal_type_label($animal['type'], $animal['gender'] ?? ''),
                    $animal['address_city'] ?? '',
                ]);
                $title = implode(', ', $bits) . ' — шукає дім';
                $image = self::get_entity_og_image($animal, $animal['name']);
                break;

            case 'organizations_archive':
                $title = 'Притулки та організації';
                break;

            case 'organization_single':
                $organization = self::get_current_organization_data();
                if (!$organization) {
                    return;
                }
                $title = $organization['name'];
                $image = self::get_entity_og_image($organization, $organization['name'], 'organization');
                break;

            case 'donate':
                $title = 'Підтримати грошима';
                break;

            default:
                return;
        }

        // Дефолтне зображення сайту, якщо у тварини/організації свого фото немає
        if (!$image) {
            $image = [
                'url' => set_url_scheme(get_template_directory_uri() . '/logo.png', 'https'),
                'width' => 800,
                'height' => 500,
                'alt' => $site_name,
            ];
        }

        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '">' . "\n";
        echo '<meta property="og:locale" content="uk_UA">' . "\n";
        if ($url) {
            echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
        }
        if ($title) {
            echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        }
        if ($description) {
            echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
        }
        if (!empty($image['url'])) {
            echo '<meta property="og:image" content="' . esc_url($image['url']) . '">' . "\n";
            echo '<meta property="og:image:secure_url" content="' . esc_url($image['url']) . '">' . "\n";
            if (!empty($image['width'])) {
                echo '<meta property="og:image:width" content="' . (int) $image['width'] . '">' . "\n";
            }
            if (!empty($image['height'])) {
                echo '<meta property="og:image:height" content="' . (int) $image['height'] . '">' . "\n";
            }
            echo '<meta property="og:image:alt" content="' . esc_attr($image['alt']) . '">' . "\n";
        }

        echo '<meta name="twitter:card" content="' . (!empty($image['url']) ? 'summary_large_image' : 'summary') . '">' . "\n";
        if ($title) {
            echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
        }
        if ($description) {
            echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
        }
        if (!empty($image['url'])) {
            echo '<meta name="twitter:image" content="' . esc_url($image['url']) . '">' . "\n";
        }
    }

    /**
     * Головне фото тварини/організації для og:image (завжди https — соцмережі
     * ігнорують http-зображення; siteurl сайту зараз http, тож примусово
     * підмінюємо схему через set_url_scheme()). Повертає null, якщо фото немає.
     */
    private static function get_entity_og_image($entity, $alt, $entity_type = 'animal') {
        if ($entity_type === 'animal') {
            $photos = !empty($entity['media']) ? array_values(array_filter($entity['media'], function ($m) {
                return $m['media_type'] === 'photo';
            })) : [];
            $photo = $photos[0] ?? null;
        } else {
            $photo = Lapki_Media::get_primary_photo($entity_type, $entity['id']);
        }

        if (empty($photo['url'])) {
            return null;
        }

        return [
            'url' => set_url_scheme($photo['url'], 'https'),
            'width' => $photo['width'] ?? null,
            'height' => $photo['height'] ?? null,
            'alt' => $alt,
        ];
    }

    /**
     * noindex для сторінок без цінності в пошуку: приватний кабінет
     * (анонімний краулер бачить лише запрошення увійти) і тестова
     * сторінка embed-віджета (навмисно не в меню).
     */
    public static function filter_wp_robots($robots) {
        $page = get_query_var('lapki_page');

        if (in_array($page, ['profile', 'widget_demo'], true)) {
            $robots['noindex'] = true;
        }

        return $robots;
    }

    /**
     * Архів автора (/author/{login}/) світить логін адміна й не несе
     * цінності для пошуку на не-блоговому сайті — редиректимо на головну.
     */
    public static function maybe_redirect_author_archive() {
        if (is_author()) {
            wp_safe_redirect(home_url('/'), 301);
            exit;
        }
    }
}
