<?php

namespace App\Livewire\PersonalData;

use App\Actions\PersonalData\SaveCustomerProfile;
use App\Domain\PersonalData\Cpf;
use App\Rules\ValidCpf;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Manager extends Component
{
    public string $cpf = '';

    public string $birthDate = '';

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

    public function render(): View
    {
        return view('livewire.personal-data.manager');
    }
}
