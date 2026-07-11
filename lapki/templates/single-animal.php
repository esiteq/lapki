<?php
/**
 * Сторінка однієї тварини — /animals/{id}/
 *
 * Тема може перевизначити цей шаблон: скопіювати у
 * wp-content/themes/{тема}/lapki/single-animal.php
 *
 * @package Lapki
 */

if (!defined('ABSPATH')) {
    exit;
}

$animal_id = Lapki_Frontend::get_current_animal_id();
$animal = $animal_id ? Lapki_Animal::get($animal_id) : null;

if (!$animal) {
    status_header(404);
    get_header();
    echo '<section class="py-5"><div class="container text-center"><h1 class="h3">Тварину не знайдено</h1>';
    echo '<p><a href="' . esc_url(home_url('/animals/')) . '" class="lapki-link-green">← Всі тварини</a></p></div></section>';
    get_footer();
    return;
}

get_header();

$type_labels   = ['dog' => 'Собака', 'cat' => 'Кіт', 'bird' => 'Птах', 'rabbit' => 'Кролик', 'other' => 'Інше'];
$age_labels    = ['baby' => 'Малюк', 'young' => 'Молодий', 'adult' => 'Дорослий', 'senior' => 'Похилого віку'];
$gender_labels = ['male' => 'Самець', 'female' => 'Самка', 'unknown' => 'Невідомо'];
$size_labels   = ['small' => 'Малий', 'medium' => 'Середній', 'large' => 'Великий', 'xlarge' => 'Дуже великий'];

$photos = !empty($animal['media']) ? array_values(array_filter($animal['media'], function ($m) {
    return $m['media_type'] === 'photo';
})) : [];

$main_photo = !empty($photos[0]['url']) ? $photos[0]['url'] : '';
?>

<section class="py-5">
    <div class="container">
        <p class="mb-3"><a href="<?php echo esc_url(home_url('/animals/')); ?>" class="lapki-link-green small">← Всі тварини</a></p>

        <div class="row g-5">
            <div class="col-lg-6">
                <div class="lapki-animal-gallery">
                    <div class="lapki-animal-gallery__main mb-3">
                        <?php if ($main_photo) : ?>
                            <img id="lapki-gallery-main-img" src="<?php echo esc_url($main_photo); ?>" alt="<?php echo esc_attr($animal['name']); ?>" class="img-fluid rounded-4 w-100" style="aspect-ratio:4/3;object-fit:cover;">
                        <?php else : ?>
                            <div class="rounded-4 w-100 d-flex align-items-center justify-content-center bg-light" style="aspect-ratio:4/3;font-size:4rem;">🐾</div>
                        <?php endif; ?>
                    </div>
                    <?php if (count($photos) > 1) : ?>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php foreach ($photos as $photo) : ?>
                            <img src="<?php echo esc_url($photo['thumbnail_url'] ?: $photo['url']); ?>"
                                 data-full="<?php echo esc_url($photo['url']); ?>"
                                 class="lapki-animal-gallery__thumb rounded-3"
                                 style="width:72px;height:72px;object-fit:cover;cursor:pointer;"
                                 alt="<?php echo esc_attr($animal['name']); ?>">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-6">
                <h1 class="fw-bold mb-2"><?php echo esc_html($animal['name']); ?></h1>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="lapki-card__tag"><?php echo esc_html($type_labels[$animal['type']] ?? $animal['type']); ?></span>
                    <?php if (!empty($animal['age'])) : ?><span class="lapki-card__tag"><?php echo esc_html($age_labels[$animal['age']] ?? $animal['age']); ?></span><?php endif; ?>
                    <?php if (!empty($animal['gender'])) : ?><span class="lapki-card__tag"><?php echo esc_html($gender_labels[$animal['gender']] ?? $animal['gender']); ?></span><?php endif; ?>
                    <?php if (!empty($animal['size'])) : ?><span class="lapki-card__tag"><?php echo esc_html($size_labels[$animal['size']] ?? $animal['size']); ?></span><?php endif; ?>
                </div>

                <?php if (!empty($animal['address_city'])) : ?>
                <p class="text-muted mb-3">📍 <?php echo esc_html($animal['address_city']); ?><?php echo !empty($animal['address_state']) ? ', ' . esc_html($animal['address_state']) : ''; ?></p>
                <?php endif; ?>

                <?php if (!empty($animal['description'])) : ?>
                <p class="mb-4"><?php echo nl2br(esc_html($animal['description'])); ?></p>
                <?php endif; ?>

                <div class="row g-2 mb-4">
                    <?php
                    $compat = [
                        'spayed_neutered' => 'Стерилізована/кастрований',
                        'house_trained' => 'Привчена до туалету',
                        'shots_current' => 'Щеплення зроблено',
                        'good_with_children' => 'Добре з дітьми',
                        'good_with_dogs' => 'Добре з собаками',
                        'good_with_cats' => 'Добре з котами',
                        'special_needs' => 'Особливі потреби',
                    ];
                    foreach ($compat as $key => $label) :
                        if (!isset($animal[$key]) || $animal[$key] === null || $animal[$key] === '') continue;
                        $yes = (bool) $animal[$key];
                        ?>
                        <div class="col-6">
                            <span class="small"><?php echo $yes ? '✅' : '❌'; ?> <?php echo esc_html($label); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($animal['organization_id'])) : ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Притулок / організація</div>
                        <a href="<?php echo esc_url(home_url('/organizations/' . (int) $animal['organization_id'] . '/')); ?>" class="fw-semibold lapki-link-green">
                            <?php echo esc_html($animal['organization_name'] ?? ''); ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <button type="button" class="btn lapki-btn-orange btn-lg w-100" data-bs-toggle="modal" data-bs-target="#lapki-application-modal">
                    Хочу прилаштувати
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Модалка форми заявки на усиновлення -->
<div class="modal fade" id="lapki-application-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Заявка на усиновлення — <?php echo esc_html($animal['name']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>
            <div class="modal-body">
                <form id="lapki-application-form">
                    <input type="hidden" name="animal_id" value="<?php echo (int) $animal['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Ваше ім'я *</label>
                        <input type="text" name="applicant_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="applicant_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Телефон</label>
                        <input type="tel" name="applicant_phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Повідомлення</label>
                        <textarea name="message" class="form-control" rows="3" placeholder="Розкажіть трохи про себе та умови для тварини"></textarea>
                    </div>
                    <div id="lapki-application-alert" class="alert d-none" role="alert"></div>
                    <button type="submit" class="btn lapki-btn-green w-100">Надіслати заявку</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
