<?php
/**
 * Configurable customer form fields and secure attachment handling.
 *
 * @package StudioA7_Withdrawal
 */

defined('ABSPATH') || exit;

class A7_Withdrawal_Form_Fields
{
    private const ALLOWED_TYPES = array('checkbox', 'radio', 'select', 'multiselect', 'text', 'textarea', 'html', 'upload');

    /** @return array<int, array<string, mixed>> */
    public function get_definitions(): array
    {
        $definitions = get_option('a7w_form_fields', array());
        return is_array($definitions) ? $definitions : array();
    }

    /**
     * Sanitizes the persisted field schema. HTML fields are deliberately limited
     * to the standard post allow-list; arbitrary scripts/forms are never stored.
     *
     * @param mixed $value Submitted settings value.
     * @return array<int, array<string, mixed>>
     */
    public function sanitize_definitions($value): array
    {
        if (is_string($value)) {
            $value = json_decode(wp_unslash($value), true);
        }
        if (!is_array($value)) {
            return array();
        }

        $clean = array();
        foreach ($value as $definition) {
            if (!is_array($definition)) {
                continue;
            }
            $key = sanitize_key($definition['key'] ?? '');
            $type = sanitize_key($definition['type'] ?? '');
            if ('' === $key || !in_array($type, self::ALLOWED_TYPES, true)) {
                continue;
            }
            $options = array();
            foreach ((array) ($definition['options'] ?? array()) as $option) {
                $option = sanitize_text_field($option);
                if ('' !== $option) {
                    $options[] = $option;
                }
            }
            $clean[] = array(
                'key' => $key,
                'type' => $type,
                'label' => sanitize_text_field($definition['label'] ?? $key),
                'required' => !empty($definition['required']),
                'options' => array_values(array_unique($options)),
                'html' => 'html' === $type ? wp_kses_post($definition['html'] ?? '') : '',
            );
        }
        return $clean;
    }

    /** Render fields into the existing step-one form. */
    public function render(): void
    {
        foreach ($this->get_definitions() as $field) {
            $key = (string) ($field['key'] ?? '');
            $type = (string) ($field['type'] ?? '');
            if ('' === $key || !in_array($type, self::ALLOWED_TYPES, true)) {
                continue;
            }
            if ('html' === $type) {
                echo '<div class="a7w-form__content">' . wp_kses_post((string) ($field['html'] ?? '')) . '</div>';
                continue;
            }
            $name = 'a7w_fields[' . $key . ']';
            $required = !empty($field['required']);
            echo '<div class="a7w-form__group">';
            echo '<label class="a7w-form__label" for="a7w-field-' . esc_attr($key) . '">' . esc_html((string) $field['label']) . ($required ? ' *' : '') . '</label>';
            if ('textarea' === $type) {
                echo '<textarea class="a7w-form__textarea" id="a7w-field-' . esc_attr($key) . '" name="' . esc_attr($name) . '"' . ($required ? ' required' : '') . '></textarea>';
            } elseif ('select' === $type || 'multiselect' === $type) {
                echo '<select class="a7w-form__input" id="a7w-field-' . esc_attr($key) . '" name="' . esc_attr($name) . ('multiselect' === $type ? '[]' : '') . '"' . ('multiselect' === $type ? ' multiple' : '') . ($required ? ' required' : '') . '>';
                if ('select' === $type) {
                    echo '<option value=""></option>';
                }
                foreach ((array) ($field['options'] ?? array()) as $option) {
                    echo '<option value="' . esc_attr($option) . '">' . esc_html($option) . '</option>';
                }
                echo '</select>';
            } elseif ('radio' === $type || 'checkbox' === $type) {
                foreach ((array) ($field['options'] ?? array()) as $index => $option) {
                    $id = 'a7w-field-' . $key . '-' . $index;
                    echo '<label class="a7w-checkbox" for="' . esc_attr($id) . '"><input id="' . esc_attr($id) . '" type="' . esc_attr($type) . '" name="' . esc_attr($name) . ('checkbox' === $type ? '[]' : '') . '" value="' . esc_attr($option) . '"' . ($required && 0 === $index ? ' required' : '') . '> ' . esc_html($option) . '</label>';
                }
            } elseif ('upload' === $type) {
                echo '<input class="a7w-form__input" id="a7w-field-' . esc_attr($key) . '" type="file" name="a7w_upload_' . esc_attr($key) . '" accept=".pdf,.jpg,.jpeg,.png"' . ($required ? ' required' : '') . '>';
            } else {
                echo '<input class="a7w-form__input" id="a7w-field-' . esc_attr($key) . '" type="text" name="' . esc_attr($name) . '"' . ($required ? ' required' : '') . '>';
            }
            echo '</div>';
        }
    }

    /** @return array<string, mixed>|\WP_Error */
    public function collect_submission(array $submitted, array $files)
    {
        $data = array();
        foreach ($this->get_definitions() as $field) {
            $key = (string) ($field['key'] ?? '');
            $type = (string) ($field['type'] ?? '');
            if ('' === $key || 'html' === $type) {
                continue;
            }
            if ('upload' === $type) {
                $file_key = 'a7w_upload_' . $key;
                if (empty($files[$file_key]['name'])) {
                    if (!empty($field['required'])) {
                        return new \WP_Error('required_field', sprintf(__('Pole „%s” jest wymagane.', 'studio-a7-odstap'), $field['label']));
                    }
                    continue;
                }
                $upload = $this->handle_upload($files[$file_key]);
                if (is_wp_error($upload)) {
                    return $upload;
                }
                $data[$key] = $upload;
                continue;
            }
            $value = $submitted[$key] ?? ('checkbox' === $type || 'multiselect' === $type ? array() : '');
            $value = is_array($value) ? array_values(array_filter(array_map('sanitize_text_field', $value), 'strlen')) : sanitize_textarea_field($value);
            if (!empty($field['required']) && ('' === $value || array() === $value)) {
                return new \WP_Error('required_field', sprintf(__('Pole „%s” jest wymagane.', 'studio-a7-odstap'), $field['label']));
            }
            if ('' !== $value && array() !== $value) {
                $data[$key] = $value;
            }
        }
        return $data;
    }

    /** @return array<string, string>|\WP_Error */
    private function handle_upload(array $file)
    {
        if (!function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        $allowed = array('pdf' => 'application/pdf', 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png');
        $checked = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], $allowed);
        if (empty($checked['ext']) || empty($checked['type']) || (int) $file['size'] > 5 * MB_IN_BYTES) {
            return new \WP_Error('invalid_upload', __('Załącznik musi być plikiem PDF, JPG lub PNG o rozmiarze do 5 MB.', 'studio-a7-odstap'));
        }
        $result = wp_handle_upload($file, array('test_form' => false, 'mimes' => $allowed));
        if (!empty($result['error'])) {
            return new \WP_Error('upload_failed', sanitize_text_field($result['error']));
        }
        return array('url' => esc_url_raw($result['url']), 'file' => sanitize_text_field(wp_basename($result['file'])), 'type' => sanitize_text_field($result['type']));
    }
}
