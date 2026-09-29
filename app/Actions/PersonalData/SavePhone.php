<?php

namespace App\Actions\PersonalData;

use App\Domain\PersonalData\BlindIndex;
use App\Domain\PersonalData\BrazilianPhone;
use App\Enums\PhoneType;
use App\Models\Phone;
use App\Models\User;
use App\Support\SecurityAudit;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavePhone
{
    public function __construct(private readonly BlindIndex $blindIndex) {}

    public function execute(
        User $user,
        ?int $phoneId,
        string $numberInput,
        PhoneType $type,
        bool $makePrimary,
    ): Phone {
        $number = BrazilianPhone::from($numberInput);
        $hash = $this->blindIndex->for($number->digits());

        try {
            return DB::transaction(function () use ($user, $phoneId, $number, $hash, $type, $makePrimary) {
                $phones = $user->phones()->lockForUpdate()->oldest()->get();
                $phone = $phoneId ? $phones->firstWhere('id', $phoneId) : new Phone;

                if ($phoneId && ! $phone) {
                    throw $this->validationFailure('Telefone não encontrado.');
                }

                if ($phones->contains(fn (Phone $item) => $item->id !== $phoneId && hash_equals($item->number_hash, $hash))) {
                    throw $this->validationFailure('Não foi possível salvar o telefone informado.');
                }

                $isFirst = $phones->where('id', '!=', $phoneId)->isEmpty();
                $shouldBePrimary = $isFirst || $makePrimary || ($phone->exists && $phone->is_primary);

                if ($makePrimary) {
                    $user->phones()->update(['is_primary' => false]);
                }

                $phone->fill([
                    'number_encrypted' => $number->formatted(),
                    'number_hash' => $hash,
                    'type' => $type,
                    'is_primary' => $shouldBePrimary,
                ]);
                $user->phones()->save($phone);

                $phone->refresh();
                app(SecurityAudit::class)->record($user, $phoneId ? 'profile.phone_updated' : 'profile.phone_created', $phone);

                return $phone;
            });
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                throw $this->validationFailure('Não foi possível salvar o telefone informado.');
            }

            throw $exception;
        }
    }

    private function validationFailure(string $message): ValidationException
    {
        return ValidationException::withMessages(['phoneNumber' => $message]);
    }
}
