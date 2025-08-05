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
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        return Status::create($request->all());
    }

    public function show(Status $status)
    {
        return $status;
    }

    public function edit(Status $status)
    {
        return $status;
    }

    public function update(Request $request, Status $status)
    {
        $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
        ]);

        $status->update($request->all());
        return $status;
    }

    public function destroy(Status $status)
    {
        $status->delete();
        return response()->json(null, 204);
    }
}



