<?php

namespace App\Http\Controllers;

use App\Models\Status;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    public function index()
    {
        return Status::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string',
            'descripcion' => 'nullable|string',
            'grupo' => 'nullable|string',
        ]);

        return Status::create($data);
    }

    public function show($id)
    {
        return Status::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $status  = Status::findOrFail($id);

        $data = $request->validate([
            'nombre' => 'sometimes|string',
            'descripcion' => 'nullable|string',
            'grupo' => 'nullable|string',
        ]);

        $status->update($data);
        return $status;
    }

    public function destroy($id)
    {
        Status::findOrFail($id)->delete();
        return response()->json(['message' => 'Estado eliminado']);
    }
}

