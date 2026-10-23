@props(['name'])

<span {{ $attributes }}>{{ \App\Support\Organization::roleLabel($name) }}</span>
