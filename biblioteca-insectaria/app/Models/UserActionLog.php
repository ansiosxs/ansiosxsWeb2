<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActionLog extends Model
{
    protected $table = 'user_action_logs';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'actor_id',
        'action',
        'resource_type',
        'resource_id',
        'details',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function getAccionLegibleAttribute(): string
    {
        $ruta = $this->details['route'] ?? null;
        $acciones = [
            'logout' => 'Cerró sesión',
            'login.store' => 'Inició sesión',
            'admin.dashboard' => 'Abrió el panel principal',
            'admin.libros.index' => 'Consultó el inventario de libros',
            'admin.libros.create' => 'Abrió el formulario para registrar un libro',
            'admin.libros.store' => 'Registró un libro',
            'admin.libros.show' => 'Consultó la ficha de un libro',
            'admin.libros.edit' => 'Abrió la edición de un libro',
            'admin.libros.update' => 'Actualizó un libro',
            'admin.libros.destroy' => 'Eliminó un libro',
            'admin.socios.index' => 'Consultó la lista de socias y socios',
            'admin.socios.create' => 'Abrió el formulario para registrar una persona socia',
            'admin.socios.store' => 'Registró una persona socia',
            'admin.socios.show' => 'Consultó la ficha de una persona socia',
            'admin.socios.edit' => 'Abrió la edición de una persona socia',
            'admin.socios.update' => 'Actualizó los datos de una persona socia',
            'admin.socios.destroy' => 'Eliminó una persona socia',
            'admin.socios.renovar' => 'Renovó la membresía de una persona socia',
            'admin.socios.caducar' => 'Caducó la membresía de una persona socia',
            'admin.socios.caducidad' => 'Cambió la fecha de vencimiento de una membresía',
            'admin.prestamos.index' => 'Consultó los préstamos activos',
            'admin.prestamos.libros' => 'Buscó libros para registrar un préstamo',
            'admin.prestamos.store' => 'Registró un préstamo',
            'admin.prestamos.devolver' => 'Registró una devolución',
            'admin.prestamos.editar' => 'Consultó los detalles de un préstamo',
            'admin.prestamos.update' => 'Actualizó los datos de un préstamo',
            'admin.prestamos.destroy' => 'Eliminó el registro de un préstamo',
            'admin.prestamos.correo-morosidad' => 'Preparó un aviso por correo de préstamo atrasado',
            'admin.prestamos.marcar-devuelto' => 'Marcó un préstamo atrasado como devuelto',
            'admin.morosidad' => 'Consultó los préstamos atrasados',
            'admin.estanterias.index' => 'Consultó las estanterías',
            'admin.muebles.index' => 'Consultó los muebles',
            'admin.anuncios.index' => 'Consultó los anuncios',
            'admin.enlaces.index' => 'Consultó los enlaces públicos',
            'admin.enlaces.create' => 'Abrió el formulario para crear un enlace público',
            'admin.enlaces.store' => 'Creó un enlace público',
            'admin.enlaces.edit' => 'Abrió la edición de un enlace público',
            'admin.enlaces.update' => 'Actualizó un enlace público',
            'admin.enlaces.destroy' => 'Eliminó un enlace público',
            'admin.estadisticas' => 'Consultó las estadísticas',
            'admin.estadisticas.libros-mas-prestados' => 'Consultó los libros más solicitados',
            'admin.turnos.index' => 'Consultó los turnos',
            'admin.turnos.show' => 'Consultó el detalle de un turno',
            'admin.turnos.create' => 'Abrió el formulario para crear turnos',
            'admin.turnos.store' => 'Creó turnos',
            'admin.turnos.voluntarios.update' => 'Actualizó la lista y participación de voluntariado para turnos',
            'admin.turnos.calendario' => 'Descargó el calendario de un turno',
            'admin.turnos.dias.update' => 'Actualizó un día del turno',
            'admin.turnos.generar' => 'Generó los turnos',
            'admin.turnos.publicar' => 'Publicó un turno',
            'admin.turnos.actividades.store' => 'Agregó una actividad a un turno',
            'admin.turnos.actividades.update' => 'Actualizó una actividad de un turno',
            'admin.turnos.actividades.destroy' => 'Eliminó una actividad de un turno',
            'admin.reportes.index' => 'Consultó los reportes',
            'admin.reportes.store' => 'Envió un reporte',
            'admin.reportes.estado' => 'Actualizó el estado de un reporte',
            'admin.usuarios.index' => 'Consultó las cuentas de usuario',
            'admin.usuarios.create' => 'Abrió el formulario para crear una cuenta',
            'admin.usuarios.store' => 'Creó una cuenta de usuario',
            'admin.usuarios.edit' => 'Abrió la edición de una cuenta',
            'admin.usuarios.update' => 'Actualizó una cuenta de usuario',
            'admin.usuarios.destroy' => 'Eliminó una cuenta de usuario',
            'admin.usuarios.toggle-status' => 'Activó o dio de baja una cuenta',
            'admin.anuncios.create' => 'Abrió el formulario para crear un anuncio',
            'admin.anuncios.store' => 'Creó un anuncio',
            'admin.anuncios.edit' => 'Abrió la edición de un anuncio',
            'admin.anuncios.update' => 'Actualizó un anuncio',
            'admin.anuncios.destroy' => 'Eliminó un anuncio',
            'admin.estanterias.store' => 'Agregó una estantería',
            'admin.estanterias.update' => 'Actualizó una estantería',
            'admin.estanterias.destroy' => 'Eliminó una estantería',
            'admin.muebles.store' => 'Agregó un mueble',
            'admin.muebles.update' => 'Actualizó un mueble',
            'admin.muebles.destroy' => 'Eliminó un mueble',
            'admin.exportar.libros' => 'Exportó el inventario de libros',
            'admin.exportar.socios' => 'Exportó la lista de socias y socios',
            'admin.exportar.prestamos' => 'Exportó la lista de préstamos',
            'admin.exportar.todo' => 'Exportó los datos de la biblioteca',
        ];

        if (is_string($ruta) && isset($acciones[$ruta])) {
            return $acciones[$ruta];
        }

        return 'Realizó una acción en la biblioteca';
    }

    public function getRecursoLegibleAttribute(): string
    {
        $recursos = [
            'LibroController' => 'Libro',
            'SocioController' => 'Persona socia',
            'PrestamoController' => 'Préstamo',
            'MuebleController' => 'Mueble o estantería',
            'AnuncioController' => 'Anuncio',
            'TurnoController' => 'Turno',
            'VolunteerReportController' => 'Reporte',
            'route' => 'Sesión',
        ];
        $recurso = $recursos[$this->resource_type] ?? 'Actividad';

        return $this->resource_id
            ? "{$recurso} n.º {$this->resource_id}"
            : $recurso;
    }
}
