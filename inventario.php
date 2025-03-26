<?php
include 'conexion.php'; 


$sql = "SELECT id, nombre, categoria, cantidad FROM inventario";
$resultado = $conn->query($sql);
?>








<?php
$conn->close(); 
?>