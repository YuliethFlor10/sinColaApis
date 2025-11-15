<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Cita</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #e91e63;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #e91e63;
            margin: 0;
            font-size: 28px;
        }
        .appointment-details {
            background-color: #f9f9f9;
            border-left: 4px solid #e91e63;
            padding: 20px;
            margin: 20px 0;
        }
        .appointment-details p {
            margin: 10px 0;
            font-size: 16px;
        }
        .appointment-details strong {
            color: #e91e63;
            display: inline-block;
            min-width: 120px;
        }
        .buttons {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 10px 5px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s;
        }
        .btn-confirm {
            background-color: #4CAF50;
            color: white !important;
        }
        .btn-cancel {
            background-color: #f44336;
            color: white !important;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #777;
        }
        .note {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Confirmación de Cita</h1>
            <p>{{ $nombreNegocio }}</p>
        </div>

        <p>Hola <strong>{{ $nombreCliente }}</strong>,</p>
        <p>Tu cita ha sido registrada exitosamente. Por favor, confirma tu asistencia haciendo clic en el botón de abajo.</p>

        <div class="appointment-details">
            <h3 style="margin-top: 0; color: #e91e63;">📋 Detalles de tu Cita</h3>
            <p><strong>Servicio:</strong> {{ $servicio }}</p>
            <p><strong>Fecha:</strong> {{ $fecha }}</p>
            <p><strong>Hora:</strong> {{ $hora }}</p>
            <p><strong>Personal:</strong> {{ $personal }}</p>
            @if($nota)
            <p><strong>Nota:</strong> {{ $nota }}</p>
            @endif
        </div>

        <div class="note">
            <strong>⚠️ Importante:</strong> Por favor confirma tu asistencia antes de 24 horas de la cita. Si no puedes asistir, cancela con anticipación.
        </div>

        <div class="buttons">
            <a href="{{ $confirmationUrl }}" class="btn btn-confirm">
                ✓ Confirmar Asistencia
            </a>
            <a href="{{ $cancellationUrl }}" class="btn btn-cancel">
                ✗ Cancelar Cita
            </a>
        </div>

        <div class="footer">
            <p>Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
            <p>© {{ date('Y') }} {{ $nombreNegocio }}. Todos los derechos reservados.</p>
            <p style="font-size: 10px; color: #999;">
                Los enlaces de confirmación y cancelación expirarán en 48 horas por seguridad.
            </p>
        </div>
    </div>
</body>
</html>
