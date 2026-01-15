# 🔧 Corrección - Estructura Diferente de Tabla Visitas

## 📋 El Problema

El código PHP estaba diseñado para una estructura de tabla `visitas` que **no coincidía** con tu implementación actual.

### Estructura Esperada (Original)
```sql
id, nombre, rut, empresa, tipo, status, acceso_permanente, fecha_expiracion, en_lista_negra
```

### Estructura Real (Tu BD)
```sql
id, nombre, rut, paterno, materno, fecha_inicio, tipo,
poc_personal_id, poc_unidad, poc_anexo,
familiar_de_personal_id, familiar_unidad, familiar_anexo,
status, fecha_expiracion, acceso_permanente, en_lista_negra
```

### La Diferencia Clave
- ❌ **Campo `empresa` NO EXISTE** en tu tabla
- ✅ **Campos `paterno` y `materno` SÍ EXISTEN** (igual que en la tabla `personal`)
- ✅ **Campo `status` SÍ EXISTE** con valor por defecto `'autorizado'`
- ✅ **Campos de contacto:** `poc_personal_id`, `familiar_de_personal_id`

## ✅ Correcciones Realizadas

### 1. Archivo: `api/portico.php`

**Línea 93 - Consulta SELECT corregida:**
```php
// ❌ ANTES
SELECT id, nombre, rut, empresa, tipo, status, acceso_permanente, fecha_expiracion, en_lista_negra

// ✅ DESPUÉS
SELECT id, nombre, paterno, materno, rut, tipo, status, acceso_permanente, fecha_expiracion, en_lista_negra, poc_personal_id, familiar_de_personal_id
```

**Línea 275-281 - Construcción del nombre:**
```php
// ❌ ANTES
$response_data['name'] = $entity['nombre'];

// ✅ DESPUÉS
$paterno = isset($entity['paterno']) && trim($entity['paterno']) !== '' ? " {$entity['paterno']}" : "";
$materno = isset($entity['materno']) && trim($entity['materno']) !== '' ? " {$entity['materno']}" : "";
$response_data['name'] = trim($entity['nombre'] . $paterno . $materno);
$response_data['tipo'] = $entity['tipo'] ?? '';
```

### 2. Archivo: `api/log_access.php`

**Línea 132 - Consulta para logs de visitas:**
```php
// ❌ ANTES
SELECT id, nombre, empresa, tipo FROM visitas WHERE id IN (...)

// ✅ DESPUÉS
SELECT id, nombre, paterno, materno, tipo FROM visitas WHERE id IN (...)
```

**Línea 143-160 - Construcción del nombre en logs:**
```php
// ❌ ANTES
'nombre' => $visita_info['nombre'] ?? 'ID ' . $log['target_id'],
'empresa' => $visita_info['empresa'] ?? 'N/A',

// ✅ DESPUÉS
// Construir nombre completo con paterno y materno
$nombre_completo = 'ID ' . $log['target_id'];
if ($visita_info) {
    $paterno = isset($visita_info['paterno']) && trim($visita_info['paterno']) !== '' ? " {$visita_info['paterno']}" : "";
    $materno = isset($visita_info['materno']) && trim($visita_info['materno']) !== '' ? " {$visita_info['materno']}" : "";
    $nombre_completo = trim($visita_info['nombre'] . $paterno . $materno);
}
'nombre' => $nombre_completo,
```

**Línea 265 - Consulta para buscar visita por RUT:**
```php
// ❌ ANTES
SELECT id, nombre, empresa, tipo, status, fecha_expiracion, acceso_permanente, en_lista_negra

// ✅ DESPUÉS
SELECT id, nombre, paterno, materno, tipo, status, fecha_expiracion, acceso_permanente, en_lista_negra
```

**Línea 278-288 - Construcción del nombre:**
```php
// ❌ ANTES
$response_data['nombre'] = $visita['nombre'];
$response_data['empresa'] = $visita['empresa'];

// ✅ DESPUÉS
$paterno = isset($visita['paterno']) && trim($visita['paterno']) !== '' ? " {$visita['paterno']}" : "";
$materno = isset($visita['materno']) && trim($visita['materno']) !== '' ? " {$visita['materno']}" : "";
$response_data['nombre'] = trim($visita['nombre'] . $paterno . $materno);
```

## 🎯 Impacto de los Cambios

### Antes
- ❌ Búsqueda de visitas por RUT fallaba (404)
- ❌ Campo `empresa` inexistente causaba errores SQL
- ❌ Nombres incompletos (faltaban apellidos)

### Después
- ✅ Búsqueda de visitas por RUT funciona correctamente
- ✅ Usa los campos correctos disponibles en tu BD
- ✅ Nombres completos (nombre + paterno + materno)
- ✅ Compatible con tu estructura de datos

## 🔍 Campos Disponibles en Tu Tabla

Si necesitas usar otros campos, tu tabla tiene:

| Campo | Tipo | Uso |
|-------|------|-----|
| `poc_personal_id` | int | ID del personal punto de contacto |
| `poc_unidad` | varchar | Unidad del POC |
| `poc_anexo` | varchar | Anexo del POC |
| `familiar_de_personal_id` | int | ID de familiar si aplica |
| `familiar_unidad` | varchar | Unidad del familiar |
| `familiar_anexo` | varchar | Anexo del familiar |
| `fecha_inicio` | date | Fecha de inicio de la visita |

## ✨ Próximos Pasos

1. **Prueba el escaneo de visita nuevamente**
   - Abre el pórtico
   - Escanea el RUT de una visita

2. **Verifica que:**
   - ✅ Se registra entrada/salida correctamente
   - ✅ Se muestra el nombre completo
   - ✅ Se carga la tabla de logs
   - ✅ Los campos mostrados son correctos

3. **Si aún tienes problemas:**
   - Verifica que la visita tenga `status = 'autorizado'`
   - Verifica que `fecha_expiracion` sea futura o NULL
   - Verifica que `acceso_permanente = 1` O `fecha_expiracion >= HOY`

## 📊 Comparación de Estructuras

| Aspecto | Original | Tu BD |
|---------|----------|-------|
| Nombre | `nombre` | `nombre + paterno + materno` |
| Empresa | `empresa` | No disponible |
| Contacto | No | `poc_personal_id`, `familiar_de_personal_id` |
| Período | `fecha_expiracion` | `fecha_inicio + fecha_expiracion` |
| Estado | `status` | `status` (default: 'autorizado') |

---

**Estado:** ✅ CORREGIDO Y LISTO PARA USAR

Ahora el pórtico debería funcionar correctamente con visitas.

