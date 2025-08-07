<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    public function index()
    {
        return Agenda::with(['business', 'user', 'status'])->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'horarios' => 'required|json',
            'activo' => 'required|boolean',
            'businesss_id' => 'required|exists:businesses,id',
            'users_id' => 'required|exists:users,id',
            'statuss_id' => 'required|exists:statuss,id'
        ]);

        return response()->json(Agenda::create($data)->load(['business', 'user', 'status']), 201);
    }

    public function show($id)
    {
        return Agenda::with(['business', 'user', 'status'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $agenda = Agenda::findOrFail($id);

        $data = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'horarios' => 'sometimes|json',
            'activo' => 'sometimes|boolean',
            'businesss_id' => 'sometimes|exists:businesses,id',
            'users_id' => 'sometimes|exists:users,id',
            'statuss_id' => 'sometimes|exists:statuss,id'
        ]);

        $agenda->update($data);
        return response()->json($agenda->load(['business', 'user', 'status']));
    }

    public function destroy($id)
    {
        $agenda = Agenda::findOrFail($id);
        $agenda->delete();
        return response()->json(['message' => 'Agenda eliminada correctamente']);
    }
}

