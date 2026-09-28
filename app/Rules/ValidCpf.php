<?php

namespace App\Rules;

use App\Domain\PersonalData\Cpf;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCpf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Cpf::isValid($value)) {
            $fail('O campo :attribute deve conter um CPF sintético válido.');
        }
    }
}
