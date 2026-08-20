@props(['message'])

<div class="text-center py-8 text-sm text-gray-500">
    {{ $message ?? $slot }}
</div>
