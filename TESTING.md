# 🧪 Guía de Testing - SCAD

## Overview

Este proyecto tiene una suite completa de tests automatizados para backend (PHP) y frontend (JavaScript):

- **Backend**: PHPUnit (231+ tests)
- **Frontend**: Jest (267 tests)
- **Total**: 500+ tests automatizados

---

## 📋 Requisitos

### Backend
- PHP 8.1+
- Composer
- MySQL 8.0+ (para tests de integración)

### Frontend
- Node.js 18+
- npm 9+

---

## 🚀 Ejecutar Tests Localmente

### Backend - PHPUnit

```bash
# Instalar dependencias
composer install

# Ejecutar todos los tests
composer test
# O usar directamente:
./vendor/bin/phpunit

# Con cobertura HTML
./vendor/bin/phpunit --coverage-html tests/coverage/html

# Tests específicos
./vendor/bin/phpunit tests/backend/AuthMigrationTest.php
./vendor/bin/phpunit tests/integration/AuthApiTest.php
```

### Frontend - Jest

```bash
# Instalar dependencias
npm install

# Ejecutar todos los tests
npm test

# Modo vigilancia (re-ejecuta en cambios)
npm run test:watch

# Con cobertura
npm run test:coverage

# Tests específicos
npm test -- validators.test.js
npm test -- api-client.test.js
```

---

## 📊 Estructura de Tests

### Backend
```
tests/
├── backend/
│   ├── AuthMigrationTest.php
│   ├── PersonalMigrationTest.php
│   ├── EmpresasMigrationTest.php
│   └── ... (19 archivos)
├── integration/
│   ├── AuthApiTest.php
│   ├── PersonalApiTest.php
│   └── ... (10+ archivos)
├── bootstrap.php
└── phpunit.xml
```

### Frontend
```
tests/
├── unit/
│   ├── validators.test.js (44 tests)
│   ├── formatters.test.js (57 tests)
│   ├── date-utils.test.js (69 tests)
│   └── api-client.test.js (44 tests)
├── integration/
│   ├── personal-module.test.js (36 tests)
│   ├── vehiculos-module.test.js (32 tests)
│   └── dashboard-module.test.js (35 tests)
├── setup.js
└── coverage/
```

---

## 📈 Cobertura de Código

### Métricas Actuales

**Backend (PHPUnit)**
- Tests: 231+
- Archivos: 20 APIs migradas
- Cobertura: 80%+

**Frontend (Jest)**

| Módulo | Tests | Cobertura |
|--------|-------|-----------|
| validators.js | 44 | 95% |
| formatters.js | 57 | 88.88% |
| date-utils.js | 69 | 98.9% |
| api-client.js | 44 | 93.93% |
| **Integration** | 103 | Mock-based |

**Total Frontend**: 267 tests, cobertura excelente

### Ver Reportes

```bash
# Backend
open tests/coverage/html/index.html

# Frontend
open tests/coverage/index.html
```

---

## 🔄 CI/CD - GitHub Actions

### Workflow Automático

El archivo `.github/workflows/tests.yml` configura:

- ✅ Ejecución automática en cada push
- ✅ Ejecución en PRs a `main`
- ✅ Parallelización de tests (backend + frontend simultáneamente)
- ✅ MySQL para tests de integración PHP
- ✅ Reportes de cobertura automáticos
- ✅ Notificaciones de estado

### Estados de Rama

| Rama | CI/CD | Descripción |
|------|-------|------------|
| `main` | ✅ | Rama de producción - requiere tests verdes |
| `refactor/phase1` | ✅ | Rama de refactoring - tests automáticos |
| `develop` | ✅ | Rama de desarrollo - tests automáticos |

### Ver Estado en GitHub

```
https://github.com/tu-usuario/tu-repo/actions
```

Cada push mostrará:
- ✅ Backend Tests Status
- ✅ Frontend Tests Status
- 📊 Coverage Reports (si está configurado Codecov)

---

## 🎯 Mejores Prácticas

### Al hacer commit

```bash
# Antes de hacer push
npm test              # Jest tests
composer test         # PHPUnit tests

# Si todo pasa, entonces push
git push origin refactor/phase1
```

### Al crear PR

1. Asegúrate que tu rama es de `refactor/phase1` o `develop`
2. Haz push - GitHub Actions ejecutará automáticamente
3. Verifica que todos los checks pasen ✅
4. Luego haz PR a `main`

### Agregar nuevos tests

```bash
# Frontend
# 1. Crea tests/unit/nueva-feature.test.js
# 2. Escribe tests siguiendo el patrón existente
# 3. Ejecuta npm test

# Backend
# 1. Crea tests/backend/NuevaFeatureTest.php
# 2. Extiende TestCase o crea test manual
# 3. Ejecuta composer test
```

---

## 🐛 Debugging de Tests

### Frontend

```bash
# Ejecutar test específico
npm test -- nombre-archivo.test.js

# Debug en Node
node --inspect-brk node_modules/.bin/jest

# Watch mode
npm run test:watch
```

### Backend

```bash
# Test específico
./vendor/bin/phpunit tests/unit/ApiClientTest.php --filter testName

# Con salida verbose
./vendor/bin/phpunit --verbose

# Detener en primer error
./vendor/bin/phpunit --stop-on-failure
```

---

## 📝 Configuración de Tests

### PHPUnit - `phpunit.xml`

```xml
<phpunit bootstrap="tests/bootstrap.php"
         colors="true"
         testdox="true">
    <testsuites>
        <testsuite name="API Tests">
            <directory>tests/backend</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### Jest - `package.json`

```json
{
  "jest": {
    "testEnvironment": "jsdom",
    "setupFilesAfterEnv": ["<rootDir>/tests/setup.js"],
    "collectCoverage": true,
    "coverageDirectory": "tests/coverage"
  }
}
```

---

## 🚨 Solución de Problemas

### "Tests fallan en CI pero pasan localmente"

**Posibles causas:**
- Diferencia de timezone (usar `new Date(yyyy, mm, dd)`)
- Variables de entorno no configuradas
- Dependencias desactualizadas

**Solución:**
```bash
rm -rf node_modules vendor
npm install
composer install
npm test
```

### "MySQL no está disponible en CI"

El workflow espera 30 segundos a que MySQL esté listo. Si falla:
- Verifica las credenciales en `.github/workflows/tests.yml`
- Aumenta el timeout de espera

### "Cobertura baja en CI"

Los tests en CI pueden tener cobertura diferente si:
- Hay código condicional por SO (Windows vs Linux)
- Tests usan rutas relativas

**Solución:**
- Usar rutas absolutas
- Tests deben ser independientes del SO

---

## 📊 Métricas y Monitoreo

### Integración con Codecov (Opcional)

Si configuraste Codecov en GitHub:

```bash
# Los reportes se envían automáticamente desde CI
# Ver: https://codecov.io/gh/tu-usuario/tu-repo
```

### Badges para README

```markdown
![Tests](https://github.com/usuario/repo/workflows/Tests/badge.svg)
[![Coverage](https://codecov.io/gh/usuario/repo/badge.svg)](https://codecov.io/gh/usuario/repo)
```

---

## 🎓 Recursos

- [Jest Documentation](https://jestjs.io/docs/getting-started)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [GitHub Actions Docs](https://docs.github.com/es/actions)

---

## 📞 Contacto

Para preguntas sobre testing, revisa:
1. Este archivo TESTING.md
2. Los comentarios en los archivos de test
3. La documentación de Jest/PHPUnit
