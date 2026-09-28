<?php

namespace App\Actions\PersonalData;

use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveAddress
{
    /** @param array<string, string|bool|null> $data */
    public function execute(User $user, ?int $addressId, array $data): Address
    {
        return DB::transaction(function () use ($user, $addressId, $data) {
            $addresses = $user->addresses()->lockForUpdate()->oldest()->get();
            $address = $addressId ? $addresses->firstWhere('id', $addressId) : new Address;

            if ($addressId && ! $address) {
                throw ValidationException::withMessages(['addressLabel' => 'Endereço não encontrado.']);
            }

            $isFirst = $addresses->where('id', '!=', $addressId)->isEmpty();
            $makePrimary = (bool) $data['is_primary'];
            $shouldBePrimary = $isFirst || $makePrimary || ($address->exists && $address->is_primary);

            if ($makePrimary) {
                $user->addresses()->update(['is_primary' => false]);
            }

            $postalDigits = preg_replace('/\D/', '', (string) $data['postal_code']) ?? '';

            $address->fill([
                'label' => $data['label'],
                'recipient_encrypted' => $data['recipient'],
                'postal_code_encrypted' => substr($postalDigits, 0, 5).'-'.substr($postalDigits, 5, 3),
                'street_encrypted' => $data['street'],
                'number_encrypted' => $data['number'],
                'complement_encrypted' => $data['complement'] ?: null,
                'district_encrypted' => $data['district'],
                'city_encrypted' => $data['city'],
                'state_encrypted' => strtoupper((string) $data['state']),
                'is_primary' => $shouldBePrimary,
            ]);
            $user->addresses()->save($address);

            return $address->refresh();
        });
    }
}
