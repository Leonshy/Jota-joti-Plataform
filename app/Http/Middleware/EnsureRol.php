<?php

namespace App\Http\Middleware;

use App\Enums\Rol;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deja pasar solo a los roles indicados (`rol:dirigente,admin`). El alcance fino (la unidad del dirigente,
 * etc.) lo siguen decidiendo las Policies: esto solo evita entrar a la sección equivocada.
 */
class EnsureRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $permitidos = array_map(fn (string $r) => Rol::from($r), $roles);

        abort_unless(in_array($request->user()?->rol, $permitidos, true), 403);

        return $next($request);
    }
}
