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
</div>
