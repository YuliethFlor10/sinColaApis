<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    // GET /agendas
    public function index()
    {
        // Incluye relaciones con usuarios, servicios y citas
        $agendas = Agenda::with(['user', 'service', 'appointment'])->get();
        return response()->json($agendas);
    }

    // GET /agendas/{id}
    public function show($id)
    {
        $agenda = Agenda::with(['user', 'service', 'appointment'])->find($id);

        if (!$agenda) {
            return response()->json(['message' => 'Agenda no encontrada'], 404);
        }

        return response()->json($agenda);
    }

    // POST /agendas
    public function store(Request $request)
    {
        $agenda = Agenda::create($request->all());

        return response()->json($agenda, 201);
    }

    // PUT /agendas/{id}
    public function update(Request $request, $id)
    {
        $agenda = Agenda::find($id);

        if (!$agenda) {
            return response()->json(['message' => 'Agenda no encontrada'], 404);
        }

        $agenda->update($request->all());

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
