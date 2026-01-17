# 📖 SCAD API - Guía Completa

## 🚀 Inicio Rápido

### 1. Autenticarse (Login)

```bash
curl -X POST http://localhost/acceso/api/auth-migrated.php \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "password"
  }'
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refreshToken": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "user": {
      "id": 1,
      "username": "admin",
      "role": "admin"
    }
  }
}
```

### 2. Usar el Token en Solicitudes

Incluir el `token` en el header `Authorization`:

```bash
curl -X GET http://localhost/acceso/api/personal-migrated.php \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

### 3. Refrescar Token (Antes de Expirar)

Access token expira en **1 hora**. Usar refresh token para obtener uno nuevo:

```bash
curl -X POST http://localhost/acceso/api/auth-refresh.php \
  -H "Authorization: Bearer {refreshToken}"
```

### 4. Logout

```bash
curl -X DELETE http://localhost/acceso/api/auth-migrated.php \
  -H "Authorization: Bearer {token}"
```

---

## 🔐 Autenticación JWT

### Estructura del Token

```
Header.Payload.Signature

Ejemplo decoded:
{
  "alg": "HS256",
  "typ": "JWT"
}
.
{
  "iat": 1705435200,
  "exp": 1705438800,
  "userId": 1,
  "username": "admin",
  "role": "admin",
  "type": "access"
}
.
{signature}
```

### Tokens

| Token | Duración | Propósito |
|-------|----------|-----------|
| **access_token** | 1 hora | Usar en solicitudes a API |
| **refresh_token** | 7 días | Obtener nuevo access_token |

### Auto-Refresh (Frontend)

El frontend automáticamente:
1. Detecta cuando el token expira en 5 minutos
2. Realiza refresh con `POST /auth-refresh.php`
3. Obtiene nuevo token
4. Continúa normal (sin interrupciones)

---

## 📋 Endpoints Principales

### 🔓 Sin Autenticación

- `POST /auth-migrated.php` - Login

### 🔒 Con Autenticación Requerida

#### Autenticación
- `GET /auth-migrated.php` - Verificar token
- `POST /auth-refresh.php` - Refrescar token
- `DELETE /auth-migrated.php` - Logout

#### Personal
- `GET /personal-migrated.php` - Listar personal (paginado)
- `GET /personal-migrated.php?search=Juan` - Buscar personal
- `GET /personal-migrated.php?rut=12345678-9` - Buscar por RUT
- `GET /personal-migrated.php?status=inside` - Personal dentro del recinto
- `POST /personal-migrated.php` - Crear personal
- `PUT /personal-migrated.php` - Actualizar personal
- `DELETE /personal-migrated.php?id=1` - Eliminar personal

#### Vehículos
- `GET /vehiculos-migrated.php` - Listar vehículos
- `POST /vehiculos-migrated.php` - Crear vehículo
- `PUT /vehiculos-migrated.php` - Actualizar vehículo
- `DELETE /vehiculos-migrated.php?id=1` - Eliminar vehículo
- `GET /vehiculo_historial-migrated.php?vehiculo_id=1` - Historial

#### Visitas
- `GET /visitas-migrated.php` - Listar visitas
- `POST /visitas-migrated.php` - Crear visita
- `PUT /visitas-migrated.php` - Actualizar visita
- `DELETE /visitas-migrated.php?id=1` - Eliminar visita
- `PUT /visitas-migrated.php?action=toggle_blacklist&id=1` - Marcar/desmarcar de lista negra

#### Empresas
- `GET /empresas-migrated.php` - Listar empresas
- `POST /empresas-migrated.php` - Crear empresa
- `PUT /empresas-migrated.php` - Actualizar empresa
- `DELETE /empresas-migrated.php?id=1` - Eliminar empresa

#### Dashboard
- `GET /dashboard-migrated.php` - Contadores principales
- `GET /dashboard-migrated.php?details=personal` - Detalles por categoría

#### Reportes
- `GET /reportes-migrated.php?report_type=acceso_personal&rut=12345678-9` - Reporte por personal
- `GET /reportes-migrated.php?report_type=horas_extra&fecha_inicio=2025-01-01&fecha_fin=2025-01-31` - Horas extra
- `GET /reportes-migrated.php?report_type=acceso_general` - Acceso general
- `GET /reportes-migrated.php?report_type=acceso_vehiculos` - Acceso de vehículos
- `GET /reportes-migrated.php?report_type=salida_no_autorizada` - Salidas no autorizadas

#### Logs
- `GET /log_access-migrated.php?target_type=personal` - Logs de acceso
- `POST /log_access-migrated.php` - Registrar acceso

#### Pórtico
- `POST /portico-migrated.php` - Registrar acceso pórtico

---

## 📊 Estructura de Respuestas

### ✅ Respuesta Exitosa (200)

```json
{
  "success": true,
  "data": {
    // Datos específicos del endpoint
  }
}
```

### ❌ Respuesta con Error (4xx / 5xx)

```json
{
  "success": false,
  "error": {
    "message": "Descripción del error",
    "code": 400
  }
}
```

### Códigos de Estado

| Código | Significado |
|--------|-------------|
| **200** | OK - Solicitud exitosa |
| **201** | Created - Recurso creado |
| **204** | No Content - Sin contenido (ej: logout) |
| **400** | Bad Request - Datos inválidos |
| **401** | Unauthorized - Token inválido/expirado |
| **403** | Forbidden - Permisos insuficientes |
| **404** | Not Found - Recurso no encontrado |
| **429** | Too Many Requests - Rate limited |
| **500** | Internal Server Error - Error del servidor |

---

## 🛡️ Rate Limiting

### Login
- **Límite:** 5 intentos
- **Ventana:** 5 minutos
- **Bloqueo:** 15 minutos automáticos después de exceder

### Respuesta 429
```json
{
  "success": false,
  "error": {
    "message": "Demasiados intentos. Intenta más tarde.",
    "code": 429
  }
}
```

---

## 🔍 Paginación

Endpoints que retornan listas soportan paginación:

```bash
GET /api/personal-migrated.php?page=1&perPage=50
```

Respuesta:
```json
{
  "success": true,
  "data": [
    // Array de items
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 50,
    "total": 250,
    "total_pages": 5
  }
}
```

---

## 🔍 Búsqueda y Filtrado

### Buscar Personal
```bash
GET /api/personal-migrated.php?search=Juan
GET /api/personal-migrated.php?rut=12345678-9
```

### Filtrar por Status
```bash
GET /api/personal-migrated.php?status=inside  # Personal dentro del recinto
```

---

## 📝 Ejemplos de Uso Práctico

### JavaScript (Fetch)

```javascript
// 1. Login
const loginResponse = await fetch('/acceso/api/auth-migrated.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    username: 'admin',
    password: 'password'
  })
});

const { data } = await loginResponse.json();
const token = data.token;

// 2. Usar token en solicitudes
const getResponse = await fetch('/acceso/api/personal-migrated.php', {
  headers: { 'Authorization': `Bearer ${token}` }
});

const personal = await getResponse.json();
```

### Python (Requests)

```python
import requests

# 1. Login
response = requests.post(
    'http://localhost/acceso/api/auth-migrated.php',
    json={'username': 'admin', 'password': 'password'}
)
token = response.json()['data']['token']

# 2. Usar token
headers = {'Authorization': f'Bearer {token}'}
personal = requests.get(
    'http://localhost/acceso/api/personal-migrated.php',
    headers=headers
).json()
```

### cURL

```bash
#!/bin/bash

# 1. Login
TOKEN=$(curl -s -X POST http://localhost/acceso/api/auth-migrated.php \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}' \
  | jq -r '.data.token')

# 2. Usar token
curl -X GET http://localhost/acceso/api/personal-migrated.php \
  -H "Authorization: Bearer $TOKEN"
```

---

## ⚠️ Errores Comunes

### Error: "Demasiados intentos. Intenta más tarde."

**Causa:** Más de 5 intentos fallidos en 5 minutos

**Solución:** Esperar 15 minutos o contactar al administrador

### Error: "Token requerido"

**Causa:** No incluiste header Authorization

**Solución:**
```bash
curl -H "Authorization: Bearer {tu_token}" ...
```

### Error: "Token expirado"

**Causa:** Access token vence cada 1 hora

**Solución:** Usar refresh token para obtener uno nuevo
```bash
curl -X POST /acceso/api/auth-refresh.php \
  -H "Authorization: Bearer {refreshToken}"
```

### Error: "Rol no autorizado"

**Causa:** Tu usuario no tiene permiso para esa operación

**Solución:** Contactar al administrador para elevar permisos

---

## 🔐 Seguridad

### ✅ Lo que Hacemos

- ✅ JWT tokens firmados con HS256
- ✅ Passwords hasheadas con bcrypt
- ✅ Rate limiting en endpoints críticos
- ✅ Audit logging de todas las operaciones
- ✅ CORS headers configurables
- ✅ CSP (Content Security Policy)
- ✅ HSTS (HTTP Strict Transport Security)

### ❌ Lo que NO Hacer

- ❌ Exponer el JWT_SECRET en cliente
- ❌ Almacenar token en cookies sin HttpOnly
- ❌ Compartir tokens entre usuarios
- ❌ Usar HTTP en producción (usar HTTPS)
- ❌ Guardar tokens en localStorage sin cuidado

---

## 🚀 Hosting en Producción

### Variables Críticas en .env

```bash
# CAMBIAR ESTOS EN PRODUCCIÓN:
JWT_SECRET=generar-valor-aleatorio-fuerte-32-caracteres

PERSONAL_DB_PASSWORD=contraseña-fuerte
ACCESO_DB_PASSWORD=contraseña-fuerte

# Configurar para HTTPS:
APP_URL=https://yourdomain.com/acceso
HSTS_MAX_AGE=31536000

# Configurar CORS para tu dominio:
CORS_ALLOWED_ORIGINS=https://yourdomain.com
```

### Generar JWT_SECRET Seguro

```bash
# Linux/Mac
openssl rand -base64 32

# Windows PowerShell
[Convert]::ToBase64String([System.Security.Cryptography.RandomNumberGenerator]::GetBytes(32))
```

---

## 📞 Soporte

- 📖 Documentación interactiva: `/acceso/docs/api-docs.html`
- 📚 Tests: Ver `TESTING.md`
- 🐛 Issues: GitHub repository
- 💬 Código: Bien documentado en `api/` y `js/`

---

**Última actualización:** 2025-01-17
**Versión API:** 2.0.0
**Estado:** ✅ Production Ready
