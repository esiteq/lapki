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
    echo '<section class="py-5"><div class="container text-center"><h1 class="h3">' . esc_html__('Тварину не знайдено', 'lapki') . '</h1>';
    echo '<p><a href="' . esc_url(home_url('/animals/')) . '" class="lapki-link-green">' . esc_html__('← Всі тварини', 'lapki') . '</a></p></div></section>';
    get_footer();
    return;
}

get_header();

$type_icons    = ['dog' => 'fa-dog', 'cat' => 'fa-cat', 'bird' => 'fa-dove', 'rabbit' => 'fa-paw', 'other' => 'fa-paw'];
$age_labels    = ['baby' => __('Малюк', 'lapki'), 'young' => __('Молодий', 'lapki'), 'adult' => __('Дорослий', 'lapki'), 'senior' => __('Похилого віку', 'lapki')];
$gender_labels = ['male' => __('Самець', 'lapki'), 'female' => __('Самка', 'lapki'), 'unknown' => __('Невідомо', 'lapki')];
$size_labels   = ['small' => __('Малий', 'lapki'), 'medium' => __('Середній', 'lapki'), 'large' => __('Великий', 'lapki'), 'xlarge' => __('Дуже великий', 'lapki')];

$photos = !empty($animal['media']) ? array_values(array_filter($animal['media'], function ($m) {
    return $m['media_type'] === 'photo';
})) : [];

$main_photo = !empty($photos[0]['url']) ? $photos[0]['url'] : '';

// Контакти організації — для модалки донату (окремий SELECT, бо Lapki_Animal::get()
// підтягує лише organization_name/organization_type через JOIN, без phone/email)
$donate_organization = !empty($animal['organization_id']) ? Lapki_Organization::get($animal['organization_id']) : null;
?>

<section class="py-5">
    <div class="container">
        <p class="mb-3"><a href="<?php echo esc_url(home_url('/animals/')); ?>" class="lapki-link-green small">← <?php esc_html_e('Всі тварини', 'lapki'); ?></a></p>

        <div class="row g-5">
            <div class="col-lg-6">
                <div class="lapki-animal-gallery">
                    <div class="lapki-animal-gallery__main mb-3">
                        <?php if ($main_photo) : ?>
                            <a href="<?php echo esc_url($main_photo); ?>" class="glightbox" data-gallery="animal-<?php echo (int) $animal['id']; ?>">
                                <img id="lapki-gallery-main-img" src="<?php echo esc_url($main_photo); ?>" alt="<?php echo esc_attr($animal['name']); ?>" class="img-fluid rounded-4 w-100" style="aspect-ratio:4/3;object-fit:cover;">
                            </a>
                        <?php else : ?>
                            <div class="rounded-4 w-100 d-flex align-items-center justify-content-center bg-light text-muted" style="aspect-ratio:4/3;font-size:4rem;"><i class="fas fa-paw"></i></div>
                        <?php endif; ?>
                    </div>
                    <?php if (count($photos) > 1) : ?>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php foreach ($photos as $photo) : ?>
                            <a href="<?php echo esc_url($photo['url']); ?>" class="glightbox" data-gallery="animal-<?php echo (int) $animal['id']; ?>">
                                <img src="<?php echo esc_url($photo['thumbnail_url'] ?: $photo['url']); ?>"
                                     class="lapki-animal-gallery__thumb rounded-3"
                                     style="width:72px;height:72px;object-fit:cover;cursor:pointer;"
                                     alt="<?php echo esc_attr($animal['name']); ?>">
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-6">
                <h1 class="fw-bold mb-2">
                    <?php echo esc_html($animal['name']); ?>
                    <?php if (is_user_logged_in() && !empty($animal['organization_id']) && Lapki_Roles::user_owns_organization($animal['organization_id'], get_current_user_id())) : ?>
                        <a href="<?php echo esc_url(home_url('/add-animal/?id=' . (int) $animal['id'])); ?>" class="btn btn-outline-secondary btn-sm align-middle ms-2">
                            <i class="fas fa-pen me-1"></i><?php esc_html_e('Редагувати', 'lapki'); ?>
                        </a>
                    <?php endif; ?>
                </h1>

                <?php
                // Обов'язкові "на видноті" характеристики: вік, стать, місто,
                // стерилізована, вакцинована, розмір (+ тип — вже був тут раніше)
                $spayed_set = isset($animal['spayed_neutered']) && $animal['spayed_neutered'] !== null && $animal['spayed_neutered'] !== '';
                $shots_set  = isset($animal['shots_current']) && $animal['shots_current'] !== null && $animal['shots_current'] !== '';
                ?>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="lapki-card__tag"><i class="fas <?php echo esc_attr($type_icons[$animal['type']] ?? 'fa-paw'); ?>"></i> <?php echo esc_html(Lapki_Main::get_animal_type_label($animal['type'], $animal['gender'] ?? '', true)); ?></span>
                    <?php if (!empty($animal['age'])) : ?><span class="lapki-card__tag lapki-card__tag--age"><?php echo esc_html($age_labels[$animal['age']] ?? $animal['age']); ?></span><?php endif; ?>
                    <?php if (!empty($animal['gender'])) : ?><span class="lapki-card__tag lapki-card__tag--gender"><?php echo esc_html($gender_labels[$animal['gender']] ?? $animal['gender']); ?></span><?php endif; ?>
                    <?php if (!empty($animal['address_city'])) : ?>
                        <?php
                        // Кешований підпис (address_city_display) заповнюється при
                        // create()/update() — Lapki_Animal::maybe_fill_geo_from_city().
                        // Фолбек на живе обчислення лишається на випадок запису без
                        // кешу (напр. до backfill-міграції).
                        $city_display = !empty($animal['address_city_display'])
                            ? $animal['address_city_display']
                            : Lapki_Main::format_city_location($animal['address_city'], $animal['address_city_katottg'] ?? '', $animal['address_state'] ?? '');
                        ?>
                        <span class="lapki-card__tag lapki-card__tag--location"><i class="fas fa-map-marker-alt"></i> <?php echo esc_html($city_display); ?></span>
                    <?php endif; ?>
                    <?php if ($spayed_set && (bool) $animal['spayed_neutered']) : ?>
                        <span class="lapki-card__tag lapki-card__tag--yes"><i class="fas fa-check"></i> <?php esc_html_e('Стерилізована', 'lapki'); ?></span>
                    <?php endif; ?>
                    <?php if ($shots_set && (bool) $animal['shots_current']) : ?>
                        <span class="lapki-card__tag lapki-card__tag--yes"><i class="fas fa-check"></i> <?php esc_html_e('Вакцинована', 'lapki'); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($animal['size'])) : ?><span class="lapki-card__tag lapki-card__tag--size"><?php echo esc_html($size_labels[$animal['size']] ?? $animal['size']); ?></span><?php endif; ?>
                </div>

                <?php if (!empty($animal['description'])) : ?>
                <p class="mb-4"><?php echo nl2br(esc_html($animal['description'])); ?></p>
                <?php endif; ?>

                <div class="row g-2 mb-4">
                    <?php
                    $compat = [
                        'house_trained' => __('Привчена до туалету', 'lapki'),
                        'good_with_children' => __('Добре з дітьми', 'lapki'),
                        'good_with_dogs' => __('Добре з собаками', 'lapki'),
                        'good_with_cats' => __('Добре з котами', 'lapki'),
                        'special_needs' => __('Особливі потреби', 'lapki'),
                        'from_war_zone' => __('З зони бойових дій', 'lapki'),
                    ];
                    foreach ($compat as $key => $label) :
                        if (empty($animal[$key])) continue;
                        ?>
                        <div class="col-6">
                            <span class="small"><i class="fas fa-check text-success"></i> <?php echo esc_html($label); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($animal['additional_attributes'])) : ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="text-muted small mb-2"><?php esc_html_e('Додаткова інформація', 'lapki'); ?></div>
                        <div class="row g-2">
                            <?php foreach ($animal['additional_attributes'] as $attr_name => $attr_value) :
                                if ($attr_value === '' || $attr_value === null) continue;
                                $attr_label = ucfirst(str_replace('_', ' ', $attr_name));
                                $attr_display = Lapki_Attributes::get_attribute_display($animal['type'], $attr_name, $attr_value);
                            ?>
                                <div class="col-6">
                                    <span class="small text-muted"><?php echo esc_html($attr_label); ?>:</span>
                                    <span class="small fw-semibold"><?php echo esc_html($attr_display); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($animal['organization_id'])) : ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="text-muted small mb-1"><?php esc_html_e('Притулок / організація', 'lapki'); ?></div>
                        <a href="<?php echo esc_url(home_url('/organizations/' . (int) $animal['organization_id'] . '/')); ?>" class="fw-semibold lapki-link-green">
                            <?php echo esc_html($animal['organization_name'] ?? ''); ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <?php $share_url = home_url('/animals/' . (int) $animal['id'] . '/'); ?>
                <div class="d-flex flex-wrap gap-2">
                    <?php if ($animal['status'] === 'found') : ?>
                        <?php
                        // "Знайдена" — тварина щойно знайдена/на лікуванні, ще не готова
                        // до прилаштування. Кнопка неактивна (сіра), клік нічого не робить.
                        ?>
                        <button type="button" class="btn btn-secondary btn-sm disabled" disabled aria-disabled="true" title="<?php esc_attr_e('Ця тварина ще не готова до прилаштування', 'lapki'); ?>">
                            <?php esc_html_e('Хочу прилаштувати', 'lapki'); ?>
                        </button>
                    <?php else : ?>
                        <button type="button" class="btn lapki-btn-orange btn-sm" data-bs-toggle="modal" data-bs-target="#lapki-application-modal">
                            <?php esc_html_e('Хочу прилаштувати', 'lapki'); ?>
                        </button>
                    <?php endif; ?>

                    <?php if ($donate_organization) : ?>
                    <button type="button" class="btn lapki-btn-green btn-sm" data-bs-toggle="modal" data-bs-target="#lapki-donate-modal">
                        <i class="fas fa-heart me-1"></i><?php esc_html_e('Допомогти грошима', 'lapki'); ?>
                    </button>
                    <?php endif; ?>

                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo rawurlencode($share_url); ?>"
                       target="_blank" rel="noopener noreferrer"
                       class="btn lapki-btn-facebook btn-sm">
                        <i class="fab fa-facebook-f me-1"></i><?php esc_html_e('Поділитися', 'lapki'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Модалка форми заявки на усиновлення -->
<div class="modal fade" id="lapki-application-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <?php
                    /* translators: %s: кличка тварини */
                    printf(esc_html__('Заявка на усиновлення — %s', 'lapki'), esc_html($animal['name']));
                    ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e('Закрити', 'lapki'); ?>"></button>
            </div>
            <div class="modal-body">
                <form id="lapki-application-form" novalidate>
                    <input type="hidden" name="animal_id" value="<?php echo (int) $animal['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e("Ваше ім'я", 'lapki'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="applicant_name" class="form-control" required>
                        <div class="invalid-feedback"><?php esc_html_e("Будь ласка, вкажіть ваше ім'я.", 'lapki'); ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Email', 'lapki'); ?> <span class="text-danger">*</span></label>
                        <input type="email" name="applicant_email" class="form-control" required>
                        <div class="invalid-feedback"><?php esc_html_e('Вкажіть коректний email.', 'lapki'); ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Телефон', 'lapki'); ?> <span class="text-danger">*</span></label>
                        <input type="tel" name="applicant_phone" class="form-control" required>
                        <div class="invalid-feedback"><?php esc_html_e('Будь ласка, вкажіть номер телефону.', 'lapki'); ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Повідомлення', 'lapki'); ?> <span class="text-danger">*</span></label>
                        <textarea name="message" class="form-control" rows="3" placeholder="<?php esc_attr_e('Розкажіть трохи про себе та умови для тварини', 'lapki'); ?>" required></textarea>
                        <div class="invalid-feedback"><?php esc_html_e('Будь ласка, розкажіть трохи про себе та умови для тварини.', 'lapki'); ?></div>
                    </div>
                    <div id="lapki-application-alert" class="alert d-none" role="alert"></div>
                    <button type="submit" class="btn lapki-btn-green w-100"><?php esc_html_e('Надіслати заявку', 'lapki'); ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($donate_organization) : ?>
<!-- Модалка донату — контакти організації для перерахування коштів -->
<div class="modal fade" id="lapki-donate-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <?php
                    /* translators: %s: кличка тварини */
                    printf(esc_html__('Допомогти грошима — %s', 'lapki'), esc_html($animal['name']));
                    ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e('Закрити', 'lapki'); ?>"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">
                    <?php
                    /* translators: %s: кличка тварини */
                    printf(esc_html__("Щоб допомогти саме %s, зв'яжіться з організацією, яка про неї піклується, і уточніть реквізити для донату:", 'lapki'), esc_html($animal['name']));
                    ?>
                </p>
                <div class="card border-0 bg-light">
                    <div class="card-body">
                        <div class="fw-semibold mb-2">
                            <a href="<?php echo esc_url(home_url('/organizations/' . (int) $donate_organization['id'] . '/')); ?>" class="lapki-link-green">
                                <?php echo esc_html($donate_organization['name']); ?>
                            </a>
                        </div>
                        <?php if (!empty($donate_organization['phone'])) : ?>
                            <p class="mb-1"><i class="fas fa-phone"></i> <?php echo esc_html($donate_organization['phone']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($donate_organization['email'])) : ?>
                            <p class="mb-1"><i class="fas fa-envelope"></i> <a href="mailto:<?php echo esc_attr($donate_organization['email']); ?>"><?php echo esc_html($donate_organization['email']); ?></a></p>
                        <?php endif; ?>
                        <?php if (!empty($donate_organization['website'])) : ?>
                            <p class="mb-0"><i class="fas fa-globe"></i> <a href="<?php echo esc_url($donate_organization['website']); ?>" target="_blank" rel="noopener"><?php esc_html_e('Сайт організації', 'lapki'); ?></a></p>
                        <?php endif; ?>
                        <?php if (empty($donate_organization['phone']) && empty($donate_organization['email']) && empty($donate_organization['website'])) : ?>
                            <p class="mb-0 text-muted"><?php esc_html_e('Контакти поки не вказані — перегляньте сторінку організації.', 'lapki'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php get_footer(); ?>
