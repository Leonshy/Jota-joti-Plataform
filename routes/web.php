<?php

use App\Domain\Acceso\DestinoPorRol;
use App\Domain\Acceso\UnidadActiva;
use App\Http\Controllers\AsistenciaPublicaController;
use App\Http\Controllers\CobroController;
use App\Http\Controllers\DisenoController;
use App\Http\Controllers\EntregaImagenController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\RegistrarAsistenciaController;
use App\Livewire\Acceso\Activar;
use App\Livewire\Acceso\Ingresar;
use App\Livewire\Beneficiario\Agenda;
use App\Livewire\Beneficiario\Entregar;
use App\Livewire\Beneficiario\Historial as BeneficiarioHistorial;
use App\Livewire\Beneficiario\Inicio as BeneficiarioInicio;
use App\Livewire\Beneficiario\Mision;
use App\Livewire\Beneficiario\Misiones;
use App\Livewire\Beneficiario\MisReservas;
use App\Livewire\Beneficiario\Ranking as BeneficiarioRanking;
use App\Livewire\Dirigente\Asistencia;
use App\Livewire\Dirigente\Cola;
use App\Livewire\Dirigente\Cumplimientos;
use App\Livewire\Dirigente\Penalizaciones;
use App\Livewire\Dirigente\Ranking as DirigenteRanking;
use App\Livewire\Dirigente\Reservas as DirigenteReservas;
use App\Livewire\Dirigente\Revisar;
use App\Livewire\Dirigente\Sesion;
use App\Livewire\Tienda\Cobrar;
use App\Livewire\Tienda\Devolver as TiendaDevolver;
use App\Livewire\Tienda\Historial as TiendaHistorial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// La raíz solo manda a cada persona a su lugar: sin sesión, a ingresar; con sesión, a la pantalla de su rol.
Route::get('/', fn () => redirect(auth()->check() ? DestinoPorRol::para(auth()->user()) : route('ingresar')))->name('raiz');

Route::middleware('guest')->group(function () {
    Route::livewire('/ingresar', Ingresar::class)->name('ingresar');
    Route::livewire('/activar', Activar::class)->name('activar');
});

Route::post('/salir', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('ingresar');
})->middleware('auth')->name('salir');

// Pantallas del participante.
Route::middleware(['auth', 'rol:beneficiario'])->prefix('app')->name('app.')->group(function () {
    Route::livewire('/', BeneficiarioInicio::class)->name('inicio');
    Route::livewire('/ranking', BeneficiarioRanking::class)->name('ranking');
    Route::livewire('/misiones', Misiones::class)->name('misiones');
    Route::livewire('/misiones/{mision}', Mision::class)->name('mision');
    Route::livewire('/misiones/{mision}/entregar', Entregar::class)->name('entregar');
    Route::livewire('/actividades', Agenda::class)->name('actividades');
    Route::livewire('/actividades/reservas', MisReservas::class)->name('reservas');
    Route::view('/escanear', 'escanear')->name('escanear');
    Route::livewire('/historial', BeneficiarioHistorial::class)->name('historial');
});

// Las pantallas del dirigente también las usa el administrador (sobre la unidad que elija).
Route::middleware(['auth', 'rol:dirigente,admin'])->prefix('dirigente')->name('dirigente.')->group(function () {
    Route::post('/unidad', function (Request $request, UnidadActiva $activa) {
        $activa->elegir($request->user(), $request->integer('unidad'));

        return back();
    })->name('unidad');
    Route::livewire('/', Cola::class)->name('inicio');
    Route::livewire('/entregas/{entrega}', Revisar::class)->name('revisar');
    Route::livewire('/ranking', DirigenteRanking::class)->name('ranking');
    Route::livewire('/reservas', DirigenteReservas::class)->name('reservas');
    Route::livewire('/asistencia', Asistencia::class)->name('asistencia');
    Route::livewire('/asistencia/{sesion}', Sesion::class)->name('sesion');
    Route::livewire('/cumplimientos', Cumplimientos::class)->name('cumplimientos');
    Route::livewire('/cumplimientos/{actividad}', Cumplimientos::class)->name('cumplimientos.actividad');
    Route::livewire('/penalizaciones', Penalizaciones::class)->name('penalizaciones');
});

// La tienda también la usa el administrador.
Route::middleware(['auth', 'rol:tienda,admin'])->prefix('tienda')->name('tienda.')->group(function () {
    Route::livewire('/', Cobrar::class)->name('inicio');
    Route::livewire('/devolver', TiendaDevolver::class)->name('devolver');
    Route::livewire('/historial', TiendaHistorial::class)->name('historial');
});

Route::middleware('auth')->group(function () {
    Route::get('/entregas/imagenes/{imagen}', [EntregaImagenController::class, 'show'])->name('entregas.imagen');

    Route::get('/ranking', RankingController::class)->name('ranking');

    // Tienda: el QR de cobro abre `cobro.ver`; la persona confirma el monto con `cobro.pagar`.
    Route::get('/cobros/{token}', [CobroController::class, 'ver'])->middleware('throttle:pagos')->name('cobro.ver');
    Route::post('/cobros/{token}/pagar', [CobroController::class, 'pagar'])->middleware('throttle:pagos')->name('cobro.pagar');
});

// Pantalla pública del QR rotativo: enlace firmado, deja de servir al cerrar la sesión.
Route::middleware(['signed', 'throttle:qr-publico'])->group(function () {
    Route::get('/asistencia/{sesion}', [AsistenciaPublicaController::class, 'show'])->name('asistencia.publica');
    Route::get('/asistencia/{sesion}/qr', [AsistenciaPublicaController::class, 'qr'])->name('asistencia.qr');
});

// Destino del QR: requiere sesión iniciada (si no, pide login y vuelve).
Route::get('/asistencia/{sesion}/registrar/{token}', RegistrarAsistenciaController::class)
    ->middleware(['auth', 'throttle:escaneos'])
    ->name('asistencia.registrar');

// Vista previa del sistema de diseño (datos de ejemplo). Nunca en producción.
if (! app()->isProduction()) {
    Route::get('/diseno', [DisenoController::class, 'guia'])->name('diseno.guia');
    Route::get('/diseno/{pantalla}', [DisenoController::class, 'pantalla'])->name('diseno.pantalla');
}
