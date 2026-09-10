<?php
/**
 * Донат — /donate/
 * Три способи допомогти грошима: притулку, волонтеру, конкретній тварині.
 *
 * Тема може перевизначити цей шаблон: скопіювати у
 * wp-content/themes/{тема}/lapki/donate.php
 *
 * @package Lapki
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<section class="py-5">
    <div class="container">
        <h1 class="h3 fw-bold mb-2"><i class="fas fa-heart text-danger me-2"></i><?php esc_html_e('Підтримати', 'lapki'); ?></h1>
        <p class="text-muted mb-5">
            <?php esc_html_e('Тут ви можете пожертвувати гроші притулку, окремому волонтеру, або конкретній тварині, яка шукає дім.', 'lapki'); ?>
        </p>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <h2 class="h5 fw-bold mb-2"><i class="fas fa-warehouse me-1"></i><?php esc_html_e('Притулок', 'lapki'); ?></h2>
                        <p class="text-muted small"><?php esc_html_e('Оберіть притулок зі списку — ви потрапите на його сторінку з контактами для донату.', 'lapki'); ?></p>
                        <div class="lapki-donate-field position-relative mb-3">
                            <input type="text" id="donate-shelter-input" class="form-control" placeholder="<?php esc_attr_e('Почніть вводити назву притулку...', 'lapki'); ?>" autocomplete="off">
                        </div>
                        <button type="button" id="donate-shelter-btn" class="btn lapki-btn-green w-100 mt-auto" disabled><?php esc_html_e('Допомогти грошима', 'lapki'); ?></button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <h2 class="h5 fw-bold mb-2"><i class="fas fa-hand-holding-heart me-1"></i><?php esc_html_e('Волонтер', 'lapki'); ?></h2>
                        <p class="text-muted small"><?php esc_html_e('Оберіть волонтерську організацію зі списку — ви потрапите на її сторінку з контактами для донату.', 'lapki'); ?></p>
                        <div class="lapki-donate-field position-relative mb-3">
                            <input type="text" id="donate-volunteer-input" class="form-control" placeholder="<?php esc_attr_e('Почніть вводити назву...', 'lapki'); ?>" autocomplete="off">
                        </div>
                        <button type="button" id="donate-volunteer-btn" class="btn lapki-btn-green w-100 mt-auto" disabled><?php esc_html_e('Допомогти грошима', 'lapki'); ?></button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <h2 class="h5 fw-bold mb-2"><i class="fas fa-paw me-1"></i><?php esc_html_e('Конкретна тварина', 'lapki'); ?></h2>
                        <p class="text-muted small">
                            <?php esc_html_e('Щоб допомогти конкретній тварині, знайдіть її за допомогою пошуку тварин і натисніть кнопку «Допомогти грошима» на сторінці цієї тварини.', 'lapki'); ?>
                        </p>
                        <a href="<?php echo esc_url(home_url('/animals/')); ?>" class="btn lapki-btn-green w-100 mt-auto">
                            <?php esc_html_e('Знайти тварину →', 'lapki'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
