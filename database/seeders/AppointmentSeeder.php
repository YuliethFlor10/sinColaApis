<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointmentSeeder extends Seeder
{
    public function run()
    {
        DB::table('appointments')->insert([
            [
                'usuarios_id' => 1,
                'negocios_id' => 1,
                'servicios_id' => 1, // Manicure
                'estados_id' => 1, // Activa
                'fecha' => Carbon::now()->addDays(1)->setTime(9, 0),
                'fecha_fin' => Carbon::now()->addDays(1)->setTime(10, 0),
                'nota' => 'Manicure clásico con esmaltado'
            ],
            [
                'usuarios_id' => 2,
                'negocios_id' => 1,
                'servicios_id' => 2, // Pedicure
                'estados_id' => 1,
                'fecha' => Carbon::now()->addDays(1)->setTime(11, 0),
                'fecha_fin' => Carbon::now()->addDays(1)->setTime(12, 0),
                'nota' => 'Pedicure spa con masaje'
            ],
            [
                'usuarios_id' => 3,
                'negocios_id' => 1,
                'servicios_id' => 3, // Uñas acrílicas
                'estados_id' => 1,
                'fecha' => Carbon::now()->addDays(2)->setTime(14, 0),
                'fecha_fin' => Carbon::now()->addDays(2)->setTime(16, 0),
                'nota' => 'Aplicación de uñas acrílicas con diseño'
            ],
            [
                'usuarios_id' => 4,
                'negocios_id' => 1,
                'servicios_id' => 4, // Semipermanente
                'estados_id' => 2, // Cancelada
                'fecha' => Carbon::now()->addDays(3)->setTime(15, 0),
                'fecha_fin' => Carbon::now()->addDays(3)->setTime(16, 0),
                'nota' => 'Cita cancelada de esmaltado semipermanente'
            ],
        ]);
    }
}
