# Diagnóstico y Corrección - Módulo Pórtico

## Problema Identificado

El módulo pórtico no estaba funcionando correctamente debido a un **desajuste entre las expectativas del cliente JavaScript y las respuestas del servidor PHP**.

### Raíz del Problema

Los métodos en `access-logs-api.js` (`logPortico`, `logClarified`, `logManual`) esperaban que el servidor devuelva un objeto con un wrapper `success`:

```javascript
if (!result.success) {
    throw new Error(result.error || 'Error al registrar acceso por pórtico.');
}
```

Sin embargo, los archivos PHP devuelven **directamente los datos sin este wrapper**:

- **`api/portico.php`** (línea 322): `json_encode($response_data)` - devuelve directamente los datos
- **`api/log_clarified_access.php`** (línea 83): `json_encode([...])` - devuelve directamente los datos
- **`api/log_access.php`** (línea 322): `json_encode(array_merge(...))` - devuelve directamente los datos

## Estructura de Respuestas del Servidor

### `portico.php` - Acceso inteligente

**Caso de éxito (201):**
```json
{
  "id": 5,
  "type": "personal",
  "action": "entrada",
  "name": "Sargento Juan González López",
  "photoUrl": "url/a/foto.jpg",
  "message": "Acceso 'entrada' para Sargento Juan González López registrado correctamente."
}
```

**Caso que requiere aclaración (200):**
```json
{
  "action": "clarification_required",
  "person_details": {
    "id": 5,
    "name": "Sargento Juan González López",
    "rut": "12345678-9",
    "photoUrl": "url/a/foto.jpg",
    "unidad": "Infantería",
    "es_residente": false
  }
}
```

**Error (403/404/500):**
```json
{
  "message": "Error description"
}
```

### `log_clarified_access.php` - Acceso con aclaración

**Éxito (201):**
```json
{
  "message": "Ingreso para Sargento Juan González López registrado con motivo: servicio",
  "name": "Sargento Juan González López",
  "id": 5,
  "type": "personal",
  "action": "entrada",
  "photoUrl": "url/a/foto.jpg"
}
```

### `log_access.php` - Acceso manual

**Éxito (201):**
```json
{
  "message": "Acceso registrado: entrada",
  "action": "entrada",
  "personalName": "Sargento Juan González López",
  "personalRut": "12345678-9",
  "personalPhotoUrl": "url/a/foto.jpg",
  "patente": "AA1234",
  "nombre": "Juan Pérez"
}
```

## Soluciones Aplicadas

### 1. Corrección de `logPortico()` en `access-logs-api.js`

**Antes:**
```javascript
if (!result.success) {
    throw new Error(result.error || 'Error al registrar acceso por pórtico.');
}
```

**Después:**
```javascript
// El servidor PHP devuelve directamente los datos sin wrapper 'success'
// Puede devolver:
// 1. { action: 'clarification_required', person_details: {...} } - Requiere aclaración
// 2. { id, type, action, name, message, ... } - Acceso registrado
// 3. { message: 'Error...' } - Error (ya manejado por ApiClient)

if (!result) {
    throw new Error('Error al registrar acceso por pórtico: respuesta vacía.');
}

return result;
```

### 2. Corrección de `logClarified()` en `access-logs-api.js`

**Cambio similar:**
- Removido check de `result.success`
- Agregada validación de respuesta vacía
- Documentados los formatos de respuesta esperados

### 3. Corrección de `logManual()` en `access-logs-api.js`

**Cambio similar:**
- Removido check de `result.success`
- Agregada validación de respuesta vacía
- Documentados los formatos de respuesta esperados

### 4. Creación de módulo `portico-api.js` (opcional)

Se creó un módulo independiente `js/api/portico-api.js` siguiendo el patrón de los otros módulos API, aunque actualmente se usa `accessLogsApi.logPortico()` en `main.js`.

**Ubicación:** `C:\xampp\htdocs\Desarrollo\acceso\js\api\portico-api.js`

## Pruebas Recomendadas

1. **Escanear un RUT de personal válido** en el pórtico
   - Verificar que se muestre la información correctamente
   - Confirmar que se registre la entrada/salida

2. **Escanear un personal que requiera aclaración**
   - Verificar que aparezca el modal de aclaración
   - Confirmar que se registre con la razón seleccionada

3. **Escanear una patente de vehículo válida**
   - Verificar que se registre correctamente
   - Confirmar que se muestre el propietario si existe

4. **Errores esperados:**
   - Escanear un RUT inexistente → Error 404
   - Escanear un vehículo sin autorización → Error 403
   - Escanear una visita en lista negra → Error 403

## Archivos Modificados

- `js/api/access-logs-api.js` - Correcciones en `logPortico()`, `logClarified()`, `logManual()`
- `js/api/portico-api.js` - Nuevo módulo (creado como referencia)

## Notas Importantes

- El `ApiClient` ya maneja correctamente los errores HTTP
- Los errores PHP (403, 404, 500) son convertidos a excepciones por `ApiClient`
- La validación de respuesta vacía previene fallos silenciosos
- El formato de respuesta directo (sin wrapper `success`) es consistente en todas las APIs del sistema

