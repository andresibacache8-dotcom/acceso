-- Migración: Agregar campos de nombre separado a personal_comision
-- Fecha: 2025-10-28
-- Descripción: Divide el campo nombre_completo en grado, nombres, paterno, materno

ALTER TABLE personal_comision ADD COLUMN (
    grado VARCHAR(50) NULL COMMENT 'Grado militar/civil',
    nombres VARCHAR(100) NULL COMMENT 'Primer nombre o nombres',
    paterno VARCHAR(100) NULL COMMENT 'Apellido paterno',
    materno VARCHAR(100) NULL COMMENT 'Apellido materno'
) AFTER rut;

-- Crear índice para búsquedas rápidas por apellido
CREATE INDEX idx_nombre_comision ON personal_comision(paterno, materno, nombres);

-- Mensaje de confirmación
SELECT 'Migración completada: Campos agregados a personal_comision' AS mensaje;
