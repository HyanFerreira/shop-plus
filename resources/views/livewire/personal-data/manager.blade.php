<div class="account-manager">
    <section class="account-panel ds-panel" aria-labelledby="personal-profile-title">
        <form wire:submit="saveProfile" class="account-section-body">
            <div>
                <h3 id="personal-profile-title" class="text-lg font-medium text-gray-900">Identificação</h3>
                <p class="mt-1 text-sm text-gray-600">Use somente dados fictícios nesta aplicação acadêmica.</p>
            </div>

            @if (session('profileSaved'))
                <p class="account-success" role="status">{{ session('profileSaved') }}</p>
            @endif

            <div class="account-field-grid">
                <div>
                    <x-label for="cpf" value="CPF fictício" />
                    <x-input id="cpf" type="text" class="mt-1 block w-full" wire:model="cpf" inputmode="numeric" autocomplete="off" placeholder="000.000.000-00"  wire:loading.attr="disabled" wire:target="saveProfile,savePhone,editPhone,cancelPhoneEdit,deletePhone,saveAddress,editAddress,cancelAddressEdit,deleteAddress" aria-invalid="{{ $errors->has('cpf') ? 'true' : 'false' }}" aria-describedby="account-error-cpf" />
                    <div id="account-error-cpf" aria-live="polite"><x-input-error for="cpf" class="mt-2"  /></div>
                </div>

                <div>
                    <x-label for="birthDate" value="Data de nascimento" />
                    <x-input id="birthDate" type="date" class="mt-1 block w-full" wire:model="birthDate" autocomplete="bday"  wire:loading.attr="disabled" wire:target="saveProfile,savePhone,editPhone,cancelPhoneEdit,deletePhone,saveAddress,editAddress,cancelAddressEdit,deleteAddress" aria-invalid="{{ $errors->has('birthDate') ? 'true' : 'false' }}" aria-describedby="account-error-birthDate" />
                    <div id="account-error-birthDate" aria-live="polite"><x-input-error for="birthDate" class="mt-2"  /></div>
                </div>
            </div>

            <div class="account-form-actions">
                <x-button type="submit" wire:loading.attr="disabled" wire:target="saveProfile">
                    Salvar identificação
                </x-button>
            </div>
        <p class="account-loading" role="status"><span wire:loading>Atualizando seus dados…</span></p></form>
    </section>

    <section class="account-panel ds-panel" aria-labelledby="phones-title">
        <div class="account-section-body">
            <div>
                <h3 id="phones-title" class="text-lg font-medium text-gray-900">Telefones</h3>
                <p class="mt-1 text-sm text-gray-600">Os números são criptografados e aparecem mascarados na listagem.</p>
            </div>

            @if (session('phoneSaved'))
                <p class="account-success" role="status">{{ session('phoneSaved') }}</p>
            @endif

            @if ($phones->isEmpty())
                <p class="text-sm text-gray-500">Nenhum telefone cadastrado.</p>
            @else
                <ul class="account-record-list" aria-label="Telefones cadastrados">
                    @foreach ($phones as $phone)
                        <li wire:key="phone-{{ $phone->id }}" class="account-phone-record">
                            <div>
                                <p class="font-medium text-gray-900">{{ \App\Domain\PersonalData\BrazilianPhone::from($phone->number_encrypted)->masked() }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ match ($phone->type) { \App\Enums\PhoneType::Mobile => 'Celular', \App\Enums\PhoneType::Home => 'Residencial', \App\Enums\PhoneType::Work => 'Trabalho' } }}
                                    @if ($phone->is_primary)
                                        <x-badge variant="info">Principal</x-badge>
                                    @endif
                                </p>
                            </div>
                            <div class="account-record-actions">
                                <x-secondary-button type="button" wire:loading.attr="disabled" wire:target="saveProfile,savePhone,editPhone,cancelPhoneEdit,deletePhone,saveAddress,editAddress,cancelAddressEdit,deleteAddress" wire:click="editPhone({{ $phone->id }})">Editar</x-secondary-button>
                                <x-danger-button type="button" wire:loading.attr="disabled" wire:target="saveProfile,savePhone,editPhone,cancelPhoneEdit,deletePhone,saveAddress,editAddress,cancelAddressEdit,deleteAddress" wire:click="deletePhone({{ $phone->id }})" wire:confirm="Remover este telefone?">Remover</x-danger-button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            <form wire:submit="savePhone" class="account-record-form">
                <h4 class="font-medium text-gray-900">{{ $phoneId ? 'Editar telefone' : 'Adicionar telefone' }}</h4>

                <div class="account-field-grid">
                    <div>
                        <x-label for="phoneNumber" value="Número" />
                        <x-input id="phoneNumber" type="tel" class="mt-1 block w-full" wire:model="phoneNumber" autocomplete="tel" placeholder="(00) 00000-0000"  wire:loading.attr="disabled" wire:target="saveProfile,savePhone,editPhone,cancelPhoneEdit,deletePhone,saveAddress,editAddress,cancelAddressEdit,deleteAddress" aria-invalid="{{ $errors->has('phoneNumber') ? 'true' : 'false' }}" aria-describedby="account-error-phoneNumber" />
                        <div id="account-error-phoneNumber" aria-live="polite"><x-input-error for="phoneNumber" class="mt-2"  /></div>
                    </div>

                    <div>
                        <x-label for="phoneType" value="Tipo" />
                        <select id="phoneType" wire:model="phoneType" class="ds-input mt-1 block w-full" wire:loading.attr="disabled" wire:target="saveProfile,savePhone,editPhone,cancelPhoneEdit,deletePhone,saveAddress,editAddress,cancelAddressEdit,deleteAddress" aria-invalid="{{ $errors->has('phoneType') ? 'true' : 'false' }}" aria-describedby="account-error-phoneType">
                            <option value="mobile">Celular</option>
                            <option value="home">Residencial</option>
                            <option value="work">Trabalho</option>
                        </select>
                        <div id="account-error-phoneType" aria-live="polite"><x-input-error for="phoneType" class="mt-2"  /></div>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <x-checkbox wire:model="phoneIsPrimary"  wire:loading.attr="disabled" wire:target="saveProfile,savePhone,editPhone,cancelPhoneEdit,deletePhone,saveAddress,editAddress,cancelAddressEdit,deleteAddress" />
                    Usar como telefone principal
                </label>

                <div class="account-form-actions">
                    @if ($phoneId)
                        <x-secondary-button type="button" wire:click="cancelPhoneEdit" wire:loading.attr="disabled" wire:target="saveProfile,savePhone,editPhone,cancelPhoneEdit,deletePhone,saveAddress,editAddress,cancelAddressEdit,deleteAddress">Cancelar</x-secondary-button>
                    @endif
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="savePhone">Salvar telefone</x-button>
                </div>
            <p class="account-loading" role="status"><span wire:loading>Atualizando seus dados…</span></p></form>
        </div>
    </section>

    <section class="account-panel ds-panel" aria-labelledby="addresses-title">
        <div class="account-section-body">
            <div>
                <h3 id="addresses-title" class="text-lg font-medium text-gray-900">Endereços</h3>
                <p class="mt-1 text-sm text-gray-600">Cadastre somente endereços fictícios. Os detalhes são armazenados criptografados.</p>
            </div>

            @if (session('addressSaved'))
                <p class="account-success" role="status">{{ session('addressSaved') }}</p>
            @endif

            @if ($addresses->isEmpty())
                <p class="text-sm text-gray-500">Nenhum endereço cadastrado.</p>
            @else
                <ul class="account-address-list" aria-label="Endereços cadastrados">
                    @foreach ($addresses as $address)
                        <li wire:key="address-{{ $address->id }}" class="account-address-card">
                            <div class="account-address-card__body">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $address->label }}</p>
                                    <p class="mt-1 text-sm text-gray-600">{{ $address->city_encrypted }}/{{ $address->state_encrypted }} · CEP *****-***</p>
                                    @if ($address->is_primary)
                                        <x-badge variant="info">Principal</x-badge>
                                    @endif
                                </div>
                                <div class="account-record-actions">
                                    <x-secondary-button type="button" wire:loading.attr="disabled" wire:target="saveProfile,savePhone,editPhone,cancelPhoneEdit,deletePhone,saveAddress,editAddress,cancelAddressEdit,deleteAddress" wire:click="editAddress({{ $address->id }})">Editar</x-secondary-button>
                                    <x-danger-button type="button" wire:loading.attr="disabled" wire:target="saveProfile,savePhone,editPhone,cancelPhoneEdit,deletePhone,saveAddress,editAddress,cancelAddressEdit,deleteAddress" wire:click="deleteAddress({{ $address->id }})" wire:confirm="Remover este endereço?">Remover</x-danger-button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            <form wire:submit="saveAddress" class="account-record-form">
                <h4 class="font-medium text-gray-900">{{ $addressId ? 'Editar endereço' : 'Adicionar endereço' }}</h4>

                <div class="account-field-grid">
                    <div>
                        <x-label for="addressLabel" value="Rótulo" />
                        <x-input id="addressLabel" type="text" class="mt-1 block w-full" wire:model="addressLabel" placeholder="Casa fictícia"  wire:loading.attr="disabled" wire:target="saveProfile,savePhone,editPhone,cancelPhoneEdit,deletePhone,saveAddress,editAddress,cancelAddressEdit,deleteAddress" aria-invalid="{{ $errors->has('addressLabel') ? 'true' : 'false' }}" aria-describedby="account-error-addressLabel" />
                        <div id="account-error-addressLabel" aria-live="polite"><x-input-error for="addressLabel" class="mt-2"  /></div>
                    </div>
                    <div>
                        <x-label for="addressRecipient" value="Destinatário fictício" />
                        <x-input id="addressRecipient" type="text" class="mt-1 block w-full" wire:model="addressRecipient" autocomplete="name"  wire:loading.attr="disabled" wire:target="saveProfile,savePhone,editPhone,cancelPhoneEdit,deletePhone,saveAddress,editAddress,cancelAddressEdit,deleteAddress" aria-invalid="{{ $errors->has('addressRecipient') ? 'true' : 'false' }}" aria-describedby="account-error-addressRecipient" />
                        <div id="account-error-addressRecipient" aria-live="polite"><x-input-error for="addressRecipient" class="mt-2"  /></div>
                    </div>
                    <div>
                        <x-label for="addressPostalCode" value="CEP" />
                        <x-input id="addressPostalCode" type="text" class="mt-1 block w-full" wire:model="addressPostalCode" inputmode="numeric" autocomplete="postal-code" placeholder="00000-000"  wire:loading.attr="disabled" wire:target="saveProfile,savePhone,editPhone,cancelPhoneEdit,deletePhone,saveAddress,editAddress,cancelAddressEdit,deleteAddress" aria-invalid="{{ $errors->has('addressPostalCode') ? 'true' : 'false' }}" aria-describedby="account-error-addressPostalCode" />
                        <div id="account-error-addressPostalCode" aria-live="polite"><x-input-error for="addressPostalCode" class="mt-2"  /></div>
                    </div>
                    <div>
                        <x-label for="addressStreet" value="Logradouro" />
                        <x-input id="addressStreet" type="text" class="mt-1 block w-full" wire:model="addressStreet" autocomplete="address-line1"  wire:loading.attr="disabled" wire:target="saveProfile,savePhone,editPhone,cancelPhoneEdit,deletePhone,saveAddress,editAddress,cancelAddressEdit,deleteAddress" aria-invalid="{{ $errors->has('addressStreet') ? 'true' : 'false' }}" aria-describedby="account-error-addressStreet" />
                        <div id="account-error-addressStreet" aria-live="polite"><x-input-error for="addressStreet" class="mt-2"  /></div>
                    </div>
                    <div>
                        <x-label for="addressNumber" value="Número" />
                        <x-input id="addressNumber" type="text" class="mt-1 block w-full" wire:model="addressNumber"  wire:loading.attr="disabled" wire:target="saveProfile,savePhone,editPhone,cancelPhoneEdit,deletePhone,saveAddress,editAddress,cancelAddressEdit,deleteAddress" aria-invalid="{{ $errors->has('addressNumber') ? 'true' : 'false' }}" aria-describedby="account-error-addressNumber" />
                        <div id="account-error-addressNumber" aria-live="polite"><x-input-error for="addressNumber" class="mt-2"  /></div>
                    </div>
                    <div>
                        <x-label for="addressComplement" value="Complemento" />
                        <x-input id="addressComplement" type="text" class="mt-1 block w-full" wire:model="addressComplement" autocomplete="address-line2"  wire:loading.attr="disabled" wire:target="saveProfile,savePhone,editPhone,cancelPhoneEdit,deletePhone,saveAddress,editAddress,cancelAddressEdit,deleteAddress" aria-invalid="{{ $errors->has('addressComplement') ? 'true' : 'false' }}" aria-describedby="account-error-addressComplement" />
                        <div id="account-error-addressComplement" aria-live="polite"><x-input-error for="addressComplement" class="mt-2"  /></div>
                    </div>
                    <div>
                        <x-label for="addressDistrict" value="Bairro" />
                        <x-input id="addressDistrict" type="text" class="mt-1 block w-full" wire:model="addressDistrict"  wire:loading.attr="disabled" wire:target="saveProfile,savePhone,editPhone,cancelPhoneEdit,deletePhone,saveAddress,editAddress,cancelAddressEdit,deleteAddress" aria-invalid="{{ $errors->has('addressDistrict') ? 'true' : 'false' }}" aria-describedby="account-error-addressDistrict" />
                        <div id="account-error-addressDistrict" aria-live="polite"><x-input-error for="addressDistrict" class="mt-2"  /></div>
                    </div>
                    <div>
                        <x-label for="addressCity" value="Cidade" />
                        <x-input id="addressCity" type="text" class="mt-1 block w-full" wire:model="addressCity" autocomplete="address-level2"  wire:loading.attr="disabled" wire:target="saveProfile,savePhone,editPhone,cancelPhoneEdit,deletePhone,saveAddress,editAddress,cancelAddressEdit,deleteAddress" aria-invalid="{{ $errors->has('addressCity') ? 'true' : 'false' }}" aria-describedby="account-error-addressCity" />
                        <div id="account-error-addressCity" aria-live="polite"><x-input-error for="addressCity" class="mt-2"  /></div>
                    </div>
                    <div>
                        <x-label for="addressState" value="UF" />
                        <select id="addressState" wire:model="addressState" autocomplete="address-level1" class="ds-input mt-1 block w-full" wire:loading.attr="disabled" wire:target="saveProfile,savePhone,editPhone,cancelPhoneEdit,deletePhone,saveAddress,editAddress,cancelAddressEdit,deleteAddress" aria-invalid="{{ $errors->has('addressState') ? 'true' : 'false' }}" aria-describedby="account-error-addressState">
                            <option value="">Selecione</option>
                            @foreach (['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'] as $state)
                                <option value="{{ $state }}">{{ $state }}</option>
                            @endforeach
                        </select>
                        <div id="account-error-addressState" aria-live="polite"><x-input-error for="addressState" class="mt-2"  /></div>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <x-checkbox wire:model="addressIsPrimary"  wire:loading.attr="disabled" wire:target="saveProfile,savePhone,editPhone,cancelPhoneEdit,deletePhone,saveAddress,editAddress,cancelAddressEdit,deleteAddress" />
                    Usar como endereço principal
                </label>

                <div class="account-form-actions">
                    @if ($addressId)
                        <x-secondary-button type="button" wire:click="cancelAddressEdit" wire:loading.attr="disabled" wire:target="saveProfile,savePhone,editPhone,cancelPhoneEdit,deletePhone,saveAddress,editAddress,cancelAddressEdit,deleteAddress">Cancelar</x-secondary-button>
                    @endif
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="saveAddress">Salvar endereço</x-button>
                </div>
            <p class="account-loading" role="status"><span wire:loading>Atualizando seus dados…</span></p></form>
        </div>
    </section>
</div>
