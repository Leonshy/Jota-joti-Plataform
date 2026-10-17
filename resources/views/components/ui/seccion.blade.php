@props(['titulo', 'nivel' => 2])

@php($id = 'sec-'.\Illuminate\Support\Str::slug($titulo))

<section aria-labelledby="{{ $id }}" {{ $attributes->merge(['class' => 'flex flex-col gap-3']) }}>
    <h{{ $nivel }} id="{{ $id }}" class="text-lg">{{ $titulo }}</h{{ $nivel }}>
    {{ $slot }}
</section>
