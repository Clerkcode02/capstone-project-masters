@php
    $user = auth()->user();
    $isAdmin = $user->isRole(\App\Domain\Identity\Enums\Role::Administrator);
    $isManager = $user->isRole(\App\Domain\Identity\Enums\Role::Manager);
@endphp

<aside class="w-64 shrink-0 bg-white border-r border-gray-200 min-h-[calc(100vh-4rem)]">
    <nav class="p-4 space-y-1">
        <a href="{{ route('my.dashboard') }}" wire:navigate
           class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('my.*') && ! request()->routeIs('my.accounts.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
            {{ __('My Workspace') }}
        </a>

        <a href="{{ route('my.accounts.index') }}" wire:navigate
           class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('my.accounts.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
            {{ __('My Accounts') }}
        </a>

        @if ($isManager || $isAdmin)
            <a href="{{ route('manager.dashboard') }}" wire:navigate
               class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('manager.*') && ! request()->routeIs('manager.accounts.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                {{ __('Team Overview') }}
            </a>

            <a href="{{ route('manager.accounts.index') }}" wire:navigate
               class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('manager.accounts.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                {{ __('Accounts') }}
            </a>
        @endif

        @if ($isAdmin)
            <a href="{{ route('admin.dashboard') }}" wire:navigate
               class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.*') && ! request()->routeIs('admin.accounts.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                {{ __('Administration') }}
            </a>

            <a href="{{ route('admin.accounts.index') }}" wire:navigate
               class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.accounts.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                {{ __('Manage Accounts') }}
            </a>
        @endif
    </nav>
</aside>
