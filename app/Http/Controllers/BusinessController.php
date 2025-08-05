<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    public function index()
    {
        return Business::with(['status', 'plan'])->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string',
            'nit' => 'required|string|unique:businesses,nit',
            'direccion' => 'required|string',
            'telefono' => 'nullable|string',
            'correo' => 'nullable|email',
            'representante' => 'nullable|string',
            'descripcion' => 'nullable|string',
            'planes_id' => 'required|exists:planes,id',
            'status_id' => 'required|exists:statuses,id',
        ]);

        return Business::create($data);
    }

    public function show($id)
    {
        return Business::with(['status', 'plan'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $business = Business::findOrFail($id);

        $data = $request->validate([
            'nombre' => 'sometimes|string',
            'nit' => 'sometimes|string|unique:businesses,nit,' . $id,
            'direccion' => 'sometimes|string',
            'telefono' => 'nullable|string',
            'correo' => 'nullable|email',
            'representante' => 'nullable|string',
            'descripcion' => 'nullable|string',
            'planes_id' => 'sometimes|exists:planes,id',
            'status_id' => 'sometimes|exists:statuses,id',
        ]);

        $business->update($data);
        return $business;
    }

    public function destroy($id)
    {
        Business::findOrFail($id)->delete();
        return response()->json(['message' => 'Negocio eliminado']);
    }
}


