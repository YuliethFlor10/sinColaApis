<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Traits\BelongsToTenant;

class Appointment extends Model
{
    use BelongsToTenant;
    
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'usuarios_id',
        'negocios_id',
        'servicios_id',
        'agendas_id',
        'estados_id',
        'nota',
        'fecha',
        'fecha_fin',
        'tiempo_estimado',
        'descripcion_cancel',
        'cliente_nombre',
        'cliente_email',
        'cliente_tipo_doc',
        'cliente_num_doc',
        'cliente_fecha_nac',
        'cliente_telefono',
        'tipo_servicio',
        'personal_asignado',
        'confirmation_token',
        'token_expires_at',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'fecha_fin' => 'datetime',
        'tiempo_estimado' => 'integer',
        'cliente_fecha_nac' => 'date',
        'token_expires_at' => 'datetime',
    ];

    protected $allowedFilters = [
        'negocio_id', 'usuario_id', 'estado', 'fecha', 'fecha_inicio',
        'fecha_fin', 'servicio_id', 'hora_inicio', 'hora_fin', 'hoy',
        'proximas', 'esta_semana', 'con_retraso', 'minutos_retraso',
    ];

    // ============================================
    // 🔥 RELACIONES - CON SOLUCIÓN DE TENANT
    // ============================================

    public function user()
    {
        return $this->belongsTo(User::class, 'usuarios_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'negocios_id');
    }

    /**
     * 🔥 SOLUCIÓN: Desactivar global scopes en service
     */
    public function service()
    {
        return $this->belongsTo(Service::class, 'servicios_id')
            ->withoutGlobalScopes(); // ← ESTA ES LA CLAVE
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'estados_id')
            ->withoutGlobalScopes(); // ← También para status por si acaso
    }

    public function agenda()
    {
        return $this->belongsTo(Agenda::class, 'agendas_id')
            ->withoutGlobalScopes(); // ← También para agenda
    }

    // ============================================
    // SCOPES (sin cambios)
    // ============================================

    public function scopeDelNegocio($query, $negocioId)
    {
        return $query->where('negocios_id', $negocioId);
    }

    public function scopeDelUsuario($query, $usuarioId)
    {
        return $query->where('usuarios_id', $usuarioId);
    }

    public function scopePorEstado($query, $estado)
    {
        if (is_numeric($estado)) {
            return $query->where('estados_id', $estado);
        }

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

    public function scopeFiltrar($query, $filters)
    {
        foreach ($filters as $filter => $value) {
            if (!in_array($filter, $this->allowedFilters)) {
                continue;
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
                case 'servicio_id':
                    $query->delServicio($value);
                    break;
                case 'hora_inicio':
                    if (isset($filters['hora_fin'])) {
                        $query->entreHoras($value, $filters['hora_fin']);
                    }
                    break;
                case 'hoy':
                    if ($value) $query->hoy();
                    break;
                case 'proximas':
                    if ($value) $query->proximas();
                    break;
                case 'esta_semana':
                    if ($value) $query->estaSemana();
                    break;
                case 'con_retraso':
                    if ($value) {
                        $minutos = $filters['minutos_retraso'] ?? 15;
                        $query->conRetraso($minutos);
                    }
                    break;
            }
        }

        return $query;
    }

    public static function hasConflict($fecha, $fechaFin, $usuarioId, $excludeId = null)
    {
        $query = self::where('usuarios_id', $usuarioId)
            ->where('estados_id', '!=', 3)
            ->where(function ($q) use ($fecha, $fechaFin) {
                $q->whereBetween('fecha', [$fecha, $fechaFin])
                  ->orWhereBetween('fecha_fin', [$fecha, $fechaFin])
                  ->orWhere(function ($q2) use ($fecha, $fechaFin) {
                      $q2->where('fecha', '<=', $fecha)
                         ->where('fecha_fin', '>=', $fechaFin);
                  });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function calcularFechaFin()
    {
        if (!$this->fecha_fin && $this->fecha && $this->tiempo_estimado) {
            $this->fecha_fin = Carbon::parse($this->fecha)
                ->addMinutes($this->tiempo_estimado);
        }
    }
}