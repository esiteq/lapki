<?php

/**
 * Lapki Sitemaps
 *
 * Тварини й організації — окремі таблиці БД, не WP custom post type, тож
 * стандартний wp-sitemap.xml їх узагалі не бачить. Реєструє два додаткові
 * XML-сайтмапи (wp-sitemap-lapkianimals-N.xml, wp-sitemap-lapkiorganizations-N.xml)
 * через штатний WP_Sitemaps_Provider — з'являються прямо в індексі wp-sitemap.xml.
 *
 * @package Lapki
 * @author Oleksii Bugrov
 */

class Lapki_Sitemaps {

    public static function init() {
        add_action('wp_sitemaps_init', [__CLASS__, 'register_providers']);
    }

    /**
     * Класи-провайдери успадковують WP_Sitemaps_Provider, який WordPress
     * підвантажує лише на хук init (через wp_sitemaps_get_server(), що й
     * ініціює wp_sitemaps_init) — тож файл з класами вимагаємо саме тут,
     * а не одразу при завантаженні плагіна, інакше буде fatal error
     * "Class WP_Sitemaps_Provider not found".
     */
    public static function register_providers() {
        require_once LAPKI_PLUGIN_DIR . 'inc/class-lapki-sitemaps-providers.php';

        // Ключ реєстрації МАЄ збігатись з провайдеровим $this->name — саме
        // за цим ключем WP_Sitemaps::render_sitemaps() шукає провайдера за
        // значенням query var `sitemap`, узятим із самого URL.
        wp_register_sitemap_provider('lapkianimals', new Lapki_Sitemaps_Animals_Provider());
        wp_register_sitemap_provider('lapkiorganizations', new Lapki_Sitemaps_Organizations_Provider());
    }
}
