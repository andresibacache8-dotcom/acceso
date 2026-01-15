-- ============================================================================
-- MIGRACIÓN 006: CREAR TABLA GUARDIA_SERVICIO
-- ============================================================================
-- Descripción: Tabla para registrar personal de guardia y servicio activo
--              Permite excluir guardias/servicios de alertas de "No Autorizado"
-- Fecha: 2025-10-29
-- ============================================================================

USE acceso_pro_db;

-- Crear tabla guardia_servicio
CREATE TABLE IF NOT EXISTS guardia_servicio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personal_rut VARCHAR(20) NOT NULL COMMENT 'RUT del personal de guardia/servicio',
    personal_nombre VARCHAR(255) NOT NULL COMMENT 'Nombre completo del personal',
    tipo ENUM('GUARDIA', 'SERVICIO') NOT NULL COMMENT 'Tipo de registro',
    servicio_detalle TEXT COMMENT 'Detalles del servicio (opcional)',
    anexo VARCHAR(50) COMMENT 'Anexo telefónico',
    movil VARCHAR(20) COMMENT 'Número móvil',
    fecha_ingreso DATETIME NOT NULL COMMENT 'Fecha y hora de inicio del turno',
    fecha_salida DATETIME NULL COMMENT 'Fecha y hora de fin del turno',
    estado ENUM('activo', 'finalizado') DEFAULT 'activo' COMMENT 'Estado del registro',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_personal_rut (personal_rut),
    INDEX idx_estado (estado),
    INDEX idx_fecha_ingreso (fecha_ingreso),
    INDEX idx_rut_estado (personal_rut, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Registros de guardia y servicio activo';

-- Trigger para registrar en access_logs cuando se crea guardia/servicio (OPCIONAL)
-- Esto permite que el registro de guardia también aparezca en el log general
DELIMITER $$
CREATE TRIGGER after_guardia_servicio_insert
AFTER INSERT ON guardia_servicio
FOR EACH ROW
BEGIN
    DECLARE personal_id INT;

    -- Obtener el ID del personal desde la tabla personal
    SELECT id INTO personal_id
    FROM personal_db.personal
    WHERE NrRut = NEW.personal_rut
    LIMIT 1;

    -- Si se encuentra el personal, registrar en access_logs
    IF personal_id IS NOT NULL THEN
        INSERT INTO access_logs (
            target_id,
            target_type,
            action,
            punto_acceso,
            motivo,
            log_time
        ) VALUES (
            personal_id,
            'personal',
            'entrada',
            'guardia',
            CONCAT(NEW.tipo, ' - ', IFNULL(NEW.servicio_detalle, 'Sin detalles')),
            NEW.fecha_ingreso
        );
    END IF;
END$$
DELIMITER ;

-- ============================================================================
-- DATOS DE PRUEBA (COMENTAR EN PRODUCCIÓN)
-- ============================================================================
/*
-- Ejemplo de inserción de guardia activa
INSERT INTO guardia_servicio
(personal_rut, personal_nombre, tipo, servicio_detalle, movil, fecha_ingreso)
VALUES
('12345678', 'CB1 JUAN PEREZ LOPEZ', 'GUARDIA', 'Guardia Principal', '987654321', NOW());

-- Ejemplo de inserción de servicio activo
INSERT INTO guardia_servicio
(personal_rut, personal_nombre, tipo, servicio_detalle, anexo, movil, fecha_ingreso)
VALUES
('87654321', 'CB2 MARIA SILVA ROJAS', 'SERVICIO', 'Servicio de Comunicaciones', '1234', '912345678', NOW());
*/

-- ============================================================================
-- VERIFICACIÓN
-- ============================================================================
-- Verificar que la tabla se creó correctamente
SELECT
    TABLE_NAME,
    TABLE_ROWS,
    CREATE_TIME,
    TABLE_COMMENT
FROM
    information_schema.TABLES
WHERE
    TABLE_SCHEMA = 'acceso_pro_db'
    AND TABLE_NAME = 'guardia_servicio';

-- Verificar índices creados
SHOW INDEX FROM guardia_servicio;

-- ============================================================================
-- CONSULTAS ÚTILES
-- ============================================================================
/*
-- Ver guardias/servicios activos
SELECT * FROM guardia_servicio WHERE estado = 'activo' ORDER BY fecha_ingreso DESC;

-- Ver historial completo
SELECT * FROM guardia_servicio ORDER BY fecha_ingreso DESC;

-- Verificar si un RUT tiene guardia/servicio activo
SELECT * FROM guardia_servicio
WHERE personal_rut = '12345678' AND estado = 'activo';

-- Finalizar un turno de guardia
UPDATE guardia_servicio
SET fecha_salida = NOW(), estado = 'finalizado'
WHERE id = 1;
*/
