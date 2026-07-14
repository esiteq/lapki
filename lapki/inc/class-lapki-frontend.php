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
                $animal = Lapki_Animal::get(self::get_current_animal_id());
                if (!$animal) {
                    return $title;
                }
                $type_labels = ['dog' => 'собака', 'cat' => 'кіт', 'bird' => 'птах', 'rabbit' => 'кролик'];
                $bits = array_filter([
                    $animal['name'],
                    $type_labels[$animal['type']] ?? '',
                    $animal['address_city'] ?? '',
                ]);
                return implode(', ', $bits) . ' — шукає дім | ' . $site_name;

            case 'organizations_archive':
                return 'Притулки та організації — ' . $site_name;

            case 'organization_single':
                $organization = Lapki_Organization::get(self::get_current_organization_id());
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
     * <meta name="description"> для кастомних route'ів — на сайті немає
     * SEO-плагіна, а без цього тегу жодна сторінка його взагалі не має.
     */
    public static function output_meta_description() {
        $page = get_query_var('lapki_page');
        $description = '';

        switch ($page) {
            case 'animals_archive':
                $description = 'Пошук собак, котів та інших тварин з притулків України, які шукають дім.';
                break;

            case 'animal_single':
                $animal = Lapki_Animal::get(self::get_current_animal_id());
                if ($animal) {
                    $description = !empty($animal['description'])
                        ? wp_trim_words(wp_strip_all_tags($animal['description']), 30)
                        : sprintf("%s шукає дім. Дізнайтесь більше — можливо, саме ви станете новою родиною.", $animal['name']);
                }
                break;

            case 'organizations_archive':
                $description = 'Притулки, ветклініки та волонтерські організації, які допомагають тваринам знайти дім.';
                break;

            case 'organization_single':
                $organization = Lapki_Organization::get(self::get_current_organization_id());
                if ($organization) {
                    $description = !empty($organization['mission_statement'])
                        ? wp_trim_words(wp_strip_all_tags($organization['mission_statement']), 30)
                        : sprintf('%s — притулок/організація на платформі Lapki.', $organization['name']);
                }
                break;

            case 'donate':
                $description = 'Підтримайте притулок, волонтера або конкретну тварину — оберіть спосіб допомогти грошима.';
                break;
        }

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
        $page = get_query_var('lapki_page');
        $url = '';

        switch ($page) {
            case 'animals_archive':
                $url = home_url('/animals/');
                break;

            case 'animal_single':
                $id = self::get_current_animal_id();
                $url = $id ? home_url('/animals/' . $id . '/') : '';
                break;

            case 'organizations_archive':
                $url = home_url('/organizations/');
                break;

            case 'organization_single':
                $id = self::get_current_organization_id();
                $url = $id ? home_url('/organizations/' . $id . '/') : '';
                break;

            case 'donate':
                $url = home_url('/donate/');
                break;
        }

        if ($url) {
            echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";
        }
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
