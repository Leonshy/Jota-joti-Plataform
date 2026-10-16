<?php

namespace App\Http\Controllers;

use App\Models\EntregaImagen;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/** Única vía para ver las fotos de las entregas: disco privado + Policy (CLAUDE.md §2.10). */
class EntregaImagenController extends Controller
{
    public function show(EntregaImagen $imagen): Response
    {
        Gate::authorize('ver', $imagen->entrega);

        // Nunca se sirve el original sin re-codificar (todavía puede traer EXIF/GPS).
        abort_if($imagen->procesada_at === null, 404);

        return Storage::disk('private')->response($imagen->ruta, null, [
            'Content-Type' => 'image/jpeg',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
