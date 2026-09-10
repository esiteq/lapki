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
                <?php
                printf(
                    /* translators: 1: посилання "увійдіть", 2: посилання "зареєструйтесь" */
                    esc_html__('Щоб переглянути особистий кабінет, спершу %1$s або %2$s.', 'lapki'),
                    '<a href="' . esc_url(home_url('/signup/?tab=login')) . '">' . esc_html__('увійдіть', 'lapki') . '</a>',
                    '<a href="' . esc_url(home_url('/signup/')) . '">' . esc_html__('зареєструйтесь', 'lapki') . '</a>'
                );
                ?>
            </div>
        </div>
    </section>
    <?php
    get_footer();
    return;
endif;

$current_user = wp_get_current_user();

// Усі організації користувача — тепер може бути кілька одночасно
$approved_memberships = Lapki_Organization_Member::get_all_by_user($current_user->ID, Lapki_Organization_Member::STATUS_APPROVED);
$pending_memberships = Lapki_Organization_Member::get_all_by_user($current_user->ID, Lapki_Organization_Member::STATUS_PENDING);

$my_organizations = [];
foreach ($approved_memberships as $m) {
    $org = Lapki_Organization::get($m['organization_id']);
    if ($org) {
        $my_organizations[] = ['membership' => $m, 'organization' => $org];
    }
}

// Заявки на приєднання ДО МОЇХ організацій (я власник) — сповіщення в кабінеті
$incoming_requests = [];
foreach ($my_organizations as $entry) {
    if ($entry['membership']['role'] === Lapki_Organization_Member::ROLE_OWNER) {
        $pending = Lapki_Organization_Member::get_pending_requests($entry['organization']['id']);
        if (!empty($pending)) {
            $incoming_requests[] = ['organization' => $entry['organization'], 'requests' => $pending];
        }
    }
}
$incoming_requests_count = array_sum(array_map(function ($e) { return count($e['requests']); }, $incoming_requests));

// Кількість тварин моїх організацій — для картки "Мої тварини" на головній
// (легкий COUNT, а не повна вибірка — та йде окремо, лише на вкладці 'animals').
// search()/count() не підтримують IN() по кількох organization_id одразу,
// тож сумуємо по кожній організації, як і $animals нижче на вкладці 'animals'
$my_animals_total = 0;
$my_animals_adoptable = 0;
foreach ($my_organizations as $entry) {
    $org_id = $entry['organization']['id'];
    $my_animals_total += Lapki_Animal::count(['organization_id' => $org_id]);
    $my_animals_adoptable += Lapki_Animal::count(['organization_id' => $org_id, 'status' => 'adoptable']);
}

$allowed_tabs = ['home', 'organizations', 'animals', 'applications', 'integration'];
$tab = isset($_GET['tab']) && in_array($_GET['tab'], $allowed_tabs, true) ? $_GET['tab'] : 'home';

$type_labels = [
    'individual' => __('Приватна особа', 'lapki'),
    'shelter'    => __('Притулок', 'lapki'),
    'vet_clinic' => __('Ветеринарна клініка', 'lapki'),
    'vet'        => __('Окремий ветеринар', 'lapki'),
    'volunteer'  => __('Волонтерська організація', 'lapki'),
];

$role_labels = [
    'owner'  => __('Власник', 'lapki'),
    'member' => __('Учасник', 'lapki'),
];

// Підписи статусів — з довідника атрибутів (wp_lapki_attributes, entity_type='all',
// attr_name='status'), як і на бейджах карток тварин; 'hold' там ще не засіяний,
// тож лишається фолбеком нижче (той самий патерн, що й у buildCard() теми).
$status_labels = array_column(Lapki_Attributes::get_global_attributes()['status'] ?? [], 'display_name', 'value');
$status_labels += [
    'adoptable' => __('Шукає дім', 'lapki'),
    'adopted'   => __('Прилаштована', 'lapki'),
    'hold'      => __('На утриманні', 'lapki'),
    'found'     => __('Знайдена', 'lapki'),
];
$status_colors = [
    'adoptable' => 'success',
    'adopted'   => 'warning',
    'hold'      => 'info',
    'found'     => 'secondary',
];

// Статуси заявок на прилаштування (wp_lapki_applications.status) — окремий
// довідник від статусів тварини вище, хардкод (4 фіксовані значення в
// Lapki_Application::STATUS_*, не з таблиці атрибутів)
$application_status_labels = [
    'new'       => __('Нова', 'lapki'),
    'contacted' => __("Зв'язались", 'lapki'),
    'approved'  => __('Схвалено', 'lapki'),
    'rejected'  => __('Відхилено', 'lapki'),
];
$application_status_colors = [
    'new'       => 'secondary',
    'contacted' => 'info',
    'approved'  => 'success',
    'rejected'  => 'danger',
];

// Мої тварини — з усіх організацій одразу (search() не підтримує IN(),
// тож для невеликої кількості організацій користувача просто зливаємо результати)
$animals = [];
if ($tab === 'animals' && !empty($my_organizations)) {
    foreach ($my_organizations as $entry) {
        $animals = array_merge($animals, Lapki_Animal::search([
            'organization_id' => $entry['organization']['id'],
            'limit' => 100,
            'order_by' => 'created_at',
            'order' => 'DESC',
        ]));
    }
    usort($animals, function ($a, $b) { return strcmp($b['created_at'], $a['created_at']); });
}

// Мої заявки на прилаштування (я заявник) — усі, незалежно від організації/тварини.
// Кількість — окремим легким COUNT-запитом і завжди (для бейджа в меню, як і
// $incoming_requests_count нижче), самі рядки — лише на вкладці 'applications'
$my_applications_count = Lapki_Application::count_by_user($current_user->ID);
$my_applications = $tab === 'applications' ? Lapki_Application::get_by_user($current_user->ID) : [];

// Список організацій для приєднання — виключно ті, з якими ще немає жодного
// зв'язку (ні активного членства, ні поданої заявки), і без "organization" типу
// individual (це не реальна організація, а мінімальний профіль, автоматично
// створений для приватної особи через /add-animal/ — приєднатись до неї нема сенсу)
$joinable_organizations = [];
if ($tab === 'organizations') {
    $linked_org_ids = array_map('intval', wp_list_pluck(
        Lapki_Organization_Member::get_all_by_user($current_user->ID),
        'organization_id'
    ));
    $all_orgs = Lapki_Organization::search(['limit' => 200]);
    $joinable_organizations = array_values(array_filter($all_orgs, function ($o) use ($linked_org_ids) {
        return !in_array((int) $o['id'], $linked_org_ids, true) && $o['type'] !== 'individual';
    }));
}

$avatar_letter = mb_strtoupper(mb_substr($current_user->display_name, 0, 1));
$avatar_photo = Lapki_Media::get_primary_photo('user', $current_user->ID);
?>

<section class="py-5">
    <div class="container">

        <div class="lapki-profile-header d-flex align-items-center gap-3 mb-4">
            <div class="lapki-profile-avatar">
                <?php if ($avatar_photo) : ?>
                    <img src="<?php echo esc_url($avatar_photo['thumbnail_url'] ?: $avatar_photo['url']); ?>" alt="">
                <?php else : ?>
                    <?php echo esc_html($avatar_letter); ?>
                <?php endif; ?>
            </div>
            <div>
                <h1 class="h4 fw-bold mb-1"><?php echo esc_html($current_user->display_name); ?></h1>
                <p class="text-muted mb-0"><i class="fas fa-envelope"></i> <?php echo esc_html($current_user->user_email); ?></p>
            </div>
        </div>

        <div class="row g-4 lapki-cabinet">
            <div class="col-md-3">
                <div class="list-group lapki-cabinet-nav">
                    <a href="<?php echo esc_url(add_query_arg('tab', 'home')); ?>"
                       class="list-group-item list-group-item-action<?php echo $tab === 'home' ? ' active' : ''; ?>">
                        <i class="fas fa-home me-2"></i><?php esc_html_e('Головна', 'lapki'); ?>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('tab', 'organizations')); ?>"
                       class="list-group-item list-group-item-action<?php echo $tab === 'organizations' ? ' active' : ''; ?>">
                        <i class="fas fa-warehouse me-2"></i><?php esc_html_e('Мої організації', 'lapki'); ?>
                        <?php if ($incoming_requests_count > 0) : ?>
                            <span class="badge bg-danger rounded-pill"><?php echo (int) $incoming_requests_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('tab', 'animals')); ?>"
                       class="list-group-item list-group-item-action<?php echo $tab === 'animals' ? ' active' : ''; ?>">
                        <i class="fas fa-paw me-2"></i><?php esc_html_e('Мої тварини', 'lapki'); ?>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('tab', 'applications')); ?>"
                       class="list-group-item list-group-item-action<?php echo $tab === 'applications' ? ' active' : ''; ?>">
                        <i class="fas fa-file-alt me-2"></i><?php esc_html_e('Заявки на прилаштування', 'lapki'); ?>
                        <?php if ($my_applications_count > 0) : ?>
                            <span class="badge lapki-badge-white rounded-pill"><?php echo (int) $my_applications_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('tab', 'integration')); ?>"
                       class="list-group-item list-group-item-action<?php echo $tab === 'integration' ? ' active' : ''; ?>">
                        <i class="fas fa-code me-2"></i><?php esc_html_e('Інтеграція', 'lapki'); ?>
                    </a>
                    <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>"
                       class="list-group-item list-group-item-action">
                        <i class="fas fa-sign-out-alt me-2"></i><?php esc_html_e('Вихід', 'lapki'); ?>
                    </a>
                </div>
            </div>

            <div class="col-md-9">
                <?php if ($tab === 'home') : ?>

                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <h2 class="h5 fw-bold mb-3"><?php esc_html_e('Інформація про користувача', 'lapki'); ?></h2>
                                <a href="<?php echo esc_url(home_url('/edit-profile/')); ?>" class="btn btn-sm btn-outline-secondary flex-shrink-0">
                                    <i class="fas fa-pen me-1"></i><?php esc_html_e('Редагувати', 'lapki'); ?>
                                </a>
                            </div>
                            <p class="mb-1"><strong><?php esc_html_e("Ім'я:", 'lapki'); ?></strong> <?php echo esc_html($current_user->display_name); ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo esc_html($current_user->user_email); ?></p>
                            <?php $phone = get_user_meta($current_user->ID, 'lapki_phone', true); ?>
                            <?php if ($phone) : ?>
                                <p class="mb-0"><strong><?php esc_html_e('Телефон:', 'lapki'); ?></strong> <?php echo esc_html($phone); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mt-4">
                        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h2 class="h5 fw-bold mb-1"><?php esc_html_e('Мої тварини', 'lapki'); ?></h2>
                                <p class="text-muted small mb-0">
                                    <?php
                                    printf(
                                        /* translators: %d: кількість тварин */
                                        esc_html(_n('%d тварина додана мною', '%d тварин додано мною', $my_animals_total, 'lapki')),
                                        $my_animals_total
                                    );
                                    ?>
                                    <?php if ($my_animals_adoptable > 0) : ?>
                                        · <?php
                                        printf(
                                            /* translators: %d: кількість тварин, що чекають прилаштування */
                                            esc_html(_n('%d чекає прилаштування', '%d чекають прилаштування', $my_animals_adoptable, 'lapki')),
                                            $my_animals_adoptable
                                        );
                                        ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <a href="<?php echo esc_url(add_query_arg('tab', 'animals')); ?>" class="btn btn-sm lapki-btn-orange"><?php esc_html_e('Перейти →', 'lapki'); ?></a>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mt-4">
                        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h2 class="h5 fw-bold mb-1"><?php esc_html_e('Мої організації', 'lapki'); ?></h2>
                                <p class="text-muted small mb-0">
                                    <?php
                                    printf(
                                        /* translators: %d: кількість організацій */
                                        esc_html(_n('%d організація', '%d організацій', count($my_organizations), 'lapki')),
                                        count($my_organizations)
                                    );
                                    ?>
                                    <?php if ($incoming_requests_count > 0) : ?>
                                        · <span class="text-danger fw-semibold">
                                            <?php
                                            printf(
                                                /* translators: %d: кількість нових заявок */
                                                esc_html(_n('%d нова заявка на приєднання', '%d нових заявок на приєднання', $incoming_requests_count, 'lapki')),
                                                (int) $incoming_requests_count
                                            );
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($pending_memberships)) : ?>
                                        · <?php
                                        printf(
                                            /* translators: %d: кількість заявок */
                                            esc_html(_n('%d моя заявка очікує підтвердження', '%d моїх заявок очікує підтвердження', count($pending_memberships), 'lapki')),
                                            count($pending_memberships)
                                        );
                                        ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <a href="<?php echo esc_url(add_query_arg('tab', 'organizations')); ?>" class="btn btn-sm lapki-btn-orange"><?php esc_html_e('Перейти →', 'lapki'); ?></a>
                        </div>
                    </div>

                <?php elseif ($tab === 'organizations') : ?>

                    <?php if (!empty($my_organizations)) : ?>
                        <div class="mb-4">
                            <h2 class="h5 fw-bold mb-3"><?php esc_html_e('Мої організації', 'lapki'); ?></h2>
                            <?php foreach ($my_organizations as $entry) :
                                $organization = $entry['organization'];
                                $membership = $entry['membership'];
                                $is_owner = $membership['role'] === Lapki_Organization_Member::ROLE_OWNER;
                                $other_members = $is_owner ? array_values(array_filter(
                                    Lapki_Organization_Member::get_members($organization['id']),
                                    function ($m) use ($current_user) { return (int) $m['wp_user_id'] !== (int) $current_user->ID; }
                                )) : [];
                                $org_photo_url = !empty($organization['primary_photo']['thumbnail_url']) ? $organization['primary_photo']['thumbnail_url'] : '';
                            ?>
                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-body">
                                        <div class="d-flex gap-3">
                                            <?php if ($org_photo_url) : ?>
                                                <img src="<?php echo esc_url($org_photo_url); ?>" alt="<?php echo esc_attr($organization['name']); ?>" style="width:72px;height:72px;object-fit:cover;border-radius:.5rem;flex-shrink:0;">
                                            <?php endif; ?>
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h3 class="h6 fw-bold mb-0"><?php echo esc_html($organization['name']); ?></h3>
                                                    <span class="badge bg-secondary"><?php echo esc_html($role_labels[$membership['role']] ?? $membership['role']); ?></span>
                                                </div>
                                                <p class="text-muted small mb-2">
                                                    <?php echo esc_html($type_labels[$organization['type']] ?? $organization['type']); ?>
                                                    <?php if (!empty($organization['is_verified'])) : ?>
                                                        <span class="badge bg-success ms-1"><i class="fas fa-check-circle"></i> <?php esc_html_e('Верифіковано', 'lapki'); ?></span>
                                                    <?php endif; ?>
                                                </p>
                                                <?php if (!empty($organization['city'])) : ?><p class="mb-1 small"><i class="fas fa-map-marker-alt"></i> <?php echo esc_html($organization['city']); ?></p><?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <a href="<?php echo esc_url(home_url('/organizations/' . (int) $organization['id'] . '/')); ?>" class="lapki-link-green small">
                                                <?php esc_html_e('Переглянути публічну сторінку →', 'lapki'); ?>
                                            </a>
                                            <?php if (!$is_owner) : ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger lapki-leave-org-btn" data-org-id="<?php echo (int) $organization['id']; ?>"><?php esc_html_e('Залишити організацію', 'lapki'); ?></button>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($is_owner) : ?>
                                            <?php if (!empty($other_members)) : ?>
                                                <hr>
                                                <h4 class="h6 fw-bold small"><?php esc_html_e('Передати право власності', 'lapki'); ?></h4>
                                                <div class="input-group">
                                                    <select class="form-select form-select-sm lapki-transfer-owner-select">
                                                        <?php foreach ($other_members as $m) : ?>
                                                            <option value="<?php echo (int) $m['wp_user_id']; ?>"><?php echo esc_html($m['display_name']); ?> (<?php echo esc_html($m['user_email']); ?>)</option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="button" class="btn btn-sm btn-outline-danger lapki-transfer-owner-btn" data-org-id="<?php echo (int) $organization['id']; ?>"><?php esc_html_e('Передати', 'lapki'); ?></button>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div id="lapki-leave-org-alert" class="alert d-none" role="alert"></div>
                            <div id="lapki-transfer-owner-alert" class="alert d-none" role="alert"></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($incoming_requests)) : ?>
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <h2 class="h5 fw-bold mb-3"><i class="fas fa-bell text-danger me-1"></i><?php esc_html_e('Заявки на приєднання', 'lapki'); ?></h2>
                                <?php foreach ($incoming_requests as $entry) : ?>
                                    <h3 class="h6 fw-bold mt-3"><?php echo esc_html($entry['organization']['name']); ?></h3>
                                    <div class="list-group mb-2">
                                        <?php foreach ($entry['requests'] as $req) : ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center lapki-join-request"
                                                 data-org-id="<?php echo (int) $entry['organization']['id']; ?>"
                                                 data-user-id="<?php echo (int) $req['wp_user_id']; ?>">
                                                <div>
                                                    <div class="fw-semibold"><?php echo esc_html($req['display_name']); ?></div>
                                                    <div class="small text-muted"><?php echo esc_html($req['user_email']); ?></div>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-sm btn-success lapki-approve-request-btn"><?php esc_html_e('Підтвердити', 'lapki'); ?></button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger lapki-reject-request-btn"><?php esc_html_e('Відхилити', 'lapki'); ?></button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                                <div id="lapki-join-request-alert" class="alert d-none" role="alert"></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-1 d-flex flex-wrap gap-2">
                        <button type="button" class="btn lapki-btn-green" data-bs-toggle="modal" data-bs-target="#lapki-join-org-modal">
                            <i class="fas fa-right-to-bracket me-1"></i><?php esc_html_e('Приєднатися до організації', 'lapki'); ?>
                        </button>
                        <a href="<?php echo esc_url(home_url('/add-organization/')); ?>" class="btn lapki-btn-orange">
                            <i class="fas fa-plus me-1"></i><?php esc_html_e('Зареєструвати організацію', 'lapki'); ?>
                        </a>
                    </div>

                    <!-- Модалка приєднання до організації — пошук + список, той самий вміст, що раніше був прямо на сторінці -->
                    <div class="modal fade" id="lapki-join-org-modal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><?php esc_html_e('Приєднатися до організації', 'lapki'); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e('Закрити', 'lapki'); ?>"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="text-muted small"><?php esc_html_e('Оберіть притулок чи ГО зі списку — власнику надійде заявка на підтвердження.', 'lapki'); ?></p>
                                    <input type="text" id="lapki-org-search" class="form-control mb-3" placeholder="<?php esc_attr_e('Пошук за назвою або містом...', 'lapki'); ?>">
                                    <div id="lapki-org-join-alert" class="alert d-none" role="alert"></div>
                                    <div id="lapki-org-list" class="list-group" style="max-height:320px;overflow-y:auto;">
                                        <?php if (empty($joinable_organizations)) : ?>
                                            <p class="text-muted small mb-0"><?php esc_html_e('Немає організацій, до яких можна подати заявку.', 'lapki'); ?></p>
                                        <?php else : foreach ($joinable_organizations as $org) : ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center lapki-org-list-item"
                                                 data-search="<?php echo esc_attr(mb_strtolower($org['name'] . ' ' . ($org['city'] ?? ''))); ?>">
                                                <div>
                                                    <div class="fw-semibold"><?php echo esc_html($org['name']); ?></div>
                                                    <div class="small text-muted">
                                                        <?php echo esc_html($type_labels[$org['type']] ?? $org['type']); ?><?php echo !empty($org['city']) ? ' · ' . esc_html($org['city']) : ''; ?>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-primary lapki-join-org-btn" data-org-id="<?php echo (int) $org['id']; ?>"><?php esc_html_e('Подати заявку', 'lapki'); ?></button>
                                            </div>
                                        <?php endforeach; endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($pending_memberships)) : ?>
                        <div class="mt-4">
                            <h2 class="h5 fw-bold mb-3"><?php esc_html_e('Мої заявки', 'lapki'); ?></h2>
                            <div class="list-group">
                                <?php foreach ($pending_memberships as $m) : ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-semibold"><?php echo esc_html($m['organization_name']); ?></div>
                                            <div class="small text-muted"><?php esc_html_e('Очікує підтвердження власником', 'lapki'); ?></div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary lapki-leave-org-btn" data-org-id="<?php echo (int) $m['organization_id']; ?>"><?php esc_html_e('Скасувати заявку', 'lapki'); ?></button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php elseif ($tab === 'integration') : ?>

                    <h2 class="h5 fw-bold mb-3"><?php esc_html_e('Інтеграція — вбудувати пошук тварин на свій сайт', 'lapki'); ?></h2>

                    <?php if (empty($my_organizations)) : ?>
                        <p class="text-muted"><?php esc_html_e('Спершу зареєструйте або приєднайтесь до організації — код вставки формується для конкретного притулку/ГО.', 'lapki'); ?></p>
                        <a href="<?php echo esc_url(add_query_arg('tab', 'organizations')); ?>" class="btn lapki-btn-orange"><?php esc_html_e('Перейти до організацій', 'lapki'); ?></a>
                    <?php else :
                        $script_base = home_url('/integration/lapki.js');
                        $default_org_id = (int) $my_organizations[0]['organization']['id'];
                        $snippet = '<script src="' . $script_base . '?organization_id=' . $default_org_id . '"></script>';
                    ?>
                        <p class="text-muted small"><?php esc_html_e("Форма пошуку та результати — тварини вашої організації — на будь-якому сайті, одним рядком коду. Колір і набір полів поки що фіксовані (у стилі lapki.help) — конфігуратор кольорів і полів з'явиться пізніше.", 'lapki'); ?></p>

                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <?php if (count($my_organizations) > 1) : ?>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold"><?php esc_html_e('Організація', 'lapki'); ?></label>
                                    <select id="lapki-integration-org-select" class="form-select form-select-sm" style="max-width:320px;">
                                        <?php foreach ($my_organizations as $entry) : ?>
                                            <option value="<?php echo (int) $entry['organization']['id']; ?>"><?php echo esc_html($entry['organization']['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>

                                <label class="form-label small fw-semibold"><?php esc_html_e('Код для вставки', 'lapki'); ?></label>
                                <div class="input-group mb-2">
                                    <input type="text" id="lapki-integration-snippet" class="form-control font-monospace small" readonly
                                           data-script-base="<?php echo esc_attr($script_base); ?>"
                                           value="<?php echo esc_attr($snippet); ?>">
                                    <button type="button" id="lapki-integration-copy" class="btn btn-outline-secondary"><?php esc_html_e('Скопіювати', 'lapki'); ?></button>
                                </div>
                                <div id="lapki-integration-copy-alert" class="small text-success d-none"><?php esc_html_e('Скопійовано!', 'lapki'); ?></div>
                                <p class="text-muted small mb-0"><?php esc_html_e('Вставте цей рядок у HTML сторінки, де має з\'явитись форма пошуку й тварини вашої організації.', 'lapki'); ?></p>
                            </div>
                        </div>

                        <h3 class="h6 fw-bold mb-2"><?php esc_html_e("Живе прев'ю", 'lapki'); ?></h3>
                        <p class="text-muted small"><?php esc_html_e('Так це виглядатиме на сторонньому сайті (без шапки, футера й меню lapki.help) — контент вбудовується прямо в сторінку, висота підлаштовується під нього автоматично:', 'lapki'); ?></p>
                        <div class="border rounded p-3" style="background:#f8f9fc;">
                            <div id="lapki-integration-preview"
                                 data-script-base="<?php echo esc_attr($script_base); ?>"
                                 data-org-id="<?php echo (int) $default_org_id; ?>"></div>
                        </div>
                    <?php endif; ?>

                <?php elseif ($tab === 'animals') : ?>

                    <h2 class="h5 fw-bold mb-3"><?php esc_html_e('Мої тварини', 'lapki'); ?></h2>

                    <?php if (empty($my_organizations)) : ?>
                        <p class="text-muted"><?php esc_html_e('У вас поки немає організації. Приєднайтесь до існуючої або зареєструйте свою на вкладці «Мої організації».', 'lapki'); ?></p>
                    <?php elseif (empty($animals)) : ?>
                        <p class="text-muted"><?php esc_html_e('Тварин поки немає.', 'lapki'); ?></p>
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

                <?php elseif ($tab === 'applications') : ?>

                    <h2 class="h5 fw-bold mb-3"><?php esc_html_e('Заявки на прилаштування', 'lapki'); ?></h2>

                    <?php if (empty($my_applications)) : ?>
                        <p class="text-muted"><?php esc_html_e('Ви ще не подавали заявок на прилаштування. Заявку можна надіслати зі сторінки будь-якої тварини кнопкою «Хочу прилаштувати».', 'lapki'); ?></p>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0 lapki-boxed-rows">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Тварина', 'lapki'); ?></th>
                                        <th><?php esc_html_e('Організація', 'lapki'); ?></th>
                                        <th><?php esc_html_e('Дата', 'lapki'); ?></th>
                                        <th><?php esc_html_e('Статус', 'lapki'); ?></th>
                                        <th class="text-end"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($my_applications as $app) :
                                        $app_photo = $app['animal_id'] ? Lapki_Media::get_primary_photo('animal', $app['animal_id']) : null;
                                        $app_photo_url = $app_photo ? ($app_photo['thumbnail_url'] ?: $app_photo['url']) : '';
                                        $app_status = $app['status'];
                                    ?>
                                        <tr class="lapki-application-row" style="cursor:pointer;" role="button" tabindex="0"
                                            data-bs-toggle="modal" data-bs-target="#lapki-my-application-modal"
                                            data-animal="<?php echo esc_attr($app['animal_name'] ?: __('Тварину видалено', 'lapki')); ?>"
                                            data-name="<?php echo esc_attr($app['applicant_name']); ?>"
                                            data-email="<?php echo esc_attr($app['applicant_email']); ?>"
                                            data-phone="<?php echo esc_attr($app['applicant_phone']); ?>"
                                            data-message="<?php echo esc_attr($app['message']); ?>"
                                            data-status-label="<?php echo esc_attr($application_status_labels[$app_status] ?? $app_status); ?>"
                                            data-status-color="<?php echo esc_attr($application_status_colors[$app_status] ?? 'secondary'); ?>">
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <?php if ($app_photo_url) : ?>
                                                        <img src="<?php echo esc_url($app_photo_url); ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:.375rem;flex-shrink:0;">
                                                    <?php endif; ?>
                                                    <span class="fw-semibold"><?php echo esc_html($app['animal_name'] ?: __('Тварину видалено', 'lapki')); ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo esc_html($app['organization_name'] ?: '—'); ?></td>
                                            <td class="text-muted small"><?php echo esc_html(mysql2date(get_option('date_format'), $app['created_at'])); ?></td>
                                            <td><span class="badge bg-<?php echo esc_attr($application_status_colors[$app_status] ?? 'secondary'); ?>"><?php echo esc_html($application_status_labels[$app_status] ?? $app_status); ?></span></td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0 lapki-delete-application-btn"
                                                        data-app-id="<?php echo (int) $app['id']; ?>"
                                                        title="<?php esc_attr_e('Видалити заявку', 'lapki'); ?>">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div id="lapki-applications-alert" class="alert d-none mt-3" role="alert"></div>

                        <!-- Модалка перегляду заявки — та сама структура, що й форма заявки на /animals/{id}/, тільки текст замість інпутів -->
                        <div class="modal fade" id="lapki-my-application-modal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><?php esc_html_e('Заявка на усиновлення — ', 'lapki'); ?><span id="lapki-my-application-animal"></span></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e('Закрити', 'lapki'); ?>"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <div class="form-label"><?php esc_html_e("Ваше ім'я", 'lapki'); ?></div>
                                            <p class="mb-0" id="lapki-my-application-name"></p>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-label"><?php esc_html_e('Email', 'lapki'); ?></div>
                                            <p class="mb-0" id="lapki-my-application-email"></p>
                                        </div>
                                        <div class="mb-3" id="lapki-my-application-phone-wrap">
                                            <div class="form-label"><?php esc_html_e('Телефон', 'lapki'); ?></div>
                                            <p class="mb-0" id="lapki-my-application-phone"></p>
                                        </div>
                                        <div class="mb-3" id="lapki-my-application-message-wrap">
                                            <div class="form-label"><?php esc_html_e('Повідомлення', 'lapki'); ?></div>
                                            <p class="mb-0" id="lapki-my-application-message"></p>
                                        </div>
                                        <div class="mb-0">
                                            <div class="form-label"><?php esc_html_e('Статус', 'lapki'); ?></div>
                                            <span class="badge" id="lapki-my-application-status"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
