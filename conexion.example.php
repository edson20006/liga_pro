<?php
// Copia este archivo como conexion.php y coloca tus credenciales locales.
$servidor = "localhost";
$usuario = "TU_USUARIO";
$password = "TU_PASSWORD";
$base_datos = "liga_futbol_pro";

$conn = new mysqli($servidor, $usuario, $password, $base_datos);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Error de conexion: " . $conn->connect_error);
}
?>
