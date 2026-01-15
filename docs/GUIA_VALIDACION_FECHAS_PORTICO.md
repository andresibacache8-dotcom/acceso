# Guía de Validación de Fechas en Pórtico

## Fecha: 2025-10-27
## Descripción: Cómo funcionan las validaciones de fecha de inicio y expiración

---

## Validaciones Implementadas

El módulo pórtico ahora valida **dos fechas** para cada vehículo/visita:

### 1. **Fecha de Inicio (Fecha Desde Cuando Puede Ingresar)**
- **¿Qué es?** La fecha a partir de la cual el vehículo está autorizado
- **¿Cuándo rechaza?** Si la fecha de inicio es **FUTURA** (aún no ha llegado)
- **Mensaje:** `"Fecha de inicio: YYYY-MM-DD (aún no autorizado)"`

**Ejemplo:**
```
Vehículo: XY5678
Fecha de Inicio: 2026-01-20
Fecha Hoy: 2025-10-27
Resultado: ❌ RECHAZADO (Falta tiempo)
Mensaje: "Fecha de inicio: 2026-01-20 (aún no autorizado)"
```

---

### 2. **Fecha de Expiración (Fecha Hasta Cuando Puede Ingresar)**
- **¿Qué es?** La fecha hasta la cual el vehículo está autorizado
- **¿Cuándo rechaza?** Si la fecha de expiración es **PASADA** (ya vencida)
- **Mensaje:** `"Acceso expirado desde: YYYY-MM-DD"`

**Ejemplo:**
```
Vehículo: ABC1234
Fecha de Expiración: 2025-09-30
Fecha Hoy: 2025-10-27
Resultado: ❌ RECHAZADO (Expirado)
Mensaje: "Acceso expirado desde: 2025-09-30"
```

---

## Flujo de Validación Completo

```
┌─────────────────────────────────────────┐
│  Usuario escanea patente en pórtico     │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  ¿Vehículo existe y está autorizado?    │
└──────────────┬──────────────────────────┘
               │
        ❌ NO  │  SÍ
               │
               ▼
      ┌────────────────────────┐
      │ Rechazar: Status no ok │
      └────────────────────────┘

               ▼
┌─────────────────────────────────────────┐
│  ¿Tiene fecha de inicio?                │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  ¿Fecha inicio <= hoy?                  │
└──────────────┬──────────────────────────┘
               │
        ❌ NO  │  SÍ
               │
               ▼
      ┌─────────────────────────────────┐
      │ Rechazar: "Aún no autorizado"   │
      └─────────────────────────────────┘

               ▼
┌─────────────────────────────────────────┐
│  ¿Acceso permanente?                    │
└──────────────┬──────────────────────────┘
               │
        SÍ ✅  │  NO
               │
               ▼
      ┌────────────────────────┐
      │ AUTORIZAR              │
      └────────────────────────┘

               ▼
┌─────────────────────────────────────────┐
│  ¿Tiene fecha de expiración?            │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  ¿Fecha expiracion >= hoy?              │
└──────────────┬──────────────────────────┘
               │
        ❌ NO  │  SÍ
               │
               ▼
      ┌──────────────────────────────┐
      │ Rechazar: "Acceso expirado"  │
      └──────────────────────────────┘

               ▼
      ┌────────────────────────┐
      │ AUTORIZAR              │
      └────────────────────────┘
```

---

## Ejemplos de Escenarios

### Escenario 1: Vehículo con Fecha Futura
```
Patente: XY5678
Status: autorizado
Fecha Inicio: 2026-01-20
Fecha Hoy: 2025-10-27
Acceso Permanente: 0
Fecha Expiración: 2026-12-31

Resultado: ❌ RECHAZADO
Mensaje: "Acceso denegado para el vehículo [XY5678]: Fecha de inicio: 2026-01-20 (aún no autorizado)"
```

---

### Escenario 2: Vehículo Expirado
```
Patente: ABC1234
Status: autorizado
Fecha Inicio: 2025-01-01
Fecha Hoy: 2025-10-27
Acceso Permanente: 0
Fecha Expiración: 2025-09-30

Resultado: ❌ RECHAZADO
Mensaje: "Acceso denegado para el vehículo [ABC1234]: Acceso expirado desde: 2025-09-30"
```

---

### Escenario 3: Vehículo Válido (En Rango)
```
Patente: SD4115
Status: autorizado
Fecha Inicio: 2025-01-15
Fecha Hoy: 2025-10-27
Acceso Permanente: 0
Fecha Expiración: 2025-12-31

Resultado: ✅ AUTORIZADO
Se registra: Entrada/Salida según última acción
```

---

### Escenario 4: Vehículo con Acceso Permanente
```
Patente: AB1234
Status: autorizado
Fecha Inicio: 2025-02-01
Fecha Hoy: 2025-10-27
Acceso Permanente: 1
Fecha Expiración: (ignorada)

Resultado: ✅ AUTORIZADO
Se registra: Entrada/Salida según última acción
```

---

### Escenario 5: Vehículo Sin Fecha de Expiración
```
Patente: TEST123
Status: autorizado
Fecha Inicio: 2025-01-01
Fecha Hoy: 2025-10-27
Acceso Permanente: 0
Fecha Expiración: (vacía)

Resultado: ❌ RECHAZADO
Mensaje: "Acceso denegado para el vehículo [TEST123]: Sin fecha de expiración válida"
```

---

## Mensajes de Error Posibles

### Para Vehículos:

| Razón | Mensaje |
|-------|---------|
| Status no autorizado | `Status no autorizado` |
| Fecha inicio futura | `Fecha de inicio: 2026-01-20 (aún no autorizado)` |
| Acceso expirado | `Acceso expirado desde: 2025-09-30` |
| Fecha inicio inválida | `Fecha de inicio inválida` |
| Fecha expiración inválida | `Fecha de expiración inválida` |
| Sin fecha expiración | `Sin fecha de expiración válida` |

### Para Visitas:

Mismos mensajes que vehículos, pero diciendo "visita" en lugar de "vehículo":

| Razón | Mensaje |
|-------|---------|
| Fecha inicio futura | `Fecha de inicio: 2026-01-20 (aún no autorizado)` |
| Acceso expirado | `Acceso expirado desde: 2025-09-30` |

---

## Cómo Crear Vehículos para Probar

### Para probar rechazo por fecha expirada:
1. Ir a Vehículos → Gestionar Vehículos
2. Crear vehículo con:
   - Patente: `TEST_EXP`
   - Fecha Inicio: `2025-01-01`
   - Acceso Permanente: `No`
   - Fecha Expiración: `2025-09-15` (fecha pasada)
3. Guardar
4. Ir a Control de Acceso (Pórtico)
5. Escanear `TEST_EXP`
6. Debería mostrar: `"Acceso expirado desde: 2025-09-15"`

---

### Para probar rechazo por fecha futura:
1. Ir a Vehículos → Gestionar Vehículos
2. Crear vehículo con:
   - Patente: `TEST_FUT`
   - Fecha Inicio: `2026-06-01` (fecha futura)
   - Acceso Permanente: `No`
   - Fecha Expiración: `2026-12-31`
3. Guardar
4. Ir a Control de Acceso (Pórtico)
5. Escanear `TEST_FUT`
6. Debería mostrar: `"Fecha de inicio: 2026-06-01 (aún no autorizado)"`

---

### Para probar autorización válida:
1. Ir a Vehículos → Gestionar Vehículos
2. Crear vehículo con:
   - Patente: `TEST_OK`
   - Fecha Inicio: `2025-01-01` (pasada)
   - Acceso Permanente: `No`
   - Fecha Expiración: `2025-12-31` (futura)
3. Guardar
4. Ir a Control de Acceso (Pórtico)
5. Escanear `TEST_OK`
6. Debería permitir acceso ✅

---

## Validaciones Implementadas

| Validación | Dónde | Estado |
|-----------|-------|--------|
| Fecha inicio futura | portico.php | ✅ Implementada |
| Fecha expiración pasada | portico.php | ✅ Implementada |
| Para vehículos | portico.php | ✅ Implementada |
| Para visitas | portico.php | ✅ Implementada |
| Mensajes descriptivos | api-client.js | ✅ Implementada |

---

## Resumen

✅ **Fecha de Inicio:** Rechaza si es futura (aún no autorizado)
✅ **Fecha de Expiración:** Rechaza si es pasada (expirado)
✅ **Mensajes claros:** El usuario sabe exactamente por qué fue rechazado
✅ **Funciona para:** Vehículos y Visitas

