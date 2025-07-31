<?php

namespace App\Http\Controllers;7


use App\Models\Citas;
use Illuminate\Http\Request;

class CitasController extends Controller
{
    public function index()
    {
        return Citas::with(['usuario', 'servicio'])->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'usuario_id' => 'required',
            'servicio_id' => 'required',
            'fecha' => 'required|date',
            'hora' => 'required',
            'duracion_cita' => 'required|integer',
            'estado' => 'string',
        ]);

        return Citas::create($request->all());
    }

    public function show($id)
    {
        return Citas::with(['usuario', 'servicio'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $cita = Citas::findOrFail($id);
        $cita->update($request->all());
        return $cita;
    }

    public function destroy($id)
    {
        Citas::destroy($id);
        return response()->json(null, 204);
    }

    public function filtrarPorEstado($estado)
{
    return Citas::estado($estado)->get();
}
    public function filtrarPorFecha($fecha)
    {
        return Citas::fecha($fecha)->get();
    }

    public function citasPorUsuario($usuarioId)
    {
        return Citas::where('usuario_id', $usuarioId)->with(['usuario', 'servicio'])->get();
    }
    public function citasPorServicio($servicioId)

    {
        return Citas::where('servicio_id', $servicioId)->with(['usuario', 'servicio'])->get();
    }

    public function citasPorNegocio($negocioId)
    {
        return Citas::whereHas('servicio', function ($query) use ($negocioId) {
            $query->where('negocio_id', $negocioId);
        })->with(['usuario', 'servicio'])->get();
    }

    public function citasPendientes()
    {
        return Citas::estado('pendiente')->with(['usuario', 'servicio'])->get();
    }

    public function citasCompletadas()
    {
        return Citas::estado('completada')->with(['usuario', 'servicio'])->get();
    }
}

