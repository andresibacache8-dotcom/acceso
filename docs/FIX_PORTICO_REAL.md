# Corrección Real - Módulo Pórtico

## 🔴 Problema Real Encontrado

El problema **NO estaba en las respuestas del servidor PHP**, sino en cómo el `ApiClient` estaba envolviendo las respuestas.

### La Estructura Real

**Lo que PHP devuelve directamente:**
```json
{
  "id": 5,
  "type": "personal",
  "action": "entrada",
  "name": "Sargento Juan González López",
  "photoUrl": "url/foto.jpg",
  "message": "Acceso registrado..."
}
```

**Lo que ApiClient retorna (api-client.js línea 62):**
```javascript
return { success: true, data, error: null };

// Resultado final:
{
  success: true,
  data: {
    id: 5,
    type: "personal",
    action: "entrada",
    name: "Sargento Juan González López",
    photoUrl: "url/foto.jpg",
    message: "Acceso registrado..."
  },
  error: null
}
```

## ❌ Error en el Código Original

Los métodos en `access-logs-api.js` estaban tratando el resultado como si fuera directamente el objeto PHP:

```javascript
// ❌ INCORRECTO
const result = await this.client.post(endpoint, data);
if (!result.success) {  // Estaba buscando result.success (correcto)
    throw new Error(result.error || 'Error...');
}
return result;  // ❌ Retornaba { success, data, error } en lugar de solo { data }
```

Pero luego en `main.js` (línea 568):
```javascript
if (result.action === 'clarification_required') {  // ❌ result.action NO EXISTE
// Porque result era { success: true, data: { action: ... }, error: null }
// El action estaba en result.data.action
```

## ✅ Solución Correcta

Acceder a `result.data` después de validar `result.success`:

```javascript
// ✅ CORRECTO
const result = await this.client.post(endpoint, data);

if (!result.success) {
    throw new Error(result.error || 'Error al registrar...');
}

if (!result.data) {
    throw new Error('Respuesta vacía');
}

return result.data;  // ✅ Retorna solo los datos del PHP
```

Ahora en `main.js`:
```javascript
const result = await accessLogsApi.logPortico(targetId);
// result es ahora: { id: 5, type: "personal", action: "entrada", name: "...", ... }

if (result.action === 'clarification_required') {  // ✅ FUNCIONA
    // result.action existe directamente
}
```

## 📝 Archivos Corregidos

### `js/api/access-logs-api.js`

Se corrigieron 3 métodos:

1. **`logPortico()` (línea 228)**
   - Antes: `return result;` ❌
   - Después: `return result.data;` ✅

2. **`logClarified()` (línea 278)**
   - Antes: `return result;` ❌
   - Después: `return result.data;` ✅

3. **`logManual()` (línea 167)**
   - Antes: `return result;` ❌
   - Después: `return result.data;` ✅

## 🔄 Flujo Completo Correcto

```
1. main.js llama accessLogsApi.logPortico("12345678")
   ↓
2. logPortico() llama this.client.post(endpoint, { id: "12345678" })
   ↓
3. ApiClient.post() hace fetch y retorna { success: true, data: {...}, error: null }
   ↓
4. logPortico() valida success: true ✓
   ↓
5. logPortico() retorna result.data (solo los datos del PHP)
   ↓
6. main.js recibe { id: 5, type: "personal", action: "entrada", ... }
   ↓
7. main.js accede directamente a result.action ✓
```

## ✨ Cambios Realizados

```diff
// access-logs-api.js - logPortico()
- return result;
+ if (!result.success) throw...
+ if (!result.data) throw...
+ return result.data;

// access-logs-api.js - logClarified()
- return result;
+ if (!result.success) throw...
+ if (!result.data) throw...
+ return result.data;

// access-logs-api.js - logManual()
- return result;
+ if (!result.success) throw...
+ if (!result.data) throw...
+ return result.data;
```

## 🧪 Ahora Debería Funcionar

✅ Escaneo en pórtico registra entrada/salida
✅ Se carga la tabla de logs correctamente
✅ Se muestra feedback visual con foto y nombre
✅ Casos de aclaración funcionan correctamente
✅ Errores se manejan y muestran correctamente

