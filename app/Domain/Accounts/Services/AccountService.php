<?php

namespace App\Domain\Accounts\Services;

use App\Domain\Accounts\Events\AccountAudited;
use App\Models\Account;
use App\Models\User;

class AccountService
{
    public function create(array $data, User $actor): Account
    {
        $account = Account::create($data);

        AccountAudited::dispatch($account, $actor, 'account_created', "Account \"{$account->name}\" ({$account->code}) created.");

        return $account;
    }

    public function update(Account $account, array $data, User $actor): Account
    {
        $account->update($data);

        AccountAudited::dispatch($account, $actor, 'account_updated', "Account \"{$account->name}\" ({$account->code}) updated.");

        return $account->refresh();
    }

    public function delete(Account $account, User $actor): void
    {
        AccountAudited::dispatch($account, $actor, 'account_deleted', "Account \"{$account->name}\" ({$account->code}) deleted.");

        $account->delete();
    }

    public function assignUser(Account $account, User $employee, User $actor): void
    {
        if ($account->users()->whereKey($employee->id)->exists()) {
            return;
        }

        $account->users()->attach($employee->id, ['assigned_at' => now()]);

        AccountAudited::dispatch(
            $account,
            $actor,
            'account_user_assigned',
            "{$employee->full_name} assigned to account \"{$account->name}\"."
        );
    }

    public function unassignUser(Account $account, User $employee, User $actor): void
    {
        $account->users()->detach($employee->id);

        AccountAudited::dispatch(
            $account,
            $actor,
            'account_user_unassigned',
            "{$employee->full_name} unassigned from account \"{$account->name}\"."
        );
    }
}
