<?php

namespace App\Actions\PersonalData;

use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteAddress
{
    public function execute(User $user, int $addressId): void
    {
        DB::transaction(function () use ($user, $addressId) {
            $addresses = $user->addresses()->lockForUpdate()->oldest()->get();
            /** @var Address|null $address */
            $address = $addresses->firstWhere('id', $addressId);

            if (! $address) {
                throw ValidationException::withMessages(['addressLabel' => 'Endereço não encontrado.']);
            }

            $wasPrimary = $address->is_primary;
            $address->delete();

            if ($wasPrimary) {
                $user->addresses()->oldest()->first()?->update(['is_primary' => true]);
            }
        });
    }
}
