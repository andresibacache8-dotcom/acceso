# Plan de Implementación Completo - Mejora del Sistema SCAD
## Sistema de Control de Acceso para Base Militar

**Fecha de Creación:** 29 de Octubre de 2025
**Tiempo Estimado:** 6 semanas
**Complejidad:** Media-Alta
**Prioridad:** Para implementación futura

---

## **Contexto del Problema**

El sistema actual tiene lógica de alertas con falsos positivos:
- ❌ Todo personal trabajando después de las 16:30 genera alerta roja "No Autorizado"
- ❌ Guardias de 24 horas (que entran día 1 y salen día 2) aparecen como no autorizados
- ❌ Personal de servicio trabajando en horarios especiales genera alertas incorrectas
- ❌ Horarios hardcodeados en código PHP (7:30-16:30) en lugar de configurables
- ❌ No hay diferenciación entre tipos de personal (regular, guardia, servicio, residente)
- ❌ Módulo de Guardia/Servicio existe pero está desconectado del sistema de alertas

**Escala del Sistema:**
- ~1500 personas entrando diariamente
- 50-200 personal de guardia/servicio
- 2 puntos de control: Pórtico (vehiculos/visitas) y Control de Unidades (personal)
- Horario normal: Lunes-Viernes 7:30-16:30
- Guardias: Turnos de 24 horas (entrada día 1, salida día 2)

---

## **Fase 1: Migración de Base de Datos (Semana 1-2)**

### 1.1 Modificar tabla `personal` - Agregar campos de tipo y horario

```sql
-- Agregar nuevos campos a la tabla personal
ALTER TABLE personal_db.personal
ADD COLUMN tipo_personal ENUM('regular', 'guardia_24h', 'servicio', 'residente')
    DEFAULT 'regular'
    COMMENT 'Tipo de personal para lógica de alertas',
ADD COLUMN horario_trabajo_id INT DEFAULT NULL
    COMMENT 'FK a horarios_trabajo',
ADD COLUMN turno_actual INT DEFAULT NULL
    COMMENT 'FK a asignacion_turnos (turno activo)',
ADD INDEX idx_tipo_personal (tipo_personal),
ADD INDEX idx_horario_trabajo (horario_trabajo_id);

-- Migrar datos existentes
UPDATE personal_db.personal
SET tipo_personal = 'residente'
WHERE es_residente = 1;

UPDATE personal_db.personal
SET tipo_personal = 'regular'
WHERE es_residente = 0 OR es_residente IS NULL;

-- Comentar: Después de migración, revisar manualmente quiénes son guardias/servicio
```

### 1.2 Crear tabla `horarios_trabajo` - Horarios configurables

```sql
CREATE TABLE acceso_pro_db.horarios_trabajo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL COMMENT 'Ej: Horario Normal, Guardia 24h',
    descripcion TEXT COMMENT 'Descripción del horario',
    hora_entrada TIME COMMENT 'Hora de entrada esperada',
    hora_salida TIME COMMENT 'Hora de salida esperada',
    dias_semana SET('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo')
        COMMENT 'Días que aplica este horario',
    duracion_horas INT COMMENT 'Para turnos de 24h o más, NULL para horarios diarios',
    es_turno_24h BOOLEAN DEFAULT FALSE COMMENT 'Indica si es turno de 24 horas',
    tolerancia_minutos INT DEFAULT 30 COMMENT 'Minutos de tolerancia antes de generar alerta',
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Horarios de trabajo configurables';

-- Insertar horarios predefinidos
INSERT INTO acceso_pro_db.horarios_trabajo
(nombre, descripcion, hora_entrada, hora_salida, dias_semana, duracion_horas, es_turno_24h, tolerancia_minutos)
VALUES
('Horario Normal', 'Personal administrativo Lunes a Viernes', '07:30:00', '16:30:00',
    'Lunes,Martes,Miércoles,Jueves,Viernes', NULL, FALSE, 30),
('Guardia 24h', 'Turno de guardia de 24 horas', '07:30:00', NULL,
    'Lunes,Martes,Miércoles,Jueves,Viernes,Sábado,Domingo', 24, TRUE, 60),
('Turno Diurno 12h', 'Turno diurno 08:00 a 20:00', '08:00:00', '20:00:00',
    'Lunes,Martes,Miércoles,Jueves,Viernes,Sábado,Domingo', NULL, FALSE, 30),
('Turno Nocturno 12h', 'Turno nocturno 20:00 a 08:00', '20:00:00', '08:00:00',
    'Lunes,Martes,Miércoles,Jueves,Viernes,Sábado,Domingo', NULL, FALSE, 30),
('Residente Sin Restricción', 'Personal residente sin horario fijo', NULL, NULL,
    'Lunes,Martes,Miércoles,Jueves,Viernes,Sábado,Domingo', NULL, FALSE, NULL);

-- Asignar horarios por defecto
UPDATE personal_db.personal
SET horario_trabajo_id = 1
WHERE tipo_personal = 'regular';

UPDATE personal_db.personal
SET horario_trabajo_id = 2
WHERE tipo_personal = 'guardia_24h';

UPDATE personal_db.personal
SET horario_trabajo_id = 5
WHERE tipo_personal = 'residente';
```

### 1.3 Crear tabla `asignacion_turnos` - Gestión de turnos programados

```sql
CREATE TABLE acceso_pro_db.asignacion_turnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personal_rut VARCHAR(20) NOT NULL COMMENT 'RUT del personal asignado',
    personal_nombre VARCHAR(255) NOT NULL COMMENT 'Nombre completo del personal',
    horario_id INT NOT NULL COMMENT 'FK a horarios_trabajo',
    fecha_inicio DATETIME NOT NULL COMMENT 'Inicio del turno',
    fecha_fin DATETIME NOT NULL COMMENT 'Fin del turno',
    tipo_turno ENUM('guardia', 'servicio', 'especial') DEFAULT 'guardia',
    motivo TEXT COMMENT 'Motivo o descripción del turno',
    estado ENUM('programado', 'activo', 'finalizado', 'cancelado') DEFAULT 'programado',
    autorizado_por_rut VARCHAR(20) COMMENT 'Quien autorizó el turno',
    autorizado_por_nombre VARCHAR(255) COMMENT 'Nombre de quien autorizó',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (horario_id) REFERENCES acceso_pro_db.horarios_trabajo(id),
    INDEX idx_personal_rut (personal_rut),
    INDEX idx_fecha_inicio (fecha_inicio),
    INDEX idx_fecha_fin (fecha_fin),
    INDEX idx_estado (estado),
    INDEX idx_fechas_estado (fecha_inicio, fecha_fin, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Asignación de turnos programados para guardias y servicios';

-- Trigger para actualizar estado automáticamente
DELIMITER $$
CREATE TRIGGER actualizar_estado_turno_before_update
BEFORE UPDATE ON acceso_pro_db.asignacion_turnos
FOR EACH ROW
BEGIN
    IF NEW.estado = 'programado' AND NOW() >= NEW.fecha_inicio AND NOW() < NEW.fecha_fin THEN
        SET NEW.estado = 'activo';
    ELSEIF NEW.estado IN ('programado', 'activo') AND NOW() >= NEW.fecha_fin THEN
        SET NEW.estado = 'finalizado';
    END IF;
END$$
DELIMITER ;
```

### 1.4 Optimización de índices para 1500+ registros

```sql
-- Optimizar access_logs para búsquedas frecuentes
ALTER TABLE acceso_pro_db.access_logs
ADD INDEX idx_target_status_action (target_id, log_status, action, log_time),
ADD INDEX idx_log_time_status (log_time, log_status),
ADD INDEX idx_punto_acceso (punto_acceso);

-- Optimizar personal para joins frecuentes
ALTER TABLE personal_db.personal
ADD INDEX idx_rut (NrRut),
ADD INDEX idx_unidad (Unidad),
ADD INDEX idx_tipo_horario (tipo_personal, horario_trabajo_id);

-- Analizar y optimizar tablas
ANALYZE TABLE acceso_pro_db.access_logs;
ANALYZE TABLE personal_db.personal;
ANALYZE TABLE acceso_pro_db.horarios_trabajo;
ANALYZE TABLE acceso_pro_db.asignacion_turnos;
```

---

## **Fase 2: Actualización de Lógica Backend (Semana 2-4)**

### 2.1 Modificar `api/dashboard.php` - Nueva lógica de alertas

#### Alerta "No Autorizado" (Roja) - Excluir guardias en turno

```php
case 'alerta-no-autorizado':
    $sql = "SELECT DISTINCT p.id, p.Grado, p.Nombres, p.Paterno, p.Materno,
                   p.Unidad, p.movil1, p.tipo_personal,
                   a.log_time as entry_time,
                   'No Autorizado' as tipo,
                   ht.nombre as horario_nombre,
                   ht.hora_salida,
                   ht.tolerancia_minutos,
                   at.id as turno_activo_id
            FROM personal_db.personal p
            JOIN acceso_pro_db.access_logs a ON p.id = a.target_id
            LEFT JOIN acceso_pro_db.horas_extra he ON p.NrRut = he.personal_rut
                AND he.status = 'activo'
            LEFT JOIN acceso_pro_db.horarios_trabajo ht ON p.horario_trabajo_id = ht.id
            LEFT JOIN acceso_pro_db.asignacion_turnos at ON p.NrRut = at.personal_rut
                AND at.estado = 'activo'
                AND NOW() BETWEEN at.fecha_inicio AND at.fecha_fin
            WHERE a.target_type = 'personal'
            AND a.action = 'entrada'
            AND a.log_status = 'activo'
            AND a.id IN (
                SELECT MAX(id) FROM acceso_pro_db.access_logs
                WHERE target_type = 'personal' AND log_status = 'activo'
                GROUP BY target_id
            )
            -- NUEVA LÓGICA: Excluir guardias 24h y residentes
            AND p.tipo_personal NOT IN ('guardia_24h', 'residente')
            -- NUEVA LÓGICA: Excluir si tiene turno activo
            AND at.id IS NULL
            -- Mantener lógica existente
            AND (a.motivo IS NULL OR a.motivo = '' OR a.motivo = 'Trabajo')
            AND he.id IS NULL
            -- NUEVA LÓGICA: Verificar que pasó el horario + tolerancia
            AND (
                ht.hora_salida IS NULL
                OR ADDTIME(ht.hora_salida, SEC_TO_TIME(ht.tolerancia_minutos * 60)) < CURTIME()
            )
            ORDER BY a.log_time DESC";
    break;
```

#### Alerta "Atrasado" (Amarilla) - Usar horario configurable

```php
case 'alerta-atrasado':
    $sql = "SELECT DISTINCT p.id, p.Grado, p.Nombres, p.Paterno, p.Materno,
                   p.Unidad, p.movil1, p.tipo_personal,
                   a.log_time as entry_time,
                   'Atrasado' as tipo,
                   he.fecha_hora_termino,
                   ht.nombre as horario_nombre,
                   ht.hora_salida
            FROM personal_db.personal p
            JOIN acceso_pro_db.access_logs a ON p.id = a.target_id
            LEFT JOIN acceso_pro_db.horas_extra he ON p.NrRut = he.personal_rut
                AND he.status = 'activo'
            LEFT JOIN acceso_pro_db.horarios_trabajo ht ON p.horario_trabajo_id = ht.id
            WHERE a.target_type = 'personal'
            AND a.action = 'entrada'
            AND a.log_status = 'activo'
            AND a.id IN (
                SELECT MAX(id) FROM acceso_pro_db.access_logs
                WHERE target_type = 'personal' AND log_status = 'activo'
                GROUP BY target_id
            )
            -- Tiene horas_extra activa
            AND he.id IS NOT NULL
            -- Ya pasó la hora de término autorizada
            AND he.fecha_hora_termino < NOW()
            -- Excluir residentes (no tienen horario de salida)
            AND p.tipo_personal != 'residente'
            ORDER BY a.log_time DESC";
    break;
```

#### Contador "Personal Trabajando" - Incluir guardias en turno

```php
case 'personal-trabajando-por-unidad':
    $sql = "SELECT p.Unidad, COUNT(DISTINCT p.id) as cantidad
            FROM personal_db.personal p
            JOIN acceso_pro_db.access_logs a ON p.id = a.target_id
            LEFT JOIN acceso_pro_db.asignacion_turnos at ON p.NrRut = at.personal_rut
                AND at.estado = 'activo'
                AND NOW() BETWEEN at.fecha_inicio AND at.fecha_fin
            WHERE a.id IN (
                SELECT MAX(id) FROM acceso_pro_db.access_logs
                WHERE target_type = 'personal' AND log_status = 'activo'
                GROUP BY target_id
            )
            AND a.action = 'entrada'
            AND (a.punto_acceso = 'oficina' OR a.punto_acceso = 'control_unidades')
            -- NUEVO: Incluir guardias si tienen turno activo
            AND (
                p.tipo_personal IN ('regular', 'servicio', 'residente')
                OR (p.tipo_personal = 'guardia_24h' AND at.id IS NOT NULL)
            )
            GROUP BY p.Unidad
            ORDER BY p.Unidad";
    break;
```

### 2.2 Crear `api/horarios.php` - CRUD de horarios de trabajo

```php
<?php
require_once __DIR__ . '/../database/db_acceso.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            $sql = "SELECT * FROM horarios_trabajo WHERE activo = 1 ORDER BY nombre";
            $result = $conn_acceso->query($sql);
            $horarios = [];
            while ($row = $result->fetch_assoc()) {
                $horarios[] = $row;
            }
            echo json_encode($horarios);
            break;

        case 'get':
            $id = intval($_GET['id']);
            $sql = "SELECT * FROM horarios_trabajo WHERE id = ?";
            $stmt = $conn_acceso->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            echo json_encode($result->fetch_assoc());
            break;

        case 'create':
            $data = json_decode(file_get_contents('php://input'), true);
            $sql = "INSERT INTO horarios_trabajo
                    (nombre, descripcion, hora_entrada, hora_salida, dias_semana,
                     duracion_horas, es_turno_24h, tolerancia_minutos)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn_acceso->prepare($sql);
            $stmt->bind_param("sssssiii",
                $data['nombre'],
                $data['descripcion'],
                $data['hora_entrada'],
                $data['hora_salida'],
                $data['dias_semana'],
                $data['duracion_horas'],
                $data['es_turno_24h'],
                $data['tolerancia_minutos']
            );
            $stmt->execute();
            echo json_encode(['success' => true, 'id' => $conn_acceso->insert_id]);
            break;

        case 'update':
            $data = json_decode(file_get_contents('php://input'), true);
            $sql = "UPDATE horarios_trabajo
                    SET nombre = ?, descripcion = ?, hora_entrada = ?,
                        hora_salida = ?, dias_semana = ?, duracion_horas = ?,
                        es_turno_24h = ?, tolerancia_minutos = ?
                    WHERE id = ?";
            $stmt = $conn_acceso->prepare($sql);
            $stmt->bind_param("sssssiiii",
                $data['nombre'],
                $data['descripcion'],
                $data['hora_entrada'],
                $data['hora_salida'],
                $data['dias_semana'],
                $data['duracion_horas'],
                $data['es_turno_24h'],
                $data['tolerancia_minutos'],
                $data['id']
            );
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;

        case 'delete':
            $id = intval($_POST['id']);
            // Soft delete
            $sql = "UPDATE horarios_trabajo SET activo = 0 WHERE id = ?";
            $stmt = $conn_acceso->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;

        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
```

### 2.3 Crear `api/turnos.php` - Gestión de turnos programados

```php
<?php
require_once __DIR__ . '/../database/db_acceso.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            // Listar todos los turnos con filtros opcionales
            $fecha_inicio = $_GET['fecha_inicio'] ?? null;
            $fecha_fin = $_GET['fecha_fin'] ?? null;
            $estado = $_GET['estado'] ?? null;

            $sql = "SELECT at.*, ht.nombre as horario_nombre
                    FROM asignacion_turnos at
                    JOIN horarios_trabajo ht ON at.horario_id = ht.id
                    WHERE 1=1";

            if ($fecha_inicio) {
                $sql .= " AND at.fecha_inicio >= '$fecha_inicio'";
            }
            if ($fecha_fin) {
                $sql .= " AND at.fecha_fin <= '$fecha_fin'";
            }
            if ($estado) {
                $sql .= " AND at.estado = '$estado'";
            }

            $sql .= " ORDER BY at.fecha_inicio DESC";
            $result = $conn_acceso->query($sql);
            $turnos = [];
            while ($row = $result->fetch_assoc()) {
                $turnos[] = $row;
            }
            echo json_encode($turnos);
            break;

        case 'active':
            // Obtener turno activo de un personal
            $rut = $_GET['rut'] ?? '';
            $sql = "SELECT at.*, ht.nombre as horario_nombre
                    FROM asignacion_turnos at
                    JOIN horarios_trabajo ht ON at.horario_id = ht.id
                    WHERE at.personal_rut = ?
                    AND at.estado = 'activo'
                    AND NOW() BETWEEN at.fecha_inicio AND at.fecha_fin
                    LIMIT 1";
            $stmt = $conn_acceso->prepare($sql);
            $stmt->bind_param("s", $rut);
            $stmt->execute();
            $result = $stmt->get_result();
            echo json_encode($result->fetch_assoc());
            break;

        case 'assign':
            // Asignar nuevo turno
            $data = json_decode(file_get_contents('php://input'), true);

            // Verificar que no haya turnos solapados
            $sql_check = "SELECT COUNT(*) as count FROM asignacion_turnos
                          WHERE personal_rut = ?
                          AND estado IN ('programado', 'activo')
                          AND (
                              (fecha_inicio BETWEEN ? AND ?)
                              OR (fecha_fin BETWEEN ? AND ?)
                              OR (? BETWEEN fecha_inicio AND fecha_fin)
                          )";
            $stmt = $conn_acceso->prepare($sql_check);
            $stmt->bind_param("ssssss",
                $data['personal_rut'],
                $data['fecha_inicio'], $data['fecha_fin'],
                $data['fecha_inicio'], $data['fecha_fin'],
                $data['fecha_inicio']
            );
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row['count'] > 0) {
                throw new Exception('El personal ya tiene un turno programado en ese período');
            }

            // Insertar turno
            $sql = "INSERT INTO asignacion_turnos
                    (personal_rut, personal_nombre, horario_id, fecha_inicio, fecha_fin,
                     tipo_turno, motivo, autorizado_por_rut, autorizado_por_nombre)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn_acceso->prepare($sql);
            $stmt->bind_param("ssissssss",
                $data['personal_rut'],
                $data['personal_nombre'],
                $data['horario_id'],
                $data['fecha_inicio'],
                $data['fecha_fin'],
                $data['tipo_turno'],
                $data['motivo'],
                $data['autorizado_por_rut'],
                $data['autorizado_por_nombre']
            );
            $stmt->execute();

            // Actualizar el campo turno_actual en personal
            $turno_id = $conn_acceso->insert_id;
            $sql_update = "UPDATE personal_db.personal
                          SET turno_actual = ?
                          WHERE NrRut = ?";
            $stmt = $conn_acceso->prepare($sql_update);
            $stmt->bind_param("is", $turno_id, $data['personal_rut']);
            $stmt->execute();

            echo json_encode(['success' => true, 'id' => $turno_id]);
            break;

        case 'cancel':
            // Cancelar turno
            $id = intval($_POST['id']);
            $sql = "UPDATE asignacion_turnos SET estado = 'cancelado' WHERE id = ?";
            $stmt = $conn_acceso->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;

        case 'calendar':
            // Vista de calendario mensual
            $mes = $_GET['mes'] ?? date('m');
            $anio = $_GET['anio'] ?? date('Y');

            $sql = "SELECT at.*, ht.nombre as horario_nombre,
                           DATE(at.fecha_inicio) as dia
                    FROM asignacion_turnos at
                    JOIN horarios_trabajo ht ON at.horario_id = ht.id
                    WHERE YEAR(at.fecha_inicio) = ? AND MONTH(at.fecha_inicio) = ?
                    AND at.estado IN ('programado', 'activo')
                    ORDER BY at.fecha_inicio";
            $stmt = $conn_acceso->prepare($sql);
            $stmt->bind_param("ii", $anio, $mes);
            $stmt->execute();
            $result = $stmt->get_result();

            $turnos = [];
            while ($row = $result->fetch_assoc()) {
                $dia = $row['dia'];
                if (!isset($turnos[$dia])) {
                    $turnos[$dia] = [];
                }
                $turnos[$dia][] = $row;
            }
            echo json_encode($turnos);
            break;

        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
```

---

## **Fase 3: Interfaz Frontend (Semana 4-5)**

### 3.1 Modificar `js/modules/personal.js` - Agregar selector tipo personal

```javascript
// En la función que genera el formulario de personal
function renderPersonalForm(personal = null) {
    const isEdit = personal !== null;

    return `
        <form id="personal-form">
            <!-- Campos existentes... -->

            <!-- NUEVO: Selector de Tipo de Personal -->
            <div class="mb-3">
                <label for="tipo-personal" class="form-label">Tipo de Personal</label>
                <select class="form-select" id="tipo-personal" name="tipo_personal" required>
                    <option value="regular" ${personal?.tipo_personal === 'regular' ? 'selected' : ''}>
                        Regular (Administrativo)
                    </option>
                    <option value="guardia_24h" ${personal?.tipo_personal === 'guardia_24h' ? 'selected' : ''}>
                        Guardia 24 horas
                    </option>
                    <option value="servicio" ${personal?.tipo_personal === 'servicio' ? 'selected' : ''}>
                        Servicio
                    </option>
                    <option value="residente" ${personal?.tipo_personal === 'residente' ? 'selected' : ''}>
                        Residente
                    </option>
                </select>
                <div class="form-text">
                    Define el tipo de personal para aplicar las reglas de horario correctas
                </div>
            </div>

            <!-- NUEVO: Selector de Horario de Trabajo -->
            <div class="mb-3">
                <label for="horario-trabajo" class="form-label">Horario de Trabajo</label>
                <select class="form-select" id="horario-trabajo" name="horario_trabajo_id">
                    <option value="">Cargando horarios...</option>
                </select>
            </div>

            <!-- NUEVO: Botón para asignar turnos (solo si es guardia/servicio) -->
            <div class="mb-3" id="turno-actions" style="display: none;">
                <button type="button" class="btn btn-info" id="btn-asignar-turno">
                    <i class="bi bi-calendar-plus me-2"></i>Asignar Turno
                </button>
            </div>

            <!-- Resto del formulario... -->
        </form>
    `;
}

// Cargar horarios dinámicamente
async function loadHorariosSelect() {
    try {
        const response = await fetch('./api/horarios.php?action=list');
        const horarios = await response.json();

        const select = document.getElementById('horario-trabajo');
        select.innerHTML = '<option value="">Seleccionar horario...</option>';

        horarios.forEach(horario => {
            const option = document.createElement('option');
            option.value = horario.id;
            option.textContent = `${horario.nombre} (${horario.hora_entrada || 'N/A'} - ${horario.hora_salida || 'N/A'})`;
            select.appendChild(option);
        });
    } catch (error) {
        console.error('Error al cargar horarios:', error);
    }
}

// Mostrar/ocultar botón de asignar turno según tipo
document.getElementById('tipo-personal')?.addEventListener('change', (e) => {
    const tipoPersonal = e.target.value;
    const turnoActions = document.getElementById('turno-actions');

    if (tipoPersonal === 'guardia_24h' || tipoPersonal === 'servicio') {
        turnoActions.style.display = 'block';
    } else {
        turnoActions.style.display = 'none';
    }
});
```

### 3.2 Crear `js/modules/turnos.js` - Módulo de gestión de turnos

```javascript
import turnosApi from '../api/turnos-api.js';

export function initTurnosModule(contentElement) {
    renderTurnosView(contentElement);
    loadTurnos();
}

function renderTurnosView(container) {
    container.innerHTML = `
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-calendar-week me-2"></i>Gestión de Turnos</h2>
                <button class="btn btn-primary" id="btn-nuevo-turno">
                    <i class="bi bi-plus-circle me-2"></i>Asignar Turno
                </button>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Fecha Inicio</label>
                            <input type="date" class="form-control" id="filter-fecha-inicio">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Fin</label>
                            <input type="date" class="form-control" id="filter-fecha-fin">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" id="filter-estado">
                                <option value="">Todos</option>
                                <option value="programado">Programado</option>
                                <option value="activo">Activo</option>
                                <option value="finalizado">Finalizado</option>
                                <option value="cancelado">Cancelado</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-secondary w-100" id="btn-filtrar">
                                <i class="bi bi-funnel me-2"></i>Filtrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vista de Calendario/Tabla -->
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#tab-lista">
                                <i class="bi bi-list-ul me-2"></i>Lista
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-calendario">
                                <i class="bi bi-calendar3 me-2"></i>Calendario
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tab-lista">
                            <div id="turnos-lista"></div>
                        </div>
                        <div class="tab-pane fade" id="tab-calendario">
                            <div id="turnos-calendario"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Event listeners
    document.getElementById('btn-nuevo-turno').addEventListener('click', () => {
        showAsignarTurnoModal();
    });

    document.getElementById('btn-filtrar').addEventListener('click', () => {
        loadTurnos();
    });
}

async function loadTurnos() {
    const fechaInicio = document.getElementById('filter-fecha-inicio').value;
    const fechaFin = document.getElementById('filter-fecha-fin').value;
    const estado = document.getElementById('filter-estado').value;

    try {
        const turnos = await turnosApi.list({ fechaInicio, fechaFin, estado });
        renderTurnosList(turnos);
    } catch (error) {
        showToast('Error al cargar turnos', 'error');
    }
}

function renderTurnosList(turnos) {
    const container = document.getElementById('turnos-lista');

    if (turnos.length === 0) {
        container.innerHTML = '<p class="text-center text-muted">No hay turnos programados</p>';
        return;
    }

    let html = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Personal</th>
                        <th>Tipo</th>
                        <th>Horario</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
    `;

    turnos.forEach(turno => {
        const estadoBadge = getEstadoBadge(turno.estado);
        const fechaInicio = new Date(turno.fecha_inicio).toLocaleString('es-CL');
        const fechaFin = new Date(turno.fecha_fin).toLocaleString('es-CL');

        html += `
            <tr>
                <td>${turno.personal_nombre}</td>
                <td><span class="badge bg-secondary">${turno.tipo_turno}</span></td>
                <td>${turno.horario_nombre}</td>
                <td>${fechaInicio}</td>
                <td>${fechaFin}</td>
                <td>${estadoBadge}</td>
                <td>
                    ${turno.estado === 'programado' ?
                        `<button class="btn btn-sm btn-danger" onclick="cancelarTurno(${turno.id})">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </button>` :
                        '-'
                    }
                </td>
            </tr>
        `;
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;
}

function getEstadoBadge(estado) {
    const badges = {
        'programado': '<span class="badge bg-info">Programado</span>',
        'activo': '<span class="badge bg-success">Activo</span>',
        'finalizado': '<span class="badge bg-secondary">Finalizado</span>',
        'cancelado': '<span class="badge bg-danger">Cancelado</span>'
    };
    return badges[estado] || estado;
}

function showAsignarTurnoModal() {
    // Implementar modal para asignar turno
    // Similar a otros modales del sistema
}
```

### 3.3 Modificar `js/modules/dashboard.js` - Agregar indicadores de tipo

```javascript
async function renderUnidadDetalle(modalBody, unidadNombre, category) {
    // ... código existente ...

    personal.forEach(item => {
        const nombre = `${item.Grado || ''} ${item.Nombres} ${item.Paterno} ${item.Materno || ''}`.trim();
        const horaEntrada = item.entry_time ?
            new Date(item.entry_time).toLocaleString('es-CL', { hour12: false }) : 'N/A';

        // NUEVO: Badge de tipo de personal
        const tipoBadge = getTipoPersonalBadge(item.tipo_personal);

        // NUEVO: Indicador de turno activo
        const turnoIndicator = item.turno_activo_id ?
            '<span class="badge bg-success ms-2"><i class="bi bi-clock-history me-1"></i>En Turno</span>' : '';

        tableHtml += `
            <tr>
                <td class="text-center">
                    ${nombre}
                    ${tipoBadge}
                    ${turnoIndicator}
                </td>
                <td class="text-center">${item.movil1 || 'N/A'}</td>
                <td class="text-center">${horaEntrada}</td>`;

        // ... resto del código ...
    });
}

function getTipoPersonalBadge(tipo) {
    const badges = {
        'regular': '<span class="badge bg-primary-subtle text-primary ms-2">Regular</span>',
        'guardia_24h': '<span class="badge bg-success-subtle text-success ms-2">Guardia 24h</span>',
        'servicio': '<span class="badge bg-warning-subtle text-warning ms-2">Servicio</span>',
        'residente': '<span class="badge bg-info-subtle text-info ms-2">Residente</span>'
    };
    return badges[tipo] || '';
}
```

---

## **Fase 4: Integración y Testing (Semana 5-6)**

### 4.1 Escenarios de Prueba

#### Escenario 1: Personal Regular - Horario Normal
```
ENTRADA: Personal tipo 'regular' entra 07:30, sale 16:30
ESPERADO: ✅ Sin alertas
QUERY: SELECT * FROM personal WHERE tipo_personal = 'regular' AND horario_trabajo_id = 1
```

#### Escenario 2: Personal Regular - Trabaja Tarde Sin Autorización
```
ENTRADA: Personal tipo 'regular' entra 07:30, no sale a las 16:30, NO tiene horas_extra
ESPERADO: ⚠️ Después 16:30 + 30min tolerancia = 17:00 → Alerta ROJA "No Autorizado"
QUERY: Alerta generada por query 'alerta-no-autorizado' modificado
```

#### Escenario 3: Guardia 24h Con Turno Activo
```
ENTRADA: Personal tipo 'guardia_24h' entra día 1 a las 07:30, tiene turno activo en asignacion_turnos
ESPERADO: ✅ Sin alertas (aunque esté 24 horas adentro)
QUERY:
  SELECT * FROM asignacion_turnos
  WHERE personal_rut = '12345678'
  AND estado = 'activo'
  AND NOW() BETWEEN fecha_inicio AND fecha_fin
```

#### Escenario 4: Guardia 24h SIN Turno Activo
```
ENTRADA: Personal tipo 'guardia_24h' entra día 1 a las 07:30, NO tiene turno activo
ESPERADO: ⚠️ Alerta ROJA "No Autorizado" (porque no está asignado a guardia)
QUERY: Alerta generada porque at.id IS NULL en query modificado
```

#### Escenario 5: Residente
```
ENTRADA: Personal tipo 'residente' entra/sale cualquier hora
ESPERADO: ✅ Sin alertas (excluido de lógica por tipo_personal = 'residente')
QUERY: WHERE p.tipo_personal NOT IN ('guardia_24h', 'residente')
```

#### Escenario 6: Personal Con Horas Extra Activa
```
ENTRADA: Personal tiene horas_extra activa con fecha_hora_termino = '2025-10-29 20:00:00'
ESPERADO:
  - Antes 20:00 → ✅ Sin alertas
  - Después 20:00 → ⚠️ Alerta AMARILLA "Atrasado" (tiene autorización pero pasó la hora)
QUERY: Alerta 'alerta-atrasado' con he.fecha_hora_termino < NOW()
```

### 4.2 Script de Pruebas SQL

```sql
-- Script de pruebas para validar nueva lógica

-- TEST 1: Crear personal de prueba de cada tipo
INSERT INTO personal_db.personal (Grado, Nombres, Paterno, Materno, NrRut, Unidad, tipo_personal, horario_trabajo_id)
VALUES
('CB1', 'Juan', 'Pérez', 'López', '11111111', 'G34', 'regular', 1),
('CB2', 'Pedro', 'González', 'Muñoz', '22222222', 'G34', 'guardia_24h', 2),
('CB3', 'María', 'Silva', 'Rojas', '33333333', 'G34', 'servicio', 1),
('S/G', 'Carlos', 'Morales', 'Díaz', '44444444', 'G34', 'residente', 5);

-- TEST 2: Crear entrada para cada uno
INSERT INTO acceso_pro_db.access_logs (target_id, target_type, action, punto_acceso, log_time)
VALUES
((SELECT id FROM personal_db.personal WHERE NrRut = '11111111'), 'personal', 'entrada', 'oficina', '2025-10-29 07:30:00'),
((SELECT id FROM personal_db.personal WHERE NrRut = '22222222'), 'personal', 'entrada', 'oficina', '2025-10-29 07:30:00'),
((SELECT id FROM personal_db.personal WHERE NrRut = '33333333'), 'personal', 'entrada', 'oficina', '2025-10-29 07:30:00'),
((SELECT id FROM personal_db.personal WHERE NrRut = '44444444'), 'personal', 'entrada', 'oficina', '2025-10-29 22:00:00');

-- TEST 3: Asignar turno a guardia
INSERT INTO acceso_pro_db.asignacion_turnos
(personal_rut, personal_nombre, horario_id, fecha_inicio, fecha_fin, tipo_turno, estado)
VALUES
('22222222', 'CB2 Pedro González Muñoz', 2, '2025-10-29 07:30:00', '2025-10-30 09:00:00', 'guardia', 'activo');

-- TEST 4: Probar query de alerta no autorizado (a las 17:30, 1 hora después horario)
-- ESPERADO: Solo debería aparecer '11111111' (regular sin turno ni horas_extra)
SELECT DISTINCT p.NrRut, p.Nombres, p.tipo_personal, at.id as turno_activo
FROM personal_db.personal p
JOIN acceso_pro_db.access_logs a ON p.id = a.target_id
LEFT JOIN acceso_pro_db.horas_extra he ON p.NrRut = he.personal_rut AND he.status = 'activo'
LEFT JOIN acceso_pro_db.asignacion_turnos at ON p.NrRut = at.personal_rut
    AND at.estado = 'activo'
    AND '2025-10-29 17:30:00' BETWEEN at.fecha_inicio AND at.fecha_fin
WHERE a.target_type = 'personal'
AND a.action = 'entrada'
AND a.log_status = 'activo'
AND p.tipo_personal NOT IN ('guardia_24h', 'residente')
AND at.id IS NULL
AND he.id IS NULL;

-- TEST 5: Verificar que el residente no aparece en alertas
-- ESPERADO: 0 registros
SELECT * FROM personal_db.personal p
JOIN acceso_pro_db.access_logs a ON p.id = a.target_id
WHERE p.NrRut = '44444444'
AND p.tipo_personal = 'residente';

-- TEST 6: Limpiar datos de prueba
DELETE FROM acceso_pro_db.access_logs WHERE target_id IN (
    SELECT id FROM personal_db.personal WHERE NrRut IN ('11111111', '22222222', '33333333', '44444444')
);
DELETE FROM acceso_pro_db.asignacion_turnos WHERE personal_rut IN ('11111111', '22222222', '33333333', '44444444');
DELETE FROM personal_db.personal WHERE NrRut IN ('11111111', '22222222', '33333333', '44444444');
```

---

## **Fase 5: Documentación (Semana 6)**

### 5.1 Diagrama ER Actualizado

```
┌─────────────────────────┐
│  personal               │
│  ├─ id (PK)             │
│  ├─ Grado               │
│  ├─ Nombres             │
│  ├─ NrRut               │
│  ├─ Unidad              │
│  ├─ es_residente        │
│  ├─ tipo_personal ★NEW  │─┐
│  ├─ horario_trabajo_id ★│─┼──────────────────────┐
│  └─ turno_actual ★NEW   │ │                      │
└─────────────────────────┘ │                      │
                            │                      │
┌─────────────────────────┐ │                      │
│  horarios_trabajo ★NEW  │ │                      │
│  ├─ id (PK)             │◄┘                      │
│  ├─ nombre              │                        │
│  ├─ hora_entrada        │                        │
│  ├─ hora_salida         │                        │
│  ├─ dias_semana         │                        │
│  ├─ es_turno_24h        │                        │
│  └─ tolerancia_minutos  │                        │
└─────────────────────────┘                        │
        ▲                                          │
        │                                          │
        │                                          │
┌─────────────────────────┐                        │
│  asignacion_turnos ★NEW │                        │
│  ├─ id (PK)             │                        │
│  ├─ personal_rut (FK) ──┼────────────────────────┘
│  ├─ horario_id (FK) ────┘
│  ├─ fecha_inicio        │
│  ├─ fecha_fin           │
│  ├─ tipo_turno          │
│  └─ estado              │
└─────────────────────────┘

┌─────────────────────────┐
│  access_logs            │
│  ├─ id (PK)             │
│  ├─ target_id (FK)──────┼──► personal.id
│  ├─ action              │
│  ├─ punto_acceso        │
│  └─ log_time            │
└─────────────────────────┘

┌─────────────────────────┐
│  horas_extra            │
│  ├─ id (PK)             │
│  ├─ personal_rut (FK) ──┼──► personal.NrRut
│  ├─ fecha_hora_termino  │
│  ├─ motivo              │
│  └─ status              │
└─────────────────────────┘
```

### 5.2 Manual de Usuario

**Guía Rápida: Gestión de Tipos de Personal**

**1. ¿Qué es el Tipo de Personal?**
El tipo de personal define las reglas de horario y alertas que se aplican:

- **Regular (Administrativo)**: Horario L-V 7:30-16:30. Genera alerta roja si permanece después de horario sin autorización.
- **Guardia 24h**: Turnos de 24 horas programados. NO genera alertas si tiene turno activo.
- **Servicio**: Personal de servicios con turnos especiales. Similar a guardias.
- **Residente**: Vive en la base. NO genera alertas de horario.

**2. ¿Cómo asignar el tipo a un personal?**
1. Ir a módulo "Personal"
2. Editar o crear personal
3. Seleccionar "Tipo de Personal" en el formulario
4. Guardar cambios

**3. ¿Cómo programar turnos de guardia?**
1. Ir a módulo "Gestión de Turnos"
2. Click en "Asignar Turno"
3. Seleccionar personal (debe ser tipo 'Guardia 24h' o 'Servicio')
4. Definir fecha/hora inicio y fin
5. Seleccionar tipo de horario (ej: Guardia 24h)
6. Guardar

**4. ¿Cómo interpretar las nuevas alertas?**

🟢 **Sin Alerta**:
- Personal trabajando en su horario normal
- Guardia con turno activo (aunque lleve 24h)
- Residente (cualquier hora)

🟡 **Alerta Amarilla "Atrasado"**:
- Personal con `horas_extra` activa
- Ya pasó la hora autorizada de salida
- Acción: Verificar si debe retirarse

🔴 **Alerta Roja "No Autorizado"**:
- Personal después de horario SIN autorización
- Guardia SIN turno activo asignado
- Acción: Verificar motivo o registrar `horas_extra`

**5. Dashboard mejorado**

El dashboard ahora muestra:
- Badge indicador de tipo de personal (Regular/Guardia/Servicio/Residente)
- Indicador "En Turno" para guardias/servicios activos
- Tarjetas separadas por unidad con drill-down
- Filtros por tipo de personal

---

## **Archivos del Proyecto**

### Nuevos Archivos

```
acceso/
├── database/
│   └── migrations/
│       ├── 003_add_personnel_types.sql ★NEW
│       ├── 004_create_horarios_trabajo.sql ★NEW
│       └── 005_create_asignacion_turnos.sql ★NEW
├── api/
│   ├── horarios.php ★NEW
│   └── turnos.php ★NEW
├── js/
│   ├── modules/
│   │   └── turnos.js ★NEW
│   └── api/
│       └── turnos-api.js ★NEW
├── views/
│   └── turnos.html ★NEW
└── PLAN_MEJORAS_FUTURO.md ★THIS FILE
```

### Archivos Modificados

```
acceso/
├── api/
│   ├── dashboard.php ★MODIFICAR (lógica alertas)
│   ├── personal.php ★MODIFICAR (agregar tipo_personal)
│   └── control-personal.php ★MODIFICAR (validación turnos)
├── js/
│   └── modules/
│       ├── dashboard.js ★MODIFICAR (indicadores visuales)
│       ├── personal.js ★MODIFICAR (selector tipo)
│       └── control-personal.js ★MODIFICAR (indicadores turnos)
```

---

## **Beneficios Esperados**

### Técnicos
✅ **Eliminación de falsos positivos**: Guardias y personal en turno no generarán alertas rojas
✅ **Horarios configurables**: No más código hardcoded, todo en base de datos
✅ **Escalabilidad**: Optimizado para 1500+ personas con índices apropiados
✅ **Mantenibilidad**: Lógica centralizada y documentada
✅ **Extensibilidad**: Fácil agregar nuevos tipos de horarios o turnos

### Operacionales
✅ **Gestión de turnos programada**: Sistema de calendario para asignar guardias
✅ **Visibilidad operacional**: Dashboard muestra claramente quién está en turno
✅ **Reducción de trabajo manual**: No más llamadas para verificar si guardia es legítima
✅ **Reportes mejorados**: Filtrado por tipo de personal en todos los reportes
✅ **Flexibilidad**: Soporte para residentes, guardias 24h, servicios, y personal regular

### Para Centinelas
✅ **Menos alarmas falsas**: Solo alertas realmente importantes
✅ **Indicadores visuales claros**: Badge "En Turno" muestra guardias activas
✅ **Información completa**: Saben tipo de personal al momento de registro
✅ **Decisiones rápidas**: Sistema indica si una presencia es legítima o no

---

## **Riesgos y Mitigaciones**

### Riesgo 1: Migración de datos incorrecta
**Mitigación**:
- Hacer backup completo antes de migración
- Ejecutar scripts en ambiente de prueba primero
- Validar manualmente una muestra de registros después de migración

### Riesgo 2: Performance con 1500+ registros
**Mitigación**:
- Índices optimizados en todas las tablas
- Queries con EXPLAIN ANALYZE antes de producción
- Caché de horarios y turnos activos
- Paginación en vistas de lista

### Riesgo 3: Complejidad operacional para usuarios
**Mitigación**:
- Capacitación completa antes de go-live
- Manual de usuario con casos de uso reales
- Soporte técnico disponible durante primeras semanas
- Valores por defecto razonables (tipo 'regular' por defecto)

### Riesgo 4: Turnos no actualizados a tiempo
**Mitigación**:
- Trigger automático para actualizar estados
- Notificaciones cuando turno está por finalizar
- Dashboard de turnos próximos a vencer
- Proceso semanal de revisión de turnos

---

## **Cronograma Detallado**

### Semana 1: Migración Base de Datos
- **Día 1-2**: Backup, ejecutar migraciones en pruebas, validar estructura
- **Día 3-4**: Migrar datos existentes, verificar integridad
- **Día 5**: Testing de queries, optimización de índices

### Semana 2: Backend - Alertas y Horarios
- **Día 1-2**: Modificar dashboard.php (lógica alertas)
- **Día 3**: Crear api/horarios.php
- **Día 4**: Testing de nuevas queries de alertas
- **Día 5**: Code review y ajustes

### Semana 3: Backend - Turnos
- **Día 1-2**: Crear api/turnos.php
- **Día 3**: Modificar api/personal.php
- **Día 4-5**: Testing integración turnos con alertas

### Semana 4: Frontend - Personal y Dashboard
- **Día 1-2**: Modificar js/modules/personal.js (selector tipo)
- **Día 3-4**: Modificar js/modules/dashboard.js (indicadores)
- **Día 5**: Testing UI, ajustes visuales

### Semana 5: Frontend - Turnos y Testing
- **Día 1-3**: Crear js/modules/turnos.js (calendario, asignación)
- **Día 4-5**: Testing completo end-to-end

### Semana 6: Documentación y Capacitación
- **Día 1-2**: Escribir documentación técnica y manual de usuario
- **Día 3-4**: Capacitación a operadores y centinelas
- **Día 5**: Go-live y soporte

---

## **Contacto y Soporte**

Para preguntas sobre este plan de implementación:
- Revisar documentación técnica en `/docs`
- Consultar changelog en `CHANGELOG_MEJORAS.md`
- Ejecutar tests en `/tests/test_alertas.sql`

**Última actualización:** 29 de Octubre de 2025
**Versión del documento:** 1.0
**Estado:** Plan aprobado, pendiente implementación
