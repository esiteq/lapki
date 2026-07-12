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
    echo '<section class="py-5"><div class="container text-center"><h1 class="h3">Організацію не знайдено</h1>';
    echo '<p><a href="' . esc_url(home_url('/organizations/')) . '" class="lapki-link-green">← Всі організації</a></p></div></section>';
    get_footer();
    return;
}

get_header();

$type_labels = [
    'individual' => 'Приватна особа',
    'shelter' => 'Притулок',
    'rescue' => 'Волонтерська організація',
    'vet_clinic' => 'Ветклініка',
];

$animals = Lapki_Animal::search(['organization_id' => $organization['id'], 'status' => '', 'limit' => 24]);
?>

<section class="py-5">
    <div class="container">
        <p class="mb-3"><a href="<?php echo esc_url(home_url('/organizations/')); ?>" class="lapki-link-green small">← Всі організації</a></p>

        <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
            <h1 class="fw-bold mb-0"><?php echo esc_html($organization['name']); ?></h1>
            <?php if (!empty($organization['is_verified'])) : ?>
                <span class="badge bg-success"><i class="fas fa-check-circle"></i> Верифіковано</span>
            <?php endif; ?>
        </div>

        <p class="text-muted mb-4"><?php echo esc_html($type_labels[$organization['type']] ?? $organization['type']); ?></p>

        <div class="row g-5">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3">Контакти</h2>
                        <?php if (!empty($organization['email'])) : ?>
                            <p class="small mb-2"><i class="fas fa-envelope"></i> <a href="mailto:<?php echo esc_attr($organization['email']); ?>"><?php echo esc_html($organization['email']); ?></a></p>
                        <?php endif; ?>
                        <?php if (!empty($organization['phone'])) : ?>
                            <p class="small mb-2"><i class="fas fa-phone"></i> <?php echo esc_html($organization['phone']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($organization['website'])) : ?>
                            <p class="small mb-2"><i class="fas fa-globe"></i> <a href="<?php echo esc_url($organization['website']); ?>" target="_blank" rel="noopener">Сайт організації</a></p>
                        <?php endif; ?>
                        <?php if (!empty($organization['city'])) : ?>
                            <p class="small mb-0"><i class="fas fa-map-marker-alt"></i> <?php echo esc_html($organization['city']); ?><?php echo !empty($organization['state']) ? ', ' . esc_html($organization['state']) : ''; ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($organization['mission_statement'])) : ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-2">Місія</h2>
                        <p class="small mb-0"><?php echo nl2br(esc_html($organization['mission_statement'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($organization['adoption_policy'])) : ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-2">Політика усиновлення</h2>
                        <p class="small mb-0"><?php echo nl2br(esc_html($organization['adoption_policy'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-8">
                <h2 class="h5 fw-bold mb-4">Тварини цієї організації (<?php echo count($animals); ?>)</h2>
                <div class="row g-4">
                    <?php if (empty($animals)) : ?>
                        <p class="text-muted">Наразі немає тварин на прилаштування.</p>
                    <?php else : foreach ($animals as $animal) :
                        $photo_url = !empty($animal['primary_photo']['thumbnail_url']) ? $animal['primary_photo']['thumbnail_url'] : '';
                        ?>
                        <div class="col-6 col-md-4">
                            <a href="<?php echo esc_url(home_url('/animals/' . (int) $animal['id'] . '/')); ?>" class="lapki-card">
                                <div class="lapki-card__img">
                                    <?php if ($photo_url) : ?>
                                        <img src="<?php echo esc_url($photo_url); ?>" alt="<?php echo esc_attr($animal['name']); ?>" loading="lazy">
                                    <?php else : ?>
                                        <div class="text-muted" style="width:100%;height:100%;background:#e8e8e8;display:flex;align-items:center;justify-content:center;font-size:3rem"><i class="fas fa-paw"></i></div>
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
