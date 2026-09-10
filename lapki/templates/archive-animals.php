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
        <h1 class="h3 fw-bold mb-4"><?php esc_html_e('Тварини, що шукають дім', 'lapki'); ?></h1>

        <form id="lapki-archive-filters" class="row g-2 mb-4">
            <div class="col-6 col-md-2">
                <select name="type" class="form-select" id="filter-type">
                    <option value=""><?php esc_html_e('Всі тварини', 'lapki'); ?></option>
                    <option value="dog">🐕 <?php esc_html_e('Собаки', 'lapki'); ?></option>
                    <option value="cat">🐈 <?php esc_html_e('Коти', 'lapki'); ?></option>
                    <option value="bird">🐦 <?php esc_html_e('Птахи', 'lapki'); ?></option>
                    <option value="rabbit">🐇 <?php esc_html_e('Кролики', 'lapki'); ?></option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="age" class="form-select" id="filter-age">
                    <option value=""><?php esc_html_e('Будь-який вік', 'lapki'); ?></option>
                    <option value="baby"><?php esc_html_e('Малюк', 'lapki'); ?></option>
                    <option value="young"><?php esc_html_e('Молодий', 'lapki'); ?></option>
                    <option value="adult"><?php esc_html_e('Дорослий', 'lapki'); ?></option>
                    <option value="senior"><?php esc_html_e('Похилого віку', 'lapki'); ?></option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="organization_id" class="form-select" id="filter-organization">
                    <option value=""><?php esc_html_e('Всі притулки', 'lapki'); ?></option>
                </select>
            </div>
            <div class="col-6 col-md-2 lapki-city-field">
                <select id="filter-location-select" class="lapki-city-select"></select>
                <input type="hidden" name="location" data-city-name>
                <input type="hidden" data-city-katottg>
                <input type="hidden" id="filter-location-lat" data-city-lat>
                <input type="hidden" id="filter-location-lon" data-city-lon>
            </div>
            <div class="col-6 col-md-2">
                <input type="text" name="search" id="filter-search" class="form-control" placeholder="<?php esc_attr_e('Кличка', 'lapki'); ?>">
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn lapki-btn-green w-100"><?php esc_html_e('Шукати', 'lapki'); ?></button>
            </div>

            <div class="col-12">
                <div id="lapki-geo-row" class="d-flex align-items-center flex-wrap gap-2">
                    <div class="form-check d-flex align-items-center gap-2 mb-0">
                        <input class="form-check-input mt-0" type="checkbox" id="filter-geo-enabled">
                        <label class="form-check-label" for="filter-geo-enabled"><?php esc_html_e('Також показувати тварин в радіусі', 'lapki'); ?></label>
                    </div>
                    <input type="number" id="filter-geo-distance" class="form-control form-control-sm" style="width:90px" value="50" min="1" max="1000">
                    <label for="filter-geo-distance" class="mb-0"><?php esc_html_e('км від мого населеного пункта', 'lapki'); ?></label>
                    <span id="lapki-geo-status" class="small fst-italic"></span>
                </div>
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
