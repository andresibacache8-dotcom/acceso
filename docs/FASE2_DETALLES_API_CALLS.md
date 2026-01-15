# 📊 REPORTE DETALLADO DE LLAMADAS API - MAIN.JS

**Fecha**: 25 de octubre de 2025  
**Archivo analizado**: `js/main.js` (4,038 líneas)  
**API actual**: `js/api.js` (543 líneas, 35+ métodos)  
**Objetivo**: Validar estructura para módulos API de Fase 2

---

## 1️⃣ API/PERSONAL.PHP (10 OPERACIONES)

### 📌 **GET - Obtener todos los registros**
```javascript
Línea: 1749
Endpoint: GET api/personal.php
Función: initPersonalModule()
Parámetros: ninguno
Uso: Carga inicial del módulo de personal

Código:
personalData = await api.getPersonal();
renderPersonalTable(personalData);
```

**Otras llamadas**:
- **Línea 2021**: Después de `createPersonal()` o `updatePersonal()`
- **Línea 2033**: Después de `deletePersonal()`
- **Línea 2436**: En `initVehiculoModule()` junto con `getVehiculos()` (Promise.all)
- **Línea 3080**: Después de `createVehiculo()`
- **Línea 3094**: Después de `deleteVehiculo()`
- **Línea 3460**: En `initVisitasModule()` para selector de personal

---

### 📌 **POST - Crear nuevo personal**
```javascript
Línea: 2017
Endpoint: POST api/personal.php
Función: handlePersonalFormSubmit()
Método HTTP: POST
Headers: { 'Content-Type': 'application/json' }
Parámetros (body JSON): {
    NrRut: string,          // RUT sin dígito verificador (7-8 dígitos)
    Nombres: string,
    Paterno: string,
    Materno: string,
    Grado: string,
    Cargo: string,
    Unidad: string,
    Compania: string,
    Departamento: string,
    Tipo: string,           // 'FUNCIONARIO', 'RESIDENTE', etc.
    Telefono: string,
    Email: string,
    Direccion: string,
    Foto: string            // URL o base64
}

Código:
await api.createPersonal(data);
showToast('Personal creado correctamente.', 'success');
modal.hide();
personalData = await api.getPersonal(); // ← Recarga inmediata
renderPersonalTable(personalData);
```

---

### 📌 **PUT - Actualizar personal existente**
```javascript
Línea: 2014
Endpoint: PUT api/personal.php
Función: handlePersonalFormSubmit()
Método HTTP: PUT
Headers: { 'Content-Type': 'application/json' }
Parámetros (body JSON): {
    id: number,             // ← Campo obligatorio para UPDATE
    NrRut: string,
    Nombres: string,
    Paterno: string,
    Materno: string,
    Grado: string,
    Cargo: string,
    Unidad: string,
    Compania: string,
    Departamento: string,
    Tipo: string,
    Telefono: string,
    Email: string,
    Direccion: string,
    Foto: string
}

Código:
if (id) {
    await api.updatePersonal(data);
    showToast('Personal actualizado correctamente.', 'success');
} else {
    await api.createPersonal(data);
}
```

---

### 📌 **DELETE - Eliminar personal**
```javascript
Línea: 2031
Endpoint: DELETE api/personal.php?id={id}
Función: deletePersonal(id)
Método HTTP: DELETE
Parámetros: id (query string)

Código:
if (confirm('¿Estás seguro de que quieres eliminar a esta persona?')) {
    await api.deletePersonal(id);
    showToast('Personal eliminado correctamente.', 'success');
    personalData = await api.getPersonal(); // ← Recarga inmediata
    renderPersonalTable(personalData);
}
```

---

### 📌 **GET - Buscar por RUT**
```javascript
Línea: 322
Endpoint: GET api/personal.php?rut={rut}
Función: handleRutLookup()
Parámetros: rut (string, 7-8 dígitos sin DV)
Retorno: Objeto persona o null si no existe

Código:
const personaByRut = await api.findPersonalByRut(rut);
if (personaByRut && personaByRut.Nombres) {
    const materno = (personaByRut.Materno === 'undefined' || personaByRut.Materno === null) ? '' : personaByRut.Materno;
    const nombreCompleto = `${personaByRut.Grado || ''} ${personaByRut.Nombres || ''} ${personaByRut.Paterno || ''} ${materno}`.trim();
    displayElement.textContent = nombreCompleto;
}

Otros usos:
- Línea 403: initHorasExtraModule() - Validar autorizador
- Línea 3872: searchEmpresaRepresentante() - Buscar representante de empresa
```

---

### 📌 **GET - Búsqueda avanzada (nombre o RUT + tipo)**
```javascript
Línea: 335
Endpoint: GET api/buscar_personal.php?query={query}&tipo={tipo}
Función: handleRutLookup()
Parámetros: 
    - query: string (nombre parcial o RUT)
    - tipo: string ('FUNCIONARIO', 'RESIDENTE', 'VISITA', etc.)
Retorno: Array de personas que coinciden

Código:
const results = await api.searchPersonal(rut, 'FUNCIONARIO');
if (results && results.length > 0) {
    const persona = results[0]; // ← Toma primer resultado
    const nombreCompleto = `${persona.Grado || ''} ${persona.Nombres || ''} ${persona.Paterno || ''} ${materno}`.trim();
    displayElement.textContent = nombreCompleto;
}

Otros usos:
- Línea 2233: handlePersonalSearch() - Búsqueda en módulo de vehículos
  const tipoAcceso = document.getElementById('tipo').value;
  const results = await api.searchPersonal(query, tipoAcceso);
```

---

### 📌 **GET - Obtener solo personal "dentro"**
```javascript
Endpoint: GET api/personal.php?status=inside
Función: getInsidePersonal() (definida en api.js pero NO usada en main.js)
Parámetros: status=inside
Propósito: Dashboard, reportes
Nota: Método DEFINIDO pero NO IMPLEMENTADO en UI actual
```

---

## 2️⃣ API/VEHICULOS.PHP (6 OPERACIONES)

### 📌 **GET - Obtener todos los vehículos**
```javascript
Línea: 2436
Endpoint: GET api/vehiculos.php
Función: initVehiculoModule()
Parámetros: ninguno
Uso: Carga inicial del módulo de vehículos

Código:
[vehiculosData, personalData] = await Promise.all([
    api.getVehiculos(), 
    api.getPersonal()
]); // ← Carga paralela con Promise.all
renderVehiculoTable(vehiculosData);

Otros usos:
- Línea 2906: Después de importación masiva de Excel
- Línea 3076: Después de updateVehiculo()
- Línea 3080: Después de createVehiculo()
- Línea 3094: Después de deleteVehiculo()
```

---

### 📌 **POST - Crear nuevo vehículo**
```javascript
Línea: 3078
Endpoint: POST api/vehiculos.php
Función: handleVehiculoFormSubmit()
Método HTTP: POST
Headers: { 'Content-Type': 'application/json' }
Parámetros (body JSON): {
    patente: string,                    // 5 formatos chilenos válidos
    marca: string,
    modelo: string,
    color: string,
    tipo: string,                       // 'particular', 'camioneta', 'bus', etc.
    tipo_vehiculo: string,              // 'LIVIANO', 'PESADO'
    asociado_id: number,                // ID del propietario
    asociado_tipo: string,              // 'personal', 'visita', 'empresa_empleado'
    seguro_vencimiento: string,         // Fecha YYYY-MM-DD
    revision_tecnica_vencimiento: string,
    permiso_circulacion_vencimiento: string,
    acceso_permanente: string,          // '0' o '1'
    fecha_expiracion: string|null,      // Solo si acceso_permanente = '0'
    observaciones: string
}

Código:
await api.createVehiculo(data);
showToast('Vehículo creado correctamente.', 'success');
[vehiculosData, personalData] = await Promise.all([
    api.getVehiculos(), 
    api.getPersonal()
]); // ← Recarga paralela
modal.hide();
renderVehiculoTable(vehiculosData);

Otro uso:
- Línea 2874: handleVehiculoAsociadoSubmit() - Creación desde modal de asociado
```

---

### 📌 **PUT - Actualizar vehículo existente**
```javascript
Línea: 3074
Endpoint: PUT api/vehiculos.php
Función: handleVehiculoFormSubmit()
Método HTTP: PUT
Headers: { 'Content-Type': 'application/json' }
Parámetros (body JSON): {
    id: number,                         // ← Campo obligatorio para UPDATE
    patente: string,
    marca: string,
    modelo: string,
    color: string,
    tipo: string,
    tipo_vehiculo: string,
    asociado_id: number,
    asociado_tipo: string,
    seguro_vencimiento: string,
    revision_tecnica_vencimiento: string,
    permiso_circulacion_vencimiento: string,
    acceso_permanente: string,
    fecha_expiracion: string|null,
    observaciones: string
}

Código:
if (id) {
    await api.updateVehiculo(data);
    showToast('Vehículo actualizado correctamente.', 'success');
    vehiculosData = await api.getVehiculos(); // ← Recarga solo vehículos
} else {
    await api.createVehiculo(data);
}
```

---

### 📌 **DELETE - Eliminar vehículo**
```javascript
Línea: 3092
Endpoint: DELETE api/vehiculos.php?id={id}
Función: deleteVehiculo(id)
Método HTTP: DELETE
Parámetros: id (query string)

Código:
if (confirm('¿Estás seguro de que quieres eliminar este vehículo?')) {
    await api.deleteVehiculo(id);
    showToast('Vehículo eliminado correctamente.', 'success');
    [vehiculosData, personalData] = await Promise.all([
        api.getVehiculos(), 
        api.getPersonal()
    ]); // ← Recarga ambos arrays
    renderVehiculoTable(vehiculosData);
}
```

---

### 📌 **GET - Obtener historial de cambios**
```javascript
Línea: 3136
Endpoint: GET api/vehiculo_historial.php?vehiculo_id={id}
Función: openHistorialModal(id, patente)
Parámetros: vehiculo_id (número)
Retorno: { historial: Array }

Código:
const historialData = await api.getVehiculoHistorial(id);

if (!historialData || !historialData.historial || historialData.historial.length === 0) {
    document.getElementById('historial-table-body').innerHTML = `
        <tr>
            <td colspan="5" class="text-center py-4 text-muted">
                No hay registros de cambios para este vehículo
            </td>
        </tr>
    `;
    return;
}

// Renderizar historial en tabla modal
historialData.historial.forEach(cambio => {
    // ... renderizar cada cambio
});
```

---

## 3️⃣ API/VISITAS.PHP (6 OPERACIONES)

### 📌 **GET - Obtener todas las visitas**
```javascript
Línea: 3520
Endpoint: GET api/visitas.php
Función: initVisitasModule()
Parámetros: ninguno
Uso: Carga inicial del módulo de visitas

Código:
visitasData = await api.getVisitas();
renderVisitasTable(visitasData);

Otros usos:
- Línea 3655: Después de createVisita() o updateVisita()
- Línea 3667: Después de deleteVisita()
- Línea 3682: Después de toggleBlacklistVisita()
```

---

### 📌 **POST - Crear nueva visita**
```javascript
Línea: 3651
Endpoint: POST api/visitas.php
Función: handleVisitaFormSubmit()
Método HTTP: POST
Headers: { 'Content-Type': 'application/json' }
Parámetros (body JSON): {
    rut: string,                        // RUT con o sin DV
    nombres: string,
    apellidos: string,
    empresa: string,
    motivo_visita: string,
    unidad_destino: string,
    fecha_inicio: string,               // YYYY-MM-DD
    fecha_fin: string,                  // YYYY-MM-DD
    telefono: string,
    email: string,
    observaciones: string,
    en_lista_negra: number              // 0 o 1
}

Código:
await api.createVisita(data);
showToast('Visita creada correctamente.', 'success');
modal.hide();
visitasData = await api.getVisitas(); // ← Recarga inmediata
renderVisitasTable(visitasData);
```

---

### 📌 **PUT - Actualizar visita existente**
```javascript
Línea: 3648
Endpoint: PUT api/visitas.php
Función: handleVisitaFormSubmit()
Método HTTP: PUT
Headers: { 'Content-Type': 'application/json' }
Parámetros (body JSON): {
    id: number,                         // ← Campo obligatorio para UPDATE
    rut: string,
    nombres: string,
    apellidos: string,
    empresa: string,
    motivo_visita: string,
    unidad_destino: string,
    fecha_inicio: string,
    fecha_fin: string,
    telefono: string,
    email: string,
    observaciones: string,
    en_lista_negra: number
}

Código:
if (id) {
    await api.updateVisita(data);
    showToast('Visita actualizada correctamente.', 'success');
} else {
    await api.createVisita(data);
}
modal.hide();
visitasData = await api.getVisitas();
renderVisitasTable(visitasData);
```

---

### 📌 **DELETE - Eliminar visita**
```javascript
Línea: 3665
Endpoint: DELETE api/visitas.php?id={id}
Función: deleteVisita(id)
Método HTTP: DELETE
Parámetros: id (query string)

Código:
if (confirm('¿Estás seguro de que quieres eliminar esta visita?')) {
    await api.deleteVisita(id);
    showToast('Visita eliminada correctamente.', 'success');
    visitasData = await api.getVisitas(); // ← Recarga inmediata
    renderVisitasTable(visitasData);
}
```

---

### 📌 **PUT - Toggle lista negra**
```javascript
Línea: 3680
Endpoint: PUT api/visitas.php?action=toggle_blacklist&id={id}
Función: toggleBlacklistVisita(id, newStatus)
Método HTTP: PUT
Headers: { 'Content-Type': 'application/json' }
Parámetros:
    - Query: action=toggle_blacklist, id={id}
    - Body JSON: { en_lista_negra: number } // 0 o 1

Código:
const isBlacklisted = row.getAttribute('data-blacklist');
const newStatus = isBlacklisted === 'true' ? 0 : 1;
const actionText = newStatus === 1 ? 'añadir a la' : 'quitar de la';

if (confirm(`¿Estás seguro de que quieres ${actionText} lista negra a esta visita?`)) {
    await api.toggleBlacklistVisita(id, newStatus);
    showToast('Estado de lista negra actualizado.', 'success');
    visitasData = await api.getVisitas(); // ← Recarga inmediata
    renderVisitasTable(visitasData);
}
```

---

## 4️⃣ API/LOG_ACCESS.PHP + PORTICO.PHP (13 OPERACIONES)

### 📌 **GET - Obtener logs por tipo (5 tipos)**
```javascript
Líneas: 505-509 (loadAndRenderPorticoLogs)
Endpoint: GET api/log_access.php?target_type={type}&nocache={timestamp}
Función: loadAndRenderPorticoLogs()
Parámetros: 
    - target_type: 'personal' | 'vehiculo' | 'visita' | 'personal_comision' | 'empresa_empleado'
    - nocache: timestamp (prevenir caché)

Código:
const [personalLogs, vehiculoLogs, visitaLogs, comisionLogs, empresaLogs] = await Promise.all([
    api.getAccessLogs('personal'),
    api.getAccessLogs('vehiculo'),
    api.getAccessLogs('visita'),
    api.getAccessLogs('personal_comision'),
    api.getAccessLogs('empresa_empleado')
]); // ← 5 llamadas paralelas con Promise.all

porticoAllLogs = [...personalLogs, ...vehiculoLogs, ...visitaLogs, ...comisionLogs, ...empresaLogs];
porticoAllLogs.sort((a, b) => new Date(b.log_time) - new Date(a.log_time));
renderPorticoLogTable(porticoAllLogs);

Otros usos:
- Líneas 600-604: Después de logPorticoAccess() (refresh de logs)
- Línea 2045: initControlPersonalModule() - Solo logs de personal
- Línea 3352: initControlVehiculoModule() - Solo logs de vehículos
- Línea 3694: initControlVisitasModule() - Solo logs de visitas
```

---

### 📌 **POST - Registrar acceso manual (3 tipos)**
```javascript
Línea: 2058 (Personal)
Endpoint: POST api/log_access.php
Función: handleScanControlPersonalSubmit()
Método HTTP: POST
Headers: { 'Content-Type': 'application/json' }
Parámetros (body JSON): {
    target_id: number|string,           // ID del personal
    target_type: 'personal',
    punto_acceso: 'oficina'             // Punto de acceso predefinido
}

Código:
const result = await api.logAccess(targetId, 'personal', 'oficina');
showToast(result.message || 'Acceso registrado.');
scanInput.value = '';
renderPersonalScanFeedback(result, 'success');

Línea: 3365 (Vehículos)
const result = await api.logAccess(targetId, 'vehiculo');
// Sin punto_acceso (usa 'desconocido' por defecto)

Línea: 3707 (Visitas)
const result = await api.logAccess(targetId, 'visita');
```

---

### 📌 **POST - Registrar acceso por pórtico (inteligente)**
```javascript
Línea: 552
Endpoint: POST api/portico.php?nocache={timestamp}
Función: handleScanPorticoSubmit()
Método HTTP: POST
Headers: { 'Content-Type': 'application/json' }
Parámetros (body JSON): {
    id: number|string                   // RUT o ID escaneado
}
Retorno: {
    action: 'entrada' | 'salida' | 'clarification_required',
    message: string,
    type: 'personal' | 'vehiculo' | 'visita' | 'personal_comision' | 'empresa_empleado',
    name: string,
    needs_clarification: boolean,       // Si requiere aclaración
    person_id: number                   // Para clarification modal
}

Código:
const result = await api.logPorticoAccess(targetId);

console.log('Respuesta de portico.php:', result);
playScanSound('success');

if (result.needs_clarification) {
    showClarificationModal(result.person_id, result.name, refreshPortico);
} else {
    renderPorticoScanFeedback(result, 'success');
}

// Actualizar logs después de registrar
const [personalLogs, vehiculoLogs, visitaLogs, comisionLogs, empresaLogs] = await Promise.all([
    api.getAccessLogs('personal'),
    api.getAccessLogs('vehiculo'),
    api.getAccessLogs('visita'),
    api.getAccessLogs('personal_comision'),
    api.getAccessLogs('empresa_empleado')
]);
```

---

### 📌 **POST - Registrar acceso con aclaración**
```javascript
Línea: 920
Endpoint: POST api/log_clarified_access.php
Función: showClarificationModal() callback
Método HTTP: POST
Headers: { 'Content-Type': 'application/json' }
Parámetros (body JSON): {
    person_id: number,                  // ID del personal
    reason: string,                     // 'servicio', 'visita_familiar', 'otro'
    details: string                     // Detalles adicionales (opcional)
}
Uso: Cuando el pórtico requiere aclaración (funcionario fuera de horario)

Código:
const result = await api.logClarifiedAccess({
    person_id: personId,
    reason: reason,
    details: details
});

modal.hide();
showToast(result.message || 'Acceso registrado con éxito.', 'success');
renderPorticoScanFeedback(result, 'success');
if (refreshCallback) {
    await refreshCallback(); // ← Refresh de logs
}
```

---

## 🎯 RESUMEN DE PATRONES IDENTIFICADOS

### **Patrón 1: Recarga después de CRUD**
```javascript
// Se repite en Personal, Vehículos, Visitas, Comisión, Empresas
await api.createX(data);                    // ← Operación CRUD
xData = await api.getX();                   // ← Recarga inmediata
renderXTable(xData);                        // ← Re-render
```

### **Patrón 2: Promise.all para cargas paralelas**
```javascript
// Vehículos necesita Personal para asociaciones
[vehiculosData, personalData] = await Promise.all([
    api.getVehiculos(), 
    api.getPersonal()
]);

// Logs del pórtico (5 tipos en paralelo)
const [personalLogs, vehiculoLogs, visitaLogs, comisionLogs, empresaLogs] = 
    await Promise.all([
        api.getAccessLogs('personal'),
        api.getAccessLogs('vehiculo'),
        api.getAccessLogs('visita'),
        api.getAccessLogs('personal_comision'),
        api.getAccessLogs('empresa_empleado')
    ]);
```

### **Patrón 3: Búsqueda con fallback**
```javascript
// Buscar por RUT exacto primero
const personaByRut = await api.findPersonalByRut(rut);
if (personaByRut && personaByRut.Nombres) {
    // Encontrado por RUT
} else {
    // Fallback: búsqueda por nombre/tipo
    const results = await api.searchPersonal(rut, 'FUNCIONARIO');
}
```

### **Patrón 4: Validación antes de eliminar**
```javascript
if (confirm('¿Estás seguro de que quieres eliminar...?')) {
    await api.deleteX(id);
    showToast('X eliminado correctamente.', 'success');
    xData = await api.getX(); // ← Recarga
}
```

---

## ✅ VALIDACIÓN DE ESTRUCTURA DE MÓDULOS API

### **Módulo 1: personal-api.js** ✅
```javascript
Métodos necesarios (7):
✅ getAll()                  → personal.php (GET)
✅ getByRut(rut)             → personal.php?rut=X (GET)
✅ search(query, tipo)       → buscar_personal.php?query=X&tipo=Y (GET)
✅ create(data)              → personal.php (POST)
✅ update(data)              → personal.php (PUT) [requiere data.id]
✅ delete(id)                → personal.php?id=X (DELETE)
🔶 getInsideOnly()           → personal.php?status=inside (GET) [futuro]
```

### **Módulo 2: vehiculos-api.js** ✅
```javascript
Métodos necesarios (5):
✅ getAll()                  → vehiculos.php (GET)
✅ getHistorial(vehiculoId)  → vehiculo_historial.php?vehiculo_id=X (GET)
✅ create(data)              → vehiculos.php (POST)
✅ update(data)              → vehiculos.php (PUT) [requiere data.id]
✅ delete(id)                → vehiculos.php?id=X (DELETE)
```

### **Módulo 3: visitas-api.js** ✅
```javascript
Métodos necesarios (5):
✅ getAll()                  → visitas.php (GET)
✅ create(data)              → visitas.php (POST)
✅ update(data)              → visitas.php (PUT) [requiere data.id]
✅ delete(id)                → visitas.php?id=X (DELETE)
✅ toggleBlacklist(id, status) → visitas.php?action=toggle_blacklist&id=X (PUT)
```

### **Módulo 4: access-logs-api.js** ✅
```javascript
Métodos necesarios (5):
✅ getByType(type)           → log_access.php?target_type=X (GET)
✅ getAllTypes()             → Promise.all de 5 tipos (GET × 5)
✅ logManual(targetId, targetType, puntoAcceso) → log_access.php (POST)
✅ logPortico(id)            → portico.php (POST) [inteligente]
✅ logClarified(data)        → log_clarified_access.php (POST)
```

---

## 📝 NOTAS IMPORTANTES

### **1. Campos obligatorios para UPDATE**
```javascript
// Todos los UPDATE requieren 'id' en el body
data.id = editingId; // ← Obligatorio antes de updateX()
```

### **2. Diferencia entre endpoints de búsqueda**
```javascript
// Búsqueda EXACTA por RUT (un solo resultado o null)
personal.php?rut=12345678

// Búsqueda AVANZADA (múltiples resultados)
buscar_personal.php?query=Juan&tipo=FUNCIONARIO
```

### **3. Timestamps anti-caché**
```javascript
// Los logs usan timestamp para evitar caché del navegador
log_access.php?target_type=personal&nocache=1730000000000
portico.php?nocache=1730000000000
```

### **4. Headers consistentes**
```javascript
// Todos los POST/PUT usan:
headers: { 'Content-Type': 'application/json' }
```

### **5. Tipos de target_type válidos**
```javascript
'personal'             → Personal militar/civil
'vehiculo'             → Vehículos
'visita'               → Visitas externas
'personal_comision'    → Personal en comisión
'empresa_empleado'     → Empleados de empresas externas
```

---

## 🚀 PRÓXIMO PASO: CREAR MÓDULOS API

Con esta información validada, ahora podemos crear:

1. **js/api/personal-api.js** (7 métodos)
2. **js/api/vehiculos-api.js** (5 métodos)
3. **js/api/visitas-api.js** (5 métodos)
4. **js/api/access-logs-api.js** (5 métodos)

**Estructura base de cada módulo**:
```javascript
import ApiClient from './api-client.js';

export class XxxApi {
    constructor() {
        this.client = new ApiClient();
        this.endpoint = 'xxx.php';
    }

    async getAll() { ... }
    async create(data) { ... }
    async update(data) { ... }
    async delete(id) { ... }
}

export default new XxxApi();
```

---

**¿Procedo a crear los 4 módulos API de PRIORIDAD ALTA?** 🎯
