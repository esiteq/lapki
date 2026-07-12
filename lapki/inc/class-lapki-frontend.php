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
    }

    public static function add_rewrite_rules() {
        add_rewrite_rule('^animals/?$', 'index.php?lapki_page=animals_archive', 'top');
        add_rewrite_rule('^animals/([0-9]+)/?$', 'index.php?lapki_page=animal_single&lapki_animal_id=$matches[1]', 'top');
        add_rewrite_rule('^organizations/?$', 'index.php?lapki_page=organizations_archive', 'top');
        add_rewrite_rule('^organizations/([0-9]+)/?$', 'index.php?lapki_page=organization_single&lapki_org_id=$matches[1]', 'top');
        add_rewrite_rule('^widget-demo/?$', 'index.php?lapki_page=widget_demo', 'top');
        add_rewrite_rule('^profile/?$', 'index.php?lapki_page=profile', 'top');
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
}
