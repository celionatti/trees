<?php

declare(strict_types=1);

namespace Trees\Security;

class Validator
{
    private $data;
    private $rules;
    private $errors = [];
    
    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }
    
    public function validate(): bool
    {
        foreach ($this->rules as $field => $rules) {
            $value = $this->data[$field] ?? null;
            $ruleList = is_string($rules) ? explode('|', $rules) : $rules;
            
            foreach ($ruleList as $rule) {
                $this->validateRule($field, $value, $rule);
            }
        }
        
        return empty($this->errors);
    }
    
    public function errors(): array
    {
        return $this->errors;
    }
    
    private function validateRule(string $field, $value, string $rule): void
    {
        if (strpos($rule, ':') !== false) {
            [$rule, $params] = explode(':', $rule, 2);
            $params = explode(',', $params);
        } else {
            $params = [];
        }
        
        $method = 'validate' . str_replace('_', '', ucwords($rule, '_'));
        
        if (method_exists($this, $method)) {
            $this->$method($field, $value, $params);
        }
    }
    
    private function validateRequired(string $field, $value): void
    {
        if ($value === null || $value === '') {
            $this->errors[$field][] = "The {$field} field is required.";
        }
    }
    
    private function validateEmail(string $field, $value): void
    {
        if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "The {$field} must be a valid email address.";
        }
    }
    
    private function validateMin(string $field, $value, array $params): void
    {
        $min = (int) $params[0];
        
        if ($value !== null && strlen($value) < $min) {
            $this->errors[$field][] = "The {$field} must be at least {$min} characters.";
        }
    }
    
    private function validateMax(string $field, $value, array $params): void
    {
        $max = (int) $params[0];
        
        if ($value !== null && strlen($value) > $max) {
            $this->errors[$field][] = "The {$field} must not exceed {$max} characters.";
        }
    }
    
    private function validateNumeric(string $field, $value): void
    {
        if ($value !== null && !is_numeric($value)) {
            $this->errors[$field][] = "The {$field} must be a number.";
        }
    }
    
    private function validateUrl(string $field, $value): void
    {
        if ($value !== null && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field][] = "The {$field} must be a valid URL.";
        }
    }
    
    private function validateRegex(string $field, $value, array $params): void
    {
        $pattern = $params[0];
        
        if ($value !== null && !preg_match($pattern, $value)) {
            $this->errors[$field][] = "The {$field} format is invalid.";
        }
    }
    
    private function validateConfirmed(string $field, $value): void
    {
        $confirmField = $field . '_confirmation';
        $confirmValue = $this->data[$confirmField] ?? null;
        
        if ($value !== $confirmValue) {
            $this->errors[$field][] = "The {$field} confirmation does not match.";
        }
    }
}