@props(['message'])

<div {{ $attributes->merge(['class' => 'rounded-md border border-dashed border-gray-200 p-4 text-center text-sm text-gray-500']) }}>
    {{ $message }}
</div>
