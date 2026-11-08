<?php

namespace Tests\Feature;

use App\Models\ConfiguracionGeneral;
use App\Models\Libro;
use App\Models\Socio;
use App\Models\User;
use App\Services\PrestamoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ConfiguracionGeneralTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_open_general_configuration(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);

        $this->actingAs($admin)->get(route('admin.configuracion.edit'))->assertOk();
        $this->actingAs($editor)->get(route('admin.configuracion.edit'))->assertForbidden();
    }

    public function test_configuration_can_be_saved_with_no_menu_links_or_statistics_selected(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->put(route('admin.configuracion.update'), [
            'plazo_prestamo_dias' => 21,
            'max_prestamos_activos' => 3,
            'permitir_membresia_vencida' => '0',
            'dias_recordatorio' => 0,
        ])->assertRedirect(route('admin.configuracion.edit'));

        $configuracion = ConfiguracionGeneral::actual();
        $this->assertSame(21, $configuracion->plazo_prestamo_dias);
        $this->assertSame(3, $configuracion->max_prestamos_activos);
        $this->assertFalse($configuracion->permitir_membresia_vencida);
        $this->assertSame(0, $configuracion->dias_recordatorio);
        $this->assertSame([], $configuracion->secciones_visibles['admin']);
        $this->assertSame([], $configuracion->secciones_visibles['editor']);
        $this->assertSame([], $configuracion->secciones_visibles['voluntario']);
        $this->assertSame([], $configuracion->estadisticas_dashboard);
    }

    public function test_menu_visibility_does_not_change_direct_route_permissions(): void
    {
        $voluntario = User::factory()->create(['role' => User::ROLE_VOLUNTARIO]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        ConfiguracionGeneral::actual()->update([
            'secciones_visibles' => ['voluntario' => ['dashboard']],
            'estadisticas_dashboard' => [],
        ]);

        $this->actingAs($voluntario)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Libros e inventario')
            ->assertDontSee('Socios con membresía vigente')
            ->assertDontSee('Libros más prestados')
            ->assertDontSee('Socios por comuna');
        $this->get(route('admin.libros.index'))->assertOk();

        ConfiguracionGeneral::actual()->update(['secciones_visibles' => ['admin' => []]]);
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.configuracion.edit'), false);
    }

    public function test_loan_duration_limit_and_expired_membership_follow_saved_settings(): void
    {
        $voluntario = User::factory()->create();
        $socio = Socio::query()->create([
            'run' => '12345678-9',
            'nombres' => 'Ada',
            'apellidos' => 'Lovelace',
            'telefono' => '+56912345678',
            'comuna' => 'Santiago',
            'fecha_inscripcion' => today()->subYear(),
            'vigente_hasta' => today()->subDay(),
            'activo' => true,
        ]);
        $primerLibro = Libro::query()->create(['titulo' => 'Libro uno', 'cantidad_total' => 2]);
        $segundoLibro = Libro::query()->create(['titulo' => 'Libro dos', 'cantidad_total' => 2]);
        $configuracion = ConfiguracionGeneral::actual();
        $service = app(PrestamoService::class);

        try {
            $service->prestar($primerLibro->id, 1, $socio->id, $voluntario, (string) Str::uuid());
            $this->fail('Un préstamo con membresía vencida debe rechazarse por defecto.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('socio_id', $exception->errors());
        }

        $configuracion->update([
            'plazo_prestamo_dias' => 21,
            'max_prestamos_activos' => 1,
            'permitir_membresia_vencida' => true,
        ]);
        $prestamo = $service->prestar($primerLibro->id, 1, $socio->id, $voluntario, (string) Str::uuid());
        $this->assertSame(today()->addDays(21)->toDateString(), $prestamo->fecha_vencimiento->toDateString());

        try {
            $service->prestar($segundoLibro->id, 1, $socio->id, $voluntario, (string) Str::uuid());
            $this->fail('El límite de unidades activas debe aplicarse.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('cantidad', $exception->errors());
        }
    }
}
