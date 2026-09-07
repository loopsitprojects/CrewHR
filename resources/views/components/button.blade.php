@props([
    'variant' => 'primary',
    'type' => 'button',
])

@php
    $baseClasses = 'px-4 py-2 rounded-lg text-sm font-bold flex items-center justify-center gap-2 shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2';
    
    $variants = [
        'primary' => 'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500',
        'secondary' => 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 focus:ring-blue-500',
        'danger' => 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500',
        'ghost' => 'bg-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-100 shadow-none border-transparent focus:ring-gray-500',
    ];

    $classes = $baseClasses . ' ' . $variants[$variant];
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
