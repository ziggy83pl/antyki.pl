<?php
declare(strict_types=1);

namespace App\Core;

class Validator {
    
    /**
     * Sprawdza zestaw reguł i wyrzuca ValidationException po napotkaniu pierwszego błędu
     *
     * @param array $data Dane do walidacji (np. $_POST)
     * @param array $rules Tablica reguł ['field_name' => ['required', 'email']]
     * @param array $messages Opcjonalne własne wiadomości błędów
     * @throws ValidationException
     */
    public static function validate(array $data, array $rules, array $messages = []): void {
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            
            foreach ($fieldRules as $rule) {
                // Parsowanie reguł z argumentami np. min:5
                $ruleArgs = [];
                if (is_string($rule) && str_contains($rule, ':')) {
                    $parts = explode(':', $rule);
                    $rule = $parts[0];
                    $ruleArgs = explode(',', $parts[1]);
                }
                
                $error = self::checkRule($field, $value, $rule, $ruleArgs, $messages);
                if ($error) {
                    throw new ValidationException($error, [$field => $error]);
                }
            }
        }
    }
    
    private static function checkRule(string $field, $value, string $rule, array $args, array $messages): ?string {
        // Pomijamy puste wartości dla reguł innych niż 'required' (opcjonalność)
        if ($rule !== 'required' && ($value === null || $value === '')) {
            return null;
        }

        switch ($rule) {
            case 'required':
                if ($value === null || $value === '') {
                    return $messages["{$field}.required"] ?? lang('Field is required') . ": {$field}";
                }
                break;
                
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return $messages["{$field}.email"] ?? lang('Invalid email format');
                }
                break;
                
            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    return $messages["{$field}.url"] ?? lang('Invalid URL format');
                }
                break;
                
            case 'min':
                $min = (int)$args[0];
                if (is_string($value) && mb_strlen($value) < $min) {
                    return $messages["{$field}.min"] ?? lang('Minimum length is') . " {$min}";
                } elseif (is_numeric($value) && $value < $min) {
                    return $messages["{$field}.min"] ?? lang('Minimum value is') . " {$min}";
                }
                break;
                
            case 'max':
                $max = (int)$args[0];
                if (is_string($value) && mb_strlen($value) > $max) {
                    return $messages["{$field}.max"] ?? lang('Maximum length is') . " {$max}";
                } elseif (is_numeric($value) && $value > $max) {
                    return $messages["{$field}.max"] ?? lang('Maximum value is') . " {$max}";
                }
                break;
                
            case 'in':
                if (!in_array($value, $args, true) && !in_array((string)$value, $args, true)) {
                    return $messages["{$field}.in"] ?? lang('Invalid value selected');
                }
                break;
        }
        
        return null;
    }
}
