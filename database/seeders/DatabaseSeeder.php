<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Crear estados necesarios
        DB::table('statuses')->insert([
            ['id' => 1, 'nombre' => 'Activo', 'descripcion' => 'Estado activo', 'grupo' => 'general', 'creado_en' => now(), 'actualizado_en' => now()],
            ['id' => 2, 'nombre' => 'Inactivo', 'descripcion' => 'Estado inactivo', 'grupo' => 'general', 'creado_en' => now(), 'actualizado_en' => now()],
        ]);

        // 2. Crear roles
        DB::table('roles')->insert([
            ['id' => 1, 'nombre' => 'Administrador', 'descripcion' => 'Rol admin', 'estados_id' => 1, 'creado_en' => now(), 'actualizado_en' => now()],
            ['id' => 2, 'nombre' => 'Cliente', 'descripcion' => 'Rol cliente', 'estados_id' => 1, 'creado_en' => now(), 'actualizado_en' => now()],
            ['id' => 3, 'nombre' => 'Empleado', 'descripcion' => 'Rol empleado', 'estados_id' => 1, 'creado_en' => now(), 'actualizado_en' => now()],
        ]);

        // 3. Crear categorías
        DB::table('categories')->insert([
            [
                'id' => 1,
                'nombre' => 'Cédula',
                'abreviatura' => 'CC',
                'descripcion' => 'Cédula de ciudadanía',
                'grupo' => 'identificacion',
                'estados_id' => 1,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
            [
                'id' => 2,
                'nombre' => 'NIT',
                'abreviatura' => 'NIT',
                'descripcion' => 'Número de Identificación Tributaria',
                'grupo' => 'identificacion',
                'estados_id' => 1,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
            // Puedes agregar más categorías si lo deseas
        ]);

        // 4. Crear planes
        DB::table('plans')->insert([
            [
                'id' => 1,
                'nombre' => 'Plan Básico',
                'caracteristicas' => json_encode([
                    'limite_usuarios' => 10,
                    'soporte' => 'Email',
                    'acceso' => 'Básico',
                ]),
                'descuentos' => 0,
                'estados_id' => 1,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
            [
                'id' => 2,
                'nombre' => 'Plan Premium',
                'caracteristicas' => json_encode([
                    'limite_usuarios' => 50,
                    'soporte' => 'Teléfono y Email',
                    'acceso' => 'Completo',
                ]),
                'descuentos' => 10,
                'estados_id' => 1,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
        ]);

        // 5. Crear negocios
        DB::table('businesses')->insert([
            [
                'id' => 1,
                'nombre' => 'Negocio Ejemplo',
                'nit' => '123456789',
                'direccion' => 'Calle 456',
                'telefono' => '1234567',
                'estados_id' => 1,
                'tipo_servicio_id' => 1,  // Asumiendo categoría 1 para tipo_servicio
                'planes_id' => 1,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
        ]);

        // 6. Crear usuario de ejemplo usando factory
        User::factory()->create([
            'nombres' => 'Test',
            'apellidos' => 'User',
            'email' => 'test@example.com',
            'tipo_identificacion_id' => 1,
            'identificacion' => '123456789',
            'roles_id' => 1,
            'negocios_id' => 1,
            'estados_id' => 1,
            'clave' => bcrypt('password'), // Recuerda cambiar la contraseña
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);
    }
}
