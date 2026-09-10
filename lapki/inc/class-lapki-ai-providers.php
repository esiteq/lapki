<?php

/**
 * Lapki AI Providers
 *
 * Конкретні реалізації Lapki_AI_Provider. Файл підвантажується лениво лише
 * з Lapki_AI_Manager::init() (inc/class-lapki-ai.php) — щоб додати новий
 * провайдер, достатньо дописати клас тут і зареєструвати його там.
 *
 * @package Lapki
 * @author Oleksii Bugrov
 */

class Lapki_AI_Gemini_Provider extends Lapki_AI_Provider {

    // "-latest" аліаси, не версійні назви (gemini-2.5-flash тощо) — Google
    // регулярно знімає версійні назви з обслуговування для нових ключів
    // (сталось із 2.5-*/2.0-* влітку 2026, живий ключ повертав 404 "no
    // longer available to new users"), а аліаси самі перемикаються на
    // актуальну модель без правок коду.
    const DEFAULT_MODEL = 'gemini-flash-latest';

    public function get_id() {
        return 'gemini';
    }

    public function get_label() {
        return 'Google Gemini';
    }

    public function get_settings_fields() {
        return [
            [
                'id' => 'api_key',
                'label' => 'API ключ',
                'type' => 'password',
                'help' => 'Безкоштовний ключ: <a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener">aistudio.google.com/apikey</a>',
            ],
            [
                'id' => 'model',
                'label' => 'Модель',
                'type' => 'select',
                'required' => false,
                'default' => self::DEFAULT_MODEL,
                'options' => [
                    ['value' => 'gemini-flash-latest', 'label' => 'Gemini Flash — latest (рекомендовано — швидка й безкоштовна)'],
                    ['value' => 'gemini-flash-lite-latest', 'label' => 'Gemini Flash-Lite — latest (найшвидша й найдешевша)'],
                    ['value' => 'gemini-pro-latest', 'label' => 'Gemini Pro — latest (найякісніша; на момент перевірки безкоштовний ліміт = 0, потрібен платний тариф)'],
                ],
                'help' => 'Перевірено живим запитом станом на серпень 2026. Навмисно "-latest"-аліаси, не версійні назви (gemini-2.5-flash тощо) — Google регулярно знімає версійні назви з обслуговування, а аліаси самі перемикаються на актуальну модель. Якщо якийсь варіант перестане працювати — перевірте актуальний список на aistudio.google.com і оновіть тут (inc/class-lapki-ai-providers.php).',
            ],
        ];
    }

    public function improve_text($text, $context = []) {
        $api_key = $this->get_setting('api_key');
        if (empty($api_key)) {
            return new WP_Error('ai_not_configured', 'Gemini API ключ не налаштований у Lapki → Налаштування.');
        }

        $text = trim((string) $text);
        if ($text === '') {
            return new WP_Error('ai_empty_text', 'Немає тексту для покращення.');
        }

        $model = $this->get_setting('model', self::DEFAULT_MODEL);
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($api_key);

        $facts_block = '';
        $facts = [];
        foreach ($context as $label => $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }
            $facts[] = '- ' . $label . ': ' . $value;
        }
        if (!empty($facts)) {
            $facts_block = "\n\nВідомі факти про тварину з форми (уже введені користувачем — врахуй їх "
                . "природно в тексті, НЕ просто перерахуй списком):\n" . implode("\n", $facts);
        }

        $prompt = "Ти редактор описів тварин на сайті прилаштування (як petfinder). "
            . "Покращ наведений нижче опис тварини українською мовою: виправ орфографію й пунктуацію, "
            . "зроби текст теплим і привабливим для потенційних власників. "
            . "ВАЖЛИВО: не вигадуй нових фактів, яких немає в оригінальному тексті чи в наведених нижче "
            . "відомих фактах, не додавай деталей, яких там немає. "
            . "Поверни ТІЛЬКИ сам покращений текст опису, без пояснень, лапок чи заголовків.\n\n"
            . "Оригінальний текст:\n" . $text
            . $facts_block;

        $response = wp_remote_post($url, [
            'timeout' => 30,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode([
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                ],
            ]),
        ]);

        if (is_wp_error($response)) {
            // wp_remote_post() на цьому рівні падає лише на транспортних
            // помилках (cURL) — сам Gemini ще нічого не відповів. Розрізняємо
            // тайм-аут (найчастіший випадок — Google довго думає чи мережа
            // сервера повільна до googleapis.com) від інших збоїв з'єднання,
            // щоб у статистиці (Lapki → Статистика) було видно причину, а не
            // сирий текст cURL-помилки на очах у користувача форми.
            $raw_message = $response->get_error_message();
            if (stripos($raw_message, 'timed out') !== false) {
                return new WP_Error('ai_timeout', 'Gemini не відповів вчасно (тайм-аут 30с). Спробуйте ще раз за кілька хвилин.');
            }
            return new WP_Error('ai_connection_failed', 'Не вдалося з\'єднатися з Gemini API: ' . $raw_message);
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200) {
            return $this->build_api_error($code, $body['error']['message'] ?? '');
        }

        $finish_reason = $body['candidates'][0]['finishReason'] ?? '';
        if ($finish_reason === 'SAFETY' || $finish_reason === 'RECITATION') {
            return new WP_Error('ai_blocked', 'Gemini відмовився обробити цей текст (спрацював фільтр безпеки).');
        }

        $improved = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $improved = trim($improved);

        // Модель іноді все одно обгортає відповідь у лапки — прибрати, якщо
        // вони охоплюють увесь текст цілком (а не є частиною самого опису).
        if (strlen($improved) > 1 && $improved[0] === '"' && substr($improved, -1) === '"') {
            $improved = trim(substr($improved, 1, -1));
        }

        if ($improved === '') {
            return new WP_Error('ai_empty_response', 'Gemini повернув порожню відповідь.');
        }

        $usage = $body['usageMetadata'] ?? [];

        return [
            'text' => $improved,
            'usage' => [
                'prompt_tokens' => (int) ($usage['promptTokenCount'] ?? 0),
                'completion_tokens' => (int) ($usage['candidatesTokenCount'] ?? 0),
                'total_tokens' => (int) ($usage['totalTokenCount'] ?? 0),
            ],
        ];
    }

    /**
     * Людяна українська помилка за HTTP-кодом відповіді Gemini. Розрізняємо
     * найчастіші реальні випадки (перевірено живими помилками з ключа
     * власника сайту, не вигадано):
     * - 429 — вичерпано безкоштовний ліміт запитів ("Quota exceeded...
     *   Please retry in 41.6s" — час очікування витягуємо з тексту Google,
     *   якщо він там є, і показуємо адміну людяно);
     * - 503 — модель тимчасово перевантажена ("high demand");
     * - решта — сирий текст від Google як є (код `ai_request_failed`,
     *   як і раніше), бо наперед передбачити всі варіанти неможливо.
     */
    private function build_api_error($code, $raw_message) {
        if ($code === 429) {
            $retry_hint = ' Спробуйте ще раз трохи пізніше.';
            if (preg_match('/retry in ([0-9.]+)\s*s/i', $raw_message, $m)) {
                $retry_hint = ' Спробуйте ще раз приблизно через ' . (int) ceil((float) $m[1]) . ' с.';
            }
            return new WP_Error('ai_quota_exceeded', 'Вичерпано безкоштовний ліміт запитів до Gemini на сьогодні.' . $retry_hint);
        }

        if ($code === 503) {
            return new WP_Error('ai_overloaded', 'Gemini зараз перевантажений (висока завантаженість моделі на боці Google). Спробуйте ще раз за хвилину.');
        }

        $message = $raw_message !== '' ? $raw_message : ('Помилка Gemini API (HTTP ' . $code . ')');
        return new WP_Error('ai_request_failed', $message);
    }
}
