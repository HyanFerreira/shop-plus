<?php

namespace App\Livewire\Admin;

use App\Actions\Inventory\AdjustInventory;
use App\Actions\Inventory\CancelPurchaseOrder;
use App\Actions\Inventory\PlacePurchaseOrder;
use App\Actions\Inventory\ReceivePurchase;
use App\Actions\Inventory\SavePurchaseOrder;
use App\Actions\Inventory\SaveSupplier;
use App\Actions\Inventory\SaveSupplierProduct;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Support\SecurityAudit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SupplyManager extends Component
{
    public ?int $supplierId = null;

    public string $supplierName = '';

    public string $supplierDocument = '';

    public string $supplierEmail = '';

    public string $supplierPhone = '';

    public string $supplierAddress = '';

    public bool $supplierActive = true;

    public ?int $linkSupplierId = null;

    public ?int $linkProductId = null;

    public string $externalCode = '';

    public string $supplierCost = '';

    public ?int $orderSupplierId = null;

    public ?int $orderProductId = null;

    public int $orderQuantity = 1;

    public ?int $receiveOrderId = null;

    public ?int $receiveItemId = null;

    public int $receiveQuantity = 1;

    public string $receiptKey = '';

    public ?int $adjustProductId = null;

    public int $adjustDelta = 0;

    public string $adjustReason = '';

    public string $adjustmentKey = '';

    public function mount(): void
    {
        $this->receiptKey = (string) Str::uuid();
        $this->adjustmentKey = (string) Str::uuid();
    }

    public function boot(): void
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
    }

    public function saveSupplier(SaveSupplier $action): void
    {
        $data = $this->validate([
            'supplierName' => ['required', 'string', 'max:160'],
            'supplierDocument' => ['required', 'regex:/^(?!([0-9])\1{13}$)[0-9.\/\-]{14,18}$/'],
            'supplierEmail' => ['required', 'email', 'max:180'],
            'supplierPhone' => ['required', 'string', 'max:30'],
            'supplierAddress' => ['required', 'string', 'max:500'],
            'supplierActive' => ['boolean'],
        ]);
        $action->execute(Auth::user(), $this->supplierId, [
            'name' => $data['supplierName'], 'document' => $data['supplierDocument'], 'email' => $data['supplierEmail'],
            'phone' => $data['supplierPhone'], 'address' => $data['supplierAddress'], 'active' => $data['supplierActive'],
        ]);
        $this->resetSupplierForm();
        session()->flash('supplyMessage', 'Fornecedor salvo.');
    }

    public function editSupplier(int $id): void
    {
        $s = Supplier::findOrFail($id);
        $this->supplierId = $s->id;
        $this->supplierName = $s->name_encrypted;
        $this->supplierDocument = $s->document_encrypted;
        $this->supplierEmail = $s->email_encrypted;
        $this->supplierPhone = $s->phone_encrypted;
        $this->supplierAddress = $s->address_encrypted;
        $this->supplierActive = $s->active;
    }

    public function deleteSupplier(int $id): void
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();
        app(SecurityAudit::class)->record(Auth::user(), 'supplier.deleted', $supplier);
        session()->flash('supplyMessage', 'Fornecedor removido.');
    }

    public function restoreSupplier(int $id): void
    {
        $supplier = Supplier::onlyTrashed()->findOrFail($id);
        $supplier->restore();
        app(SecurityAudit::class)->record(Auth::user(), 'supplier.restored', $supplier);
        session()->flash('supplyMessage', 'Fornecedor restaurado.');
    }

    public function saveSupplierProduct(SaveSupplierProduct $action): void
    {
        $v = $this->validate([
            'linkSupplierId' => ['required', 'integer', Rule::exists('suppliers', 'id')->whereNull('deleted_at')],
            'linkProductId' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'externalCode' => ['required', 'string', 'max:80'],
            'supplierCost' => ['required', 'regex:/^\d{1,10}(?:[,.]\d{1,2})?$/'],
        ]);
        $action->execute(Auth::user(), $v['linkSupplierId'], $v['linkProductId'], $v['externalCode'], $v['supplierCost']);
        $this->reset('linkSupplierId', 'linkProductId', 'externalCode', 'supplierCost');
        session()->flash('supplyMessage', 'Custo vinculado.');
    }

    public function savePurchaseOrder(SavePurchaseOrder $action): void
    {
        $v = $this->validate([
            'orderSupplierId' => ['required', 'integer'], 'orderProductId' => ['required', 'integer'],
            'orderQuantity' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);
        $action->execute(Auth::user(), null, $v['orderSupplierId'], [$v['orderProductId'] => $v['orderQuantity']]);
        $this->reset('orderSupplierId', 'orderProductId');
        $this->orderQuantity = 1;
        session()->flash('supplyMessage', 'Ordem criada em rascunho.');
    }

    public function placePurchaseOrder(PlacePurchaseOrder $action, int $id): void
    {
        $action->execute(Auth::user(), PurchaseOrder::findOrFail($id));
        session()->flash('supplyMessage', 'Ordem emitida.');
    }

    public function cancelPurchaseOrder(CancelPurchaseOrder $action, int $id): void
    {
        $action->execute(Auth::user(), PurchaseOrder::findOrFail($id));
        session()->flash('supplyMessage', 'Ordem cancelada.');
    }

    public function receivePurchase(ReceivePurchase $action): void
    {
        $v = $this->validate(['receiveOrderId' => ['required', 'integer'], 'receiveItemId' => ['required', 'integer'], 'receiveQuantity' => ['required', 'integer', 'min:1'], 'receiptKey' => ['required', 'uuid']]);
        $action->execute(Auth::user(), PurchaseOrder::findOrFail($v['receiveOrderId']), [$v['receiveItemId'] => $v['receiveQuantity']], $v['receiptKey']);
        $this->reset('receiveOrderId', 'receiveItemId');
        $this->receiptKey = (string) Str::uuid();
        $this->receiveQuantity = 1;
        session()->flash('supplyMessage', 'Recebimento registrado.');
    }

    public function adjustInventory(AdjustInventory $action): void
    {
        $v = $this->validate(['adjustProductId' => ['required', 'integer'], 'adjustDelta' => ['required', 'integer', 'not_in:0'], 'adjustReason' => ['required', 'string', 'min:5', 'max:500'], 'adjustmentKey' => ['required', 'uuid']]);
        $action->execute(Auth::user(), Product::findOrFail($v['adjustProductId']), $v['adjustDelta'], $v['adjustReason'], $v['adjustmentKey']);
        $this->reset('adjustProductId', 'adjustDelta', 'adjustReason');
        $this->adjustmentKey = (string) Str::uuid();
        session()->flash('supplyMessage', 'Estoque ajustado.');
    }

    public function render(): View
    {
        return view('livewire.admin.supply-manager', [
            'suppliers' => Supplier::withTrashed()->with('supplierProducts.product')->get(),
            'products' => Product::orderBy('name')->get(),
            'orders' => PurchaseOrder::with(['supplier', 'items.product'])->latest()->get(),
            'inventoryItems' => InventoryItem::with('product')->orderBy('product_id')->get(),
        ]);
    }

    private function resetSupplierForm(): void
    {
        $this->reset('supplierId', 'supplierName', 'supplierDocument', 'supplierEmail', 'supplierPhone', 'supplierAddress');
        $this->supplierActive = true;
        $this->resetValidation();
    }
}
