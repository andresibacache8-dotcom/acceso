# 🔐 SCAD - Sistema de Control de Acceso Digital

Sistema integral para la gestión y control de acceso a una base militar.

## 📋 Características

- ✅ Control de acceso por pórtico con QR/RUT
- ✅ Gestión de personal, vehículos y visitas
- ✅ Dashboard en tiempo real con contadores
- ✅ Registro automático de entrada/salida
- ✅ Gestión de horas extra y comisiones
- ✅ Reportes y análisis de acceso
- ✅ Sistema de clarificación para accesos fuera de horario
- ✅ Importación masiva de datos

## 🚀 Inicio Rápido

### Requisitos
- PHP 7.4+
- MySQL 5.7+
- Navegador moderno (Chrome, Firefox, Edge)

### Instalación
1. Copiar el proyecto a `/xampp/htdocs/Desarrollo/acceso`
2. Crear las bases de datos según scripts en `/sql`
3. Configurar conexiones BD en `/api/database/`
4. Acceder a `http://localhost/Desarrollo/acceso/`

## 📁 Estructura del Proyecto

Ver **ESTRUCTURA_PROYECTO.md** en la carpeta `/docs` para documentación completa.

```
acceso/
├── index.html              # Punto de entrada
├── api/                    # Backend PHP
│   └── database/          # Conexiones a BD
├── js/                    # Frontend JavaScript
│   ├── modules/           # Módulos funcionales
│   ├── api/              # Clientes API
│   └── ui/               # Componentes UI
├── css/                   # Estilos
├── assets/                # Recursos (sonidos, imágenes)
├── docs/                  # Documentación
└── sql/                   # Scripts de BD
```

## 🔧 Desarrolladores

### Puntos de Entrada
- **Frontend**: `js/main-refactored.js`
- **Backend**: `api/*.php`

### Para Agregar un Nuevo Módulo
1. Crear `/js/modules/nuevo-modulo.js`
2. Crear `/js/api/nuevo-api.js` (si necesita datos)
3. Agregar template en `/js/ui/ui.js`
4. Importar en `/js/main-refactored.js`

### Para Agregar una Nueva API PHP
1. Crear `/api/nueva-api.php`
2. Importar conexiones: `require_once 'database/db_*.php';`
3. Devolver JSON: `json_encode($response);`

## 📚 Documentación

- `ESTRUCTURA_PROYECTO.md` - Guía completa de la estructura
- `CAMBIOS_*.md` - Registros de cambios realizados
- `FIX_*.md` - Soluciones a problemas específicos
- `PLAN_*.md` - Planes de implementación

## 🐛 Reportar Problemas

Ver logs en navegador (F12) y reportar al equipo de desarrollo.

## 📝 Licencia

Sistema interno de la institución. Uso restringido.

---

**Última actualización**: 2025-10-26  
**Versión**: 2.0 - Modular
