<?php
session_start();
include 'conexion.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST['correo'];
    $password = $_POST['password'];

   
    $sql = "SELECT id_almacenista, nombres, password, es_admin FROM almacenistas WHERE correo=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();

  
    $row = $result->fetch_assoc();

    if ($row && password_verify($password, $row['password'])) { 
       
        $_SESSION['id_usuario'] = $row['id_almacenista'];
        $_SESSION['nombre'] = $row['nombres'];
        $_SESSION['es_admin'] = $row['es_admin'];

        header("Location: index.php");
        exit();
    } else {
        echo "❌ Usuario o contraseña incorrectos.";
    }

    $_SESSION['hora_ingreso'] = date("Y-m-d H:i:s"); 

$id_usuario = $_SESSION['id_usuario'];
$conn->query("UPDATE almacenistas SET hora_ingreso = NOW() WHERE id = $id_usuario");


    $stmt->close();
    $conn->close();
}
?>
