<?php
require_once "conexion.php"; 

if (!isset($conn)) {
    die("❌ Error: No se pudo establecer conexión con la base de datos.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST["nombre"];
    $apellido = $_POST["apellido"];
    $correo = $_POST["correo"];
    $telefono = empty($_POST["telefono"]) ? NULL : $_POST["telefono"];
    $ambiente = $_POST["ambiente"];

    $sql = "INSERT INTO instructores (nombre, apellido, correo, telefono, ambiente) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("❌ Error en la preparación: " . $conn->error);
    }

    $stmt->bind_param("sssss", $nombre, $apellido, $correo, $telefono, $ambiente);

    if ($stmt->execute()) {
        
        header("Location: mostrar_registros.php");
        exit(); // 
    } else {
        echo "❌ Error al ejecutar la consulta: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>



