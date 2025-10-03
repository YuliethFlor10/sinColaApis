# API Endpoints para Customizations - Angular Integration

## Configuración Base
- **Base URL**: `http://localhost:8000/api` (ajustar según tu configuración)
- **Autenticación**: Sanctum Token (Bearer Token)
- **CORS**: Configurado para `http://localhost:4200` y `http://127.0.0.1:4200`

## Endpoints Disponibles

### 1. Obtener Personalización por Business ID
```
GET /api/customizations/business/{businessId}
```
**Headers**: `Authorization: Bearer {token}`
**Respuesta**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "negocios_id": 1,
    "nombre_comercial": "Mi Negocio",
    "eslogan": "El mejor servicio",
    "descripcion_negocio": "Descripción del negocio",
    "facebook_url": "https://facebook.com/minegocio",
    "instagram_url": "https://instagram.com/minegocio",
    "whatsapp_numero": "+573001234567",
    "texto_seguir_redes": "Síguenos en nuestras redes sociales",
    "acepta_efectivo": true,
    "acepta_tarjeta": true,
    "acepta_nequi": false,
    "acepta_transferencia": false,
    "texto_metodos_pago": "Métodos de pago aceptados",
    "logo_empresa": "customizations/1234567890_logo_empresa.jpg",
    "color_fondo_branding": "#f8d7da",
    "color_letra_branding": "#333333",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z",
    "business": {
      "id": 1,
      "name": "Mi Negocio"
    }
  },
  "message": "Personalización del negocio obtenida exitosamente"
}
```

### 2. Crear Nueva Personalización
```
POST /api/customizations
```
**Headers**: 
- `Authorization: Bearer {token}`
- `Content-Type: multipart/form-data` (para archivos)

**Body** (FormData):
```javascript
const formData = new FormData();
formData.append('negocios_id', businessId);
formData.append('nombre_comercial', 'Mi Negocio');
formData.append('eslogan', 'El mejor servicio');
formData.append('descripcion_negocio', 'Descripción del negocio');
formData.append('facebook_url', 'https://facebook.com/minegocio');
formData.append('instagram_url', 'https://instagram.com/minegocio');
formData.append('whatsapp_numero', '+573001234567');
formData.append('texto_seguir_redes', 'Síguenos en nuestras redes sociales');
formData.append('acepta_efectivo', 'true');
formData.append('acepta_tarjeta', 'true');
formData.append('acepta_nequi', 'false');
formData.append('acepta_transferencia', 'false');
formData.append('texto_metodos_pago', 'Métodos de pago aceptados');
formData.append('color_fondo_branding', '#f8d7da');
formData.append('color_letra_branding', '#333333');
formData.append('logo_empresa', file); // Archivo opcional
```

### 3. Actualizar Personalización
```
PUT /api/customizations/{id}
PATCH /api/customizations/{id}
```
**Headers**: 
- `Authorization: Bearer {token}`
- `Content-Type: multipart/form-data` (para archivos)

### 4. Eliminar Personalización
```
DELETE /api/customizations/{id}
```
**Headers**: `Authorization: Bearer {token}`

## Campos Disponibles

### Información del Negocio
- `negocios_id` (integer, required) - ID del negocio
- `nombre_comercial` (string, max: 200) - Nombre comercial del negocio
- `eslogan` (string, max: 300) - Eslogan del negocio
- `descripcion_negocio` (text) - Descripción del negocio

### Redes Sociales
- `facebook_url` (url, max: 500) - URL de Facebook
- `instagram_url` (url, max: 500) - URL de Instagram
- `whatsapp_numero` (string, max: 20, formato: +1234567890) - Número de WhatsApp
- `texto_seguir_redes` (string, max: 100) - Texto para seguir redes sociales

### Métodos de Pago
- `acepta_efectivo` (boolean) - Acepta pagos en efectivo
- `acepta_tarjeta` (boolean) - Acepta pagos con tarjeta
- `acepta_nequi` (boolean) - Acepta pagos con Nequi
- `acepta_transferencia` (boolean) - Acepta transferencias bancarias
- `texto_metodos_pago` (string, max: 200) - Texto descriptivo de métodos de pago

### Archivos y Colores
- `logo_empresa` (file, image, max: 2MB) - Logo de la empresa
- `color_fondo_branding` (string, formato: #RRGGBB) - Color de fondo del branding
- `color_letra_branding` (string, formato: #RRGGBB) - Color de letra del branding

## Valores por Defecto

Si no existe personalización para un negocio, se crea automáticamente con estos valores:

```json
{
  "color_fondo_branding": "#f8d7da",
  "color_letra_branding": "#333333",
  "texto_seguir_redes": "Síguenos en nuestras redes sociales",
  "acepta_efectivo": true,
  "acepta_tarjeta": true,
  "acepta_nequi": false,
  "acepta_transferencia": false,
  "texto_metodos_pago": "Métodos de pago aceptados"
}
```

## Códigos de Respuesta

- `200`: Operación exitosa
- `201`: Recurso creado exitosamente
- `404`: Recurso no encontrado
- `422`: Error de validación
- `500`: Error interno del servidor

## Ejemplo de Uso en Angular

```typescript
// Obtener personalización
const response = await this.http.get(`/api/customizations/business/${businessId}`).toPromise();

// Crear/Actualizar personalización
const formData = new FormData();
formData.append('negocios_id', businessId);
formData.append('nombre_comercial', 'Mi Negocio');
formData.append('eslogan', 'El mejor servicio');
formData.append('color_fondo_branding', '#f8d7da');
formData.append('color_letra_branding', '#333333');
if (logoFile) {
  formData.append('logo_empresa', logoFile);
}

const response = await this.http.post('/api/customizations', formData).toPromise();
```

## Notas Importantes

1. **Autenticación**: Todas las rutas requieren autenticación Sanctum
2. **Archivos**: Los archivos se almacenan en `storage/app/public/customizations/`
3. **Colores**: Deben estar en formato hexadecimal (#RRGGBB)
4. **WhatsApp**: El número debe incluir código de país (+573001234567)
5. **URLs**: Las URLs de redes sociales deben ser válidas
6. **Unicidad**: Solo puede existir una personalización por negocio
