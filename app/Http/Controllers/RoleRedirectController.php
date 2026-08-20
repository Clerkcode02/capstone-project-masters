<?php

namespace App\Http\Controllers;

use App\Domain\Identity\Enums\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoleRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return match ($request->user()->role?->name) {
            Role::Administrator->value => redirect()->route('admin.dashboard'),
            Role::Manager->value => redirect()->route('manager.dashboard'),
            default => redirect()->route('my.dashboard'),
        };
    }
}
