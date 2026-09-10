<?php
/**
 * Один запис блогу — /blog/{slug}/ (uk) або /en/blog/{slug}/ (en)
 *
 * Тема може перевизначити цей шаблон: скопіювати у
 * wp-content/themes/{тема}/lapki/single-blog.php
 *
 * @package Lapki
 */

if (!defined('ABSPATH')) {
    exit;
}

$post = Lapki_Frontend::get_current_blog_post();
$blog_archive_url = home_url(Lapki_Frontend::get_current_blog_lang() === 'en' ? '/en/blog/' : '/blog/');

if (!$post) {
    status_header(404);
    get_header();
    echo '<section class="py-5"><div class="container text-center"><h1 class="h3">' . esc_html__('Публікацію не знайдено', 'lapki') . '</h1>';
    echo '<p><a href="' . esc_url($blog_archive_url) . '" class="lapki-link-green">' . esc_html__('← Всі публікації', 'lapki') . '</a></p></div></section>';
    get_footer();
    return;
}

$next_post = Lapki_Frontend::get_next_blog_post($post);

get_header();
?>

<section class="pt-3 pb-5">
    <div class="container">
        <p class="mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <a href="<?php echo esc_url($blog_archive_url); ?>" class="lapki-link-green small">← <?php esc_html_e('Всі публікації', 'lapki'); ?></a>
            <?php if ($next_post) : ?>
                <a href="<?php echo esc_url(Lapki_Frontend::get_blog_post_url($next_post)); ?>" class="lapki-link-green small"><?php esc_html_e('Наступна публікація', 'lapki'); ?> →</a>
            <?php endif; ?>
        </p>

        <?php if ($post->post_status !== 'publish') : ?>
            <div class="alert alert-warning small">
                <?php esc_html_e('Це чернетка — публікацію бачите лише ви як автор/адміністратор. Опублікуйте її в wp-admin → Записи, коли текст буде готовий.', 'lapki'); ?>
            </div>
        <?php endif; ?>

        <?php if (has_post_thumbnail($post->ID)) : ?>
            <div class="mb-4">
                <?php echo get_the_post_thumbnail($post->ID, 'large', ['class' => 'lapki-blog-hero-img rounded-4', 'alt' => esc_attr($post->post_title)]); ?>
            </div>
        <?php endif; ?>

        <h1 class="h3 fw-bold mb-2"><?php echo esc_html($post->post_title); ?></h1>
        <p class="small text-muted mb-4"><?php echo esc_html(get_the_date('', $post)); ?></p>

        <div class="lapki-about-content">
            <?php echo apply_filters('the_content', $post->post_content); ?>
        </div>
    </div>
</section>

<?php get_footer(); ?>
