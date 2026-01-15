# Guía de Importación Masiva de Personal

## Descripción General

Se ha implementado un sistema completo de importación masiva de personal que permite cargar múltiples registros desde archivos Excel (.xlsx, .xls) o CSV. El sistema incluye:

- **Backend**: Endpoint REST para procesar importaciones
- **Frontend**: Interfaz visual con modal de carga y progreso
- **Validación**: Validación en cliente y servidor
- **Plantilla**: Descarga de plantilla de referencia

## Componentes Implementados

### 1. Backend (PHP)

**Archivo**: `api/personal.php`

**Nuevo Endpoint**:
```
POST /api/personal.php?action=import
```

**Características**:
- Procesa arrays de personal en formato JSON
- Valida campos requeridos (Nombres, Paterno, NrRut)
- Valida formato de RUT (12345678-9 o 123456789)
- Crea nuevos registros o actualiza existentes (por RUT)
- Transacciones de base de datos para rollback en errores
- Respuesta detallada con estadísticas

**Ejemplo de Request**:
```json
{
  "personal": [
    {
      "Nombres": "JUAN",
      "Paterno": "GONZALEZ",
      "Materno": "LOPEZ",
      "NrRut": "12345678-9",
      "Grado": "SARGENTO",
      "Unidad": "A1",
      "Estado": 1,
      "es_residente": 0
    }
  ]
}
```

**Ejemplo de Response**:
```json
{
  "success": [
    {
      "row": 2,
      "rut": "12345678-9",
      "action": "creado"
    }
  ],
  "errors": [],
  "total": 1,
  "processed": 1,
  "created": 1,
  "updated": 0
}
```

### 2. Frontend (JavaScript)

**Archivos Principales**:

#### a) `js/api/personal-api.js`
- Nuevo método: `importMasivo(personalArray)`
- Envía datos al endpoint de importación
- Retorna resultado con estadísticas

#### b) `js/modules/personal.js`
- **Funciones principales**:
  - `openImportModal()` - Abre modal de importación
  - `handleImportFile(modalInstance)` - Procesa archivo seleccionado
  - `readFileAsArray(file)` - Lee Excel o CSV
  - `showImportProgressModal(personalData)` - Muestra progreso
  - `downloadImportTemplate()` - Descarga plantilla

- **Templates**:
  - `getImportPersonalModalTemplate()` - Modal de selección de archivo
  - `getImportProgressTemplate()` - Modal de progreso

### 3. Templates HTML

**Archivo**: `js/ui/templates-personal.js`

Se agregó botón "Importar Masivo" en la interfaz de gestión de personal

**Archivo**: `index.html`

Se agregaron contenedores para modales:
```html
<div class="modal fade" id="import-personal-modal" tabindex="-1"></div>
<div class="modal fade" id="import-progress-modal" tabindex="-1"></div>
```

## Flujo de Uso

### 1. Iniciar Importación
```
Usuario hace clic en botón "Importar Masivo"
    ↓
Se abre modal de importación
```

### 2. Seleccionar Archivo
```
Usuario selecciona archivo Excel o CSV
    ↓
Validación de tipo de archivo
    ↓
Usuario hace clic en "Importar"
```

### 3. Procesar Archivo
```
Lectura de archivo (Excel con XLSX.js o CSV con parseador)
    ↓
Validación básica de contenido
    ↓
Se oculta modal de selección
    ↓
Se muestra modal de progreso
```

### 4. Enviar al Servidor
```
Se llama API importMasivo()
    ↓
Backend procesa cada registro
    ↓
Se devuelven estadísticas con éxitos y errores
```

### 5. Mostrar Resultados
```
Modal actualiza con:
  - Contadores (total, procesados, creados, actualizados, errores)
  - Lista detallada de éxitos
  - Lista detallada de errores por fila
    ↓
Se recarga tabla de personal
    ↓
Se cierra modal automáticamente
```

## Campos Soportados

### Campos Requeridos:
- `Nombres` - Nombres de la persona
- `Paterno` - Apellido paterno
- `NrRut` - RUT (formato: 12345678-9 o 123456789)

### Campos Opcionales:
- `Materno` - Apellido materno
- `Grado` - Grado/Rango militar
- `Unidad` - Unidad a la que pertenece
- `Estado` - 1=Activo, 0=Inactivo (predeterminado: 1)
- `es_residente` - 1=Residente, 0=No residente (predeterminado: 0)

## Validaciones Implementadas

### Cliente:
1. Validación de tipo de archivo (Excel/CSV)
2. Validación de archivo no vacío

### Servidor:
1. Campos requeridos no vacíos (Nombres, Paterno, NrRut)
2. Formato de RUT válido
3. Normalización: mayúsculas automáticas, trim de espacios
4. Detección de duplicados por RUT (actualiza vs crea)

## Descarga de Plantilla

El sistema proporciona un botón "Descargar Plantilla" que genera:

- **Formato Excel**: `plantilla_personal.xlsx` con ejemplo
- **Formato CSV**: `plantilla_personal.csv` si Excel no está disponible

Estructura:
```
Grado | Nombres | Paterno | Materno | NrRut | Unidad | Estado | es_residente
------|---------|---------|---------|-------|--------|--------|---------------
Sargt | Juan    | Gonzalez| Lopez   | ...   | A1     | 1      | 0
```

## Manejo de Errores

### Errores Mostrados:
- Campos requeridos faltantes
- RUT inválido
- Errores de inserción/actualización en BD
- Errores de lectura de archivo
- Errores de conexión

### Rollback Automático:
Si hay error durante la importación, la transacción se revierte (no se guardan cambios)

## Validación de Formato RUT

Se acepta cualquiera de estos formatos:
- `12345678-9` (con guión y dígito verificador)
- `123456789` (solo números)

**Validación**:
- Mínimo 7 dígitos, máximo 10 dígitos
- Último carácter puede ser número o K/k

## Ejemplos de Uso

### Ejemplo 1: Crear nuevos registros
```csv
Grado,Nombres,Paterno,Materno,NrRut,Unidad,Estado,es_residente
SARGENTO,JUAN,GONZALEZ,LOPEZ,12345678-9,A1,1,0
CABO,MARIA,RODRIGUEZ,MARTINEZ,87654321-4,A2,1,0
```

### Ejemplo 2: Actualizar registros existentes
Si un RUT ya existe, se actualiza el registro:
```csv
Grado,Nombres,Paterno,Materno,NrRut,Unidad,Estado,es_residente
TENIENTE,JUAN,GONZALEZ,LOPEZ,12345678-9,B1,1,0
```

## Respuestas de API

### Éxito (200 OK):
```json
{
  "success": [
    {"row": 2, "rut": "12345678-9", "action": "creado"},
    {"row": 3, "rut": "87654321-4", "action": "actualizado"}
  ],
  "errors": [
    {"row": 4, "message": "RUT inválido: 999..."}
  ],
  "total": 3,
  "processed": 3,
  "created": 1,
  "updated": 1
}
```

### Error (500):
```json
{
  "error": "Error durante la importación: ...",
  "results": {
    "success": [],
    "errors": [],
    "total": 0,
    "processed": 0,
    "created": 0,
    "updated": 0
  }
}
```

## Consideraciones Técnicas

### Rendimiento:
- Transacción única por lote (todo o nada)
- Carga dinámica de librería XLSX bajo demanda
- Progreso en tiempo real en interfaz

### Seguridad:
- Prepared statements en PHP (previene SQL injection)
- Validación en cliente y servidor
- Normalización de datos (mayúsculas, trim)
- Manejo seguro de errores

### Compatibilidad:
- Soporta Excel 2003+ (.xls)
- Soporta Excel 2007+ (.xlsx)
- Soporta CSV estándar
- Compatible con XLSX.js (librería existente del proyecto)

## Prueba del Sistema

1. Accede al módulo "Mantenedores > Personal"
2. Haz clic en botón "Importar Masivo" (color verde)
3. Haz clic en "Descargar Plantilla" para obtener ejemplo
4. Completa la plantilla con datos
5. Selecciona el archivo y haz clic en "Importar"
6. Observa progreso y resultados

## Troubleshooting

### "Modal de importación no encontrado"
- Verificar que index.html incluya: `<div class="modal fade" id="import-personal-modal">`

### "Error al procesar archivo"
- Verificar que XLSX.js esté disponible en `js/xlsx.full.min.js`
- Para CSV, la librería se carga dinámicamente (no requiere XLSX.js)

### "RUT inválido"
- Asegurar formato: 12345678-9 o 123456789
- Sin espacios ni puntos

### No se crean registros
- Verificar campos requeridos: Nombres, Paterno, NrRut
- Revisar la consola del navegador para detalles
- Revisar response en Network tab (F12)

## Extensiones Futuras

1. Importación de más campos (teléfono, email, dirección)
2. Vista previa de datos antes de importar
3. Opción de descargar reporte de importación
4. Soporte para múltiples hojas en Excel
5. Mapeo de columnas personalizado
