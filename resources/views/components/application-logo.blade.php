<div {{ $attributes->merge(['class' => 'flex items-center']) }}>
    <img src="{{ app_logo('light') }}" alt="{{ app_setting('company_name', 'Loops HR') }} Logo" class="h-10 w-auto object-contain dark:hidden">
    <img src="{{ app_logo('dark') }}" alt="{{ app_setting('company_name', 'Loops HR') }} Logo" class="h-10 w-auto object-contain hidden dark:inline-block">
</div>
