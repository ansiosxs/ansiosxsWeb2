<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VolunteerReport;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VolunteerReportController extends Controller
{
    private const CATEGORIES = ['Mantención', 'Materiales', 'Incidente', 'Convivencia', 'Sugerencia', 'Otro'];

    public function index(Request $request): View
    {
        $reports = VolunteerReport::query()
            ->with(['reporter', 'resolver'])
            ->when(! $request->user()->isAdmin(), fn ($query) => $query->where('reported_by', $request->user()->id))
            ->latest()
            ->paginate(20);

        return view('admin.reportes.index', [
            'reportes' => $reports,
            'categorias' => self::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isVolunteer() && $request->user()->activo, 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'category' => ['required', 'string', 'in:'.implode(',', self::CATEGORIES)],
            'description' => ['required', 'string', 'max:5000'],
        ]);

        VolunteerReport::create([
            ...$data,
            'reported_by' => $request->user()->id,
            'status' => 'open',
        ]);

        return redirect()->route('admin.reportes.index')->with('status', 'Informe enviado al administrador.');
    }

    public function actualizarEstado(Request $request, VolunteerReport $reporte): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved'],
        ]);
        $resolved = $data['status'] === 'resolved';

        $reporte->update([
            'status' => $data['status'],
            'resolved_by' => $resolved ? $request->user()->id : null,
            'resolved_at' => $resolved ? now() : null,
        ]);

        return redirect()->route('admin.reportes.index')->with('status', $resolved ? 'Informe marcado como resuelto.' : 'Estado del informe actualizado.');
    }
}
