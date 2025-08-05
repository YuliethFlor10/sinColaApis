<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    public function index()
    {
        return Agenda::with(['negocio', 'estado'])->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'dia' => 'required|string',
            'hora_inicio' => 'required|date_format:H:i:s',
            'hora_fin' => 'required|date_format:H:i:s',
            'negocios_id' => 'required|exists:negocios,id',
            'estados_id' => 'required|exists:estados,id',
        ]);

        return Agenda::create($data);
    }

    public function show($id)
    {
        return Agenda::with(['negocio', 'estado'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $agenda = Agenda::findOrFail($id);

        $data = $request->validate([
            'dia' => 'sometimes|string',
            'hora_inicio' => 'sometimes|date_format:H:i:s',
            'hora_fin' => 'sometimes|date_format:H:i:s',
            'negocios_id' => 'sometimes|exists:negocios,id',
            'estados_id' => 'sometimes|exists:estados,id',
        ]);

        $agenda->update($data);
        return $agenda;
    }

    public function destroy($id)
    {
        Agenda::findOrFail($id)->delete();
        return response()->json(['message' => 'Agenda eliminada']);
    }
}

