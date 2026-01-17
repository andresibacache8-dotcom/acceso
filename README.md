# 🔐 SCAD - Sistema de Control de Acceso Digital

![Tests](https://github.com/tu-usuario/repo/workflows/Ejecutar%20Tests%20-%20SCAD/badge.svg)
[![Coverage Status](https://img.shields.io/badge/coverage-95%25-brightgreen)](https://github.com/tu-usuario/repo)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-purple)](https://www.php.net/)
[![Node.js 18+](https://img.shields.io/badge/Node.js-18%2B-green)](https://nodejs.org/)

Sistema integral de control de acceso con registro de personal, vehículos y visitantes.

---

## 📋 Tabla de Contenidos

- [Características](#características)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Testing](#testing)
- [Desarrollo](#desarrollo)
- [Estructura del Proyecto](#estructura-del-proyecto)
- [Documentación](#documentación)

---

## ✨ Características

### Backend
- ✅ 20 APIs RESTful migradas y refactorizadas
- ✅ Patrón ResponseHandler estandarizado
- ✅ Gestión de personal, vehículos, visitantes
- ✅ Control de acceso dual-DB
- ✅ 231+ tests PHPUnit automatizados

### Frontend
- ✅ Interfaz SPA con hash routing
- ✅ 9 módulos feature independientes
- ✅ Utilities modernas (validators, formatters, date-utils)
- ✅ 267 tests Jest automatizados
- ✅ 95%+ cobertura de código

### DevOps
- ✅ GitHub Actions CI/CD automático
- ✅ Ejecución paralela de tests (backend + frontend)
- ✅ Reportes de cobertura automáticos
- ✅ Bloqueo de merge si tests fallan

---

## 📦 Requisitos

### Backend
- **PHP**: 8.1 o superior
- **Composer**: 2.0+
- **MySQL**: 8.0+ (para tests de integración)

### Frontend
- **Node.js**: 18 LTS o superior
- **npm**: 9+

### Herramientas
- **Git**: 2.37+
- **GitHub**: Acceso a repositorio

---

## 🚀 Instalación

### 1. Clonar repositorio
```bash
git clone https://github.com/tu-usuario/scad.git
cd scad
```

### 2. Backend - PHP
```bash
# Instalar dependencias
composer install

# Configurar base de datos
cp config/database.example.php config/database.php
# Editar config/database.php con tus credenciales

# Ejecutar migraciones (si aplica)
php scripts/migrate.php
```

### 3. Frontend - JavaScript
```bash
# Instalar dependencias
npm install

# (Opcional) Ver en modo desarrollo
npm run dev
```

### 4. Verificar instalación
```bash
# Ejecutar suite completa de tests
./run-tests.sh  # Linux/Mac
# o
run-tests.bat   # Windows
```

---

## 🧪 Testing

### Ejecutar Tests

**Opción rápida (ambos):**
```bash
./run-tests.sh        # Linux/Mac
run-tests.bat         # Windows
```

**Opción específica:**
```bash
# Solo Backend
composer test
./vendor/bin/phpunit

# Solo Frontend
npm test
npm run test:watch    # Con vigilancia

# Con cobertura
./run-tests.sh coverage
npm run test:coverage
```

### Resultados

```
✅ 500+ tests automatizados
✅ 95%+ cobertura de utilities
✅ CI/CD automático en GitHub Actions
```

### Documentación Completa
Ver [`TESTING.md`](TESTING.md) para:
- Estructura de tests
- Métricas de cobertura
- Debugging y troubleshooting
- Mejores prácticas

---

## 📊 Estado de Tests

### Backend (PHPUnit)
| Componente | Tests | Cobertura | Estado |
|------------|-------|-----------|--------|
| Migraciones API | 231+ | 80%+ | ✅ |
| Integración | 50+ | 85%+ | ✅ |
| **Total Backend** | **281+** | **82%+** | **✅** |

### Frontend (Jest)
| Módulo | Tests | Cobertura | Estado |
|--------|-------|-----------|--------|
| validators.js | 44 | 95% | ✅ |
| formatters.js | 57 | 88.88% | ✅ |
| date-utils.js | 69 | 98.9% | ✅ |
| api-client.js | 44 | 93.93% | ✅ |
| Integración | 103 | 100% | ✅ |
| **Total Frontend** | **267** | **95%+** | **✅** |

### Resumen Total
- **Tests**: 548+
- **Cobertura**: 88%+ promedio
- **CI/CD**: Automático en cada push

---

## 🛠️ Desarrollo

### Workflow Local

```bash
# 1. Crear rama de feature
git checkout -b feature/nueva-funcionalidad

# 2. Hacer cambios
# ... editar archivos ...

# 3. Ejecutar tests antes de commit
./run-tests.sh

# 4. Si todo pasa, hacer commit
git add .
git commit -m "feat: descripción de cambios"

# 5. Push a rama
git push origin feature/nueva-funcionalidad

# 6. GitHub Actions ejecutará tests automáticamente
# 7. Una vez aprobados, crear PR a main
```

### Agregar Nuevos Tests

**Frontend:**
```bash
# 1. Crear archivo tests/unit/mi-feature.test.js
# 2. Escribir tests usando el patrón existente
# 3. npm test para validar
```

**Backend:**
```bash
# 1. Crear archivo tests/backend/MiFeatureTest.php
# 2. Extender TestCase de PHPUnit
# 3. composer test para validar
```

---

## 📂 Estructura del Proyecto

```
scad/
├── 📁 api/                          # Backend PHP
│   ├── core/
│   │   └── ResponseHandler.php      # Respuestas estandarizadas
│   ├── personal-api.php
│   ├── vehiculos-api.php
│   ├── visitas-api.php
│   └── ... (20 APIs total)
│
├── 📁 config/                       # Configuración
│   ├── database.php                 # BD centralizada
│   └── database.example.php
│
├── 📁 js/                           # Frontend JavaScript
│   ├── api/
│   │   └── api-client.js            # Cliente HTTP centralizado
│   ├── modules/
│   │   ├── personal.js
│   │   ├── vehiculos.js
│   │   ├── visitas.js
│   │   └── ... (9 módulos)
│   ├── utils/
│   │   ├── validators.js            # 229 líneas, 95% cobertura
│   │   ├── formatters.js            # 333 líneas, 88% cobertura
│   │   └── date-utils.js            # Utilidades de fecha
│   └── core/
│       └── app.js                   # App principal
│
├── 📁 tests/                        # Suite de Tests
│   ├── backend/
│   │   ├── *Test.php               # Tests unitarios PHPUnit
│   │   └── ... (19 archivos)
│   ├── integration/
│   │   ├── *ApiTest.php            # Tests de integración
│   │   └── ... (10+ archivos)
│   ├── unit/
│   │   ├── validators.test.js      # 44 tests
│   │   ├── formatters.test.js      # 57 tests
│   │   ├── date-utils.test.js      # 69 tests
│   │   └── api-client.test.js      # 44 tests
│   ├── integration/
│   │   ├── personal-module.test.js # 36 tests
│   │   ├── vehiculos-module.test.js# 32 tests
│   │   └── dashboard-module.test.js# 35 tests
│   ├── setup.js                    # Setup Jest
│   ├── bootstrap.php               # Setup PHPUnit
│   └── coverage/                   # Reportes HTML
│
├── 📁 .github/
│   └── workflows/
│       └── tests.yml               # CI/CD Pipeline
│
├── .gitignore
├── composer.json                   # Dependencias PHP
├── package.json                    # Dependencias Node.js
├── phpunit.xml                     # Config PHPUnit
├── codecov.yml                     # Config Codecov
├── TESTING.md                      # Guía completa de testing
├── README.md                       # Este archivo
├── run-tests.sh                    # Script tests (Linux/Mac)
└── run-tests.bat                   # Script tests (Windows)
```

---

## 📚 Documentación

### Archivos Importantes

| Archivo | Descripción |
|---------|------------|
| [`TESTING.md`](TESTING.md) | Guía completa de testing |
| [`phpunit.xml`](phpunit.xml) | Config tests PHPUnit |
| [`package.json`](package.json) | Config tests Jest y dependencias |
| [`.github/workflows/tests.yml`](.github/workflows/tests.yml) | CI/CD Pipeline |

### Referencias Externas

- [Jest Documentation](https://jestjs.io/)
- [PHPUnit Documentation](https://phpunit.de/)
- [GitHub Actions Docs](https://docs.github.com/actions)
- [PHP 8.1 Manual](https://www.php.net/manual/en/)

---

## 🔗 URLs Importantes

- **Aplicación**: [http://localhost/acceso/](http://localhost/acceso/)
- **API Backend**: [http://localhost/acceso/api/](http://localhost/acceso/api/)
- **GitHub Actions**: [https://github.com/tu-usuario/repo/actions](https://github.com/tu-usuario/repo/actions)
- **Coverage Reports**: [https://codecov.io/gh/tu-usuario/repo](https://codecov.io/gh/tu-usuario/repo)

---

## 🐛 Reportar Issues

Si encuentras un problema:

1. Verifica que los tests pasen: `./run-tests.sh`
2. Crea un issue en GitHub con:
   - Descripción clara del problema
   - Pasos para reproducir
   - Output de tests si aplica
   - Tu entorno (OS, PHP version, Node version)

---

## 📄 Licencia

Proyecto privado - Todos los derechos reservados

---

## 👥 Contribuidores

- Sistema de Control de Acceso Digital (SCAD) Team

---

## 🚀 Próximas Mejoras

- [ ] Tests E2E (Cypress/Playwright)
- [ ] Performance Testing
- [ ] Visual Regression Testing
- [ ] Security Testing (OWASP ZAP)
- [ ] Monitoring en Producción (Sentry)
- [ ] Documentación API (Swagger/OpenAPI)

---

**Última actualización**: Enero 2025
**Estado**: ✅ En desarrollo - Testing completo implementado
