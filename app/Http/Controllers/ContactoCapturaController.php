<?php

namespace App\Http\Controllers;

use App\Models\Contacto;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/** Única vía para ver las capturas de los contactos JOTI: disco privado + Policy (CLAUDE.md §2.10). */
class ContactoCapturaController extends Controller
{
    public function show(Contacto $contacto): Response
    {
        Gate::authorize('ver', $contacto);

        // Nunca se sirve el original sin re-codificar (todavía puede traer EXIF/GPS).
        abort_if($contacto->captura_procesada_at === null || $contacto->captura_ruta === null, 404);

        return Storage::disk('private')->response($contacto->captura_ruta, null, [
            'Content-Type' => 'image/jpeg',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
