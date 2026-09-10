<?php

/**
 * Lapki AI
 *
 * Універсальна архітектура для підключення ШІ-провайдерів (Gemini, і надалі —
 * будь-які інші за тим самим контрактом) для функцій на кшталт "Покращити
 * опис за допомогою ШІ". Кожен провайдер — окремий клас-нащадок
 * Lapki_AI_Provider, що сам оголошує потрібні йому налаштування (ключ API
 * тощо) — сторінка налаштувань (Lapki_Admin) рендерить ці поля автоматично
 * для КОЖНОГО зареєстрованого провайдера, без хардкоду під конкретний.
 *
 * @package Lapki
 * @author Oleksii Bugrov
 */

/**
 * Базовий контракт ШІ-провайдера. Нащадок реалізує лише саму логіку виклику
 * зовнішнього API — все спільне (читання/перевірка налаштувань за іменем
 * опції) вже тут.
 */
abstract class Lapki_AI_Provider {

    /** Унікальний слаг провайдера, напр. 'gemini'. Використовується як частина імені опцій. */
    abstract public function get_id();

    /** Людська назва для сторінки налаштувань і вибору дефолтного провайдера. */
    abstract public function get_label();

    /**
     * Поля налаштувань, які потрібні цьому провайдеру (типово — API-ключ, +
     * вибір моделі). Кожне поле:
     * ['id' => 'api_key', 'label' => '...', 'type' => 'password'|'text'|'select',
     *  'help' => '...' (необов'язково, HTML), 'required' => true (за замовчуванням),
     *  'default' => '' (необов'язково),
     *  'options' => [['value' => '...', 'label' => '...'], ...] (обов'язково для type='select')].
     *
     * КОНВЕНЦІЯ ДЛЯ ВСІХ НАСТУПНИХ ПРОВАЙДЕРІВ: поле вибору моделі (типово
     * 'model') — це ЗАВЖДИ 'select' із заздалегідь заданим списком 'options',
     * а не вільний текст 'text'. Адмін обирає зі списку реальних назв моделей
     * провайдера, а не вгадує/копіює точний рядок і не ламає інтеграцію
     * друкарською помилкою чи застарілою назвою знятої моделі. Якщо провайдер
     * підтримує рівно одну модель без варіантів — окреме поле для неї взагалі
     * не потрібне (жорстко закодувати в improve_text()).
     */
    abstract public function get_settings_fields();

    /**
     * Покращити текст. Повертає або WP_Error, або масив
     * ['text' => string покращений текст,
     *  'usage' => ['prompt_tokens' => int, 'completion_tokens' => int, 'total_tokens' => int]].
     * $context — необов'язкові додаткові дані (напр. яке саме поле форми).
     *
     * 'usage' — те, що реально повернув API провайдера (для статистики,
     * Lapki → Статистика). Якщо провайдер не звітує токени — повертати
     * ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0],
     * а не пропускати ключ (щоб виклик Lapki_AI_Usage_Log::log() лишався
     * однаковим для всіх провайдерів).
     */
    abstract public function improve_text($text, $context = []);

    /** Ім'я опції в БД для конкретного поля налаштувань цього провайдера. */
    public function get_option_name($field_id) {
        return 'lapki_ai_' . $this->get_id() . '_' . $field_id;
    }

    /** Значення налаштування (з фолбеком на 'default' з опису поля, якщо порожнє). */
    protected function get_setting($field_id, $default = '') {
        $value = get_option($this->get_option_name($field_id), '');
        return $value !== '' ? $value : $default;
    }

    /** Чи заповнені всі обов'язкові поля (типово — чи є API-ключ). */
    public function is_configured() {
        foreach ($this->get_settings_fields() as $field) {
            $required = $field['required'] ?? true;
            if ($required && get_option($this->get_option_name($field['id']), '') === '') {
                return false;
            }
        }
        return true;
    }
}

/**
 * Реєстр провайдерів + вибір дефолтного. Провайдери підключаються в init()
 * нижче — щоб додати новий, достатньо дописати require + register() тут,
 * решта (налаштування, REST-ендпоінт) підхоплює його автоматично.
 */
class Lapki_AI_Manager {

    private static $providers = [];

    public static function init() {
        require_once LAPKI_PLUGIN_DIR . 'inc/class-lapki-ai-providers.php';

        self::register(new Lapki_AI_Gemini_Provider());
    }

    public static function register(Lapki_AI_Provider $provider) {
        self::$providers[$provider->get_id()] = $provider;
    }

    /** @return Lapki_AI_Provider[] */
    public static function get_providers() {
        return self::$providers;
    }

    public static function get_provider($id) {
        return self::$providers[$id] ?? null;
    }

    /**
     * Дефолтний провайдер: опція `lapki_ai_default_provider`, якщо вказана й
     * досі зареєстрована, інакше — перший зареєстрований (поки провайдер
     * лише один, вибір в адмінці не показується — див. Lapki_Admin).
     */
    public static function get_default_provider_id() {
        $default = get_option('lapki_ai_default_provider', '');
        if ($default && isset(self::$providers[$default])) {
            return $default;
        }

        $ids = array_keys(self::$providers);
        return $ids ? $ids[0] : '';
    }

    public static function get_default_provider() {
        return self::get_provider(self::get_default_provider_id());
    }
}
