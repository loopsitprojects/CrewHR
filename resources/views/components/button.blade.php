@props([
    'variant' => 'primary',
    'type' => 'button',
])

@php
    $baseClasses = 'px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all cursor-pointer focus:outline-none';
    
    $variants = [
        'primary' => 'btn-primary text-white font-extrabold shadow-md shadow-blue-600/30',
        'secondary' => 'btn-secondary text-slate-700 dark:text-slate-200 font-bold',
        'danger' => 'btn-rose text-white font-extrabold shadow-md shadow-rose-600/30',
        'ghost' => 'bg-transparent text-slate-500 hover:text-slate-800 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 shadow-none border-transparent',
    ];

    $classes = $baseClasses . ' ' . $variants[$variant];
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
