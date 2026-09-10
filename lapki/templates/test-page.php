<?php
/**
 * Тестова сторінка для embed-віджета інтеграції (js/lapki-integration.js) — /test-page/
 * Навмисно не додана в меню — лише для перевірки скрипта вбудовування
 * (форма пошуку + результати), який конструюється на /profile/?tab=integration.
 *
 * Тема може перевизначити цей шаблон: скопіювати у
 * wp-content/themes/{тема}/lapki/test-page.php
 *
 * @package Lapki
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$organization_id = isset($_GET['organization_id']) ? absint($_GET['organization_id']) : 1;
$snippet = '<script src="' . home_url('/integration/lapki.js?organization_id=' . $organization_id) . '"></script>';
?>

<section class="py-5">
    <div class="container">
        <h1 class="h3 fw-bold mb-3">Тестова сторінка інтеграції</h1>
        <p class="text-muted mb-4">
            Ця сторінка не додана в меню — призначена лише для перевірки скрипта вбудовування
            пошукової форми й результатів (<code>/integration/lapki.js</code>), який формується
            на вкладці «Інтеграція» в <a href="<?php echo esc_url(home_url('/profile/?tab=integration')); ?>">особистому кабінеті</a>.
        </p>

        <form method="get" class="row g-2 align-items-end mb-4" style="max-width:420px;">
            <div class="col-8">
                <label class="form-label small fw-semibold">ID організації для перевірки</label>
                <input type="number" name="organization_id" min="1" class="form-control" value="<?php echo (int) $organization_id; ?>">
            </div>
            <div class="col-4">
                <button type="submit" class="btn lapki-btn-orange w-100">Показати</button>
            </div>
        </form>

        <h2 class="h5 fw-bold mb-2">Код для вставки</h2>
        <pre class="p-3 rounded-3 bg-dark text-light" style="overflow-x:auto;"><code><?php echo esc_html($snippet); ?></code></pre>
        <p class="text-muted small mb-5">
            Необов'язкові параметри рядка запиту: <code>limit</code> (за замовчуванням 12), <code>status</code> (за замовчуванням <code>adoptable</code>).
        </p>

        <h2 class="h5 fw-bold mb-3">Як це виглядає у вставленому вигляді</h2>
        <p class="text-muted small mb-2">
            Нижче — приклад "чужої сторінки": білий блок імітує контейнер стороннього сайту чи блогу.
            Оскільки сторінка все ще завантажує CSS теми lapki.help, це лише візуальна перевірка —
            для повної перевірки незалежності стилів вставте скрипт на справді сторонній сайт.
        </p>
        <div class="border rounded-3 p-4" style="background:#fafafa;">
            <p class="text-muted small mb-3">— початок стороннього контенту —</p>
            <script src="<?php echo esc_url(home_url('/integration/lapki.js?organization_id=' . $organization_id)); ?>"></script>
            <p class="text-muted small mt-3 mb-0">— кінець стороннього контенту —</p>
        </div>
    </div>
</section>

<?php get_footer(); ?>
