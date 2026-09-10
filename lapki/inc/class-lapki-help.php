<?php

/**
 * Lapki Help
 *
 * CPT "lapki_help" (статті допомоги) + ієрархічна таксономія
 * "lapki_help_category" (категорії допомоги) — редагуються нативним
 * WP_List_Table/класичним редактором, підпунктами меню Lapki
 * (show_in_menu = 'lapki', той самий top-level слаг, що й
 * Lapki_Admin::add_admin_menu()).
 *
 * Фронтенд НЕ використовує нативний permalink CPT — публічні URL
 * (/help/, /help/{slug}/) реєструє Lapki_Frontend власним rewrite, як і
 * решта розділів плагіна (animals/organizations/blog). Тому тут
 * 'publicly_queryable' => false і 'rewrite' => false: WP не повинен сам
 * намагатись віддавати ці записи за нативним query/permalink — це заодно
 * автоматично виключає CPT з штатного wp-sitemap.xml
 * (is_post_type_viewable() дивиться саме на publicly_queryable для
 * не-вбудованих типів).
 *
 * @package Lapki
 * @author Oleksii Bugrov
 */

class Lapki_Help {

    const POST_TYPE = 'lapki_help';
    const TAXONOMY = 'lapki_help_category';

    public static function init() {
        add_action('init', [__CLASS__, 'register_post_type']);
        add_action('init', [__CLASS__, 'register_taxonomy']);

        // WP-ядро само нащує підпункт CPT під кастомним show_in_menu-слагом
        // (wp-includes/post.php::_add_post_type_submenus()), але НЕ робить
        // того самого для прив'язаної таксономії — той автонащ (wp-admin/menu.php)
        // працює лише коли show_in_menu === true (свій топ-левел), не для
        // рядка-слага. Тож пункт "Категорії допомоги" реєструємо вручну.
        add_action('admin_menu', [__CLASS__, 'add_admin_menu'], 20);
    }

    public static function register_post_type() {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('Статті допомоги', 'lapki'),
                'singular_name' => __('Стаття допомоги', 'lapki'),
                'add_new' => __('Додати статтю', 'lapki'),
                'add_new_item' => __('Нова стаття допомоги', 'lapki'),
                'edit_item' => __('Редагувати статтю допомоги', 'lapki'),
                'new_item' => __('Нова стаття', 'lapki'),
                'view_item' => __('Переглянути статтю', 'lapki'),
                'view_items' => __('Переглянути статті', 'lapki'),
                'search_items' => __('Пошук статей допомоги', 'lapki'),
                'not_found' => __('Статей не знайдено', 'lapki'),
                'not_found_in_trash' => __('У кошику статей не знайдено', 'lapki'),
                'all_items' => __('Статті допомоги', 'lapki'),
                'menu_name' => __('Статті допомоги', 'lapki'),
            ],
            'public' => true,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => 'lapki',
            'show_in_admin_bar' => false,
            'show_in_nav_menus' => false,
            'show_in_rest' => false, // класичний редактор (без блоків), як зафіксовано в завданні
            'rewrite' => false,
            'has_archive' => false,
            'query_var' => false,
            'hierarchical' => false,
            'supports' => ['title', 'editor', 'page-attributes'], // page-attributes — поле "Порядок" для ручного сортування статей у категорії
            'capability_type' => 'post',
        ]);
    }

    public static function register_taxonomy() {
        register_taxonomy(self::TAXONOMY, [self::POST_TYPE], [
            'labels' => [
                'name' => __('Категорії допомоги', 'lapki'),
                'singular_name' => __('Категорія допомоги', 'lapki'),
                'search_items' => __('Пошук категорій', 'lapki'),
                'all_items' => __('Усі категорії', 'lapki'),
                'parent_item' => __('Батьківська категорія', 'lapki'),
                'parent_item_colon' => __('Батьківська категорія:', 'lapki'),
                'edit_item' => __('Редагувати категорію', 'lapki'),
                'update_item' => __('Оновити категорію', 'lapki'),
                'add_new_item' => __('Додати нову категорію', 'lapki'),
                'new_item_name' => __('Назва нової категорії', 'lapki'),
                'menu_name' => __('Категорії допомоги', 'lapki'),
            ],
            'hierarchical' => true,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => false,
            'show_in_rest' => false,
            'query_var' => false,
            'rewrite' => false,
        ]);
    }

    public static function add_admin_menu() {
        $taxonomy = get_taxonomy(self::TAXONOMY);

        add_submenu_page(
            'lapki',
            __('Категорії допомоги', 'lapki'),
            __('Категорії допомоги', 'lapki'),
            $taxonomy->cap->manage_terms,
            'edit-tags.php?taxonomy=' . self::TAXONOMY . '&post_type=' . self::POST_TYPE
        );
    }

    /**
     * Стаття допомоги за слагом (для /help/{slug}/). Чернетку бачить лише
     * той, хто може її редагувати — той самий підхід, що й
     * Lapki_Frontend::get_current_about_post()/get_current_blog_post().
     */
    public static function get_post_by_slug($slug) {
        $slug = sanitize_title($slug);

        if (!$slug) {
            return null;
        }

        $query = new WP_Query([
            'name' => $slug,
            'post_type' => self::POST_TYPE,
            'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
        ]);
        $post = $query->have_posts() ? $query->posts[0] : null;
        wp_reset_postdata();

        if ($post && $post->post_status !== 'publish' && !current_user_can('edit_post', $post->ID)) {
            $post = null;
        }

        return $post;
    }

    /**
     * Публічний URL статті — спільний для сайдбара і будь-яких посилань на статтю.
     */
    public static function get_post_url($post) {
        return home_url('/help/' . $post->post_name . '/');
    }

    /**
     * Категорії (з опублікованими статтями) разом зі списком своїх статей —
     * основа сайдбара-змісту на /help/ і /help/{slug}/. Статті без жодної
     * категорії потрапляють в окрему групу "Без категорії" в кінці (а не
     * губляться мовчки).
     *
     * @return array Список ['term' => WP_Term|null, 'posts' => WP_Post[]]
     */
    public static function get_categories_with_posts() {
        $terms = get_terms([
            'taxonomy' => self::TAXONOMY,
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        if (is_wp_error($terms)) {
            $terms = [];
        }

        $groups = [];
        $seen_post_ids = [];

        foreach ($terms as $term) {
            $posts = get_posts([
                'post_type' => self::POST_TYPE,
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'orderby' => 'menu_order title',
                'order' => 'ASC',
                'tax_query' => [[
                    'taxonomy' => self::TAXONOMY,
                    'field' => 'term_id',
                    'terms' => $term->term_id,
                ]],
            ]);

            if (!$posts) {
                continue;
            }

            foreach ($posts as $post) {
                $seen_post_ids[$post->ID] = true;
            }

            $groups[] = ['term' => $term, 'posts' => $posts];
        }

        // Опубліковані статті без жодної категорії — окрема група в кінці
        $uncategorized = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'menu_order title',
            'order' => 'ASC',
            'tax_query' => [[
                'taxonomy' => self::TAXONOMY,
                'operator' => 'NOT EXISTS',
            ]],
        ]);
        $uncategorized = array_values(array_filter($uncategorized, function ($post) use ($seen_post_ids) {
            return empty($seen_post_ids[$post->ID]);
        }));

        if ($uncategorized) {
            $groups[] = ['term' => null, 'posts' => $uncategorized];
        }

        return $groups;
    }
}
