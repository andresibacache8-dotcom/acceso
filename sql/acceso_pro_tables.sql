-- A ser ejecutado en la base de datos: acceso_pro_db

CREATE TABLE `vehiculos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patente` varchar(10) NOT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `personalId` int(11) NOT NULL, -- Este ID se relaciona con la tabla 'personal' en la otra BD
  `status` varchar(20) DEFAULT NULL,
  `fecha_expiracion` date DEFAULT NULL,
  `acceso_permanente` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patente_UNIQUE` (`patente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `visitas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rut` varchar(20) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `empresa` varchar(150) DEFAULT NULL,
  `poc` varchar(150) DEFAULT NULL,
  `movil` varchar(20) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'autorizado',
  `fecha_expiracion` date DEFAULT NULL,
  `acceso_permanente` tinyint(1) DEFAULT NULL,
  `en_lista_negra` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

