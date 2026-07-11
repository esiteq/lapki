<?php

/**
 * Lapki Template Loader
 *
 * Аналог wc_get_template() — спочатку шукає шаблон в активній темі
 * (child → parent) у теці "lapki/", і лише якщо не знайшов —
 * підвантажує дефолтний шаблон з плагіна (templates/).
 *
 * Тема може перевизначити будь-який шаблон, скопіювавши файл у
 * wp-content/themes/{тема}/lapki/{ім'я-файлу}.
 *
 * @package Lapki
 * @author Oleksii Bugrov
 */

class Lapki_Template_Loader {

    /**
     * Знайти шлях до шаблону: тема → плагін
     *
     * @param string $template_name Наприклад, 'single-animal.php'
     * @return string Абсолютний шлях або '' якщо не знайдено
     */
    public static function locate($template_name) {
        $template_name = ltrim($template_name, '/');

        $theme_file = locate_template([
            'lapki/' . $template_name,
        ]);

        if ($theme_file) {
            return $theme_file;
        }

        $plugin_file = LAPKI_PLUGIN_DIR . 'templates/' . $template_name;

        if (file_exists($plugin_file)) {
            return $plugin_file;
        }

        return '';
    }
}
