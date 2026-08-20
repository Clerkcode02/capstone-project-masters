<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Contracts\View\View;

class AccountController extends Controller
{
    public function index(): View
    {
        $accounts = auth()->user()->accounts()
            ->withCount('users')
            ->orderBy('name')
            ->paginate(15);

        return view('employee.accounts.index', compact('accounts'));
    }

    public function show(Account $account): View
    {
        $this->authorize('view', $account);

        $account->load(['users' => fn ($query) => $query->orderBy('first_name')]);

        return view('employee.accounts.show', compact('account'));
    }
}
