<?php
/**
 * Тестова сторінка для віджета вбудовування /js/animals.js — /widget-demo/
 * Навмисно не додана в меню — лише для перевірки скрипта.
 *
 * Тема може перевизначити цей шаблон: скопіювати у
 * wp-content/themes/{тема}/lapki/widget-demo.php
 *
 * @package Lapki
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$snippet = '<script src="' . home_url('/js/animals.js?organization_id=1') . '"></script>';
?>

<section class="py-5">
    <div class="container">
        <h1 class="h3 fw-bold mb-3">Демонстрація віджета «Тварини притулку»</h1>
        <p class="text-muted mb-4">
            Ця сторінка не додана в меню — призначена лише для перевірки скрипта вбудовування.
            Притулки та волонтери можуть вставити цей рядок у себе на сайті чи в блозі,
            щоб показати список своїх тварин зі стилями як на lapki.help.
        </p>

        <h2 class="h5 fw-bold mb-2">Код для вставки</h2>
        <pre class="p-3 rounded-3 bg-dark text-light" style="overflow-x:auto;"><code><?php echo esc_html($snippet); ?></code></pre>
        <p class="text-muted small mb-5">
            Необов'язкові параметри рядка запиту: <code>limit</code> (за замовчуванням 24), <code>status</code> (за замовчуванням <code>adoptable</code>).
            Замініть <code>organization_id</code> на ID своєї організації.
        </p>

        <h2 class="h5 fw-bold mb-3">Як це виглядає у вставленому вигляді</h2>
        <p class="text-muted small mb-2">
            Нижче — приклад "чужої сторінки": білий блок імітує контейнер стороннього блогу/сайту.
            Оскільки сторінка все ще завантажує CSS теми lapki.help, це лише візуальна перевірка —
            для повної перевірки незалежності стилів вставте скрипт на справді сторонній сайт.
        </p>
        <div class="border rounded-3 p-4" style="background:#fafafa;">
            <p class="text-muted small mb-3">— початок стороннього контенту —</p>
            <script src="<?php echo esc_url(home_url('/js/animals.js?organization_id=1')); ?>"></script>
            <p class="text-muted small mt-3 mb-0">— кінець стороннього контенту —</p>
        </div>
    </div>
</section>

<?php get_footer(); ?>
