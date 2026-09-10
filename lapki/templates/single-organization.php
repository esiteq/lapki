<?php
/**
 * Сторінка організації/притулку — /organizations/{id}/
 *
 * Тема може перевизначити цей шаблон: скопіювати у
 * wp-content/themes/{тема}/lapki/single-organization.php
 *
 * @package Lapki
 */

if (!defined('ABSPATH')) {
    exit;
}

$org_id = Lapki_Frontend::get_current_organization_id();
$organization = $org_id ? Lapki_Organization::get($org_id) : null;

if (!$organization) {
    status_header(404);
    get_header();
    echo '<section class="py-5"><div class="container text-center"><h1 class="h3">' . esc_html__('Організацію не знайдено', 'lapki') . '</h1>';
    echo '<p><a href="' . esc_url(home_url('/organizations/')) . '" class="lapki-link-green">' . esc_html__('← Всі організації', 'lapki') . '</a></p></div></section>';
    get_footer();
    return;
}

get_header();

$type_labels = [
    'individual' => __('Приватна особа', 'lapki'),
    'shelter' => __('Притулок', 'lapki'),
    'rescue' => __('Волонтерська організація', 'lapki'),
    'vet_clinic' => __('Ветклініка', 'lapki'),
];

$animals = Lapki_Animal::search(['organization_id' => $organization['id'], 'status' => '', 'limit' => 24]);

// Стрічка-бейдж статусу на компактних картках нижче — з довідника атрибутів
// (wp_lapki_attributes), той самий текст/колір, що й на .lapki-card__badge
// у buildCard() теми; тут статус завжди присутній (search() без фільтра
// status повертає всі, включно з adopted/found), тому бейдж потрібен.
$org_animal_status_labels = array_column(Lapki_Attributes::get_global_attributes()['status'] ?? [], 'display_name', 'value');
$org_animal_badge_modifiers = ['adopted', 'found', 'hold'];

// Фото/відео притулку — зберігаються окремо від фото тварин, показуються
// окремо від грід-списку тварин (у лівій панелі, не серед карток тварин)
$org_photos = array_values(array_filter($organization['media'] ?? [], function ($m) {
    return $m['media_type'] === 'photo';
}));
$org_videos = array_values(array_filter($organization['media'] ?? [], function ($m) {
    return $m['media_type'] === 'video';
}));
?>

<section class="py-5">
    <div class="container">
        <p class="mb-3"><a href="<?php echo esc_url(home_url('/organizations/')); ?>" class="lapki-link-green small">← <?php esc_html_e('Всі організації', 'lapki'); ?></a></p>

        <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
            <h1 class="fw-bold mb-0"><?php echo esc_html($organization['name']); ?></h1>
            <?php if (!empty($organization['is_verified'])) : ?>
                <span class="badge bg-success"><i class="fas fa-check-circle"></i> <?php esc_html_e('Верифіковано', 'lapki'); ?></span>
            <?php endif; ?>
        </div>

        <div class="row g-5 align-items-end mb-4">
            <div class="col-lg-4">
                <p class="text-muted mb-0"><?php echo esc_html($type_labels[$organization['type']] ?? $organization['type']); ?></p>
            </div>
            <div class="col-lg-8">
                <h2 class="h5 fw-bold mb-0">
                    <?php
                    /* translators: %d: кількість тварин */
                    printf(esc_html__('Тварини цієї організації (%d)', 'lapki'), count($animals));
                    ?>
                </h2>
            </div>
        </div>

        <div class="row g-5">
            <div class="col-lg-4">
                <?php if (!empty($org_photos)) :
                    $main_photo = $org_photos[0];
                    $thumb_photos = array_slice($org_photos, 1);
                ?>
                <div class="mb-4">
                    <a href="<?php echo esc_url($main_photo['url']); ?>" class="glightbox" data-gallery="org-<?php echo (int) $organization['id']; ?>" style="display:block;border-radius:8px;overflow:hidden;">
                        <img src="<?php echo esc_url($main_photo['url']); ?>" alt="<?php echo esc_attr($organization['name']); ?>" style="width:100%;aspect-ratio:4/3;object-fit:cover;display:block;">
                    </a>
                    <?php if (!empty($thumb_photos)) : ?>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <?php foreach ($thumb_photos as $photo) : ?>
                            <a href="<?php echo esc_url($photo['url']); ?>" class="glightbox" data-gallery="org-<?php echo (int) $organization['id']; ?>" style="width:70px;height:70px;display:block;border-radius:4px;overflow:hidden;">
                                <img src="<?php echo esc_url($photo['thumbnail_url'] ?: $photo['url']); ?>" alt="<?php echo esc_attr($organization['name']); ?>" style="width:100%;height:100%;object-fit:cover;" loading="lazy">
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($org_videos)) : ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3"><?php esc_html_e('Відео', 'lapki'); ?></h2>
                        <div class="d-flex flex-column gap-1">
                            <?php foreach ($org_videos as $video) : ?>
                                <a href="<?php echo esc_url($video['url']); ?>" target="_blank" rel="noopener" class="small">
                                    <i class="fas fa-play-circle"></i> <?php echo esc_html($video['title'] ?: __('Відео', 'lapki')); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3"><?php esc_html_e('Контакти', 'lapki'); ?></h2>
                        <?php if (!empty($organization['email'])) : ?>
                            <p class="small mb-2"><i class="fas fa-envelope"></i> <a href="mailto:<?php echo esc_attr($organization['email']); ?>"><?php echo esc_html($organization['email']); ?></a></p>
                        <?php endif; ?>
                        <?php if (!empty($organization['phone'])) : ?>
                            <p class="small mb-2"><i class="fas fa-phone"></i> <?php echo esc_html($organization['phone']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($organization['website'])) : ?>
                            <p class="small mb-2"><i class="fas fa-globe"></i> <a href="<?php echo esc_url($organization['website']); ?>" target="_blank" rel="noopener"><?php esc_html_e('Сайт організації', 'lapki'); ?></a></p>
                        <?php endif; ?>
                        <?php if (!empty($organization['city'])) : ?>
                            <?php
                            // Кешований підпис (city_display) заповнюється при create()/
                            // update() — Lapki_Organization::maybe_fill_geo_from_city().
                            // Фолбек на живе обчислення — на випадок запису без кешу.
                            $city_display = !empty($organization['city_display'])
                                ? $organization['city_display']
                                : Lapki_Main::format_city_location($organization['city'], $organization['city_katottg'] ?? '', $organization['state'] ?? '');
                            ?>
                            <p class="small mb-0"><i class="fas fa-map-marker-alt"></i> <?php echo esc_html($city_display); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($organization['mission_statement'])) : ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-2"><?php esc_html_e('Місія', 'lapki'); ?></h2>
                        <p class="small mb-0"><?php echo nl2br(esc_html($organization['mission_statement'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($organization['adoption_policy'])) : ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-2"><?php esc_html_e('Політика усиновлення', 'lapki'); ?></h2>
                        <p class="small mb-0"><?php echo nl2br(esc_html($organization['adoption_policy'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-8">
                <div class="row g-4">
                    <?php if (empty($animals)) : ?>
                        <p class="text-muted"><?php esc_html_e('Наразі немає тварин на прилаштування.', 'lapki'); ?></p>
                    <?php else : foreach ($animals as $animal) :
                        $photo_url = !empty($animal['primary_photo']['thumbnail_url']) ? $animal['primary_photo']['thumbnail_url'] : '';
                        ?>
                        <div class="col-6 col-md-4">
                            <?php
                            $org_animal_badge_text = $org_animal_status_labels[$animal['status']] ?? $animal['status'];
                            $org_animal_badge_class = 'lapki-card__badge' . (in_array($animal['status'], $org_animal_badge_modifiers, true) ? ' lapki-card__badge--' . $animal['status'] : '');
                            ?>
                            <a href="<?php echo esc_url(home_url('/animals/' . (int) $animal['id'] . '/')); ?>" class="lapki-card lapki-card--compact">
                                <div class="lapki-card__img">
                                    <?php if ($photo_url) : ?>
                                        <img src="<?php echo esc_url($photo_url); ?>" alt="<?php echo esc_attr($animal['name']); ?>" loading="lazy">
                                    <?php else : ?>
                                        <div class="text-muted" style="width:100%;height:100%;background:#e8e8e8;display:flex;align-items:center;justify-content:center;font-size:3rem"><i class="fas fa-paw"></i></div>
                                    <?php endif; ?>
                                    <?php if ($org_animal_badge_text) : ?>
                                        <span class="<?php echo esc_attr($org_animal_badge_class); ?>"><?php echo esc_html($org_animal_badge_text); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="lapki-card__body">
                                    <div class="lapki-card__name"><?php echo esc_html($animal['name']); ?></div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
