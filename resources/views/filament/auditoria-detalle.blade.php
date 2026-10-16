@php($propiedades = $registro->properties->toArray())
<div style="display: grid; gap: .75rem;">
    <p style="margin: 0;">
        <strong>{{ $registro->causer?->name ?? 'Sistema' }}</strong> · {{ $registro->created_at->format('d/m/Y H:i:s') }}
    </p>

    @if (! empty($propiedades['old']) || ! empty($propiedades['attributes']))
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr>
                    <th scope="col" style="padding: .4rem; border-bottom: 1px solid var(--gray-300);">Campo</th>
                    <th scope="col" style="padding: .4rem; border-bottom: 1px solid var(--gray-300);">Antes</th>
                    <th scope="col" style="padding: .4rem; border-bottom: 1px solid var(--gray-300);">Después</th>
                </tr>
            </thead>
            <tbody>
                @foreach (array_keys(($propiedades['attributes'] ?? []) + ($propiedades['old'] ?? [])) as $campo)
                    <tr>
                        <td style="padding: .4rem; border-bottom: 1px solid var(--gray-200);">{{ $campo }}</td>
                        <td style="padding: .4rem; border-bottom: 1px solid var(--gray-200);">{{ is_scalar($propiedades['old'][$campo] ?? null) ? $propiedades['old'][$campo] : '—' }}</td>
                        <td style="padding: .4rem; border-bottom: 1px solid var(--gray-200);">{{ is_scalar($propiedades['attributes'][$campo] ?? null) ? $propiedades['attributes'][$campo] : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif ($propiedades)
        <pre style="white-space: pre-wrap; margin: 0;">{{ json_encode($propiedades, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    @else
        <p style="margin: 0;">Sin más datos.</p>
    @endif
</div>
