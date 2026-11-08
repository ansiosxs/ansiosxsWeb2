<?php

namespace Tests\Feature;

use App\Mail\RecordatorioAutomatico;
use App\Models\ConfiguracionGeneral;
use App\Models\ConfiguracionRecordatorio;
use App\Models\Libro;
use App\Models\Prestamo;
use App\Models\RecordatorioAutomatico as Recordatorio;
use App\Models\Socio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class RecordatoriosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 11, 7)->setTime(10, 0));
    }

    public function test_reminders_are_disabled_and_approval_is_required_by_default(): void
    {
        Mail::fake();
        $socio = $this->createSocio(['email' => 'lectora@example.cl', 'vigente_hasta' => today()->subDay()]);
        $this->createPrestamo($socio, today()->subDay());

        $this->artisan('biblioteca:enviar-recordatorios')->assertSuccessful();

        $configuracion = ConfiguracionRecordatorio::query()->findOrFail(1);
        $this->assertFalse($configuracion->automaticos_activos);
        $this->assertTrue($configuracion->preguntar_antes);
        $this->assertDatabaseCount('recordatorios_automaticos', 0);
        Mail::assertNothingSent();
    }

    public function test_daily_process_queues_expired_loan_and_membership_for_authorization(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $socioPrestamo = $this->createSocio(['email' => 'libro@example.cl']);
        $socioMembresia = $this->createSocio(['email' => 'socia@example.cl', 'vigente_hasta' => today()->subDay()]);
        $this->createPrestamo($socioPrestamo, today()->subDay());
        ConfiguracionRecordatorio::query()->findOrFail(1)->update([
            'automaticos_activos' => true,
            'preguntar_antes' => true,
        ]);

        $this->artisan('biblioteca:enviar-recordatorios')->assertSuccessful();

        $this->assertDatabaseCount('recordatorios_automaticos', 2);
        $this->assertDatabaseHas('recordatorios_automaticos', [
            'tipo' => 'prestamo_vencido',
            'socio_id' => $socioPrestamo->id,
            'estado' => 'pendiente',
        ]);
        $this->assertDatabaseHas('recordatorios_automaticos', [
            'tipo' => 'membresia_vencida',
            'socio_id' => $socioMembresia->id,
            'estado' => 'pendiente',
        ]);
        Mail::assertNothingSent();

        $this->actingAs($admin)
            ->get(route('admin.recordatorios.index'))
            ->assertOk()
            ->assertSee('Correos pendientes (2)')
            ->assertSee('Autorizar y enviar')
            ->assertSee('Correos enviados (0)')
            ->assertSee('Editar plantillas de correo');
        $this->get(route('admin.recordatorios.plantillas'))
            ->assertOk()
            ->assertSee('Plantillas de correo')
            ->assertSee('name="prestamo_cuerpo"', false);
    }

    public function test_authorizing_expired_loan_sends_custom_template_with_signed_postponement(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $socio = $this->createSocio(['nombres' => 'María', 'email' => 'lectora@example.cl']);
        $prestamo = $this->createPrestamo($socio, today()->subDay());
        ConfiguracionRecordatorio::query()->findOrFail(1)->update([
            'automaticos_activos' => true,
            'preguntar_antes' => true,
            'prestamo_asunto' => 'Aviso para {{nombre}}',
            'prestamo_cuerpo' => 'El libro {{libro}} venció el {{fecha_vencimiento}}.',
        ]);
        $this->artisan('biblioteca:enviar-recordatorios')->assertSuccessful();
        $recordatorio = Recordatorio::query()->where('tipo', 'prestamo_vencido')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.recordatorios.enviar', $recordatorio))
            ->assertRedirect();

        Mail::assertSent(RecordatorioAutomatico::class, fn (RecordatorioAutomatico $mail) => $mail->asunto === 'Aviso para María'
            && str_contains($mail->cuerpo, 'El libro Libro de prueba venció el 06/11/2026.')
            && $mail->urlPostergacion !== null);
        $this->assertDatabaseHas('recordatorios_automaticos', [
            'id' => $recordatorio->id,
            'estado' => 'enviado',
            'autorizado_por_user_id' => $admin->id,
        ]);
        $this->get(route('admin.recordatorios.index'))
            ->assertOk()
            ->assertSee('Correos enviados (1)')
            ->assertSee('María')
            ->assertSee($admin->name);

        $urlFirmada = URL::temporarySignedRoute('prestamos.postergar.confirmar', now()->addDay(), [
            'prestamo' => $prestamo->id,
            'fecha_vencimiento' => $prestamo->fecha_vencimiento->toDateString(),
        ]);
        $this->get($urlFirmada)->assertOk()->assertSee('Confirmar postergación');
        $this->post($urlFirmada)->assertOk()->assertSee('se postergó dos semanas');
        $this->assertSame(today()->addDays(13)->toDateString(), $prestamo->fresh()->fecha_vencimiento->toDateString());
    }

    public function test_disabling_automatic_emails_does_not_create_or_send_reminders(): void
    {
        Mail::fake();
        $socio = $this->createSocio(['email' => 'lector@example.cl', 'vigente_hasta' => today()->subDay()]);
        $this->createPrestamo($socio, today()->subDay());
        ConfiguracionRecordatorio::query()->findOrFail(1)->update(['automaticos_activos' => false]);

        $this->artisan('biblioteca:enviar-recordatorios')->assertSuccessful();

        $this->assertDatabaseCount('recordatorios_automaticos', 0);
        Mail::assertNothingSent();
    }

    public function test_zero_advance_days_disables_upcoming_loan_reminders(): void
    {
        Mail::fake();
        $socio = $this->createSocio(['email' => 'lector@example.cl']);
        $this->createPrestamo($socio, today()->addDays(2));
        ConfiguracionGeneral::actual()->update(['dias_recordatorio' => 0]);
        ConfiguracionRecordatorio::query()->findOrFail(1)->update([
            'automaticos_activos' => true,
            'preguntar_antes' => true,
        ]);

        $this->artisan('biblioteca:enviar-recordatorios')->assertSuccessful();

        $this->assertDatabaseCount('recordatorios_automaticos', 0);
        Mail::assertNothingSent();
    }

    public function test_admin_can_save_settings_and_non_admin_cannot_manage_them(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $voluntario = User::factory()->create();
        $this->actingAs($voluntario)->get(route('admin.recordatorios.index'))->assertForbidden();

        $this->actingAs($admin)->put(route('admin.recordatorios.update'), [
            'automaticos_activos' => '1',
            'preguntar_antes' => '1',
        ])->assertRedirect(route('admin.recordatorios.index'));
        $this->put(route('admin.recordatorios.plantillas.update'), [
            'prestamo_asunto' => 'Préstamo vencido',
            'prestamo_cuerpo' => 'Hola {{nombre}}: {{libro}}.',
            'membresia_asunto' => 'Membresía vencida',
            'membresia_cuerpo' => 'Hola {{nombre}}.',
        ])->assertRedirect(route('admin.recordatorios.plantillas'));

        $this->assertTrue(ConfiguracionRecordatorio::query()->findOrFail(1)->preguntar_antes);
        $this->assertSame('Préstamo vencido', ConfiguracionRecordatorio::query()->findOrFail(1)->prestamo_asunto);
    }

    private function createSocio(array $overrides = []): Socio
    {
        static $correlativo = 0;
        $correlativo++;

        return Socio::create(array_merge([
            'run' => '1234567'.str_pad((string) $correlativo, 2, '0', STR_PAD_LEFT).'K',
            'nombres' => 'Socio',
            'apellidos' => 'Prueba',
            'telefono' => '+56 9 1234 5678',
            'email' => null,
            'comuna' => 'Concepción',
            'fecha_inscripcion' => today(),
            'vigente_hasta' => today()->addYear(),
            'activo' => true,
        ], $overrides));
    }

    private function createPrestamo(Socio $socio, $fechaVencimiento): Prestamo
    {
        $user = User::factory()->create();
        $libro = Libro::create([
            'titulo' => 'Libro de prueba',
            'autor' => 'Autora de prueba',
            'cantidad_total' => 1,
        ]);

        return Prestamo::create([
            'socio_id' => $socio->id,
            'libro_id' => $libro->id,
            'cantidad' => 1,
            'cantidad_devuelta' => 0,
            'user_id' => $user->id,
            'fecha_prestamo' => now()->subDays(15),
            'fecha_vencimiento' => $fechaVencimiento,
            'estado' => 'activo',
            'operacion_uuid' => (string) Str::uuid(),
        ]);
    }
}
