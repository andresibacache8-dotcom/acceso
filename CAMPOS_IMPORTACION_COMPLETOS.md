# Campos Completos para Importación de Personal

## Actualización - TODOS los campos soportados

Se ha actualizado el sistema para soportar **TODOS los 40+ campos** de la tabla personal.

Ahora puedes subir un archivo con todos los campos y se actualizarán correctamente.

## Lista Completa de Campos

### Campos REQUERIDOS (no pueden estar vacíos):
```
Nombres          - Nombres de la persona
Paterno          - Apellido paterno
NrRut            - RUT del personal
```

### Campos Opcionales (se actualizan si están presentes):

#### Información Personal:
```
Grado            - Grado/Rango militar
Materno          - Apellido materno
fechaNacimiento  - Fecha de nacimiento (formato: YYYY-MM-DD)
sexo             - Sexo (M/F u otro)
estadoCivil      - Estado civil (Soltero, Casado, etc.)
```

#### Información Laboral:
```
nrEmpleado       - Número de empleado
puesto           - Puesto o cargo
especialidadPrimaria - Especialidad primaria
Unidad           - Unidad a la que pertenece
unidadEspecifica - Unidad específica
categoria        - Categoría
escalafon        - Escafón
trabajoExterno   - Trabajo externo
fechaIngreso     - Fecha de ingreso (formato: YYYY-MM-DD)
fechaPresentacion - Fecha de presentación (formato: YYYY-MM-DD)
```

#### Información de Contacto:
```
telefonoFijo     - Teléfono fijo
movil1           - Celular 1
movil2           - Celular 2
email1           - Email 1
email2           - Email 2
anexo            - Anexo telefónico
```

#### Información de Domicilio:
```
calle            - Nombre de la calle
numeroDepto      - Número y departamento
poblacionVilla   - Población/Villa
```

#### Información de Salud y Beneficios:
```
prevision        - Previsión (AFP, Sistema público, etc.)
sistemaSalud     - Sistema de salud
regimenMatrimonial - Régimen matrimonial
religion         - Religión
tipoVivienda     - Tipo de vivienda
```

#### Información Familiar:
```
nombreConyuge    - Nombre del cónyuge
profesionConyuge - Profesión del cónyuge
nombreContactoEmergencia - Nombre contacto emergencia
direccionEmergencia - Dirección emergencia
movilEmergencia  - Móvil emergencia
```

#### Información Administrativa:
```
foto             - Nombre del archivo de foto
Estado           - Estado (1=Activo, 0=Inactivo)
fechaExpiracion  - Fecha de expiración (formato: YYYY-MM-DD)
accesoPermanente - Acceso permanente (1=Sí, 0=No)
es_residente     - Es residente (1=Sí, 0=No)
```

## Normalizaciones Automáticas

El sistema normaliza automáticamente:

### Campos convertidos a MAYÚSCULAS:
- Grado
- Nombres
- Paterno
- Materno
- sexo
- estadoCivil
- nrEmpleado
- puesto
- especialidadPrimaria
- Unidad
- unidadEspecifica
- categoria
- escalafon
- trabajoExterno
- calle
- numeroDepto
- poblacionVilla
- anexo
- prevision
- sistemaSalud
- regimenMatrimonial
- religion
- tipoVivienda
- nombreConyuge
- profesionConyuge
- nombreContactoEmergencia
- direccionEmergencia

### Campos convertidos a minúsculas:
- email1
- email2

### Campos que se trimean (eliminan espacios):
- Todos

### Campos de fecha que se validan:
- fechaNacimiento
- fechaIngreso
- fechaPresentacion
- fechaExpiracion

Formato esperado: **YYYY-MM-DD** (ej: 2000-01-15)

## Formatos de Archivo Soportados

### CSV con COMA como separador:
```csv
Grado,Nombres,Paterno,Materno,NrRut,Unidad,...
SARGENTO,JUAN,GONZALEZ,LOPEZ,12345678-9,A1,...
```

### CSV con PUNTO Y COMA como separador (como tu archivo):
```csv
Grado;Nombres;Paterno;Materno;NrRut;Unidad;...
SARGENTO;JUAN;GONZALEZ;LOPEZ;12345678-9;A1;...
```

### Excel (.xlsx):
Los campos van en la primera fila como encabezados.

**Nota**: El sistema detecta automáticamente si usas coma (,) o punto y coma (;).

## Validaciones en la Importación

### Validaciones automáticas:
✅ RUT debe estar en formato: 12345678-9 o 123456789
✅ Campos requeridos (Nombres, Paterno, NrRut) no pueden estar vacíos
✅ RUT duplicado: Se actualiza el registro existente
✅ Fechas: Validadas antes de guardar

### Si hay error:
❌ Se reporta la fila específica y el error
❌ Los datos no se guardan (rollback automático)
❌ Puedes corregir y reintentar

## Comportamiento al Subir

### Si el RUT YA EXISTE en BD:
→ Se **ACTUALIZA** el registro con todos los datos nuevos (PISA TODO)

### Si el RUT es NUEVO:
→ Se **CREA** un nuevo registro con los datos proporcionados

## Ejemplo de Archivo Completo

Tu archivo `planoApellido11.csv` tiene exactamente esta estructura:

```csv
Grado;Nombres;Paterno;Materno;NrRut;fechaNacimiento;sexo;estadoCivil;nrEmpleado;puesto;especialidadPrimaria;fechaIngreso;fechaPresentacion;Unidad;unidadEspecifica;categoria;escalafon;trabajoExterno;calle;numeroDepto;poblacionVilla;telefonoFijo;movil1;movil2;email1;email2;anexo;foto;prevision;sistemaSalud;regimenMatrimonial;religion;tipoVivienda;nombreConyuge;profesionConyuge;nombreContactoEmergencia;direccionEmergencia;movilEmergencia;Estado;fechaExpiracion;accesoPermanente;es_residente
SARGENTO;JUAN;GONZALEZ;LOPEZ;12345678-9;2000-01-15;M;Casado;EMP001;Sargento;Administración;2020-01-01;2020-02-01;A1;A1-1;Tropa;3º;No;Calle Principal;123;Santiago;987654321;912345678;912345679;juan@email.com;juan.alt@email.com;123;foto_001.jpg;AFP;Fonasa;Régimen Matrimonial;Católica;Casa;María García;Abogada;Roberto González;Calle 2 nº 456;987654322;1;2025-12-31;1;0
```

## Campos Actualizados en la Importación

**ANTES** (versión anterior):
- Solo 7 campos se actualizaban
- Grado, Nombres, Paterno, Materno, Unidad, Estado, es_residente

**AHORA** (versión mejorada):
- **TODOS los 40+ campos** se actualizan
- Puedes pisar cualquier información del personal

## Ventajas de la Actualización

✅ Sincronización completa de datos
✅ Actualización masiva de toda la información
✅ Compatibilidad con tus archivos existentes
✅ No necesitas campos adicionales en BD
✅ El RUT sigue siendo la clave única

## Casos de Uso Comunes

### 1. Actualización Anual
Subir archivo con datos actualizados de todo el personal
→ Se pisan los datos viejos con los nuevos

### 2. Integración de Datos
Combinar datos de múltiples fuentes
→ Todos los campos se sincronizan

### 3. Corrección Masiva
Si todos los teléfonos están mal
→ Subir nuevo archivo corregido

### 4. Alta Inicial
Cargar todos los empleados con información completa
→ Se crea todo desde cero

## Limitaciones / Consideraciones

⚠️ **No se actualiza**:
- ID del personal (identificador interno)
- El RUT NO se modifica en el UPDATE (se usa para identificar)

⚠️ **Campos de fecha**:
- Deben estar en formato YYYY-MM-DD
- Si está vacío, se guarda como NULL

⚠️ **Archivos grandes**:
- Se procesan en una sola transacción
- Si hay error, se revierte TODO
- Para +10,000 registros, puede tomar tiempo

## Cómo Usar tu Archivo

1. Tu archivo `planoApellido11.csv` ya tiene la estructura correcta
2. Solo necesitas:
   - Verificar que los RUT sean únicos
   - Asegurar que Nombres, Paterno, NrRut no estén vacíos
   - Verificar fechas en formato YYYY-MM-DD

3. Luego:
   - Abre la aplicación
   - Mantenedores → Personal
   - Click "Importar Masivo"
   - Selecciona tu archivo
   - Click "Importar"

## Soporte

Si tienes dudas:
- Consulta `IMPORTACION_PERSONAL.md` para detalles técnicos
- Lee `GUIA_RAPIDA.txt` para instrucciones rápidas
- Revisa `TEST_IMPORTACION.md` para casos de prueba

---

**Actualizado**: 2025-11-05
**Estado**: ✅ COMPLETAMENTE FUNCIONAL
**Campos soportados**: 40+
