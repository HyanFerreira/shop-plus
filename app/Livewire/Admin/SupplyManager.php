<?php

namespace App\Livewire\Admin;

use App\Actions\Inventory\SaveSupplier;
use App\Actions\Inventory\SaveSupplierProduct;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
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

    public function boot(): void { abort_unless(Auth::user()?->isAdmin(), 403); }

    public function saveSupplier(SaveSupplier $action): void
    {
        $data = $this->validate([
            'supplierName' => ['required','string','max:160'],
            'supplierDocument' => ['required','regex:/^(?!([0-9])\1{13}$)[0-9.\/\-]{14,18}$/'],
            'supplierEmail' => ['required','email','max:180'],
            'supplierPhone' => ['required','string','max:30'],
            'supplierAddress' => ['required','string','max:500'],
            'supplierActive' => ['boolean'],
        ]);
        $action->execute(Auth::user(), $this->supplierId, [
            'name'=>$data['supplierName'],'document'=>$data['supplierDocument'],'email'=>$data['supplierEmail'],
            'phone'=>$data['supplierPhone'],'address'=>$data['supplierAddress'],'active'=>$data['supplierActive'],
        ]);
        $this->resetSupplierForm(); session()->flash('supplyMessage','Fornecedor salvo.');
    }

    public function editSupplier(int $id): void
    {
        $s=Supplier::findOrFail($id); $this->supplierId=$s->id; $this->supplierName=$s->name_encrypted;
        $this->supplierDocument=$s->document_encrypted; $this->supplierEmail=$s->email_encrypted;
        $this->supplierPhone=$s->phone_encrypted; $this->supplierAddress=$s->address_encrypted; $this->supplierActive=$s->active;
    }
    public function deleteSupplier(int $id):void { Supplier::findOrFail($id)->delete(); session()->flash('supplyMessage','Fornecedor removido.'); }
    public function restoreSupplier(int $id):void { Supplier::onlyTrashed()->findOrFail($id)->restore(); session()->flash('supplyMessage','Fornecedor restaurado.'); }

    public function saveSupplierProduct(SaveSupplierProduct $action):void
    {
        $v=$this->validate([
            'linkSupplierId'=>['required','integer',Rule::exists('suppliers','id')->whereNull('deleted_at')],
            'linkProductId'=>['required','integer',Rule::exists('products','id')->whereNull('deleted_at')],
            'externalCode'=>['required','string','max:80'],
            'supplierCost'=>['required','regex:/^\d{1,10}(?:[,.]\d{1,2})?$/'],
        ]);
        $action->execute(Auth::user(),$v['linkSupplierId'],$v['linkProductId'],$v['externalCode'],$v['supplierCost']);
        $this->reset('linkSupplierId','linkProductId','externalCode','supplierCost'); session()->flash('supplyMessage','Custo vinculado.');
    }

    public function render():View
    {
        return view('livewire.admin.supply-manager',[
            'suppliers'=>Supplier::withTrashed()->with('supplierProducts.product')->get(),
            'products'=>Product::orderBy('name')->get(),
        ]);
    }
    private function resetSupplierForm():void { $this->reset('supplierId','supplierName','supplierDocument','supplierEmail','supplierPhone','supplierAddress');$this->supplierActive=true;$this->resetValidation(); }
}
