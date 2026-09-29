<?php

namespace App\Actions\Inventory;

use App\Domain\PersonalData\BlindIndex;
use App\Models\Supplier;
use App\Models\User;
use App\Support\SecurityAudit;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class SaveSupplier
{
    public function __construct(private readonly BlindIndex $blindIndex) {}

    /** @param array<string, mixed> $data */
    public function execute(User $actor, ?int $supplierId, array $data): Supplier
    {
        abort_unless($actor->isAdmin(), 403);
        $supplier = $supplierId ? Supplier::query()->findOrFail($supplierId) : new Supplier;
        $digits = preg_replace('/\D/', '', (string) $data['document']) ?? '';
        $hash = $this->blindIndex->for($digits);

        if (Supplier::withTrashed()->where('document_hash', $hash)->when($supplier->exists, fn ($q) => $q->whereKeyNot($supplier->id))->exists()) {
            throw ValidationException::withMessages(['supplierDocument' => 'Não foi possível salvar o documento informado.']);
        }

        try {
            $supplier->fill([
                'name_encrypted' => trim($data['name']),
                'document_encrypted' => $this->formatDocument($digits),
                'document_hash' => $hash,
                'email_encrypted' => strtolower(trim($data['email'])),
                'phone_encrypted' => trim($data['phone']),
                'address_encrypted' => trim($data['address']),
                'active' => (bool) $data['active'],
            ])->save();
        } catch (QueryException $e) {
            if ((string) $e->getCode() === '23000') {
                throw ValidationException::withMessages(['supplierDocument' => 'Não foi possível salvar o documento informado.']);
            }
            throw $e;
        }

        $supplier->refresh();
        app(SecurityAudit::class)->record($actor, $supplierId ? 'supplier.updated' : 'supplier.created', $supplier);

        return $supplier;
    }

    private function formatDocument(string $digits): string
    {
        return substr($digits, 0, 2).'.'.substr($digits, 2, 3).'.'.substr($digits, 5, 3).'/'.substr($digits, 8, 4).'-'.substr($digits, 12, 2);
    }
}
