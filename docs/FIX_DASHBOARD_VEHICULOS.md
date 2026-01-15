# 🔧 FIX: Dashboard - Detalles de Vehículos

## 📋 PROBLEMA IDENTIFICADO

En el módulo de inicio (dashboard), las tarjetas de vehículos mostraban correctamente los **contadores**, pero al hacer clic en una tarjeta para ver el detalle, **NO se mostraban los propietarios/asociados** de los vehículos.

## 🔍 CAUSA RAÍZ

### Backend (api/dashboard.php)
1. La consulta SQL usaba `LEFT JOIN` con `personal_db.personal` pero:
   - Solo concatenaba `Grado + Nombres + Paterno` (faltaba `Materno`)
   - No manejaba el caso cuando el vehículo NO tiene `personalId` asociado
   - Vehículos FISCALES, EMPRESA, VISITA típicamente no tienen `personalId`

### Frontend (js/main.js)
1. El código intentaba acceder a `item.asociado_nombre` sin validar si era NULL
2. No había fallback al campo `propietario` cuando `asociado_nombre` era NULL
3. El rendering mostraba "N/A" genérico en lugar del propietario real

## ✅ SOLUCIÓN APLICADA

### 1️⃣ Backend (api/dashboard.php, líneas 107-115)

**ANTES:**
```php
$sql = "SELECT v.id, v.patente, v.marca, v.modelo, 
    TRIM(CONCAT_WS(' ', p.Grado, p.Nombres, p.Paterno)) AS asociado_nombre, 
    a.log_time as entry_time
    FROM acceso_pro_db.vehiculos v
    JOIN acceso_pro_db.access_logs a ON v.id = a.target_id
    LEFT JOIN personal_db.personal p ON v.personalId = p.id
    WHERE ...";
```

**DESPUÉS:**
```php
$sql = "SELECT v.id, v.patente, v.marca, v.modelo, v.propietario,
    CASE 
        WHEN v.personalId IS NOT NULL THEN TRIM(CONCAT_WS(' ', p.Grado, p.Nombres, p.Paterno, p.Materno))
        ELSE v.propietario
    END AS asociado_nombre, 
    a.log_time as entry_time
    FROM acceso_pro_db.vehiculos v
    JOIN acceso_pro_db.access_logs a ON v.id = a.target_id
    LEFT JOIN personal_db.personal p ON v.personalId = p.id
    WHERE ...";
```

**Mejoras:**
- ✅ Agregado campo `v.propietario` en SELECT
- ✅ Agregado `Materno` al nombre completo del funcionario
- ✅ Uso de `CASE WHEN` para elegir entre nombre de funcionario o propietario
- ✅ Si hay `personalId`, muestra nombre completo del funcionario
- ✅ Si NO hay `personalId`, muestra el campo `propietario`

### 2️⃣ Frontend (js/main.js, líneas 1696-1709)

**ANTES:**
```javascript
} else if (category.startsWith('vehiculos')) {
    headers = `<tr><th>Patente</th><th>Marca/Modelo</th><th>Asociado</th><th>Hora de Entrada</th></tr>`;
    rows = data.map(item => {
        const asociado = item.asociado_nombre || 'N/A';
        const searchText = `${item.patente} ${item.marca} ${item.modelo} ${asociado}`.toLowerCase();
        return `<tr data-search-text="${searchText}">
                    <td>${item.patente}</td>
                    <td>${item.marca || ''} ${item.modelo || ''}</td>
                    <td>${asociado}</td>
                    <td>${new Date(item.entry_time).toLocaleString('es-CL')}</td>
                </tr>`;
    }).join('');
}
```

**DESPUÉS:**
```javascript
} else if (category.startsWith('vehiculos')) {
    headers = `<tr><th>Patente</th><th>Marca/Modelo</th><th>Propietario/Asociado</th><th>Hora de Entrada</th></tr>`;
    rows = data.map(item => {
        const asociado = item.asociado_nombre || item.propietario || 'No especificado';
        const marcaModelo = `${item.marca || 'N/A'} ${item.modelo || ''}`.trim();
        const searchText = `${item.patente} ${marcaModelo} ${asociado}`.toLowerCase();
        return `<tr data-search-text="${searchText}">
                    <td><strong>${item.patente}</strong></td>
                    <td>${marcaModelo}</td>
                    <td>${asociado}</td>
                    <td>${new Date(item.entry_time).toLocaleString('es-CL')}</td>
                </tr>`;
    }).join('');
}
```

**Mejoras:**
- ✅ Header cambiado a "Propietario/Asociado" (más descriptivo)
- ✅ Triple fallback: `asociado_nombre` → `propietario` → "No especificado"
- ✅ Mejor formato de marca/modelo con trim()
- ✅ Patente en **negrita** para mejor visibilidad
- ✅ Mejor manejo de búsqueda con `marcaModelo` consolidado

## 📊 TIPOS DE VEHÍCULOS Y SU ASOCIACIÓN

| Tipo Vehículo | `personalId` | Campo que se muestra |
|---------------|-------------|---------------------|
| FUNCIONARIO   | ✅ Tiene    | Nombre completo del funcionario |
| RESIDENTE     | ✅ Tiene    | Nombre completo del residente |
| FISCAL        | ❌ NULL     | Campo `propietario` |
| EMPRESA       | ❌ NULL     | Campo `propietario` |
| VISITA        | ❌ NULL     | Campo `propietario` |

## 🧪 PRUEBAS REALIZADAS

- ✅ Click en tarjeta "Vehículos Funcionario" → Muestra nombre completo con grado
- ✅ Click en tarjeta "Vehículos Fiscal" → Muestra nombre del propietario
- ✅ Click en tarjeta "Vehículos Empresa" → Muestra empresa/propietario
- ✅ Click en tarjeta "Vehículos Visita" → Muestra nombre de la visita
- ✅ Búsqueda en modal funciona correctamente
- ✅ No más "N/A" o campos vacíos

## 🎯 RESULTADO FINAL

Ahora el modal de detalles muestra correctamente:
- **Patente** en negrita
- **Marca/Modelo** completo
- **Propietario/Asociado** según corresponda:
  - Nombre completo del funcionario (con grado y apellido materno)
  - Nombre del propietario si no es funcionario
- **Hora de entrada** formateada

## 📅 FECHA

26 de Octubre de 2025

## 🔗 ARCHIVOS MODIFICADOS

1. `api/dashboard.php` (líneas 107-115)
2. `js/main.js` (líneas 1696-1709)

---

**Estado:** ✅ RESUELTO
