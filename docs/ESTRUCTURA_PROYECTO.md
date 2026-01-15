# Estructura del Proyecto SCAD (Sistema de Control de Acceso Digital)

## 📁 Estructura de Carpetas

```
acceso/
│
├── 📄 index.html                      # Página principal de la aplicación
├── 📄 login.html                      # Página de login
│
├── api/                               # APIs PHP (Backend)
│   ├── database/
│   │   ├── db_acceso.php             # Conexión a BD de acceso
│   │   └── db_personal.php           # Conexión a BD de personal
│   │
│   ├── auth.php                       # Autenticación
│   ├── portico.php                    # Registro de acceso por pórtico
│   ├── log_access.php                 # Logs de acceso
│   ├── log_clarified_access.php       # Logs de acceso aclarado
│   ├── dashboard.php                  # Datos del dashboard
│   ├── personal.php                   # Gestión de personal
│   ├── vehiculos.php                  # Gestión de vehículos
│   ├── visitas.php                    # Gestión de visitas
│   ├── comision.php                   # Gestión de comisiones
│   ├── empresas.php                   # Gestión de empresas
│   ├── empresa_empleados.php          # Gestión de empleados de empresa
│   ├── horas_extra.php                # Gestión de horas extra
│   ├── reportes.php                   # Reportes
│   └── users.php                      # Gestión de usuarios
│
├── assets/                            # Recursos estáticos
│   ├── sounds/
│   │   ├── scan-success.mp3           # Sonido de éxito
│   │   └── scan-error.mp3             # Sonido de error
│   └── images/
│
├── css/
│   └── style.css                      # Estilos principales
│
├── js/                                # JavaScript Frontend
│   ├── 📄 main-refactored.js          # Punto de entrada principal (USAR ESTE)
│   ├── login.js                       # Lógica de login
│   │
│   ├── api/                           # Clientes API
│   │   ├── api-client.js              # Cliente HTTP base
│   │   ├── access-logs-api.js         # API de logs de acceso
│   │   ├── dashboard-api.js           # API de dashboard
│   │   ├── personal-api.js            # API de personal
│   │   ├── vehiculos-api.js           # API de vehículos
│   │   ├── visitas-api.js             # API de visitas
│   │   ├── comision-api.js            # API de comisiones
│   │   ├── empresas-api.js            # API de empresas
│   │   ├── horas-extra-api.js         # API de horas extra
│   │   └── portico-api.js             # API de pórtico
│   │
│   ├── modules/                       # Módulos funcionales
│   │   ├── dashboard.js               # Módulo de dashboard/inicio
│   │   ├── control.js                 # Módulo de control de pórtico
│   │   ├── personal.js                # Módulo de gestión de personal
│   │   ├── vehiculos.js               # Módulo de gestión de vehículos
│   │   ├── visitas.js                 # Módulo de gestión de visitas
│   │   ├── empresas.js                # Módulo de gestión de empresas
│   │   ├── horas-extra.js             # Módulo de horas extra
│   │   │
│   │   └── ui/                        # Componentes de UI
│   │       ├── ui.js                  # Templates HTML de módulos
│   │       ├── notifications.js       # Sistema de notificaciones
│   │       ├── loading.js             # Spinner de carga
│   │       └── modal-helpers.js       # Utilidades de modales
│   │
│   ├── utils/                         # Utilidades
│   │   └── api.js                     # Funciones de utilidad API
│   │
│   └── lib/                           # Librerías locales
│       ├── create-excel-template.js   # Generador de plantillas Excel
│       └── guardia-servicio.js        # Lógica de guardias de servicio
│
├── sql/                               # Scripts de base de datos
│
├── templates/                         # Plantillas (Excel, etc)
│
└── tests/                             # Pruebas (si aplica)
```

## 🎯 Puntos de Entrada

### Frontend
- **`index.html`** → Carga `main-refactored.js`
- **`login.html`** → Carga `login.js`

### Backend
- Todas las APIs están en `/api/*.php`
- Las conexiones a BD están en `/api/database/`

## 📦 Dependencias y Relaciones

### main-refactored.js importa:
```javascript
✓ ./modules/ (dashboard, control, personal, etc)
✓ ./api/ (todos los clientes API)
✓ ./ui/ (notifications, loading, modal-helpers)
✓ ./utils/ (validators)
```

### Módulos importan:
```javascript
✓ ./ui/notifications.js (showToast)
✓ ../api/*-api.js (para obtener datos)
```

### APIs PHP importan:
```php
✓ database/db_acceso.php (conexión acceso)
✓ database/db_personal.php (conexión personal)
```

## 🔄 Flujo de Aplicación

```
1. index.html carga
2. main-refactored.js se ejecuta
3. Verifica sesión (sessionStorage)
4. Si no hay sesión → redirige a login.html
5. Si hay sesión → carga módulos dinámicamente
6. Cada módulo inicializa con initXxxModule(contentElement)
7. Los módulos usan APIs para obtener datos
8. Las APIs usan api-client.js para hacer requests a PHP
9. PHP conecta a BD usando database/db_*.php
```

## 📝 Estructura de Nombres

### Módulos
- `init[NombreModulo]Module(contentElement)` - Inicializa módulo
- Exportan funciones públicas como `stop[Nombre]AutoRefresh()`

### APIs
- `class [NombreCapital]Api extends ApiClient`
- Métodos: `async getByType()`, `async logAccess()`, etc.
- Exportan instancia singleton: `export default new [Nombre]Api();`

### UI Components
- En `modules/ui/*.js`
- Exportan: `initNotifications()`, `showToast()`, etc.

## 🚀 Cómo Agregar un Nuevo Módulo

1. Crear `/js/modules/nuevo-modulo.js`
2. Crear `/js/api/nuevo-api.js`
3. Agregar template en `/js/ui/ui.js`
4. Importar en `/js/main-refactored.js`
5. Agregar ruta de navegación

## ✅ Verificación de Integridad

Después de cambios, verificar:
- [ ] Todas las rutas de importación son correctas
- [ ] Las conexiones BD están en database/
- [ ] Los módulos exportan funciones correctas
- [ ] Las APIs heredan de ApiClient
- [ ] No hay archivos huérfanos o duplicados

