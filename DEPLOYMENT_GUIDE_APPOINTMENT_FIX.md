# 🚀 GUÍA DE DESPLIEGUE - CORRECCIONES APPOINTMENT

## 📋 PASO A PASO PARA APLICAR LAS CORRECCIONES

### **PASO 1: Asegurar que estés en la rama correcta**
```bash
git branch -v
# Deberías ver: * Yuli
```

### **PASO 2: Ejecutar las nuevas migraciones**
```bash
# Desde la raíz del proyecto
php artisan migrate

# Verifica el resultado
php artisan migrate:status
```

**Migraciones que se ejecutarán:**
- `2025_11_14_000001_add_agendas_id_to_appointments_table`
- `2025_11_14_000002_add_default_to_tipo_identificacion_id_users_table`

### **PASO 3: Limpiar caché (recomendado)**
```bash
php artisan cache:clear
php artisan config:clear
```

### **PASO 4: Verificar cambios en archivos**

Archivos modificados:
- ✅ `app/Models/Appointment.php` - Relación + Fillable
- ✅ `app/Http/Controllers/AppointmentController.php` - Métodos actualizados
- ✅ `database/migrations/2025_11_14_000001_*` - Nueva migration
- ✅ `database/migrations/2025_11_14_000002_*` - Correción migration

### **PASO 5: Testing de Endpoints**

#### **Test 1: GET - Listar citas completas**
```bash
curl -X GET "http://localhost:8000/api/appointments" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Respuesta esperada:** Array de citas CON relaciones (user, business, service, status, agenda)

#### **Test 2: GET - Obtener cita específica**
```bash
curl -X GET "http://localhost:8000/api/appointments/1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Respuesta esperada:** Cita individual CON relaciones completas

#### **Test 3: POST - Crear nueva cita (SIN error 500)**
```bash
curl -X POST "http://localhost:8000/api/appointments" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "nombre": "Juan García López",
    "email": "juan.garcia@example.com",
    "tipo_documento": "CC",
    "numero_documento": "1234567890",
    "fecha_nacimiento": "1990-05-15",
    "numero_telefono": "3001234567",
    "tipo_cita": "Corte cabello",
    "personal_servicio": "Carlos",
    "fecha_cita": "2025-11-15",
    "hora_cita": "10:00",
    "negocios_id": 1,
    "servicios_id": 2,
    "agendas_id": 1,
    "nota": "Cliente especial"
  }'
```

**Respuesta esperada:** 
- ✅ Status 201 (Created)
- ✅ Cita creada con TODAS las relaciones
- ✅ Usuario nuevo creado automáticamente sin error

#### **Test 4: POST - Crear cita (SIN agendas_id - debe funcionar)**
```bash
curl -X POST "http://localhost:8000/api/appointments" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "nombre": "María González",
    "email": "maria.gonzalez@example.com",
    "tipo_documento": "CC",
    "numero_documento": "0987654321",
    "fecha_nacimiento": "1995-03-20",
    "numero_telefono": "3009876543",
    "tipo_cita": "Corte cabello",
    "personal_servicio": "Ana",
    "fecha_cita": "2025-11-16",
    "hora_cita": "14:00",
    "negocios_id": 1,
    "servicios_id": 2
  }'
```

**Respuesta esperada:** 
- ✅ Status 201 (Created)
- ✅ agendas_id será null (permitido)
- ✅ Sin error 500

### **PASO 6: Verificar en base de datos (opcional)**

```sql
-- Ver nuevas columnas en appointments
DESCRIBE appointments;
-- Deberías ver: agendas_id | bigint unsigned | YES | MUL | NULL

-- Ver usuarios creados automáticamente
SELECT id, nombres, apellidos, email, tipo_identificacion_id, identificacion, estados_id, roles_id 
FROM users 
WHERE roles_id = 3 
ORDER BY creado_en DESC 
LIMIT 5;
```

---

## ✅ CHECKLIST DE VALIDACIÓN

- [ ] Migraciones ejecutadas sin errores
- [ ] Cache limpiado
- [ ] GET /api/appointments devuelve citas completas
- [ ] GET /api/appointments/{id} devuelve cita completa
- [ ] POST /api/appointments crea cita sin error 500
- [ ] POST devuelve todas las relaciones incluidas
- [ ] Usuario nuevo creado automáticamente
- [ ] Campo agendas_id es opcional (null si no se pasa)
- [ ] Otros módulos siguen funcionando normalmente

---

## 🔄 ROLLBACK (Si necesitas revertir)

```bash
php artisan migrate:rollback --step=2

# Esto revierte las 2 últimas migraciones
```

---

## 📞 TROUBLESHOOTING

### Error: "SQLSTATE[HY000]: General error: 1364"
- **Causa:** Migraciones no ejecutadas
- **Solución:** `php artisan migrate`

### Error: "Relation 'agenda' doesn't exist"
- **Causa:** Modelo no actualizado
- **Solución:** Verificar que `public function agenda()` existe en Appointment.php

### Error: "Column 'agendas_id' doesn't exist"
- **Causa:** Primera migration no ejecutada
- **Solución:** `php artisan migrate`

### GET devuelve citas SIN relaciones
- **Causa:** Cache no limpiado
- **Solución:** `php artisan cache:clear`

---

## 📊 RESUMEN DE CAMBIOS PARA REVISAR

```php
// 1. Verifica en Appointment.php
public function agenda()
{
    return $this->belongsTo(Agenda::class, 'agendas_id');
}

// 2. Verifica en AppointmentController.php store()
'agendas_id' => $validated['agendas_id'] ?? null,
'tipo_identificacion_id' => 1,
'identificacion' => $validated['numero_documento'],
'clave' => bcrypt('temp_' . rand(100000, 999999)),
'estados_id' => 1,

// 3. Verifica en todos los with()
->with(['user', 'business', 'status', 'service', 'agenda'])
```

