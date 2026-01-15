# 🔍 REVISIÓN - MÓDULO EMPRESAS Y EMPLEADOS

**Fecha:** 2025-10-25
**Estado:** ERRORES ENCONTRADOS

---

## 📋 ESTRUCTURA DE TABLA `empresa_empleados` (PROPORCIONADA)

```sql
id                          INT(11)         PRIMARY KEY AUTO_INCREMENT
empresa_id                  INT(11)         NOT NULL (Índice)
nombre                      VARCHAR(100)    NOT NULL
paterno                     VARCHAR(100)    NOT NULL
materno                     VARCHAR(100)    NULL
rut                         VARCHAR(20)     NOT NULL (Índice)
fecha_expiracion            DATE            NULL
acceso_permanente           TINYINT(1)      NOT NULL DEFAULT 0
status                      VARCHAR(20)     NOT NULL DEFAULT 'autorizado'
```

---

## ⚠️ ERRORES IDENTIFICADOS

### ERROR 1: Falta validación de sesión en empresa_empleados.php
**Ubicación:** `api/empresa_empleados.php` líneas 1-5
**Severidad:** 🔴 CRÍTICO - Seguridad

```php
// ❌ INCORRECTO - Sin validación de sesión
<?php
require_once 'db_acceso.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
// Cualquiera puede acceder sin autenticación
```

**Análisis:**
Comparar con `api/horas_extra.php` (ya corregido):
```php
// ✅ CORRECTO
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado...']);
    exit;
}
```

**Impacto:**
- 🔴 CRÍTICO: Cualquiera puede ver, crear, modificar o eliminar empleados
- 🔴 CRÍTICO: Datos de empleados expuestos sin autenticación
- 🔴 CRÍTICO: Sin auditoría de accesos

---

### ERROR 2: Falta validación de sesión en empresas.php
**Ubicación:** `api/empresas.php` líneas 1-6
**Severidad:** 🔴 CRÍTICO - Seguridad

```php
// ❌ INCORRECTO - Sin validación de sesión
<?php
require_once 'db_acceso.php';
require_once 'db_personal.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
// Cualquiera puede acceder a lista de empresas
```

**Impacto:**
- 🔴 CRÍTICO: Cualquiera puede ver lista completa de empresas
- 🔴 CRÍTICO: Información sensible expuesta

---

### ERROR 3: DELETE usa borrado físico en empresa_empleados.php
**Ubicación:** `api/empresa_empleados.php` línea 67
**Severidad:** 🔴 CRÍTICO - Diseño & Recuperación

```php
// ❌ INCORRECTO - Borrado físico (irreversible)
$stmt = $conn_acceso->prepare("DELETE FROM empresa_empleados WHERE id=?");
```

**Análisis:**
La tabla TIENE campo `status` para borrado suave:
```sql
status VARCHAR(20) NOT NULL DEFAULT 'autorizado'
```

**Debería ser:**
```php
// ✅ CORRECTO - Borrado lógico
$stmt = $conn_acceso->prepare("UPDATE empresa_empleados SET status = 'inactivo' WHERE id = ?");
```

**Impacto:**
- ❌ Empleados eliminados permanentemente sin recuperación
- ❌ No hay auditoría de qué se eliminó
- ❌ Viola el patrón de borrado suave

---

### ERROR 4: DELETE usa borrado físico en empresas.php
**Ubicación:** `api/empresas.php` línea 54
**Severidad:** 🔴 CRÍTICO - Diseño

```php
// ❌ INCORRECTO
$stmt = $conn_acceso->prepare("DELETE FROM empresas WHERE id=?");
```

**Análisis:**
Aunque la tabla `empresas` no tiene campo `status`, eliminar empresas elimina:
- Datos de la empresa
- Referencias desde `empresa_empleados` (si no hay FK con CASCADE)
- Información histórica

**Debería:**
- Implementar borrado lógico con campo `status`
- O validar que no tenga empleados antes de eliminar

**Impacto:**
- ❌ Pérdida de datos irreversible
- ❌ Posibles violaciones de integridad referencial

---

### ERROR 5: JSDoc documenta campos que NO existen en empresa_empleados
**Ubicación:** `js/api/empresas-api.js` líneas 211-221
**Severidad:** 🟡 MODERADO - Documentación

```javascript
// ❌ INCORRECTO - Campos documentados no existen
@param {string} empleadoData.apellido_paterno - Apellido paterno
@param {string} empleadoData.apellido_materno - Apellido materno
@param {string} empleadoData.cargo - Cargo del empleado
@param {string} empleadoData.departamento - Departamento
@param {string} empleadoData.email - Email
@param {string} empleadoData.telefono - Teléfono
@param {string} empleadoData.observaciones - Observaciones
```

**Análisis:**
Estructura real de tabla:
```sql
empresa_id, nombre, paterno, materno, rut, fecha_expiracion, acceso_permanente, status
```

**Campos documentados que NO existen:**
- `apellido_paterno` → debería ser `paterno` ✅
- `apellido_materno` → debería ser `materno` ✅
- `cargo` → NO existe ❌
- `departamento` → NO existe ❌
- `email` → NO existe ❌
- `telefono` → NO existe ❌
- `observaciones` → NO existe ❌

**Campos que faltan en JSDoc:**
- `fecha_expiracion` (DATE, NULL) ❌
- `acceso_permanente` (TINYINT, DEFAULT 0) ❌
- `status` (VARCHAR, DEFAULT 'autorizado') ❌

**Impacto:**
- ❌ Documentación FALSA - promete parámetros que no existen
- ❌ Usuarios esperarían poder enviar cargo, email, teléfono
- ❌ Confusión sobre estructura de datos

---

### ERROR 6: JSDoc documenta parámetros incorrecto para crear empresa
**Ubicación:** `js/api/empresas-api.js` líneas 69-79
**Severidad:** 🟡 MODERADO - Documentación

```javascript
// ❌ Documentación de parámetros de empresa
@param {string} empresaData.razon_social - Razón social
@param {string} empresaData.direccion - Dirección
@param {string} empresaData.ciudad - Ciudad
@param {string} empresaData.region - Región
@param {string} empresaData.telefono - Teléfono de contacto
@param {string} empresaData.email - Email de contacto
@param {string} empresaData.rubro - Rubro de la empresa
```

**Análisis:**
Código PHP actual (línea 35):
```php
INSERT INTO empresas (nombre, unidad_poc, poc_rut, poc_nombre, poc_anexo)
```

**Solo acepta:**
- `nombre`
- `unidad_poc`
- `poc_rut`
- `poc_nombre`
- `poc_anexo`

**Campos documentados que NO se usan:**
- `razon_social` ❌
- `direccion` ❌
- `ciudad` ❌
- `region` ❌
- `telefono` ❌
- `email` ❌
- `rubro` ❌

**Impacto:**
- ❌ Documentación FALSA
- ❌ Parámetros enviados serán ignorados silenciosamente
- ❌ Datos no se guardan

---

### ERROR 7: create() y createEmpleado() retornan objeto incorrecto
**Ubicación:** `js/api/empresas-api.js` líneas 104, 246
**Severidad:** 🔴 CRÍTICO - Inconsistencia

```javascript
// ❌ INCORRECTO - Línea 104 y 246
return result;  // Retorna objeto envuelto de ApiClient
```

**Análisis:**
Comparar con:
- `getAll()` (línea 59): `return result.data || result` ✅
- `delete()` (línea 171): `return true` ✅

**Inconsistencia:**
- `getAll()` extrae datos
- `create()` retorna objeto envuelto
- Patrón NO uniforme

**Impacto:**
- ⚠️ Inconsistencia en patrón de retorno
- ⚠️ Frontend esperaría `result.data` pero recibe objeto envuelto

---

### ERROR 8: update() y updateEmpleado() retornan objeto incorrecto
**Ubicación:** `js/api/empresas-api.js` líneas 149, 293
**Severidad:** 🔴 CRÍTICO - Inconsistencia

```javascript
// ❌ INCORRECTO
return result;  // Debería ser return result.data || result
```

**Impacto:**
- ⚠️ Inconsistencia con otros métodos
- ⚠️ Patrón NO uniforme

---

### ERROR 9: POST sin validación de datos en empresa_empleados.php
**Ubicación:** `api/empresa_empleados.php` línea 41-51
**Severidad:** 🟡 MODERADO - Validación

```php
// ❌ INCORRECTO - Sin validación
$data = json_decode(file_get_contents('php://input'), true);
$stmt = $conn_acceso->prepare("INSERT INTO empresa_empleados...");
// No valida si campos requeridos están presentes
```

**Campos obligatorios:**
- `empresa_id` (INT) ❌ No validado
- `nombre` (VARCHAR 100) ❌ No validado
- `paterno` (VARCHAR 100) ❌ No validado
- `rut` (VARCHAR 20) ❌ No validado

**Impacto:**
- ⚠️ Podría insertar empleados sin datos requeridos
- ⚠️ Datos incompletos en BD

---

### ERROR 10: POST en empresa_empleados.php no valida sesión
**Ubicación:** `api/empresa_empleados.php` línea 40
**Severidad:** 🔴 CRÍTICO - Seguridad

```php
// ❌ Sin validación
case 'POST':
    // Cualquiera puede crear empleados
```

**Impacto:**
- 🔴 CRÍTICO: Cualquiera puede crear empleados sin autenticación

---

### ERROR 11: PUT en empresa_empleados.php no valida datos
**Ubicación:** `api/empresa_empleados.php` línea 53-64
**Severidad:** 🟡 MODERADO - Validación

```php
// ❌ Sin validación
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'];  // No valida si existe
// Ejecuta actualización sin verificar
```

**Impacto:**
- ⚠️ Podría actualizar con datos inválidos
- ⚠️ Sin verificación de que registro existe

---

### ERROR 12: PUT en empresa_empleados.php no valida sesión
**Ubicación:** `api/empresa_empleados.php` línea 53
**Severidad:** 🔴 CRÍTICO - Seguridad

```php
// ❌ Sin validación de sesión
case 'PUT':
    // Cualquiera puede editar empleados
```

**Impacto:**
- 🔴 CRÍTICO: Cualquiera puede modificar empleados

---

### ERROR 13: GET sin validación de sesión en empresa_empleados.php
**Ubicación:** `api/empresa_empleados.php` línea 18
**Severidad:** 🔴 CRÍTICO - Seguridad

```php
// ❌ Sin validación
case 'GET':
    // Cualquiera puede ver empleados de empresa
```

**Impacto:**
- 🔴 CRÍTICO: Cualquiera puede ver lista de empleados

---

### ERROR 14: JSDoc de updateEmpleado documenta campos que NO existen
**Ubicación:** `js/api/empresas-api.js` líneas 260-267
**Severidad:** 🟡 MODERADO - Documentación

```javascript
// ❌ Mismo problema que createEmpleado
@param {string} empleadoData.apellido_paterno - Apellido paterno ❌
@param {string} empleadoData.cargo - Cargo ❌
@param {string} empleadoData.departamento - Departamento ❌
@param {string} empleadoData.email - Email ❌
@param {string} empleadoData.telefono - Teléfono ❌
```

**Impacto:**
- ❌ Documentación FALSA

---

## 📊 RESUMEN DE ERRORES

| # | Error | Archivo | Línea | Severidad | Tipo |
|---|-------|---------|-------|-----------|------|
| 1 | Sin validación sesión | empresa_empleados.php | 1-5 | 🔴 CRÍTICO | Seguridad |
| 2 | Sin validación sesión | empresas.php | 1-6 | 🔴 CRÍTICO | Seguridad |
| 3 | DELETE físico | empresa_empleados.php | 67 | 🔴 CRÍTICO | Diseño |
| 4 | DELETE físico | empresas.php | 54 | 🔴 CRÍTICO | Diseño |
| 5 | JSDoc falso empleados | empresas-api.js | 211-221 | 🟡 MODERADO | Documentación |
| 6 | JSDoc falso empresas | empresas-api.js | 69-79 | 🟡 MODERADO | Documentación |
| 7 | create() retorna incorrecto | empresas-api.js | 104, 246 | 🔴 CRÍTICO | API |
| 8 | update() retorna incorrecto | empresas-api.js | 149, 293 | 🔴 CRÍTICO | API |
| 9 | POST sin validación | empresa_empleados.php | 41 | 🟡 MODERADO | Validación |
| 10 | POST sin sesión | empresa_empleados.php | 40 | 🔴 CRÍTICO | Seguridad |
| 11 | PUT sin validación | empresa_empleados.php | 53 | 🟡 MODERADO | Validación |
| 12 | PUT sin sesión | empresa_empleados.php | 53 | 🔴 CRÍTICO | Seguridad |
| 13 | GET sin sesión | empresa_empleados.php | 18 | 🔴 CRÍTICO | Seguridad |
| 14 | JSDoc falso update | empresas-api.js | 260-267 | 🟡 MODERADO | Documentación |

---

## 🔴 PRIORIDAD DE CORRECCIONES

**CRÍTICOS (6 ERRORES):**
1. ERROR 1: Agregar validación de sesión en empresa_empleados.php
2. ERROR 2: Agregar validación de sesión en empresas.php
3. ERROR 3: Cambiar DELETE a borrado lógico en empresa_empleados.php
4. ERROR 4: Cambiar DELETE a borrado lógico en empresas.php
5. ERROR 7: Normalizar create() en empresas-api.js
6. ERROR 8: Normalizar update() en empresas-api.js

**MODERADOS (8 ERRORES):**
7. ERROR 5: Actualizar JSDoc de createEmpleado
8. ERROR 6: Actualizar JSDoc de create
9. ERROR 9: Validar datos en POST
10. ERROR 11: Validar datos en PUT
11. ERROR 14: Actualizar JSDoc de updateEmpleado

---

## 📌 PRÓXIMOS PASOS

Proceder a crear documento de soluciones para todos estos errores.

