<?php

namespace App\Http\Controllers;

use App\Models\Status;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    // GET /statuses
    public function index()
    {
        $statuses = Status::all();
        return response()->json($statuses);
    }

    // GET /statuses/{id}
    public function show($id)
    {
        $status = Status::find($id);

        if (!$status) {
            return response()->json(['message' => 'Estado no encontrado'], 404);
        }

        return response()->json($status);
    }

    // POST /statuses
    public function store(Request $request)
    {
        $status = Status::create($request->all());
        return response()->json($status, 201);
    }

    // PUT /statuses/{id}
    public function update(Request $request, $id)
    {
        $status = Status::find($id);

        if (!$status) {
            return response()->json(['message' => 'Estado no encontrado'], 404);
        }

        $status->update($request->all());
        return response()->json($status);
    }

    // DELETE /statuses/{id}
    public function destroy($id)
    {
        $status = Status::find($id);

        if (!$status) {
            return response()->json(['message' => 'Estado no encontrado'], 404);
        }

        $status->delete();
        return response()->json(['message' => 'Estado eliminado correctamente']);
    }
}
