<?php
/**
 * Архів розділу "Допомога" — /help/
 *
 * Сайдбар (25%, .col-lg-3) — зміст, згрупований за категоріями
 * (Lapki_Help::get_categories_with_posts()); основна колонка (75%,
 * .col-lg-9) — тут, на архіві (без обраної статті), підказка обрати
 * статтю зліва. Сама стаття рендериться на /help/{slug}/ — див.
 * single-help.php (той самий сайдбар, лише з підсвіченим активним пунктом).
 *
 * Тема може перевизначити цей шаблон: скопіювати у
 * wp-content/themes/{тема}/lapki/archive-help.php
 *
 * @package Lapki
 */

if (!defined('ABSPATH')) {
    exit;
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
                    <?php if (!$help_groups) : ?>
                        <p class="text-muted small"><?php esc_html_e('Статей поки немає.', 'lapki'); ?></p>
                    <?php endif; ?>
                    <?php foreach ($help_groups as $group) : ?>
                        <div class="lapki-help-nav-heading"><?php echo esc_html($group['term'] ? $group['term']->name : __('Без категорії', 'lapki')); ?></div>
                        <div class="list-group lapki-cabinet-nav mb-3">
                            <?php foreach ($group['posts'] as $help_post) : ?>
                                <a href="<?php echo esc_url(Lapki_Help::get_post_url($help_post)); ?>" class="list-group-item list-group-item-action">
                                    <?php echo esc_html($help_post->post_title); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </nav>
            </div>

            <div class="col-lg-9">
                <h1 class="h3 fw-bold mb-4"><?php echo esc_html(_x('Допомога', 'help section', 'lapki')); ?></h1>
                <p class="text-muted"><?php esc_html_e('Оберіть статтю зі списку зліва, щоб переглянути її.', 'lapki'); ?></p>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
