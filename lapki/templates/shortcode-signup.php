<?php
/**
 * Шорткод [lapki_signup] — таби «Реєстрація» / «Логін».
 * Реєстрація — лише акаунт, прив'язка до притулку/ГО (створення нової чи
 * приєднання до вже існуючої) відбувається окремим кроком у кабінеті (/profile/).
 * Обидва таби працюють виключно через REST API (POST /signup, POST /login) —
 * без wp-login.php і без звичайного POST-сабміту форми.
 *
 * @package Lapki
 */

if (!defined('ABSPATH')) {
    exit;
}

// Контекстне пояснення "зверху" форми — за білим списком відомих ключів
// (не відбиваємо довільний текст з GET, щоб сторінку не можна було
// використати для фішингових повідомлень через підроблене посилання)
$context_notices = [
    'add-animal' => __("Щоб з вами могли зв'язатися щодо прилаштування тварини, будь ласка, зареєструйтесь або увійдіть в свій акаунт, якщо ви вже зареєстровані.", 'lapki'),
    'add-organization' => __("Щоб зареєструвати організацію, будь ласка, зареєструйтесь або увійдіть в свій акаунт, якщо ви вже зареєстровані.", 'lapki'),
    'edit-profile' => __("Щоб редагувати профіль, будь ласка, зареєструйтесь або увійдіть в свій акаунт, якщо ви вже зареєстровані.", 'lapki'),
];
$context = isset($_GET['context']) ? sanitize_key(wp_unslash($_GET['context'])) : '';
$context_notice = $context_notices[$context] ?? '';
?>
<div class="lapki-signup">
    <?php if ($context_notice) : ?>
        <div class="alert alert-info"><?php echo esc_html($context_notice); ?></div>
    <?php endif; ?>

    <?php /* ТИМЧАСОВО: форма показується навіть залогіненим користувачам (за запитом, для тестування) */ ?>
    <?php if (is_user_logged_in()) : ?>
        <div class="alert alert-warning">
            <?php
            printf(
                /* translators: %s: ім'я поточного користувача */
                esc_html__('Ви авторизовані як %s. Реєстрація нового акаунта увійде в систему під новим користувачем.', 'lapki'),
                esc_html(wp_get_current_user()->display_name)
            );
            ?>
        </div>
    <?php endif; ?>

    <ul class="nav nav-tabs lapki-auth-tabs mb-4" id="lapki-auth-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="lapki-tab-signup-btn" data-bs-toggle="tab" data-bs-target="#lapki-tab-signup" type="button" role="tab" aria-controls="lapki-tab-signup" aria-selected="true"><?php esc_html_e('Реєстрація', 'lapki'); ?></button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="lapki-tab-login-btn" data-bs-toggle="tab" data-bs-target="#lapki-tab-login" type="button" role="tab" aria-controls="lapki-tab-login" aria-selected="false"><?php esc_html_e('Логін', 'lapki'); ?></button>
        </li>
    </ul>

    <div class="tab-content" id="lapki-auth-tabs-content">
        <div class="tab-pane fade show active" id="lapki-tab-signup" role="tabpanel" aria-labelledby="lapki-tab-signup-btn">
            <form id="lapki-signup-form" class="row g-3" novalidate>
                <div class="col-md-6">
                    <label class="form-label"><?php esc_html_e('Прізвище *', 'lapki'); ?></label>
                    <input type="text" name="last_name" class="form-control" required>
                    <div class="invalid-feedback"><?php esc_html_e('Будь ласка, вкажіть прізвище.', 'lapki'); ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?php esc_html_e("Ім'я *", 'lapki'); ?></label>
                    <input type="text" name="first_name" class="form-control" required>
                    <div class="invalid-feedback"><?php esc_html_e("Будь ласка, вкажіть ім'я.", 'lapki'); ?></div>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?php esc_html_e('Email *', 'lapki'); ?></label>
                    <input type="email" name="email" class="form-control" required>
                    <div class="invalid-feedback"><?php esc_html_e('Вкажіть коректний email.', 'lapki'); ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?php esc_html_e('Пароль *', 'lapki'); ?></label>
                    <input type="password" name="password" class="form-control" minlength="6" required>
                    <div class="invalid-feedback"><?php esc_html_e('Пароль має містити щонайменше 6 символів.', 'lapki'); ?></div>
                </div>

                <div class="col-12">
                    <label class="form-label"><?php esc_html_e('Телефон *', 'lapki'); ?></label>
                    <input type="tel" name="phone" class="form-control" placeholder="+38 (___) ___-__-__" pattern="^\+38 \(\d{3}\) \d{3}-\d{2}-\d{2}$" required>
                    <div class="invalid-feedback"><?php esc_html_e('Введіть номер телефону повністю: +38 (XXX) XXX-XX-XX.', 'lapki'); ?></div>
                </div>

                <div class="col-12">
                    <div id="lapki-signup-alert" class="alert d-none" role="alert"></div>
                    <button type="submit" class="btn btn-warning w-100"><?php esc_html_e('Зареєструватися', 'lapki'); ?></button>
                    <p class="text-muted small mt-2 mb-0"><?php esc_html_e('Після реєстрації в кабінеті можна приєднатись до вже існуючого притулку/ГО або зареєструвати свій.', 'lapki'); ?></p>
                </div>
            </form>
        </div>

        <div class="tab-pane fade" id="lapki-tab-login" role="tabpanel" aria-labelledby="lapki-tab-login-btn">
            <form id="lapki-login-form" class="row g-3" novalidate>
                <div class="col-12">
                    <label class="form-label"><?php esc_html_e('Email *', 'lapki'); ?></label>
                    <input type="email" name="email" class="form-control" required>
                    <div class="invalid-feedback"><?php esc_html_e('Вкажіть коректний email.', 'lapki'); ?></div>
                </div>
                <div class="col-12">
                    <label class="form-label"><?php esc_html_e('Пароль *', 'lapki'); ?></label>
                    <input type="password" name="password" class="form-control" required>
                    <div class="invalid-feedback"><?php esc_html_e('Вкажіть пароль.', 'lapki'); ?></div>
                </div>

                <div class="col-12">
                    <div id="lapki-login-alert" class="alert d-none" role="alert"></div>
                    <button type="submit" class="btn btn-warning w-100"><?php esc_html_e('Увійти', 'lapki'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
