<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class AppointmentConfirmation extends Mailable  // ✅ Nombre correcto
{
    use Queueable, SerializesModels;

    public $appointment;
    public $confirmationUrl;
    public $cancellationUrl;

    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;

        // Generar URLs firmadas
        $this->confirmationUrl = URL::temporarySignedRoute(
            'appointments.confirm.email',
            now()->addHours(48),
            ['id' => $appointment->id]
        );

        $this->cancellationUrl = URL::temporarySignedRoute(
            'appointments.cancel.email',
            now()->addHours(48),
            ['id' => $appointment->id]
        );
    }

    public function build()
    {
        return $this->subject('Confirmación de Cita - ' . $this->appointment->business->nombre)
                    ->view('emails.appointment-confirmation')
                    ->with([
                        'nombreCliente' => $this->appointment->cliente_nombre,
                        'nombreNegocio' => $this->appointment->business->nombre,
                        'servicio' => $this->appointment->service->nombre,
                        'fecha' => $this->appointment->fecha->format('d/m/Y'),
                        'hora' => $this->appointment->fecha->format('h:i A'),
                        'personal' => $this->appointment->personal_asignado,
                        'nota' => $this->appointment->nota,
                    ]);
    }
}
