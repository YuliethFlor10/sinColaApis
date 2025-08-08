<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    // GET /businesses
    public function index()
    {
        $businesses = Business::with('status')->get();
        return response()->json($businesses);
    }

    // GET /businesses/{id}
    public function show($id)
    {
        $business = Business::with('status')->find($id);

        if (!$business) {
            return response()->json(['message' => 'Negocio no encontrado'], 404);
        }

        return response()->json($business);
    }

    // POST /businesses
    public function store(Request $request)
    {
        $business = Business::create($request->all());
        return response()->json($business, 201);
    }

    // PUT /businesses/{id}
    public function update(Request $request, $id)
    {
        $business = Business::find($id);

        if (!$business) {
            return response()->json(['message' => 'Negocio no encontrado'], 404);
        }

        $business->update($request->all());
        return response()->json($business);
    }

    // DELETE /businesses/{id}
    public function destroy($id)
    {
        $business = Business::find($id);

        if (!$business) {
            return response()->json(['message' => 'Negocio no encontrado'], 404);
        }

        $business->delete();
        return response()->json(['message' => 'Negocio eliminado correctamente']);
    }
}
