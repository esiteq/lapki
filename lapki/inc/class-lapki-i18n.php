<?php
/**
 * Перемикач мови UA/EN.
 *
 * Джерело правди про поточну мову — Lapki_I18n::get_lang(): кука lapki_lang,
 * однаково для залогінених і анонімних відвідувачів (не user_meta 'locale' —
 * свідомо, щоб перемикання мови на фронтенді не чіпало профіль користувача
 * і працювало для анонімів так само, як і для залогінених).
 *
 * За замовчуванням (нічого не обрано) — українська, бо весь контент платформи
 * спершу написаний українською.
 *
 * Технічно: з WP 6.7 переклади завантажуються "just-in-time" через
 * determine_locale() — старий фільтр 'plugin_locale' (який раніше визначав,
 * який .mo вантажити для load_plugin_textdomain()) більше НЕ застосовується
 * цим механізмом. Тому основний гачок тут — фільтр 'determine_locale',
 * який підміняє мову для всього запиту одразу (ядро + плагін + тема —
 * всі під тим самим доменом 'lapki', аби не плодити другий комплект .po/.mo
 * для тісно зв'язаної власної теми). 'plugin_locale' лишається для
 * зворотної сумісності зі старішими версіями WordPress.
 *
 * @package Lapki
 */

if (!defined('ABSPATH')) {
    exit;
}

class Lapki_I18n {

    const COOKIE_NAME   = 'lapki_lang';
    const SUPPORTED_LANGS = ['uk', 'en'];
    const DEFAULT_LANG  = 'uk';

    /**
     * Сторінки-винятки з окремим URL на кожну мову (замість спільного URL +
     * глобального перемикача) — окремий WP-запис (post_id) на кожну мову,
     * як у WPML. Мова тут визначається шляхом (URL), а не кукою/user_meta:
     * заходиш на /en/about/ — бачиш англійську версію незалежно від того,
     * яка мова обрана в решті сайту. Щоб додати ще одну таку сторінку —
     * достатньо дописати рядок сюди (без змін у Lapki_Frontend).
     */
    const URL_LANG_PAIRS = [
        'about' => ['uk' => '/about/', 'en' => '/en/about/'],
    ];

    /**
     * Те саме, але для розділів із динамічним сегментом у шляху (наразі —
     * записи блогу: /blog/{slug}/ і /en/blog/{slug}/, включно з самим
     * архівом /blog/ і /en/blog/) — мова визначається ПРЕФІКСОМ шляху, а не
     * точним співпадінням. На відміну від URL_LANG_PAIRS, тут немає
     * систематичного зв'язку між uk- і en-записом (різні post_id, різні
     * слаги — див. CHANGELOG сесія 91), тож перемикач мови на такій
     * сторінці веде не на "той самий запис іншою мовою", а на архів
     * блогу відповідної мови (switch_url() нижче).
     */
    const URL_LANG_PREFIXES = [
        'blog' => ['uk' => '/blog/', 'en' => '/en/blog/'],
    ];

    /**
     * Якщо поточний запит підпадає під один з URL_LANG_PAIRS — повернути
     * ['key' => <ключ пари>, 'lang' => 'uk'/'en'], інакше null.
     */
    private static function match_url_lang_pair() {
        $path = self::get_current_path();
        if (!$path) {
            return null;
        }

        foreach (self::URL_LANG_PAIRS as $key => $urls) {
            foreach ($urls as $lang => $url) {
                if ($path === untrailingslashit($url) . '/') {
                    return ['key' => $key, 'lang' => $lang];
                }
            }
        }

        return null;
    }

    /**
     * Те саме для URL_LANG_PREFIXES — порівняння за префіксом шляху, не за
     * точним співпадінням (бо далі йде динамічний слаг запису).
     */
    private static function match_url_lang_prefix() {
        $path = self::get_current_path();
        if (!$path) {
            return null;
        }

        foreach (self::URL_LANG_PREFIXES as $key => $urls) {
            // Спершу en — його префікс довший і не є підрядком uk-префікса,
            // але порядок про всяк випадок: найдовший/найспецифічніший перший.
            uasort($urls, function ($a, $b) {
                return strlen($b) <=> strlen($a);
            });
            foreach ($urls as $lang => $prefix) {
                if (strpos($path, $prefix) === 0) {
                    return ['key' => $key, 'lang' => $lang];
                }
            }
        }

        return null;
    }

    /**
     * Шлях поточного запиту з кінцевим слешем (спільна нормалізація для
     * match_url_lang_pair()/match_url_lang_prefix()).
     */
    private static function get_current_path() {
        $path = isset($_SERVER['REQUEST_URI']) ? wp_parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
        return $path ? untrailingslashit($path) . '/' : '';
    }

    public static function init() {
        // WP 6.7+: реальний механізм, яким визначається мова just-in-time
        // завантаження перекладів (для домену 'lapki' і для ядра заразом).
        add_filter('determine_locale', [__CLASS__, 'filter_determine_locale'], 20);

        // Старіші версії WordPress (до 6.7) — locale саме для нашого домену.
        add_filter('plugin_locale', [__CLASS__, 'filter_plugin_locale'], 10, 2);

        // Обробка перемикання — максимально рано на init (до setup() плагіна,
        // який на цьому ж хуку завантажує textdomain).
        add_action('init', [__CLASS__, 'maybe_handle_switch'], 1);

        // Перемикач у адмін-барі WordPress (видно і в /wp-admin/, і на фронтенді
        // для залогінених користувачів).
        add_action('admin_bar_menu', [__CLASS__, 'add_admin_bar_switcher'], 100);
    }

    /**
     * Фільтр 'determine_locale' (WP 5.0+, а з 6.7 — саме те, що читає
     * just-in-time завантажувач перекладів). Підміняє мову всього запиту.
     */
    public static function filter_determine_locale($locale) {
        return self::locale_for_lang(self::get_lang());
    }

    /**
     * Фільтр 'plugin_locale' — для WordPress до 6.7, де саме він визначав,
     * який .mo вантажити для textdomain 'lapki' через load_plugin_textdomain().
     */
    public static function filter_plugin_locale($locale, $domain) {
        if ($domain !== 'lapki') {
            return $locale;
        }
        return self::locale_for_lang(self::get_lang());
    }

    /**
     * Коротка мова ('uk'/'en') -> WP locale-рядок файлу перекладу.
     */
    public static function locale_for_lang($lang) {
        return $lang === 'en' ? 'en_US' : 'uk';
    }

    /**
     * ?lapki_lang=uk|en у будь-якому запиті — зберігає вибір і редіректить
     * на той самий URL без цього параметра.
     */
    public static function maybe_handle_switch() {
        if (empty($_GET['lapki_lang'])) {
            return;
        }

        $lang = sanitize_key(wp_unslash($_GET['lapki_lang']));
        if (!in_array($lang, self::SUPPORTED_LANGS, true)) {
            return;
        }

        if (!headers_sent()) {
            setcookie(
                self::COOKIE_NAME,
                $lang,
                time() + YEAR_IN_SECONDS,
                defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/',
                defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : ''
            );
        }
        $_COOKIE[self::COOKIE_NAME] = $lang;

        $redirect = remove_query_arg('lapki_lang');
        wp_safe_redirect($redirect ? $redirect : home_url('/'));
        exit;
    }

    /**
     * Поточна мова інтерфейсу: 'uk' або 'en'.
     */
    public static function get_lang() {
        // URL-сторінки з окремим постом на мову (URL_LANG_PAIRS) — мова
        // визначається шляхом, а не кукою/user_meta, і має пріоритет.
        $match = self::match_url_lang_pair();
        if ($match !== null) {
            return $match['lang'];
        }

        // Розділи з динамічним сегментом у шляху (URL_LANG_PREFIXES, /blog/…) —
        // той самий пріоритет: /en/blog/будь-що завжди англійською, незалежно
        // від куки (критично для пошукових ботів, які кук не шлють — без цього
        // Googlebot побачив би /en/blog/... українською).
        $prefix_match = self::match_url_lang_prefix();
        if ($prefix_match !== null) {
            return $prefix_match['lang'];
        }

        if (isset($_COOKIE[self::COOKIE_NAME]) && in_array($_COOKIE[self::COOKIE_NAME], self::SUPPORTED_LANGS, true)) {
            return $_COOKIE[self::COOKIE_NAME];
        }

        return self::DEFAULT_LANG;
    }

    /** Короткий код поточної мови для довідника атрибутів ('uk'/'en'). */
    public static function get_attr_lang() {
        return self::get_lang();
    }

    /**
     * URL для перемикання на вказану мову з поточної сторінки.
     */
    public static function switch_url($lang) {
        // На сторінці з окремим URL на мову (URL_LANG_PAIRS) перемикач має
        // вести на URL сусідньої мовної версії (інший post_id), а не додавати
        // ?lapki_lang= до поточного — це не той самий запис.
        $match = self::match_url_lang_pair();
        if ($match !== null && isset(self::URL_LANG_PAIRS[$match['key']][$lang])) {
            return esc_url(home_url(self::URL_LANG_PAIRS[$match['key']][$lang]));
        }

        // Розділи з динамічним сегментом (URL_LANG_PREFIXES, /blog/…) —
        // uk- і en-запис у загальному випадку не пов'язані (різні post_id),
        // тож дефолт — перемикач веде на префікс (архів розділу) відповідної
        // мови. Але якщо для конкретного запису явно вказано пару-переклад
        // (Lapki_Admin: метабокс "Пов'язаний запис", postmeta
        // 'lapki_blog_pair_id') — фільтр нижче (Lapki_Frontend) підміняє це
        // на URL саме того пов'язаного запису.
        $prefix_match = self::match_url_lang_prefix();
        if ($prefix_match !== null && isset(self::URL_LANG_PREFIXES[$prefix_match['key']][$lang])) {
            $default_url = home_url(self::URL_LANG_PREFIXES[$prefix_match['key']][$lang]);
            $url = apply_filters('lapki_i18n_prefix_switch_url', $default_url, $prefix_match['key'], $lang);
            return esc_url($url);
        }

        return esc_url(add_query_arg('lapki_lang', $lang));
    }

    /**
     * HTML-розмітка перемикача UA/EN для фронтенду (шапка теми).
     */
    public static function render_switcher() {
        $current = self::get_lang();
        ob_start();
        ?>
        <div class="lapki-lang-switch" role="group" aria-label="<?php esc_attr_e('Мова сайту', 'lapki'); ?>">
            <a href="<?php echo self::switch_url('uk'); ?>" class="lapki-lang-switch__item<?php echo $current === 'uk' ? ' is-active' : ''; ?>">UA</a>
            <span class="lapki-lang-switch__sep">/</span>
            <a href="<?php echo self::switch_url('en'); ?>" class="lapki-lang-switch__item<?php echo $current === 'en' ? ' is-active' : ''; ?>">EN</a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Пункт перемикача мови в адмін-барі WordPress.
     */
    public static function add_admin_bar_switcher($wp_admin_bar) {
        if (!is_user_logged_in()) {
            return;
        }

        $current = self::get_lang();

        $wp_admin_bar->add_node([
            'id'    => 'lapki-lang',
            'title' => 'Lapki: ' . strtoupper($current),
            'href'  => false,
        ]);

        $wp_admin_bar->add_node([
            'id'     => 'lapki-lang-uk',
            'parent' => 'lapki-lang',
            'title'  => ($current === 'uk' ? '&#10003; ' : '') . 'Українська',
            'href'   => self::switch_url('uk'),
        ]);

        $wp_admin_bar->add_node([
            'id'     => 'lapki-lang-en',
            'parent' => 'lapki-lang',
            'title'  => ($current === 'en' ? '&#10003; ' : '') . 'English',
            'href'   => self::switch_url('en'),
        ]);
    }
}
