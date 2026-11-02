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
            file_put_contents($csvPath, "\u{FEFF}ID,ISBN,Título,Autor,Editorial,Cantidad,URL Imagen,Año de publicación,Páginas,Idioma,Descripción\n"
                ."1,9780000000001,Libro existente,Autora existente,Editorial,4,https://example.com/portada.jpg,2001,140,Español,Descripción existente\n"
                ."2,9780000000002,Nuevo libro,Nuevo autor,Editorial,2,https://example.com/nuevo.jpg,2024,210,Inglés,Descripción nueva\n"
                ."3,9780000000003,,Autor sin título,Editorial,1,,,,,\n");

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
            'anio' => 2001,
            'paginas' => 140,
            'idioma' => 'Español',
            'descripcion' => 'Descripción existente',
        ]);
        $this->assertDatabaseHas('libros', [
            'isbn' => '9780000000002',
            'titulo' => 'Nuevo libro',
            'cantidad_total' => 2,
            'portada_url' => 'https://example.com/nuevo.jpg',
            'mueble_id' => $shelf->id,
            'anio' => 2024,
            'paginas' => 210,
            'idioma' => 'Inglés',
            'descripcion' => 'Descripción nueva',
        ]);
        $this->assertStringContainsString('fila,motivo', file_get_contents($reportPath));
        @unlink($reportPath);
    }

    public function test_import_normalizes_dash_and_date_values_to_missing_authors(): void
    {
        $csvPath = tempnam(sys_get_temp_dir(), 'catalogo-');
        $reportPath = tempnam(sys_get_temp_dir(), 'informe-');

        try {
            file_put_contents($csvPath, "titulo,autor\n"
                ."Sin autor,-\n"
                ."Fecha como autor,13/3/2025\n"
                ."Autor correcto,Autora real\n");

            $this->artisan('biblioteca:importar-catalogo', [
                'archivo' => $csvPath,
                '--reporte' => $reportPath,
            ])
                ->expectsOutputToContain('Filas importadas: 3. Filas rechazadas: 0.')
                ->assertExitCode(0);
        } finally {
            @unlink($csvPath);
            @unlink($reportPath);
        }

        $this->assertDatabaseHas('libros', ['titulo' => 'Sin autor', 'autor' => null]);
        $this->assertDatabaseHas('libros', ['titulo' => 'Fecha como autor', 'autor' => null]);
        $this->assertDatabaseHas('libros', ['titulo' => 'Autor correcto', 'autor' => 'Autora real']);
    }

    public function test_migration_cleans_existing_dash_and_date_authors(): void
    {
        $dashAuthorBook = Libro::create([
            'titulo' => 'Autor con guion',
            'autor' => '-',
            'cantidad_total' => 1,
        ]);
        $dateAuthorBook = Libro::create([
            'titulo' => 'Autor con fecha',
            'autor' => '15/2/2025',
            'cantidad_total' => 1,
        ]);
        $realAuthorBook = Libro::create([
            'titulo' => 'Autor válido',
            'autor' => 'Autora real',
            'cantidad_total' => 1,
        ]);

        $migration = require database_path('migrations/2026_10_29_000002_normalize_missing_book_authors.php');
        $migration->up();

        $this->assertNull($dashAuthorBook->fresh()->autor);
        $this->assertNull($dateAuthorBook->fresh()->autor);
        $this->assertSame('Autora real', $realAuthorBook->fresh()->autor);
    }
}
