# Prueba Rápida - Campo "Fecha de Inicio de Acceso"

## Checklist de Verificación

### 1. Descargar Plantilla Excel Actualizada
- [ ] Ir a: Vehículos → Carga Masiva
- [ ] Hacer clic: "Descargar plantilla Excel"
- [ ] Verificar que el archivo contiene las columnas:
  - patente
  - marca
  - modelo
  - tipo
  - tipo_vehiculo
  - **personalNrRut**
  - **fecha_inicio** ← NUEVO
  - acceso_permanente
  - fecha_expiracion

### 2. Revisar Datos de Ejemplo en Excel
- [ ] Fila 2: SD4115, TOYOTA, SANTA FE, VISITA, FURGON, 17964768, **2025-01-15**, 0, 2025-12-31
- [ ] Fila 3: AB1234, HONDA, CIVIC, FUNCIONARIO, AUTO, 12345678, **2025-02-01**, 1, (vacío)
- [ ] Fila 4: XY5678, FORD, FIESTA, EMPRESA, AUTO, 98765432, **2025-01-20**, 0, 2025-11-15

### 3. Verificar Plantilla CSV
- [ ] Descargar: "Descargar plantilla CSV"
- [ ] Abrir con editor de texto
- [ ] Primera línea debe contener: patente,marca,modelo,tipo,tipo_vehiculo,personalNrRut,**fecha_inicio**,acceso_permanente,fecha_expiracion
- [ ] Datos con fechas de inicio: 2025-01-15, 2025-02-01, 2025-01-20

### 4. Instrucciones Actualizadas
- [ ] En el modal de carga masiva, verificar que dice:
  - "El archivo debe tener las siguientes columnas: patente, marca, modelo, tipo, tipo_vehiculo, personalNrRut, **fecha_inicio**, acceso_permanente, fecha_expiracion"
- [ ] Las instrucciones incluyen:
  - "fecha_inicio: Fecha desde cuando el vehículo puede ingresar (YYYY-MM-DD)"

### 5. Prueba de Importación
- [ ] Descargar plantilla Excel
- [ ] Agregar una fila de prueba con fecha_inicio:
  ```
  TEST123,FORD,FIESTA,VISITANTE,AUTO,20123456,2025-03-01,0,2025-12-31
  ```
- [ ] Importar el archivo
- [ ] Verificar que se importa sin errores
- [ ] En la tabla de vehículos, el nuevo vehículo debe estar presente

### 6. Verificar en Base de Datos
- [ ] Abrir PhpMyAdmin o cliente de BD
- [ ] Ejecutar:
  ```sql
  SELECT patente, fecha_inicio, fecha_expiracion FROM vehiculos
  WHERE patente = 'TEST123' LIMIT 1;
  ```
- [ ] fecha_inicio debe mostrar: 2025-03-01

### 7. Verificar en Modal de Edición
- [ ] Hacer clic en "Editar" para el vehículo TEST123
- [ ] Campo "Fecha de Inicio de Acceso" debe mostrar: 2025-03-01
- [ ] Poder editar la fecha si es necesario
- [ ] Cambiar fecha a 2025-04-01 y guardar
- [ ] Verificar que se actualiza correctamente

### 8. Validar Consola (F12)
- [ ] Abrir F12 → Console
- [ ] No debe haber errores críticos
- [ ] Durante importación pueden aparecer logs de progreso

## Resumen de Cambios

| Aspecto | Antes | Ahora |
|---------|-------|-------|
| Columnas Excel | patente, marca, modelo, tipo, tipo_vehiculo, personalNrRut, acceso_permanente, fecha_expiracion | + **fecha_inicio** |
| Campos Importación | 8 columnas | 9 columnas |
| Datos Ejemplo | Fechas de expiración | + Fechas de inicio |
| Documentación | Básica | Mejorada con detalles |

## Datos de Prueba Listos

Puedes copiar esta fila directamente al Excel:

```
TEST001,FORD,FIESTA,PERSONAL,AUTO,12345678,2025-03-15,0,2025-12-31
TEST002,TOYOTA,COROLLA,EMPLEADO,AUTO,87654321,2025-04-01,1,
TEST003,HONDA,CIVIC,VISITA,AUTO,11223344,2025-02-20,0,2025-06-30
```

## Resultado Esperado

✓ Plantillas incluyen fecha_inicio
✓ Datos importados tienen fecha_inicio en BD
✓ Modal de edición muestra fecha_inicio
✓ Campo es opcional (puede estar vacío)
✓ Formato YYYY-MM-DD se acepta correctamente

---

**Última actualización**: 2025-10-27
**Versión**: 1.0
