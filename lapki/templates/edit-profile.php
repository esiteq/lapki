<?php
/**
 * Редагування профілю користувача — /edit-profile/
 * Окрема сторінка (не вкладка /profile/) — ім'я, email, телефон і аватар
 * (одне фото, на відміну від галереї фото тварин/організацій).
 *
 * Анонімів редіректить на /signup/ ще на template_redirect
 * (Lapki_Frontend::maybe_redirect_add_pages_if_logged_out()).
 *
 * Тема може перевизначити цей шаблон: скопіювати у
 * wp-content/themes/{тема}/lapki/edit-profile.php
 *
 * @package Lapki
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$current_user = wp_get_current_user();
$phone = get_user_meta($current_user->ID, 'lapki_phone', true);
$avatar_photo = Lapki_Media::get_primary_photo('user', $current_user->ID);
$avatar_letter = mb_strtoupper(mb_substr($current_user->display_name, 0, 1));
?>

<section class="py-5">
    <div class="container" style="max-width: 640px;">
        <p class="mb-3"><a href="<?php echo esc_url(home_url('/profile/')); ?>" class="lapki-link-green small">← <?php esc_html_e('Особистий кабінет', 'lapki'); ?></a></p>

        <h1 class="h3 fw-bold mb-4"><i class="fas fa-user-edit me-2"></i><?php esc_html_e('Редагування профілю', 'lapki'); ?></h1>

        <div class="card border-0 shadow-sm lapki-edit-profile">
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="lapki-avatar-upload__circle" id="lapki-avatar-circle" title="<?php esc_attr_e('Змінити фото', 'lapki'); ?>">
                        <span id="lapki-avatar-letter"<?php echo $avatar_photo ? ' class="d-none"' : ''; ?>><?php echo esc_html($avatar_letter); ?></span>
                        <img id="lapki-avatar-preview-img" alt="" <?php echo $avatar_photo ? '' : 'class="d-none" '; ?>src="<?php echo $avatar_photo ? esc_url($avatar_photo['thumbnail_url'] ?: $avatar_photo['url']) : ''; ?>">
                        <div class="lapki-avatar-upload__overlay"><i class="fas fa-camera"></i></div>
                    </div>
                    <input type="file" id="lapki-avatar-input" accept="image/jpeg,image/png,image/gif,image/webp" class="d-none">
                    <div class="mt-2">
                        <button type="button" id="lapki-avatar-remove" class="btn btn-sm btn-link text-danger p-0<?php echo $avatar_photo ? '' : ' d-none'; ?>"><?php esc_html_e('Видалити фото', 'lapki'); ?></button>
                    </div>
                    <p class="text-muted small mb-0"><?php esc_html_e('Натисніть на фото, щоб завантажити нове (JPG, PNG, GIF, WebP, до 10 МБ)', 'lapki'); ?></p>
                </div>

                <form id="lapki-edit-profile-form" class="row g-3" novalidate>
                    <div class="col-md-6">
                        <label class="form-label"><?php esc_html_e("Ім'я *", 'lapki'); ?></label>
                        <input type="text" name="first_name" class="form-control" value="<?php echo esc_attr($current_user->first_name); ?>" required>
                        <div class="invalid-feedback"><?php esc_html_e("Будь ласка, вкажіть ім'я.", 'lapki'); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php esc_html_e('Прізвище *', 'lapki'); ?></label>
                        <input type="text" name="last_name" class="form-control" value="<?php echo esc_attr($current_user->last_name); ?>" required>
                        <div class="invalid-feedback"><?php esc_html_e('Будь ласка, вкажіть прізвище.', 'lapki'); ?></div>
                    </div>

                    <div class="col-12">
                        <label class="form-label"><?php esc_html_e('Email *', 'lapki'); ?></label>
                        <input type="email" name="email" class="form-control" value="<?php echo esc_attr($current_user->user_email); ?>" required>
                        <div class="invalid-feedback"><?php esc_html_e('Вкажіть коректний email.', 'lapki'); ?></div>
                    </div>

                    <div class="col-12">
                        <label class="form-label"><?php esc_html_e('Телефон', 'lapki'); ?></label>
                        <input type="tel" name="phone" class="form-control" placeholder="+38 (___) ___-__-__" pattern="^\+38 \(\d{3}\) \d{3}-\d{2}-\d{2}$" value="<?php echo esc_attr($phone); ?>">
                        <div class="invalid-feedback"><?php esc_html_e('Введіть номер телефону повністю: +38 (XXX) XXX-XX-XX, або залиште поле порожнім.', 'lapki'); ?></div>
                    </div>

                    <div class="col-12 mt-4">
                        <div id="lapki-edit-profile-alert" class="alert d-none" role="alert"></div>
                        <button type="submit" class="btn lapki-btn-orange"><?php esc_html_e('Зберегти', 'lapki'); ?></button>
                        <a href="<?php echo esc_url(home_url('/profile/')); ?>" class="btn btn-outline-secondary"><?php esc_html_e('Скасувати', 'lapki'); ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
