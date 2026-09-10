<?php
/**
 * Зареєструвати організацію — /add-organization/
 * Окрема сторінка для самостійної реєстрації притулку/ГО/клініки залогіненим
 * користувачем — робить його власником одразу (POST /organizations).
 * Раніше ця форма була вбудована в /profile/?tab=organizations; винесена
 * окремо, бо для приватних осіб реєструвати "організацію" взагалі не
 * потрібно — вони додають тварин напряму через /add-animal/, який сам
 * підготує мінімальний профіль (Lapki_Organization_Member::ensure_membership()),
 * без жодної форми. Тому "Приватна особа" тут навмисно відсутня в списку типів.
 *
 * Анонімів редіректить на /signup/ ще на template_redirect
 * (Lapki_Frontend::maybe_redirect_add_pages_if_logged_out()).
 *
 * Тема може перевизначити цей шаблон: скопіювати у
 * wp-content/themes/{тема}/lapki/add-organization.php
 *
 * @package Lapki
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<section class="py-5">
    <div class="container">
        <p class="mb-3"><a href="<?php echo esc_url(home_url('/profile/?tab=organizations')); ?>" class="lapki-link-green small">← <?php esc_html_e('Мої організації', 'lapki'); ?></a></p>

        <h1 class="h3 fw-bold mb-4"><i class="fas fa-warehouse me-2"></i><?php esc_html_e('Зареєструвати організацію', 'lapki'); ?></h1>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form id="lapki-org-create-form" class="row g-3" novalidate>
                    <div class="col-12">
                        <label class="form-label"><?php esc_html_e('Тип організації *', 'lapki'); ?></label>
                        <select name="type" class="form-select" required>
                            <option value="" selected disabled><?php esc_html_e('Оберіть тип', 'lapki'); ?></option>
                            <option value="shelter"><?php esc_html_e('Притулок', 'lapki'); ?></option>
                            <option value="vet_clinic"><?php esc_html_e('Ветеринарна клініка', 'lapki'); ?></option>
                            <option value="vet"><?php esc_html_e('Окремий ветеринар', 'lapki'); ?></option>
                            <option value="volunteer"><?php esc_html_e('Волонтерська організація', 'lapki'); ?></option>
                        </select>
                        <div class="invalid-feedback"><?php esc_html_e('Оберіть тип організації.', 'lapki'); ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label"><?php esc_html_e('Назва *', 'lapki'); ?></label>
                        <input type="text" name="name" class="form-control" required>
                        <div class="invalid-feedback"><?php esc_html_e('Вкажіть назву організації.', 'lapki'); ?></div>
                    </div>
                    <div class="col-md-6 lapki-city-field">
                        <label class="form-label" for="org-create-city-select"><?php esc_html_e('Населений пункт *', 'lapki'); ?></label>
                        <select id="org-create-city-select" class="lapki-city-select"></select>
                        <input type="hidden" name="city" data-city-name>
                        <input type="hidden" name="city_katottg" data-city-katottg>
                        <div class="invalid-feedback"><?php esc_html_e('Почніть вводити назву й оберіть населений пункт зі списку підказок.', 'lapki'); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php esc_html_e('Телефон', 'lapki'); ?></label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label"><?php esc_html_e('Опис організації', 'lapki'); ?></label>
                        <textarea name="mission_statement" class="form-control" rows="4" placeholder="<?php esc_attr_e('Розкажіть про організацію: чим займаєтесь, кому й як допомагаєте…', 'lapki'); ?>"></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label"><?php esc_html_e('Фото', 'lapki'); ?></label>
                        <div id="add-organization-dropzone" class="lapki-dropzone">
                            <div class="dz-message">
                                <?php esc_html_e('Перетягніть файли сюди або клікніть для вибору', 'lapki'); ?><br>
                                <span class="text-muted small">(JPG, PNG, GIF, WebP, <?php esc_html_e('до 10 МБ', 'lapki'); ?>)</span>
                            </div>
                        </div>
                        <div class="form-text"><?php esc_html_e('Перше фото стане обкладинкою організації — саме воно показується в картці на сторінці «Організації», зокрема в результатах пошуку. Відео-файли не приймаються — лише посилання нижче.', 'lapki'); ?></div>
                    </div>

                    <div class="col-12">
                        <label class="form-label"><?php esc_html_e('Відео', 'lapki'); ?></label>
                        <textarea name="video_urls" class="form-control" rows="2" placeholder="https://www.youtube.com/watch?v=... https://vimeo.com/..."></textarea>
                        <div class="form-text"><?php esc_html_e('Посилання на YouTube, Vimeo, TikTok, Instagram, Facebook чи Dailymotion. Можна вставити одразу декілька — розділювач не важливий (пробіл, кома, з нового рядка).', 'lapki'); ?></div>
                    </div>

                    <div class="col-12">
                        <div id="lapki-org-create-alert" class="alert d-none" role="alert"></div>
                        <button type="submit" class="btn lapki-btn-orange"><?php esc_html_e('Зареєструвати організацію', 'lapki'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
