-- Agregar columna tipo_vehiculo a la tabla vehiculos
-- Ejecutar en phpMyAdmin o MySQL CLI en la base de datos: acceso_pro_db

USE acceso_pro_db;

ALTER TABLE `vehiculos` 
ADD COLUMN `tipo_vehiculo` VARCHAR(50) DEFAULT 'AUTO' AFTER `tipo`;

-- Actualizar registros existentes con valor por defecto
UPDATE `vehiculos` SET `tipo_vehiculo` = 'AUTO' WHERE `tipo_vehiculo` IS NULL;

-- Verificar
SELECT id, patente, marca, modelo, tipo, tipo_vehiculo FROM vehiculos LIMIT 5;
