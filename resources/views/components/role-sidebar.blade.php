@php
    $user = auth()->user();
    $isAdmin = $user->isRole(\App\Domain\Identity\Enums\Role::Administrator);
    $isManager = $user->isRole(\App\Domain\Identity\Enums\Role::Manager);
@endphp

<aside class="w-64 shrink-0 bg-white border-r border-gray-200 min-h-[calc(100vh-4rem)]">
    <nav class="p-4 space-y-1">
        <a href="{{ route('my.dashboard') }}" wire:navigate
           class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('my.dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
            {{ __('My Workspace') }}
        </a>

        <a href="{{ route('my.tasks.index') }}" wire:navigate
           class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('my.tasks.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
            {{ __('My Tasks') }}
        </a>

        @if ($isManager || $isAdmin)
            <a href="{{ route('manager.dashboard') }}" wire:navigate
               class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('manager.dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                {{ __('Team Overview') }}
            </a>

            <a href="{{ route('manager.tasks.index') }}" wire:navigate
               class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('manager.tasks.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                {{ __('Tasks') }}
            </a>
        @endif

        @if ($isAdmin)
            <a href="{{ route('admin.dashboard') }}" wire:navigate
               class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                {{ __('Administration') }}
            </a>
        @endif
    </nav>
</aside>
