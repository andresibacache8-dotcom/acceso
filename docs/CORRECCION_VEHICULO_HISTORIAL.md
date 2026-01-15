# 🔧 Análisis - Tabla `vehiculo_historial`

## 📋 El Problema

Hay una **inconsistencia** entre el nombre de campos en tu tabla y lo que espera el código PHP:

### Estructura de Tu Tabla
```sql
personalId_anterior
personalId_nuevo
```

### Lo que Espera el Código PHP
```sql
asociado_id_anterior
asociado_id_nuevo
```

**Referencias encontradas:**
- `vehiculos.php` línea ~: Intenta insertar en campos `asociado_id_anterior`, `asociado_id_nuevo`
- `vehiculo_historial.php` línea 44-49: Intenta leer desde campos `asociado_id_anterior`, `asociado_id_nuevo`

## ❌ Consecuencias

Si intentas usar la funcionalidad de historial de vehículos:
1. **Al actualizar vehículos:** Error SQL (campos no existen)
2. **Al ver historial:** Datos vacíos o error
3. **Inconsistencia:** Nombre `personalId` no refleja que puede ser visita o empresa

## ✅ SOLUCIONES

### OPCIÓN 1: Renombrar Campos en la Tabla (Recomendado)

**Ventajas:**
- ✅ Mantiene consistencia con el código
- ✅ Refleja mejor que puede ser asociado de cualquier tipo
- ✅ Alinea todo el proyecto
- ✅ No requiere cambios en PHP

**Pasos:**

1. **Abrir phpMyAdmin**
2. **Ir a tabla `vehiculo_historial`**
3. **Renombrar campo `personalId_anterior` → `asociado_id_anterior`:**
   ```sql
   ALTER TABLE vehiculo_historial
   CHANGE COLUMN personalId_anterior asociado_id_anterior INT(11) NULL;
   ```

4. **Renombrar campo `personalId_nuevo` → `asociado_id_nuevo`:**
   ```sql
   ALTER TABLE vehiculo_historial
   CHANGE COLUMN personalId_nuevo asociado_id_nuevo INT(11) NULL;
   ```

5. **Verificar:**
   ```sql
   DESCRIBE vehiculo_historial;
   ```

Después de esto, **el código funcionará sin cambios**.

---

### OPCIÓN 2: Cambiar el Código PHP

**Ventajas:**
- ✅ No modifica la estructura de BD
- ❌ Pero mantiene inconsistencia con tabla `vehiculos` que usa `asociado_id`
- ❌ Requiere cambios en 2 archivos PHP

**Pasos (NO RECOMENDADO - para referencia):**

En `vehiculos.php`:
```php
// Cambiar
(vehiculo_id, patente, asociado_id_anterior, asociado_id_nuevo, ...)
// Por
(vehiculo_id, patente, personalId_anterior, personalId_nuevo, ...)
```

En `vehiculo_historial.php`:
```php
// Cambiar
LEFT JOIN ... ON vh.asociado_id_anterior = ...
// Por
LEFT JOIN ... ON vh.personalId_anterior = ...
```

---

## 🎯 RECOMENDACIÓN

**Usa OPCIÓN 1** (Renombrar campos en BD)

Porque:
1. Tu tabla `vehiculos` usa `asociado_id` / `asociado_tipo`
2. El código PHP ya está actualizado con estos nombres
3. Es más mantenible a largo plazo
4. Refleja mejor que puede ser cualquier tipo de asociado

## 📊 Comparación

| Aspecto | Tu Tabla Actual | Después OPCIÓN 1 |
|---------|-----------------|------------------|
| Campo 1 | `personalId_anterior` | `asociado_id_anterior` |
| Campo 2 | `personalId_nuevo` | `asociado_id_nuevo` |
| Consistencia | ❌ Inconsistente | ✅ Consistente |
| Código PHP | ❌ Error SQL | ✅ Funciona |
| Refleja realidad | ❌ Solo personal | ✅ Cualquier asociado |

## 🔍 Campos Completos de `vehiculo_historial`

Después de renombrar, tu tabla tendrá:

```sql
id (Primaria)
vehiculo_id (Índice) - ID del vehículo
patente (varchar) - Patente del vehículo
asociado_id_anterior (Índice) ← RENOMBRADO
asociado_id_nuevo (Índice) ← RENOMBRADO
fecha_cambio (datetime) - Cuándo cambió
usuario_id (Índice) - Quién hizo el cambio
tipo_cambio (enum) - creacion, actualizacion, cambio_propietario, eliminacion
detalles (text) - JSON con detalles del cambio
```

## ✨ Próximos Pasos

1. Ejecuta los comandos SQL de renombramiento (OPCIÓN 1)
2. Actualiza un vehículo en la aplicación
3. Verifica que se registre el historial correctamente
4. Consulta el historial del vehículo

## 🚀 Después de Corregir

El módulo de historial de vehículos funcionará correctamente:
- ✅ Se registrarán cambios automáticamente
- ✅ Se mostrará quién cambió qué y cuándo
- ✅ Se verá el propietario anterior y actual
- ✅ Compatible con personal, visitas y empleados de empresa

---

**Recomendación:** Ejecuta OPCIÓN 1 (renombrar campos)

