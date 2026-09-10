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
        add_action('wp_head', [__CLASS__, 'output_hreflang_alternates'], 1);
        add_filter('wp_robots', [__CLASS__, 'filter_wp_robots']);

        // Перемикач мови на конкретному записі блогу (/blog/{slug}/) — якщо
        // для нього вказано пару-переклад (Lapki_Admin: метабокс "Пов'язаний
        // запис"), веде саме на неї, а не на архів блогу (дефолт у Lapki_I18n).
        add_filter('lapki_i18n_prefix_switch_url', [__CLASS__, 'filter_blog_switch_url'], 10, 3);

        // Фонове фото головного банера (.lapki-hero) з Lapki → Налаштування.
        // Пріоритет 20 — після друку стилів теми (wp_head, пріоритет 8), щоб
        // цей селектор перекрив дефолтний градієнт без !important.
        add_action('wp_head', [__CLASS__, 'output_hero_background_css'], 20);

        // Приховати архів автора (світить логін адміна, немає цінності для пошуку)
        add_action('template_redirect', [__CLASS__, 'maybe_redirect_author_archive']);

        // Записи блогу (post_type=post) читаються лише через /blog/{slug}/
        // (uk) або /en/blog/{slug}/ (en) — нативний permalink
        // (/%year%/%monthnum%/%day%/%postname%/, теж валідний і
        // зареєстрований WP-ядром) веде на 301 сюди, щоб не було двох URL
        // для того самого запису (дубль для пошукових систем) і щоб сторінка
        // завжди рендерилась стилізованим single-blog.php, а не "голим"
        // index.php теми (немає власного single.php).
        add_action('template_redirect', [__CLASS__, 'maybe_redirect_native_post_permalink']);

        // /add-animal/ і /add-organization/ доступні лише залогіненим —
        // анонімів на /signup/
        add_action('template_redirect', [__CLASS__, 'maybe_redirect_add_pages_if_logged_out']);

        // /integration/lapki.js — WP-канонічний редирект намагається дописати
        // трейлінг-слеш (бо не впізнає .js як "справжній" файл при цій
        // rewrite-структурі) — це зламало б будь-який сторонній <script src>,
        // вставлений БЕЗ слеша (а саме так і виглядає код у конструкторі)
        add_filter('redirect_canonical', [__CLASS__, 'skip_canonical_redirect_for_integration_js']);

        // Приховати стандартний адмінбар WP зверху фронтенду для звичайних
        // користувачів (власники притулків/волонтери) — платформа працює
        // через власний фронтенд-кабінет (/profile/), адмінбар їм не
        // потрібен і візуально заважає темі. Лишається для сайт-адмінів.
        add_filter('show_admin_bar', [__CLASS__, 'maybe_hide_admin_bar']);
    }

    /**
     * Якщо адмін задав фонове фото банера (Lapki → Налаштування → "Фонове фото
     * головного банера"), перекриваємо .lapki-hero інлайновим стилем — саме фото
     * на всю ширину, без градієнта/оверлея (за вимогою). Лише на головній.
     */
    public static function output_hero_background_css() {
        if (!is_front_page()) {
            return;
        }

        $image_id = (int) get_option('lapki_hero_bg_image_id', 0);
        if (!$image_id) {
            return;
        }

        $url = wp_get_attachment_image_url($image_id, 'full');
        if (!$url) {
            return;
        }

        printf(
            '<style id="lapki-hero-bg-css">.lapki-hero{background:var(--lapki-hero-to,#1c8a4d) url(\'%s\') center center / cover no-repeat;}</style>' . "\n",
            esc_url($url)
        );
    }

    public static function maybe_hide_admin_bar($show) {
        if (is_admin()) {
            return $show;
        }

        return current_user_can('manage_options');
    }

    public static function add_rewrite_rules() {
        add_rewrite_rule('^animals/?$', 'index.php?lapki_page=animals_archive', 'top');
        add_rewrite_rule('^animals/([0-9]+)/?$', 'index.php?lapki_page=animal_single&lapki_animal_id=$matches[1]', 'top');
        add_rewrite_rule('^organizations/?$', 'index.php?lapki_page=organizations_archive', 'top');
        add_rewrite_rule('^organizations/([0-9]+)/?$', 'index.php?lapki_page=organization_single&lapki_org_id=$matches[1]', 'top');
        add_rewrite_rule('^widget-demo/?$', 'index.php?lapki_page=widget_demo', 'top');
        add_rewrite_rule('^test-page/?$', 'index.php?lapki_page=test_page', 'top');
        add_rewrite_rule('^profile/?$', 'index.php?lapki_page=profile', 'top');
        add_rewrite_rule('^donate/?$', 'index.php?lapki_page=donate', 'top');
        add_rewrite_rule('^add-animal/?$', 'index.php?lapki_page=add_animal', 'top');
        add_rewrite_rule('^add-organization/?$', 'index.php?lapki_page=add_organization', 'top');
        add_rewrite_rule('^edit-profile/?$', 'index.php?lapki_page=edit_profile', 'top');

        // Блог — звичайні WP-записи (post_type=post), не нативний permalink
        // (/%year%/%monthnum%/%day%/%postname%/), а власний URL зі слагом
        // під мовним префіксом (/blog/{slug}/ — uk, /en/blog/{slug}/ — en;
        // для SEO — Google має бачити мову прямо в URL, а не лише в
        // куці/cookie). uk- і en-версія "того самого" запису в блозі — два
        // ОКРЕМІ WP-записи (різні post_id, розрізняються через postmeta
        // 'lang', див. Lapki_Admin::add_blog_lang_meta_box()), тому колізій
        // слагів між мовами немає — кожен префікс своя мовна "теки".
        // Мова тут же й визначає Lapki_I18n::get_lang() для всього запиту
        // (URL_LANG_PREFIXES) — важливо для пошукових ботів без кук.
        add_rewrite_rule('^blog/?$', 'index.php?lapki_page=blog_archive&lapki_blog_lang=uk', 'top');
        add_rewrite_rule('^en/blog/?$', 'index.php?lapki_page=blog_archive&lapki_blog_lang=en', 'top');
        // Пагінація архіву — /blog/page/2/, не ?paged=2: WP-ядро (redirect_canonical(),
        // спираючись на глобальну permalink_structure /blog/%postname%/, сесія 93)
        // все одно 301-редіректило б query-string-варіант сюди, тож краще одразу
        // віддавати правильну rewrite-адресу, а не боротись з редиректом.
        add_rewrite_rule('^blog/page/([0-9]+)/?$', 'index.php?lapki_page=blog_archive&lapki_blog_lang=uk&paged=$matches[1]', 'top');
        add_rewrite_rule('^en/blog/page/([0-9]+)/?$', 'index.php?lapki_page=blog_archive&lapki_blog_lang=en&paged=$matches[1]', 'top');
        add_rewrite_rule('^blog/([^/]+)/?$', 'index.php?lapki_page=blog_single&lapki_blog_lang=uk&lapki_blog_slug=$matches[1]', 'top');
        add_rewrite_rule('^en/blog/([^/]+)/?$', 'index.php?lapki_page=blog_single&lapki_blog_lang=en&lapki_blog_slug=$matches[1]', 'top');

        // "Про нас" — окремий WP-запис (post_id) на кожну мову, як у WPML,
        // замість спільного URL з глобальним перемикачем (див. Lapki_I18n::URL_LANG_PAIRS)
        add_rewrite_rule('^about/?$', 'index.php?lapki_page=about&lapki_about_lang=uk', 'top');
        add_rewrite_rule('^en/about/?$', 'index.php?lapki_page=about&lapki_about_lang=en', 'top');

        // Embed-віджет пошуку тварин для стороннього сайту: <script src=".../integration/lapki.js?organization_id=1">
        // (окремо від /js/animals.js — той лежить поза плагіном, поза git;
        // цей рендериться через WP-роутинг, тож лишається в git разом з рештою плагіна)
        add_rewrite_rule('^integration/lapki\.js$', 'index.php?lapki_page=integration_widget', 'top');

        // Розділ "Допомога" (/help/) — CPT lapki_help, але власний rewrite
        // (не нативний permalink CPT), той самий підхід, що й в усього
        // іншого фронтенду плагіна. Див. Lapki_Help — 'publicly_queryable' => false.
        add_rewrite_rule('^help/?$', 'index.php?lapki_page=help_archive', 'top');
        add_rewrite_rule('^help/([^/]+)/?$', 'index.php?lapki_page=help_single&lapki_help_slug=$matches[1]', 'top');
    }

    /**
     * /add-animal/ і /add-organization/ вимагають акаунт (щоб з користувачем
     * можна було зв'язатися) — анонімного відвідувача редіректимо на форму
     * реєстрації/логіну з поясненням і поверненням сюди після входу.
     */
    public static function maybe_redirect_add_pages_if_logged_out() {
        $paths = [
            'add_animal' => '/add-animal/',
            'add_organization' => '/add-organization/',
            'edit_profile' => '/edit-profile/',
        ];
        $page = get_query_var('lapki_page');

        if (!isset($paths[$page]) || is_user_logged_in()) {
            return;
        }

        $url = add_query_arg([
            'context' => str_replace('_', '-', $page),
            'redirect' => rawurlencode($paths[$page]),
        ], home_url('/signup/'));

        wp_safe_redirect($url);
        exit;
    }

    public static function add_query_vars($vars) {
        $vars[] = 'lapki_page';
        $vars[] = 'lapki_animal_id';
        $vars[] = 'lapki_org_id';
        $vars[] = 'lapki_about_lang';
        $vars[] = 'lapki_blog_lang';
        $vars[] = 'lapki_blog_slug';
        $vars[] = 'lapki_help_slug';
        return $vars;
    }

    public static function template_include($template) {
        $page = get_query_var('lapki_page');

        if (!$page) {
            return $template;
        }

        // Ці два не рендерять WP-шаблон/тему взагалі (JS-файл і гола HTML-сторінка
        // без header()/footer()) — самі формують відповідь і завершують запит
        if ($page === 'integration_widget') {
            self::serve_integration_widget_js();
        }

        $map = [
            'animals_archive' => 'archive-animals.php',
            'animal_single' => 'single-animal.php',
            'organizations_archive' => 'archive-organizations.php',
            'organization_single' => 'single-organization.php',
            'widget_demo' => 'widget-demo.php',
            'test_page' => 'test-page.php',
            'profile' => 'profile.php',
            'donate' => 'donate.php',
            'add_animal' => 'add-animal.php',
            'add_organization' => 'add-organization.php',
            'edit_profile' => 'edit-profile.php',
            'about' => 'about.php',
            'blog_archive' => 'blog.php',
            'blog_single' => 'single-blog.php',
            'help_archive' => 'archive-help.php',
            'help_single' => 'single-help.php',
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
     * redirect_canonical спрацьовує на template_redirect — раніше, ніж наш
     * template_include — тож саме тут (не пізніше) треба скасувати редирект.
     */
    public static function skip_canonical_redirect_for_integration_js($redirect_url) {
        if (get_query_var('lapki_page') === 'integration_widget') {
            return false;
        }
        return $redirect_url;
    }

    /**
     * Віддає JS-файл embed-віджета пошуку тварин (integration.js) як
     * application/javascript — без завантаження теми, без header()/footer().
     * Контент статичний (organization_id читається клієнтським JS з рядка
     * запиту власного <script src>, як і в /js/animals.js), тож досить
     * просто віддати вміст файлу з плагіна з коротким кешем.
     */
    private static function serve_integration_widget_js() {
        status_header(200);
        header('Content-Type: application/javascript; charset=UTF-8');
        header('Cache-Control: public, max-age=300');
        $path = LAPKI_PLUGIN_DIR . 'js/lapki-integration.js';
        if (file_exists($path)) {
            readfile($path);
        }
        exit;
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
     * Мова блогу для поточного запиту — з рядка rewrite-правила
     * (/blog/… → uk, /en/blog/… → en), а не з куки/Lapki_I18n::get_lang()
     * (та сама тепер теж читає це через URL_LANG_PREFIXES, але тут — пряме
     * джерело, без залежності від порядку хуків).
     */
    public static function get_current_blog_lang() {
        return get_query_var('lapki_blog_lang') === 'en' ? 'en' : 'uk';
    }

    /**
     * WP-запис блогу за слагом + мовним префіксом поточного запиту
     * (/blog/{slug}/ або /en/blog/{slug}/). Слаг сам по собі неунікальний
     * між мовами (uk/en — окремі записи, різні post_id), тож збіг слага БЕЗ
     * збігу postmeta 'lang' із префіксом шляху не рахується — інакше
     * /en/blog/{uk-слаг}/ показував би український запис під англійським
     * URL. Чернетку (post_status=draft) бачить лише той, хто може її
     * редагувати — так само, як "Про нас".
     */
    public static function get_current_blog_post() {
        static $cache = [];
        $slug = sanitize_title(get_query_var('lapki_blog_slug'));
        $lang = self::get_current_blog_lang();

        if (!$slug) {
            return null;
        }

        $cache_key = $lang . ':' . $slug;
        if (array_key_exists($cache_key, $cache)) {
            return $cache[$cache_key];
        }

        $query = new WP_Query([
            'name' => $slug,
            'post_type' => 'post',
            'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
        ]);
        $post = $query->have_posts() ? $query->posts[0] : null;
        wp_reset_postdata();

        if ($post) {
            $post_lang = get_post_meta($post->ID, 'lang', true) === 'en' ? 'en' : 'uk';
            if ($post_lang !== $lang) {
                $post = null;
            } elseif ($post->post_status !== 'publish' && !current_user_can('edit_post', $post->ID)) {
                $post = null;
            }
        }

        return $cache[$cache_key] = $post;
    }

    /**
     * URL запису блогу з правильним мовним префіксом (postmeta 'lang') —
     * спільна для templates/blog.php (посилання карток) і редиректу з
     * нативного permalink нижче.
     */
    public static function get_blog_post_url($post) {
        $lang = get_post_meta($post->ID, 'lang', true) === 'en' ? 'en' : 'uk';
        $prefix = $lang === 'en' ? '/en/blog/' : '/blog/';
        return home_url($prefix . $post->post_name . '/');
    }

    /**
     * Наступний запис блогу (тієї ж мови) — той, що йде одразу за поточним
     * у стрічці архіву /blog/ (ORDER BY post_date DESC, ID ASC — той самий
     * тайбрейк, що й templates/blog.php). "Наступний" тут — старіший за
     * датою пост (або той самий день, більший ID), не хронологічно новіший.
     * null, якщо поточний запис останній у стрічці.
     */
    public static function get_next_blog_post($post) {
        global $wpdb;

        $lang = get_post_meta($post->ID, 'lang', true) === 'en' ? 'en' : 'uk';

        $next_id = $wpdb->get_var($wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = 'lang' AND pm.meta_value = %s
             WHERE p.post_type = 'post' AND p.post_status = 'publish'
             AND (p.post_date < %s OR (p.post_date = %s AND p.ID > %d))
             ORDER BY p.post_date DESC, p.ID ASC
             LIMIT 1",
            $lang,
            $post->post_date,
            $post->post_date,
            $post->ID
        ));

        return $next_id ? get_post($next_id) : null;
    }

    /**
     * Колбек 'lapki_i18n_prefix_switch_url' — на конкретному записі блогу з
     * вказаною парою-перекладом (postmeta 'lapki_blog_pair_id') перемикач
     * веде саме на неї, а не на дефолтний архів блогу цільової мови.
     */
    public static function filter_blog_switch_url($default_url, $key, $lang) {
        if ($key !== 'blog' || get_query_var('lapki_page') !== 'blog_single') {
            return $default_url;
        }

        $post = self::get_current_blog_post();
        if (!$post) {
            return $default_url;
        }

        $pair_id = (int) get_post_meta($post->ID, 'lapki_blog_pair_id', true);
        if (!$pair_id) {
            return $default_url;
        }

        $pair_post = get_post($pair_id);
        if (!$pair_post || $pair_post->post_type !== 'post') {
            return $default_url;
        }

        $pair_lang = get_post_meta($pair_post->ID, 'lang', true) === 'en' ? 'en' : 'uk';
        if ($pair_lang !== $lang) {
            return $default_url; // пара веде не в ту мову, куди зараз перемикаємось
        }

        if ($pair_post->post_status !== 'publish' && !current_user_can('edit_post', $pair_post->ID)) {
            return $default_url; // чернетку показуємо лише тому, хто може її редагувати
        }

        return self::get_blog_post_url($pair_post);
    }

    /**
     * Стаття допомоги для поточного запиту (/help/{slug}/) — кешована на
     * запит, той самий підхід, що й get_current_blog_post()/get_current_about_post().
     */
    public static function get_current_help_post() {
        static $cache = [];
        $slug = sanitize_title(get_query_var('lapki_help_slug'));

        if (!$slug) {
            return null;
        }

        if (!array_key_exists($slug, $cache)) {
            $cache[$slug] = Lapki_Help::get_post_by_slug($slug);
        }

        return $cache[$slug];
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
     * Мова поточного запиту "Про нас" ('uk'/'en', з rewrite-правила).
     */
    public static function get_current_about_lang() {
        $lang = get_query_var('lapki_about_lang');
        return $lang === 'en' ? 'en' : 'uk';
    }

    /**
     * WP-запис "Про нас" для поточної мови (окремий post_id на мову, як у
     * WPML — див. Lapki_I18n::URL_LANG_PAIRS). Чернетку (post_status=draft)
     * бачить лише той, хто може її редагувати (прев'ю, як для звичайних
     * WP-сторінок) — звичайний відвідувач отримує null → 404 у шаблоні.
     */
    public static function get_current_about_post() {
        static $cache = [];
        $lang = self::get_current_about_lang();

        if (array_key_exists($lang, $cache)) {
            return $cache[$lang];
        }

        $slug = $lang === 'en' ? 'about-en' : 'about';
        $post = get_page_by_path($slug, OBJECT, 'page');

        if ($post && $post->post_status !== 'publish' && !current_user_can('edit_page', $post->ID)) {
            $post = null;
        }

        return $cache[$lang] = $post ?: null;
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

            case 'test_page':
                return 'Тестова сторінка інтеграції — ' . $site_name;

            case 'add_animal':
                return 'Додати тварину — ' . $site_name;

            case 'add_organization':
                return 'Зареєструвати організацію — ' . $site_name;

            case 'edit_profile':
                return 'Редагування профілю — ' . $site_name;

            case 'about':
                $post = self::get_current_about_post();
                return $post ? $post->post_title . ' — ' . $site_name : $title;

            case 'blog_archive':
                return 'Блог — ' . $site_name;

            case 'blog_single':
                $post = self::get_current_blog_post();
                return $post ? $post->post_title . ' — ' . $site_name : $title;

            case 'help_archive':
                return _x('Допомога', 'help section', 'lapki') . ' — ' . $site_name;

            case 'help_single':
                $post = self::get_current_help_post();
                return $post ? $post->post_title . ' — ' . $site_name : $title;
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

            case 'about':
                $post = self::get_current_about_post();
                return $post ? wp_trim_words(wp_strip_all_tags($post->post_content), 30) : '';

            case 'blog_archive':
                return 'Новини та історії платформи Lapki — притулки, тварини, усиновлення.';

            case 'blog_single':
                $post = self::get_current_blog_post();
                if (!$post) {
                    return '';
                }
                return !empty($post->post_excerpt)
                    ? wp_strip_all_tags($post->post_excerpt)
                    : wp_trim_words(wp_strip_all_tags($post->post_content), 30);

            case 'help_archive':
                return 'Відповіді на поширені запитання про Lapki — тварин, притулки та роботу з платформою.';

            case 'help_single':
                $post = self::get_current_help_post();
                if (!$post) {
                    return '';
                }
                return wp_trim_words(wp_strip_all_tags($post->post_content), 30);
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

            case 'about':
                return home_url(self::get_current_about_lang() === 'en' ? '/en/about/' : '/about/');

            case 'blog_archive':
                return home_url(self::get_current_blog_lang() === 'en' ? '/en/blog/' : '/blog/');

            case 'blog_single':
                $post = self::get_current_blog_post();
                return $post ? self::get_blog_post_url($post) : '';

            case 'help_archive':
                return home_url('/help/');

            case 'help_single':
                $post = self::get_current_help_post();
                return $post ? Lapki_Help::get_post_url($post) : '';
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

            case 'about':
                $post = self::get_current_about_post();
                if (!$post) {
                    return;
                }
                $title = $post->post_title;
                break;

            case 'blog_archive':
                $title = 'Блог';
                break;

            case 'blog_single':
                $post = self::get_current_blog_post();
                if (!$post) {
                    return;
                }
                $title = $post->post_title;
                if (has_post_thumbnail($post->ID)) {
                    $thumb_id = get_post_thumbnail_id($post->ID);
                    $thumb = wp_get_attachment_image_src($thumb_id, 'large');
                    if ($thumb) {
                        $image = [
                            'url' => set_url_scheme($thumb[0], 'https'),
                            'width' => $thumb[1],
                            'height' => $thumb[2],
                            'alt' => $post->post_title,
                        ];
                    }
                }
                break;

            case 'help_archive':
                $title = _x('Допомога', 'help section', 'lapki');
                break;

            case 'help_single':
                $post = self::get_current_help_post();
                if (!$post) {
                    return;
                }
                $title = $post->post_title;
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

        echo '<meta property="og:type" content="' . (in_array($page, ['blog_single', 'help_single'], true) ? 'article' : 'website') . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '">' . "\n";
        // Динамічно (а не завжди uk_UA) — інакше /en/about/ оголошував би
        // локаль uk_UA для сторінки, вміст якої насправді англійською.
        $og_locale = class_exists('Lapki_I18n') && Lapki_I18n::get_lang() === 'en' ? 'en_US' : 'uk_UA';
        echo '<meta property="og:locale" content="' . esc_attr($og_locale) . '">' . "\n";
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
     * <link rel="alternate" hreflang="..."> — сторінки з окремим URL на
     * кожну мову (Lapki_I18n::URL_LANG_PAIRS), напр. /about/ ↔ /en/about/,
     * щоб пошуковики знали, що це переклади одна одної, а не дублі.
     *
     * Архів блогу (/blog/ ↔ /en/blog/) — та сама логіка, з URL_LANG_PREFIXES.
     * Окремий запис блогу (blog_single) тут НЕ бере участі — uk/en-запис не
     * пов'язані одне з одним (різні post_id, окремі слаги), тож "переклад"
     * конкретного допису просто не існує; hreflang для нього означав би
     * невірну заяву пошуковику.
     */
    public static function output_hreflang_alternates() {
        $page = get_query_var('lapki_page');

        if ($page === 'about') {
            if (!self::get_current_about_post()) {
                return;
            }
            foreach (Lapki_I18n::URL_LANG_PAIRS['about'] as $lang => $path) {
                $hreflang = $lang === 'en' ? 'en' : 'uk';
                echo '<link rel="alternate" hreflang="' . esc_attr($hreflang) . '" href="' . esc_url(home_url($path)) . '">' . "\n";
            }
            return;
        }

        if ($page === 'blog_archive') {
            foreach (Lapki_I18n::URL_LANG_PREFIXES['blog'] as $lang => $path) {
                $hreflang = $lang === 'en' ? 'en' : 'uk';
                echo '<link rel="alternate" hreflang="' . esc_attr($hreflang) . '" href="' . esc_url(home_url($path)) . '">' . "\n";
            }
        }
    }

    /**
     * noindex для сторінок без цінності в пошуку: приватний кабінет
     * (анонімний краулер бачить лише запрошення увійти) і тестова
     * сторінка embed-віджета (навмисно не в меню).
     *
     * Індексація сайту ЗАГАЛОМ (напр. поки в базі тестові дані, перед
     * запуском) вимикається штатним засобом WordPress — Налаштування →
     * Читання → "Прохати пошукові системи не індексувати сайт" (опція
     * `blog_public`), не кастомним кодом тут: WP-ядро само додає noindex
     * на КОЖНУ сторінку (`wp_robots_noindex()` в `wp-includes/robots-template.php`)
     * і вимикає `wp-sitemap.xml` (`WP_Sitemaps::is_enabled()`), коли ця
     * опція вимкнена — без потреби дублювати цю логіку в плагіні.
     */
    public static function filter_wp_robots($robots) {
        $page = get_query_var('lapki_page');

        if (in_array($page, ['profile', 'widget_demo', 'add_animal', 'add_organization', 'edit_profile', 'test_page'], true)) {
            $robots['noindex'] = true;
        }

        // "Про нас" — поки не опублікована (post_status != publish), не
        // повинна індексуватись, навіть якщо адмін її зараз переглядає.
        if ($page === 'about') {
            $post = self::get_current_about_post();
            if (!$post || $post->post_status !== 'publish') {
                $robots['noindex'] = true;
            }
        }

        // Так само для чернеток у блозі, показаних авторові в прев'ю.
        if ($page === 'blog_single') {
            $post = self::get_current_blog_post();
            if (!$post || $post->post_status !== 'publish') {
                $robots['noindex'] = true;
            }
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

    /**
     * Нативний permalink запису блогу → /blog/{slug}/ або /en/blog/{slug}/
     * (301, мовний префікс за postmeta 'lang' — див. get_blog_post_url()).
     * Не наш власний lapki_page-роут (той не потрапляє під is_singular('post')
     * узагалі, тож перевірка нижче їх не перетинає).
     */
    public static function maybe_redirect_native_post_permalink() {
        if (!is_singular('post')) {
            return;
        }

        $post = get_queried_object();
        if (!$post instanceof WP_Post) {
            return;
        }

        wp_safe_redirect(self::get_blog_post_url($post), 301);
        exit;
    }
}
