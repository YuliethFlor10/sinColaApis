<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;

class AppointmentController extends Controller
{
    // GET /api/appointments
    public function index()
    {
        return response()->json(Appointment::all());
    }

    // POST /api/appointments
    public function store(Request $request)
    {
        $data = $this->mapFromAngular($request);
        $appointment = Appointment::create($data);
        return response()->json($appointment, 201);
    }

    // GET /api/appointments/{id}
    public function show($id)
    {
        $appointment = Appointment::findOrFail($id);
        return response()->json($appointment);
    }

    // PUT/PATCH /api/appointments/{id}
    public function update(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);
        $data = $this->mapFromAngular($request);
        $appointment->update($data);
        return response()->json($appointment);
    }

    // DELETE /api/appointments/{id}
    public function destroy($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();
        return response()->json(null, 204);
    }

    /**
     * Mapear datos desde Angular al formato de Laravel
     */
    private function mapFromAngular(Request $request)
    {
        $day = $request->input('day');
        $monthName = strtoupper($request->input('monthName', ''));
        $year = date('Y');

        // Convertir monthName → número
        $months = [
            'ENERO' => '01', 'FEBRERO' => '02', 'MARZO' => '03', 'ABRIL' => '04',
            'MAYO' => '05', 'JUNIO' => '06', 'JULIO' => '07', 'AGOSTO' => '08',
            'SEPTIEMBRE' => '09', 'OCTUBRE' => '10', 'NOVIEMBRE' => '11', 'DICIEMBRE' => '12'
        ];
        $month = $months[$monthName] ?? '01';
        $fechaCita = $day ? "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT) : null;

        return [
            'nombre'            => $request->input('clientName'),
            'tipo_cita'         => $request->input('serviceName'),
            'fecha_cita'        => $fechaCita,
            'hora_cita'         => $request->input('time'),
            'nota'              => $request->input('nota'),
            'email'             => $request->input('clientEmail'),
            'personal_servicio' => $request->input('staffName', 'Por asignar'),

            // Campos adicionales requeridos
            'tipo_documento'    => $request->input('tipo_documento', 'CC'),
            'numero_documento'  => $request->input('numero_documento', '0000000000'),
            'fecha_nacimiento'  => $request->input('fecha_nacimiento', '2000-01-01'),
            'numero_telefono'   => $request->input('numero_telefono', '3000000000'),
            'negocios_id'       => $request->input('negocios_id', 1),
        ];
    }
}
