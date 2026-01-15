CREATE TABLE `acceso_pro_db`.`empresas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(255) NOT NULL,
  `unidad_poc` VARCHAR(255) NULL,
  `poc_rut` VARCHAR(20) NULL,
  `poc_nombre` VARCHAR(255) NULL,
  `poc_anexo` VARCHAR(50) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;