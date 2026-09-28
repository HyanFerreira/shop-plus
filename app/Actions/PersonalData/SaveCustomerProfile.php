<?php

namespace App\Actions\PersonalData;

use App\Domain\PersonalData\BlindIndex;
use App\Domain\PersonalData\Cpf;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveCustomerProfile
{
    public function __construct(private readonly BlindIndex $blindIndex)
    {
    }

    public function execute(User $user, string $cpfInput, ?string $birthDate): CustomerProfile
    {
        $cpf = Cpf::from($cpfInput);
        $hash = $this->blindIndex->for($cpf->digits());

        try {
            return DB::transaction(function () use ($user, $cpf, $hash, $birthDate) {
                $profile = $user->customerProfile()->lockForUpdate()->first();

                $alreadyUsed = CustomerProfile::query()
                    ->where('cpf_hash', $hash)
                    ->when($profile, fn ($query) => $query->whereKeyNot($profile->getKey()))
                    ->exists();

                if ($alreadyUsed) {
                    throw ValidationException::withMessages([
                        'cpf' => 'Não foi possível salvar o CPF informado.',
                    ]);
                }

                $profile ??= new CustomerProfile;
                $profile->fill([
                    'cpf_encrypted' => $cpf->formatted(),
                    'cpf_hash' => $hash,
                    'birth_date' => $birthDate ?: null,
                ]);

                $user->customerProfile()->save($profile);

                return $profile->refresh();
            });
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                throw ValidationException::withMessages([
                    'cpf' => 'Não foi possível salvar o CPF informado.',
                ]);
            }

            throw $exception;
        }
    }
}
