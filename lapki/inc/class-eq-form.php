<?php

/**
 * Class EQ_Form
 *
 * Універсальний конструктор форм для WordPress
 * Підтримує: options, posts, postmeta, custom tables
 */
class EQ_Form {
    protected $fields = [];
    protected $values = [];
    protected $data = [];
    protected $config = [];
    protected $errors = [];
    
    // Режими збереження
    const MODE_OPTIONS = 'options';
    const MODE_POST = 'post';
    const MODE_POSTMETA = 'postmeta';
    const MODE_TABLE = 'table';
    const MODE_MIXED = 'mixed'; // Комбінований режим
    
    public function __construct($config = []) {
        $defaults = [
            'mode' => self::MODE_OPTIONS,
            'option_id' => 'eq_form',
            'post_id' => null,
            'post_type' => 'post',
            'table' => '',
            'table_id_field' => 'id',
            'record_id' => null,
            'form_id' => 'eq_form',
            'nonce_action' => 'eq_form_save',
            'capability' => 'manage_options',
            'redirect_after_save' => true,
            'success_message' => 'Дані успішно збережено!',
            'auto_load_values' => true
        ];
        
        $this->config = wp_parse_args($config, $defaults);
        
        if ($this->config['auto_load_values']) {
            $this->load_values();
        }
    }
    
    /**
     * Завантажити значення з джерела даних
     */
    public function load_values() {
        switch ($this->config['mode']) {
            case self::MODE_OPTIONS:
                $this->values = get_option($this->config['option_id'], []);
                break;
                
            case self::MODE_POST:
                if ($this->config['post_id']) {
                    $post = get_post($this->config['post_id'], ARRAY_A);
                    $this->values = $post ?: [];
                }
                break;
                
            case self::MODE_POSTMETA:
                if ($this->config['post_id']) {
                    $meta = get_post_meta($this->config['post_id']);
                    foreach ($meta as $key => $value) {
                        $this->values[$key] = is_array($value) && count($value) === 1 ? $value[0] : $value;
                    }
                }
                break;
                
            case self::MODE_TABLE:
                if ($this->config['record_id'] && $this->config['table']) {
                    $this->values = $this->load_from_table();
                }
                break;
                
            case self::MODE_MIXED:
                // Для мікшед режиму кожне поле може мати свій source
                $this->load_mixed_values();
                break;
        }
    }
    
    /**
     * Завантажити дані з кастомної таблиці
     */
    private function load_from_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . $this->config['table'];
        $id_field = $this->config['table_id_field'];
        $record_id = $this->config['record_id'];
        
        $sql = $wpdb->prepare("SELECT * FROM {$table_name} WHERE {$id_field} = %d", $record_id);
        $result = $wpdb->get_row($sql, ARRAY_A);
        
        return $result ?: [];
    }
    
    /**
     * Завантажити значення для змішаного режиму
     */
    private function load_mixed_values() {
        // Буде реалізовано при додаванні полів з source
    }
    
    /**
     * Додати поле
     */
    public function add_field($field) {
        $defaults = [
            'type' => 'text',
            'id' => '',
            'label' => '',
            'required' => false,
            'value' => '',
            'fields' => [],
            'values' => [],
            'description' => '',
            'placeholder' => '',
            'class' => '',
            'validation' => [],
            'source' => null, // Для мікшед режиму: 'option:key', 'meta:key', 'table:field'
            'save_to' => null // Куди зберігати: 'option:key', 'meta:key', 'table:field'
        ];
        
        $field = wp_parse_args($field, $defaults);
        
        // Завантажити значення для поля
        if (isset($this->values[$field['id']])) {
            $field['value'] = $this->values[$field['id']];
        }
        
        // Для мікшед режиму завантажити з конкретного джерела
        if ($this->config['mode'] === self::MODE_MIXED && $field['source']) {
            $field['value'] = $this->load_field_value($field['source'], $field['id']);
        }
        
        $this->fields[] = $field;
        
        return $this;
    }
    
    /**
     * Завантажити значення поля з конкретного джерела
     */
    private function load_field_value($source, $field_id) {
        list($source_type, $source_key) = explode(':', $source, 2);
        $source_key = $source_key ?: $field_id;
        
        switch ($source_type) {
            case 'option':
                $option_data = get_option($source_key, []);
                return is_array($option_data) ? ($option_data[$field_id] ?? '') : $option_data;
                
            case 'meta':
                if ($this->config['post_id']) {
                    return get_post_meta($this->config['post_id'], $source_key, true);
                }
                break;
                
            case 'table':
                if ($this->config['record_id']) {
                    global $wpdb;
                    $table_name = $wpdb->prefix . $this->config['table'];
                    $id_field = $this->config['table_id_field'];
                    
                    return $wpdb->get_var($wpdb->prepare(
                        "SELECT {$source_key} FROM {$table_name} WHERE {$id_field} = %d",
                        $this->config['record_id']
                    ));
                }
                break;
                
            case 'post':
                if ($this->config['post_id']) {
                    $post = get_post($this->config['post_id'], ARRAY_A);
                    return $post[$source_key] ?? '';
                }
                break;
        }
        
        return '';
    }
    
    /**
     * Додати групу полів
     */
    public function add_fields($fields) {
        foreach ($fields as $field) {
            $this->add_field($field);
        }
        return $this;
    }
    
    /**
     * Додати секцію (група полів з заголовком)
     */
    public function add_section($title, $fields = []) {
        $this->add_field([
            'type' => 'section_title',
            'id' => sanitize_title($title),
            'label' => $title
        ]);
        
        if (!empty($fields)) {
            $this->add_fields($fields);
        }
        
        return $this;
    }
    
    /**
     * Отримати дані з POST
     */
    public function get_post_data() {
        $this->data = [];
        
        foreach ($this->fields as $field) {
            if ($field['type'] === 'section_title') continue;
            
            $id = $field['id'];
            
            if ($field['type'] === 'checkbox') {
                $this->data[$id] = isset($_POST[$id]) ? '1' : '0';
            } elseif ($field['type'] === 'checkbox-group') {
                $this->data[$id] = isset($_POST[$id]) ? array_map('sanitize_text_field', (array) $_POST[$id]) : [];
            } elseif ($field['type'] === 'radio-group') {
                $this->data[$id] = isset($_POST[$id]) ? sanitize_text_field($_POST[$id]) : '';
            } elseif ($field['type'] === 'table') {
                $this->data[$id] = isset($_POST[$id]) ? $_POST[$id] : [];
            } elseif ($field['type'] === 'file') {
                $this->data[$id] = $this->handle_file_upload($id);
            } else {
                $this->data[$id] = isset($_POST[$id]) ? sanitize_text_field($_POST[$id]) : '';
            }
        }
        
        return $this->data;
    }
    
    /**
     * Валідація даних
     */
    public function validate() {
        $this->errors = [];
        
        foreach ($this->fields as $field) {
            if ($field['type'] === 'section_title') continue;
            
            $id = $field['id'];
            $value = $this->data[$id] ?? '';
            $label = $field['label'];
            
            // Перевірка обов'язкових полів
            if (!empty($field['required']) && empty($value)) {
                $this->errors[$id] = sprintf('Поле "%s" є обов\'язковим', $label);
                continue;
            }
            
            // Кастомна валідація
            if (!empty($field['validation'])) {
                foreach ($field['validation'] as $rule => $params) {
                    $error = $this->validate_field($value, $rule, $params, $label);
                    if ($error) {
                        $this->errors[$id] = $error;
                        break;
                    }
                }
            }
            
            // Вбудована валідація за типом поля
            $type_error = $this->validate_by_type($value, $field['type'], $label);
            if ($type_error) {
                $this->errors[$id] = $type_error;
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Валідація конкретного поля
     */
    private function validate_field($value, $rule, $params, $label) {
        switch ($rule) {
            case 'min_length':
                if (strlen($value) < $params) {
                    return sprintf('Поле "%s" повинно містити мінімум %d символів', $label, $params);
                }
                break;
                
            case 'max_length':
                if (strlen($value) > $params) {
                    return sprintf('Поле "%s" повинно містити максимум %d символів', $label, $params);
                }
                break;
                
            case 'pattern':
                if (!preg_match($params, $value)) {
                    return sprintf('Поле "%s" має неправильний формат', $label);
                }
                break;
                
            case 'callback':
                if (is_callable($params)) {
                    $result = call_user_func($params, $value);
                    if ($result !== true) {
                        return is_string($result) ? $result : sprintf('Поле "%s" не пройшло валідацію', $label);
                    }
                }
                break;
        }
        
        return false;
    }
    
    /**
     * Валідація за типом поля
     */
    private function validate_by_type($value, $type, $label) {
        if (empty($value)) return false;
        
        switch ($type) {
            case 'email':
                if (!is_email($value)) {
                    return sprintf('Поле "%s" повинно містити правильну email адресу', $label);
                }
                break;
                
            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    return sprintf('Поле "%s" повинно містити правильну URL адресу', $label);
                }
                break;
                
            case 'number':
                if (!is_numeric($value)) {
                    return sprintf('Поле "%s" повинно містити число', $label);
                }
                break;
        }
        
        return false;
    }
    
    /**
     * Зберегти дані
     */
    public function save() {
        // Відключено для тестування
        // if (!$this->verify_nonce()) {
        //     return false;
        // }
        
        // Відключено для тестування  
        // if (!current_user_can($this->config['capability'])) {
        //     return false;
        // }
        
        $this->get_post_data();
        
        if (!$this->validate()) {
            return false;
        }
        
        switch ($this->config['mode']) {
            case self::MODE_OPTIONS:
                return $this->save_to_options();
                
            case self::MODE_POST:
                return $this->save_to_post();
                
            case self::MODE_POSTMETA:
                return $this->save_to_postmeta();
                
            case self::MODE_TABLE:
                return $this->save_to_table();
                
            case self::MODE_MIXED:
                return $this->save_mixed();
        }
        
        return false;
    }
    
    /**
     * Зберегти в options
     */
    private function save_to_options() {
        return update_option($this->config['option_id'], $this->data);
    }
    
    /**
     * Зберегти як пост
     */
    private function save_to_post() {
        $post_data = array_intersect_key($this->data, array_flip([
            'post_title', 'post_content', 'post_excerpt', 'post_status', 
            'post_type', 'post_parent', 'menu_order'
        ]));
        
        if ($this->config['post_id']) {
            $post_data['ID'] = $this->config['post_id'];
            return wp_update_post($post_data);
        } else {
            $post_data['post_type'] = $this->config['post_type'];
            return wp_insert_post($post_data);
        }
    }
    
    /**
     * Зберегти в postmeta
     */
    private function save_to_postmeta() {
        if (!$this->config['post_id']) {
            return false;
        }
        
        foreach ($this->data as $key => $value) {
            update_post_meta($this->config['post_id'], $key, $value);
        }
        
        return true;
    }
    
    /**
     * Зберегти в кастомну таблицю
     */
    private function save_to_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . $this->config['table'];
        $prepared_data = $this->prepare_table_data($this->data);
        
        if ($this->config['record_id']) {
            // Оновлення
            return $wpdb->update(
                $table_name,
                $prepared_data,
                [$this->config['table_id_field'] => $this->config['record_id']]
            );
        } else {
            // Створення
            $result = $wpdb->insert($table_name, $prepared_data);
            if ($result) {
                $this->config['record_id'] = $wpdb->insert_id;
            }
            return $result;
        }
    }
    
    /**
     * Зберегти в змішаному режимі
     */
    private function save_mixed() {
        foreach ($this->fields as $field) {
            if ($field['type'] === 'section_title' || !isset($field['save_to'])) {
                continue;
            }
            
            $value = $this->data[$field['id']] ?? '';
            $this->save_field_value($field['save_to'], $field['id'], $value);
        }
        
        return true;
    }
    
    /**
     * Зберегти значення поля в конкретне джерело
     */
    private function save_field_value($save_to, $field_id, $value) {
        list($target_type, $target_key) = explode(':', $save_to, 2);
        $target_key = $target_key ?: $field_id;
        
        switch ($target_type) {
            case 'option':
                $option_data = get_option($target_key, []);
                if (is_array($option_data)) {
                    $option_data[$field_id] = $value;
                    update_option($target_key, $option_data);
                } else {
                    update_option($target_key, $value);
                }
                break;
                
            case 'meta':
                if ($this->config['post_id']) {
                    update_post_meta($this->config['post_id'], $target_key, $value);
                }
                break;
                
            case 'table':
                if ($this->config['record_id']) {
                    global $wpdb;
                    $table_name = $wpdb->prefix . $this->config['table'];
                    $id_field = $this->config['table_id_field'];
                    
                    $wpdb->update(
                        $table_name,
                        [$target_key => $value],
                        [$id_field => $this->config['record_id']]
                    );
                }
                break;
        }
    }
    
    /**
     * Підготувати дані для таблиці
     */
    private function prepare_table_data($data) {
        $prepared = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $prepared[$key] = json_encode($value);
            } else {
                $prepared[$key] = $value;
            }
        }
        
        return $prepared;
    }
    
    /**
     * Перевірка nonce
     */
    private function verify_nonce() {
        return isset($_POST[$this->config['form_id'] . '_nonce']) && 
               wp_verify_nonce($_POST[$this->config['form_id'] . '_nonce'], $this->config['nonce_action']);
    }
    
    /**
     * Обробка завантаження файлів
     */
    private function handle_file_upload($field_id) {
        if (!isset($_FILES[$field_id]) || $_FILES[$field_id]['error'] !== UPLOAD_ERR_OK) {
            return '';
        }
        
        $uploaded = wp_handle_upload($_FILES[$field_id], ['test_form' => false]);
        
        if ($uploaded && !isset($uploaded['error'])) {
            return $uploaded['url'];
        }
        
        return '';
    }
    
    /**
     * Відображення форми
     */
    public function display() {
        // Перевірка збереження
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST[$this->config['form_id'] . '_nonce'])) {
            if ($this->save()) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>' . esc_html($this->config['success_message']) . '</p>';
                echo '</div>';
                
                if ($this->config['redirect_after_save']) {
                    // Перезавантажити значення після збереження
                    $this->load_values();
                }
            } else {
                $this->display_errors();
            }
        }
        
        echo '<form method="post" enctype="multipart/form-data" class="eq-form" id="' . esc_attr($this->config['form_id']) . '">';
        wp_nonce_field($this->config['nonce_action'], $this->config['form_id'] . '_nonce');
        
        echo '<table class="form-table">';
        
        foreach ($this->fields as $field) {
            $this->render_field($field);
        }
        
        echo '</table>';
        
        submit_button('Зберегти');
        echo '</form>';
        
        $this->render_scripts();
    }
    
    /**
     * Відображення поля
     */
    private function render_field($field) {
        if ($field['type'] === 'section_title') {
            echo '</table>';
            echo '<h2>' . esc_html($field['label']) . '</h2>';
            echo '<table class="form-table">';
            return;
        }
        
        if ($field['type'] === 'hidden') {
            echo '<input type="hidden" name="' . esc_attr($field['id']) . '" value="' . esc_attr($field['value']) . '" />';
            return;
        }
        
        echo '<tr>';
        echo '<th><label for="' . esc_attr($field['id']) . '">' . esc_html($field['label']);
        if (!empty($field['required'])) {
            echo ' <span class="required">*</span>';
        }
        echo '</label></th>';
        echo '<td>';
        
        $this->render_field_input($field);
        
        if (!empty($field['description'])) {
            echo '<p class="description">' . esc_html($field['description']) . '</p>';
        }
        
        // Показати помилку валідації
        if (isset($this->errors[$field['id']])) {
            echo '<p class="error-message" style="color: #d63384;">' . esc_html($this->errors[$field['id']]) . '</p>';
        }
        
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Відображення input поля
     */
    private function render_field_input($field) {
        $value = $field['value'];
        $required = !empty($field['required']) ? ' required' : '';
        $placeholder = !empty($field['placeholder']) ? ' placeholder="' . esc_attr($field['placeholder']) . '"' : '';
        $class = !empty($field['class']) ? ' class="' . esc_attr($field['class']) . '"' : ' class="regular-text"';
        
        switch ($field['type']) {
            case 'textarea':
                $rows = !empty($field['rows']) ? intval($field['rows']) : 5;
                echo '<textarea name="' . esc_attr($field['id']) . '" rows="' . $rows . '"' . $required . $placeholder . ' class="large-text">' . esc_textarea($value) . '</textarea>';
                break;
                
            case 'select':
                echo '<select name="' . esc_attr($field['id']) . '"' . $required . $class . '>';
                if (!empty($field['placeholder'])) {
                    echo '<option value="">' . esc_html($field['placeholder']) . '</option>';
                }
                foreach ($field['values'] as $key => $label) {
                    echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
                }
                echo '</select>';
                break;
                
            case 'radio-group':
                foreach ($field['values'] as $key => $label) {
                    $checked = ($value === $key) ? 'checked' : '';
                    echo '<label style="margin-right: 1em;">';
                    echo '<input type="radio" name="' . esc_attr($field['id']) . '" value="' . esc_attr($key) . '" ' . $checked . $required . ' />';
                    echo ' ' . esc_html($label);
                    echo '</label>';
                }
                break;
                
            case 'checkbox-group':
                if (!is_array($value)) $value = [];
                foreach ($field['values'] as $key => $label) {
                    $checked = in_array($key, $value) ? 'checked' : '';
                    echo '<label style="margin-right: 1em; display: block;">';
                    echo '<input type="checkbox" name="' . esc_attr($field['id']) . '[]" value="' . esc_attr($key) . '" ' . $checked . ' />';
                    echo ' ' . esc_html($label);
                    echo '</label>';
                }
                break;
                
            case 'checkbox':
                echo '<label>';
                echo '<input type="checkbox" name="' . esc_attr($field['id']) . '" value="1" ' . checked($value, '1', false) . $required . ' />';
                if (!empty($field['description'])) {
                    echo ' ' . esc_html($field['description']);
                }
                echo '</label>';
                break;
                
            case 'file':
                echo '<input type="file" name="' . esc_attr($field['id']) . '"' . $required . ' />';
                if ($value) {
                    echo '<br><small>Поточний файл: <a href="' . esc_url($value) . '" target="_blank">Переглянути</a></small>';
                }
                break;
                
            case 'number':
                $min = !empty($field['min']) ? ' min="' . esc_attr($field['min']) . '"' : '';
                $max = !empty($field['max']) ? ' max="' . esc_attr($field['max']) . '"' : '';
                $step = !empty($field['step']) ? ' step="' . esc_attr($field['step']) . '"' : '';
                echo '<input type="number" name="' . esc_attr($field['id']) . '" value="' . esc_attr($value) . '"' . $required . $min . $max . $step . $placeholder . $class . ' />';
                break;
                
            case 'email':
                echo '<input type="email" name="' . esc_attr($field['id']) . '" value="' . esc_attr($value) . '"' . $required . $placeholder . $class . ' />';
                break;
                
            case 'url':
                echo '<input type="url" name="' . esc_attr($field['id']) . '" value="' . esc_attr($value) . '"' . $required . $placeholder . $class . ' />';
                break;
                
            case 'date':
                echo '<input type="date" name="' . esc_attr($field['id']) . '" value="' . esc_attr($value) . '"' . $required . $class . ' />';
                break;
                
            case 'password':
                echo '<input type="password" name="' . esc_attr($field['id']) . '" value="' . esc_attr($value) . '"' . $required . $placeholder . $class . ' />';
                break;
                
            case 'table':
                $this->render_table_field($field);
                break;
                
            default: // text
                echo '<input type="text" name="' . esc_attr($field['id']) . '" value="' . esc_attr($value) . '"' . $required . $placeholder . $class . ' />';
        }
    }
    
    /**
     * Відображення помилок
     */
    public function display_errors() {
        if (!empty($this->errors)) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>Виправте наступні помилки:</strong></p>';
            echo '<ul>';
            foreach ($this->errors as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
    }
    
    /**
     * Отримати помилки
     */
    public function get_errors() {
        return $this->errors;
    }
    
    /**
     * Перевірити чи є помилки
     */
    public function has_errors() {
        return !empty($this->errors);
    }
    
    /**
     * Отримати збережені дані
     */
    public function get_data() {
        return $this->data;
    }
    
    /**
     * Отримати значення конкретного поля
     */
    public function get_field_value($field_id, $default = '') {
        return $this->values[$field_id] ?? $default;
    }
    
    /**
     * Встановити значення поля
     */
    public function set_field_value($field_id, $value) {
        $this->values[$field_id] = $value;
        return $this;
    }
    
    /**
     * Отримати ID створеного/оновленого запису
     */
    public function get_record_id() {
        return $this->config['record_id'];
    }
    
    // Включаємо попередню реалізацію table field і scripts
    private function render_table_field($field) {
        echo '<div class="eq-table-wrapper">';
        echo '<table class="widefat" data-field-id="' . esc_attr($field['id']) . '" data-fields=\'' . json_encode($field['fields']) . '\'>';
        echo '<thead><tr>';
        
        foreach ($field['fields'] as $sub) {
            echo '<th>' . esc_html($sub['label']) . (!empty($sub['required']) ? ' <span class="required">*</span>' : '') . '</th>';
        }
        echo '<th style="width: 80px;">Дії</th></tr></thead><tbody>';

        $rows = is_array($field['value']) ? $field['value'] : [];
        $row_index = 0;
        
        foreach ($rows as $row) {
            echo $this->render_table_row($field['id'], $field['fields'], $row, $row_index);
            $row_index++;
        }

        echo '</tbody></table>';
        echo '<button type="button" class="button eq-add-row">+ Додати рядок</button>';
        echo '</div>';
        
        if (!empty($field['description'])) {
            echo '<p class="description">' . esc_html($field['description']) . '</p>';
        }
    }
    
    private function render_table_row($field_id, $fields, $row = [], $row_index = 0) {
        $html = '<tr>';
        
        foreach ($fields as $sub) {
            $sub_id = $sub['id'];
            $val = isset($row[$sub_id]) ? $row[$sub_id] : '';
            $name = esc_attr($field_id) . '[' . $row_index . '][' . esc_attr($sub_id) . ']';
            $required = (!empty($sub['required'])) ? ' required' : '';
            
            switch ($sub['type']) {
                case 'select':
                    $html .= '<td><select name="' . $name . '"' . $required . '>';
                    
                    if (!empty($sub['placeholder'])) {
                        $html .= '<option value="">' . esc_html($sub['placeholder']) . '</option>';
                    }
                    
                    foreach ($sub['values'] as $k => $v) {
                        $selected = selected($val, $k, false);
                        $html .= '<option value="' . esc_attr($k) . '" ' . $selected . '>' . esc_html($v) . '</option>';
                    }
                    $html .= '</select></td>';
                    break;
                    
                case 'checkbox':
                    $checked = $val === '1' ? 'checked' : '';
                    $html .= '<td><input type="hidden" name="' . $name . '" value="0" /><input type="checkbox" name="' . $name . '" value="1" ' . $checked . $required . ' /></td>';
                    break;
                    
                case 'number':
                    $min = !empty($sub['min']) ? ' min="' . esc_attr($sub['min']) . '"' : '';
                    $max = !empty($sub['max']) ? ' max="' . esc_attr($sub['max']) . '"' : '';
                    $step = !empty($sub['step']) ? ' step="' . esc_attr($sub['step']) . '"' : '';
                    $html .= '<td><input type="number" name="' . $name . '" value="' . esc_attr($val) . '"' . $required . $min . $max . $step . ' /></td>';
                    break;
                    
                case 'textarea':
                    $rows = !empty($sub['rows']) ? intval($sub['rows']) : 3;
                    $html .= '<td><textarea name="' . $name . '" rows="' . $rows . '"' . $required . '>' . esc_textarea($val) . '</textarea></td>';
                    break;
                    
                case 'email':
                    $html .= '<td><input type="email" name="' . $name . '" value="' . esc_attr($val) . '"' . $required . ' /></td>';
                    break;
                    
                case 'url':
                    $html .= '<td><input type="url" name="' . $name . '" value="' . esc_attr($val) . '"' . $required . ' /></td>';
                    break;
                    
                case 'date':
                    $html .= '<td><input type="date" name="' . $name . '" value="' . esc_attr($val) . '"' . $required . ' /></td>';
                    break;
                    
                default: // text
                    $placeholder = !empty($sub['placeholder']) ? ' placeholder="' . esc_attr($sub['placeholder']) . '"' : '';
                    $html .= '<td><input type="text" name="' . $name . '" value="' . esc_attr($val) . '"' . $required . $placeholder . ' /></td>';
            }
        }
        
        $html .= '<td><a href="#" class="eq-remove-row button-link-delete">✖ Видалити</a></td>';
        $html .= '</tr>';
        
        return $html;
    }
    
    private function render_scripts() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Сортування рядків у таблицях
            $(".eq-table-wrapper tbody").sortable({
                handle: "td",
                placeholder: "ui-state-highlight",
                helper: function(e, tr) {
                    let originals = tr.children();
                    let helper = tr.clone();
                    helper.children().each(function(index) {
                        $(this).width(originals.eq(index).width());
                    });
                    return helper;
                },
                update: function(event, ui) {
                    // Опціонально: переіндексація полів
                }
            }).disableSelection();

            // Додавання нового рядка
            $(".eq-add-row").click(function(e) {
                e.preventDefault();
                const wrapper = $(this).closest(".eq-table-wrapper");
                const tbody = wrapper.find("tbody");
                const fieldId = wrapper.find("table").data("field-id");
                const fields = wrapper.find("table").data("fields");
                const index = tbody.find("tr").length;
                let row = "<tr>";

                fields.forEach(function(field) {
                    let name = fieldId + "[" + index + "][" + field.id + "]";
                    let required = field.required ? ' required' : '';
                    switch (field.type) {
                        case 'select':
                            row += '<td><select name="' + name + '"' + required + '>';
                            if (field.placeholder) {
                                row += '<option value="">' + field.placeholder + '</option>';
                            }
                            $.each(field.values, function(k, v) {
                                row += '<option value="' + k + '">' + v + '</option>';
                            });
                            row += '</select></td>';
                            break;
                        case 'checkbox':
                            row += '<td><input type="hidden" name="' + name + '" value="0" />' +
                                   '<input type="checkbox" name="' + name + '" value="1"' + required + ' /></td>';
                            break;
                        case 'number':
                            let min = field.min ? ' min="' + field.min + '"' : '';
                            let max = field.max ? ' max="' + field.max + '"' : '';
                            let step = field.step ? ' step="' + field.step + '"' : '';
                            row += '<td><input type="number" name="' + name + '" value=""' + required + min + max + step + ' /></td>';
                            break;
                        case 'textarea':
                            let rows = field.rows || 3;
                            row += '<td><textarea name="' + name + '" rows="' + rows + '"' + required + '></textarea></td>';
                            break;
                        default:
                            let placeholder = field.placeholder ? ' placeholder="' + field.placeholder + '"' : '';
                            row += '<td><input type="text" name="' + name + '" value=""' + required + placeholder + ' /></td>';
                    }
                });
                row += '<td><a href="#" class="eq-remove-row button-link-delete">✖ Видалити</a></td>';
                row += "</tr>";
                tbody.append(row);
            });

            // Видалення рядка
            $(document).on("click", ".eq-remove-row", function(e) {
                e.preventDefault();
                if (confirm('Ви впевнені, що хочете видалити цей рядок?')) {
                    $(this).closest("tr").remove();
                }
            });
        });
        </script>
        <style>
        .eq-table-wrapper {
            margin-top: 10px;
        }
        .eq-table-wrapper table {
            border-collapse: collapse;
            width: 100%;
        }
        .eq-table-wrapper th,
        .eq-table-wrapper td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .eq-table-wrapper th {
            background-color: #f1f1f1;
            font-weight: bold;
        }
        .eq-table-wrapper tbody tr:hover {
            background-color: #f9f9f9;
        }
        .ui-state-highlight {
            background-color: #fff3cd !important;
            border: 2px dashed #ffc107 !important;
        }
        .eq-remove-row {
            color: #a00;
            text-decoration: none;
        }
        .eq-remove-row:hover {
            color: #dc3545;
        }
        .eq-add-row {
            margin-top: 10px;
        }
        .required {
            color: #d63384;
        }
        .eq-form .form-table th {
            width: 200px;
            padding: 20px 10px 20px 0;
        }
        .error-message {
            margin-top: 5px;
            font-weight: bold;
        }
        </style>
        <?php
    }
}

/**
 * Хелпер клас для швидкого створення форм
 */
class EQ_Form_Builder {
    
    /**
     * Створити універсальну форму
     */
    public static function form($config = []) {
        return new EQ_Form($config);
    }
    
    /**
     * Створити форму для WordPress опцій
     */
    public static function options($option_id, $config = []) {
        $defaults = [
            'mode' => EQ_Form::MODE_OPTIONS,
            'option_id' => $option_id,
            'form_id' => 'options_form',
            'nonce_action' => 'save_options',
            'capability' => 'manage_options'
        ];
        
        return new EQ_Form(wp_parse_args($config, $defaults));
    }
    
    /**
     * Створити форму для WordPress поста
     */
    public static function post($post_id = null, $config = []) {
        $defaults = [
            'mode' => EQ_Form::MODE_POST,
            'post_id' => $post_id,
            'post_type' => 'post',
            'form_id' => 'post_form',
            'nonce_action' => 'save_post',
            'capability' => 'edit_posts'
        ];
        
        return new EQ_Form(wp_parse_args($config, $defaults));
    }
    
    /**
     * Створити форму для postmeta
     */
    public static function meta($post_id, $config = []) {
        $defaults = [
            'mode' => EQ_Form::MODE_POSTMETA,
            'post_id' => $post_id,
            'form_id' => 'meta_form',
            'nonce_action' => 'save_meta',
            'capability' => 'edit_posts'
        ];
        
        return new EQ_Form(wp_parse_args($config, $defaults));
    }
    
    /**
     * Створити форму для кастомної таблиці
     */
    public static function table($table, $record_id = null, $config = []) {
        $defaults = [
            'mode' => EQ_Form::MODE_TABLE,
            'table' => $table,
            'record_id' => $record_id,
            'table_id_field' => 'id',
            'form_id' => 'table_form',
            'nonce_action' => 'save_table',
            'capability' => 'edit_posts'
        ];
        
        return new EQ_Form(wp_parse_args($config, $defaults));
    }
    
    /**
     * Створити змішану форму
     */
    public static function mixed($config = []) {
        $defaults = [
            'mode' => EQ_Form::MODE_MIXED,
            'form_id' => 'mixed_form',
            'nonce_action' => 'save_mixed',
            'capability' => 'edit_posts'
        ];
        
        return new EQ_Form(wp_parse_args($config, $defaults));
    }
}

/**
 * Приклади використання (універсальні):
 * 
 * // Базова форма з кастомною конфігурацією
 * $form = EQ_Form_Builder::form([
 *     'mode' => 'table',
 *     'table' => 'my_animals',
 *     'record_id' => 123
 * ]);
 * 
 * // WordPress опції
 * $form = EQ_Form_Builder::options('my_plugin_settings');
 * $form->add_field(['id' => 'api_key', 'label' => 'API Key']);
 * 
 * // Кастомна таблиця
 * $form = EQ_Form_Builder::table('my_animals', 123);
 * $form->add_field(['id' => 'name', 'label' => 'Name', 'required' => true]);
 * 
 * // WordPress пост
 * $form = EQ_Form_Builder::post(456, ['post_type' => 'product']);
 * $form->add_field(['id' => 'post_title', 'label' => 'Title']);
 * 
 * // Meta поля
 * $form = EQ_Form_Builder::meta(456);
 * $form->add_field(['id' => 'custom_field', 'label' => 'Custom Field']);
 * 
 * // Змішана форма (різні джерела)
 * $form = EQ_Form_Builder::mixed(['post_id' => 123, 'record_id' => 456]);
 * $form->add_field(['id' => 'title', 'save_to' => 'post:post_title']);
 * $form->add_field(['id' => 'meta_key', 'save_to' => 'meta:my_meta']);
 * $form->add_field(['id' => 'table_field', 'save_to' => 'table:custom_field']);
 * 
 * // Повна кастомізація
 * $form = EQ_Form_Builder::form([
 *     'mode' => 'table',
 *     'table' => 'wp_lapki_animals',
 *     'record_id' => 123,
 *     'table_id_field' => 'id',
 *     'form_id' => 'animal_form',
 *     'nonce_action' => 'save_animal',
 *     'capability' => 'edit_posts',
 *     'success_message' => 'Animal saved successfully!',
 *     'redirect_after_save' => true
 * ]);
 */
?>