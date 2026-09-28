<?php

namespace App\Livewire\PersonalData;

use App\Actions\PersonalData\SaveCustomerProfile;
use App\Actions\PersonalData\DeletePhone;
use App\Actions\PersonalData\SavePhone;
use App\Domain\PersonalData\BrazilianPhone;
use App\Domain\PersonalData\Cpf;
use App\Enums\PhoneType;
use App\Models\Phone;
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

    public function render(): View
    {
        $phones = Auth::user()->phones()->orderByDesc('is_primary')->oldest()->get();

        return view('livewire.personal-data.manager', compact('phones'));
    }

    private function resetPhoneForm(): void
    {
        $this->reset('phoneId', 'phoneNumber', 'phoneType', 'phoneIsPrimary');
        $this->resetValidation('phoneNumber');
    }
}
