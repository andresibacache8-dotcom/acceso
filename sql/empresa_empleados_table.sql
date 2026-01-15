CREATE TABLE `acceso_pro_db`.`empresa_empleados` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `paterno` VARCHAR(100) NOT NULL,
  `materno` VARCHAR(100) NULL,
  `rut` VARCHAR(20) NOT NULL UNIQUE,
  `fecha_expiracion` DATE NULL,
  `acceso_permanente` TINYINT(1) NOT NULL DEFAULT 0,
  `status` VARCHAR(20) NOT NULL DEFAULT 'autorizado',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;