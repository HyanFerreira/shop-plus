<?php

namespace App\Actions\PersonalData;

use App\Models\Phone;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeletePhone
{
    public function execute(User $user, int $phoneId): void
    {
        DB::transaction(function () use ($user, $phoneId) {
            $phones = $user->phones()->lockForUpdate()->oldest()->get();
            /** @var Phone|null $phone */
            $phone = $phones->firstWhere('id', $phoneId);

            if (! $phone) {
                throw ValidationException::withMessages([
                    'phoneNumber' => 'Telefone não encontrado.',
                ]);
            }

            $wasPrimary = $phone->is_primary;
            $phone->delete();

            if ($wasPrimary) {
                $user->phones()->oldest()->first()?->update(['is_primary' => true]);
            }
        });
    }
}
