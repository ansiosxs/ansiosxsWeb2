<?php

namespace Tests\Feature;

use App\Mail\RecordatorioVencimiento;
use App\Models\Anuncio;
use App\Models\ConfiguracionRecordatorio;
use App\Models\Libro;
use App\Models\Mueble;
use App\Models\Prestamo;
use App\Models\Socio;
use App\Models\User;
use App\Models\UserActionLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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
        $this->post('/login', ['login' => $user->email, 'password' => 'ClaveSegura123!'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_volunteer_can_log_in_with_name_or_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Camila Voluntaria',
            'email' => 'camila@example.cl',
            'password' => 'ClaveSegura123!',
        ]);

        $this->post('/login', ['login' => $user->name, 'password' => 'ClaveSegura123!'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('user_action_logs', [
            'user_id' => $user->id,
            'action' => 'POST login.store',
        ]);

        $this->post('/logout');
        $this->post('/login', ['login' => $user->email, 'password' => 'ClaveSegura123!'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_creates_volunteer_with_name_and_provisional_password_only(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.create'))
            ->assertOk()
            ->assertSee('Contraseña provisional')
            ->assertDontSee('name="email"', false)
            ->assertDontSee('name="role"', false);

        $this->post(route('admin.usuarios.store'), [
            'name' => 'Camila Nueva',
            'password' => 'Provisional123!',
        ])->assertRedirect(route('admin.usuarios.index'));

        $user = User::query()->where('name', 'Camila Nueva')->firstOrFail();
        $this->assertNull($user->email);
        $this->assertSame(User::ROLE_VOLUNTARIO, $user->role);
        $this->assertTrue($user->activo);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('Provisional123!', $user->password));
    }

    public function test_provisional_login_requires_email_and_a_different_permanent_password(): void
    {
        $user = User::factory()->create([
            'name' => 'Camila Nueva',
            'email' => null,
            'password' => 'Provisional123!',
            'must_change_password' => true,
        ]);

        $this->post(route('login.store'), ['login' => $user->name, 'password' => 'Provisional123!'])
            ->assertRedirect(route('account.setup'));
        $this->assertAuthenticatedAs($user);
        $this->get(route('admin.dashboard'))->assertRedirect(route('account.setup'));
        $this->get(route('account.setup'))->assertOk()
            ->assertSee('Completa tu cuenta')
            ->assertSee('name="email"', false);

        $this->from(route('account.setup'))->post(route('account.setup.store'), [
            'email' => 'camila@localhost',
            'password' => 'Definitiva123!',
            'password_confirmation' => 'Definitiva123!',
        ])->assertSessionHasErrors('email');
        $this->assertNull($user->fresh()->email);

        $this->from(route('account.setup'))->post(route('account.setup.store'), [
            'email' => 'camila@example.cl',
            'password' => 'Provisional123!',
            'password_confirmation' => 'Provisional123!',
        ])->assertSessionHasErrors('password');
        $this->assertNull($user->fresh()->email);
        $this->assertTrue($user->fresh()->must_change_password);

        $this->post(route('account.setup.store'), [
            'email' => 'camila@example.cl',
            'password' => 'Definitiva123!',
            'password_confirmation' => 'Definitiva123!',
        ])->assertRedirect(route('admin.dashboard'));

        $user->refresh();
        $this->assertSame('camila@example.cl', $user->email);
        $this->assertFalse($user->must_change_password);
        $this->get(route('admin.dashboard'))->assertOk();

        $this->post(route('logout'));
        $this->post(route('login.store'), ['login' => $user->email, 'password' => 'Provisional123!'])
            ->assertSessionHasErrors('login');
        $this->post(route('login.store'), ['login' => $user->email, 'password' => 'Definitiva123!'])
            ->assertRedirect(route('admin.dashboard'));
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
            $this->from('/login')->post('/login', ['login' => 'alguien@example.cl', 'password' => 'incorrecta'])
                ->assertSessionHasErrors(['login' => 'Las credenciales ingresadas no son correctas.']);
        }

        $this->from('/login')->post('/login', ['login' => 'alguien@example.cl', 'password' => 'incorrecta'])
            ->assertSessionHasErrors(['login' => 'Demasiados intentos. Intenta nuevamente en un minuto.']);
    }

    public function test_volunteer_command_can_generate_phpmyadmin_sql_without_creating_local_account(): void
    {
        $this->artisan('biblioteca:crear-voluntario', ['--sql' => true])
            ->expectsQuestion('Nombre', "Ada O'Neil")
            ->expectsQuestion('Contraseña provisional (mínimo 12 caracteres)', 'ClaveSegura123!')
            ->expectsOutputToContain('`must_change_password`)')
            ->expectsOutputToContain("'Ada O''Neil',")
            ->expectsOutputToContain('    NULL,')
            ->expectsOutputToContain('    1')
            ->expectsOutputToContain('No se creó una cuenta en esta base local.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_volunteer_command_creates_a_pending_account_with_only_name_and_provisional_password(): void
    {
        $this->artisan('biblioteca:crear-voluntario')
            ->expectsQuestion('Nombre', 'Ada Lovelace')
            ->expectsQuestion('Contraseña provisional (mínimo 12 caracteres)', 'ClaveSegura123!')
            ->expectsOutputToContain('Cuenta voluntaria creada.')
            ->assertExitCode(0);

        $user = User::query()->where('name', 'Ada Lovelace')->firstOrFail();
        $this->assertNull($user->email);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('ClaveSegura123!', $user->password));
    }

    public function test_admin_cannot_delete_user_with_history_and_is_told_to_deactivate_instead(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $voluntario = User::factory()->create();
        $socio = $this->createMember();
        $libro = $this->createBookAndStock('Libro con historial de usuario');
        $this->createActiveLoan($socio, $libro, $voluntario);

        $this->actingAs($admin)
            ->from(route('admin.usuarios.index'))
            ->delete(route('admin.usuarios.destroy', $voluntario))
            ->assertRedirect(route('admin.usuarios.index'))
            ->assertSessionHasErrors('usuario');

        $this->assertDatabaseHas('users', ['id' => $voluntario->id, 'activo' => true]);
        $this->assertDatabaseHas('prestamos', ['user_id' => $voluntario->id]);
    }

    public function test_admin_can_delete_user_and_its_action_logs(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $voluntario = User::factory()->create();

        UserActionLog::create([
            'user_id' => $voluntario->id,
            'actor_id' => $admin->id,
            'action' => 'POST admin.socios.store',
        ]);
        UserActionLog::create([
            'user_id' => $admin->id,
            'actor_id' => $voluntario->id,
            'action' => 'POST admin.libros.store',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.usuarios.destroy', $voluntario))
            ->assertRedirect(route('admin.usuarios.index'))
            ->assertSessionHas('status', 'Usuario eliminado correctamente.');

        $this->assertDatabaseMissing('users', ['id' => $voluntario->id]);
        $this->assertDatabaseMissing('user_action_logs', [
            'user_id' => $voluntario->id,
        ]);
        $this->assertDatabaseMissing('user_action_logs', [
            'actor_id' => $voluntario->id,
        ]);
    }

    public function test_volunteer_cannot_delete_books_and_admin_cannot_delete_while_loan_is_active(): void
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
            ->assertForbidden();
        $this->assertDatabaseHas('libros', ['id' => $libro->id, 'deleted_at' => null]);

        $administrador = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($administrador)->from(route('admin.libros.show', $libro))
            ->delete(route('admin.libros.destroy', $libro))
            ->assertSessionHasErrors('libro');
        $this->assertDatabaseHas('libros', ['id' => $libro->id, 'deleted_at' => null]);
    }

    public function test_admin_can_export_csv_inventory_and_volunteer_cannot_access_it(): void
    {
        $voluntario = User::factory()->create();
        $administrador = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $libro = $this->createBookAndStock('Libro exportable');
        $socio = $this->createMember();
        $this->createActiveLoan($socio, $libro, $voluntario);

        $this->actingAs($voluntario)->get(route('admin.exportar.libros'))->assertForbidden();
        $this->actingAs($voluntario)->get(route('admin.exportar.socios'))->assertForbidden();
        $this->actingAs($voluntario)->get(route('admin.exportar.prestamos'))->assertForbidden();
        $this->actingAs($voluntario)->get(route('admin.exportar.todo'))->assertForbidden();

        $this->actingAs($administrador)
            ->get(route('admin.exportar.libros'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename=inventario-libros.csv');

        $this->actingAs($administrador)
            ->get(route('admin.exportar.socios'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename=socios.csv');

        $this->actingAs($administrador)
            ->get(route('admin.exportar.prestamos'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename=prestamos.csv');

        $this->actingAs($administrador)
            ->get(route('admin.exportar.todo'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename=biblioteca-export.csv');
    }

    public function test_book_form_offers_isbn_scanning(): void
    {
        $voluntario = User::factory()->create();

        $this->actingAs($voluntario)->get(route('admin.libros.create'))
            ->assertOk()
            ->assertSee('data-isbn-form', false)
            ->assertSee('data-isbn-escanear', false)
            ->assertSee('data-isbn-input', false)
            ->assertSee('data-isbn-video', false)
            ->assertSee('Escanear ISBN')
            ->assertSee('Imagen')
            ->assertSee('data-book-image-url', false)
            ->assertSee('data-book-image-file', false)
            ->assertSee('data-book-image-preview', false);
    }

    public function test_book_metadata_can_be_saved_and_is_shown_on_admin_book_detail(): void
    {
        $voluntario = User::factory()->create();

        $this->actingAs($voluntario)->post(route('admin.libros.store'), [
            'titulo' => 'Libro con ficha completa',
            'autor' => 'Autora',
            'cantidad_total' => 2,
            'anio' => 2024,
            'paginas' => 280,
            'idioma' => 'Español',
            'descripcion' => 'Una descripción completa.',
            'portada_url' => 'https://example.com/portada.jpg',
        ])->assertRedirect();

        $libro = Libro::query()->where('titulo', 'Libro con ficha completa')->firstOrFail();
        $this->assertDatabaseHas('libros', [
            'id' => $libro->id,
            'anio' => 2024,
            'paginas' => 280,
            'idioma' => 'Español',
            'descripcion' => 'Una descripción completa.',
        ]);

        $this->get(route('admin.libros.show', $libro))
            ->assertOk()
            ->assertSee('https://example.com/portada.jpg')
            ->assertSee('Portada de Libro con ficha completa')
            ->assertSee('Año de publicación:')
            ->assertSee('2024')
            ->assertSee('280')
            ->assertSee('Español')
            ->assertSee('Una descripción completa.');

        $this->get(route('admin.libros.index'))
            ->assertOk()
            ->assertDontSee('2024')
            ->assertDontSee('Español')
            ->assertDontSee('Una descripción completa.');
    }

    public function test_book_cover_can_be_set_by_url_or_uploaded_file(): void
    {
        Storage::fake('public');
        $voluntario = User::factory()->create();

        $this->actingAs($voluntario)->post(route('admin.libros.store'), [
            'titulo' => 'Portada por URL',
            'autor' => 'Autora',
            'cantidad_total' => 1,
            'portada_url' => 'https://example.com/portada.jpg',
        ])->assertRedirect();

        $libro = Libro::query()->where('titulo', 'Portada por URL')->firstOrFail();
        $this->assertSame('https://example.com/portada.jpg', $libro->portada_url);

        $image = UploadedFile::fake()->image('portada.png');
        $this->put(route('admin.libros.update', $libro), [
            'titulo' => $libro->titulo,
            'autor' => $libro->autor,
            'cantidad_total' => $libro->cantidad_total,
            'portada' => $image,
        ])->assertRedirect(route('admin.libros.show', $libro));

        $path = 'book-covers/'.$image->hashName();
        Storage::disk('public')->assertExists($path);
        $this->assertSame(url(Storage::disk('public')->url($path)), $libro->fresh()->portada_url);

        $libro->refresh();
        $this->put(route('admin.libros.update', $libro), [
            'titulo' => $libro->titulo,
            'autor' => $libro->autor,
            'cantidad_total' => $libro->cantidad_total,
            'portada_url' => $libro->portada_url,
        ])->assertRedirect(route('admin.libros.show', $libro));
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

        $administrador = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($administrador)->delete(route('admin.libros.destroy', $libro))
            ->assertRedirect(route('admin.libros.index'));
        $this->assertSoftDeleted('libros', ['id' => $libro->id]);
    }

    public function test_volunteer_cannot_manage_users_delete_members_or_change_announcements_and_shelves(): void
    {
        $voluntario = User::factory()->create();
        $socio = $this->createMember();
        $libro = $this->createBookAndStock('Libro protegido');
        $mueble = Mueble::create(['nombre' => 'Estantería protegida']);
        $anuncio = Anuncio::create([
            'titulo' => 'Anuncio protegido',
            'cuerpo' => 'Contenido',
            'publicar_desde' => today(),
            'user_id' => $voluntario->id,
        ]);

        $this->actingAs($voluntario)
            ->get(route('admin.usuarios.index'))
            ->assertForbidden();
        $this->get(route('admin.logs.index'))->assertForbidden();
        $this->delete(route('admin.libros.destroy', $libro))->assertForbidden();
        $this->delete(route('admin.socios.destroy', $socio))->assertForbidden();
        $this->get(route('admin.anuncios.create'))->assertForbidden();
        $this->post(route('admin.anuncios.store'), [
            'titulo' => 'Intento voluntario',
            'cuerpo' => 'Contenido',
            'publicar_desde' => today()->format('Y-m-d'),
        ])->assertForbidden();
        $this->put(route('admin.anuncios.update', $anuncio), [
            'titulo' => 'Modificación voluntaria',
            'cuerpo' => 'Contenido',
            'publicar_desde' => today()->format('Y-m-d'),
        ])->assertForbidden();
        $this->delete(route('admin.anuncios.destroy', $anuncio))->assertForbidden();
        $this->post(route('admin.estanterias.store'), ['nombre' => 'Nueva estantería'])->assertForbidden();
        $this->put(route('admin.estanterias.update', $mueble), ['nombre' => 'Modificación voluntaria'])->assertForbidden();
        $this->delete(route('admin.muebles.destroy', $mueble))->assertForbidden();

        $this->assertDatabaseHas('socios', ['id' => $socio->id, 'activo' => true]);
        $this->assertDatabaseHas('libros', ['id' => $libro->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('anuncios', ['id' => $anuncio->id, 'titulo' => 'Anuncio protegido']);
        $this->assertDatabaseHas('muebles', ['id' => $mueble->id, 'nombre' => 'Estantería protegida']);
        $this->assertDatabaseMissing('muebles', ['nombre' => 'Nueva estantería']);
    }

    public function test_action_log_omits_queries_and_records_session_changes(): void
    {
        $voluntario = User::factory()->create();
        $search = 'dato-personal-que-no-debe-guardarse';

        $this->actingAs($voluntario)
            ->get(route('admin.socios.index', ['q' => $search]))
            ->assertOk();

        $this->assertDatabaseMissing('user_action_logs', [
            'user_id' => $voluntario->id,
            'action' => 'GET admin.socios.index',
        ]);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertDatabaseHas('user_action_logs', [
            'user_id' => $voluntario->id,
            'action' => 'POST logout',
        ]);
    }

    public function test_action_log_displays_plain_language_descriptions(): void
    {
        $voluntario = User::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($voluntario)->post(route('admin.libros.store'), [
            'titulo' => 'Libro en bitácora',
            'autor' => 'Autora',
            'cantidad_total' => 1,
        ])->assertRedirect();

        UserActionLog::query()->create([
            'user_id' => $voluntario->id,
            'actor_id' => $voluntario->id,
            'action' => 'GET admin.socios.index',
            'resource_type' => 'SocioController',
            'details' => ['route' => 'admin.socios.index', 'method' => 'GET'],
        ]);

        $this->actingAs($admin)->get(route('admin.logs.index'))
            ->assertOk()
            ->assertSee('Quién')
            ->assertSee('Qué hizo')
            ->assertSee('Elemento relacionado')
            ->assertSee('Registró un libro')
            ->assertSee('Libro')
            ->assertDontSee('Consultó la lista de socias y socios')
            ->assertDontSee('GET admin.libros.index')
            ->assertDontSee('IP');
    }

    public function test_action_log_records_successful_creates_edits_and_admin_deletions(): void
    {
        $voluntario = User::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($voluntario)->post(route('admin.libros.store'), [
            'titulo' => 'Libro para auditar',
            'autor' => 'Autora',
            'cantidad_total' => 1,
        ])->assertRedirect();

        $libro = Libro::query()->where('titulo', 'Libro para auditar')->firstOrFail();
        $this->assertDatabaseHas('user_action_logs', [
            'user_id' => $voluntario->id,
            'action' => 'POST admin.libros.store',
        ]);
        $this->assertSame(
            'Registró un libro',
            UserActionLog::query()->latest('id')->firstOrFail()->accion_legible,
        );

        $this->put(route('admin.libros.update', $libro), [
            'titulo' => 'Libro auditado actualizado',
            'autor' => 'Autora',
            'cantidad_total' => 1,
        ])->assertRedirect();

        $this->assertSame(
            'Actualizó un libro',
            UserActionLog::query()->latest('id')->firstOrFail()->accion_legible,
        );

        $this->actingAs($admin)->delete(route('admin.libros.destroy', $libro))->assertRedirect();

        $this->assertDatabaseHas('user_action_logs', [
            'user_id' => $admin->id,
            'action' => 'DELETE admin.libros.destroy',
        ]);
        $this->get(route('admin.logs.index'))
            ->assertOk()
            ->assertSee('Eliminó un libro');
    }

    public function test_failed_actions_are_not_recorded_as_completed_actions(): void
    {
        $voluntario = User::factory()->create();

        $this->actingAs($voluntario)->from(route('admin.libros.create'))
            ->post(route('admin.libros.store'), ['titulo' => '', 'autor' => 'Autora'])
            ->assertSessionHasErrors('titulo');

        $this->assertDatabaseMissing('user_action_logs', [
            'user_id' => $voluntario->id,
            'action' => 'POST admin.libros.store',
        ]);
    }

    public function test_last_active_admin_cannot_be_demoted_or_deactivated(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->from(route('admin.usuarios.edit', $admin))
            ->put(route('admin.usuarios.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => User::ROLE_VOLUNTARIO,
                'activo' => false,
            ])
            ->assertRedirect(route('admin.usuarios.edit', $admin))
            ->assertSessionHasErrors('usuario');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => User::ROLE_ADMIN,
            'activo' => true,
        ]);
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

        $response = $this->actingAs($voluntario)->postJson(route('admin.prestamos.store'), $payload);
        $response
            ->assertOk()
            ->assertJsonPath('mensaje', 'Préstamo ingresado correctamente. Vence el 30/10/2026.')
            ->assertJsonPath('redireccion', route('admin.prestamos.index'))
            ->assertSessionHas('status', 'Préstamo ingresado correctamente. Vence el 30/10/2026.');
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

    public function test_loan_registration_shows_success_flash_message(): void
    {
        $voluntario = User::factory()->create();
        $socio = $this->createMember();
        $libro = $this->createBookAndStock('Una lectura');

        $this->actingAs($voluntario)->post(route('admin.prestamos.store'), [
            'libro_id' => $libro->id,
            'cantidad' => 1,
            'socio_id' => $socio->id,
            'operacion_uuid' => (string) Str::uuid(),
        ])->assertRedirect(route('admin.prestamos.index'))
            ->assertSessionHas('status', 'Préstamo ingresado correctamente. Vence el 30/10/2026.');

        $this->get(route('admin.prestamos.index'))
            ->assertOk()
            ->assertSee('Préstamo ingresado correctamente. Vence el 30/10/2026.');
    }

    public function test_active_loan_list_can_be_searched_by_book_title_or_member_name(): void
    {
        $voluntario = User::factory()->create();
        $ana = $this->createMember(['nombres' => 'Ana', 'apellidos' => 'Lectora']);
        $bruno = $this->createMember(['nombres' => 'Bruno', 'apellidos' => 'Socio']);
        $book = $this->createBookAndStock('El libro escondido');
        $otherBook = $this->createBookAndStock('Otra lectura');
        $this->createActiveLoan($ana, $book, $voluntario);
        $this->createActiveLoan($bruno, $otherBook, $voluntario);

        $this->actingAs($voluntario)->get(route('admin.prestamos.index', ['q' => 'libro escondido']))
            ->assertOk()
            ->assertSee('El libro escondido')
            ->assertDontSee('Otra lectura')
            ->assertSee('name="q"', false)
            ->assertSee('value="libro escondido"', false);

        $this->get(route('admin.prestamos.index', ['q' => 'Ana Lectora']))
            ->assertOk()
            ->assertSee('El libro escondido')
            ->assertDontSee('Otra lectura');
    }

    public function test_active_loan_search_updates_rows_and_pagination_asynchronously(): void
    {
        $voluntario = User::factory()->create();
        $socio = $this->createMember(['nombres' => 'Ana', 'apellidos' => 'Lectora']);
        $book = $this->createBookAndStock('El libro escondido');
        for ($index = 0; $index < 26; $index++) {
            $this->createActiveLoan($socio, $book, $voluntario);
        }

        $this->actingAs($voluntario)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('admin.prestamos.index', ['q' => 'Ana']))
            ->assertOk()
            ->assertJsonStructure(['rows', 'pagination'])
            ->assertJsonPath('rows', fn (string $rows) => str_contains($rows, 'El libro escondido'))
            ->assertJsonPath('pagination', fn (string $pagination) => str_contains($pagination, 'page=2'));
    }

    public function test_active_loan_can_be_edited_and_deleted_from_the_loan_table(): void
    {
        $voluntario = User::factory()->create();
        $socio = $this->createMember();
        $book = $this->createBookAndStock('Libro editable', 2);
        $loan = $this->createActiveLoan($socio, $book, $voluntario);

        $this->actingAs($voluntario)
            ->get(route('admin.prestamos.index', ['q' => 'Libro']))
            ->assertOk()
            ->assertSee('data-busqueda-viva', false)
            ->assertSee('Editar')
            ->assertSee('Eliminar')
            ->assertSee('data-confirm="¿Eliminar definitivamente el registro de este préstamo?"', false);

        $this->put(route('admin.prestamos.update', $loan), [
            'cantidad' => 1,
            'cantidad_devuelta' => 0,
            'fecha_prestamo' => today()->format('Y-m-d'),
            'fecha_vencimiento' => today()->addDays(14)->format('Y-m-d'),
            'volver' => 'prestamos',
            'q' => 'Libro',
        ])->assertRedirect(route('admin.prestamos.index', ['q' => 'Libro']));

        $this->delete(route('admin.prestamos.destroy', $loan), ['volver' => 'prestamos', 'q' => 'Libro'])
            ->assertRedirect(route('admin.prestamos.index', ['q' => 'Libro']))
            ->assertSessionHas('status', 'El registro del préstamo fue eliminado.');

        $this->assertDatabaseMissing('prestamos', ['id' => $loan->id]);
        $this->assertSame(2, $book->fresh()->cantidad_disponible);
    }

    public function test_member_profile_requires_confirmation_before_deleting_a_loan_record(): void
    {
        $voluntario = User::factory()->create();
        $socio = $this->createMember();
        $book = $this->createBookAndStock('Libro histórico');
        $loan = $this->createActiveLoan($socio, $book, $voluntario);

        $this->actingAs($voluntario)
            ->get(route('admin.socios.show', $socio))
            ->assertOk()
            ->assertSee('Eliminar registro')
            ->assertSee('Puede afectar el historial del socio y la disponibilidad del libro.');

        $this->delete(route('admin.prestamos.destroy', $loan), ['volver' => 'socio'])
            ->assertRedirect(route('admin.socios.show', $socio));
        $this->assertDatabaseMissing('prestamos', ['id' => $loan->id]);
    }

    public function test_loan_search_returns_book_details_and_available_quantity(): void
    {
        $voluntario = User::factory()->create();
        $book = Libro::create([
            'titulo' => 'La biblioteca secreta',
            'autor' => 'Autora de prueba',
            'isbn' => '978-0-000000-00-1',
            'editorial' => 'Insectaria',
            'anio' => 2024,
            'cantidad_total' => 4,
        ]);
        $this->createActiveLoan($this->createMember(), $book, $voluntario, null, 1);
        $this->createBookAndStock('La biblioteca agotada', 0);

        $this->actingAs($voluntario)->get(route('admin.prestamos.index'))
            ->assertOk()
            ->assertSee('data-libro-escanear', false)
            ->assertSee('data-isbn-video', false)
            ->assertSee('título, autor o ISBN');

        $this->actingAs($voluntario)->getJson(route('admin.prestamos.libros', ['q' => 'La biblioteca']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $book->id)
            ->assertJsonPath('0.autor', 'Autora de prueba')
            ->assertJsonPath('0.editorial', 'Insectaria')
            ->assertJsonPath('0.cantidad_disponible', 3);

        $this->getJson(route('admin.prestamos.libros', ['q' => '9780000000001']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $book->id)
            ->assertJsonPath('0.isbn', '978-0-000000-00-1');
    }

    public function test_loan_form_can_search_members_by_name_or_run(): void
    {
        $voluntario = User::factory()->create();
        $socio = $this->createMember([
            'nombres' => 'María',
            'apellidos' => 'Lectora',
            'run' => '12345678-5',
        ]);

        $this->actingAs($voluntario)->get(route('admin.prestamos.index'))
            ->assertOk()
            ->assertSee('Buscar socio por nombre o RUT')
            ->assertSee('data-socio-busqueda', false)
            ->assertSee('data-socios-select', false)
            ->assertSee('data-socio-nombre="María Lectora"', false)
            ->assertSee('data-socio-run="12345678-5"', false)
            ->assertSee('Lectora, María — 12345678-5');
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

    public function test_admin_can_open_an_editable_mailto_draft_for_an_overdue_loan(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $voluntario = User::factory()->create();
        $socio = $this->createMember([
            'nombres' => 'Ana',
            'apellidos' => 'Lectora',
            'email' => 'ana@example.cl',
        ]);
        $libro = $this->createBookAndStock('Libro atrasado');
        $prestamo = $this->createActiveLoan($socio, $libro, $voluntario, today()->subDays(3), 1);

        $this->actingAs($admin)->get(route('admin.morosidad'))
            ->assertOk()
            ->assertSee('Enviar correo')
            ->assertSee('Marcar como devuelto');

        $response = $this->from(route('admin.morosidad'))
            ->post(route('admin.prestamos.correo-morosidad', $prestamo));
        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('mailto:ana@example.cl?', $location);

        parse_str((string) parse_url($location, PHP_URL_QUERY), $emailFields);
        $this->assertSame('Aviso por préstamo vencido - Biblioteca La Insectaria', $emailFields['subject']);
        $this->assertStringContainsString('Hola Ana Lectora', $emailFields['body']);
        $this->assertStringContainsString('Libro: Libro atrasado', $emailFields['body']);
        $this->assertStringContainsString('Unidades pendientes: 1', $emailFields['body']);
        $this->assertStringEndsWith('Si crees que es un error, responde este correo.', $emailFields['body']);
    }

    public function test_admin_cannot_open_overdue_email_draft_when_member_email_is_invalid(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $voluntario = User::factory()->create();
        $socio = $this->createMember(['email' => 'correo-invalido']);
        $prestamo = $this->createActiveLoan(
            $socio,
            $this->createBookAndStock('Libro atrasado'),
            $voluntario,
            today()->subDay(),
        );

        $this->actingAs($admin)->from(route('admin.morosidad'))
            ->post(route('admin.prestamos.correo-morosidad', $prestamo))
            ->assertRedirect(route('admin.morosidad'))
            ->assertSessionHasErrors('email');
    }

    public function test_admin_can_mark_an_overdue_loan_as_returned(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $voluntario = User::factory()->create();
        $libro = $this->createBookAndStock('Libro devuelto', 2);
        $prestamo = $this->createActiveLoan(
            $this->createMember(),
            $libro,
            $voluntario,
            today()->subDay(),
        );

        $this->actingAs($admin)
            ->post(route('admin.prestamos.marcar-devuelto', $prestamo))
            ->assertRedirect(route('admin.morosidad'))
            ->assertSessionHas('status', 'El préstamo fue marcado como devuelto.');

        $this->assertDatabaseHas('prestamos', [
            'id' => $prestamo->id,
            'estado' => 'devuelto',
            'cantidad_devuelta' => 1,
            'devuelto_por_user_id' => $admin->id,
        ]);
        $this->assertSame(2, $libro->fresh()->cantidad_disponible);
        $this->actingAs($admin)->get(route('admin.morosidad'))->assertDontSee('Libro devuelto');
    }

    public function test_volunteer_cannot_see_or_use_overdue_management_actions(): void
    {
        $voluntario = User::factory()->create();
        $prestamo = $this->createActiveLoan(
            $this->createMember(['email' => 'ana@example.cl']),
            $this->createBookAndStock('Libro atrasado'),
            $voluntario,
            today()->subDay(),
        );

        $this->actingAs($voluntario)->get(route('admin.morosidad'))
            ->assertOk()
            ->assertDontSee('Enviar correo')
            ->assertDontSee('Marcar como devuelto');
        $this->post(route('admin.prestamos.correo-morosidad', $prestamo))->assertForbidden();
        $this->post(route('admin.prestamos.marcar-devuelto', $prestamo))->assertForbidden();
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
    }

    public function test_signed_in_catalog_displays_book_edit_links_but_guest_does_not(): void
    {
        $guestResponse = $this->get(route('catalogo'));
        $guestResponse->assertOk()->assertSee('data-edit-url=""', false);

        $voluntario = User::factory()->create();
        $authenticatedResponse = $this->actingAs($voluntario)->get(route('catalogo'));
        $authenticatedResponse->assertOk()
            ->assertSee(route('admin.libros.edit', ['libro' => '__LIBRO_ID__']), false);

        $this->get(route('admin.libros.edit', $this->createBookAndStock('Editar desde catálogo')))
            ->assertOk();
        $this->getJson('/api/catalogo?pagina=0')->assertStatus(422)
            ->assertJsonStructure(['mensaje', 'errores']);
    }

    public function test_public_catalog_returns_new_metadata_only_in_book_detail(): void
    {
        $libro = $this->createBookAndStock('Libro con metadatos', 3);
        $libro->update([
            'anio' => 2022,
            'paginas' => 190,
            'idioma' => 'Castellano',
            'descripcion' => 'Descripción para la ficha.',
            'portada_url' => 'https://example.com/metadatos.jpg',
        ]);

        $this->getJson('/api/catalogo?q=Libro con metadatos')
            ->assertOk()
            ->assertJsonPath('datos.0.titulo', $libro->titulo)
            ->assertJsonMissingPath('datos.0.anio')
            ->assertJsonMissingPath('datos.0.paginas')
            ->assertJsonMissingPath('datos.0.idioma')
            ->assertJsonMissingPath('datos.0.descripcion');

        $this->getJson('/api/catalogo/'.$libro->id)
            ->assertOk()
            ->assertJsonPath('anio', 2022)
            ->assertJsonPath('paginas', 190)
            ->assertJsonPath('idioma', 'Castellano')
            ->assertJsonPath('descripcion', 'Descripción para la ficha.')
            ->assertJsonPath('portada_url', 'https://example.com/metadatos.jpg');
    }

    public function test_public_catalog_can_search_by_isbn(): void
    {
        $book = $this->createBookAndStock('Libro con ISBN', 2);
        $book->update(['isbn' => '978-0-000000-12-3']);
        $this->createBookAndStock('Otro libro', 2);

        $this->getJson('/api/catalogo?q=9780000000123')
            ->assertOk()
            ->assertJsonCount(1, 'datos')
            ->assertJsonPath('datos.0.titulo', 'Libro con ISBN');
    }

    public function test_public_catalog_returns_only_one_page_at_a_time_for_incremental_loading(): void
    {
        for ($number = 1; $number <= 13; $number++) {
            $this->createBookAndStock(sprintf('Libro %02d', $number));
        }

        $firstPage = $this->getJson('/api/catalogo')
            ->assertOk()
            ->assertJsonPath('pagina', 1)
            ->assertJsonPath('total_paginas', 2)
            ->assertJsonCount(12, 'datos');

        $secondPage = $this->getJson('/api/catalogo?pagina=2')
            ->assertOk()
            ->assertJsonPath('pagina', 2)
            ->assertJsonPath('total_paginas', 2)
            ->assertJsonCount(1, 'datos')
            ->assertJsonPath('datos.0.titulo', 'Libro 13');

        $firstPageIds = array_column($firstPage->json('datos'), 'id');
        $secondPageIds = array_column($secondPage->json('datos'), 'id');
        $this->assertSame([], array_intersect($firstPageIds, $secondPageIds));
    }

    public function test_public_catalog_dashboard_shows_available_units_and_distinct_titles(): void
    {
        $firstBook = $this->createBookAndStock('Primer título', 5);
        $this->createBookAndStock('Segundo título', 4);
        $deletedBook = $this->createBookAndStock('Título eliminado', 20);
        $deletedBook->delete();

        $prestamo = $this->createActiveLoan($this->createMember(), $firstBook, User::factory()->create(), null, 3);
        $prestamo->update(['cantidad_devuelta' => 1]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Ejemplares disponibles')
            ->assertSee('Títulos en catálogo')
            ->assertSee('7')
            ->assertSee('2');
    }

    public function test_public_catalog_can_filter_books_by_furniture(): void
    {
        $prosa = Mueble::create(['nombre' => 'Novela en Prosa']);
        $grafica = Mueble::create(['nombre' => 'Novela Gráfica']);
        $prosaBook = $this->createBookAndStock('Libro en prosa');
        $prosaBook->update(['mueble_id' => $prosa->id]);
        $graphicBook = $this->createBookAndStock('Libro gráfico');
        $graphicBook->update(['mueble_id' => $grafica->id]);
        $this->createBookAndStock('Sin mueble');

        $this->getJson('/api/catalogo?mueble_id='.$prosa->id)
            ->assertOk()
            ->assertJsonPath('total_paginas', 1)
            ->assertJsonCount(1, 'datos')
            ->assertJsonPath('datos.0.titulo', 'Libro en prosa')
            ->assertJsonPath('datos.0.mueble', 'Novela en Prosa');

        $this->getJson('/api/catalogo?mueble_id='.$grafica->id)
            ->assertOk()
            ->assertJsonPath('datos.0.titulo', 'Libro gráfico');

        $this->getJson('/api/catalogo?mueble_id=999')
            ->assertStatus(422)
            ->assertJsonStructure(['mensaje', 'errores']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Novela en Prosa')
            ->assertSee('Novela Gráfica');
    }

    public function test_public_catalog_can_sort_by_title_or_author_in_both_directions(): void
    {
        $this->createCatalogBook('Zeta', 'Ana');
        $this->createCatalogBook('Beta', 'Zoe');
        $this->createCatalogBook('Alfa', 'Luis');
        $this->createCatalogBook('Libro sin autor', '')->update(['autor' => null]);

        $this->getJson('/api/catalogo')
            ->assertOk()
            ->assertJsonPath('datos.0.autor', 'Ana')
            ->assertJsonPath('datos.2.autor', 'Zoe');

        $this->getJson('/api/catalogo?orden=titulo_asc')
            ->assertOk()
            ->assertJsonPath('datos.0.titulo', 'Alfa')
            ->assertJsonPath('datos.3.titulo', 'Zeta');

        $this->getJson('/api/catalogo?orden=titulo_desc')
            ->assertOk()
            ->assertJsonPath('datos.0.titulo', 'Zeta')
            ->assertJsonPath('datos.3.titulo', 'Alfa');

        $this->getJson('/api/catalogo?orden=autor_asc')
            ->assertOk()
            ->assertJsonPath('datos.0.autor', 'Ana')
            ->assertJsonPath('datos.2.autor', 'Zoe')
            ->assertJsonPath('datos.3.titulo', 'Libro sin autor');

        $this->getJson('/api/catalogo?orden=autor_desc')
            ->assertOk()
            ->assertJsonPath('datos.0.autor', 'Zoe')
            ->assertJsonPath('datos.2.autor', 'Ana')
            ->assertJsonPath('datos.3.titulo', 'Libro sin autor');

        $this->getJson('/api/catalogo?orden=columna_privada')
            ->assertStatus(422)
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
        ConfiguracionRecordatorio::query()->findOrFail(1)->update([
            'automaticos_activos' => true,
            'preguntar_antes' => false,
        ]);
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
            ->assertSee('Unidades prestadas por mes')
            ->assertDontSee('Estadísticas')
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
            route('admin.muebles.index'),
            route('admin.estanterias.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get(route('admin.anuncios.create'))->assertForbidden();
        $this->get(route('admin.anuncios.edit', Anuncio::first()))->assertForbidden();
    }

    public function test_dashboard_can_select_only_years_with_loans(): void
    {
        $voluntario = User::factory()->create();
        $socio = $this->createMember();
        $libro = $this->createBookAndStock('Préstamos por año', 5);
        $loan2024 = $this->createActiveLoan($socio, $libro, $voluntario, null, 2);
        $loan2024->update(['fecha_prestamo' => '2024-03-15 10:00:00']);
        $loan2026 = $this->createActiveLoan($socio, $libro, $voluntario);
        $loan2026->update(['fecha_prestamo' => '2026-10-10 10:00:00']);

        $this->actingAs($voluntario)->get(route('admin.estadisticas', ['year' => 2024]))
            ->assertOk()
            ->assertSee('<option value="2024" selected>', false)
            ->assertSee('<option value="2026" >', false)
            ->assertDontSee('value="2025"', false)
            ->assertSee('2024-03: 2 unidades prestadas')
            ->assertDontSee('2026-10: 1 unidades prestadas');

        $this->get(route('admin.estadisticas', ['year' => 2025]))->assertSessionHasErrors('year');
    }

    public function test_dashboard_detail_lists_all_books_by_units_borrowed_descending(): void
    {
        $voluntario = User::factory()->create();
        $socio = $this->createMember();

        for ($quantity = 1; $quantity <= 6; $quantity++) {
            $book = $this->createBookAndStock('Libro '.$quantity, $quantity);
            $this->createActiveLoan($socio, $book, $voluntario, null, $quantity);
        }

        $this->actingAs($voluntario)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.estadisticas.libros-mas-prestados'), false);

        $this->get(route('admin.estadisticas.libros-mas-prestados'))
            ->assertOk()
            ->assertSee('Libro 6')
            ->assertSee('Libro 1')
            ->assertSeeInOrder(['Libro 6', 'Libro 5', 'Libro 4', 'Libro 3', 'Libro 2', 'Libro 1']);
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

    private function createCatalogBook(string $title, string $author): Libro
    {
        return Libro::create([
            'titulo' => $title,
            'autor' => $author,
            'cantidad_total' => 1,
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
