<?php

namespace App\Domain\Contactos;

use App\Domain\Misiones\EntregaInvalida;
use App\Domain\Misiones\ImagenEntrega;
use App\Enums\EstadoEntrega;
use App\Enums\Rol;
use App\Enums\TipoContacto;
use App\Jobs\ProcesarCapturaContacto;
use App\Models\Contacto;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Contacto JOTI (internet): suma puntos a la PERSONA, con el puntaje local o del exterior que defina el admin. Se guardan solo
 * los datos del grupo con el que habló (grupo, ciudad, unidad y país: nada de nombres, teléfonos ni correos) y la captura de la
 * conversación, que se re-codifica sin EXIF y solo la ve el dirigente de la unidad y el admin. Sin tope de contactos.
 */
class RegistrarContactoJoti
{
    /**
     * @param  array{grupo_scout?: string, ciudad?: string, unidad_scout?: string, pais?: string}  $datos
     *
     * @throws AuthorizationException
     * @throws ContactoInvalido
     */
    public function __invoke(User $user, array $datos, ?UploadedFile $captura): Contacto
    {
        if ($user->rol !== Rol::Beneficiario || $user->unidad_id === null) {
            throw new AuthorizationException;
        }

        $limpios = [];
        foreach (['grupo_scout' => ['el grupo scout', 120], 'ciudad' => ['la ciudad', 120], 'unidad_scout' => ['la unidad', 120], 'pais' => ['el país', 80]] as $campo => [$nombre, $max]) {
            $valor = trim((string) preg_replace('/\s+/u', ' ', (string) ($datos[$campo] ?? '')));

            if ($valor === '') {
                throw new ContactoInvalido("Falta {$nombre}.");
            }

            if (mb_strlen($valor) > $max) {
                throw new ContactoInvalido(ucfirst($nombre)." puede tener hasta {$max} caracteres.");
            }

            $limpios[$campo] = $valor;
        }

        if ($captura === null) {
            throw new ContactoInvalido('Subí la captura de pantalla de la conversación.');
        }

        try {
            ImagenEntrega::validar($captura);
        } catch (EntregaInvalida $e) {
            throw new ContactoInvalido($e->getMessage());
        }

        $ruta = Storage::disk('private')->putFile('tmp', $captura);

        try {
            $contacto = Contacto::create($limpios + [
                'tipo' => TipoContacto::Joti,
                'user_id' => $user->getKey(),
                'unidad_id' => $user->unidad_id,
                'es_local' => Procedencia::esPaisLocal($limpios['pais']),
                'estado' => EstadoEntrega::Pendiente,
                'captura_ruta' => $ruta,
            ]);
        } catch (\Throwable $e) {
            Storage::disk('private')->delete($ruta);

            throw $e;
        }

        ProcesarCapturaContacto::dispatch($contacto->getKey());

        return $contacto;
    }
}
