<?php
/**
 * Архів/пошук тварин — /animals/
 *
 * Тема може перевизначити цей шаблон: скопіювати у
 * wp-content/themes/{тема}/lapki/archive-animals.php
 *
 * @package Lapki
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$initial_type            = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : '';
$initial_location        = isset($_GET['location']) ? sanitize_text_field(wp_unslash($_GET['location'])) : '';
$initial_age             = isset($_GET['age']) ? sanitize_text_field(wp_unslash($_GET['age'])) : '';
$initial_search          = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
$initial_organization_id = isset($_GET['organization_id']) ? absint($_GET['organization_id']) : 0;
?>

<section class="py-5">
    <div class="container">
        <h1 class="h3 fw-bold mb-4">Тварини, що шукають дім</h1>

        <form id="lapki-archive-filters" class="row g-2 mb-4">
            <div class="col-6 col-md-2">
                <select name="type" class="form-select" id="filter-type">
                    <option value="">Всі тварини</option>
                    <option value="dog">🐕 Собаки</option>
                    <option value="cat">🐈 Коти</option>
                    <option value="bird">🐦 Птахи</option>
                    <option value="rabbit">🐇 Кролики</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="age" class="form-select" id="filter-age">
                    <option value="">Будь-який вік</option>
                    <option value="baby">Малюк</option>
                    <option value="young">Молодий</option>
                    <option value="adult">Дорослий</option>
                    <option value="senior">Похилого віку</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="organization_id" class="form-select" id="filter-organization">
                    <option value="">Всі притулки</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <input type="text" name="location" id="filter-location" class="form-control" placeholder="Місто">
            </div>
            <div class="col-6 col-md-2">
                <input type="text" name="search" id="filter-search" class="form-control" placeholder="Кличка">
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn lapki-btn-green w-100">Шукати</button>
            </div>
        </form>

        <div id="lapki-archive-grid" class="row g-4">
            <?php for ($i = 0; $i < 8; $i++) : ?>
            <div class="col-6 col-md-4 col-lg-3 lapki-skeleton-col">
                <div class="lapki-card lapki-card--skeleton">
                    <div class="lapki-card__img skeleton"></div>
                    <div class="lapki-card__body">
                        <div class="skeleton" style="height:20px;width:60%;margin-bottom:8px;border-radius:4px"></div>
                        <div class="skeleton" style="height:14px;width:80%;border-radius:4px"></div>
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>

        <nav class="d-flex justify-content-center mt-4">
            <ul id="lapki-archive-pagination" class="pagination"></ul>
        </nav>
    </div>
</section>

<script>
window.lapkiArchiveInitial = {
    type: <?php echo wp_json_encode($initial_type); ?>,
    location: <?php echo wp_json_encode($initial_location); ?>,
    age: <?php echo wp_json_encode($initial_age); ?>,
    search: <?php echo wp_json_encode($initial_search); ?>,
    organization_id: <?php echo wp_json_encode($initial_organization_id ? (string) $initial_organization_id : ''); ?>
};
</script>

<?php get_footer(); ?>
