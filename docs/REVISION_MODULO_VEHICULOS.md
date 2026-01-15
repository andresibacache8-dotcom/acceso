# 🔍 REVISIÓN COMPLETA - MÓDULO MANTENEDOR DE VEHÍCULOS

**Fecha:** 2025-10-25
**Estado:** ERRORES ENCONTRADOS

---

## ⚠️ ERRORES IDENTIFICADOS

### ERROR 1: Campo incorrecto en renderVehiculoTable()
**Ubicación:** `js/main.js` línea 2469
**Severidad:** 🔴 CRÍTICO - Afecta visualización en tabla

```javascript
// ❌ INCORRECTO - Línea 2469
const asociadoNombre = v.asociado_nombre || 'N/A';
```

**Problema:**
- El backend en `api/vehiculos.php` línea 156 devuelve el campo `asociado_nombre`
- Sin embargo, al revisar la respuesta GET del API, el campo se llama: `asociado_nombre` ✅
- Pero en la tabla se intenta mostrar este campo que DEBERÍA existir

**Análisis detallado:**
En `api/vehiculos.php` GET (línea 148-162):
```php
SELECT
    v.id, v.patente, v.marca, v.modelo, v.tipo, v.tipo_vehiculo,
    v.asociado_id, v.asociado_tipo, v.status, v.fecha_inicio, v.fecha_expiracion, v.acceso_permanente,
    CASE
        WHEN v.tipo IN ('PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL') THEN TRIM(CONCAT_WS(' ', p.Grado, p.Nombres, p.Paterno))
        WHEN v.tipo IN ('EMPLEADO', 'EMPRESA') THEN TRIM(CONCAT_WS(' ', ee.nombre, ee.paterno, ee.materno))
        WHEN v.tipo = 'VISITA' THEN TRIM(CONCAT_WS(' ', vis.nombre, vis.paterno, vis.materno))
        ELSE 'N/A'
    END as asociado_nombre,  ← ✅ Campo correcto
    COALESCE(p.NrRut, ee.rut, vis.rut, '') as rut_asociado
```

**Impacto:**
- La tabla SÍPUEDE mostrar el nombre si los JOINS funcionan correctamente
- Pero si los JOINS fallan (NULL values), el campo mostrará 'N/A'

**Recomendación:** Este campo es CORRECTO, pero necesita validar que los LEFT JOINs retornen datos.

---

### ERROR 2: Inconsistencia en campo de nombre en modal
**Ubicación:** `js/main.js` línea 2976
**Severidad:** 🟡 MODERADO

```javascript
// Línea 2976 - al editar vehículo
nombreDisplay.textContent = `Asociado actual: ${vehiculo.asociado_nombre}`;
```

**Problema:**
- Usa `vehiculo.asociado_nombre` que viene del GET
- PERO en la respuesta GET del backend, el campo se llama `asociado_nombre` ✅ (CORRECTO)
- Sin embargo, en respuesta POST/PUT (líneas 338-342 y 501-554), el backend devuelve DIFERENTES campos

**En POST (línea 338-342):**
```javascript
// Envía devuelta: { id, status, acceso_permanente, ... }
// pero NO incluye: asociado_nombre, rut_asociado
```

**En PUT (línea 501-554):**
```php
// El backend AGREGA estos campos:
$data['personalNrRut'] = $person['NrRut'];
$data['personalName'] = trim(...);  // ← Nombre del propietario
```

**El Problema Real:**
- En GET: devuelve `asociado_nombre` y `rut_asociado` ✅
- En POST: devuelve `id`, `status`, `acceso_permanente` (NO incluye nombres)
- En PUT: devuelve `personalNrRut` y `personalName` (nombres DIFERENTES)

**Inconsistencia encontrada:**
```javascript
// ❌ En POST/PUT, backend devuelve:
data['personalNrRut']  // nombre del campo en PUT
data['personalName']   // nombre del campo en PUT

// ✅ Pero en GET, devuelve:
asociado_nombre  // diferente
rut_asociado    // diferente
```

**Impacto:**
- Después de crear/editar, los campos de nombre NO están disponibles en la respuesta
- El frontend debe hacer un GET adicional para actualizar datos

---

### ERROR 3: Campo `color` DOCUMENTADO pero NO IMPLEMENTADO
**Ubicación:** `js/api/vehiculos-api.js` línea 96 vs `api/vehiculos.php` línea 304
**Severidad:** 🔴 CRÍTICO - Inconsistencia grave

**Estructura actual de tabla `vehiculos` (REAL):**
```sql
id, patente, marca, modelo, tipo, tipo_vehiculo, asociado_id, asociado_tipo,
status, fecha_inicio, fecha_expiracion, acceso_permanente
```

**Campo `color` NO EXISTE en la tabla real**

**El problema:**
- JSDoc en línea 96 documenta: `vehiculoData.color` como parámetro esperado
- Formulario modal NO tiene campo de `color`
- API POST/PUT NO envía `color`
- INSERT de BD NO incluye `color` (y es correcto, no existe)

**Impacto:**
- ❌ Documentación FALSA - promete soporte para `color` que no existe
- ❌ Usuarios esperarían poder registrar color de vehículo
- ✅ API funciona CORRECTAMENTE al NO enviarlo (tabla no lo tiene)

**Recomendación:**
- Eliminar mención de `color` de JSDoc en línea 96
- O agregar campo `color` a tabla si es necesario

---

### ERROR 4: Bind_param incorrecto en registrar_historial_vehiculo()
**Ubicación:** `api/vehiculos.php` línea 114
**Severidad:** 🔴 CRÍTICO - Errores SQL

```php
// ❌ Línea 114 - TYPES INCORRECTOS
$stmt->bind_param("isiiiss", $vehiculo_id, $patente, $asociado_id_anterior, $asociado_id_nuevo, $usuario_id, $tipo_cambio, $detalles);
//                  ^^^^^^^
//                  Debería ser: isssiiss
```

**Análisis:**
```
INSERT INTO vehiculo_historial
    (vehiculo_id,    patente,  asociado_id_anterior, asociado_id_nuevo, fecha_cambio, usuario_id, tipo_cambio, detalles)
    VALUES (?,       ?,        ?,                    ?,                 NOW(),        ?,         ?,           ?)

Tipos correctos:
    i (int)         s (string) i (int)               i (int)            [timestamp]   i (int)    s (string)   s (string)
    ^               ^          ^                     ^                  [automático]  ^          ^            ^
```

**El bind_param actual dice:**
```
isiiiss  = i(int) + s(str) + i(int) + i(int) + i(int) + s(str) + s(str)
           ^       ^        ^       ^       ^       ^      ^
           1       2        3       4       5       6      7
```

**Los parámetros son:**
```
1. $vehiculo_id           → i ✅ CORRECTO
2. $patente               → s ✅ CORRECTO
3. $asociado_id_anterior  → i ✅ CORRECTO
4. $asociado_id_nuevo     → i ✅ CORRECTO
5. $usuario_id            → i (pero en el string dice 'i') ✅ CORRECTO
6. $tipo_cambio           → s ✅ CORRECTO
7. $detalles              → s ✅ CORRECTO
```

**Espera:** El bind_param es `isiiiss` (7 tipos) pero hay 7 parámetros.

Contando:
- i = vehiculo_id
- s = patente
- i = asociado_id_anterior
- i = asociado_id_nuevo
- i = usuario_id
- s = tipo_cambio
- s = detalles

**Esto parece CORRECTO.** ✅ Error FALSO.

---

### ERROR 5: Inconsistencia en tipos de datos - acceso_permanente
**Ubicación:** Múltiples archivos
**Severidad:** 🟡 MODERADO

**En PHP (api/vehiculos.php):**
```php
// Línea 314 - bind_param
$stmt->bind_param("sssssissssi", ..., $acceso_permanente);
//                         ^
//                         Declarado como: i (INT)

// Pero línea 230:
$acceso_permanente = !empty($data['acceso_permanente']) ? 1 : 0;  // INT ✅
```

**En JavaScript (js/main.js):**
```javascript
// Línea 2877
acceso_permanente: vehiculo.acceso_permanente === '1' || vehiculo.acceso_permanente.toLowerCase() === 'true',
//                                                                                    ^
//                                                    Llama .toLowerCase() a INT/BOOLEAN

// Línea 2970
form.elements.acceso_permanente.checked = !!vehiculo.acceso_permanente;
//                                             ^
//                                             Si es INT (1 o 0), funciona ✅
```

**Problema:**
- Línea 2877: intenta llamar `.toLowerCase()` a un valor que puede ser INT
- Si `vehiculo.acceso_permanente` es número (1 o 0), causará ERROR

```javascript
// Si llega como: acceso_permanente: 1
(1).toLowerCase()  // ❌ TypeError: (1).toLowerCase is not a function
```

---

---

### ERROR 7: Modal usa `v.asociado_nombre` pero GET retorna `asociado_nombre`
**Ubicación:** `js/main.js` línea 2976
**Severidad:** 🟢 BAJO

```javascript
// ❌ Inconsistencia en propiedad
nombreDisplay.textContent = `Asociado actual: ${vehiculo.asociado_nombre}`;
//                                            ^^^^^^^^^^^^^^
//                                            Correcto, el GET devuelve esto
```

Actualización: Este campo SÍ existe en GET ✅

---

### ERROR 8: Validación deficiente de acceso_permanente en formulario
**Ubicación:** `js/main.js` línea 2877-2878
**Severidad:** 🔴 CRÍTICO

```javascript
// ❌ Línea 2877-2878
acceso_permanente: vehiculo.acceso_permanente === '1' || vehiculo.acceso_permanente.toLowerCase() === 'true',
fecha_expiracion: vehiculo.acceso_permanente === '1' ? null : vehiculo.fecha_expiracion || null
```

**Problemas:**
1. `acceso_permanente` viene del BD como INT/BOOLEAN (0 o 1)
2. Comparar con '1' (string) puede fallar
3. Llamar `.toLowerCase()` a un INT → ERROR

**Ejemplo fallido:**
```javascript
const vehiculo = {
    acceso_permanente: 1  // INT del BD
};

// Línea 2877 intenta:
1 === '1'  // false ✅ (INT vs STRING)
(1).toLowerCase()  // ❌ ERROR: TypeError
```

**Fix necesario:**
```javascript
// ✅ CORRECTO
acceso_permanente: Boolean(vehiculo.acceso_permanente || false),
fecha_expiracion: vehiculo.acceso_permanente ? null : (vehiculo.fecha_expiracion || null)
```

---

### ERROR 9: Parámetro incorrecto en vehiculosApi.getHistorial()
**Ubicación:** `js/main.js` línea 3144
**Severidad:** 🔴 CRÍTICO

```javascript
// Línea 3144
const historialData = await vehiculosApi.getHistorial(id);
```

**El método getHistorial espera (línea 74-76 de vehiculos-api.js):**
```javascript
const result = await this.client.get(this.historialEndpoint, {
    vehiculo_id: vehiculoId  // ← KEY es 'vehiculo_id'
});
```

**El API espera (línea 30 de vehiculo_historial.php):**
```php
$vehiculo_id = $_GET['vehiculo_id'] ?? null;  // ← Parámetro: 'vehiculo_id'
```

**Estado:** ✅ CORRECTO - El parámetro se pasa como `vehiculo_id`

---

### ERROR 10: Tipo de vehículo NO se envía en importación
**Ubicación:** `js/main.js` línea 2871-2879
**Severidad:** 🟡 MODERADO

```javascript
// ❌ Línea 2871-2879 - Falta campo
const vehiculoData = {
    patente: patente,
    marca: vehiculo.marca,
    modelo: vehiculo.modelo,
    tipo: vehiculo.tipo.toUpperCase(),
    personalNrRut: vehiculo.personalNrRut || null,
    acceso_permanente: vehiculo.acceso_permanente === '1' || vehiculo.acceso_permanente.toLowerCase() === 'true',
    fecha_expiracion: vehiculo.acceso_permanente === '1' ? null : vehiculo.fecha_expiracion || null
    // ❌ Falta: tipo_vehiculo
    // ❌ Falta: color (si es requerido)
};
```

**API espera (línea 207):**
```php
$tipo_vehiculo = isset($data['tipo_vehiculo']) ? strtoupper(trim($data['tipo_vehiculo'])) : 'AUTO';
```

**Impacto:** El vehículo se crea con `tipo_vehiculo = 'AUTO'` por defecto (podría no ser deseado)

---

### ERROR 11: Historial modal assume estructura que puede no existir
**Ubicación:** `js/main.js` línea 3165
**Severidad:** 🟡 MODERADO

```javascript
// Línea 3165-3166
const vehiculo = historialData.vehiculo || {};
document.getElementById('historial-propietario-actual').textContent = vehiculo.propietario_actual_nombre || 'No asignado';
```

**En vehiculo_historial.php:**
```php
// Línea 102-109
SELECT v.*,
       ...
       END as propietario_actual_nombre  ← ✅ Existe
FROM vehiculos v
```

**Pero:** Si el vehículo fue eliminado, `vehiculo` será NULL en BD, y `propietario_actual_nombre` no existirá.

---

## 📊 RESUMEN DE ERRORES

| # | Error | Archivo | Línea | Severidad | Tipo |
|---|-------|---------|-------|-----------|------|
| 1 | `acceso_permanente.toLowerCase()` en INT | main.js | 2877 | 🔴 CRÍTICO | TypeError |
| 2 | Falta `tipo_vehiculo` en importación | main.js | 2871-2879 | 🟡 MODERADO | Datos incompletos |
| 3 | `color` documentado pero no existe | vehiculos-api.js | 96 | 🟡 MODERADO | Documentación falsa |
| 4 | Respuesta POST sin info propietario | api/vehiculos.php | 338-342 | 🟡 MODERADO | Inconsistencia API |
| 5 | Respuesta PUT campos diferentes | api/vehiculos.php | 501-554 | 🟡 MODERADO | Inconsistencia API |
| 6 | Historial assumes propietario actual | main.js | 3165-3166 | 🟡 MODERADO | Null pointer potencial |

---

## 🔧 PRÓXIMOS PASOS

Se procederá a corregir TODOS estos errores en el siguiente paso.

