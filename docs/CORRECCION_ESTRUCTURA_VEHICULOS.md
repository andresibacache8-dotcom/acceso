# 🔧 Corrección - Estructura Tabla Vehículos

## 📋 El Problema

El código PHP estaba diseñado para una estructura de tabla `vehiculos` **diferente** a la real.

### Estructura Esperada (Original)
```sql
id, patente, tipo, personalId, acceso_permanente, fecha_expiracion
```

### Estructura Real (Tu BD)
```sql
id, patente, marca, modelo, tipo, tipo_vehiculo, asociado_id, asociado_tipo,
status, fecha_inicio, fecha_expiracion, acceso_permanente
```

### Cambios Clave
| Aspecto | Original | Tu BD |
|---------|----------|-------|
| ID del propietario | `personalId` | `asociado_id` |
| Tipo de propietario | Asume personal | `asociado_tipo` (puede ser 'personal', 'visita', 'empresa', etc.) |
| Status | No valida | `status` (con valor 'no autorizado' por defecto) |
| Información | Solo patente | `marca`, `modelo`, `tipo_vehiculo` |
| Período | Solo fecha_expiracion | `fecha_inicio`, `fecha_expiracion` |

## ✅ Correcciones Realizadas

### 1. Archivo: `api/portico.php`

**Línea 57 - Consulta SELECT:**
```php
// ❌ ANTES
SELECT id, patente, tipo, personalId, acceso_permanente, fecha_expiracion

// ✅ DESPUÉS
SELECT id, patente, tipo, tipo_vehiculo, marca, modelo, asociado_id, asociado_tipo, status, acceso_permanente, fecha_expiracion
```

**Línea 67 - Validación de autorización:**
```php
// ❌ ANTES
if ($vehicle_data['acceso_permanente']) {
    $is_authorized = true;
}

// ✅ DESPUÉS
if ($vehicle_data['status'] === 'autorizado') {
    if ($vehicle_data['acceso_permanente']) {
        $is_authorized = true;
    } elseif (!empty($vehicle_data['fecha_expiracion'])) {
        // Validar fecha de expiración
    }
}
```

**Línea 262-279 - Obtener propietario:**
```php
// ❌ ANTES
if (!empty($entity['personalId'])) {
    // Busca en tabla personal

// ✅ DESPUÉS
if (!empty($entity['asociado_id']) && $entity['asociado_tipo'] === 'personal') {
    // Solo busca en personal si el asociado es de tipo 'personal'
    // También agrega marca, modelo, tipo_vehiculo a la respuesta
```

### 2. Archivo: `api/log_access.php`

**Línea 84 - Consulta para logs:**
```php
// ❌ ANTES
SELECT id, patente, personalId FROM vehiculos

// ✅ DESPUÉS
SELECT id, patente, marca, modelo, asociado_id, asociado_tipo FROM vehiculos
```

**Línea 93-96 - Filtrado de propietarios:**
```php
// ❌ ANTES
if ($row['personalId']) $personal_ids_from_vehiculos[] = $row['personalId'];

// ✅ DESPUÉS
if ($row['asociado_tipo'] === 'personal' && $row['asociado_id']) {
    $personal_ids_from_vehiculos[] = $row['asociado_id'];
}
```

**Línea 244 - Consulta por patente:**
```php
// ❌ ANTES
SELECT id, patente, personalId, status, fecha_expiracion, acceso_permanente

// ✅ DESPUÉS
SELECT id, patente, marca, modelo, asociado_id, asociado_tipo, status, fecha_expiracion, acceso_permanente
```

**Línea 258-267 - Obtener propietario:**
```php
// ❌ ANTES
if ($vehiculo['personalId']) {
    // Busca en personal

// ✅ DESPUÉS
if ($vehiculo['asociado_id'] && $vehiculo['asociado_tipo'] === 'personal') {
    // Solo si es personal
    // También agrega marca y modelo
```

## 🎯 Impacto de los Cambios

### Antes
- ❌ Campo `personalId` inexistente causaba errores SQL
- ❌ No validaba el campo `status`
- ❌ Perdía información de `marca`, `modelo`, `tipo_vehiculo`
- ❌ No contemplaba otros tipos de propietarios (visita, empresa)

### Después
- ✅ Usa el campo correcto `asociado_id`
- ✅ Valida `status` antes de autorizar
- ✅ Incluye marca, modelo y tipo de vehículo
- ✅ Flexible para diferentes tipos de propietarios
- ✅ Compatible con tu estructura de datos

## 🔍 Estructura Completa de Vehiculos

Tu tabla tiene disponibles:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int | ID del vehículo |
| `patente` | varchar | Patente del vehículo |
| `marca` | varchar | Marca (Toyota, Ford, etc.) |
| `modelo` | varchar | Modelo (Corolla, Focus, etc.) |
| `tipo` | enum | Tipo: FUNCIONARIO, VISITA, RESIDENTE, EMPRESA |
| `tipo_vehiculo` | varchar | LIVIANO, PESADO, etc. |
| `asociado_id` | int | ID del propietario/usuario |
| `asociado_tipo` | varchar | Tipo: personal, visita, empresa, etc. |
| `status` | varchar | autorizado, no autorizado (default) |
| `fecha_inicio` | date | Fecha de inicio de autorización |
| `fecha_expiracion` | date | Fecha de vencimiento |
| `acceso_permanente` | tinyint | 1 = permanente, 0 = temporal |

## ✨ Próximos Pasos

1. **Prueba el escaneo de vehículos:**
   - Abre el pórtico
   - Escanea la patente de un vehículo

2. **Verifica que:**
   - ✅ Se registra entrada/salida correctamente
   - ✅ Se muestra la patente, marca y modelo
   - ✅ Se muestra el propietario (si aplica)
   - ✅ Se carga la tabla de logs
   - ✅ Los datos mostrados son correctos

3. **Requisitos para autorizar vehículos:**
   - `status = 'autorizado'`
   - `acceso_permanente = 1` O `fecha_expiracion >= HOY`

4. **Si el propietario no aparece:**
   - Verifica que `asociado_tipo = 'personal'`
   - Verifica que `asociado_id` tenga un ID válido
   - Verifica que ese ID exista en tabla `personal`

## 📊 Flujo Completo de Búsqueda (Pórtico)

```
Escanear patente o ID
    ↓
1. Busca en Personal (RUT)
    ↓ (si no)
2. Busca en Vehículos (patente o ID) ← ¡Aquí es!
    ↓ Valida status = 'autorizado'
    ↓ Valida acceso_permanente O fecha_expiracion
    ↓ Si tiene asociado_id y asociado_tipo='personal'
    ↓ Busca datos del propietario
    ↓
3. Si no autorizado, retorna error 403
    ↓
4. Si existe pero no autorizado, retorna error 403
    ↓
5. Si no existe, busca en Visitas
    ... (continúa)
```

---

**Estado:** ✅ CORREGIDO Y LISTO PARA USAR

Ahora el pórtico debería funcionar correctamente con vehículos.

