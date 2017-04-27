<?php

// libreria para realizar la conexion
require_once '/usr/local/sbin/ccl_ligero/MysqliDb.php';

// zona horaria
date_default_timezone_set('America/Guayaquil');

$credenciales = [
	"host" => "127.0.0.1",
	"user" => "ccl_ligero",
	"password" => "6NV00LpdmwKdw",
	"database" => "ccl_ligero"
];

$condiciones = [
	"fecha_fin",
	NULL,
	" IS"
];

// actualizar la tabla descansos
$descansos = new MysqliDb ($credenciales["host"], $credenciales["user"], $credenciales["password"], $credenciales["database"]);

// datos que se van a setear en la desconexion
$datos = [
	"fecha_fin" => $descansos->now(),
	"motivo_desconexion" => "forzado"
];

$descansos->where($condiciones[0], $condiciones[1], $condiciones[2]);

$descansos->update("descansos_tomados", $datos);

// actualizar la tabla sesiones
$sesiones = new MysqliDb ($credenciales["host"], $credenciales["user"], $credenciales["password"], $credenciales["database"]);

// datos que se van a setear en la desconexion
$datos = [
	"fecha_fin" => $sesiones->now(),
	"motivo_desconexion" => "forzado"
];

$sesiones->where($condiciones[0], $condiciones[1], $condiciones[2]);

$sesiones->update("sesiones", $datos);