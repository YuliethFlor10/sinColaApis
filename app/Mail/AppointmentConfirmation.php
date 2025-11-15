<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class AppointmentConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $appointment;
    public $confirmationUrl;
    public $cancellationUrl;

    public function __construct(Appointment $appointment)
    {
        // 🔥 Cargar las relaciones necesarias
        $this->appointment = $appointment->load(['business', 'service', 'user']);

        // Generar URLs firmadas (expiran en 48 horas)
        $this->confirmationUrl = URL::temporarySignedRoute(
            'appointments.confirm-email',  // 🔥 Cambié el nombre de la ruta
            now()->addHours(48),
            ['id' => $appointment->id]
        );

        $this->cancellationUrl = URL::temporarySignedRoute(
            'appointments.cancel-email',   // 🔥 Cambié el nombre de la ruta
            now()->addHours(48),
            ['id' => $appointment->id]
        );
    }

    public function build()
    {
        // 🔥 Formatear fecha en español
        $fechaObj = Carbon::parse($this->appointment->fecha);
        $fechaFormateada = $fechaObj->translatedFormat('l, d \de F \de Y'); // Ejemplo: "lunes, 15 de enero de 2025"
        $horaFormateada = $fechaObj->format('h:i A'); // Ejemplo: "05:00 PM"

        // 🔥 Datos seguros con valores por defecto
        $nombreNegocio = $this->appointment->business->nombre ??
                        $this->appointment->business->nombre_negocio ??
                        'MK Nails Salon';

        $servicio = $this->appointment->service->nombre ??
                   $this->appointment->tipo_servicio ??
                   'Servicio';

        return $this->subject('✅ Confirmación de Cita - ' . $nombreNegocio)
                    ->view('emails.appointment-confirmation')
                    ->with([
                        'nombreCliente' => $this->appointment->cliente_nombre ?? 'Cliente',
                        'nombreNegocio' => $nombreNegocio,
                        'servicio' => $servicio,
                        'fecha' => $fechaFormateada,
                        'hora' => $horaFormateada,
                        'personal' => $this->appointment->personal_asignado ?? 'Personal disponible',
                        'nota' => $this->appointment->nota ?? '',
                        'confirmationUrl' => $this->confirmationUrl,
                        'cancellationUrl' => $this->cancellationUrl,
                    ]);
    }
}
