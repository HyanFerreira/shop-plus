<?php

namespace App\Rules;

use App\Domain\PersonalData\BrazilianPhone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidBrazilianPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! BrazilianPhone::isValid($value)) {
            $fail('O campo :attribute deve conter um telefone brasileiro válido.');
        }
    }
}
