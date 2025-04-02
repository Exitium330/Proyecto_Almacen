<?php
require_once "auth.php"; 
?>


<?php
include 'conexion.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombres = $_POST['nombres'];
    $apellidos = $_POST['apellidos'];
    $correo = $_POST['correo'];
    $telefono = $_POST['telefono'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 
    $hora_ingreso = date('Y-m-d H:i:s');
    $hora_salida = date('Y-m-d 17:00:00');
    $estado = 'activo';

    $sql = "INSERT INTO almacenistas (nombres, apellidos, correo, telefono, password, hora_ingreso, hora_salida, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $nombres, $apellidos, $correo, $telefono, $password, $hora_ingreso, $hora_salida, $estado);

    if ($stmt->execute()) {
        echo "<script>alert('Usuario creado exitosamente'); window.location.href='crear_usuario.html';</script>";
    } else {
        echo "<script>alert('Error al crear el usuario'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Método no permitido.";
}


?>


