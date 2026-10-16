<?php

namespace App\Livewire\Tienda;

use App\Models\Cobro;
use App\Models\Devolucion;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Lo que cobró y devolvió este puesto de tienda. */
#[Layout('components.layouts.movil')]
class Historial extends Component
{
    public function render()
    {
        $yo = auth()->id();

        $cobros = Cobro::query()->where('creado_por', $yo)->with('pagador:id,name')->latest('id')->limit(30)->get()
            ->map(fn (Cobro $c) => ['monto' => $c->monto, 'estado' => $c->estado(), 'quien' => $c->pagador?->name, 'cuando' => ($c->pagado_at ?? $c->created_at)->format('H:i')])->all();

        $devoluciones = Devolucion::query()->where('registrado_por', $yo)->with('user:id,name')->latest('id')->limit(15)->get()
            ->map(fn (Devolucion $d) => ['monto' => $d->monto, 'quien' => $d->user->name, 'motivo' => $d->motivo, 'cuando' => $d->created_at->format('H:i')])->all();

        return view('livewire.tienda.historial', [
            'total' => (int) Cobro::query()->where('creado_por', $yo)->whereNotNull('pagado_at')->sum('monto'),
            'cobros' => $cobros,
            'devoluciones' => $devoluciones,
        ])->title(__('jota.tienda.historial_titulo'));
    }
}
