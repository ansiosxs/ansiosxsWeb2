<?php

namespace App\Console\Commands;

use App\Models\Libro;
use App\Models\Mueble;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ImportarCatalogo extends Command
{
    protected $signature = 'biblioteca:importar-catalogo {archivo : CSV exportado desde Excel} {--reporte=storage/app/importacion-informe.csv : Ruta del informe de filas rechazadas} {--mueble= : Asigna todos los libros al mueble indicado}';

    protected $description = 'Importa libros y cantidades de inventario desde un CSV exportado desde Excel.';

    public function handle(): int
    {
        $path = $this->argument('archivo');

        if (! is_file($path) || ! is_readable($path)) {
            $this->error('No se puede leer el archivo CSV indicado.');

            return self::FAILURE;
        }

        $file = new \SplFileObject($path, 'r');
        $firstLine = $file->fgets();
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        $file->rewind();
        $headers = $file->fgetcsv($delimiter);

        if (! is_array($headers)) {
            $this->error('El archivo no contiene una fila de encabezados.');

            return self::FAILURE;
        }

        $headers = array_map(function ($header): string {
            $header = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header);

            return mb_strtolower(trim($header));
        }, $headers);
        $aliases = [
            'título' => 'titulo',
            'url imagen' => 'portada_url',
        ];
        $headers = array_map(fn ($header) => $aliases[$header] ?? $header, $headers);
        if (array_diff(['titulo', 'autor'], $headers)) {
            $this->error('El CSV debe incluir las columnas titulo y autor.');

            return self::FAILURE;
        }

        $defaultShelf = trim((string) $this->option('mueble'));

        $reportPath = $this->option('reporte');
        $directory = dirname($reportPath);
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            $this->error('No se pudo crear la carpeta del informe.');

            return self::FAILURE;
        }

        $report = fopen($reportPath, 'wb');
        if ($report === false) {
            $this->error('No se pudo crear el informe de importación.');

            return self::FAILURE;
        }
        if (fputcsv($report, ['fila', 'motivo']) === false) {
            fclose($report);
            $this->error('No se pudo escribir el encabezado del informe.');

            return self::FAILURE;
        }

        $rejected = 0;
        $imported = 0;
        $line = 1;

        if ($defaultShelf !== '') {
            DB::transaction(function () use ($defaultShelf): void {
                $mueble = Mueble::query()->firstOrCreate(['nombre' => $defaultShelf]);
                Libro::query()->update(['mueble_id' => $mueble->id]);
            });
        }

        while (! $file->eof()) {
            $values = $file->fgetcsv($delimiter);
            $line++;

            if (! is_array($values) || count($values) === 1 && ($values[0] === null || trim((string) $values[0]) === '')) {
                continue;
            }

            $row = array_combine($headers, array_pad(array_slice($values, 0, count($headers)), count($headers), null));
            $row = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row);
            $row['cantidad'] = ($row['cantidad'] ?? '') === '' ? 1 : $row['cantidad'];
            $errors = Validator::make($row, [
                'titulo' => ['required', 'string', 'max:255'],
                'autor' => ['required', 'string', 'max:255'],
                'isbn' => ['nullable', 'string', 'max:20'],
                'editorial' => ['nullable', 'string', 'max:255'],
                'anio' => ['nullable', 'integer', 'between:1000,9999'],
                'portada_url' => ['nullable', 'url', 'max:255'],
                'mueble' => ['nullable', 'string', 'max:255'],
                'cantidad' => ['required', 'integer', 'min:0', 'max:1000000'],
            ])->errors()->all();

            if ($errors) {
                if (fputcsv($report, [$line, implode(' ', $errors)]) === false) {
                    fclose($report);
                    throw new \RuntimeException('No se pudo escribir el informe de importación.');
                }
                $rejected++;

                continue;
            }

            DB::transaction(function () use ($row, $defaultShelf, &$imported): void {
                $muebleNombre = $defaultShelf !== '' ? $defaultShelf : ($row['mueble'] ?? null);
                $mueble = ! empty($muebleNombre)
                    ? Mueble::query()->firstOrCreate(['nombre' => $muebleNombre])
                    : null;
                $libro = Libro::query()
                    ->where('titulo', $row['titulo'])
                    ->where('autor', $row['autor'])
                    ->where('isbn', $row['isbn'] ?? null)
                    ->first();

                if (! $libro) {
                    $libro = Libro::query()->create([
                        'titulo' => $row['titulo'],
                        'autor' => $row['autor'],
                        'isbn' => $row['isbn'] ?? null,
                        'editorial' => $row['editorial'] ?? null,
                        'anio' => $row['anio'] ?? null,
                        'portada_url' => $row['portada_url'] ?? null,
                        'mueble_id' => $mueble?->id,
                        'cantidad_total' => 0,
                    ]);
                } elseif ($mueble && $libro->mueble_id !== $mueble->id) {
                    $libro->update(['mueble_id' => $mueble->id]);
                }

                $libro->increment('cantidad_total', (int) $row['cantidad']);
                $imported++;
            });
        }

        fclose($report);
        $this->info("Filas importadas: {$imported}. Filas rechazadas: {$rejected}. Informe: {$reportPath}");

        return $rejected > 0 ? self::FAILURE : self::SUCCESS;
    }
}
