# 🔍 REVISIÓN - MÓDULO HORAS EXTRA

**Fecha:** 2025-10-25
**Estado:** ERRORES ENCONTRADOS

---

## 📋 ESTRUCTURA DE TABLA (PROPORCIONADA)

```sql
id                          INT(11)         PRIMARY KEY AUTO_INCREMENT
personal_rut                VARCHAR(20)     NOT NULL
personal_nombre             VARCHAR(255)    NULL
fecha_hora_termino          DATETIME        NOT NULL
motivo                      VARCHAR(50)     NOT NULL
motivo_detalle              TEXT            NULL
autorizado_por_rut          VARCHAR(20)     NOT NULL
autorizado_por_nombre       VARCHAR(255)    NULL
fecha_registro              TIMESTAMP       NOT NULL DEFAULT current_timestamp()
status                      VARCHAR(20)     NOT NULL DEFAULT 'activo'
```

---

## ⚠️ ERRORES IDENTIFICADOS

### ERROR 1: JSDoc documenta campos que NO existen
**Ubicación:** `js/api/horas-extra-api.js` línea 62-66
**Severidad:** 🟡 MODERADO - Documentación falsa

```javascript
// ❌ INCORRECTO - Línea 62-66
@param {number} horasData.personal_id - ID del personal
@param {number} horasData.horas - Cantidad de horas extras
@param {string} horasData.fecha - Fecha de las horas extra (YYYY-MM-DD)
@param {string} horasData.motivo - Motivo de las horas extra
@param {string} horasData.observaciones - Observaciones adicionales
```

**Análisis:**
Según la tabla real, los campos son:
- `personal_rut` (VARCHAR) - NO `personal_id` (INT)
- `personal_nombre` (VARCHAR) - NO se envía, se obtiene de la búsqueda
- `fecha_hora_termino` (DATETIME) - NO `fecha` simple
- `motivo` (VARCHAR) - CORRECTO ✅
- `motivo_detalle` (TEXT) - NO `observaciones`
- `autorizado_por_rut` (VARCHAR) - FALTA en JSDoc
- `autorizado_por_nombre` (VARCHAR) - FALTA en JSDoc

**Impacto:**
- ❌ Documentación NO coincide con código real
- ❌ Usuarios esperarían parámetros diferentes
- ❌ Confusión sobre estructura de datos

---

### ERROR 2: El método create() retorna objeto INCORRECTO
**Ubicación:** `js/api/horas-extra-api.js` línea 87
**Severidad:** 🔴 CRÍTICO - Inconsistencia en respuesta

```javascript
// ❌ INCORRECTO - Línea 87
return result;  // Retorna: { success, data, error }
```

**Análisis:**
Comparar con otros métodos:
- `getAll()` (línea 51): `return result.data || result` ✅ Extrae datos
- `delete()` (línea 109): `return true` ✅ Retorna booleano

**El problema:**
- `getAll()` y `delete()` retornan datos extraídos
- `create()` retorna objeto envuelto de ApiClient
- Inconsistencia en patrón de retorno

**En main.js línea 489:**
```javascript
await horasExtraApi.create(data);  // Espera resultado exitoso
// Pero recibe: { success: true, data: ..., error: null }
// Si esperaba solo los datos, habría error
```

**Impacto:**
- ⚠️ Potencial error si frontend espera estructura diferente
- ⚠️ Inconsistencia con métodos getAll() y delete()

---

### ERROR 3: DELETE usa borrado físico en lugar de lógico
**Ubicación:** `api/horas_extra.php` línea 67
**Severidad:** 🔴 CRÍTICO - Violación de diseño

```php
// ❌ INCORRECTO - Línea 67
$stmt = $conn_acceso->prepare("DELETE FROM horas_extra WHERE id=?");
//                            ^^^^^^^
//                            Borrado FÍSICO (irreversible)
```

**Análisis:**
La tabla TIENE campo `status` para borrado suave:
```sql
status VARCHAR(20) NOT NULL DEFAULT 'activo'
```

**El backend debería:**
```php
// ✅ CORRECTO - Borrado LÓGICO
$stmt = $conn_acceso->prepare("UPDATE horas_extra SET status='inactivo' WHERE id=?");
```

**Impacto:**
- ❌ Registros eliminados PERMANENTEMENTE sin recuperación
- ❌ No hay auditoría de qué se eliminó
- ❌ Viola el patrón de borrado suave implementado en tabla
- ❌ GET ya filtra por `status = 'activo'`, pero esto es inconsistente

---

### ERROR 4: POST no valida autenticación
**Ubicación:** `api/horas_extra.php` línea 1-8
**Severidad:** 🟡 MODERADO - Seguridad

```php
// Falta validación de sesión
require_once 'db_acceso.php';
require_once 'db_personal.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

// ❌ NO HAY validación de $_SESSION['logged_in']
```

**Análisis:**
Comparar con `api/vehiculos.php`:
```php
// ✅ CORRECTO - Valida autenticación
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado. Por favor, inicie sesión.']);
    exit;
}
```

**Impacto:**
- ⚠️ Cualquier usuario podría crear registros de horas extra
- ⚠️ Sin verificación de identidad
- ⚠️ Falta de auditoría

---

### ERROR 5: POST no maneja excepciones de BD correctamente
**Ubicación:** `api/horas_extra.php` línea 30-62
**Severidad:** 🟡 MODERADO - Robustez

```php
// ❌ INCORRECTO - Bind_param incorrecto
$stmt->bind_param(
    "sssssss",  // 7 parámetros tipo STRING
    $persona['rut'],
    $persona['nombre'],
    $data['fecha_hora_termino'],
    $data['motivo'],
    $motivo_detalle,
    $data['autorizado_por_rut'],
    $data['autorizado_por_nombre']
);
```

**Análisis:**
Los tipos deberían ser:
```
personal_rut (VARCHAR)              → s ✅
personal_nombre (VARCHAR)           → s ✅
fecha_hora_termino (DATETIME)       → s (como string) ✅
motivo (VARCHAR)                    → s ✅
motivo_detalle (TEXT)               → s ✅
autorizado_por_rut (VARCHAR)        → s ✅
autorizado_por_nombre (VARCHAR)     → s ✅
```

**El bind_param es CORRECTO, pero:**
- No valida que personal_rut exista
- No valida que autorizado_por_rut exista
- No valida formato de DATETIME

**Impacto:**
- ⚠️ Podrían insertarse RUTs inexistentes
- ⚠️ Sin validación referencial

---

### ERROR 6: Falta validación de sesión en DELETE
**Ubicación:** `api/horas_extra.php` línea 65-80
**Severidad:** 🔴 CRÍTICO - Seguridad

```php
// ❌ NO VALIDA SESIÓN
case 'DELETE':
    $id = $_GET['id'];
    $stmt = $conn_acceso->prepare("DELETE FROM horas_extra WHERE id=?");
    // Ejecuta sin verificar si usuario está autenticado
```

**Análisis:**
Comparar con `api/vehiculos.php`:
```php
// ✅ Valida sesión
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    exit;
}
```

**Impacto:**
- 🔴 CRÍTICO: Cualquiera puede eliminar registros
- 🔴 Sin autenticación = Sin auditoría
- 🔴 Violación de seguridad

---

### ERROR 7: GET no valida sesión
**Ubicación:** `api/horas_extra.php` línea 10-18
**Severidad:** 🔴 CRÍTICO - Seguridad

```php
// ❌ NO VALIDA SESIÓN
case 'GET':
    // Lógica para obtener registros
    $result = $conn_acceso->query("SELECT * FROM horas_extra WHERE status = 'activo'...");
    // Cualquiera puede leer registros de horas extra
```

**Impacto:**
- 🔴 CRÍTICO: Cualquiera puede ver datos privados
- 🔴 Información sensible expuesta

---

### ERROR 8: Inconsistencia en estructura de respuesta POST
**Ubicación:** `api/horas_extra.php` línea 54 vs main.js línea 489
**Severidad:** 🟡 MODERADO

```php
// API devuelve
echo json_encode(['message' => 'Registros de horas extra creados correctamente.']);

// Pero frontend espera
await horasExtraApi.create(data);
// Recibe: { success: true, data: {...}, error: null }
// O: { message: 'Registros creados...' }
```

**Análisis:**
ApiClient envuelve la respuesta automáticamente:
```javascript
// En ApiClient (generalmente)
response = {
    success: true,
    data: responseData,  // Aquí va la respuesta JSON original
    error: null
}
```

**La respuesta será:**
```javascript
{
    success: true,
    data: { message: 'Registros creados...' },
    error: null
}
```

**Frontend en línea 489:**
```javascript
await horasExtraApi.create(data);  // Si no checkea, asume éxito ✅
```

**Impacto:** ✅ Funciona pero no devuelve datos del registro creado

---

## 📊 RESUMEN DE ERRORES

| # | Error | Archivo | Línea | Severidad | Tipo |
|---|-------|---------|-------|-----------|------|
| 1 | Documentación falsa de parámetros | horas-extra-api.js | 62-66 | 🟡 MODERADO | Documentación |
| 2 | create() retorna objeto incorrecto | horas-extra-api.js | 87 | 🔴 CRÍTICO | Inconsistencia |
| 3 | DELETE usa borrado físico en lugar de lógico | horas_extra.php | 67 | 🔴 CRÍTICO | Diseño |
| 4 | POST sin validación de sesión | horas_extra.php | 1-8 | 🟡 MODERADO | Seguridad |
| 5 | POST sin validación de datos | horas_extra.php | 24-31 | 🟡 MODERADO | Validación |
| 6 | DELETE sin validación de sesión | horas_extra.php | 65 | 🔴 CRÍTICO | Seguridad |
| 7 | GET sin validación de sesión | horas_extra.php | 10 | 🔴 CRÍTICO | Seguridad |
| 8 | Inconsistencia en respuesta POST | horas_extra.php | 54 | 🟡 MODERADO | API |

---

## 🔴 PRIORIDAD DE CORRECCIONES

**CRÍTICOS (Deben corregirse INMEDIATAMENTE):**
1. ERROR 7: GET sin validación de sesión
2. ERROR 6: DELETE sin validación de sesión
3. ERROR 3: DELETE usa borrado físico
4. ERROR 2: create() retorna objeto incorrecto

**MODERADOS:**
5. ERROR 4: POST sin validación de sesión
6. ERROR 5: POST sin validación de datos
7. ERROR 8: Inconsistencia respuesta POST
8. ERROR 1: Documentación falsa

---

## 📌 PRÓXIMOS PASOS

Proceder a crear documento de soluciones.

