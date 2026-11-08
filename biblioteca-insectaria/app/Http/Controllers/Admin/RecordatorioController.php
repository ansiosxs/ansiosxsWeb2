<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionRecordatorio;
use App\Models\RecordatorioAutomatico;
use App\Services\RecordatorioAutomaticoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecordatorioController extends Controller
{
    public function index(): View
    {
        $configuracion = ConfiguracionRecordatorio::query()->findOrFail(1);
        $pendientes = RecordatorioAutomatico::query()
            ->with(['socio', 'prestamo.libro'])
            ->where('estado', 'pendiente')
            ->orderBy('solicitado_at')
            ->paginate(20, ['*'], 'pendientes_page');
        $enviados = RecordatorioAutomatico::query()
            ->with(['socio', 'prestamo.libro', 'autorizadoPor'])
            ->where('estado', 'enviado')
            ->orderByDesc('enviado_at')
            ->paginate(20, ['*'], 'enviados_page');

        return view('admin.recordatorios.index', compact('configuracion', 'pendientes', 'enviados'));
    }

    public function update(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'automaticos_activos' => ['required', 'boolean'],
            'preguntar_antes' => ['required', 'boolean'],
        ]);
        ConfiguracionRecordatorio::query()->findOrFail(1)->update($datos);

        return redirect()->route('admin.recordatorios.index')->with('status', 'La configuración de recordatorios quedó guardada.');
    }

    public function plantillas(): View
    {
        $configuracion = ConfiguracionRecordatorio::query()->findOrFail(1);

        return view('admin.recordatorios.plantillas', compact('configuracion'));
    }

    public function actualizarPlantillas(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'prestamo_asunto' => ['required', 'string', 'max:255'],
            'prestamo_cuerpo' => ['required', 'string', 'max:10000'],
            'membresia_asunto' => ['required', 'string', 'max:255'],
            'membresia_cuerpo' => ['required', 'string', 'max:10000'],
        ]);
        ConfiguracionRecordatorio::query()->findOrFail(1)->update($datos);

        return redirect()->route('admin.recordatorios.plantillas')->with('status', 'Las plantillas de correo quedaron guardadas.');
    }

    public function enviar(RecordatorioAutomatico $recordatorio, Request $request, RecordatorioAutomaticoService $service): RedirectResponse
    {
        if (! $service->enviar($recordatorio->id, $request->user())) {
            return back()->withErrors(['recordatorio' => 'El recordatorio ya no está pendiente o el vencimiento cambió.']);
        }

        return back()->with('status', 'Correo autorizado y enviado.');
    }

    public function descartar(RecordatorioAutomatico $recordatorio, RecordatorioAutomaticoService $service): RedirectResponse
    {
        if (! $service->descartar($recordatorio->id)) {
            return back()->withErrors(['recordatorio' => 'El recordatorio ya no está pendiente.']);
        }

        return back()->with('status', 'El recordatorio pendiente fue descartado.');
    }
}
