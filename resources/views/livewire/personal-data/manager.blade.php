<div class="space-y-8">
    <section class="bg-white overflow-hidden shadow-xl sm:rounded-lg" aria-labelledby="personal-profile-title">
        <form wire:submit="saveProfile" class="p-6 space-y-6">
            <div>
                <h3 id="personal-profile-title" class="text-lg font-medium text-gray-900">Identificação</h3>
                <p class="mt-1 text-sm text-gray-600">Use somente dados fictícios nesta aplicação acadêmica.</p>
            </div>

            @if (session('profileSaved'))
                <p class="text-sm text-green-700" role="status">{{ session('profileSaved') }}</p>
            @endif

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <x-label for="cpf" value="CPF fictício" />
                    <x-input id="cpf" type="text" class="mt-1 block w-full" wire:model="cpf" inputmode="numeric" autocomplete="off" placeholder="000.000.000-00" />
                    <x-input-error for="cpf" class="mt-2" />
                </div>

                <div>
                    <x-label for="birthDate" value="Data de nascimento" />
                    <x-input id="birthDate" type="date" class="mt-1 block w-full" wire:model="birthDate" autocomplete="bday" />
                    <x-input-error for="birthDate" class="mt-2" />
                </div>
            </div>

            <div class="flex justify-end">
                <x-button type="submit" wire:loading.attr="disabled" wire:target="saveProfile">
                    Salvar identificação
                </x-button>
            </div>
        </form>
    </section>

    <section class="bg-white overflow-hidden shadow-xl sm:rounded-lg" aria-labelledby="phones-title">
        <div class="p-6 space-y-6">
            <div>
                <h3 id="phones-title" class="text-lg font-medium text-gray-900">Telefones</h3>
                <p class="mt-1 text-sm text-gray-600">Os números são criptografados e aparecem mascarados na listagem.</p>
            </div>

            @if (session('phoneSaved'))
                <p class="text-sm text-green-700" role="status">{{ session('phoneSaved') }}</p>
            @endif

            @if ($phones->isEmpty())
                <p class="text-sm text-gray-500">Nenhum telefone cadastrado.</p>
            @else
                <ul class="divide-y divide-gray-200" aria-label="Telefones cadastrados">
                    @foreach ($phones as $phone)
                        <li wire:key="phone-{{ $phone->id }}" class="flex flex-wrap items-center justify-between gap-4 py-4">
                            <div>
                                <p class="font-medium text-gray-900">{{ \App\Domain\PersonalData\BrazilianPhone::from($phone->number_encrypted)->masked() }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ match ($phone->type) { \App\Enums\PhoneType::Mobile => 'Celular', \App\Enums\PhoneType::Home => 'Residencial', \App\Enums\PhoneType::Work => 'Trabalho' } }}
                                    @if ($phone->is_primary)
                                        <span class="ms-2 rounded-full bg-indigo-50 px-2 py-1 text-xs text-indigo-700">Principal</span>
                                    @endif
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <x-secondary-button type="button" wire:click="editPhone({{ $phone->id }})">Editar</x-secondary-button>
                                <x-danger-button type="button" wire:click="deletePhone({{ $phone->id }})" wire:confirm="Remover este telefone?">Remover</x-danger-button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            <form wire:submit="savePhone" class="border-t border-gray-200 pt-6 space-y-5">
                <h4 class="font-medium text-gray-900">{{ $phoneId ? 'Editar telefone' : 'Adicionar telefone' }}</h4>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <x-label for="phoneNumber" value="Número" />
                        <x-input id="phoneNumber" type="tel" class="mt-1 block w-full" wire:model="phoneNumber" autocomplete="tel" placeholder="(00) 00000-0000" />
                        <x-input-error for="phoneNumber" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="phoneType" value="Tipo" />
                        <select id="phoneType" wire:model="phoneType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="mobile">Celular</option>
                            <option value="home">Residencial</option>
                            <option value="work">Trabalho</option>
                        </select>
                        <x-input-error for="phoneType" class="mt-2" />
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <x-checkbox wire:model="phoneIsPrimary" />
                    Usar como telefone principal
                </label>

                <div class="flex justify-end gap-3">
                    @if ($phoneId)
                        <x-secondary-button type="button" wire:click="cancelPhoneEdit">Cancelar</x-secondary-button>
                    @endif
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="savePhone">Salvar telefone</x-button>
                </div>
            </form>
        </div>
    </section>
</div>
