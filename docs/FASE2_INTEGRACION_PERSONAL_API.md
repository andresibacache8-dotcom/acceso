# 📋 ANÁLISIS DE INTEGRACIÓN - PERSONAL-API.JS

**Fecha**: 25 de octubre de 2025  
**Archivo a modificar**: `js/main.js` (4,038 líneas)  
**Módulo a integrar**: `js/api/personal-api.js` (10,307 bytes, 260 líneas)

---

## 📊 RESUMEN EJECUTIVO

| Métrica | Valor |
|---------|-------|
| **Total de llamadas encontradas** | **15 llamadas** (10 únicas) |
| **Métodos API afectados** | 6 métodos |
| **Funciones afectadas** | 8 funciones en main.js |
| **Líneas a modificar** | 15 líneas |

---

## 🎯 PASO 1: AGREGAR IMPORT

**Ubicación**: Inicio de `js/main.js`, después de las validaciones de sesión

**ANTES** (línea ~6):
```javascript
// Guardián de la página: redirige a login si no se ha iniciado sesión.
if (sessionStorage.getItem('isLoggedIn') !== 'true') {
    window.location.href = 'login.html';
}

// ============================================================================
// IMPORTS
// ============================================================================
```

**DESPUÉS** (agregar después de línea 6):
```javascript
// Guardián de la página: redirige a login si no se ha iniciado sesión.
if (sessionStorage.getItem('isLoggedIn') !== 'true') {
    window.location.href = 'login.html';
}

// ============================================================================
// IMPORTS
// ============================================================================
import personalApi from './api/personal-api.js';
```

---

## 📝 PASO 2: REEMPLAZOS NECESARIOS

### ✅ **1. api.findPersonalByRut() → personalApi.findByRut()** (3 ocurrencias)

#### **Ocurrencia 1/3:**
```
LÍNEA: 322
FUNCIÓN: handleRutLookup()
CONTEXTO: Búsqueda de personal por RUT para autocompletar nombre
```

**ANTES:**
```javascript
320:        try {
321:            // Primero intentar buscar por RUT
322:            const personaByRut = await api.findPersonalByRut(rut);
323:            if (personaByRut && personaByRut.Nombres) {
324:                const materno = (personaByRut.Materno === 'undefined' || personaByRut.Materno === null) ? '' : personaByRut.Materno;
```

**DESPUÉS:**
```javascript
320:        try {
321:            // Primero intentar buscar por RUT
322:            const personaByRut = await personalApi.findByRut(rut);
323:            if (personaByRut && personaByRut.Nombres) {
324:                const materno = (personaByRut.Materno === 'undefined' || personaByRut.Materno === null) ? '' : personaByRut.Materno;
```

---

#### **Ocurrencia 2/3:**
```
LÍNEA: 403
FUNCIÓN: initHorasExtraModule() - Agregar personal a lista
CONTEXTO: Validación de RUT al agregar personal a horas extra
```

**ANTES:**
```javascript
401:            try {
402:                const persona = await api.findPersonalByRut(rut);
403:                if (persona && persona.Nombres) {
404:                    const nombreCompleto = `${persona.Grado || ''} ${persona.Nombres || ''} ${persona.Paterno || ''} ${persona.Materno || ''}`.trim();
```

**DESPUÉS:**
```javascript
401:            try {
402:                const persona = await personalApi.findByRut(rut);
403:                if (persona && persona.Nombres) {
404:                    const nombreCompleto = `${persona.Grado || ''} ${persona.Nombres || ''} ${persona.Paterno || ''} ${persona.Materno || ''}`.trim();
```

---

#### **Ocurrencia 3/3:**
```
LÍNEA: 3872
FUNCIÓN: initEmpresasModule() - Buscar representante
CONTEXTO: Búsqueda de representante de empresa por RUT
```

**ANTES:**
```javascript
3870:            try {
3871:                const personal = await api.findPersonalByRut(rut);
3872:                if (personal) {
3873:                    const nombreCompleto = `${personal.Grado || ''} ${personal.Nombres} ${personal.Paterno}`.trim();
```

**DESPUÉS:**
```javascript
3870:            try {
3871:                const personal = await personalApi.findByRut(rut);
3872:                if (personal) {
3873:                    const nombreCompleto = `${personal.Grado || ''} ${personal.Nombres} ${personal.Paterno}`.trim();
```

---

### ✅ **2. api.searchPersonal() → personalApi.search()** (2 ocurrencias)

#### **Ocurrencia 1/2:**
```
LÍNEA: 335
FUNCIÓN: handleRutLookup()
CONTEXTO: Búsqueda por nombre si RUT no encuentra resultado
```

**ANTES:**
```javascript
333:            // Si no se encuentra por RUT, intentar buscar como FUNCIONARIO
334:            const results = await api.searchPersonal(rut, 'FUNCIONARIO');
335:            if (results && results.length > 0) {
336:                const persona = results[0]; // Tomar el primer resultado
```

**DESPUÉS:**
```javascript
333:            // Si no se encuentra por RUT, intentar buscar como FUNCIONARIO
334:            const results = await personalApi.search(rut, 'FUNCIONARIO');
335:            if (results && results.length > 0) {
336:                const persona = results[0]; // Tomar el primer resultado
```

---

#### **Ocurrencia 2/2:**
```
LÍNEA: 2233
FUNCIÓN: handlePersonalSearch() - Módulo de vehículos
CONTEXTO: Búsqueda de propietario para asociar vehículo
```

**ANTES:**
```javascript
2231:        // Obtener el tipo de acceso seleccionado
2232:        const tipoAcceso = document.getElementById('tipo').value;
2233:        const results = await api.searchPersonal(query, tipoAcceso);
2234:        
2235:        if (!results || results.length === 0) {
```

**DESPUÉS:**
```javascript
2231:        // Obtener el tipo de acceso seleccionado
2232:        const tipoAcceso = document.getElementById('tipo').value;
2233:        const results = await personalApi.search(query, tipoAcceso);
2234:        
2235:        if (!results || results.length === 0) {
```

---

### ✅ **3. api.getPersonal() → personalApi.getAll()** (7 ocurrencias)

#### **Ocurrencia 1/7:**
```
LÍNEA: 1749
FUNCIÓN: initPersonalModule()
CONTEXTO: Carga inicial del módulo de personal
```

**ANTES:**
```javascript
1747:        try {
1748:            personalData = await api.getPersonal();
1749:            renderPersonalTable(personalData);
1750:        } catch (error) {
```

**DESPUÉS:**
```javascript
1747:        try {
1748:            personalData = await personalApi.getAll();
1749:            renderPersonalTable(personalData);
1750:        } catch (error) {
```

---

#### **Ocurrencia 2/7:**
```
LÍNEA: 2021
FUNCIÓN: handlePersonalFormSubmit()
CONTEXTO: Recarga después de crear/actualizar personal
```

**ANTES:**
```javascript
2019:            }
2020:            modal.hide();
2021:            personalData = await api.getPersonal();
2022:            renderPersonalTable(personalData);
2023:        } catch (error) {
```

**DESPUÉS:**
```javascript
2019:            }
2020:            modal.hide();
2021:            personalData = await personalApi.getAll();
2022:            renderPersonalTable(personalData);
2023:        } catch (error) {
```

---

#### **Ocurrencia 3/7:**
```
LÍNEA: 2033
FUNCIÓN: deletePersonal()
CONTEXTO: Recarga después de eliminar personal
```

**ANTES:**
```javascript
2031:                await api.deletePersonal(id);
2032:                showToast('Personal eliminado correctamente.', 'success');
2033:                personalData = await api.getPersonal();
2034:                renderPersonalTable(personalData);
2035:            } catch (error) {
```

**DESPUÉS:**
```javascript
2031:                await personalApi.delete(id);
2032:                showToast('Personal eliminado correctamente.', 'success');
2033:                personalData = await personalApi.getAll();
2034:                renderPersonalTable(personalData);
2035:            } catch (error) {
```

**⚠️ NOTA**: Esta línea tiene DOS cambios: `deletePersonal()` Y `getPersonal()`

---

#### **Ocurrencia 4/7:**
```
LÍNEA: 2436
FUNCIÓN: initVehiculoModule()
CONTEXTO: Carga paralela de vehículos y personal
```

**ANTES:**
```javascript
2434:        try {
2435:            // Cargar los datos de vehículos y personal
2436:            [vehiculosData, personalData] = await Promise.all([api.getVehiculos(), api.getPersonal()]);
2437:            renderVehiculoTable(vehiculosData);
2438:        } catch (error) {
```

**DESPUÉS:**
```javascript
2434:        try {
2435:            // Cargar los datos de vehículos y personal
2436:            [vehiculosData, personalData] = await Promise.all([api.getVehiculos(), personalApi.getAll()]);
2437:            renderVehiculoTable(vehiculosData);
2438:        } catch (error) {
```

**⚠️ NOTA**: `api.getVehiculos()` se mantiene por ahora (vehiculos-api.js se integrará después)

---

#### **Ocurrencia 5/7:**
```
LÍNEA: 3080
FUNCIÓN: handleVehiculoFormSubmit()
CONTEXTO: Recarga después de crear vehículo
```

**ANTES:**
```javascript
3078:            } else {
3079:                await api.createVehiculo(data);
3080:                showToast('Vehículo creado correctamente.', 'success');
3081:                [vehiculosData, personalData] = await Promise.all([api.getVehiculos(), api.getPersonal()]);
3082:            }
```

**DESPUÉS:**
```javascript
3078:            } else {
3079:                await api.createVehiculo(data);
3080:                showToast('Vehículo creado correctamente.', 'success');
3081:                [vehiculosData, personalData] = await Promise.all([api.getVehiculos(), personalApi.getAll()]);
3082:            }
```

---

#### **Ocurrencia 6/7:**
```
LÍNEA: 3094
FUNCIÓN: deleteVehiculo()
CONTEXTO: Recarga después de eliminar vehículo
```

**ANTES:**
```javascript
3092:                await api.deleteVehiculo(id);
3093:                showToast('Vehículo eliminado correctamente.', 'success');
3094:                [vehiculosData, personalData] = await Promise.all([api.getVehiculos(), api.getPersonal()]);
3095:                renderVehiculoTable(vehiculosData);
3096:            } catch (error) {
```

**DESPUÉS:**
```javascript
3092:                await api.deleteVehiculo(id);
3093:                showToast('Vehículo eliminado correctamente.', 'success');
3094:                [vehiculosData, personalData] = await Promise.all([api.getVehiculos(), personalApi.getAll()]);
3095:                renderVehiculoTable(vehiculosData);
3096:            } catch (error) {
```

---

#### **Ocurrencia 7/7:**
```
LÍNEA: 3460
FUNCIÓN: initVisitasModule()
CONTEXTO: Cargar lista de personal para selectors (POC/Familiar)
```

**ANTES:**
```javascript
3458:        }
3459:
3460:        const personalList = await api.getPersonal();
3461:        const personalOptions = document.querySelector('#personal-options');
3462:        const personalOptionsFamiliar = document.querySelector('#personal-options-familiar');
```

**DESPUÉS:**
```javascript
3458:        }
3459:
3460:        const personalList = await personalApi.getAll();
3461:        const personalOptions = document.querySelector('#personal-options');
3462:        const personalOptionsFamiliar = document.querySelector('#personal-options-familiar');
```

---

### ✅ **4. api.createPersonal() → personalApi.create()** (1 ocurrencia)

```
LÍNEA: 2017
FUNCIÓN: handlePersonalFormSubmit()
CONTEXTO: Crear nuevo registro de personal
```

**ANTES:**
```javascript
2015:                showToast('Personal actualizado correctamente.', 'success');
2016:            } else {
2017:                await api.createPersonal(data);
2018:                showToast('Personal creado correctamente.', 'success');
2019:            }
```

**DESPUÉS:**
```javascript
2015:                showToast('Personal actualizado correctamente.', 'success');
2016:            } else {
2017:                await personalApi.create(data);
2018:                showToast('Personal creado correctamente.', 'success');
2019:            }
```

---

### ✅ **5. api.updatePersonal() → personalApi.update()** (1 ocurrencia)

```
LÍNEA: 2014
FUNCIÓN: handlePersonalFormSubmit()
CONTEXTO: Actualizar registro de personal existente
```

**ANTES:**
```javascript
2012:        try {
2013:            if (id) {
2014:                await api.updatePersonal(data);
2015:                showToast('Personal actualizado correctamente.', 'success');
2016:            } else {
```

**DESPUÉS:**
```javascript
2012:        try {
2013:            if (id) {
2014:                await personalApi.update(data);
2015:                showToast('Personal actualizado correctamente.', 'success');
2016:            } else {
```

---

### ✅ **6. api.deletePersonal() → personalApi.delete()** (1 ocurrencia)

```
LÍNEA: 2031
FUNCIÓN: deletePersonal()
CONTEXTO: Eliminar registro de personal
```

**ANTES:**
```javascript
2029:        if (confirm('¿Estás seguro de que quieres eliminar a esta persona?')) {
2030:            try {
2031:                await api.deletePersonal(id);
2032:                showToast('Personal eliminado correctamente.', 'success');
2033:                personalData = await api.getPersonal();
```

**DESPUÉS:**
```javascript
2029:        if (confirm('¿Estás seguro de que quieres eliminar a esta persona?')) {
2030:            try {
2031:                await personalApi.delete(id);
2032:                showToast('Personal eliminado correctamente.', 'success');
2033:                personalData = await personalApi.getAll();
```

**⚠️ NOTA**: Esta línea ya fue mostrada anteriormente (tiene DOS cambios)

---

## 📋 TABLA RESUMEN DE CAMBIOS

| Línea | Función | Método Anterior | Método Nuevo | Tipo |
|-------|---------|----------------|--------------|------|
| 322 | handleRutLookup() | api.findPersonalByRut() | personalApi.findByRut() | Búsqueda |
| 335 | handleRutLookup() | api.searchPersonal() | personalApi.search() | Búsqueda |
| 403 | initHorasExtraModule() | api.findPersonalByRut() | personalApi.findByRut() | Búsqueda |
| 1749 | initPersonalModule() | api.getPersonal() | personalApi.getAll() | Lectura |
| 2014 | handlePersonalFormSubmit() | api.updatePersonal() | personalApi.update() | CRUD |
| 2017 | handlePersonalFormSubmit() | api.createPersonal() | personalApi.create() | CRUD |
| 2021 | handlePersonalFormSubmit() | api.getPersonal() | personalApi.getAll() | Lectura |
| 2031 | deletePersonal() | api.deletePersonal() | personalApi.delete() | CRUD |
| 2033 | deletePersonal() | api.getPersonal() | personalApi.getAll() | Lectura |
| 2233 | handlePersonalSearch() | api.searchPersonal() | personalApi.search() | Búsqueda |
| 2436 | initVehiculoModule() | api.getPersonal() | personalApi.getAll() | Lectura |
| 3080 | handleVehiculoFormSubmit() | api.getPersonal() | personalApi.getAll() | Lectura |
| 3094 | deleteVehiculo() | api.getPersonal() | personalApi.getAll() | Lectura |
| 3460 | initVisitasModule() | api.getPersonal() | personalApi.getAll() | Lectura |
| 3872 | initEmpresasModule() | api.findPersonalByRut() | personalApi.findByRut() | Búsqueda |

---

## ⚠️ ADVERTENCIA: CAMBIO EN NOMENCLATURA

### **MÉTODOS QUE CAMBIAN DE NOMBRE:**

| api.js (antiguo) | personal-api.js (nuevo) | ¿Por qué? |
|------------------|-------------------------|-----------|
| `deletePersonal()` | `delete()` | Evita conflicto con palabra reservada `delete` en contexto del módulo |
| `getPersonal()` | `getAll()` | Estándar REST: GET /resource → getAll() |
| `findPersonalByRut()` | `findByRut()` | "Personal" está implícito en el módulo |
| `searchPersonal()` | `search()` | "Personal" está implícito en el módulo |
| `createPersonal()` | `create()` | "Personal" está implícito en el módulo |
| `updatePersonal()` | `update()` | "Personal" está implícito en el módulo |

**NOTA IMPORTANTE**: El método `delete()` en `personal-api.js` se llama `deletePersonal()` internamente, pero desde `main.js` lo llamamos como `personalApi.delete()`. Esto NO genera conflicto porque está en el contexto del objeto `personalApi`.

---

## 🎯 VALIDACIÓN FINAL

Después de aplicar los cambios, verificar:

1. ✅ **Import agregado** en línea ~7
2. ✅ **15 reemplazos** aplicados correctamente
3. ✅ **No hay errores** de sintaxis
4. ✅ **Funcionalidad intacta**:
   - Módulo Personal (CRUD completo)
   - Búsqueda por RUT (autocompletar)
   - Asociación vehículo-personal
   - Horas extra (validación personal)
   - Empresas (representante)
   - Visitas (POC/Familiar)

---

## 🚀 SIGUIENTE PASO

Una vez completada la integración de `personal-api.js`:

1. **Probar** todas las funciones del módulo Personal
2. **Integrar** `vehiculos-api.js` (similar proceso)
3. **Integrar** `visitas-api.js`
4. **Integrar** `access-logs-api.js`
5. **Eliminar** `api.js` cuando todos los módulos estén integrados

---

**¿PROCEDER CON LOS REEMPLAZOS?** 🎯
