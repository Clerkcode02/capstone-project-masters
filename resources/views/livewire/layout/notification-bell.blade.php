<div wire:poll.30s>
    <x-dropdown align="right" width="w-80">
        <x-slot name="trigger">
            <button class="relative inline-flex items-center p-2 rounded-md text-gray-500 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
                @if ($this->unreadCount > 0)
                    <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center h-4 min-w-[1rem] px-1 rounded-full bg-rose-600 text-white text-[10px] font-medium leading-none">
                        {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
                    </span>
                @endif
            </button>
        </x-slot>

        <x-slot name="content">
            <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100">
                <span class="text-sm font-semibold text-gray-700">{{ __('Notifications') }}</span>
                @if ($this->unreadCount > 0)
                    <button type="button" wire:click="markAllAsRead" wire:loading.attr="disabled" wire:target="markAllAsRead" class="text-xs text-indigo-600 hover:text-indigo-800">
                        {{ __('Mark all read') }}
                    </button>
                @endif
            </div>

            <div class="max-h-96 overflow-y-auto">
                @forelse ($this->notifications as $notification)
                    @php
                        [$message, $link] = match ($notification->type) {
                            \App\Notifications\OverAllocationWarning::class => [
                                "{$notification->data['user_name']} is over the workload threshold ({$notification->data['workload_score']}/{$notification->data['threshold']} points).",
                                route('dashboard.workload'),
                            ],
                            \App\Notifications\NewRecommendationAvailable::class => [
                                "New {$notification->data['trigger_label']} recommendation for task {$notification->data['task_reference']}.",
                                route('optimization.recommendations'),
                            ],
                            \App\Notifications\ReportReady::class => [
                                "{$notification->data['label']} is ready to download.",
                                route('reports.index'),
                            ],
                            default => ['You have a new notification.', null],
                        };
                    @endphp
                    <div wire:key="notification-{{ $notification->id }}" class="px-4 py-3 border-b border-gray-50 last:border-0 {{ $notification->read_at ? 'bg-white' : 'bg-indigo-50/60' }}">
                        <a
                            @if ($link) href="{{ $link }}" wire:navigate @endif
                            wire:click="markAsRead('{{ $notification->id }}')"
                            class="block text-sm text-gray-700 hover:text-gray-900"
                        >
                            {{ $message }}
                        </a>
                        <p class="mt-1 text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                @empty
                    <x-empty-state message="No notifications yet." />
                @endforelse
            </div>
        </x-slot>
    </x-dropdown>
</div>
