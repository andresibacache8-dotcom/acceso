# Plan de Testing - Sistema de Importación Masiva de Personal

## Testing Checklist

### 1. INTERFAZ Y ACCESIBILIDAD
- [ ] Botón "Importar Masivo" visible en módulo Personal
- [ ] Botón tiene icono y color verde
- [ ] Botón accesible desde navegación: Mantenedores → Personal
- [ ] Modal se abre correctamente al hacer click
- [ ] Modal tiene validación de campo archivo

### 2. DESCARGA DE PLANTILLA
- [ ] Botón "Descargar Plantilla" presente en modal
- [ ] Descarga archivo Excel (plantilla_personal.xlsx)
- [ ] Archivo contiene encabezados correctos
- [ ] Archivo contiene fila de ejemplo
- [ ] Fallback a CSV si librería XLSX no está disponible

### 3. SELECCIÓN DE ARCHIVO
- [ ] Se puede seleccionar archivo Excel (.xlsx)
- [ ] Se puede seleccionar archivo Excel antiguo (.xls)
- [ ] Se puede seleccionar archivo CSV (.csv)
- [ ] Se rechaza archivo inválido (ej: .txt, .pdf)
- [ ] Se muestra error si archivo está vacío
- [ ] Se muestra error si no se selecciona archivo

### 4. PROCESAMIENTO DE ARCHIVOS
#### Excel:
- [ ] Lee correctamente archivos .xlsx
- [ ] Lee correctamente archivos .xls
- [ ] Identifica encabezados correctamente
- [ ] Convierte filas a objetos JSON

#### CSV:
- [ ] Lee correctamente archivos CSV
- [ ] Identifica encabezados (primera fila)
- [ ] Parsea correctamente valores con comas
- [ ] Maneja espacios en blanco correctamente

### 5. VALIDACIONES EN CLIENTE
- [ ] Valida que exista campo "Nombres"
- [ ] Valida que exista campo "Paterno"
- [ ] Valida que exista campo "NrRut"
- [ ] Muestra error si falta algún campo requerido
- [ ] No envía datos si hay error de validación

### 6. MODAL DE PROGRESO
- [ ] Modal se muestra después de seleccionar archivo
- [ ] Modal oculta modal de importación
- [ ] Barra de progreso se visualiza
- [ ] Contador de total muestra número correcto
- [ ] Contador de procesados se actualiza
- [ ] Modal no es cerrable durante importación (botón X deshabilitado)

### 7. COMUNICACIÓN CON SERVIDOR
- [ ] Request se envía a `api/personal.php?action=import`
- [ ] Request method es POST
- [ ] Body contiene array "personal" con datos
- [ ] Content-Type es application/json
- [ ] Response contiene datos de éxito y errores

### 8. VALIDACIONES EN SERVIDOR
- [ ] Se valida que Nombres no esté vacío
- [ ] Se valida que Paterno no esté vacío
- [ ] Se valida que NrRut no esté vacío
- [ ] Se valida formato RUT (12345678-9 o 123456789)
- [ ] Se rechaza RUT inválido
- [ ] Se normalizan datos a mayúsculas
- [ ] Se eliminan espacios en blanco
- [ ] Se detectan duplicados por RUT

### 9. PROCESAMIENTO DE DATOS
- [ ] Se crean nuevos registros si RUT no existe
- [ ] Se actualizan registros si RUT ya existe
- [ ] Se usa transacción para todo el lote
- [ ] Si hay error, se revierte toda la transacción (rollback)
- [ ] Los contadores se actualizan correctamente
- [ ] Campo "Estado" se guarda correctamente (0 o 1)
- [ ] Campo "es_residente" se guarda correctamente

### 10. RESPUESTA DEL SERVIDOR
- [ ] Response HTTP status es 200 para éxito
- [ ] Response contiene array "success"
- [ ] Response contiene array "errors"
- [ ] Response contiene "total" (cantidad de registros)
- [ ] Response contiene "processed" (procesados)
- [ ] Response contiene "created" (nuevos)
- [ ] Response contiene "updated" (actualizados)

### 11. VISUALIZACIÓN DE RESULTADOS
- [ ] Se muestran registros exitosos con badge verde
- [ ] Se muestra fila número de éxito
- [ ] Se muestra RUT de éxito
- [ ] Se muestra acción (creado/actualizado)
- [ ] Se muestran errores con badge rojo
- [ ] Se muestra número de fila del error
- [ ] Se muestra mensaje de error descriptivo
- [ ] Se actualiza modal con información

### 12. POST-IMPORTACIÓN
- [ ] Tabla se recarga automáticamente después de 2 segundos
- [ ] Nuevos registros aparecen en tabla
- [ ] Registros actualizados muestran datos nuevos
- [ ] Se muestra notificación Toast con resumen
- [ ] Modal se cierra automáticamente
- [ ] Usuario puede ver datos importados inmediatamente

### 13. CASOS DE ERROR
- [ ] Error si RUT duplicado no tiene acción clara
- [ ] Error si datos inválidos muestra fila específica
- [ ] Error si servidor no responde muestra mensaje claro
- [ ] Error si JSON inválido maneja gracefully
- [ ] Error en transacción revierte todos los cambios
- [ ] Errores no impiden que otros registros se procesen

### 14. DATOS DE PRUEBA
#### CSV Válido:
```csv
Grado,Nombres,Paterno,Materno,NrRut,Unidad,Estado,es_residente
SARGENTO,JUAN,GONZALEZ,LOPEZ,12345678-9,A1,1,0
CABO,MARIA,RODRIGUEZ,MARTINEZ,87654321-4,A2,1,0
```

#### CSV Inválido (sin campos requeridos):
```csv
Grado,Nombres,Paterno,Materno,Unidad,Estado
SARGENTO,JUAN,GONZALEZ,LOPEZ,A1,1
```

#### CSV con Errores Mixtos:
```csv
Grado,Nombres,Paterno,Materno,NrRut,Unidad,Estado,es_residente
SARGENTO,JUAN,GONZALEZ,LOPEZ,12345678-9,A1,1,0
,MARIA,RODRIGUEZ,MARTINEZ,87654321-4,A2,1,0
TENIENTE,CARLOS,FERNANDEZ,GARCIA,INVALIDO,B1,1,0
```

### 15. TESTING DE VOLUMEN
- [ ] Importación de 10 registros
- [ ] Importación de 100 registros
- [ ] Importación de 1000 registros
- [ ] Performance aceptable (< 10 segundos para 1000 registros)
- [ ] No hay memory leaks
- [ ] Interfaz responsiva durante importación

### 16. TESTING DE EDGECASES
- [ ] Archivo con espacios extra en encabezados
- [ ] RUT con formato 12345678-K (dígito verificador)
- [ ] RUT con espacios (12 345 678 - 9)
- [ ] Nombres con caracteres especiales (áéíóú)
- [ ] Nombres muy largos (100+ caracteres)
- [ ] Campos vacíos en columnas opcionales
- [ ] Archivo con BOM (Byte Order Mark)
- [ ] Archivo con diferentes encodings (UTF-8, ANSI)

### 17. TESTING DE NAVEGADOR
- [ ] Chrome/Chromium (último)
- [ ] Firefox (último)
- [ ] Safari (si es aplicable)
- [ ] Edge (Chromium)
- [ ] Dispositivos móviles (responsive)

### 18. TESTING DE ACCEIBILIDAD
- [ ] Botones tienen labels descriptivos
- [ ] Errores se anuncian al usuario
- [ ] Modal es navigable con teclado
- [ ] Colores tienen suficiente contraste

### 19. INTEGRACIÓN CON BD
- [ ] Registros se guardan en tabla personal correcta
- [ ] Campos se guardan con valores correctos
- [ ] Duplicados por RUT se detectan
- [ ] Transacción funciona (rollback en error)
- [ ] No hay corrupción de datos

### 20. DOCUMENTACIÓN
- [ ] IMPORTACION_PERSONAL.md existe y es completo
- [ ] RESUMEN_IMPORTACION.md existe
- [ ] GUIA_RAPIDA.txt existe
- [ ] TEST_IMPORTACION.md existe (este archivo)
- [ ] Ejemplos de CSV disponibles

## Procedimiento de Testing Manual

### Test 1: Importación Simple
1. Abre navegador y ve a http://localhost/Desarrollo/acceso/
2. Navega a Mantenedores → Personal
3. Click en "Importar Masivo"
4. Click en "Descargar Plantilla"
5. Abre plantilla, completa con datos
6. Guarda como CSV
7. Vuelve al modal, selecciona archivo
8. Click "Importar"
9. Observa progreso y resultados
10. Verifica tabla se actualiza

### Test 2: Error Handling
1. Crea CSV con campo RUT vacío
2. Intenta importar
3. Verifica error se muestra por fila
4. Verifica otros registros se procesan

### Test 3: Actualización
1. Importa un registro con RUT "11111111-1"
2. Vuelve a importar con mismo RUT pero datos diferentes
3. Verifica que se actualiza (no duplica)
4. Verifica contador de "actualizados" = 1

### Test 4: Normalizaciones
1. Crea CSV con datos en minúsculas
2. Importa
3. Verifica que BD tiene datos en MAYÚSCULAS

### Test 5: Volumen
1. Crea CSV con 100 registros
2. Importa
3. Verifica tiempo de importación
4. Verifica todos los registros están en BD

## Criterios de Aceptación

✅ Sistema debe ser intuitivo (usuarios sin entrenamiento pueden usarlo)
✅ Importación debe ser confiable (transacciones, validación)
✅ Errores deben ser claros y actionables
✅ Performance debe ser aceptable (< 10 seg para 1000 registros)
✅ Interfaz debe ser responsive
✅ Documentación debe ser completa

## Bugs Conocidos / Limitaciones

(Ninguno identificado en la versión 1.0.0)

## Notas

- Librería XLSX.js se carga dinámicamente (bajo demanda)
- CSV se parsea con parseador nativo (sin dependencias)
- Transacciones MySQL se usan para integridad de datos
- Prepared statements previenen SQL injection
- Los RUT se normalizan a mayúsculas automáticamente

---

**Última actualización**: 2025-11-05
**Versión**: 1.0.0
**Estado**: Completado
