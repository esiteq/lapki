<?php
/**
 * Про нас — /about/ (uk) і /en/about/ (en)
 *
 * Окремий WP-запис (post_id) на кожну мову, як у WPML — див.
 * Lapki_I18n::URL_LANG_PAIRS і Lapki_Frontend::get_current_about_post().
 * Контент редагується як звичайна WP-сторінка (Сторінки → «Про нас» /
 * «About us» у wp-admin), а не хардкодиться в шаблоні.
 *
 * Тема може перевизначити цей шаблон: скопіювати у
 * wp-content/themes/{тема}/lapki/about.php
 *
 * @package Lapki
 */

if (!defined('ABSPATH')) {
    exit;
}

$post = Lapki_Frontend::get_current_about_post();

if (!$post) {
    status_header(404);
    get_header();
    echo '<section class="py-5"><div class="container text-center"><h1 class="h3">' . esc_html__('Сторінку не знайдено', 'lapki') . '</h1>';
    echo '<p><a href="' . esc_url(home_url('/')) . '" class="lapki-link-green">' . esc_html__('← На головну', 'lapki') . '</a></p></div></section>';
    get_footer();
    return;
}

get_header();
?>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if ($post->post_status !== 'publish') : ?>
                    <div class="alert alert-warning small">
                        <?php esc_html_e('Це чернетка — сторінку бачите лише ви як адміністратор. Опублікуйте її в wp-admin → Сторінки, коли текст буде готовий.', 'lapki'); ?>
                    </div>
                <?php endif; ?>

                <h1 class="h3 fw-bold mb-4"><?php echo esc_html($post->post_title); ?></h1>

                <div class="lapki-about-content">
                    <?php echo apply_filters('the_content', $post->post_content); ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
