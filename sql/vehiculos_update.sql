-- Actualización de la tabla de vehículos para añadir fecha de inicio
ALTER TABLE `vehiculos` ADD COLUMN `fecha_inicio` date DEFAULT NULL AFTER `status`;