<?php
/**
 * Список організацій — /organizations/
 *
 * Тема може перевизначити цей шаблон: скопіювати у
 * wp-content/themes/{тема}/lapki/archive-organizations.php
 *
 * @package Lapki
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$selected_city = isset($_GET['city']) ? sanitize_text_field(wp_unslash($_GET['city'])) : '';
$cities = Lapki_Organization::get_cities_with_counts();

$organizations = Lapki_Organization::search([
    'limit' => 50,
    'city' => $selected_city,
]);

$type_labels = [
    'individual' => 'Приватна особа',
    'shelter' => 'Притулок',
    'rescue' => 'Волонтерська організація',
    'vet_clinic' => 'Ветклініка',
];

// Циклічна палітра кольорів боксів — та сама, що й для тегів картки тварини
$tag_colors = ['dog', 'cat', 'bird', 'rabbit', 'age', 'org', 'location'];
$pin_svg = '<svg class="lapki-pin-icon" viewBox="0 0 24 24" width="12" height="12" aria-hidden="true"><path fill="#EA4335" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>';
?>

<section class="py-5">
    <div class="container">
        <h1 class="h3 fw-bold mb-4">Організації та притулки</h1>

        <?php if (!empty($cities)) : ?>
        <div class="d-flex flex-wrap gap-2 mb-4">
            <a href="<?php echo esc_url(remove_query_arg('city')); ?>"
               class="lapki-card__tag lapki-card__tag--filter lapki-card__tag--location<?php echo $selected_city === '' ? ' is-active' : ''; ?>">
                Всі міста
            </a>
            <?php foreach ($cities as $i => $c) :
                $color = $tag_colors[$i % count($tag_colors)];
                $is_active = ($selected_city !== '' && $selected_city === $c['city']);
            ?>
                <a href="<?php echo esc_url(add_query_arg('city', rawurlencode($c['city']))); ?>"
                   class="lapki-card__tag lapki-card__tag--filter lapki-card__tag--<?php echo esc_attr($color); ?><?php echo $is_active ? ' is-active' : ''; ?>">
                    <?php echo $pin_svg; ?><?php echo esc_html($c['city']); ?> (<?php echo (int) $c['count']; ?>)
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php if (empty($organizations)) : ?>
                <p class="text-muted">
                    <?php echo $selected_city !== ''
                        ? 'У місті ' . esc_html($selected_city) . ' організацій поки немає.'
                        : 'Організацій поки немає.'; ?>
                </p>
            <?php else : foreach ($organizations as $org) : ?>
                <div class="col-md-6 col-lg-4">
                    <a href="<?php echo esc_url(home_url('/organizations/' . (int) $org['id'] . '/')); ?>" class="card border-0 shadow-sm h-100 text-decoration-none">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h2 class="h6 fw-bold mb-0"><?php echo esc_html($org['name']); ?></h2>
                                <?php if (!empty($org['is_verified'])) : ?>
                                    <span class="badge bg-success"><i class="fas fa-check"></i></span>
                                <?php endif; ?>
                            </div>
                            <p class="small text-muted mb-2"><?php echo esc_html($type_labels[$org['type']] ?? $org['type']); ?></p>
                            <?php if (!empty($org['city'])) : ?>
                                <p class="small text-muted mb-2"><i class="fas fa-map-marker-alt"></i> <?php echo esc_html($org['city']); ?></p>
                            <?php endif; ?>
                            <p class="small mb-0"><?php echo (int) $org['animals_count']; ?> тварин на прилаштування</p>
                        </div>
                    </a>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</section>

<?php get_footer(); ?>
