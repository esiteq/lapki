<?php
/**
 * Особистий кабінет користувача — /profile/
 * (раніше /cabinet/ через шорткод [lapki_cabinet] + звичайну WP-сторінку;
 * тепер повноцінний full-width шаблон, як /animals/, /organizations/ тощо)
 *
 * Тема може перевизначити цей шаблон: скопіювати у
 * wp-content/themes/{тема}/lapki/profile.php
 *
 * @package Lapki
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

if (!is_user_logged_in()) :
    ?>
    <section class="py-5">
        <div class="container">
            <div class="alert alert-info mb-0">
                Щоб переглянути особистий кабінет, спершу
                <a href="<?php echo esc_url(wp_login_url(home_url('/profile/'))); ?>">увійдіть</a>
                або <a href="<?php echo esc_url(home_url('/signup/')); ?>">зареєструйтесь</a>.
            </div>
        </div>
    </section>
    <?php
    get_footer();
    return;
endif;

$current_user = wp_get_current_user();
$membership = Lapki_Organization_Member::get_by_user($current_user->ID);
$organization = $membership ? Lapki_Organization::get($membership['organization_id']) : null;

// Власник не може вийти з організації — лише передати комусь іншому з учасників
$is_owner = $membership && $membership['role'] === Lapki_Organization_Member::ROLE_OWNER;
$other_members = [];
if ($is_owner) {
    $other_members = array_values(array_filter(
        Lapki_Organization_Member::get_members($organization['id']),
        function ($m) use ($current_user) {
            return (int) $m['wp_user_id'] !== (int) $current_user->ID;
        }
    ));
}

$allowed_tabs = ['home', 'animals'];
$tab = isset($_GET['tab']) && in_array($_GET['tab'], $allowed_tabs, true) ? $_GET['tab'] : 'home';

$type_labels = [
    'individual' => 'Приватна особа',
    'shelter'    => 'Притулок',
    'vet_clinic' => 'Ветеринарна клініка',
    'vet'        => 'Окремий ветеринар',
    'volunteer'  => 'Волонтерська організація',
];

$role_labels = [
    'owner'  => 'Власник',
    'member' => 'Учасник',
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

// Список організацій для приєднання (тільки якщо користувач ще нікуди не прив'язаний)
$joinable_organizations = [];
if ($tab === 'home' && !$organization) {
    $joinable_organizations = Lapki_Organization::search(['limit' => 200]);
}

$avatar_letter = mb_strtoupper(mb_substr($current_user->display_name, 0, 1));
?>

<section class="py-5">
    <div class="container">

        <div class="lapki-profile-header d-flex align-items-center gap-3 mb-4">
            <div class="lapki-profile-avatar"><?php echo esc_html($avatar_letter); ?></div>
            <div>
                <h1 class="h4 fw-bold mb-1"><?php echo esc_html($current_user->display_name); ?></h1>
                <p class="text-muted mb-0"><i class="fas fa-envelope"></i> <?php echo esc_html($current_user->user_email); ?></p>
            </div>
        </div>

        <div class="row g-4 lapki-cabinet">
            <div class="col-md-3">
                <div class="list-group lapki-cabinet-nav shadow-sm">
                    <a href="<?php echo esc_url(add_query_arg('tab', 'home')); ?>"
                       class="list-group-item list-group-item-action<?php echo $tab === 'home' ? ' active' : ''; ?>">
                        <i class="fas fa-home me-2"></i>Головна
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('tab', 'animals')); ?>"
                       class="list-group-item list-group-item-action<?php echo $tab === 'animals' ? ' active' : ''; ?>">
                        <i class="fas fa-paw me-2"></i>Мої тварини
                    </a>
                    <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>"
                       class="list-group-item list-group-item-action">
                        <i class="fas fa-sign-out-alt me-2"></i>Вихід
                    </a>
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
                                <p class="mb-0"><strong>Телефон:</strong> <?php echo esc_html($phone); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($organization) : ?>
                        <div class="card border-0 shadow-sm mt-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h2 class="h5 fw-bold mb-0"><?php echo esc_html($organization['name']); ?></h2>
                                    <span class="badge bg-secondary"><?php echo esc_html($role_labels[$membership['role']] ?? $membership['role']); ?></span>
                                </div>
                                <p class="text-muted mb-2">
                                    <?php echo esc_html($type_labels[$organization['type']] ?? $organization['type']); ?>
                                    <?php if (!empty($organization['is_verified'])) : ?>
                                        <span class="badge bg-success ms-1"><i class="fas fa-check-circle"></i> Верифіковано</span>
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($organization['city'])) : ?><p class="mb-1"><i class="fas fa-map-marker-alt"></i> <?php echo esc_html($organization['city']); ?></p><?php endif; ?>
                                <?php if (!empty($organization['phone'])) : ?><p class="mb-1"><i class="fas fa-phone"></i> <?php echo esc_html($organization['phone']); ?></p><?php endif; ?>
                                <?php if (!empty($organization['email'])) : ?><p class="mb-1"><i class="fas fa-envelope"></i> <?php echo esc_html($organization['email']); ?></p><?php endif; ?>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <a href="<?php echo esc_url(home_url('/organizations/' . (int) $organization['id'] . '/')); ?>" class="lapki-link-green">
                                        Переглянути публічну сторінку →
                                    </a>
                                    <?php if (!$is_owner) : ?>
                                        <button type="button" id="lapki-leave-org-btn" class="btn btn-sm btn-outline-danger">Залишити організацію</button>
                                    <?php endif; ?>
                                </div>
                                <div id="lapki-leave-org-alert" class="alert d-none mt-3" role="alert"></div>

                                <?php if ($is_owner) : ?>
                                    <hr>
                                    <h3 class="h6 fw-bold">Передати право власності</h3>
                                    <?php if (!empty($other_members)) : ?>
                                        <p class="text-muted small">Власник не може вийти з організації — спершу передайте право власності іншому учаснику.</p>
                                        <div class="input-group">
                                            <select id="lapki-transfer-owner-select" class="form-select">
                                                <?php foreach ($other_members as $m) : ?>
                                                    <option value="<?php echo (int) $m['wp_user_id']; ?>"><?php echo esc_html($m['display_name']); ?> (<?php echo esc_html($m['user_email']); ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" id="lapki-transfer-owner-btn" class="btn btn-outline-danger" data-org-id="<?php echo (int) $organization['id']; ?>">Передати</button>
                                        </div>
                                        <div id="lapki-transfer-owner-alert" class="alert d-none mt-3" role="alert"></div>
                                    <?php else : ?>
                                        <p class="text-muted small mb-0">У організації поки немає інших учасників, тож передати чи вийти неможливо. Поділіться посиланням на <a href="<?php echo esc_url(home_url('/signup/')); ?>">реєстрацію</a>, щоб хтось приєднався.</p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="card border-0 shadow-sm mt-4">
                            <div class="card-body">
                                <h2 class="h5 fw-bold mb-3">Приєднатися до організації</h2>
                                <p class="text-muted small">Ви поки не прив'язані до жодного притулку чи ГО. Приєднайтесь до вже існуючої організації або зареєструйте свою.</p>

                                <ul class="nav nav-tabs mb-3" id="lapki-org-attach-tabs">
                                    <li class="nav-item">
                                        <button type="button" class="nav-link active" data-attach-tab="join">Приєднатися</button>
                                    </li>
                                    <li class="nav-item">
                                        <button type="button" class="nav-link" data-attach-tab="create">Зареєструвати нову</button>
                                    </li>
                                </ul>

                                <div id="lapki-org-join-panel">
                                    <input type="text" id="lapki-org-search" class="form-control mb-3" placeholder="Пошук за назвою або містом...">
                                    <div id="lapki-org-join-alert" class="alert d-none" role="alert"></div>
                                    <div id="lapki-org-list" class="list-group">
                                        <?php if (empty($joinable_organizations)) : ?>
                                            <p class="text-muted small mb-0">Організацій поки немає — зареєструйте першу.</p>
                                        <?php else : foreach ($joinable_organizations as $org) : ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center lapki-org-list-item"
                                                 data-search="<?php echo esc_attr(mb_strtolower($org['name'] . ' ' . ($org['city'] ?? ''))); ?>">
                                                <div>
                                                    <div class="fw-semibold"><?php echo esc_html($org['name']); ?></div>
                                                    <div class="small text-muted">
                                                        <?php echo esc_html($type_labels[$org['type']] ?? $org['type']); ?><?php echo !empty($org['city']) ? ' · ' . esc_html($org['city']) : ''; ?>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-primary lapki-join-org-btn" data-org-id="<?php echo (int) $org['id']; ?>">Приєднатися</button>
                                            </div>
                                        <?php endforeach; endif; ?>
                                    </div>
                                </div>

                                <div id="lapki-org-create-panel" class="d-none">
                                    <form id="lapki-org-create-form" class="row g-3" novalidate>
                                        <div class="col-12">
                                            <label class="form-label">Тип організації *</label>
                                            <select name="type" class="form-select" required>
                                                <option value="" selected disabled>Оберіть тип</option>
                                                <option value="shelter">Притулок</option>
                                                <option value="vet_clinic">Ветеринарна клініка</option>
                                                <option value="vet">Окремий ветеринар</option>
                                                <option value="volunteer">Волонтерська організація</option>
                                                <option value="individual">Приватна особа</option>
                                            </select>
                                            <div class="invalid-feedback">Оберіть тип організації.</div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Назва *</label>
                                            <input type="text" name="name" class="form-control" required>
                                            <div class="invalid-feedback">Вкажіть назву організації.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Місто</label>
                                            <input type="text" name="city" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Телефон</label>
                                            <input type="tel" name="phone" class="form-control">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control">
                                        </div>
                                        <div class="col-12">
                                            <div id="lapki-org-create-alert" class="alert d-none" role="alert"></div>
                                            <button type="submit" class="btn lapki-btn-orange">Зареєструвати організацію</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php else : /* tab === 'animals' */ ?>

                    <h2 class="h5 fw-bold mb-3">Мої тварини</h2>

                    <?php if (!$organization) : ?>
                        <p class="text-muted">У вас поки немає організації. Приєднайтесь до існуючої або зареєструйте свою на вкладці «Головна».</p>
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
                                            <div class="d-flex align-items-center justify-content-center bg-light text-muted" style="aspect-ratio:4/3;font-size:2.5rem;"><i class="fas fa-paw"></i></div>
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
    </div>
</section>

<?php get_footer(); ?>
