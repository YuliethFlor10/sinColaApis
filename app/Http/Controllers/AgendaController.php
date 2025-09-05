<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    // GET /agendas
    public function index(Request $request)
{
    $agendas = Agenda::filtrar($request->all())->get();
    if ($agendas->isEmpty()) {
        return response()->json(['message' => 'No se encuentra ninguna agenda con los filtros aplicados.'], 404);
    }
    return response()->json($agendas);
}

    /*public function index()
    {
        $agendas = Agenda::with(['business', 'user'])->get();
        return response()->json($agendas);
    }*/

    // GET /agendas/{id}
    public function show($id)
    {
        $agenda = Agenda::with(['business', 'user'])->find($id);

        if (!$agenda) {
            return response()->json(['message' => 'Agenda no encontrada'], 404);
        }

        return response()->json($agenda);
    }

    // POST /agendas
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'horarios' => 'required|array',
            'activo' => 'boolean',
            'negocios_id' => 'required|exists:businesses,id',
            'usuarios_id' => 'required|exists:users,id',
        ]);

        $agenda = Agenda::create($validated);

        return response()->json($agenda, 201);
    }

    // PUT /agendas/{id}
    public function update(Request $request, $id)
    {
        $agenda = Agenda::find($id);

        if (!$agenda) {
            return response()->json(['message' => 'Agenda no encontrada'], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:100',
            'horarios' => 'sometimes|required|array',
            'activo' => 'sometimes|boolean',
            'negocios_id' => 'sometimes|required|exists:businesses,id',
            'usuarios_id' => 'sometimes|required|exists:users,id',
        ]);

        $agenda->update($validated);

        return response()->json($agenda);
    }

    // DELETE /agendas/{id}
    public function destroy($id)
    {
        $agenda = Agenda::find($id);

        if (!$agenda) {
            return response()->json(['message' => 'Agenda no encontrada'], 404);
        }

        $agenda->delete();

        return response()->json(['message' => 'Agenda eliminada correctamente']);
    }
}
