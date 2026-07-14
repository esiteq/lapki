<?php

/**
 * Lapki Sitemap Providers
 *
 * Успадковують WP_Sitemaps_Provider — вимагається лише лениво з
 * Lapki_Sitemaps::register_providers() (на хук wp_sitemaps_init), коли
 * базовий клас гарантовано вже підвантажений ядром WordPress.
 *
 * @package Lapki
 * @author Oleksii Bugrov
 */

class Lapki_Sitemaps_Animals_Provider extends WP_Sitemaps_Provider {

    public function __construct() {
        // WP-ядро матчить сегмент назви в URL сайтмапу регексом [a-z]+ (без
        // дефісів/цифр/підкреслень) — з дефісом ("lapki-animals") rewrite
        // rule WordPress просто не спрацьовує, і URL віддає 404/дефолтну сторінку.
        $this->name = 'lapkianimals';
        $this->object_type = 'lapki_animal';
    }

    public function get_url_list($page_num, $object_subtype = '') {
        $per_page = wp_sitemaps_get_max_urls($this->object_type);

        $animals = Lapki_Animal::search([
            'limit' => $per_page,
            'offset' => ($page_num - 1) * $per_page,
            'order_by' => 'updated_at',
            'order' => 'DESC',
        ]);

        $url_list = [];
        foreach ($animals as $animal) {
            $entry = ['loc' => home_url('/animals/' . (int) $animal['id'] . '/')];
            if (!empty($animal['updated_at'])) {
                $entry['lastmod'] = mysql2date('c', $animal['updated_at'], false);
            }
            $url_list[] = $entry;
        }

        return $url_list;
    }

    public function get_max_num_pages($object_subtype = '') {
        $per_page = wp_sitemaps_get_max_urls($this->object_type);
        $total = Lapki_Animal::count();

        return (int) max(1, ceil($total / $per_page));
    }
}

class Lapki_Sitemaps_Organizations_Provider extends WP_Sitemaps_Provider {

    public function __construct() {
        $this->name = 'lapkiorganizations';
        $this->object_type = 'lapki_organization';
    }

    public function get_url_list($page_num, $object_subtype = '') {
        $per_page = wp_sitemaps_get_max_urls($this->object_type);

        $organizations = Lapki_Organization::search([
            'limit' => $per_page,
            'offset' => ($page_num - 1) * $per_page,
        ]);

        $url_list = [];
        foreach ($organizations as $organization) {
            $entry = ['loc' => home_url('/organizations/' . (int) $organization['id'] . '/')];
            if (!empty($organization['updated_at'])) {
                $entry['lastmod'] = mysql2date('c', $organization['updated_at'], false);
            }
            $url_list[] = $entry;
        }

        return $url_list;
    }

    public function get_max_num_pages($object_subtype = '') {
        $per_page = wp_sitemaps_get_max_urls($this->object_type);
        $total = count(Lapki_Organization::search(['limit' => 100000]));

        return (int) max(1, ceil($total / $per_page));
    }
}
