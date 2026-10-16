@props(['titulo' => null, 'title' => null])
@php($titulo ??= $title)

<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#622599">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $titulo ? $titulo.' · ' : '' }}{{ __('jota.app.nombre') }}</title>
    <link rel="preload" href="{{ Vite::asset('resources/fonts/poppins-medium.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ Vite::asset('resources/fonts/poppins-bold.woff2') }}" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh md:bg-fondo-suave">
    <a href="#contenido" class="sr-only focus:not-sr-only focus:absolute focus:left-3 focus:top-3 focus:z-50 focus:rounded-full focus:bg-primario focus:px-5 focus:py-3 focus:text-sobre-primario">{{ __('jota.app.saltar') }}</a>
    {{ $slot }}
</body>
</html>
