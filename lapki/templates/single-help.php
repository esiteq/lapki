<?php
/**
 * Одна стаття допомоги — /help/{slug}/
 *
 * Той самий сайдбар-зміст, що й на архіві (/help/, див. archive-help.php),
 * лише з підсвіченою активною статтею; основна колонка — сам текст
 * статті (класичний редактор, apply_filters('the_content', ...) — той
 * самий підхід, що й /blog/{slug}/, /about/).
 *
 * Тема може перевизначити цей шаблон: скопіювати у
 * wp-content/themes/{тема}/lapki/single-help.php
 *
 * @package Lapki
 */

if (!defined('ABSPATH')) {
    exit;
}

$post = Lapki_Frontend::get_current_help_post();

if (!$post) {
    status_header(404);
    get_header();
    echo '<section class="py-5"><div class="container text-center"><h1 class="h3">' . esc_html__('Публікацію не знайдено', 'lapki') . '</h1>';
    echo '<p><a href="' . esc_url(home_url('/help/')) . '" class="lapki-link-green">' . esc_html__('← Усі статті допомоги', 'lapki') . '</a></p></div></section>';
    get_footer();
    return;
}

$help_groups = Lapki_Help::get_categories_with_posts();

get_header();
?>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3 mb-4 mb-lg-0">
                <button class="btn btn-outline-secondary w-100 d-lg-none mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#lapkiHelpNav" aria-expanded="false" aria-controls="lapkiHelpNav">
                    <i class="fas fa-list me-1"></i> <?php esc_html_e('Зміст', 'lapki'); ?>
                </button>
                <nav id="lapkiHelpNav" class="collapse d-lg-block">
                    <?php foreach ($help_groups as $group) : ?>
                        <div class="lapki-help-nav-heading"><?php echo esc_html($group['term'] ? $group['term']->name : __('Без категорії', 'lapki')); ?></div>
                        <div class="list-group lapki-cabinet-nav mb-3">
                            <?php foreach ($group['posts'] as $help_post) : ?>
                                <a href="<?php echo esc_url(Lapki_Help::get_post_url($help_post)); ?>"
                                   class="list-group-item list-group-item-action<?php echo $help_post->ID === $post->ID ? ' active' : ''; ?>">
                                    <?php echo esc_html($help_post->post_title); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </nav>
            </div>

            <div class="col-lg-9">
                <?php if ($post->post_status !== 'publish') : ?>
                    <div class="alert alert-warning small">
                        <?php esc_html_e('Це чернетка — статтю бачите лише ви як адміністратор. Опублікуйте її в wp-admin, коли текст буде готовий.', 'lapki'); ?>
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
