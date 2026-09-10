<?php
/**
 * Блог — /blog/ (uk) і /en/blog/ (en)
 *
 * Звичайні WP-записи (post_type=post), відфільтровані за мовою через
 * postmeta 'lang' ('uk'/'en') — uk- і en-версія "того самого" запису це
 * два ОКРЕМІ записи (різний post_id, різний слаг), не переклад одна одної.
 * Мова кожного поста задається в редакторі метабоксом "Мова" (Lapki_Admin).
 * Мова САМОЇ сторінки — з URL-префікса (Lapki_I18n::URL_LANG_PREFIXES, для
 * SEO — Google має бачити мову в URL, а не лише в куці); перемикач UA/EN у
 * шапці (Lapki_I18n::render_switcher()) на цій сторінці веде на архів
 * блогу іншою мовою, а не додає ?lapki_lang= до поточного URL.
 *
 * Тема може перевизначити цей шаблон: скопіювати у
 * wp-content/themes/{тема}/lapki/blog.php
 *
 * @package Lapki
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$lang = Lapki_Frontend::get_current_blog_lang();
// 'paged' — з rewrite-правила /blog/page/{n}/ (query var 'paged', те саме,
// що WordPress використовує для пагінації нативних архівів) — не query-string
// ?paged=, який WP-ядро (redirect_canonical(), permalink_structure з сесії 93)
// все одно 301-редіректило б сюди ж.
$paged = max(1, absint(get_query_var('paged')));
$archive_url = home_url($lang === 'en' ? '/en/blog/' : '/blog/');

$blog_query = new WP_Query([
    'post_type'   => 'post',
    'post_status' => 'publish',
    // posts_per_page навмисно не передаємо — WP_Query сам бере стандартну
    // опцію "Скільки записів блогу показувати на сторінці" (Налаштування →
    // Читання, get_option('posts_per_page')), а не власне число тут.
    'paged'       => $paged,
    // Явний тайбрейк по ID (не лише 'date') — за замовчуванням MySQL при
    // однакових post_date не гарантує стабільний порядок, а get_next_blog_post()
    // у Lapki_Frontend має видавати "наступний" пост саме в цьому порядку.
    'orderby'     => ['date' => 'DESC', 'ID' => 'ASC'],
    'meta_key'    => 'lang',
    'meta_value'  => $lang,
]);
?>

<section class="py-5">
    <div class="container">
        <h1 class="h3 fw-bold mb-4"><?php esc_html_e('Блог', 'lapki'); ?></h1>

        <div class="row g-4">
            <?php if (!$blog_query->have_posts()) : ?>
                <p class="text-muted"><?php esc_html_e('Публікацій поки немає.', 'lapki'); ?></p>
            <?php else : while ($blog_query->have_posts()) : $blog_query->the_post(); ?>
                <div class="col-md-6 col-lg-4">
                    <a href="<?php echo esc_url(Lapki_Frontend::get_blog_post_url(get_post())); ?>" class="lapki-card text-decoration-none">
                        <div class="lapki-card__img" style="aspect-ratio:16/10;">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('medium_large', ['loading' => 'lazy', 'alt' => get_the_title()]); ?>
                            <?php else : ?>
                                <div class="lapki-card__img-placeholder">
                                    <i class="fas fa-paw"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="lapki-card__body">
                            <p class="small text-muted mb-1"><?php echo esc_html(get_the_date()); ?></p>
                            <h2 class="h6 fw-bold mb-2"><?php the_title(); ?></h2>
                            <p class="small text-muted mb-0"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20)); ?></p>
                        </div>
                    </a>
                </div>
            <?php endwhile; endif; ?>
        </div>

        <?php
        $pagination = paginate_links([
            'base'      => trailingslashit($archive_url) . '%_%',
            'format'    => 'page/%#%/',
            'current'   => $paged,
            'total'     => $blog_query->max_num_pages,
            'prev_text' => '←',
            'next_text' => '→',
            'type'      => 'array',
        ]);
        ?>
        <?php if (!empty($pagination)) : ?>
            <nav class="mt-5 d-flex justify-content-center" aria-label="<?php esc_attr_e('Сторінки блогу', 'lapki'); ?>">
                <ul class="pagination">
                    <?php foreach ($pagination as $link) : ?>
                        <li class="page-item<?php echo (strpos($link, 'current') !== false) ? ' active' : ''; ?>">
                            <?php echo str_replace('page-numbers', 'page-link', $link); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</section>

<?php
wp_reset_postdata();
get_footer();
