-- Tabla de historial de cambios de vehículos
CREATE TABLE IF NOT EXISTS `vehiculo_historial` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehiculo_id` int(11) NOT NULL,
  `patente` varchar(10) NOT NULL,
  `personalId_anterior` int(11) DEFAULT NULL,
  `personalId_nuevo` int(11) DEFAULT NULL,
  `fecha_cambio` datetime NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `tipo_cambio` enum('creacion','actualizacion','eliminacion','cambio_propietario') NOT NULL,
  `detalles` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vehiculo_id` (`vehiculo_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `vehiculo_historial_ibfk_1` FOREIGN KEY (`vehiculo_id`) REFERENCES `vehiculos` (`id`) ON DELETE CASCADE,
  -- CORRECCIÓN: Se especifica la base de datos "personal_db"
  CONSTRAINT `vehiculo_historial_ibfk_2` FOREIGN KEY (`personalId_anterior`) REFERENCES `personal_db`.`personal` (`id`) ON DELETE SET NULL,
  -- CORRECCIÓN: Se especifica la base de datos "personal_db"
  CONSTRAINT `vehiculo_historial_ibfk_3` FOREIGN KEY (`personalId_nuevo`) REFERENCES `personal_db`.`personal` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehiculo_historial_ibfk_4` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;