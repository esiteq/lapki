<?php

/**
 * Class EQ_Form
 *
 * Provides a flexible form builder for WordPress.
 * Supports multiple storage modes: options, custom post types, and custom tables.
 */
class EQ_Form {
    protected $fields = [];
    protected $values = [];
    protected $data = [];
    protected $mode;
    protected $option_id;
    protected $post_type;
    protected $table;

    public function __construct($args = []) {
        $defaults = [
            'save_to'   => 'options',
            'option_id' => 'eq_form',
            'post_type' => '',
            'table'     => '',
        ];
        $args = wp_parse_args($args, $defaults);

        $this->mode      = $args['save_to'];
        $this->option_id = $args['option_id'];
        $this->post_type = $args['post_type'];
        $this->table     = $args['table'];

        if ($this->mode === 'options' && $this->option_id) {
            $this->values = get_option($this->option_id, []);
        }
    }

    public function get_post() {
        $this->data = [];
        foreach ($this->fields as $field) {
            $id = $field['id'];
            if ($field['type'] === 'checkbox') {
                $this->data[$id] = isset($_POST[$id]) ? '1' : '0';
            } elseif ($field['type'] === 'checkbox-group') {
                $this->data[$id] = isset($_POST[$id]) ? array_map('sanitize_text_field', (array) $_POST[$id]) : [];
            } elseif ($field['type'] === 'radio-group') {
                $this->data[$id] = isset($_POST[$id]) ? sanitize_text_field($_POST[$id]) : '';
            } elseif ($field['type'] === 'table') {
                $this->data[$id] = isset($_POST[$id]) ? $_POST[$id] : [];
            } else {
                $this->data[$id] = isset($_POST[$id]) ? sanitize_text_field($_POST[$id]) : '';
            }
        }
    }

    public function add_field($field) {
        $defaults = [
            'type' => 'text',
            'id' => '',
            'label' => '',
            'required' => false,
            'value' => '',
            'fields' => [],
            'values' => [],
        ];
        $field = wp_parse_args($field, $defaults);

        if (isset($this->values[$field['id']])) {
            $field['value'] = $this->values[$field['id']];
        }

        $this->fields[] = $field;
    }

    public function display() {
        echo '<form method="post">';
        echo '<table class="form-table">';
        foreach ($this->fields as $field) {
            echo '<tr>';
            echo '<th><label for="' . esc_attr($field['id']) . '">' . esc_html($field['label']) . (!empty($field['required']) ? ' <span class="required">*</span>' : '') . '</label></th>';
            echo '<td>';
            switch ($field['type']) {
                case 'hidden':
                    echo '<input type="hidden" name="' . esc_attr($field['id']) . '" value="' . esc_attr($field['value']) . '" />';
                    break;
                case 'radio-group':
                    foreach ($field['values'] as $key => $label) {
                        $checked = ($field['value'] === $key) ? 'checked' : '';
                        echo '<label style="margin-right: 1em;">';
                        echo '<input type="radio" name="' . esc_attr($field['id']) . '" value="' . esc_attr($key) . '" ' . $checked . ' />';
                        echo ' ' . esc_html($label);
                        echo '</label>';
                    }
                    break;
                case 'checkbox-group':
                    if (!is_array($field['value'])) {
                        $field['value'] = [];
                    }
                    foreach ($field['values'] as $key => $label) {
                        $checked = in_array($key, $field['value']) ? 'checked' : '';
                        echo '<label style="margin-right: 1em;">';
                        echo '<input type="checkbox" name="' . esc_attr($field['id']) . '[]" value="' . esc_attr($key) . '" ' . $checked . ' />';
                        echo ' ' . esc_html($label);
                        echo '</label>';
                    }
                    break;
                case 'textarea':
                    echo '<textarea name="' . esc_attr($field['id']) . '" class="large-text">' . esc_textarea($field['value']) . '</textarea>';
                    break;
                case 'select':
                    $required = !empty($field['required']) ? ' required' : '';
                    echo '<select name="' . esc_attr($field['id']) . '"' . $required . '>';
                    foreach ($field['values'] as $key => $label) {
                        echo '<option value="' . esc_attr($key) . '" ' . selected($field['value'], $key, false) . '>' . esc_html($label) . '</option>';
                    }
                    echo '</select>';
                    break;
                case 'checkbox':
                    echo '<input type="checkbox" name="' . esc_attr($field['id']) . '" value="1" ' . checked($field['value'], '1', false) . ' />';
                    break;
                case 'table':
                    $this->render_table_field($field);
                    break;
                default:
                    $val = is_array($field['value']) ? '' : esc_attr($field['value']);
                    $required = !empty($field['required']) ? ' required' : '';
                    echo '<input type="text" name="' . esc_attr($field['id']) . '" value="' . $val . '" class="regular-text"' . $required . ' />';
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
        submit_button();
        echo '</form>';
        ?>
        <script>
        jQuery(document).ready(function($) {
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
                    // Optional: reindexing inputs if needed
                }
            }).disableSelection();

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
                            $.each(field.values, function(k, v) {
                                row += '<option value="' + k + '">' + v + '</option>';
                            });
                            row += '</select></td>';
                            break;
                        case 'checkbox':
                            row += '<td><input type="hidden" name="' + name + '" value="0" />' +
                                   '<input type="checkbox" name="' + name + '" value="1"' + required + ' /></td>';
                            break;
                        default:
                            row += '<td><input type="text" name="' + name + '" value=""' + required + ' /></td>';
                    }
                });
                row += '<td><a href="#" class="eq-remove-row">✖</a></td>';
                row += "</tr>";
                tbody.append(row);
            });

            $(document).on("click", ".eq-remove-row", function(e) {
                e.preventDefault();
                $(this).closest("tr").remove();
            });
        });
        </script>
        <?php
    }

    private function render_table_field($field) {
        echo '<div class="eq-table-wrapper">';
        echo '<table class="widefat" data-field-id="' . esc_attr($field['id']) . '" data-fields=\'' . json_encode($field['fields']) . '\'>';
        echo '<thead><tr>';
        foreach ($field['fields'] as $sub) {
            echo '<th>' . esc_html($sub['label']) . (!empty($sub['required']) ? ' <span class="required">*</span>' : '') . '</th>';
        }
        echo '<th></th></tr></thead><tbody>';

        $rows = is_array($field['value']) ? $field['value'] : [];
        foreach ($rows as $row) {
            echo $this->render_table_row($field['id'], $field['fields'], $row);
        }

        echo '</tbody></table>';
        echo '<button class="button eq-add-row">Add row</button>';
        echo '</div>';
    }

    private function render_table_row($field_id, $fields, $row = []) {
        static $row_index = 0;
        $html = '<tr>';
        foreach ($fields as $sub) {
            $sub_id = $sub['id'];
            $val = isset($row[$sub_id]) ? $row[$sub_id] : '';
            $name = esc_attr($field_id) . '[' . $row_index . '][' . esc_attr($sub_id) . ']';
            $required = (!empty($sub['required'])) ? ' required' : '';
            switch ($sub['type']) {
                case 'select':
                    $html .= '<td><select name="' . $name . '"' . $required . '>';
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
                default:
                    $html .= '<td><input type="text" name="' . $name . '" value="' . esc_attr($val) . '"' . $required . ' /></td>';
            }
        }
        $html .= '<td><a href="#" class="eq-remove-row">✖</a></td>';
        $html .= '</tr>';
        $row_index++;
        return $html;
    }

    public function save() {
        if ($this->mode === 'options' && $this->option_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->get_post();
            update_option($this->option_id, $this->data);
        }
    }
}
?>