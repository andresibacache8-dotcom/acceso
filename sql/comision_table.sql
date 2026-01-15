CREATE TABLE `personal_db`.`personal_comision` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `rut` VARCHAR(12) NOT NULL,
  `nombre_completo` VARCHAR(255) NOT NULL,
  `unidad_origen` VARCHAR(255) NULL,
  `unidad_poc` VARCHAR(255) NULL,
  `fecha_inicio` DATE NULL,
  `fecha_fin` DATE NULL,
  `motivo` TEXT NULL,
  `poc_nombre` VARCHAR(255) NULL,
  `poc_anexo` VARCHAR(50) NULL,
  `estado` VARCHAR(20) NOT NULL DEFAULT 'Activo',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `rut_UNIQUE` (`rut` ASC));