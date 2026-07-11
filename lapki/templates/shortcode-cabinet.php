<?php
/**
 * Шорткод [lapki_cabinet] — особистий кабінет користувача.
 *
 * @package Lapki
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) :
    ?>
    <div class="alert alert-info mb-0">
        Щоб переглянути особистий кабінет, спершу
        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">увійдіть</a>
        або <a href="<?php echo esc_url(home_url('/signup/')); ?>">зареєструйтесь</a>.
    </div>
    <?php
    return;
endif;

$current_user = wp_get_current_user();
$organizations = Lapki_Organization::get_by_wp_user_id($current_user->ID);
$organization = !empty($organizations) ? $organizations[0] : null;

$allowed_tabs = ['home', 'animals'];
$tab = isset($_GET['tab']) && in_array($_GET['tab'], $allowed_tabs, true) ? $_GET['tab'] : 'home';

$type_labels = [
    'individual' => 'Приватна особа',
    'shelter'    => 'Притулок',
    'vet_clinic' => 'Ветеринарна клініка',
    'vet'        => 'Окремий ветеринар',
    'volunteer'  => 'Волонтер',
];

$status_labels = [
    'adoptable' => 'Шукає дім',
    'adopted'   => 'Прилаштована',
    'hold'      => 'На утриманні',
    'found'     => 'Знайдена',
];
$status_colors = [
    'adoptable' => 'success',
    'adopted'   => 'secondary',
    'hold'      => 'warning',
    'found'     => 'info',
];

$animals = [];
if ($tab === 'animals' && $organization) {
    $animals = Lapki_Animal::search([
        'organization_id' => $organization['id'],
        'limit' => 100,
        'order_by' => 'created_at',
        'order' => 'DESC',
    ]);
}
?>
<div class="row g-4 lapki-cabinet">
    <div class="col-md-3">
        <div class="list-group lapki-cabinet-nav">
            <a href="<?php echo esc_url(add_query_arg('tab', 'home')); ?>"
               class="list-group-item list-group-item-action<?php echo $tab === 'home' ? ' active' : ''; ?>">Головна</a>
            <a href="<?php echo esc_url(add_query_arg('tab', 'animals')); ?>"
               class="list-group-item list-group-item-action<?php echo $tab === 'animals' ? ' active' : ''; ?>">Мої тварини</a>
            <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>"
               class="list-group-item list-group-item-action">Вихід</a>
        </div>
    </div>

    <div class="col-md-9">
        <?php if ($tab === 'home') : ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h5 fw-bold mb-3">Інформація про користувача</h2>
                    <p class="mb-1"><strong>Ім'я:</strong> <?php echo esc_html($current_user->display_name); ?></p>
                    <p class="mb-1"><strong>Email:</strong> <?php echo esc_html($current_user->user_email); ?></p>
                    <?php $phone = get_user_meta($current_user->ID, 'lapki_phone', true); ?>
                    <?php if ($phone) : ?>
                        <p class="mb-1"><strong>Телефон:</strong> <?php echo esc_html($phone); ?></p>
                    <?php endif; ?>
                    <?php $user_type = get_user_meta($current_user->ID, 'lapki_user_type', true); ?>
                    <?php if ($user_type) : ?>
                        <p class="mb-0"><strong>Тип реєстрації:</strong> <?php echo esc_html($type_labels[$user_type] ?? $user_type); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($organization) : ?>
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h2 class="h5 fw-bold mb-0"><?php echo esc_html($organization['name']); ?></h2>
                            <?php if (!empty($organization['is_verified'])) : ?>
                                <span class="badge bg-success">✓ Верифіковано</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted mb-2"><?php echo esc_html($type_labels[$organization['type']] ?? $organization['type']); ?></p>
                        <?php if (!empty($organization['city'])) : ?><p class="mb-1">📍 <?php echo esc_html($organization['city']); ?></p><?php endif; ?>
                        <?php if (!empty($organization['phone'])) : ?><p class="mb-1">☎ <?php echo esc_html($organization['phone']); ?></p><?php endif; ?>
                        <?php if (!empty($organization['email'])) : ?><p class="mb-1">✉ <?php echo esc_html($organization['email']); ?></p><?php endif; ?>
                        <p class="mb-0 mt-2">
                            <a href="<?php echo esc_url(home_url('/organizations/' . (int) $organization['id'] . '/')); ?>" class="lapki-link-green">
                                Переглянути публічну сторінку →
                            </a>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

        <?php else : /* tab === 'animals' */ ?>

            <h2 class="h5 fw-bold mb-3">Мої тварини</h2>

            <?php if (!$organization) : ?>
                <p class="text-muted">У вас поки немає організації.</p>
            <?php elseif (empty($animals)) : ?>
                <p class="text-muted">Тварин поки немає.</p>
            <?php else : ?>
                <div class="row g-3">
                    <?php foreach ($animals as $animal) :
                        $photo_url = !empty($animal['primary_photo']['thumbnail_url']) ? $animal['primary_photo']['thumbnail_url'] : '';
                    ?>
                        <div class="col-sm-6 col-lg-4">
                            <a href="<?php echo esc_url(home_url('/animals/' . (int) $animal['id'] . '/')); ?>" class="card border-0 shadow-sm h-100 text-decoration-none">
                                <?php if ($photo_url) : ?>
                                    <img src="<?php echo esc_url($photo_url); ?>" class="card-img-top" style="aspect-ratio:4/3;object-fit:cover;" alt="<?php echo esc_attr($animal['name']); ?>">
                                <?php else : ?>
                                    <div class="d-flex align-items-center justify-content-center bg-light" style="aspect-ratio:4/3;font-size:2.5rem;">🐾</div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h3 class="h6 fw-bold mb-1"><?php echo esc_html($animal['name']); ?></h3>
                                    <span class="badge bg-<?php echo esc_attr($status_colors[$animal['status']] ?? 'secondary'); ?>">
                                        <?php echo esc_html($status_labels[$animal['status']] ?? $animal['status']); ?>
                                    </span>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>
