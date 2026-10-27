<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;

class UserDirectoryController extends Controller
{
    public function __invoke(): View
    {
        $now = now();
        $totalUsers = User::query()->count();
        $automatedUsersCount = User::query()->where('is_automated_demo', true)->count();
        $recentUsersCount = User::query()->whereBetween('created_at', [$now->copy()->subMinutes(5), $now])->count();
        $automatedUsers = User::query()
            ->select(['id', 'name', 'created_at'])
            ->where('is_automated_demo', true)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('users.index', compact('totalUsers', 'automatedUsersCount', 'recentUsersCount', 'automatedUsers'));
    }
}
