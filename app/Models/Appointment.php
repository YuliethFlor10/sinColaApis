<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'usuarios_id',
        'negocios_id',
        'servicios_id',
        'estados_id',
        'nota',
        'fecha',
        'fecha_fin',
        'tiempo_estimado',
        'descripcion_cancel',
    ];
    //lista blanca de filtros permitidos para filtrar dinámicamente
    protected $allowedFilters = [
    'negocio_id',
    'usuario_id',
    'estado',
    'fecha',
    'fecha_inicio',
    'fecha_fin',
    'servicio_id',
    'hora_inicio',
    'hora_fin',
    'hoy',
    'proximas',
    'esta_semana',
    'con_retraso',
    'minutos_retraso',
];

    // Relaciones

    public function user()
    {
        return $this->belongsTo(User::class, 'usuarios_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'negocios_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'servicios_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'estados_id');
    }

   // === SCOPES PRINCIPALES ===

    public function scopeDelNegocio($query, $negocioId)
    {
        return $query->where('negocios_id', $negocioId);
    }

    public function scopeDelUsuario($query, $usuarioId)
    {
        return $query->where('usuarios_id', $usuarioId);
    }

    public function scopeConfirmadas($query)
    {
        return $query->whereHas('status', function ($q) {
            $q->whereRaw('LOWER(nombre) = ?', ['confirmada']);
        });
    }

    public function scopeCanceladas($query)
    {
        return $query->whereHas('status', function ($q) {
            $q->whereRaw('LOWER(nombre) = ?', ['cancelada']);
        });
    }

    public function scopeCompletadas($query)
    {
        return $query->whereHas('status', function ($q) {
            $q->whereRaw('LOWER(nombre) = ?', ['completada']);
        });
    }

    public function scopePorEstado($query, $estado)
    {
        if (is_numeric($estado)) {
            return $query->where('estados_id', $estado);
        }

        // Normaliza a minúsculas y sin tildes para comparar
        $estadoNormalizado = mb_strtolower($estado);
        return $query->whereHas('status', function ($q) use ($estadoNormalizado) {
            $q->whereRaw('LOWER(nombre) = ?', [$estadoNormalizado]);
        });
    }

    public function scopeEnFecha($query, $fecha)
    {
        return $query->whereDate('fecha', $fecha);
    }

    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereDate('fecha', '>=', $fechaInicio)
                     ->whereDate('fecha', '<=', $fechaFin);
    }

    public function scopeHoy($query)
    {
        return $query->whereDate('fecha', now()->format('Y-m-d'));
    }

    public function scopeProximas($query)
    {
        return $query->where('fecha', '>', now());
    }

    public function scopeEstaSemana($query)
    {
        return $query->whereBetween('fecha', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeDelServicio($query, $servicioId)
    {
        return $query->where('servicios_id', $servicioId);
    }

    public function scopeEntreHoras($query, $horaInicio, $horaFin)
    {
        return $query->whereTime('fecha', '>=', $horaInicio)
                     ->whereTime('fecha', '<=', $horaFin);
    }

    public function scopeConRetraso($query, $minutosRetraso = 15)
    {
        return $query->where('fecha', '<', now()->subMinutes($minutosRetraso))
                     ->whereHas('status', function ($q) {
                         $q->where('nombre', 'Confirmada');
                     });
    }


    // === SCOPE FILTRAR DINÁMICO ===

    public function scopeFiltrar($query, $filters)
    {
        foreach ($filters as $filter => $value) {
            if (!in_array($filter, $this->allowedFilters)) {
                continue; // ignorar filtros no permitidos
            }

            switch ($filter) {
                case 'negocio_id':
                    $query->delNegocio($value);
                    break;

                case 'usuario_id':
                    $query->delUsuario($value);
                    break;

                case 'estado':
                    $query->porEstado($value);
                    break;

                case 'fecha':
                    $query->enFecha($value);
                    break;

                case 'fecha_inicio':
                    if (isset($filters['fecha_fin'])) {
                        $query->entreFechas($value, $filters['fecha_fin']);
                    }
                    break;

                case 'fecha_fin':
                    // Se maneja en 'fecha_inicio'
                    break;

                case 'servicio_id':
                    $query->delServicio($value);
                    break;

                case 'hora_inicio':
                    if (isset($filters['hora_fin'])) {
                        $query->entreHoras($value, $filters['hora_fin']);
                    }
                    break;

                case 'hora_fin':
                    // Se maneja en 'hora_inicio'
                    break;

                case 'hoy':
                    if ($value) {
                        $query->hoy();
                    }
                    break;

                case 'proximas':
                    if ($value) {
                        $query->proximas();
                    }
                    break;

                case 'esta_semana':
                    if ($value) {
                        $query->estaSemana();
                    }
                    break;

                case 'con_retraso':
                    if ($value) {
                        $minutos = $filters['minutos_retraso'] ?? 15;
                        $query->conRetraso($minutos);
                    }
                    break;

                case 'minutos_retraso':
                    // Se maneja en 'con_retraso'
                    break;
            }
        }

        return $query;
    }

}
