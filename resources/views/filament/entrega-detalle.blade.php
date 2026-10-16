<div style="display: grid; gap: 1rem;">
    <dl style="display: grid; grid-template-columns: max-content 1fr; gap: .25rem 1rem; margin: 0;">
        <dt><strong>Entregó</strong></dt>
        <dd style="margin: 0;">{{ $entrega->user->name }} · {{ $entrega->user->unidad?->nombre }}</dd>
        @if ($entrega->patrulla)
            <dt><strong>En nombre de</strong></dt>
            <dd style="margin: 0;">Patrulla {{ $entrega->patrulla->nombre }}</dd>
        @endif
        <dt><strong>Estado</strong></dt>
        <dd style="margin: 0;">{{ $entrega->estado->getLabel() }}</dd>
        @if ($entrega->revisado_at)
            <dt><strong>Revisó</strong></dt>
            <dd style="margin: 0;">{{ $entrega->revisor?->name }} · {{ $entrega->revisado_at->format('d/m H:i') }}</dd>
        @endif
        @if ($entrega->motivo_rechazo)
            <dt><strong>Motivo</strong></dt>
            <dd style="margin: 0;">{{ $entrega->motivo_rechazo }}</dd>
        @endif
    </dl>

    @if ($entrega->texto)
        <div>
            <strong>Texto</strong>
            <p style="white-space: pre-wrap; margin: .25rem 0 0;">{{ $entrega->texto }}</p>
        </div>
    @endif

    @if ($entrega->imagenes->isNotEmpty())
        <div>
            <strong>Fotos</strong>
            <div style="display: grid; gap: .75rem; margin-top: .25rem;">
                @foreach ($entrega->imagenes as $imagen)
                    @if ($imagen->procesada_at)
                        <a href="{{ route('entregas.imagen', $imagen) }}" target="_blank" rel="noopener">
                            <img src="{{ route('entregas.imagen', $imagen) }}" alt="Foto {{ $loop->iteration }} de la entrega" loading="lazy" style="max-width: 100%; max-height: 22rem;">
                        </a>
                    @else
                        <p>La foto {{ $loop->iteration }} no está disponible (se está procesando o no se pudo procesar).</p>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</div>
