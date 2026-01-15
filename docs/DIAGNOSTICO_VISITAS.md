# 🔍 Diagnóstico - Problema con Escaneo de Visitas

## El Problema

Cuando intentas escanear una **visita** en el pórtico, obtienes error **404** (no encontrado), pero el escaneo de **personal** funciona correctamente.

```
Error: HTTP 404: Not Found
Mensaje: "ID no encontrado en personal, vehículos, visitas, empleados de empresa o personal en comisión."
```

## Posibles Causas

### 1. ❌ La visita no existe en la tabla `visitas`
- Nunca fue creada
- El RUT que escaneas no coincide con ningún registro

### 2. ❌ La visita NO está autorizada
- `status` ≠ `'autorizado'`
- La fecha de autorización expiró

### 3. ❌ Datos inconsistentes en la BD
- RUT mal capturado
- Campo RUT vacío

## Herramienta de Diagnóstico

He creado un archivo para ayudarte a diagnosticar: **`test-portico-debug.html`**

### Cómo Usarlo

1. **Abre el archivo en tu navegador:**
   ```
   http://localhost/Desarrollo/acceso/test-portico-debug.html
   ```

2. **Sigue los pasos en orden:**

   **Paso 1: Ver todas las visitas**
   - Click en "Probar GET de Visitas"
   - Verás una tabla con TODAS las visitas en tu BD
   - **Importante:** Anota los RUT de visitas que tengan `status = 'autorizado'`

   **Paso 2: Probar escaneo**
   - Ingresa un RUT de una visita autorizada
   - Click en "Probar Escaneo"
   - Verás si funciona o qué error retorna

   **Paso 3: Crear visita de prueba (si es necesario)**
   - Si no hay visitas autorizadas, copia el SQL que aparece en el paso 3
   - Ejecuta en tu base de datos (phpMyAdmin o consola MySQL)
   - Esto crea una visita de prueba automáticamente

## Consultas SQL Útiles

### Ver todas las visitas
```sql
SELECT id, nombre, rut, empresa, status, acceso_permanente, fecha_expiracion, en_lista_negra
FROM visitas
ORDER BY id DESC;
```

### Ver visitas autorizadas (las que deberían funcionar)
```sql
SELECT id, nombre, rut, empresa, status, fecha_expiracion
FROM visitas
WHERE status = 'autorizado'
AND (acceso_permanente = 1 OR fecha_expiracion >= CURDATE());
```

### Crear una visita de prueba
```sql
INSERT INTO visitas (nombre, rut, empresa, tipo, status, acceso_permanente, fecha_expiracion, en_lista_negra)
VALUES
('Juan Pérez Visita', '98765432', 'Empresa Prueba', 'proveedor', 'autorizado', 0, DATE_ADD(NOW(), INTERVAL 30 DAY), 0);
```

### Autorizar una visita existente
```sql
UPDATE visitas
SET status = 'autorizado',
    acceso_permanente = 0,
    fecha_expiracion = DATE_ADD(NOW(), INTERVAL 30 DAY)
WHERE rut = '12345678';
```

## Flujo de Búsqueda en portico.php

El código PHP busca en este orden:

```
1. Personal (tabla personal.personal)
   ↓ (si no encuentra)
2. Vehículos (tabla acceso.vehiculos)
   ↓ (si no encuentra)
3. Visitas (tabla acceso.visitas) ← ¡Aquí es donde busca!
   ↓ (si no encuentra)
4. Empleados de Empresa (tabla acceso.empresa_empleados)
   ↓ (si no encuentra)
5. Personal en Comisión (tabla personal_db.personal_comision)
   ↓ (si no encuentra)
6. ERROR 404
```

Para que una **visita** funcione:
1. Debe existir en `acceso.visitas`
2. Debe tener `status = 'autorizado'`
3. Debe tener:
   - `acceso_permanente = 1` O
   - `fecha_expiracion >= HOY`

## Checklist de Verificación

- [ ] ¿La visita existe en la tabla `visitas`?
- [ ] ¿El RUT que escaneas coincide exactamente?
- [ ] ¿El `status` de la visita es 'autorizado'?
- [ ] ¿La fecha de expiración es futura o NULL?
- [ ] ¿El campo `en_lista_negra` = 0?

Si alguno es NO, eso es el problema.

## Próximos Pasos

1. Abre `test-portico-debug.html`
2. Ejecuta "Probar GET de Visitas"
3. Comparte los resultados:
   - ¿Qué visitas aparecen?
   - ¿Cuáles tienen status 'autorizado'?
   - ¿Qué RUT intentaste escanear?

Con esa información podré ayudarte a corregir el problema específico.

