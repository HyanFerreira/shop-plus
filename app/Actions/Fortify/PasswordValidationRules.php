<?php

namespace App\Actions\Fortify;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function passwordRules(bool $forRegistration = false): array
    {
        // Clone so registration never mutates defaults used by password reset/update.
        $policy = clone Password::default();
        if ($forRegistration) {
            $policy->letters()->mixedCase()->symbols();
        }

        return ['required', 'string', 'max:128', $policy, 'confirmed'];
    }
}
