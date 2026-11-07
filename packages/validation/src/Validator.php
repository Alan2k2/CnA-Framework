<?php

declare(strict_types=1);

namespace CnA\Validation;

use CnA\Contracts\Exceptions\ValidationException;

class Validator
{
    protected array $errors = [];

    public function __construct(
        protected array $data = [],
        protected array $rules = [],
        protected array $customMessages = []
    ) {
    }

    public static function make(array $data, array $rules, array $messages = []): static
    {
        return new static($data, $rules, $messages);
    }

    public function validate(): array
    {
        if ($this->fails()) {
            throw new ValidationException($this->errors());
        }
        return $this->validated();
    }

    public function passes(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $fieldRules) {
            $rulesList = is_string($fieldRules) ? explode('|', $fieldRules) : (array) $fieldRules;
            $value = $this->data[$field] ?? null;

            foreach ($rulesList as $ruleStr) {
                [$rule, $param] = array_pad(explode(':', $ruleStr, 2), 2, null);

                if ($rule === 'required' && ($value === null || $value === '' || (is_array($value) && empty($value)))) {
                    $this->addError($field, $rule, "The {$field} field is required.");
                    continue 2;
                }

                // If not required and value is null/empty, skip further checks
                if ($value === null || $value === '') {
                    continue;
                }

                $this->validateRule($field, $rule, $param, $value);
            }
        }

        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        return array_intersect_key($this->data, $this->rules);
    }

    protected function validateRule(string $field, string $rule, ?string $param, mixed $value): void
    {
        match ($rule) {
            'string' => is_string($value) ?: $this->addError($field, $rule, "The {$field} field must be a string."),
            'integer', 'int' => (filter_var($value, FILTER_VALIDATE_INT) !== false) ?: $this->addError($field, $rule, "The {$field} field must be an integer."),
            'numeric' => is_numeric($value) ?: $this->addError($field, $rule, "The {$field} field must be a number."),
            'boolean', 'bool' => (is_bool($value) || in_array($value, [0, 1, '0', '1', true, false], true)) ?: $this->addError($field, $rule, "The {$field} field must be true or false."),
            'email' => (filter_var($value, FILTER_VALIDATE_EMAIL) !== false) ?: $this->addError($field, $rule, "The {$field} field must be a valid email address."),
            'min' => $this->validateMin($field, $param, $value),
            'max' => $this->validateMax($field, $param, $value),
            'in' => $this->validateIn($field, $param, $value),
            'confirmed' => $this->validateConfirmed($field, $value),
            'array' => is_array($value) ?: $this->addError($field, $rule, "The {$field} field must be an array."),
            default => null,
        };
    }

    protected function validateMin(string $field, ?string $param, mixed $value): void
    {
        $min = (float) $param;
        if (is_numeric($value) && $value < $min) {
            $this->addError($field, 'min', "The {$field} field must be at least {$param}.");
        } elseif (is_string($value) && mb_strlen($value) < (int) $param) {
            $this->addError($field, 'min', "The {$field} field must be at least {$param} characters.");
        } elseif (is_array($value) && count($value) < (int) $param) {
            $this->addError($field, 'min', "The {$field} field must have at least {$param} items.");
        }
    }

    protected function validateMax(string $field, ?string $param, mixed $value): void
    {
        $max = (float) $param;
        if (is_numeric($value) && $value > $max) {
            $this->addError($field, 'max', "The {$field} field must not exceed {$param}.");
        } elseif (is_string($value) && mb_strlen($value) > (int) $param) {
            $this->addError($field, 'max', "The {$field} field must not exceed {$param} characters.");
        } elseif (is_array($value) && count($value) > (int) $param) {
            $this->addError($field, 'max', "The {$field} field must not have more than {$param} items.");
        }
    }

    protected function validateIn(string $field, ?string $param, mixed $value): void
    {
        $options = explode(',', (string) $param);
        if (!in_array((string) $value, $options, true)) {
            $this->addError($field, 'in', "The selected {$field} is invalid.");
        }
    }

    protected function validateConfirmed(string $field, mixed $value): void
    {
        $confirmation = $this->data["{$field}_confirmation"] ?? null;
        if ($value !== $confirmation) {
            $this->addError($field, 'confirmed', "The {$field} confirmation does not match.");
        }
    }

    protected function addError(string $field, string $rule, string $defaultMessage): void
    {
        $key = "{$field}.{$rule}";
        $message = $this->customMessages[$key] ?? $this->customMessages[$field] ?? $defaultMessage;
        $this->errors[$field][] = $message;
    }
}

