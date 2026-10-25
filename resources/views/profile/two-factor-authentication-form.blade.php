<x-action-section class="account-jetstream-section ds-panel">
    <x-slot name="title">
        {{ __('Autenticação de dois fatores') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Adicione uma segunda etapa de verificação ao entrar na sua conta.') }}
    </x-slot>

    <x-slot name="content">
        <h3 class="text-lg font-medium text-gray-900">
            @if ($this->enabled)
                @if ($showingConfirmation)
                    {{ __('Conclua a ativação da autenticação de dois fatores.') }}
                @else
                    {{ __('A autenticação de dois fatores está ativada.') }}
                @endif
            @else
                {{ __('A autenticação de dois fatores ainda não está ativada.') }}
            @endif
        </h3>

        <div class="mt-3 max-w-xl text-sm text-gray-600">
            <p>
                {{ __('Quando ativado, esse recurso solicita um código do aplicativo autenticador no seu celular ao entrar na conta.') }}
            </p>
        </div>

        @if ($this->enabled)
            @if ($showingQrCode)
                <div class="mt-4 max-w-xl text-sm text-gray-600">
                    <p class="font-semibold">
                        @if ($showingConfirmation)
                            {{ __('Para concluir, escaneie o QR code no aplicativo autenticador ou informe a chave de configuração e o código gerado.') }}
                        @else
                            {{ __('Escaneie o QR code no aplicativo autenticador ou informe a chave de configuração.') }}
                        @endif
                    </p>
                </div>

                <div class="mt-4 p-2 inline-block bg-white">
                    {!! $this->user->twoFactorQrCodeSvg() !!}
                </div>

                <div class="mt-4 max-w-xl text-sm text-gray-600">
                    <p class="font-semibold">
                        {{ __('Chave de configuração') }}: {{ decrypt($this->user->two_factor_secret) }}
                    </p>
                </div>

                @if ($showingConfirmation)
                    <div class="mt-4">
                        <x-label for="code" value="{{ __('Código') }}" />

                        <x-input id="code" type="text" name="code" class="block mt-1 w-1/2" inputmode="numeric" autofocus autocomplete="one-time-code"
                            wire:model="code"
                            wire:keydown.enter="confirmTwoFactorAuthentication" />

                        <x-input-error for="code" class="mt-2" />
                    </div>
                @endif
            @endif

            @if ($showingRecoveryCodes)
                <div class="mt-4 max-w-xl text-sm text-gray-600">
                    <p class="font-semibold">
                        {{ __('Guarde estes códigos em um gerenciador de senhas seguro. Eles permitem recuperar o acesso se você perder seu dispositivo autenticador.') }}
                    </p>
                </div>

                <div class="grid gap-1 max-w-xl mt-4 px-4 py-4 font-mono text-sm bg-gray-100 rounded-lg">
                    @foreach (json_decode(decrypt($this->user->two_factor_recovery_codes), true) as $code)
                        <div>{{ $code }}</div>
                    @endforeach
                </div>
            @endif
        @endif

        <div class="mt-5">
            @if (! $this->enabled)
                <x-confirms-password title="Confirmar senha" content="Para sua segurança, confirme sua senha para continuar." button="Confirmar" wire:then="enableTwoFactorAuthentication">
                    <x-button type="button" wire:loading.attr="disabled">
                        {{ __('Ativar') }}
                    </x-button>
                </x-confirms-password>
            @else
                @if ($showingRecoveryCodes)
                    <x-confirms-password title="Confirmar senha" content="Para sua segurança, confirme sua senha para continuar." button="Confirmar" wire:then="regenerateRecoveryCodes">
                        <x-secondary-button class="me-3">
                            {{ __('Gerar novos códigos de recuperação') }}
                        </x-secondary-button>
                    </x-confirms-password>
                @elseif ($showingConfirmation)
                    <x-confirms-password title="Confirmar senha" content="Para sua segurança, confirme sua senha para continuar." button="Confirmar" wire:then="confirmTwoFactorAuthentication">
                        <x-button type="button" class="me-3" wire:loading.attr="disabled">
                            {{ __('Confirmar') }}
                        </x-button>
                    </x-confirms-password>
                @else
                    <x-confirms-password title="Confirmar senha" content="Para sua segurança, confirme sua senha para continuar." button="Confirmar" wire:then="showRecoveryCodes">
                        <x-secondary-button class="me-3">
                            {{ __('Mostrar códigos de recuperação') }}
                        </x-secondary-button>
                    </x-confirms-password>
                @endif

                @if ($showingConfirmation)
                    <x-confirms-password title="Confirmar senha" content="Para sua segurança, confirme sua senha para continuar." button="Confirmar" wire:then="disableTwoFactorAuthentication">
                        <x-secondary-button wire:loading.attr="disabled">
                            {{ __('Cancelar') }}
                        </x-secondary-button>
                    </x-confirms-password>
                @else
                    <x-confirms-password title="Confirmar senha" content="Para sua segurança, confirme sua senha para continuar." button="Confirmar" wire:then="disableTwoFactorAuthentication">
                        <x-danger-button wire:loading.attr="disabled">
                            {{ __('Desativar') }}
                        </x-danger-button>
                    </x-confirms-password>
                @endif

            @endif
        </div>
    </x-slot>
</x-action-section>
