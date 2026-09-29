<?php

namespace App\Livewire\Admin;

use App\Actions\Admin\CreateAdministrator;
use App\Actions\Admin\SetUserStatus;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class UserManager extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function create(CreateAdministrator $action): void
    {
        $action->execute(Auth::user(), $this->only(['name', 'email', 'password', 'password_confirmation']));
        $this->reset('name', 'email', 'password', 'password_confirmation');
        session()->flash('user-admin-status', 'Administrador criado.');
    }

    public function toggle(SetUserStatus $action, int $id): void
    {
        $user = User::findOrFail($id);
        $action->execute(Auth::user(), $user, $user->status === UserStatus::Active ? UserStatus::Disabled : UserStatus::Active);
    }

    public function render()
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        return view('livewire.admin.user-manager', ['users' => User::query()->latest()->paginate(15)]);
    }
}
