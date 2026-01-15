# Cambios Realizados - Mensajes de Error Descriptivos en Pórtico

## Fecha: 2025-10-27
## Descripción: Agregar mensajes de error específicos que indiquen el motivo del rechazo

---

## Problema Original
Cuando un vehículo era rechazado, el mensaje era genérico:
```
"Acceso denegado para el vehículo [XY5678]. Autorización expirada o no válida."
```

El usuario no sabía específicamente por qué fue rechazado (fecha de inicio futura, expirada, sin autorización, etc.)

---

## Cambios Realizados

### Archivo: api/portico.php

#### 1. Variables de Razón de Rechazo (líneas 40-42)
Agregadas variables para rastrear el motivo específico:
```php
$unauthorized_vehicle_reason = null;
$unauthorized_visitor_reason = null;
```

#### 2. Lógica de Rechazo Detallada - Vehículos (líneas 67-120)

**Ahora captura razones específicas:**
- `"Status no autorizado"` - Vehículo no está autorizado
- `"Fecha de inicio: 2026-01-20 (aún no autorizado)"` - Fecha futura
- `"Acceso expirado desde: 2025-09-30"` - Fecha vencida
- `"Fecha de inicio inválida"` - Formato de fecha incorrecto
- `"Fecha de expiración inválida"` - Formato de fecha incorrecto
- `"Sin fecha de expiración válida"` - No tiene validez

#### 3. Lógica de Rechazo Detallada - Visitas (líneas 140-191)

Mismas razones que vehículos

#### 4. Mensajes de Error Mejorados (líneas 246-265)

**Ahora muestra:**
```php
"Acceso denegado para el vehículo [XY5678]: Fecha de inicio: 2026-01-20 (aún no autorizado)"
```

En lugar de:
```php
"Acceso denegado para el vehículo [XY5678]. Autorización expirada o no válida."
```

---

## Ejemplos de Mensajes Ahora

### Vehículo con Fecha Futura
**Antes:**
```
Acceso denegado para el vehículo [XY5678]. Autorización expirada o no válida.
```

**Ahora:**
```
Acceso denegado para el vehículo [XY5678]: Fecha de inicio: 2026-01-20 (aún no autorizado)
```

---

### Vehículo Expirado
**Antes:**
```
Acceso denegado para el vehículo [TEST123]. Autorización expirada o no válida.
```

**Ahora:**
```
Acceso denegado para el vehículo [TEST123]: Acceso expirado desde: 2025-09-30
```

---

### Visita con Fecha Futura
**Antes:**
```
Acceso denegado para la visita [JUAN PÉREZ]. Autorización expirada o no válida.
```

**Ahora:**
```
Acceso denegado para la visita [JUAN PÉREZ]: Fecha de inicio: 2025-12-01 (aún no autorizado)
```

---

### Visita Sin Fecha Válida
**Antes:**
```
Acceso denegado para la visita [MARIA GARCÍA]. Autorización expirada o no válida.
```

**Ahora:**
```
Acceso denegado para la visita [MARIA GARCÍA]: Sin fecha de expiración válida
```

---

## Razones de Rechazo Posibles

| Tipo | Razón | Mensaje |
|------|-------|---------|
| Vehículo/Visita | Status no autorizado | `Status no autorizado` |
| Vehículo/Visita | Fecha futura | `Fecha de inicio: YYYY-MM-DD (aún no autorizado)` |
| Vehículo/Visita | Expirado | `Acceso expirado desde: YYYY-MM-DD` |
| Vehículo/Visita | Fecha inicio inválida | `Fecha de inicio inválida` |
| Vehículo/Visita | Fecha expiración inválida | `Fecha de expiración inválida` |
| Vehículo/Visita | Sin expiración | `Sin fecha de expiración válida` |

---

## Beneficios

✓ **Información clara:** Usuario sabe exactamente por qué fue rechazado
✓ **Debugging fácil:** Administrador puede identificar rápidamente el problema
✓ **Mejor experiencia:** Mensajes más descriptivos y útiles
✓ **Auditoría:** Razones quedan registradas
✓ **Educativo:** Ayuda al usuario a entender los requisitos

---

## Archivos Modificados

- **C:\xampp\htdocs\Desarrollo\acceso\api\portico.php**
  - Líneas 40-42: Variables de razón de rechazo
  - Líneas 67-120: Lógica detallada para vehículos
  - Líneas 140-191: Lógica detallada para visitas
  - Líneas 246-265: Mensajes de error mejorados

---

## Impacto en el Sistema

✅ **Módulo Pórtico (Control de Acceso)**
- Mensajes más descriptivos
- Mejor feedback al usuario

✅ **Experiencia del Usuario**
- Entiende por qué fue rechazado
- Puede tomar acciones correctivas

✅ **Administración**
- Más fácil diagnosticar problemas
- Mejor auditoría

---

## Pruebas Recomendadas

1. **Vehículo con fecha futura**
   - Debe mostrar: `Fecha de inicio: 2026-01-20 (aún no autorizado)`

2. **Vehículo expirado**
   - Debe mostrar: `Acceso expirado desde: YYYY-MM-DD`

3. **Visita no autorizada**
   - Debe mostrar: `Status no autorizado`

4. **Vehículo válido**
   - Debe permitir acceso (no mostrar error)

---

## Estado Final

✓ Mensajes de error descriptivos implementados
✓ Razones de rechazo específicas para cada caso
✓ Mejor feedback al usuario
✓ Más fácil diagnóstico de problemas
✓ Listo para uso en producción

