# 🔍 REVISIÓN - MÓDULO ACCESS LOGS

**Fecha:** 2025-10-25
**Estado:** ERRORES ENCONTRADOS

---

## 📋 ESTRUCTURA DE TABLA `access_logs` (PROPORCIONADA)

```sql
id                  INT(11)         PRIMARY KEY AUTO_INCREMENT
log_time            TIMESTAMP       NOT NULL DEFAULT current_timestamp()
target_id           INT(11)         NOT NULL (Índice)
target_type         ENUM(...)       NOT NULL
name                VARCHAR(255)    NULL
action              ENUM('entrada', 'salida') NOT NULL
status_message      VARCHAR(255)    NULL
motivo              VARCHAR(255)    NULL
log_status          ENUM('activo', 'cancelado') NOT NULL DEFAULT 'activo'
punto_acceso        VARCHAR(20)     NOT NULL DEFAULT 'desconocido'
```

---

## ⚠️ ERRORES IDENTIFICADOS

### ERROR 1: Falta validación de sesión en log_access.php
**Ubicación:** `api/log_access.php` líneas 1-10
**Severidad:** 🔴 CRÍTICO - Seguridad

```php
// ❌ INCORRECTO - Sin validación de sesión
<?php
require_once 'db_acceso.php';
require_once 'db_personal.php';

ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
```

**Problema:**
- Cualquiera puede acceder sin autenticación
- No hay validación de usuario logueado
- Datos de acceso expuestos públicamente

**Impacto:**
- 🔴 CRÍTICO: Acceso sin autenticación a registros de acceso
- 🔴 CRÍTICO: Vulnerabilidad de privacidad

---

### ERROR 2: Falta validación de sesión en log_clarified_access.php
**Ubicación:** `api/log_clarified_access.php` líneas 1-10
**Severidad:** 🔴 CRÍTICO - Seguridad

```php
// ❌ INCORRECTO - Sin validación de sesión
<?php
require_once 'db_acceso.php';
require_once 'db_personal.php';

ini_set('display_errors', 1);  // ⚠️ También está activado display_errors en producción
error_reporting(E_ALL);

header('Content-Type: application/json');
```

**Impacto:**
- 🔴 CRÍTICO: Cualquiera puede registrar accesos aclarados
- 🟡 MODERADO: display_errors y error_reporting en producción (leak de información)

---

### ERROR 3: Falta CORS y preflight en log_access.php
**Ubicación:** `api/log_access.php` líneas 1-10
**Severidad:** 🟡 MODERADO - Configuración

```php
// ❌ Sin headers CORS ni OPTIONS
header('Content-Type: application/json');
// Falta:
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type, Authorization');
// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }
```

**Impacto:**
- 🟡 MODERADO: CORS no configurado
- 🟡 MODERADO: Preflight requests no soportadas

---

### ERROR 4: Falta CORS y preflight en log_clarified_access.php
**Ubicación:** `api/log_clarified_access.php` líneas 1-10
**Severidad:** 🟡 MODERADO - Configuración

**Mismo problema que ERROR 3**

---

### ERROR 5: POST en log_access.php sin validación de sesión
**Ubicación:** `api/log_access.php` líneas 219-336
**Severidad:** 🔴 CRÍTICO - Seguridad

```php
case 'POST':
    // ❌ Sin validación de sesión
    $data = json_decode(file_get_contents('php://input'), true);
    // Cualquiera puede registrar accesos
```

**Impacto:**
- 🔴 CRÍTICO: Cualquiera puede registrar logs de acceso falsos

---

### ERROR 6: DELETE en log_access.php sin validación de sesión
**Ubicación:** `api/log_access.php` líneas 339-360
**Severidad:** 🔴 CRÍTICO - Seguridad

```php
case 'DELETE':
    // ❌ Sin validación de sesión
    $log_id = intval($_GET['id']);
    // Cualquiera puede cancelar logs
```

**Impacto:**
- 🔴 CRÍTICO: Cualquiera puede cancelar registros de acceso
- 🔴 CRÍTICO: Auditoría comprometida

---

### ERROR 7: JSDoc de access-logs-api.js documenta parámetros incorrectamente
**Ubicación:** `js/api/access-logs-api.js` líneas 199-227
**Severidad:** 🟡 MODERADO - Documentación

```javascript
// ❌ INCORRECTO - logPortico documenta mal
@param {number|string} id - ID o RUT de la persona/vehículo

// Pero en realidad PHP espera:
// - Una búsqueda muy compleja que invoca portico.php
// - NO está implementado en log_access.php
// - Se usa en access-logs-api.js pero NO existe portico.php en los archivos revisados
```

**Problema:**
- El método `logPortico()` hace POST a `portico.php`
- No se proporcionó estructura de `portico.php`
- No se revisa si ese archivo existe o está correctamente implementado

**Impacto:**
- 🟡 MODERADO: Dependencia en archivo no revisado

---

### ERROR 8: JSDoc de logClarified documenta parámetros con campos que NO existen en tabla
**Ubicación:** `js/api/access-logs-api.js` líneas 261-308
**Severidad:** 🟡 MODERADO - Documentación

```javascript
// ❌ INCORRECTO - Documenta campos no existentes en tabla
@param {Object} data - Datos de la aclaración
@param {number} data.person_id - ID del personal
@param {string} data.reason - Razón: 'servicio', 'visita_familiar', 'otro'
@param {string} [data.details] - Detalles adicionales (opcional)
```

**Análisis:**
- La tabla `access_logs` tiene campos: `motivo`, `status_message`, `name`, NO `reason`
- JSDoc usa `reason`, pero PHP mapea a diferentes campos internamente

**Impacto:**
- 🟡 MODERADO: API esperaría campo `reason` que luego se mapea internamente

---

### ERROR 9: POST en log_clarified_access.php sin validación de datos
**Ubicación:** `api/log_clarified_access.php` líneas 23-30
**Severidad:** 🟡 MODERADO - Validación

```php
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['person_id']) || !isset($data['reason'])) {
    send_error(400, 'Datos de entrada inválidos.');
}

// ✅ Validación está presente, pero:
// ❌ No valida que $details sea string válido
// ❌ No valida que $reason tenga valores conocidos
```

**Impacto:**
- 🟡 MODERADO: Validación incompleta de parámetros

---

### ERROR 10: display_errors activo en log_clarified_access.php
**Ubicación:** `api/log_clarified_access.php` línea 6-7
**Severidad:** 🟡 MODERADO - Configuración

```php
ini_set('display_errors', 1);  // ❌ EN PRODUCCIÓN
error_reporting(E_ALL);        // ❌ EN PRODUCCIÓN
```

**Problema:**
- Expone detalles internos de errores
- Comparte información sensible (ruta archivos, stack trace, etc.)
- Violación de seguridad

**Impacto:**
- 🟡 MODERADO: Leak de información sensible

---

### ERROR 11: log_access.php desactiva display_errors pero no sigue estándar
**Ubicación:** `api/log_access.php` líneas 6-7
**Severidad:** 🟡 MODERADO - Inconsistencia

```php
ini_set('display_errors', 0);  // ✅ Correcto
error_reporting(0);            // ⚠️ Peligroso - suprime TODOS los errores

// Debería ser:
// error_reporting(E_ALL);
// ini_set('display_errors', 0);
// ini_set('log_errors', 1);
```

**Impacto:**
- 🟡 MODERADO: Errores silenciados sin logging

---

### ERROR 12: POST en log_access.php no valida empresa_empleado
**Ubicación:** `api/log_access.php` líneas 219-336
**Severidad:** 🟡 MODERADO - Validación

```php
// ❌ FALTA: Case para 'empresa_empleado'
if ($target_type === 'personal') { ... }
else if ($target_type === 'vehiculo') { ... }
else if ($target_type === 'visita') { ... }
else if ($target_type === 'empresa_empleado') { ... }
// ✅ SÍ existe

// Pero en GET:
// ✅ SÍ existe validación (línea 164-192)
```

**Impacto:**
- ✅ Está implementado, sin problema

---

### ERROR 13: POST en log_access.php no guarda campos requeridos
**Ubicación:** `api/log_access.php` línea 329
**Severidad:** 🔴 CRÍTICO - Datos Faltantes

```php
// ❌ INCORRECTO - Falta grabar campos
$stmt_insert = $conn_acceso->prepare("INSERT INTO access_logs (target_id, target_type, action, status_message, punto_acceso) VALUES (?, ?, ?, ?, ?)");

// Tabla tiene 10 campos, INSERT usa 5:
// ✅ target_id
// ✅ target_type
// ❌ name (VARCHAR 255, NULL) - NO se graba
// ✅ action
// ✅ status_message
// ❌ motivo (VARCHAR 255, NULL) - NO se graba
// ❌ log_status (DEFAULT 'activo') - usa default, pero no visible
// ✅ punto_acceso
// ❌ log_time (TIMESTAMP DEFAULT) - usa default automático, OK

// Campos que se graban:
// INSERT INTO access_logs (target_id, target_type, action, status_message, punto_acceso)
//                        ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
//                        Faltan: name, motivo, log_status (aunque default)
```

**Análisis:**
- La tabla espera que se grabe `name` (nombre de la persona)
- PHP construye respuesta con `personalName`, `patente`, `nombre`, etc.
- Pero NO graba `name` en la tabla
- Esto causa inconsistencia: la tabla access_logs no tiene el nombre

**Impacto:**
- 🔴 CRÍTICO: Campo `name` nunca se graba
- 🔴 CRÍTICO: Campo `motivo` nunca se graba
- Auditoría incompleta: registros sin nombres

---

### ERROR 14: GET en log_access.php no obtiene campos de tabla
**Ubicación:** `api/log_access.php` línea 38
**Severidad:** 🟡 MODERADO - Datos Incompletos

```php
// ❌ INCORRECTO - SELECT incompleto
$stmt_logs = $conn_acceso->prepare(
    "SELECT id, target_id, action, log_time FROM access_logs ..."
);

// Tabla tiene 10 campos, SELECT trae 4:
// ✅ id
// ❌ log_time
// ✅ target_id
// ❌ target_type
// ❌ name
// ✅ action
// ❌ status_message
// ❌ motivo
// ❌ log_status
// ❌ punto_acceso

// Aunque el SELECT es limitado, la lógica posterior LO CONSTRUYE MANUALMENTE
// desde otras tablas. Pero NUNCA LEE: name, motivo, status_message, log_status, punto_acceso
```

**Impacto:**
- 🟡 MODERADO: No retorna todos los campos de la tabla

---

### ERROR 15: Función send_error() retorna estructura inconsistente
**Ubicación:** `api/log_access.php` líneas 12-15 y `api/log_clarified_access.php` líneas 11-14
**Severidad:** 🟡 MODERADO - Inconsistencia

```php
// ❌ INCORRECTO - Retorna { message } no { error, success }
function send_error($code, $message) {
    http_response_code($code);
    echo json_encode(['message' => $message]);  // ❌ Debería ser ['error' => $message]
    exit;
}

// API esperaría:
// { "error": "mensaje" }
// Pero recibe:
// { "message": "mensaje" }
```

**Impacto:**
- 🟡 MODERADO: Inconsistencia con patrón ApiClient que espera `{ success, data, error }`

---

### ERROR 16: Falta nombre (`name`) en INSERT de log_clarified_access.php
**Ubicación:** `api/log_clarified_access.php` línea 73
**Severidad:** 🔴 CRÍTICO - Datos Faltantes

```php
// ✅ CORRECTO - Sí graba name
$stmt_insert = $conn_acceso->prepare(
    "INSERT INTO access_logs (target_id, target_type, action, punto_acceso, name, motivo) VALUES (?, 'personal', 'entrada', ?, ?, ?)"
);

// ✅ Graba: target_id, target_type, action, punto_acceso, name, motivo
// ❌ Falta: status_message, log_status (aunque tiene defaults)
```

**Impacto:**
- ✅ Este archivo SÍ graba `name` y `motivo` correctamente
- ❌ Pero `log_access.php` NO lo hace (inconsistencia)

---

### ERROR 17: access-logs-api.js no retorna datos consistentemente
**Ubicación:** `js/api/access-logs-api.js` líneas 83, 190, 251, 303
**Severidad:** 🟡 MODERADO - Inconsistencia

```javascript
// ✅ CORRECTO en getByType():
return result.data || result;  // Línea 83

// ✅ CORRECTO en logManual():
return result.data;  // Línea 190

// ✅ CORRECTO en logPortico():
return result.data;  // Línea 251

// ✅ CORRECTO en logClarified():
return result.data;  // Línea 303
```

**Impacto:**
- ✅ Retornos están normalizados (aunque podría mejorarse)

---

## 📊 RESUMEN DE ERRORES

| # | Error | Archivo | Línea | Severidad | Tipo |
|---|-------|---------|-------|-----------|------|
| 1 | Sin validación sesión | log_access.php | 1-10 | 🔴 CRÍTICO | Seguridad |
| 2 | Sin validación sesión | log_clarified_access.php | 1-10 | 🔴 CRÍTICO | Seguridad |
| 3 | Falta CORS | log_access.php | 1-10 | 🟡 MODERADO | Config |
| 4 | Falta CORS | log_clarified_access.php | 1-10 | 🟡 MODERADO | Config |
| 5 | POST sin sesión | log_access.php | 219 | 🔴 CRÍTICO | Seguridad |
| 6 | DELETE sin sesión | log_access.php | 339 | 🔴 CRÍTICO | Seguridad |
| 7 | JSDoc incorrecto | access-logs-api.js | 199-227 | 🟡 MODERADO | Doc |
| 8 | JSDoc parámetros falsos | access-logs-api.js | 261-308 | 🟡 MODERADO | Doc |
| 9 | Validación incompleta | log_clarified_access.php | 23-30 | 🟡 MODERADO | Validación |
| 10 | display_errors activo | log_clarified_access.php | 6-7 | 🟡 MODERADO | Config |
| 11 | error_reporting(0) | log_access.php | 6-7 | 🟡 MODERADO | Config |
| 12 | - | log_access.php | - | ✅ OK | - |
| 13 | Campos no grabados | log_access.php | 329 | 🔴 CRÍTICO | Datos |
| 14 | SELECT incompleto | log_access.php | 38 | 🟡 MODERADO | Datos |
| 15 | send_error inconsistente | ambos archivos | - | 🟡 MODERADO | API |
| 16 | - | log_clarified_access.php | 73 | ✅ OK | - |
| 17 | - | access-logs-api.js | - | ✅ OK | - |

---

## 🔴 PRIORIDAD DE CORRECCIONES

**CRÍTICOS (5 ERRORES):**
1. ERROR 1: Agregar sesión en log_access.php
2. ERROR 2: Agregar sesión en log_clarified_access.php
3. ERROR 5: Validación sesión en POST
4. ERROR 6: Validación sesión en DELETE
5. ERROR 13: Grabar campos `name` y `motivo` en INSERT

**MODERADOS (7 ERRORES):**
6. ERROR 3: Configurar CORS en log_access.php
7. ERROR 4: Configurar CORS en log_clarified_access.php
8. ERROR 7: Revisar JSDoc de logPortico()
9. ERROR 8: Corregir JSDoc de logClarified()
10. ERROR 9: Validación completa en log_clarified_access.php
11. ERROR 10: Desactivar display_errors en log_clarified_access.php
12. ERROR 11: Mejorar error_reporting en log_access.php
13. ERROR 14: Considerar retornar más campos en GET
14. ERROR 15: Normalizar respuesta de send_error()

---

## 📌 OBSERVACIONES

1. **log_access.php** es muy complejo y largo (371 líneas)
   - Maneja 5 tipos de targets diferentes
   - Lógica horaria para oficina
   - Validación de autorización por fecha

2. **log_clarified_access.php** es específico para aclaraciones
   - Mapea valores de `reason` a `punto_acceso` y `motivo`
   - BIEN: Graba `name` y `motivo`
   - MAL: Sin validación de sesión

3. **access-logs-api.js** es un cliente para 3 endpoints diferentes
   - `log_access.php`
   - `portico.php` (NO REVISADO)
   - `log_clarified_access.php`

---

**Estado:** Listo para correcciones

