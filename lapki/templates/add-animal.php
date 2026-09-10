<?php
/**
 * Додати/редагувати тварину — /add-animal/ (створення) або /add-animal/?id=N
 * (редагування, лише власник/учасник організації тварини). Публічна форма
 * самостійного додавання тварини — доступна БУДЬ-ЯКОМУ залогіненому
 * користувачу, не лише зареєстрованим притулкам/ГО: якщо в користувача ще
 * немає організації, Lapki_Organization_Member::ensure_membership()
 * автоматично створює для нього мінімальну організацію типу 'individual'
 * (з даних акаунта) — так само, як самостійна реєстрація організації в
 * кабінеті, просто без ручного кроку. Анонімів редіректить на /signup/ ще
 * на template_redirect (Lapki_Frontend::maybe_redirect_add_animal_if_logged_out()).
 *
 * Тема може перевизначити цей шаблон: скопіювати у
 * wp-content/themes/{тема}/lapki/add-animal.php
 *
 * @package Lapki
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$current_user = wp_get_current_user();

$editing_animal_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
$editing_animal = $editing_animal_id ? Lapki_Animal::get($editing_animal_id) : null;

// Редагування — лише власник чи учасник організації, якій належить ця
// тварина (та сама перевірка, що й на PUT /animals/{id} в REST API).
if ($editing_animal_id && (!$editing_animal || !Lapki_Roles::user_owns_organization($editing_animal['organization_id'], $current_user->ID))) {
    ?>
    <section class="py-5">
        <div class="container">
            <div class="alert alert-danger mb-0"><?php esc_html_e('У вас немає доступу до редагування цієї тварини.', 'lapki'); ?></div>
        </div>
    </section>
    <?php
    get_footer();
    return;
}

$membership = $editing_animal
    ? ['organization_id' => $editing_animal['organization_id']]
    : Lapki_Organization_Member::ensure_membership($current_user->ID);
?>

<section class="py-5">
    <div class="container">
        <h1 class="h3 fw-bold mb-4"><i class="fas fa-paw me-2"></i><?php echo $editing_animal ? esc_html__('Редагувати тварину', 'lapki') : esc_html__('Додати тварину', 'lapki'); ?></h1>

        <?php if (!$membership) : ?>
            <div class="alert alert-danger mb-0">
                <?php esc_html_e('Не вдалося підготувати профіль для додавання тварини. Спробуйте ще раз або зверніться до підтримки.', 'lapki'); ?>
            </div>
        <?php else : ?>
            <div class="lapki-signup">
                <form id="lapki-add-animal-form" class="row g-3" novalidate
                      data-organization-id="<?php echo esc_attr($membership['organization_id']); ?>"
                      data-animal-id="<?php echo esc_attr($editing_animal_id); ?>"
                      <?php if ($editing_animal) : ?>data-animal="<?php echo esc_attr(wp_json_encode($editing_animal)); ?>"<?php endif; ?>>
                    <div class="col-md-6">
                        <label class="form-label"><?php esc_html_e('Кличка *', 'lapki'); ?></label>
                        <input type="text" name="name" class="form-control" value="<?php echo esc_attr($editing_animal['name'] ?? ''); ?>" required>
                        <div class="invalid-feedback"><?php esc_html_e('Вкажіть кличку тварини.', 'lapki'); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php esc_html_e('Вид *', 'lapki'); ?></label>
                        <select name="type" class="form-select" required>
                            <option value=""><?php esc_html_e('Оберіть вид…', 'lapki'); ?></option>
                        </select>
                        <div class="invalid-feedback"><?php esc_html_e('Оберіть вид тварини.', 'lapki'); ?></div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label"><?php esc_html_e('Вік *', 'lapki'); ?></label>
                        <select name="age" class="form-select" required>
                            <option value=""><?php esc_html_e('Оберіть…', 'lapki'); ?></option>
                        </select>
                        <div class="invalid-feedback"><?php esc_html_e('Оберіть вік.', 'lapki'); ?></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?php esc_html_e('Стать *', 'lapki'); ?></label>
                        <select name="gender" class="form-select" required>
                            <option value=""><?php esc_html_e('Оберіть…', 'lapki'); ?></option>
                        </select>
                        <div class="invalid-feedback"><?php esc_html_e('Оберіть стать.', 'lapki'); ?></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?php esc_html_e('Розмір *', 'lapki'); ?></label>
                        <select name="size" class="form-select" required>
                            <option value=""><?php esc_html_e('Оберіть…', 'lapki'); ?></option>
                        </select>
                        <div class="invalid-feedback"><?php esc_html_e('Оберіть розмір.', 'lapki'); ?></div>
                    </div>

                    <div class="col-12 lapki-city-field">
                        <label class="form-label" for="add-animal-city-select"><?php esc_html_e('Населений пункт *', 'lapki'); ?></label>
                        <select id="add-animal-city-select" class="lapki-city-select"></select>
                        <input type="hidden" name="address_city" data-city-name value="<?php echo esc_attr($editing_animal['address_city'] ?? ''); ?>">
                        <input type="hidden" name="address_city_katottg" data-city-katottg value="<?php echo esc_attr($editing_animal['address_city_katottg'] ?? ''); ?>">
                        <div class="invalid-feedback"><?php esc_html_e('Почніть вводити назву й оберіть населений пункт зі списку підказок.', 'lapki'); ?></div>
                    </div>

                    <div class="col-12">
                        <label class="form-label"><?php esc_html_e('Опис', 'lapki'); ?></label>
                        <textarea name="description" class="form-control lapki-ai-improve-target" rows="4"><?php echo esc_textarea($editing_animal['description'] ?? ''); ?></textarea>
                        <div class="mt-2 d-flex gap-2 align-items-center lapki-ai-improve-controls">
                            <button type="button" class="btn btn-outline-secondary btn-sm lapki-ai-improve-btn">
                                <i class="fas fa-wand-magic-sparkles me-1"></i><?php esc_html_e('Покращити за допомогою ШІ', 'lapki'); ?>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm lapki-ai-undo-btn d-none">
                                <?php esc_html_e('Скасувати зміни', 'lapki'); ?>
                            </button>
                            <span class="small text-muted lapki-ai-status"></span>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label d-block"><?php esc_html_e('Додатково', 'lapki'); ?></label>
                        <div class="row row-cols-2 row-cols-md-3 g-2">
                            <div class="col form-check">
                                <input type="checkbox" class="form-check-input" id="animal-spayed_neutered" name="spayed_neutered" <?php checked(!empty($editing_animal['spayed_neutered'])); ?>>
                                <label class="form-check-label" for="animal-spayed_neutered"><?php esc_html_e('Стерилізована/кастрований', 'lapki'); ?></label>
                            </div>
                            <div class="col form-check">
                                <input type="checkbox" class="form-check-input" id="animal-shots_current" name="shots_current" <?php checked(!empty($editing_animal['shots_current'])); ?>>
                                <label class="form-check-label" for="animal-shots_current"><?php esc_html_e('Вакцинована', 'lapki'); ?></label>
                            </div>
                            <div class="col form-check">
                                <input type="checkbox" class="form-check-input" id="animal-house_trained" name="house_trained" <?php checked(!empty($editing_animal['house_trained'])); ?>>
                                <label class="form-check-label" for="animal-house_trained"><?php esc_html_e('Привчена до туалету', 'lapki'); ?></label>
                            </div>
                            <div class="col form-check">
                                <input type="checkbox" class="form-check-input" id="animal-good_with_children" name="good_with_children" <?php checked(!empty($editing_animal['good_with_children'])); ?>>
                                <label class="form-check-label" for="animal-good_with_children"><?php esc_html_e('Добре з дітьми', 'lapki'); ?></label>
                            </div>
                            <div class="col form-check">
                                <input type="checkbox" class="form-check-input" id="animal-good_with_dogs" name="good_with_dogs" <?php checked(!empty($editing_animal['good_with_dogs'])); ?>>
                                <label class="form-check-label" for="animal-good_with_dogs"><?php esc_html_e('Добре з собаками', 'lapki'); ?></label>
                            </div>
                            <div class="col form-check">
                                <input type="checkbox" class="form-check-input" id="animal-good_with_cats" name="good_with_cats" <?php checked(!empty($editing_animal['good_with_cats'])); ?>>
                                <label class="form-check-label" for="animal-good_with_cats"><?php esc_html_e('Добре з котами', 'lapki'); ?></label>
                            </div>
                            <div class="col form-check">
                                <input type="checkbox" class="form-check-input" id="animal-special_needs" name="special_needs" <?php checked(!empty($editing_animal['special_needs'])); ?>>
                                <label class="form-check-label" for="animal-special_needs"><?php esc_html_e('Особливі потреби', 'lapki'); ?></label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label"><?php esc_html_e('Фото', 'lapki'); ?></label>
                        <?php
                        $existing_photos = $editing_animal ? array_values(array_filter($editing_animal['media'] ?? [], function ($m) {
                            return $m['media_type'] === 'photo';
                        })) : [];
                        ?>
                        <?php if (!empty($existing_photos)) : ?>
                        <div id="add-animal-existing-photos" class="d-flex flex-wrap gap-2 mb-2">
                            <?php foreach ($existing_photos as $photo) : ?>
                            <div class="lapki-existing-photo position-relative" data-media-id="<?php echo (int) $photo['id']; ?>" style="width:90px;">
                                <img src="<?php echo esc_url($photo['thumbnail_url'] ?: $photo['url']); ?>" class="rounded-3" style="width:90px;height:90px;object-fit:cover;<?php echo !empty($photo['is_primary']) ? 'outline:3px solid #ffc107;outline-offset:-3px;' : ''; ?>" alt="">
                                <button type="button" class="btn btn-sm btn-danger lapki-delete-photo-btn" title="<?php esc_attr_e('Видалити фото', 'lapki'); ?>" style="position:absolute;top:-8px;right:-8px;border-radius:50%;width:24px;height:24px;padding:0;line-height:1;">×</button>
                                <?php if (empty($photo['is_primary'])) : ?>
                                <button type="button" class="btn btn-sm btn-light lapki-set-primary-photo-btn" title="<?php esc_attr_e('Зробити головним фото', 'lapki'); ?>" style="position:absolute;bottom:-8px;left:-8px;width:24px;height:24px;padding:0;line-height:1;border-radius:50%;">
                                    <i class="fas fa-star" style="font-size:11px;"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-text mb-2">
                            <?php
                            printf(
                                /* translators: %s: іконка зірки */
                                esc_html__('Жовта рамка — головне фото. Клацніть %s, щоб зробити інше фото головним, або × — щоб видалити.', 'lapki'),
                                '<i class="fas fa-star"></i>'
                            );
                            ?>
                        </div>
                        <?php endif; ?>
                        <div id="add-animal-dropzone" class="lapki-dropzone">
                            <div class="dz-message">
                                <?php esc_html_e('Перетягніть файли сюди або клікніть для вибору', 'lapki'); ?><br>
                                <span class="text-muted small">(JPG, PNG, GIF, WebP, <?php esc_html_e('до 10 МБ', 'lapki'); ?>)</span>
                            </div>
                        </div>
                        <div class="form-text"><?php echo $editing_animal ? esc_html__('Нові фото додаються до вже наявних.', 'lapki') : esc_html__('Перше фото стане головним.', 'lapki'); ?> <?php esc_html_e('Відео-файли не приймаються — лише посилання нижче.', 'lapki'); ?></div>
                    </div>

                    <div class="col-12">
                        <label class="form-label"><?php esc_html_e('Відео', 'lapki'); ?></label>
                        <textarea name="video_urls" class="form-control" rows="2" placeholder="https://www.youtube.com/watch?v=... https://vimeo.com/..."></textarea>
                        <div class="form-text"><?php esc_html_e('Посилання на YouTube, Vimeo, TikTok, Instagram, Facebook чи Dailymotion. Можна вставити одразу декілька — розділювач не важливий (пробіл, кома, з нового рядка)', 'lapki'); ?><?php echo $editing_animal ? esc_html__(', додадуться до вже наявних відео', 'lapki') : ''; ?>.</div>
                    </div>

                    <div class="col-12">
                        <div id="lapki-add-animal-alert" class="alert d-none" role="alert"></div>
                        <button type="submit" class="btn btn-warning w-100"><?php echo $editing_animal ? esc_html__('Зберегти зміни', 'lapki') : esc_html__('Додати тварину', 'lapki'); ?></button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php get_footer(); ?>
