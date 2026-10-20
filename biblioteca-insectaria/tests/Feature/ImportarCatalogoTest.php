<?php

namespace Tests\Feature;

use App\Models\Libro;
use App\Models\Mueble;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportarCatalogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_csv_headers_import_and_move_books_to_the_selected_shelf(): void
    {
        $existingBook = Libro::create([
            'titulo' => 'Libro existente',
            'autor' => 'Autora existente',
            'isbn' => '9780000000001',
            'cantidad_total' => 3,
        ]);
        $otherBook = Libro::create([
            'titulo' => 'Otro libro',
            'autor' => 'Otro autor',
            'cantidad_total' => 2,
        ]);
        $csvPath = tempnam(sys_get_temp_dir(), 'catalogo-');
        $reportPath = tempnam(sys_get_temp_dir(), 'informe-');

        try {
            file_put_contents($csvPath, "\u{FEFF}ID,ISBN,Título,Autor,Editorial,Cantidad,URL Imagen\n"
                ."1,9780000000001,Libro existente,Autora existente,Editorial,4,https://example.com/portada.jpg\n"
                ."2,9780000000002,Nuevo libro,Nuevo autor,Editorial,2,https://example.com/nuevo.jpg\n"
                ."3,9780000000003,,Autor sin título,Editorial,1,\n");

            $this->artisan('biblioteca:importar-catalogo', [
                'archivo' => $csvPath,
                '--reporte' => $reportPath,
                '--mueble' => 'Plan Lector',
            ])
                ->expectsOutputToContain('Filas importadas: 2. Filas rechazadas: 1.')
                ->assertExitCode(1);
        } finally {
            @unlink($csvPath);
        }

        $shelf = Mueble::where('nombre', 'Plan Lector')->firstOrFail();
        $this->assertSame($shelf->id, $existingBook->fresh()->mueble_id);
        $this->assertSame($shelf->id, $otherBook->fresh()->mueble_id);
        $this->assertDatabaseHas('libros', [
            'isbn' => '9780000000001',
            'cantidad_total' => 7,
            'mueble_id' => $shelf->id,
        ]);
        $this->assertDatabaseHas('libros', [
            'isbn' => '9780000000002',
            'titulo' => 'Nuevo libro',
            'cantidad_total' => 2,
            'portada_url' => 'https://example.com/nuevo.jpg',
            'mueble_id' => $shelf->id,
        ]);
        $this->assertStringContainsString('fila,motivo', file_get_contents($reportPath));
        @unlink($reportPath);
    }
}
