@props(['disabled' => false, 'error' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border rounded-lg text-sm focus:outline-none focus:ring-2 px-3 py-2 bg-gray-50 ' . ($error ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-blue-500')]) !!}>
