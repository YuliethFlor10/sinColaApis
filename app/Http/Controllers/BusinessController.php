<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    public function index()
    {
        return Business::with(['plan', 'estado'])->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'nit' => 'required|string|unique:businesses,nit',
            'direccion' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'representante' => 'nullable|string|max:255',
            'planes_id' => 'required|exists:planes,id',
            'estados_id' => 'required|exists:estados,id'
        ]);

        return response()->json(Business::create($data)->load(['plan', 'estado']), 201);
    }

    public function show($id)
    {
        return Business::with(['plan', 'estado'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $business = Business::findOrFail($id);

        $data = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'nit' => 'sometimes|string|unique:businesses,nit,' . $id,
            'direccion' => 'sometimes|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'representante' => 'nullable|string|max:255',
            'planes_id' => 'sometimes|exists:planes,id',
            'estados_id' => 'sometimes|exists:estados,id'
        ]);

        $business->update($data);
        return response()->json($business->load(['plan', 'estado']));
    }

    public function destroy($id)
    {
        $business = Business::findOrFail($id);
        $business->delete();
        return response()->json(['message' => 'Negocio eliminado correctamente']);
    }
}


