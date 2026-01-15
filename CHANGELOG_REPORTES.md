# Changelog - Módulo de Reportes
## Sistema de Control de Acceso Digital (SCAD)

**Fecha:** 28 de Octubre, 2025
**Versión:** 2.0
**Autor:** Refactorización y Correcciones

---

## 📋 Resumen Ejecutivo

Este documento detalla todas las correcciones, mejoras y cambios implementados en el módulo de Reportes del sistema SCAD. El módulo presentaba múltiples errores críticos identificados durante las pruebas, incluyendo consultas SQL incorrectas, campos faltantes, y referencias a tablas sin prefijos de base de datos.

---

## 🔧 Correcciones Principales

### 1. Inicialización del Módulo

**Problema:**
- El módulo de reportes no se inicializaba correctamente
- Script cargado duplicadamente (como módulo y como script normal)
- Función `initReportesModule` no era invocada desde `main-refactored.js`

**Solución:**
- **Archivo:** `main-refactored.js`
- **Línea:** 103, 241
- **Cambios:**
  ```javascript
  // Agregado import
  import { initReportesModule } from './reportes.js';

  // Actualizado switch case
  case 'reportes':
      initReportesModule(mainContent);
      break;
  ```

**Archivos modificados:**
- `js/main-refactored.js`
- `index.html` (removida línea 303 de carga duplicada)

---

### 2. API Client para Reportes

**Problema:**
- No existía un cliente API dedicado para reportes
- Las llamadas al backend no estaban centralizadas

**Solución:**
- **Archivo creado:** `js/api/reportes-api.js`
- **Funcionalidad:**
  ```javascript
  export class ReportesApi {
      async getReport(filters) {
          // Construir query params
          // Llamada GET al endpoint
          // Manejo de errores HTTP
      }
  }
  ```

**Archivos creados:**
- `js/api/reportes-api.js`

---

### 3. Reporte: Historial de Acceso por Persona

**Problema:**
- Faltaba campo "Punto de Acceso" para diferenciar entre Pórtico y Control de Unidades
- No se mostraba información de dónde se registró el acceso

**Solución:**
- **Archivo:** `api/reportes.php` (línea 300)
- **Cambios en SQL:**
  ```sql
  SELECT al.action, al.log_time, al.punto_acceso,
         p.Grado, p.Nombres, p.Paterno, p.Materno
  FROM acceso_pro_db.access_logs al
  JOIN personal_db.personal p ON al.target_id = p.id
  WHERE al.target_type = 'personal'
  ```

- **Archivo:** `js/reportes.js` (líneas 98-109)
- **Renderizado con badges:**
  ```javascript
  let puntoAcceso = 'N/A';
  if (row.punto_acceso) {
      if (row.punto_acceso.toLowerCase().includes('portico')) {
          puntoAcceso = '<span class="badge bg-primary"><i class="bi bi-shield-check"></i>Pórtico</span>';
      } else if (row.punto_acceso.toLowerCase().includes('control_unidades')) {
          puntoAcceso = '<span class="badge bg-info"><i class="bi bi-building"></i>Control de Unidades</span>';
      }
  }
  ```

- **Archivo:** `api/reportes.php` (líneas 386, 429-449)
- **Headers PDF:**
  ```php
  $headers = ['Nombre Completo', 'Acción', 'Punto de Acceso', 'Fecha y Hora'];
  $widths = [80, 25, 40, 55];
  ```

**Archivos modificados:**
- `api/reportes.php`
- `js/reportes.js`
- `js/modules/control.js` (línea actualizada para enviar 'control_unidades')

---

### 4. Reporte: Salida Posterior por Período (Horas Extra)

**Problema 1: Error HTTP 500**
- Query SQL incorrecta: usaba `personal_id` que no existe en tabla `horas_extra`
- Faltaba prefijo de base de datos `acceso_pro_db.`

**Solución:**
- **Archivo:** `api/reportes.php` (línea 302)
- **Cambios:**
  ```sql
  -- ANTES (INCORRECTO)
  FROM horas_extra h LEFT JOIN personal_db.personal p ON h.personal_id = p.id

  -- DESPUÉS (CORRECTO)
  FROM acceso_pro_db.horas_extra he
  LEFT JOIN personal_db.personal p ON he.personal_rut = p.NrRut
  ```

**Problema 2: "Invalid Date" en columna Fecha**
- JavaScript intentaba leer campo `fecha_hora_inicio` que no existe
- La tabla solo tiene `fecha_hora_termino`

**Solución:**
- **Archivo:** `js/reportes.js` (líneas 116-126)
- **Cambios:**
  ```javascript
  // Fecha - usar fecha_hora_termino (es la única fecha disponible)
  let fecha = 'N/A';
  if (row.fecha_hora_termino) {
      const dateObj = new Date(row.fecha_hora_termino);
      if (!isNaN(dateObj.getTime())) {
          fecha = dateObj.toLocaleDateString('es-CL');
      } else {
          fecha = row.fecha_hora_termino;
      }
  }
  ```

- **Archivo:** `api/reportes.php` (líneas 460-475)
- **PDF Rendering:**
  ```php
  // Fecha y Hora de la misma columna fecha_hora_termino
  $fecha = date('d/m/Y', strtotime($row['fecha_hora_termino']));
  $hora = date('H:i', strtotime($row['fecha_hora_termino']));
  ```

**Archivos modificados:**
- `api/reportes.php`
- `js/reportes.js`

---

### 5. Reporte: Acceso de Vehículos

**Problema 1: Error HTTP 500**
- Tablas sin prefijo de base de datos: `vehiculos`, `access_logs`
- Campo incorrecto: `personalId` debería ser `asociado_id`

**Solución:**
- **Archivo:** `api/reportes.php` (línea 306)
- **Query corregida:**
  ```sql
  SELECT al.action, al.log_time, v.id, v.patente, v.marca, v.modelo,
         v.tipo, v.status, v.asociado_tipo,
         CASE
             WHEN v.asociado_tipo IN ('PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL')
                 THEN TRIM(CONCAT_WS(' ', p.Grado, p.Nombres, p.Paterno, p.Materno))
             WHEN v.asociado_tipo IN ('EMPLEADO', 'EMPRESA')
                 THEN TRIM(CONCAT_WS(' ', ee.nombre, ee.paterno, ee.materno))
             WHEN v.asociado_tipo = 'VISITA'
                 THEN TRIM(CONCAT_WS(' ', vis.nombre, vis.paterno, vis.materno))
             ELSE 'N/A'
         END as personal_nombre_completo
  FROM acceso_pro_db.access_logs al
  JOIN acceso_pro_db.vehiculos v ON al.target_id = v.id
  LEFT JOIN personal_db.personal p ON v.asociado_id = p.id
      AND v.asociado_tipo IN ('PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL')
  LEFT JOIN acceso_pro_db.empresa_empleados ee ON v.asociado_id = ee.id
      AND v.asociado_tipo IN ('EMPLEADO', 'EMPRESA')
  LEFT JOIN acceso_pro_db.visitas vis ON v.asociado_id = vis.id
      AND v.asociado_tipo = 'VISITA'
  WHERE al.target_type = 'vehiculo'
  ```

**Problema 2: Nombre del asociado mostraba "N/A"**
- Solo se hacía JOIN con tabla `personal`
- No consideraba empleados de empresas ni visitas

**Solución:**
- Agregados múltiples LEFT JOINs según tipo de asociado
- CASE WHEN para seleccionar nombre correcto según tipo

**Archivos modificados:**
- `api/reportes.php`

---

### 6. Reporte: Acceso de Visitas

**Problema:**
- Columna "Empresa" mostraba `undefined`
- Campo `empresa` no existe en tabla `visitas`

**Solución:**
- **Archivo:** `js/reportes.js` (líneas 76, 158)
- **Cambios:**
  ```javascript
  // Header
  headers = ['Nombre', 'RUT', 'Tipo', 'Acción', 'Fecha y Hora'];

  // Renderizado
  table += `<td>${row.tipo || 'N/A'}</td>`;
  ```

- **Archivo:** `api/reportes.php` (líneas 402-403, 497)
- **PDF:**
  ```php
  $headers = ['Nombre', 'RUT', 'Tipo', 'Accion', 'Fecha y Hora'];
  $widths = [70, 30, 40, 30, 60];

  $pdf->Cell($widths[2], 6, utf8_decode($row['tipo'] ?? 'N/A'), 'LR');
  ```

**Campo `tipo` indica:**
- `"Visita"` - Visita con punto de contacto (POC)
- `"Familiar"` - Familiar de personal

**Archivos modificados:**
- `api/reportes.php`
- `js/reportes.js`

---

### 7. Reporte: Salidas Después de las 17 horas sin Autorización

**Problema:**
- Faltaba campo `Grado` en nombre del personal
- Mostraba solo: "DANIEL IBACACHE ARAYA"
- Debía mostrar: "CB1 DANIEL IBACACHE ARAYA"

**Solución:**
- **Archivo:** `api/reportes.php` (líneas 229-241)
- **Query especial corregida:**
  ```sql
  SELECT
      al.log_time,
      p.Grado,      -- ← AGREGADO
      p.Nombres,
      p.Paterno,
      p.Materno,
      p.NrRut
  FROM acceso_pro_db.access_logs al
  JOIN personal_db.personal p ON al.target_id = p.id
  LEFT JOIN acceso_pro_db.horas_extra he
      ON p.NrRut = he.personal_rut
      AND DATE(al.log_time) = DATE(he.fecha_hora_termino)
  WHERE al.target_type = 'personal'
      AND al.action = 'salida'
      AND HOUR(al.log_time) > 17
      AND he.id IS NULL
  ```

- **Archivo:** `api/reportes.php` (línea 511)
- **PDF Rendering:**
  ```php
  $nombreCompleto = trim(utf8_decode(
      ($row['Grado'] ?? '') . ' ' .
      ($row['Nombres'] ?? '') . ' ' .
      ($row['Paterno'] ?? '') . ' ' .
      ($row['Materno'] ?? '')
  ));
  ```

- **Archivo:** `api/reportes.php` (línea 312)
- **Query base también corregida:**
  ```sql
  SELECT al.action, al.log_time, p.Grado, p.Nombres, p.Paterno, p.Materno, p.NrRut
  FROM acceso_pro_db.access_logs al
  JOIN personal_db.personal p ON al.target_id = p.id
  WHERE al.target_type = 'personal'
  ```

**Archivos modificados:**
- `api/reportes.php`

---

### 8. Reporte: Acceso General

**Problema:**
- Tablas `visitas` y `vehiculos` sin prefijo de base de datos

**Solución:**
- **Archivo:** `api/reportes.php` (línea 304)
- **Cambios:**
  ```sql
  LEFT JOIN acceso_pro_db.visitas v ON al.target_id = v.id AND al.target_type = 'visita'
  LEFT JOIN acceso_pro_db.vehiculos ve ON al.target_id = ve.id AND al.target_type = 'vehiculo'
  ```

**Archivos modificados:**
- `api/reportes.php`

---

## 🎨 Mejoras de Interfaz y Formato

### PDF Mejorado

**Cambios en clase PDF:**
- **Archivo:** `api/reportes.php` (líneas 35-48)

**Header con estilo:**
```php
function Header() {
    $this->SetFillColor(52, 152, 219); // Azul
    $this->SetTextColor(255, 255, 255); // Blanco
    $this->SetFont('Arial','B',14);
    $this->Cell(0, 10, utf8_decode('REPORTE DE SISTEMA DE ACCESO'), 0, 1, 'C', true);

    $this->SetTextColor(0, 0, 0);
    $this->SetFont('Arial', '', 9);
    $this->Cell(0, 6, utf8_decode('Generado: ' . date('d/m/Y H:i:s')), 0, 1, 'R');
    $this->Ln(3);
}
```

**Headers de tabla con color:**
- **Archivo:** `api/reportes.php` (líneas 415-421)
```php
$pdf->SetFillColor(52, 152, 219);    // Fondo azul
$pdf->SetTextColor(255, 255, 255);    // Texto blanco
$pdf->SetFont('Arial', 'B', 8);
for ($i = 0; $i < count($headers); $i++) {
    $pdf->Cell($widths[$i], 7, utf8_decode($headers[$i]), 1, 0, 'C', true);
}
```

**Footer con resumen:**
- **Archivo:** `api/reportes.php` (líneas 524-528)
```php
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 5, utf8_decode('Total de registros: ' . count($data)), 0, 1, 'L');
$pdf->Cell(0, 5, utf8_decode('Reporte generado automáticamente por el Sistema de Control de Acceso'), 0, 1, 'L');
```

---

## 📁 Archivos Creados

| Archivo | Propósito |
|---------|-----------|
| `js/api/reportes-api.js` | Cliente API centralizado para reportes |

---

## 📝 Archivos Modificados

| Archivo | Líneas Modificadas | Descripción |
|---------|-------------------|-------------|
| `api/reportes.php` | 229-241, 300-312, 386-403, 415-528 | Queries SQL, headers PDF, renderizado |
| `js/reportes.js` | 76, 98-126, 155-160, 169-172 | Renderizado de tablas, manejo de datos |
| `js/main-refactored.js` | 103, 241 | Inicialización del módulo |
| `index.html` | 303 (removida) | Eliminada carga duplicada de script |
| `js/modules/control.js` | Línea de log_access | Envío de 'control_unidades' en punto_acceso |

---

## 🔍 Errores Corregidos

### Error 1: "Uncaught SyntaxError: Cannot use import statement outside a module"
- **Causa:** Script cargado duplicadamente
- **Solución:** Removida línea 303 de `index.html`

### Error 2: "Identifier 'initReportesModule' has already been declared"
- **Causa:** Función declarada dos veces en `reportes.js`
- **Solución:** Reescrito completamente el archivo con una sola declaración

### Error 3: "Assignment to constant variable"
- **Causa:** `const headers = []` no puede ser reasignado
- **Solución:** Cambiado a `let headers = []` (línea 61)

### Error 4: "HTTP 500: Internal Server Error" (horas_extra)
- **Causa:** Campo `personal_id` no existe, falta prefijo DB
- **Solución:** Usar `personal_rut` y agregar `acceso_pro_db.`

### Error 5: "HTTP 500: Internal Server Error" (vehiculos)
- **Causa:** Tablas sin prefijo, campo `personalId` incorrecto
- **Solución:** Agregar prefijos, usar `asociado_id`, JOINs múltiples

### Error 6: "Invalid Date" en reportes
- **Causa:** Campo `fecha_hora_inicio` no existe
- **Solución:** Usar `fecha_hora_termino` con manejo de errores

### Error 7: Campo "Empresa" muestra "undefined"
- **Causa:** Campo `empresa` no existe en tabla `visitas`
- **Solución:** Usar campo `tipo` (Visita/Familiar)

### Error 8: Falta Grado en nombre de personal
- **Causa:** Campo `Grado` no incluido en SELECT
- **Solución:** Agregado a todas las queries relevantes

---

## 🗄️ Base de Datos

### Estructura de Tablas Confirmada

**Base de datos: `acceso_pro_db`**
- `access_logs` - Logs de acceso
- `vehiculos` - Registro de vehículos
- `visitas` - Registro de visitas
- `horas_extra` - Horas extraordinarias
- `empresa_empleados` - Empleados de empresas

**Base de datos: `personal_db`**
- `personal` - Personal militar/civil
- `personal_comision` - Personal en comisión

### Campos Importantes

**Tabla `horas_extra`:**
- `personal_rut` (NO `personal_id`)
- `fecha_hora_termino` (NO `fecha_hora_inicio`)
- `motivo`, `motivo_detalle`

**Tabla `vehiculos`:**
- `asociado_id` (NO `personalId`)
- `asociado_tipo` ('PERSONAL', 'EMPLEADO', 'EMPRESA', 'VISITA')
- `patente`, `marca`, `modelo`

**Tabla `visitas`:**
- `tipo` ('Visita', 'Familiar') (NO `empresa`)
- `poc_personal_id`, `familiar_de_personal_id`

**Tabla `access_logs`:**
- `punto_acceso` ('portico', 'control_unidades')
- `target_type`, `target_id`, `action`, `log_time`

---

## ✅ Testing Realizado

| Reporte | Estado | Notas |
|---------|--------|-------|
| Historial de Acceso por Persona | ✅ Funcional | Muestra punto de acceso con badges |
| Salida Posterior por Período | ✅ Funcional | Fechas correctas, nombres completos |
| Acceso General | ✅ Funcional | Todas las tablas con prefijos |
| Acceso de Vehículos | ✅ Funcional | Asociados correctos según tipo |
| Acceso de Visitas | ✅ Funcional | Tipo (Visita/Familiar) correcto |
| Personal en Comisión | ✅ Funcional | Sin cambios requeridos |
| Salidas No Autorizadas | ✅ Funcional | Nombres con grado incluido |

---

## 🚀 Próximas Mejoras Sugeridas

1. **Filtros Avanzados:**
   - Búsqueda por rango de horas específico
   - Filtro por unidad/departamento
   - Filtro por tipo de vehículo

2. **Exportación:**
   - Agregar exportación a Excel (XLSX ya está disponible)
   - Exportación a CSV
   - Envío automático por email

3. **Visualización:**
   - Gráficos de accesos por hora
   - Dashboard de estadísticas
   - Mapa de calor de accesos

4. **Performance:**
   - Paginación de resultados
   - Caché de reportes frecuentes
   - Índices en tablas de BD

---

## 📞 Soporte

Para reportar problemas o solicitar nuevas funcionalidades:
1. Crear issue en el repositorio del proyecto
2. Contactar al equipo de desarrollo
3. Revisar documentación en `/docs`

---

## 📜 Historial de Versiones

| Versión | Fecha | Descripción |
|---------|-------|-------------|
| 2.0 | 2025-10-28 | Refactorización completa del módulo de reportes |
| 1.0 | 2025-10-25 | Versión inicial con errores críticos |

---

**Documento generado:** 28 de Octubre, 2025
**Última actualización:** 28 de Octubre, 2025
**Responsable:** Equipo de Desarrollo SCAD
