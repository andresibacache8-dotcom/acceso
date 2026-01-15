-- A ser ejecutado en la base de datos: acceso_pro_db

CREATE TABLE `access_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `log_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `target_id` int(11) NOT NULL,
  `target_type` enum('personal','vehiculo','visita','personal_comision') NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `action` enum('entrada','salida') NOT NULL,
  `status_message` varchar(255) DEFAULT NULL, -- Para guardar mensajes como 'Acceso denegado'
  `motivo` varchar(255) DEFAULT NULL,
  `log_status` enum('activo', 'cancelado') NOT NULL DEFAULT 'activo',
  `punto_acceso` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `target_idx` (`target_type`,`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;