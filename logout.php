<?php
session_start();
session_destroy(); 
header("Location: login.php"); 
exit();


$id_usuario = $_SESSION['id_usuario'];
$conn->query("UPDATE almacenistas SET hora_salida = NOW() WHERE id = $id_usuario");

session_destroy(); 
header("Location: login.php");
exit();

?>