-- Adminer 4.2.4 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `agentes`;
CREATE TABLE `agentes` (
  `numero` varchar(6) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `campana` varchar(4) NOT NULL,
  PRIMARY KEY (`numero`),
  UNIQUE KEY `numero` (`numero`),
  KEY `campana` (`campana`),
  CONSTRAINT `agentes_ibfk_1` FOREIGN KEY (`campana`) REFERENCES `campanas` (`prefijo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `campanas`;
CREATE TABLE `campanas` (
  `prefijo` varchar(4) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `marca` varchar(2) NOT NULL,
  PRIMARY KEY (`prefijo`),
  UNIQUE KEY `prefijo` (`prefijo`),
  KEY `marca` (`marca`),
  CONSTRAINT `campanas_ibfk_1` FOREIGN KEY (`marca`) REFERENCES `marcas` (`prefijo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `descansos`;
CREATE TABLE `descansos` (
  `prefijo` varchar(2) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  PRIMARY KEY (`prefijo`),
  UNIQUE KEY `prefijo` (`prefijo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `descansos_tomados`;
CREATE TABLE `descansos_tomados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login_logout_id` int(11) NOT NULL,
  `descanso` varchar(2) NOT NULL,
  `fecha_inicio` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_fin` timestamp NULL DEFAULT NULL,
  `motivo_desconexion` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `descanso` (`descanso`),
  KEY `login_logout_id` (`login_logout_id`),
  CONSTRAINT `descansos_tomados_ibfk_1` FOREIGN KEY (`descanso`) REFERENCES `descansos` (`prefijo`),
  CONSTRAINT `descansos_tomados_ibfk_2` FOREIGN KEY (`login_logout_id`) REFERENCES `sesiones` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `marcas`;
CREATE TABLE `marcas` (
  `prefijo` varchar(2) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`prefijo`),
  UNIQUE KEY `prefix` (`prefijo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `sesiones`;
CREATE TABLE `sesiones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agente` varchar(6) NOT NULL,
  `extension` varchar(20) NOT NULL,
  `fecha_inicio` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_fin` timestamp NULL DEFAULT NULL,
  `motivo_desconexion` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agente` (`agente`),
  CONSTRAINT `sesiones_ibfk_1` FOREIGN KEY (`agente`) REFERENCES `agentes` (`numero`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `supervisores_marcas`;
CREATE TABLE `supervisores_marcas` (
  `marca` varchar(2) NOT NULL,
  `id_supervisor` int(11) NOT NULL,
  KEY `marca` (`marca`),
  CONSTRAINT `supervisores_marcas_ibfk_1` FOREIGN KEY (`marca`) REFERENCES `marcas` (`prefijo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- 2017-04-27 02:03:13
