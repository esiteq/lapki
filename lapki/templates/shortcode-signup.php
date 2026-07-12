<?php
/**
 * Шорткод [lapki_signup] — форма реєстрації нового користувача.
 *
 * @package Lapki
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="lapki-signup">
    <?php /* ТИМЧАСОВО: форма показується навіть залогіненим користувачам (за запитом, для тестування) */ ?>
    <?php if (is_user_logged_in()) : ?>
        <div class="alert alert-warning">
            Ви авторизовані як <?php echo esc_html(wp_get_current_user()->display_name); ?>. Реєстрація нового акаунта увійде в систему під новим користувачем.
        </div>
    <?php endif; ?>
        <form id="lapki-signup-form" class="row g-3" novalidate>
            <div class="col-12">
                <label class="form-label">Хто ви? *</label>
                <select name="user_type" id="lapki-signup-type" class="form-select" required>
                    <option value="" selected disabled>Оберіть тип реєстрації</option>
                    <option value="individual">Приватна особа</option>
                    <option value="shelter">Притулок</option>
                    <option value="vet_clinic">Ветеринарна клініка</option>
                    <option value="vet">Окремий ветеринар</option>
                    <option value="volunteer">Волонтер</option>
                </select>
                <div class="invalid-feedback">Будь ласка, оберіть тип реєстрації.</div>
            </div>

            <div class="col-12">
                <label class="form-label">Ваше ім'я *</label>
                <input type="text" name="name" class="form-control" required>
                <div class="invalid-feedback">Будь ласка, вкажіть ваше ім'я.</div>
            </div>

            <div class="col-12 d-none" id="lapki-signup-org-wrap">
                <label class="form-label">Назва організації</label>
                <input type="text" name="organization_name" class="form-control" placeholder="Якщо відрізняється від імені вище">
            </div>

            <div class="col-md-6">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" required>
                <div class="invalid-feedback">Вкажіть коректний email.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Пароль *</label>
                <input type="password" name="password" class="form-control" minlength="6" required>
                <div class="invalid-feedback">Пароль має містити щонайменше 6 символів.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Телефон</label>
                <input type="tel" name="phone" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">Місто</label>
                <input type="text" name="city" class="form-control">
            </div>

            <div class="col-12">
                <div id="lapki-signup-alert" class="alert d-none" role="alert"></div>
                <button type="submit" class="btn btn-warning w-100">Зареєструватися</button>
            </div>
        </form>
</div>
