@props(['titulo'])

@php($id = 'sec-'.\Illuminate\Support\Str::slug($titulo))

<section aria-labelledby="{{ $id }}" {{ $attributes->merge(['class' => 'flex flex-col gap-3']) }}>
    <h2 id="{{ $id }}" class="text-lg">{{ $titulo }}</h2>
    {{ $slot }}
</section>
