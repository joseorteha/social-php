<?php
$conexion = new mysqli("localhost", "jose", "jose2025", "socialphp");

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}
?>
