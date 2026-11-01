<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Usage: $errors = Validator::validate($data, ['email' => 'required|email', 'qty' => 'required|numeric|min:1']);
 */
class Validator
{
    public static function validate(array $data, array $rules, array $labels = []): array
    {
        $errors = [];
        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $label = $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
            $isEmpty = $value === null || $value === '';

            foreach (explode('|', $ruleString) as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);

                if ($name === 'required' && $isEmpty) {
                    $errors[$field] = "$label is required.";
                    break;
                }
                if ($isEmpty) {
                    continue; // other rules only apply when a value is present
                }
                $msg = match ($name) {
                    'email'      => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "$label must be a valid email address.",
                    'numeric'    => is_numeric($value) ? null : "$label must be a number.",
                    'integer'    => filter_var($value, FILTER_VALIDATE_INT) !== false ? null : "$label must be a whole number.",
                    'min'        => (is_numeric($value) && (float) $value < (float) $arg) ? "$label must be at least $arg." : null,
                    'max'        => (is_numeric($value) && (float) $value > (float) $arg) ? "$label must not be more than $arg." : null,
                    'min_length' => mb_strlen((string) $value) < (int) $arg ? "$label must be at least $arg characters." : null,
                    'max_length' => mb_strlen((string) $value) > (int) $arg ? "$label must not exceed $arg characters." : null,
                    'in'         => in_array((string) $value, explode(',', (string) $arg), true) ? null : "$label has an invalid value.",
                    'date'       => (\DateTime::createFromFormat('Y-m-d', (string) $value) !== false) ? null : "$label must be a valid date.",
                    'same'       => $value === ($data[$arg] ?? null) ? null : "$label does not match.",
                    default      => null,
                };
                if ($msg) {
                    $errors[$field] = $msg;
                    break;
                }
            }
        }
        return $errors;
    }
}
