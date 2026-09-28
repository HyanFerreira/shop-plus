<?php

namespace App\Livewire\PersonalData;

use App\Actions\PersonalData\DeleteAddress;
use App\Actions\PersonalData\SaveCustomerProfile;
use App\Actions\PersonalData\DeletePhone;
use App\Actions\PersonalData\SaveAddress;
use App\Actions\PersonalData\SavePhone;
use App\Domain\PersonalData\BrazilianPhone;
use App\Domain\PersonalData\Cpf;
use App\Enums\PhoneType;
use App\Models\Phone;
use App\Models\Address;
use App\Rules\ValidCpf;
use App\Rules\ValidBrazilianPhone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Manager extends Component
{
    public string $cpf = '';

    public string $birthDate = '';

    public ?int $phoneId = null;

    public string $phoneNumber = '';

    public string $phoneType = 'mobile';

    public bool $phoneIsPrimary = false;

    public ?int $addressId = null;

    public string $addressLabel = '';

    public string $addressRecipient = '';

    public string $addressPostalCode = '';

    public string $addressStreet = '';

    public string $addressNumber = '';

    public string $addressComplement = '';

    public string $addressDistrict = '';

    public string $addressCity = '';

    public string $addressState = '';

    public bool $addressIsPrimary = false;

    public function mount(): void
    {
        $profile = Auth::user()->customerProfile;

        if ($profile) {
            $this->cpf = Cpf::from($profile->cpf_encrypted)->formatted();
            $this->birthDate = $profile->birth_date?->format('Y-m-d') ?? '';
        }
    }

    public function saveProfile(SaveCustomerProfile $saveCustomerProfile): void
    {
        $validated = $this->validate([
            'cpf' => ['required', 'string', new ValidCpf],
            'birthDate' => ['nullable', 'date', 'before_or_equal:today', 'after_or_equal:1900-01-01'],
        ], [], [
            'cpf' => 'CPF',
            'birthDate' => 'data de nascimento',
        ]);

        $profile = $saveCustomerProfile->execute(
            Auth::user(),
            $validated['cpf'],
            $validated['birthDate'] ?: null,
        );

        $this->cpf = Cpf::from($profile->cpf_encrypted)->formatted();
        $this->birthDate = $profile->birth_date?->format('Y-m-d') ?? '';

        session()->flash('profileSaved', 'Dados pessoais salvos com segurança.');
    }

    public function savePhone(SavePhone $savePhone): void
    {
        $validated = $this->validate([
            'phoneNumber' => ['required', 'string', new ValidBrazilianPhone],
            'phoneType' => ['required', Rule::enum(PhoneType::class)],
            'phoneIsPrimary' => ['boolean'],
        ], [], [
            'phoneNumber' => 'telefone',
            'phoneType' => 'tipo',
        ]);

        $savePhone->execute(
            Auth::user(),
            $this->phoneId,
            $validated['phoneNumber'],
            PhoneType::from($validated['phoneType']),
            $validated['phoneIsPrimary'],
        );

        $this->resetPhoneForm();
        session()->flash('phoneSaved', 'Telefone salvo com segurança.');
    }

    public function editPhone(int $phoneId): void
    {
        /** @var Phone|null $phone */
        $phone = Auth::user()->phones()->find($phoneId);

        if (! $phone) {
            throw ValidationException::withMessages(['phoneNumber' => 'Telefone não encontrado.']);
        }

        $this->phoneId = $phone->id;
        $this->phoneNumber = BrazilianPhone::from($phone->number_encrypted)->formatted();
        $this->phoneType = $phone->type->value;
        $this->phoneIsPrimary = $phone->is_primary;
        $this->resetValidation('phoneNumber');
    }

    public function cancelPhoneEdit(): void
    {
        $this->resetPhoneForm();
    }

    public function deletePhone(DeletePhone $deletePhone, int $phoneId): void
    {
        $deletePhone->execute(Auth::user(), $phoneId);

        if ($this->phoneId === $phoneId) {
            $this->resetPhoneForm();
        }

        session()->flash('phoneSaved', 'Telefone removido.');
    }

    public function saveAddress(SaveAddress $saveAddress): void
    {
        $validated = $this->validate([
            'addressLabel' => ['required', 'string', 'max:50'],
            'addressRecipient' => ['required', 'string', 'max:120'],
            'addressPostalCode' => ['required', 'regex:/^\d{5}-?\d{3}$/'],
            'addressStreet' => ['required', 'string', 'max:150'],
            'addressNumber' => ['required', 'string', 'max:20'],
            'addressComplement' => ['nullable', 'string', 'max:100'],
            'addressDistrict' => ['required', 'string', 'max:100'],
            'addressCity' => ['required', 'string', 'max:100'],
            'addressState' => ['required', 'string', Rule::in(self::BRAZILIAN_STATES)],
            'addressIsPrimary' => ['boolean'],
        ], [], [
            'addressLabel' => 'rótulo',
            'addressRecipient' => 'destinatário',
            'addressPostalCode' => 'CEP',
            'addressStreet' => 'logradouro',
            'addressNumber' => 'número',
            'addressComplement' => 'complemento',
            'addressDistrict' => 'bairro',
            'addressCity' => 'cidade',
            'addressState' => 'UF',
        ]);

        $saveAddress->execute(Auth::user(), $this->addressId, [
            'label' => $validated['addressLabel'],
            'recipient' => $validated['addressRecipient'],
            'postal_code' => $validated['addressPostalCode'],
            'street' => $validated['addressStreet'],
            'number' => $validated['addressNumber'],
            'complement' => $validated['addressComplement'] ?: null,
            'district' => $validated['addressDistrict'],
            'city' => $validated['addressCity'],
            'state' => $validated['addressState'],
            'is_primary' => $validated['addressIsPrimary'],
        ]);

        $this->resetAddressForm();
        session()->flash('addressSaved', 'Endereço salvo com segurança.');
    }

    public function editAddress(int $addressId): void
    {
        /** @var Address|null $address */
        $address = Auth::user()->addresses()->find($addressId);

        if (! $address) {
            throw ValidationException::withMessages(['addressLabel' => 'Endereço não encontrado.']);
        }

        $this->addressId = $address->id;
        $this->addressLabel = $address->label;
        $this->addressRecipient = $address->recipient_encrypted;
        $this->addressPostalCode = $address->postal_code_encrypted;
        $this->addressStreet = $address->street_encrypted;
        $this->addressNumber = $address->number_encrypted;
        $this->addressComplement = $address->complement_encrypted ?? '';
        $this->addressDistrict = $address->district_encrypted;
        $this->addressCity = $address->city_encrypted;
        $this->addressState = $address->state_encrypted;
        $this->addressIsPrimary = $address->is_primary;
        $this->resetValidation('addressLabel');
    }

    public function cancelAddressEdit(): void
    {
        $this->resetAddressForm();
    }

    public function deleteAddress(DeleteAddress $deleteAddress, int $addressId): void
    {
        $deleteAddress->execute(Auth::user(), $addressId);

        if ($this->addressId === $addressId) {
            $this->resetAddressForm();
        }

        session()->flash('addressSaved', 'Endereço removido.');
    }

    public function render(): View
    {
        $phones = Auth::user()->phones()->orderByDesc('is_primary')->oldest()->get();
        $addresses = Auth::user()->addresses()->orderByDesc('is_primary')->oldest()->get();

        return view('livewire.personal-data.manager', compact('phones', 'addresses'));
    }

    private function resetPhoneForm(): void
    {
        $this->reset('phoneId', 'phoneNumber', 'phoneType', 'phoneIsPrimary');
        $this->resetValidation('phoneNumber');
    }

    private function resetAddressForm(): void
    {
        $this->reset(
            'addressId',
            'addressLabel',
            'addressRecipient',
            'addressPostalCode',
            'addressStreet',
            'addressNumber',
            'addressComplement',
            'addressDistrict',
            'addressCity',
            'addressState',
            'addressIsPrimary',
        );
        $this->resetValidation('addressLabel');
    }

    private const BRAZILIAN_STATES = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG',
        'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
    ];
}
