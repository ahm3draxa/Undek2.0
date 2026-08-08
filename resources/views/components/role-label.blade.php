@props(['name'])

@php
    $label = $name === 'ceo'
        ? 'CEO'
        : \Illuminate\Support\Str::of($name)->replace('_', ' ')->title();
@endphp

<span {{ $attributes }}>{{ $label }}</span>
