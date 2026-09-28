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

    <section class="bg-white overflow-hidden shadow-xl sm:rounded-lg" aria-labelledby="addresses-title">
        <div class="p-6 space-y-6">
            <div>
                <h3 id="addresses-title" class="text-lg font-medium text-gray-900">Endereços</h3>
                <p class="mt-1 text-sm text-gray-600">Cadastre somente endereços fictícios. Os detalhes são armazenados criptografados.</p>
            </div>

            @if (session('addressSaved'))
                <p class="text-sm text-green-700" role="status">{{ session('addressSaved') }}</p>
            @endif

            @if ($addresses->isEmpty())
                <p class="text-sm text-gray-500">Nenhum endereço cadastrado.</p>
            @else
                <ul class="grid gap-4 md:grid-cols-2" aria-label="Endereços cadastrados">
                    @foreach ($addresses as $address)
                        <li wire:key="address-{{ $address->id }}" class="rounded-lg border border-gray-200 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $address->label }}</p>
                                    <p class="mt-1 text-sm text-gray-600">{{ $address->city_encrypted }}/{{ $address->state_encrypted }} · CEP *****-***</p>
                                    @if ($address->is_primary)
                                        <span class="mt-2 inline-block rounded-full bg-indigo-50 px-2 py-1 text-xs text-indigo-700">Principal</span>
                                    @endif
                                </div>
                                <div class="flex flex-col gap-2">
                                    <x-secondary-button type="button" wire:click="editAddress({{ $address->id }})">Editar</x-secondary-button>
                                    <x-danger-button type="button" wire:click="deleteAddress({{ $address->id }})" wire:confirm="Remover este endereço?">Remover</x-danger-button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            <form wire:submit="saveAddress" class="border-t border-gray-200 pt-6 space-y-5">
                <h4 class="font-medium text-gray-900">{{ $addressId ? 'Editar endereço' : 'Adicionar endereço' }}</h4>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <x-label for="addressLabel" value="Rótulo" />
                        <x-input id="addressLabel" type="text" class="mt-1 block w-full" wire:model="addressLabel" placeholder="Casa fictícia" />
                        <x-input-error for="addressLabel" class="mt-2" />
                    </div>
                    <div>
                        <x-label for="addressRecipient" value="Destinatário fictício" />
                        <x-input id="addressRecipient" type="text" class="mt-1 block w-full" wire:model="addressRecipient" autocomplete="name" />
                        <x-input-error for="addressRecipient" class="mt-2" />
                    </div>
                    <div>
                        <x-label for="addressPostalCode" value="CEP" />
                        <x-input id="addressPostalCode" type="text" class="mt-1 block w-full" wire:model="addressPostalCode" inputmode="numeric" autocomplete="postal-code" placeholder="00000-000" />
                        <x-input-error for="addressPostalCode" class="mt-2" />
                    </div>
                    <div>
                        <x-label for="addressStreet" value="Logradouro" />
                        <x-input id="addressStreet" type="text" class="mt-1 block w-full" wire:model="addressStreet" autocomplete="address-line1" />
                        <x-input-error for="addressStreet" class="mt-2" />
                    </div>
                    <div>
                        <x-label for="addressNumber" value="Número" />
                        <x-input id="addressNumber" type="text" class="mt-1 block w-full" wire:model="addressNumber" />
                        <x-input-error for="addressNumber" class="mt-2" />
                    </div>
                    <div>
                        <x-label for="addressComplement" value="Complemento" />
                        <x-input id="addressComplement" type="text" class="mt-1 block w-full" wire:model="addressComplement" autocomplete="address-line2" />
                        <x-input-error for="addressComplement" class="mt-2" />
                    </div>
                    <div>
                        <x-label for="addressDistrict" value="Bairro" />
                        <x-input id="addressDistrict" type="text" class="mt-1 block w-full" wire:model="addressDistrict" />
                        <x-input-error for="addressDistrict" class="mt-2" />
                    </div>
                    <div>
                        <x-label for="addressCity" value="Cidade" />
                        <x-input id="addressCity" type="text" class="mt-1 block w-full" wire:model="addressCity" autocomplete="address-level2" />
                        <x-input-error for="addressCity" class="mt-2" />
                    </div>
                    <div>
                        <x-label for="addressState" value="UF" />
                        <select id="addressState" wire:model="addressState" autocomplete="address-level1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Selecione</option>
                            @foreach (['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'] as $state)
                                <option value="{{ $state }}">{{ $state }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="addressState" class="mt-2" />
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <x-checkbox wire:model="addressIsPrimary" />
                    Usar como endereço principal
                </label>

                <div class="flex justify-end gap-3">
                    @if ($addressId)
                        <x-secondary-button type="button" wire:click="cancelAddressEdit">Cancelar</x-secondary-button>
                    @endif
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="saveAddress">Salvar endereço</x-button>
                </div>
            </form>
        </div>
    </section>
</div>
