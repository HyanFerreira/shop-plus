<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class AuditManager extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function maskedEmail(?string $email): string
    {
        if (! $email || ! str_contains($email, '@')) {
            return 'sistema';
        }
        [$name, $domain] = explode('@', $email, 2);

        return mb_substr($name, 0, 2).'***@'.$domain;
    }

    public function render()
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $logs = AuditLog::query()->with('actor')->when($this->search, fn ($query) => $query->where('event', 'like', '%'.addcslashes($this->search, '%_\\').'%'))->latest('id')->paginate(25);

        return view('livewire.admin.audit-manager', compact('logs'));
    }
}
