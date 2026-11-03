<?php

namespace Tests\Feature;

use App\Models\Noticia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoticiasTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_manage_news_and_has_volunteer_capabilities(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $this->assertTrue($editor->isVolunteer());

        $this->actingAs($editor)
            ->get(route('admin.noticias.index'))
            ->assertOk()
            ->assertSee('Noticias')
            ->assertSee('Crear noticia');

        $this->post(route('admin.noticias.store'), [
            'titulo' => 'Lecturas en comunidad',
            'resumen' => 'Una jornada de lectura abierta.',
            'contenido' => "Nos reunimos para leer.\nTe esperamos.",
        ])->assertRedirect(route('admin.noticias.index'));

        $noticia = Noticia::query()->firstOrFail();
        $this->assertSame($editor->id, $noticia->user_id);
        $this->assertDatabaseHas('noticias', ['titulo' => 'Lecturas en comunidad']);

        $this->put(route('admin.noticias.update', $noticia), [
            'titulo' => 'Lecturas compartidas',
            'resumen' => 'Nueva fecha.',
            'contenido' => 'Te esperamos este sábado.',
        ])->assertRedirect(route('admin.noticias.index'));
        $this->assertDatabaseHas('noticias', ['id' => $noticia->id, 'titulo' => 'Lecturas compartidas']);

        $this->delete(route('admin.noticias.destroy', $noticia))
            ->assertRedirect(route('admin.noticias.index'));
        $this->assertDatabaseMissing('noticias', ['id' => $noticia->id]);
    }

    public function test_volunteer_cannot_manage_news(): void
    {
        $volunteer = User::factory()->create(['role' => User::ROLE_VOLUNTARIO]);

        $this->actingAs($volunteer)
            ->get(route('admin.noticias.index'))
            ->assertForbidden();
        $this->post(route('admin.noticias.store'), [
            'titulo' => 'Acceso no permitido',
            'contenido' => 'Contenido',
        ])->assertForbidden();
    }

    public function test_news_editor_supports_safe_html_and_removes_unsafe_markup(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $summary = '<p><strong>Lectura</strong> y <em>comunidad</em></p>';
        $content = '<p><strong>Nos encontramos</strong> con <em>libros</em>.</p>'
            .'<p><a href="https://example.org" onclick="alert(1)">Más información</a></p>'
            .'<p><a href="javascript:alert(1)">Enlace no permitido</a></p>'
            .'<script>alert(1)</script><img src=x onerror="alert(1)">';

        $this->actingAs($editor)
            ->get(route('admin.noticias.create'))
            ->assertOk()
            ->assertSee('data-rich-text-surface', false)
            ->assertSee('Ctrl+B')
            ->assertSee('insertUnorderedList')
            ->assertSee('Encabezado')
            ->assertSee('Viñetas')
            ->assertSee('Deshacer')
            ->assertDontSee('Opcional; se muestra en el listado público')
            ->assertDontSee('Escribe y aplica formato con la barra')
            ->assertDontSee('Ctrl+Enter añade otra línea a la cita');

        $this->post(route('admin.noticias.store'), [
            'titulo' => 'Lectura en comunidad',
            'resumen' => $summary,
            'contenido' => $content,
        ])->assertRedirect(route('admin.noticias.index'));

        $noticia = Noticia::query()->firstOrFail();
        $this->assertSame($summary, $noticia->resumen);
        $this->assertStringContainsString('<strong>Nos encontramos</strong>', $noticia->contenido);
        $this->assertStringContainsString('<em>libros</em>', $noticia->contenido);
        $this->assertStringContainsString('rel="noopener noreferrer"', $noticia->contenido);
        $this->assertStringNotContainsString('<script', $noticia->contenido);
        $this->assertStringNotContainsString('<img', $noticia->contenido);
        $this->assertStringNotContainsString('onclick', $noticia->contenido);
        $this->assertStringNotContainsString('javascript:', $noticia->contenido);

        $this->getJson('/api/noticias/'.$noticia->id)
            ->assertOk()
            ->assertJsonPath('data.resumen', $summary)
            ->assertJsonPath('data.contenido', $noticia->contenido);
    }

    public function test_admin_can_assign_editor_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_VOLUNTARIO]);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.edit', $user))
            ->assertOk()
            ->assertSee('Editor');

        $this->put(route('admin.usuarios.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => User::ROLE_EDITOR,
            'activo' => true,
        ])->assertRedirect(route('admin.usuarios.index'));

        $this->assertSame(User::ROLE_EDITOR, $user->fresh()->role);
        $this->get(route('admin.usuarios.index'))->assertSee('Editor');
    }

    public function test_public_api_returns_news_and_allows_the_static_site_origin(): void
    {
        $author = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $noticia = Noticia::query()->create([
            'titulo' => 'Una noticia pública',
            'resumen' => 'Resumen visible',
            'contenido' => 'Texto de la noticia.',
            'user_id' => $author->id,
        ]);

        $this->withHeader('Origin', 'https://www.ansiosxs.cl')
            ->getJson('/api/noticias')
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'https://www.ansiosxs.cl')
            ->assertJsonPath('data.0.titulo', 'Una noticia pública')
            ->assertJsonPath('data.0.url', route('noticias.publicas.show', $noticia))
            ->assertJsonMissingPath('data.0.user_id');

        $this->getJson('/api/noticias/'.$noticia->id)
            ->assertOk()
            ->assertJsonPath('data.contenido', 'Texto de la noticia.');
    }

    public function test_public_news_page_renders_share_and_search_metadata_on_the_server(): void
    {
        $author = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $noticia = Noticia::query()->create([
            'titulo' => 'Lecturas para compartir',
            'resumen' => '<p>Una <strong>jornada comunitaria</strong> de lectura.</p>',
            'contenido' => '<p>Invitamos a toda la comunidad.</p>',
            'user_id' => $author->id,
        ]);
        $url = route('noticias.publicas.show', $noticia);
        config(['cors.allowed_origins.0' => 'http://127.0.0.1:8002']);

        $this->get($url)
            ->assertOk()
            ->assertSee('<title>Lecturas para compartir | Ansiosxs – Nuevas Lecturas</title>', false)
            ->assertSee('name="description" content="Una jornada comunitaria de lectura."', false)
            ->assertSee('property="og:title" content="Lecturas para compartir"', false)
            ->assertSee('property="og:url" content="'.$url.'"', false)
            ->assertSee('name="twitter:card"', false)
            ->assertSee('rel="canonical" href="'.$url.'"', false)
            ->assertSee('"@type":"Article"', false)
            ->assertSee('href="http://127.0.0.1:8002/noticias.html">← Volver a noticias</a>', false)
            ->assertSee('class="article-layout"', false)
            ->assertSee('class="cover-panel"', false)
            ->assertSee('class="article-body"', false)
            ->assertSee('Invitamos a toda la comunidad.')
            ->assertSee('Compartir noticia');
    }
}
