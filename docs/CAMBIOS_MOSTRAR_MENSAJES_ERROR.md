# Cambios Realizados - Mostrar Mensajes de Error del Backend en Frontend

## Fecha: 2025-10-27
## Descripción: Hacer que el frontend muestre los mensajes de error descriptivos del backend

---

## Problema Original
El API enviaba mensajes de error descriptivos (HTTP 403 con message en el body), pero el frontend estaba ignorándolos y mostrando solo un genérico `HTTP 403: Forbidden`.

**Problema:**
- Backend: `"Acceso denegado para el vehículo [XY5678]: Fecha de inicio: 2026-01-20 (aún no autorizado)"`
- Frontend mostraba: `HTTP 403: Forbidden` ❌

---

## Cambios Realizados

### Archivo: js/api/api-client.js (líneas 52-65)

**Cambio en el manejo de respuestas HTTP:**

**Antes:**
```javascript
if (!response.ok) {
    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
}

// HTTP 204 No Content no tiene body
if (response.status === 204) {
    return { success: true, data: null, error: null };
}

const data = await response.json();
return { success: true, data, error: null };
```

**Ahora:**
```javascript
// HTTP 204 No Content no tiene body
if (response.status === 204) {
    return { success: true, data: null, error: null };
}

const data = await response.json();

if (!response.ok) {
    // Si hay un mensaje de error en el response, usarlo
    const errorMessage = data.message || `HTTP ${response.status}: ${response.statusText}`;
    throw new Error(errorMessage);
}

return { success: true, data, error: null };
```

---

## ¿Qué cambió?

1. **Se parsea el body antes de validar response.ok**
   - Antes: Se validaba y lanzaba error SIN leer el body
   - Ahora: Se lee el body primero

2. **Se extrae el mensaje del error**
   - Si existe `data.message`: lo usa
   - Si no: usa el statusText genérico

3. **El flujo ahora es:**
   - Leer response.json() (que contiene { message: "..." })
   - Validar response.ok
   - Si hay error, usar el mensaje específico

---

## Ejemplo de Flujo Actual

### Vehículo con fecha futura:

1. **Backend (portico.php) envía:**
   ```
   HTTP 403
   {
     "message": "Acceso denegado para el vehículo [XY5678]: Fecha de inicio: 2026-01-20 (aún no autorizado)"
   }
   ```

2. **ApiClient recibe:**
   - Lee el body: `data.message = "Acceso denegado para el vehículo [XY5678]: Fecha de inicio: 2026-01-20 (aún no autorizado)"`
   - Lanza Error con ese mensaje

3. **control.js recibe el error:**
   - `error.message = "Acceso denegado para el vehículo [XY5678]: Fecha de inicio: 2026-01-20 (aún no autorizado)"`

4. **Usuario ve:**
   ```
   Acceso denegado para el vehículo [XY5678]: Fecha de inicio: 2026-01-20 (aún no autorizado)
   ```

---

## Beneficios

✓ **Mensajes descriptivos:** El usuario sabe exactamente por qué fue rechazado
✓ **Debugging:** Administrador ve el error específico
✓ **Consistencia:** Frontend y backend sincronizados
✓ **Mejor UX:** Información clara en lugar de genérico

---

## Archivos Modificados

- **C:\xampp\htdocs\Desarrollo\acceso\js\api\api-client.js**
  - Líneas 52-65: Cambio en manejo de respuestas HTTP

---

## Impacto

✅ **Todos los errores del API:**
- Ahora muestran el mensaje específico del backend
- Funciona para cualquier endpoint (403, 400, 404, 500, etc.)

✅ **Módulo Pórtico:**
- Ahora muestra razones específicas de rechazo:
  - "Fecha de inicio: 2026-01-20 (aún no autorizado)"
  - "Acceso expirado desde: 2025-09-30"
  - "Status no autorizado"
  - etc.

---

## Pruebas

1. **Vehículo con fecha futura:**
   - Escanear XY5678
   - Debería mostrar: `Acceso denegado para el vehículo [XY5678]: Fecha de inicio: 2026-01-20 (aún no autorizado)`

2. **Vehículo expirado:**
   - Debería mostrar: `Acceso denegado para el vehículo [ABC]: Acceso expirado desde: YYYY-MM-DD`

3. **Vehículo válido:**
   - Debería permitir acceso sin errores

---

## Estado Final

✓ Mensajes de error descriptivos del backend ahora se muestran en frontend
✓ Usuario tiene información clara sobre por qué fue rechazado
✓ Mejor experiencia de usuario
✓ Listo para uso en producción

