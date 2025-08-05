<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index()
    {
        return Appointment::with(['usuario', 'negocio', 'servicio', 'estado'])->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fecha' => 'required|date',
            'hora' => 'required|date_format:H:i:s',
            'usuarios_id' => 'required|exists:usuarios,id',
            'negocios_id' => 'required|exists:negocios,id',
            'servicios_id' => 'required|exists:servicios,id',
            'estados_id' => 'required|exists:estados,id',
        ]);

        return Appointment::create($data);
    }

    public function show($id)
    {
        return Appointment::with(['usuario', 'negocio', 'servicio', 'estado'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $Appointment = Appointment::findOrFail($id);

        $data = $request->validate([
            'fecha' => 'sometimes|date',
            'hora' => 'sometimes|date_format:H:i:s',
            'usuarios_id' => 'sometimes|exists:usuarios,id',
            'negocios_id' => 'sometimes|exists:negocios,id',
            'servicios_id' => 'sometimes|exists:servicios,id',
            'estados_id' => 'sometimes|exists:estados,id',
        ]);

        $Appointment->update($data);
        return $Appointment;
    }

    public function destroy($id)
    {
        Appointment::findOrFail($id)->delete();
        return response()->json(['message' => 'Appointment eliminada']);
    }
}
