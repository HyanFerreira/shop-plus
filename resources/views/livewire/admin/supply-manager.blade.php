<div class="space-y-8">
    @if(session('supplyMessage'))<p class="rounded bg-green-50 p-4 text-green-800" role="status">{{ session('supplyMessage') }}</p>@endif
    <section class="bg-white p-6 shadow-xl sm:rounded-lg space-y-6">
        <h3 class="text-lg font-medium">Fornecedores fictícios</h3>
        <div class="space-y-3">
            @foreach($suppliers as $supplier)
                <div wire:key="supplier-{{ $supplier->id }}" class="flex flex-wrap justify-between gap-3 border-b pb-3 {{ $supplier->trashed() ? 'opacity-60' : '' }}">
                    <div><p class="font-medium">{{ $supplier->name_encrypted }}</p><p class="text-sm text-gray-500">{{ $supplier->active ? 'Ativo' : 'Inativo' }} · {{ $supplier->supplierProducts->count() }} produto(s)</p></div>
                    <div>@if($supplier->trashed())<x-secondary-button wire:click="restoreSupplier({{ $supplier->id }})">Restaurar</x-secondary-button>@else<x-secondary-button wire:click="editSupplier({{ $supplier->id }})">Editar</x-secondary-button><x-danger-button class="ms-2" wire:click="deleteSupplier({{ $supplier->id }})">Remover</x-danger-button>@endif</div>
                </div>
            @endforeach
        </div>
        <form wire:submit="saveSupplier" class="grid gap-4 md:grid-cols-2 border-t pt-6">
            <div><x-label for="supplierName" value="Nome fictício"/><x-input id="supplierName" class="mt-1 w-full" wire:model="supplierName"/><x-input-error for="supplierName"/></div>
            <div><x-label for="supplierDocument" value="CNPJ sintético"/><x-input id="supplierDocument" class="mt-1 w-full" wire:model="supplierDocument"/><x-input-error for="supplierDocument"/></div>
            <div><x-label for="supplierEmail" value="E-mail"/><x-input id="supplierEmail" type="email" class="mt-1 w-full" wire:model="supplierEmail"/><x-input-error for="supplierEmail"/></div>
            <div><x-label for="supplierPhone" value="Telefone"/><x-input id="supplierPhone" class="mt-1 w-full" wire:model="supplierPhone"/><x-input-error for="supplierPhone"/></div>
            <div class="md:col-span-2"><x-label for="supplierAddress" value="Endereço comercial fictício"/><x-input id="supplierAddress" class="mt-1 w-full" wire:model="supplierAddress"/><x-input-error for="supplierAddress"/></div>
            <label class="flex gap-2"><x-checkbox wire:model="supplierActive"/> Ativo</label>
            <div class="text-right"><x-button>Salvar fornecedor</x-button></div>
        </form>
    </section>
    <section class="bg-white p-6 shadow-xl sm:rounded-lg space-y-5">
        <h3 class="text-lg font-medium">Custos por fornecedor</h3>
        <form wire:submit="saveSupplierProduct" class="grid gap-4 md:grid-cols-4">
            <select wire:model="linkSupplierId" class="rounded-md border-gray-300"><option value="">Fornecedor</option>@foreach($suppliers->whereNull('deleted_at')->where('active',true) as $s)<option value="{{ $s->id }}">{{ $s->name_encrypted }}</option>@endforeach</select>
            <select wire:model="linkProductId" class="rounded-md border-gray-300"><option value="">Produto</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select>
            <x-input wire:model="externalCode" placeholder="Código externo"/><x-input wire:model="supplierCost" placeholder="Custo R$" inputmode="decimal"/>
            <x-input-error for="linkSupplierId"/><x-input-error for="linkProductId"/><x-input-error for="externalCode"/><x-input-error for="supplierCost"/>
            <div class="md:col-span-4 text-right"><x-button>Salvar custo</x-button></div>
        </form>
    </section>
</div>
