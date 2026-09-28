<?php

namespace App\Domain\PersonalData;

use RuntimeException;

final class BlindIndex
{
    public function for(string $normalizedValue): string
    {
        $key = (string) config('personal-data.blind_index_key');

        if ($key === '') {
            throw new RuntimeException('The personal data blind-index key is not configured.');
        }

        return hash_hmac('sha256', $normalizedValue, $key);
    }
}
