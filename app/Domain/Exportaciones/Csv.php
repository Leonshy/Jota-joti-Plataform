<?php

namespace App\Domain\Exportaciones;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV pensado para abrirse en Excel en español: separador ";" y UTF-8 con BOM (para las tildes).
 */
class Csv
{
    /**
     * Evita la inyección de fórmulas: un nombre como "=HYPERLINK(...)" o "@SUM(...)" que Excel
     * ejecutaría al abrir el archivo se neutraliza con un apóstrofo. Los números no se tocan.
     */
    public static function celda(mixed $valor): string|int|float
    {
        if (is_int($valor) || is_float($valor)) {
            return $valor;
        }

        $texto = (string) $valor;

        return $texto !== '' && str_contains("=+-@\t\r", $texto[0]) ? "'".$texto : $texto;
    }

    /** @param  iterable<list<mixed>>  $filas */
    public static function contenido(array $encabezados, iterable $filas): string
    {
        $flujo = fopen('php://temp', 'r+');
        fwrite($flujo, "\xEF\xBB\xBF");
        fputcsv($flujo, $encabezados, ';', '"', '');

        foreach ($filas as $fila) {
            fputcsv($flujo, array_map(self::celda(...), $fila), ';', '"', '');
        }

        rewind($flujo);
        $texto = stream_get_contents($flujo);
        fclose($flujo);

        return $texto;
    }

    /** @param  iterable<list<mixed>>  $filas */
    public static function descargar(string $archivo, array $encabezados, iterable $filas): StreamedResponse
    {
        return response()->streamDownload(function () use ($encabezados, $filas) {
            echo self::contenido($encabezados, $filas);
        }, $archivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
