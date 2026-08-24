<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Accounts\Services\AccountService;
use App\Domain\Identity\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignAccountUserRequest;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AccountController extends Controller
{
    public function __construct(private readonly AccountService $accounts) {}

    public function index(): View
    {
        $this->authorize('viewAny', Account::class);

        $accounts = Account::query()
            ->withCount('users')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.accounts.index', compact('accounts'));
    }

    public function create(): View
    {
        $this->authorize('create', Account::class);

        return view('admin.accounts.create');
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $this->accounts->create($request->validated(), $request->user());

        return redirect()->route('admin.accounts.index')->with('status', 'Account created.');
    }

    public function edit(Account $account): View
    {
        $this->authorize('update', $account);

        $account->load(['users' => fn ($query) => $query->orderBy('first_name')]);

        $assignableUsers = User::query()
            ->whereRelation('role', 'name', Role::Employee->value)
            ->whereDoesntHave('accounts', fn ($query) => $query->whereKey($account->id))
            ->orderBy('first_name')
            ->get();

        return view('admin.accounts.edit', compact('account', 'assignableUsers'));
    }

    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $this->accounts->update($account, $request->validated(), $request->user());

        return redirect()->route('admin.accounts.edit', $account)->with('status', 'Account updated.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        $this->authorize('delete', $account);

        $this->accounts->delete($account, auth()->user());

        return redirect()->route('admin.accounts.index')->with('status', 'Account deleted.');
    }

    public function assignUser(AssignAccountUserRequest $request, Account $account): RedirectResponse
    {
        $employee = User::findOrFail($request->validated('user_id'));

        $this->accounts->assignUser($account, $employee, $request->user());

        return redirect()->route('admin.accounts.edit', $account)->with('status', 'Employee assigned.');
    }

    public function unassignUser(Account $account, User $user): RedirectResponse
    {
        $this->authorize('manageAssignments', $account);

        $this->accounts->unassignUser($account, $user, auth()->user());

        return redirect()->route('admin.accounts.edit', $account)->with('status', 'Employee unassigned.');
    }
}
