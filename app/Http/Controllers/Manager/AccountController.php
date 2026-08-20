<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Contracts\View\View;

class AccountController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Account::class);

        $accounts = Account::query()
            ->withCount('users')
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate(15);

        return view('manager.accounts.index', compact('accounts'));
    }

    public function show(Account $account): View
    {
        $this->authorize('view', $account);

        $account->load(['users' => fn ($query) => $query->orderBy('first_name')]);

        return view('manager.accounts.show', compact('account'));
    }
}
