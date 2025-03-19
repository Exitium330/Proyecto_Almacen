<?php
// Parámetros de conexión
$servidor = "localhost";  // Servidor de MySQL
$usuario = "root";        // Usuario de MySQL 
$clave = "1027802491";              // Contraseña 
$base_datos = "proyecto_almacen"; // Nombre de la base de datos

// Conectar a MySQL con MySQLi
$conexion = new mysqli($servidor, $usuario, $clave, $base_datos);

// Verificar la conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
} else {
    echo "Conexión exitosa a la base de datos";
}
?>
