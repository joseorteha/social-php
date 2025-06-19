<?php
$conexion = new mysqli(
    "sql309.infinityfree.com",
    "if0_39269301",
    "NgDQpWAilTXb3",
    "if0_39269301_socialphp"
);

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}
?>
