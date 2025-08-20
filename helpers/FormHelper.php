<?php

class FormHelper {
    
    

    public static function csrfToken() {
        return '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
    }
    
    

    public static function input($name, $type = 'text', $options = []) {
        $value = $options['value'] ?? '';
        $placeholder = $options['placeholder'] ?? '';
        $required = $options['required'] ?? false;
        $class = $options['class'] ?? 'mt-1 block w-full p-2 border border-gray-300 rounded-md';
        $id = $options['id'] ?? $name;
        
        $attributes = [
            'type' => $type,
            'id' => $id,
            'name' => $name,
            'class' => $class,
            'value' => htmlspecialchars($value)
        ];
        
        if ($placeholder) $attributes['placeholder'] = $placeholder;
        if ($required) $attributes['required'] = 'required';
        
        $attributeString = '';
        foreach ($attributes as $key => $val) {
            $attributeString .= " {$key}=\"{$val}\"";
        }
        
        return "<input{$attributeString}>";
    }
    
    

    public static function select($name, $options = [], $selected = '', $attributes = []) {
        $class = $attributes['class'] ?? 'mt-1 block w-full p-2 border border-gray-300 rounded-md';
        $required = $attributes['required'] ?? false;
        $id = $attributes['id'] ?? $name;
        
        $html = "<select id=\"{$id}\" name=\"{$name}\" class=\"{$class}\"";
        if ($required) $html .= ' required';
        $html .= '>';
        
        foreach ($options as $value => $label) {
            $selectedAttr = ($value == $selected) ? ' selected' : '';
            $html .= "<option value=\"{$value}\"{$selectedAttr}>{$label}</option>";
        }
        
        $html .= '</select>';
        return $html;
    }
    
    

    public static function textarea($name, $value = '', $options = []) {
        $rows = $options['rows'] ?? 3;
        $placeholder = $options['placeholder'] ?? '';
        $required = $options['required'] ?? false;
        $class = $options['class'] ?? 'mt-1 block w-full p-2 border border-gray-300 rounded-md';
        $id = $options['id'] ?? $name;
        
        $html = "<textarea id=\"{$id}\" name=\"{$name}\" rows=\"{$rows}\" class=\"{$class}\"";
        if ($placeholder) $html .= " placeholder=\"{$placeholder}\"";
        if ($required) $html .= ' required';
        $html .= '>' . htmlspecialchars($value) . '</textarea>';
        
        return $html;
    }
    
    

    public static function label($for, $text, $icon = null, $required = false) {
        $html = "<label for=\"{$for}\" class=\"block text-sm font-medium text-gray-700 flex items-center\">";
        if ($icon) {
            $html .= "<i class=\"{$icon} mr-2 text-blue-600\"></i>";
        }
        $html .= $text;
        if ($required) {
            $html .= '<span class="text-red-500 ml-1">*</span>';
        }
        $html .= '</label>';
        
        return $html;
    }
    
    

        public static function button($text, $type = 'button', $options = []) {
        $attributes = [
            'type' => $type,
            'class' => 'bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700'
        ];

        

        $attributes = array_merge($attributes, $options);

        

        $icon = $attributes['icon'] ?? null;
        unset($attributes['icon']);

        $attributeString = '';
        foreach ($attributes as $key => $val) {
            if ($val !== null) {
                $attributeString .= " {$key}=\"" . htmlspecialchars($val) . "\"";
            }
        }

        $html = "<button{$attributeString}";
        $html .= '>';
        
        if ($icon) {
            $html .= "<i class=\"{$icon} mr-1\"></i>";
        }
        $html .= $text . '</button>';
        
        return $html;
    }
    
    

    public static function notification($message, $type = 'info') {
        $classes = [
            'success' => 'bg-green-500 text-white',
            'error' => 'bg-red-500 text-white',
            'warning' => 'bg-yellow-500 text-white',
            'info' => 'bg-blue-500 text-white'
        ];
        
        $class = $classes[$type] ?? $classes['info'];
        
        return "<div class=\"{$class} p-4 rounded mb-4\">{$message}</div>";
    }
    
    

    public static function modal($id, $title, $content, $options = []) {
        $size = $options['size'] ?? 'max-w-md';
        $icon = $options['icon'] ?? 'ri-information-line';
        
        return "
        <div id=\"{$id}\" class=\"fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden\" style=\"z-index: 1000;\">
            <div class=\"bg-white p-6 rounded-lg shadow-lg w-full {$size}\">
                <h2 class=\"text-xl font-bold mb-4 flex items-center\">
                    <i class=\"{$icon} mr-2 text-blue-600\"></i> {$title}
                </h2>
                {$content}
            </div>
        </div>";
    }
    
    

    public static function dataTable($id, $headers, $options = []) {
        $class = $options['class'] ?? 'min-w-full divide-y divide-gray-200';
        
        $html = "<table id=\"{$id}\" class=\"{$class}\">";
        $html .= '<thead class="bg-gray-50"><tr>';
        
        foreach ($headers as $header) {
            $html .= "<th class=\"px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">{$header}</th>";
        }
        
        $html .= '</tr></thead>';
        $html .= '<tbody class="bg-white divide-y divide-gray-200"></tbody>';
        $html .= '</table>';
        
        return $html;
    }
    
    

    public static function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            

            if (($rule['required'] ?? false) && empty($value)) {
                $errors[$field] = ($rule['messages']['required'] ?? "{$field} alanı zorunludur.");
                continue;
            }
            
            if (empty($value)) continue;
            
            

            switch ($rule['type'] ?? 'string') {
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field] = $rule['messages']['email'] ?? 'Geçersiz e-posta formatı.';
                    }
                    break;
                    
                case 'numeric':
                    if (!is_numeric($value)) {
                        $errors[$field] = $rule['messages']['numeric'] ?? 'Bu alan sayısal olmalıdır.';
                    }
                    break;
                    
                case 'string':
                    $min = $rule['min'] ?? 0;
                    $max = $rule['max'] ?? 255;
                    $length = strlen($value);
                    
                    if ($length < $min) {
                        $errors[$field] = $rule['messages']['min'] ?? "Bu alan en az {$min} karakter olmalıdır.";
                    } elseif ($length > $max) {
                        $errors[$field] = $rule['messages']['max'] ?? "Bu alan en fazla {$max} karakter olmalıdır.";
                    }
                    break;
            }
        }
        
        return $errors;
    }
    
    

    public static function form($action, $method = 'POST', $fields = [], $options = []) {
        $class = $options['class'] ?? 'bg-white p-6 rounded-lg shadow-lg';
        $enctype = $options['enctype'] ?? '';
        
        $html = "<form action=\"{$action}\" method=\"{$method}\" class=\"{$class}\"";
        if ($enctype) $html .= " enctype=\"{$enctype}\"";
        $html .= '>';
        
        

        $html .= self::csrfToken();
        
        

        foreach ($fields as $field) {
            $html .= '<div class="mb-4">';
            
            if ($field['type'] === 'hidden') {
                $html .= self::input($field['name'], 'hidden', ['value' => $field['value'] ?? '']);
            } else {
                

                if (isset($field['label'])) {
                    $html .= self::label(
                        $field['name'], 
                        $field['label'], 
                        $field['icon'] ?? null, 
                        $field['required'] ?? false
                    );
                }
                
                

                switch ($field['type']) {
                    case 'select':
                        $html .= self::select($field['name'], $field['options'] ?? [], $field['value'] ?? '', $field);
                        break;
                    case 'textarea':
                        $html .= self::textarea($field['name'], $field['value'] ?? '', $field);
                        break;
                    default:
                        $html .= self::input($field['name'], $field['type'], $field);
                        break;
                }
            }
            
            $html .= '</div>';
        }
        
        

        if (isset($options['submit'])) {
            $html .= '<div class="flex justify-end">';
            $html .= self::button($options['submit']['text'], 'submit', $options['submit']);
            $html .= '</div>';
        }
        
        $html .= '</form>';
        
        return $html;
    }
}
?>
