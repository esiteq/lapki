<?php
/**
 * Шорткод [lapki_signup] — форма реєстрації нового користувача.
 * Лише акаунт — прив'язка до притулку/ГО (створення нової чи приєднання
 * до вже існуючої) відбувається окремим кроком у кабінеті ([lapki_cabinet]).
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
                <label class="form-label">Ваше ім'я *</label>
                <input type="text" name="name" class="form-control" required>
                <div class="invalid-feedback">Будь ласка, вкажіть ваше ім'я.</div>
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

            <div class="col-12">
                <label class="form-label">Телефон</label>
                <input type="tel" name="phone" class="form-control">
            </div>

            <div class="col-12">
                <div id="lapki-signup-alert" class="alert d-none" role="alert"></div>
                <button type="submit" class="btn btn-warning w-100">Зареєструватися</button>
                <p class="text-muted small mt-2 mb-0">Після реєстрації в кабінеті можна приєднатись до вже існуючого притулку/ГО або зареєструвати свій.</p>
            </div>
        </form>
</div>
