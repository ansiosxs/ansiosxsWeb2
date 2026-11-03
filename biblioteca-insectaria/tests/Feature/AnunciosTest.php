<?php

namespace Tests\Feature;

use App\Models\Anuncio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnunciosTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_logo_returns_to_the_configured_static_site(): void
    {
        config()->set('cors.allowed_origins.0', 'http://127.0.0.1:8002');
        $this->get(route('catalogo'))
            ->assertOk()
            ->assertSee('href="http://127.0.0.1:8002/" aria-label="Volver al sitio de Ansiosxs"', false);

        config()->set('cors.allowed_origins.0', 'https://www.ansiosxs.cl');
        $this->get(route('catalogo'))
            ->assertOk()
            ->assertSee('href="https://www.ansiosxs.cl/" aria-label="Volver al sitio de Ansiosxs"', false);
    }

    public function test_admin_can_create_rich_text_announcements_safely(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $content = '<p><strong>Atención</strong> y <em>novedades</em></p>'
            .'<blockquote>Una cita para la comunidad</blockquote>'
            .'<p><a href="https://example.org" onclick="alert(1)">Más información</a></p>'
            .'<p><a href="javascript:alert(1)">Enlace no permitido</a></p>'
            .'<script>alert(1)</script><img src=x onerror="alert(1)">';

        $this->actingAs($admin)
            ->get(route('admin.anuncios.create'))
            ->assertOk()
            ->assertSee('data-rich-text-editor', false)
            ->assertSee('data-rich-text-surface', false)
            ->assertSee('Ctrl+B')
            ->assertSee('Ctrl+K')
            ->assertSee('blockquote')
            ->assertSee('Viñetas');

        $this->post(route('admin.anuncios.store'), [
            'titulo' => 'Novedades de la biblioteca',
            'cuerpo' => $content,
            'publicar_desde' => today()->format('Y-m-d'),
        ])->assertRedirect(route('admin.anuncios.index'));

        $anuncio = Anuncio::query()->firstOrFail();
        $this->assertSame(
            '<p><strong>Atención</strong> y <em>novedades</em></p>'
                .'<blockquote>Una cita para la comunidad</blockquote>'
                .'<p><a href="https://example.org" target="_blank" rel="noopener noreferrer">Más información</a></p>'
                .'<p>Enlace no permitido</p>',
            $anuncio->cuerpo,
        );

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('<strong>Atención</strong>', false)
            ->assertSee('<blockquote>Una cita para la comunidad</blockquote>', false)
            ->assertSee('Anuncios vigentes')
            ->assertSee('data-announcement-dialog', false)
            ->assertSee('showModal()', false)
            ->assertSee('@cancel.prevent', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('Cerrar avisos')
            ->assertDontSee('alert(1)', false)
            ->assertDontSee('onclick=', false)
            ->assertDontSee('javascript:', false)
            ->assertDontSee('src="x"', false);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Anuncios vigentes')
            ->assertDontSee('data-announcement-dialog', false);

        $this->get(route('catalogo'))
            ->assertOk()
            ->assertDontSee('Novedades de la biblioteca')
            ->assertDontSee('<strong>Atención</strong>', false)
            ->assertDontSee('alert(1)', false)
            ->assertDontSee('onclick=', false)
            ->assertDontSee('javascript:', false);
    }

    public function test_admin_cannot_save_an_announcement_without_text_after_sanitizing(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->from(route('admin.anuncios.create'))
            ->post(route('admin.anuncios.store'), [
                'titulo' => 'Anuncio vacío',
                'cuerpo' => '<script>alert(1)</script>',
                'publicar_desde' => today()->format('Y-m-d'),
            ])
            ->assertRedirect(route('admin.anuncios.create'))
            ->assertSessionHasErrors('cuerpo');

        $this->assertDatabaseCount('anuncios', 0);
    }

    public function test_only_announcements_within_their_publication_dates_appear_in_the_admin_summary(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        Anuncio::query()->create([
            'titulo' => 'Anuncio futuro',
            'cuerpo' => 'Aún no debe mostrarse.',
            'publicar_desde' => today()->addDay(),
            'user_id' => $admin->id,
        ]);
        Anuncio::query()->create([
            'titulo' => 'Anuncio vencido',
            'cuerpo' => 'Ya no debe mostrarse.',
            'publicar_desde' => today()->subDays(2),
            'publicar_hasta' => today()->subDay(),
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Anuncio futuro')
            ->assertDontSee('Anuncio vencido')
            ->assertDontSee('data-announcement-alert', false);
    }
}
