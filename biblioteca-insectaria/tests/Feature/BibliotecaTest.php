<?php

namespace Tests\Feature;

use App\Mail\RecordatorioVencimiento;
use App\Models\Anuncio;
use App\Models\Libro;
use App\Models\Prestamo;
use App\Models\Socio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class BibliotecaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 16)->setTime(10, 0));
    }

    public function test_us01_guest_cannot_open_admin_and_volunteer_can_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get(route('login'))->assertOk();

        $user = User::factory()->create(['email' => 'voluntaria@example.cl', 'password' => 'ClaveSegura123!']);
        $this->post('/login', ['email' => $user->email, 'password' => 'ClaveSegura123!'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_volunteer_access_link_opens_dashboard_and_expired_session_returns_to_login(): void
    {
        $voluntario = User::factory()->create();

        $this->actingAs($voluntario)->get('/')
            ->assertOk()
            ->assertSee(route('admin.dashboard'), false);
        $this->get(route('login'))->assertRedirect(route('admin.dashboard'));

        $this->post(route('logout'));
        $this->get('/')->assertOk()->assertSee(route('login'), false);
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_us01_login_errors_are_generic_and_attempts_are_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from('/login')->post('/login', ['email' => 'alguien@example.cl', 'password' => 'incorrecta'])
                ->assertSessionHasErrors(['email' => 'Las credenciales ingresadas no son correctas.']);
        }

        $this->from('/login')->post('/login', ['email' => 'alguien@example.cl', 'password' => 'incorrecta'])
            ->assertSessionHasErrors(['email' => 'Demasiados intentos. Intenta nuevamente en un minuto.']);
    }

    public function test_volunteer_command_can_generate_phpmyadmin_sql_without_creating_local_account(): void
    {
        $this->artisan('biblioteca:crear-voluntario', ['--sql' => true])
            ->expectsQuestion('Nombre', "Ada O'Neil")
            ->expectsQuestion('Correo electrónico', 'ada@example.cl')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'ClaveSegura123!')
            ->expectsOutputToContain("VALUES ('Ada O''Neil', 'ada@example.cl',")
            ->expectsOutputToContain('No se creó una cuenta en esta base local.')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('users', ['email' => 'ada@example.cl']);
    }

    public function test_us02_book_requires_title_and_block_deactivation_while_loan_is_active(): void
    {
        $voluntario = User::factory()->create();
        $this->actingAs($voluntario)->from('/admin/libros/create')
            ->post(route('admin.libros.store'), ['titulo' => '', 'autor' => 'Autor'])
            ->assertSessionHasErrors('titulo');
        $this->assertDatabaseCount('libros', 0);

        $libro = $this->createBookAndStock('Libro prestado');
        $socio = $this->createMember();
        $this->createActiveLoan($socio, $libro, $voluntario);

        $this->actingAs($voluntario)->delete(route('admin.libros.destroy', $libro))
            ->assertSessionHasErrors('libro');
        $this->assertDatabaseHas('libros', ['id' => $libro->id, 'deleted_at' => null]);
    }

    public function test_us02_book_with_loan_history_is_soft_deleted_after_all_units_are_returned(): void
    {
        $voluntario = User::factory()->create();
        $libro = $this->createBookAndStock('Libro con historial');
        $prestamo = $this->createActiveLoan($this->createMember(), $libro, $voluntario, today()->subDay());
        $prestamo->update([
            'estado' => 'devuelto',
            'cantidad_devuelta' => $prestamo->cantidad,
            'fecha_devolucion' => now(),
            'devuelto_por_user_id' => $voluntario->id,
        ]);
        $this->travelTo(now()->addDay());

        $this->actingAs($voluntario)->delete(route('admin.libros.destroy', $libro))
            ->assertRedirect(route('admin.libros.index'));
        $this->assertSoftDeleted('libros', ['id' => $libro->id]);
    }

    public function test_us03_book_inventory_is_required_and_cannot_be_reduced_below_loans(): void
    {
        $voluntario = User::factory()->create();
        $this->actingAs($voluntario)->from(route('admin.libros.create'))
            ->post(route('admin.libros.store'), ['titulo' => 'Sin inventario declarado', 'autor' => 'Autora'])
            ->assertSessionHasErrors('cantidad_total');

        $libro = $this->createBookAndStock('Libro asociado', 3);
        $this->createActiveLoan($this->createMember(), $libro, $voluntario, null, 2);
        $this->from(route('admin.libros.edit', $libro))
            ->put(route('admin.libros.update', $libro), [
                'titulo' => $libro->titulo,
                'autor' => $libro->autor,
                'cantidad_total' => 1,
            ])->assertSessionHasErrors('cantidad_total');
    }

    public function test_us04_rejects_invalid_run_and_normalizes_valid_member_data(): void
    {
        $voluntario = User::factory()->create();
        $payload = [
            'run' => '12.345.678-4',
            'nombres' => 'Camila',
            'apellidos' => 'Lectora',
            'telefono' => '987654321',
            'comuna' => 'Concepción',
        ];

        $this->actingAs($voluntario)->from(route('admin.socios.create'))
            ->post(route('admin.socios.store'), $payload)
            ->assertSessionHasErrors('run');
        $this->assertDatabaseCount('socios', 0);

        $payload['run'] = '12.345.678-5';
        $this->actingAs($voluntario)->post(route('admin.socios.store'), $payload)
            ->assertRedirect();
        $this->assertDatabaseHas('socios', [
            'run' => '123456785',
            'telefono' => '+56 9 8765 4321',
            'vigente_hasta' => today()->addYear()->format('Y-m-d').' 00:00:00',
            'user_id' => $voluntario->id,
        ]);

        $payload['run'] = '12345678-5';
        $this->actingAs($voluntario)->from(route('admin.socios.create'))
            ->post(route('admin.socios.store'), $payload)
            ->assertSessionHasErrors('run');
    }

    public function test_us05_borrow_uses_configured_due_date_and_retries_are_idempotent(): void
    {
        $voluntario = User::factory()->create();
        $socio = $this->createMember();
        $libro = $this->createBookAndStock('Una lectura', 3);
        $uuid = (string) Str::uuid();
        $payload = ['libro_id' => $libro->id, 'cantidad' => 2, 'socio_id' => $socio->id, 'operacion_uuid' => $uuid];

        $this->actingAs($voluntario)->postJson(route('admin.prestamos.store'), $payload)
            ->assertOk()->assertJsonPath('redireccion', route('admin.prestamos.index'));
        $this->postJson(route('admin.prestamos.store'), $payload)->assertOk();

        $this->assertDatabaseCount('prestamos', 1);
        $this->assertDatabaseHas('prestamos', [
            'libro_id' => $libro->id,
            'cantidad' => 2,
            'cantidad_devuelta' => 0,
            'fecha_vencimiento' => today()->addDays(14)->format('Y-m-d').' 00:00:00',
            'estado' => 'activo',
            'user_id' => $voluntario->id,
        ]);
        $this->assertSame(1, $libro->fresh()->cantidad_disponible);
    }

    public function test_loan_search_returns_book_details_and_available_quantity(): void
    {
        $voluntario = User::factory()->create();
        $book = Libro::create([
            'titulo' => 'La biblioteca secreta',
            'autor' => 'Autora de prueba',
            'isbn' => '9780000000001',
            'editorial' => 'Insectaria',
            'anio' => 2024,
            'cantidad_total' => 4,
        ]);
        $this->createActiveLoan($this->createMember(), $book, $voluntario, null, 1);
        $this->createBookAndStock('La biblioteca agotada', 0);

        $this->actingAs($voluntario)->getJson(route('admin.prestamos.libros', ['q' => 'La biblioteca']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $book->id)
            ->assertJsonPath('0.autor', 'Autora de prueba')
            ->assertJsonPath('0.editorial', 'Insectaria')
            ->assertJsonPath('0.cantidad_disponible', 3);
    }

    public function test_member_membership_expiration_actions_update_the_due_date(): void
    {
        $voluntario = User::factory()->create();
        $socio = $this->createMember();

        $this->actingAs($voluntario)->post(route('admin.socios.caducar', $socio))
            ->assertRedirect();
        $this->assertSame(today()->subDay()->toDateString(), $socio->fresh()->vigente_hasta->toDateString());

        $nuevaFecha = today()->addMonths(6)->toDateString();
        $this->put(route('admin.socios.caducidad', $socio), ['vigente_hasta' => $nuevaFecha])
            ->assertRedirect();
        $this->assertSame($nuevaFecha, $socio->fresh()->vigente_hasta->toDateString());
    }

    public function test_us05_rejects_overdue_member_expired_membership_and_maximum_loans(): void
    {
        $voluntario = User::factory()->create();
        $bookA = $this->createBookAndStock('A', 3);
        $overdue = $this->createMember();
        $this->createActiveLoan($overdue, $bookA, $voluntario, today()->subDay());
        $bookB = $this->createBookAndStock('B', 5);
        $bookC = $this->createBookAndStock('C', 5);

        $this->actingAs($voluntario)->from(route('admin.prestamos.index'))
            ->post(route('admin.prestamos.store'), [
                'libro_id' => $bookB->id,
                'cantidad' => 1,
                'socio_id' => $overdue->id,
                'operacion_uuid' => (string) Str::uuid(),
            ])->assertSessionHasErrors('socio_id');

        $expired = $this->createMember(['vigente_hasta' => today()->subDay()]);
        $this->post(route('admin.prestamos.store'), [
            'libro_id' => $bookB->id,
            'cantidad' => 1,
            'socio_id' => $expired->id,
            'operacion_uuid' => (string) Str::uuid(),
        ])->assertSessionHasErrors('socio_id');

        $limited = $this->createMember();
        $this->createActiveLoan($limited, $bookA, $voluntario, null, 1);
        $this->createActiveLoan($limited, $this->createBookAndStock('Otro', 2), $voluntario);
        $this->post(route('admin.prestamos.store'), [
            'libro_id' => $bookC->id,
            'cantidad' => 1,
            'socio_id' => $limited->id,
            'operacion_uuid' => (string) Str::uuid(),
        ])->assertSessionHasErrors('cantidad');
    }

    public function test_us05_rejects_quantity_above_available_stock(): void
    {
        $voluntario = User::factory()->create();
        $socio = $this->createMember();
        $libro = $this->createBookAndStock('Stock insuficiente', 2);
        $this->createActiveLoan($this->createMember(), $libro, $voluntario, null, 1);

        $this->actingAs($voluntario)->from(route('admin.prestamos.index'))
            ->post(route('admin.prestamos.store'), [
                'libro_id' => $libro->id,
                'cantidad' => 2,
                'socio_id' => $socio->id,
                'operacion_uuid' => (string) Str::uuid(),
            ])->assertSessionHasErrors('cantidad');
        $this->assertSame(1, $libro->fresh()->cantidad_disponible);
    }

    public function test_us06_return_closes_loan_and_records_late_days(): void
    {
        $voluntario = User::factory()->create();
        $libro = $this->createBookAndStock('Atrasado', 3);
        $prestamo = $this->createActiveLoan($this->createMember(), $libro, $voluntario, today()->subDays(3), 3);

        $this->actingAs($voluntario)->from(route('admin.prestamos.index'))
            ->post(route('admin.prestamos.devolver', $prestamo), ['cantidad' => 1])
            ->assertSessionHas('status', 'Devolución registrada. Quedan 2 unidades pendientes. El préstamo tenía 3 días de atraso.');
        $this->assertDatabaseHas('prestamos', ['id' => $prestamo->id, 'estado' => 'activo', 'cantidad_devuelta' => 1]);
        $this->post(route('admin.prestamos.devolver', $prestamo), ['cantidad' => 2])
            ->assertSessionHas('status', 'Devolución registrada. El préstamo quedó completo. El préstamo tenía 3 días de atraso.');
        $this->assertDatabaseHas('prestamos', [
            'id' => $prestamo->id,
            'estado' => 'devuelto',
            'cantidad_devuelta' => 3,
            'devuelto_por_user_id' => $voluntario->id,
        ]);
        $this->assertSame(3, $libro->fresh()->cantidad_disponible);
    }

    public function test_us06_cannot_return_more_units_than_the_loan_has_pending(): void
    {
        $voluntario = User::factory()->create();
        $libro = $this->createBookAndStock('Préstamo');
        $prestamo = $this->createActiveLoan($this->createMember(), $libro, $voluntario);

        $this->actingAs($voluntario)->from(route('admin.prestamos.index'))
            ->post(route('admin.prestamos.devolver', $prestamo), ['cantidad' => 2])
            ->assertSessionHasErrors('cantidad');
        $this->assertDatabaseHas('prestamos', ['id' => $prestamo->id, 'estado' => 'activo', 'cantidad_devuelta' => 0]);
    }

    public function test_us07_morosity_page_lists_only_overdue_active_loans(): void
    {
        $voluntario = User::factory()->create();
        $libro = $this->createBookAndStock('Vencido', 2);
        $this->createActiveLoan($this->createMember(), $libro, $voluntario, today()->subDay(), 2);

        $this->actingAs($voluntario)->get(route('admin.morosidad'))
            ->assertOk()->assertSee('Vencido')->assertSee('1');
    }

    public function test_us08_public_catalog_exposes_inventory_counts_without_personal_data(): void
    {
        $libro = $this->createBookAndStock('Catálogo público', 4);
        $this->createMember(['nombres' => 'Dato privado']);

        $response = $this->getJson('/api/catalogo?q=Catálogo');
        $response->assertOk()
            ->assertJsonPath('datos.0.titulo', $libro->titulo)
            ->assertJsonPath('datos.0.cantidad_total', 4)
            ->assertJsonPath('datos.0.cantidad_disponible', 4)
            ->assertJsonMissingPath('datos.0.socio')
            ->assertJsonMissingPath('datos.0.prestamos');
        $this->assertStringNotContainsString('Dato privado', $response->getContent());
        $this->getJson('/api/catalogo?pagina=0')->assertStatus(422)
            ->assertJsonStructure(['mensaje', 'errores']);
    }

    public function test_us09_announcements_are_public_only_during_their_date_window(): void
    {
        $user = User::factory()->create();
        Anuncio::create([
            'titulo' => 'Anuncio vigente',
            'cuerpo' => 'Información pública',
            'publicar_desde' => today()->subDay(),
            'publicar_hasta' => today()->addDay(),
            'user_id' => $user->id,
        ]);
        Anuncio::create([
            'titulo' => 'Anuncio futuro',
            'cuerpo' => 'Todavía no',
            'publicar_desde' => today()->addDay(),
            'user_id' => $user->id,
        ]);

        $this->get('/')->assertOk()->assertSee('Anuncio vigente')->assertDontSee('Anuncio futuro');
    }

    public function test_us10_reminder_email_is_sent_and_recorded_only_once(): void
    {
        Mail::fake();
        $voluntario = User::factory()->create();
        $socio = $this->createMember(['email' => 'lector@example.cl']);
        $libro = $this->createBookAndStock('Recordatorio');
        $this->createActiveLoan($socio, $libro, $voluntario, today()->addDays(2));

        $this->artisan('biblioteca:enviar-recordatorios')->assertSuccessful();
        $this->artisan('biblioteca:enviar-recordatorios')->assertSuccessful();

        Mail::assertSentTimes(RecordatorioVencimiento::class, 1);
        $this->assertDatabaseCount('recordatorios', 1);
    }

    public function test_admin_views_render_for_authenticated_volunteer(): void
    {
        $voluntario = User::factory()->create();
        $libro = $this->createBookAndStock('Vistas');
        $socio = $this->createMember();
        $this->createActiveLoan($socio, $libro, $voluntario);
        $socio->update(['user_id' => $voluntario->id]);
        Anuncio::create([
            'titulo' => 'Aviso',
            'cuerpo' => 'Texto',
            'publicar_desde' => today(),
            'user_id' => $voluntario->id,
        ]);

        $this->actingAs($voluntario);
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Últimos 12 meses')
            ->assertSee('Vistas')
            ->assertSee('2026-10');
        $this->get(route('admin.prestamos.index'))
            ->assertOk()
            ->assertSee('Registrado por')
            ->assertSee($voluntario->name);
        $this->get(route('admin.socios.index'))
            ->assertOk()
            ->assertSee('Registrado por')
            ->assertSee($voluntario->name);
        foreach ([
            route('admin.dashboard'),
            route('admin.estadisticas'),
            route('admin.libros.index'),
            route('admin.libros.create'),
            route('admin.libros.show', $libro),
            route('admin.libros.edit', $libro),
            route('admin.socios.index'),
            route('admin.socios.create'),
            route('admin.socios.show', $socio),
            route('admin.socios.edit', $socio),
            route('admin.prestamos.index'),
            route('admin.morosidad'),
            route('admin.anuncios.index'),
            route('admin.anuncios.create'),
            route('admin.anuncios.edit', Anuncio::first()),
            route('admin.muebles.index'),
            route('admin.estanterias.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_catalog_import_handles_excel_csv_and_reports_rejected_rows(): void
    {
        $csv = tempnam(sys_get_temp_dir(), 'insectaria-csv-');
        $report = tempnam(sys_get_temp_dir(), 'insectaria-report-');
        file_put_contents($csv, "\xEF\xBB\xBFtitulo,autor,cantidad,mueble\nLibro,Autora,4,Sala infantil\nOtro libro,Otro autor,-2,Sala infantil\n");

        try {
            $this->artisan('biblioteca:importar-catalogo', ['archivo' => $csv, '--reporte' => $report])
                ->expectsOutputToContain('Filas importadas: 1. Filas rechazadas: 1.')
                ->assertExitCode(1);
            $this->assertDatabaseCount('libros', 1);
            $this->assertDatabaseHas('libros', ['titulo' => 'Libro', 'cantidad_total' => 4]);
            $this->assertStringContainsString('al menos 0', file_get_contents($report));
        } finally {
            unlink($csv);
            unlink($report);
        }
    }

    private function createBookAndStock(string $title, int $quantity = 1): Libro
    {
        return Libro::create([
            'titulo' => $title,
            'autor' => 'Autora de prueba',
            'cantidad_total' => $quantity,
        ]);
    }

    private function createMember(array $overrides = []): Socio
    {
        return Socio::create(array_merge([
            'run' => (string) random_int(10000000, 99999999).'K',
            'nombres' => 'Persona',
            'apellidos' => 'Lectora',
            'telefono' => '+56 9 1234 5678',
            'email' => null,
            'comuna' => 'Concepción',
            'ocupacion' => null,
            'fecha_inscripcion' => today(),
            'vigente_hasta' => today()->addYear(),
            'activo' => true,
        ], $overrides));
    }

    private function createActiveLoan(Socio $socio, Libro $libro, User $voluntario, $dueDate = null, int $quantity = 1): Prestamo
    {
        return Prestamo::create([
            'socio_id' => $socio->id,
            'libro_id' => $libro->id,
            'cantidad' => $quantity,
            'cantidad_devuelta' => 0,
            'user_id' => $voluntario->id,
            'fecha_prestamo' => now(),
            'fecha_vencimiento' => $dueDate ?? today()->addDays(14),
            'estado' => 'activo',
            'operacion_uuid' => (string) Str::uuid(),
        ]);
    }
}
