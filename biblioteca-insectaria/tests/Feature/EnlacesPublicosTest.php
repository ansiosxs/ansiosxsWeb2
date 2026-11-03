<?php

namespace Tests\Feature;

use App\Models\EnlacePublico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnlacesPublicosTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_editor_can_manage_public_links(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);

        $this->actingAs($editor)
            ->get(route('admin.enlaces.index'))
            ->assertOk()
            ->assertSee('Agregar enlace')
            ->assertSee('Enlaces públicos')
            ->assertSee('Formularios de Actividades')
            ->assertSee('Pendiente');

        $this->post(route('admin.enlaces.store'), [
            'titulo' => 'Talleres',
            'descripcion' => 'Conoce nuestras actividades.',
            'url' => 'https://example.org/talleres',
            'orden' => 102,
            'activo' => '1',
        ])->assertRedirect(route('admin.enlaces.index'));

        $enlace = EnlacePublico::query()->where('titulo', 'Talleres')->firstOrFail();
        $this->assertSame($editor->id, $enlace->user_id);
        $this->assertTrue($enlace->activo);

        $this->put(route('admin.enlaces.update', $enlace), [
            'titulo' => 'Próximos talleres',
            'descripcion' => '',
            'url' => 'https://example.org/proximos',
            'orden' => 1,
        ])->assertRedirect(route('admin.enlaces.index'));

        $this->assertDatabaseHas('enlaces_publicos', [
            'id' => $enlace->id,
            'titulo' => 'Próximos talleres',
            'activo' => false,
        ]);

        $this->delete(route('admin.enlaces.destroy', $enlace))
            ->assertRedirect(route('admin.enlaces.index'));
        $this->assertDatabaseMissing('enlaces_publicos', ['id' => $enlace->id]);
    }

    public function test_public_api_returns_only_active_links_in_manual_order(): void
    {
        $author = User::factory()->create(['role' => User::ROLE_ADMIN]);
        EnlacePublico::query()->create([
            'titulo' => 'Segundo',
            'url' => 'https://example.org/segundo',
            'orden' => 102,
            'activo' => true,
            'user_id' => $author->id,
        ]);
        EnlacePublico::query()->create([
            'titulo' => 'Oculto',
            'url' => 'https://example.org/oculto',
            'orden' => 99,
            'activo' => false,
            'user_id' => $author->id,
        ]);
        EnlacePublico::query()->create([
            'titulo' => 'Primero',
            'url' => 'https://example.org/primero',
            'orden' => 101,
            'activo' => true,
            'user_id' => $author->id,
        ]);

        $this->withHeader('Origin', 'https://www.ansiosxs.cl')
            ->getJson('/api/enlaces')
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'https://www.ansiosxs.cl')
            ->assertJsonPath('data.13.titulo', 'Primero')
            ->assertJsonPath('data.14.titulo', 'Segundo')
            ->assertJsonMissing(['titulo' => 'Oculto'])
            ->assertJsonMissingPath('data.0.user_id');
    }

    public function test_only_http_or_https_urls_are_accepted_and_volunteers_cannot_manage_links(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $volunteer = User::factory()->create(['role' => User::ROLE_VOLUNTARIO]);

        $this->actingAs($admin)
            ->from(route('admin.enlaces.create'))
            ->post(route('admin.enlaces.store'), [
                'titulo' => 'URL insegura',
                'url' => 'javascript:alert(1)',
                'orden' => 0,
            ])
            ->assertRedirect(route('admin.enlaces.create'))
            ->assertSessionHasErrors('url');

        $this->post(route('admin.enlaces.store'), [
            'titulo' => 'Enlace pendiente',
            'seccion' => 'Redes Sociales',
            'url' => '',
            'orden' => 100,
            'activo' => '1',
        ])->assertRedirect(route('admin.enlaces.index'));

        $this->assertDatabaseHas('enlaces_publicos', [
            'titulo' => 'Enlace pendiente',
            'seccion' => 'Redes Sociales',
            'url' => null,
            'activo' => true,
        ]);

        $this->actingAs($volunteer)
            ->get(route('admin.enlaces.index'))
            ->assertForbidden();
    }

    public function test_default_links_are_seeded_as_active_pending_entries_with_sections(): void
    {
        $this->getJson('/api/enlaces')
            ->assertOk()
            ->assertJsonPath('data.0.titulo', '🌈 Intereses de la comunidad de Biblioteca Insectaria')
            ->assertJsonPath('data.0.url', null)
            ->assertJsonPath('data.3.seccion', 'Formularios de Actividades')
            ->assertJsonPath('data.7.seccion', 'Redes Sociales')
            ->assertJsonPath('data.7.url', 'https://www.instagram.com/ansiosxs')
            ->assertJsonPath('data.8.url', 'https://www.facebook.com/Ansiosxs/')
            ->assertJsonPath('data.9.url', 'https://www.youtube.com/channel/UCMWvXVIqLlI72pXkdeWM62g')
            ->assertJsonPath('data.10.url', 'https://www.linkedin.com/company/ong-ansiosxs-nuevas-lecturas/?viewAsMember=true')
            ->assertJsonCount(13, 'data');
    }
}
