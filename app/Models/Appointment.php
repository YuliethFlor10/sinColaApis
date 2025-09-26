<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Appointment extends Model
{
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        // Información personal del cliente
        'tipo_documento',
        'numero_documento', 
        'nombre',
        'email',
        'fecha_nacimiento',
        'numero_telefono',
        
        // Información del servicio y cita
        'tipo_cita',
        'personal_servicio',
        'fecha_cita',
        'hora_cita',
        'fecha_hora_completa',
        
        // Relaciones y campos del sistema
        'usuarios_id',
        'negocios_id',
        'servicios_id',
        'estados_id',
        'nota',
        'tiempo_estimado',
        'fecha_fin',
        'descripcion_cancel',
        
        // Campo heredado (mantener compatibilidad)
        'atendido_por_id'
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_cita' => 'date',
        'hora_cita' => 'datetime:H:i',
        'fecha_hora_completa' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    protected $allowedFilters = [
        'negocio_id', 'usuario_id', 'estado', 'fecha_cita', 'fecha_inicio', 'fecha_fin', 
        'servicio_id', 'hora_inicio', 'hora_fin', 'hoy', 'proximas', 'esta_semana', 
        'con_retraso', 'minutos_retraso', 'tipo_documento', 'numero_documento', 'email'
    ];

    protected $allowedSorts = [
        'id', 'fecha_cita', 'fecha_hora_completa', 'created_at', 'nombre'
    ];

    protected $allowedIncludes = [
        'user', 'business', 'service', 'status', 'staff'
    ];

    // === MUTATORS Y ACCESSORS ===
    
    // Automáticamente combinar fecha y hora
    public function setFechaCitaAttribute($value)
    {
        $this->attributes['fecha_cita'] = $value;
        $this->updateFechaHoraCompleta();
    }

    public function setHoraCitaAttribute($value)
    {
        $this->attributes['hora_cita'] = $value;
        $this->updateFechaHoraCompleta();
    }

    private function updateFechaHoraCompleta()
    {
        if (isset($this->attributes['fecha_cita']) && isset($this->attributes['hora_cita'])) {
            $fecha = Carbon::parse($this->attributes['fecha_cita']);
            $hora = Carbon::parse($this->attributes['hora_cita']);
            $this->attributes['fecha_hora_completa'] = $fecha->setTime($hora->hour, $hora->minute)->toDateTimeString();
        }
    }

    // === SCOPES ACTUALIZADOS ===

    public function scopeUsuarioId($query, $valor) {
        return $query->where('usuarios_id', $valor);
    }

    public function scopeNegocioId($query, $valor) {
        return $query->where('negocios_id', $valor);
    }

    public function scopeServicioId($query, $valor) {
        return $query->where('servicios_id', $valor);
    }

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

        $estadoNormalizado = mb_strtolower($estado);
        return $query->whereHas('status', function ($q) use ($estadoNormalizado) {
            $q->whereRaw('LOWER(nombre) = ?', [$estadoNormalizado]);
        });
    }

    // Actualizar scopes para usar fecha_cita en lugar de fecha
    public function scopeEnFecha($query, $fecha)
    {
        return $query->whereDate('fecha_cita', $fecha);
    }

    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereDate('fecha_cita', '>=', $fechaInicio)
                     ->whereDate('fecha_cita', '<=', $fechaFin);
    }

    public function scopeHoy($query)
    {
        return $query->whereDate('fecha_cita', now()->format('Y-m-d'));
    }

    public function scopeProximas($query)
    {
        return $query->where('fecha_hora_completa', '>', now());
    }

    public function scopeEstaSemana($query)
    {
        return $query->whereBetween('fecha_cita', [
            now()->startOfWeek()->format('Y-m-d'),
            now()->endOfWeek()->format('Y-m-d')
        ]);
    }

    public function scopeDelServicio($query, $servicioId)
    {
        return $query->where('servicios_id', $servicioId);
    }

    public function scopeEntreHoras($query, $horaInicio, $horaFin)
    {
        return $query->whereTime('hora_cita', '>=', $horaInicio)
                     ->whereTime('hora_cita', '<=', $horaFin);
    }

    public function scopeConRetraso($query, $minutosRetraso = 15)
    {
        return $query->where('fecha_hora_completa', '<', now()->subMinutes($minutosRetraso))
                     ->whereHas('status', function ($q) {
                         $q->whereRaw('LOWER(nombre) = ?', ['confirmada']);
                     });
    }

    // Nuevos scopes para los campos adicionales
    public function scopePorTipoDocumento($query, $tipoDocumento)
    {
        return $query->where('tipo_documento', $tipoDocumento);
    }

    public function scopePorNumeroDocumento($query, $numeroDocumento)
    {
        return $query->where('numero_documento', $numeroDocumento);
    }

    public function scopePorEmail($query, $email)
    {
        return $query->where('email', 'like', '%' . $email . '%');
    }

    public function scopePorNombre($query, $nombre)
    {
        return $query->where('nombre', 'like', '%' . $nombre . '%');
    }

    // === RELACIONES ===

    public function user()
    {
        return $this->belongsTo(User::class, 'usuarios_id');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'atendido_por_id');
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

    // === SCOPE FILTRAR DINÁMICO ACTUALIZADO ===

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

                case 'fecha_cita':
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

                case 'tipo_documento':
                    $query->porTipoDocumento($value);
                    break;

                case 'numero_documento':
                    $query->porNumeroDocumento($value);
                    break;

                case 'email':
                    $query->porEmail($value);
                    break;

                case 'minutos_retraso':
                    // Se maneja en 'con_retraso'
                    break;
            }
        }

        return $query;
    }

    // === MÉTODOS AUXILIARES ===

    public function getNombreCompletoAttribute()
    {
        return $this->nombre;
    }

    public function getFechaHoraFormateadaAttribute()
    {
        return $this->fecha_hora_completa ? 
            Carbon::parse($this->fecha_hora_completa)->format('d/m/Y H:i') : null;
    }

    public function getEsClienteRegistradoAttribute()
    {
        return !is_null($this->usuarios_id);
    }
}