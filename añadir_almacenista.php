<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['es_admin'] != 1) {
    header("Location: login.php"); 
    exit();
}


$conn = new mysqli("localhost", "root", "1027802491", "proyecto_almacen");


if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}


$mensaje = "";
$clase_mensaje = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (!empty($_POST['nombres']) && !empty($_POST['apellidos']) && !empty($_POST['correo'])) {
        
       
        $nombres = trim($_POST['nombres']);
        $apellidos = trim($_POST['apellidos']);
        $correo = trim($_POST['correo']);
        $telefono = !empty($_POST['telefono']) ? trim($_POST['telefono']) : NULL;
 

        
        $sql = "INSERT INTO almacenistas (nombres, apellidos, correo, telefono, hora_ingreso)
        VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $nombres, $apellidos, $correo, $telefono);


        if ($stmt->execute()) {
            $mensaje = "✅ Almacenista añadido correctamente.";
            $clase_mensaje = "success";
        } else {
            $mensaje = "❌ Error al añadir almacenista: " . $conn->error;
            $clase_mensaje = "error";
        }

        
        $stmt->close();
    } else {
        $mensaje = "⚠️ Por favor, completa todos los campos obligatorios.";
        $clase_mensaje = "warning";
    }
}


$conn->close();
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Almacenista</title>
    <link rel="stylesheet" href="Css/añadir_almacenista.css?v=<?php echo time(); ?>">

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        if (localStorage.getItem("modoOscuro") === "enabled") {
            document.body.classList.add("dark-mode");
        } else {
            document.body.classList.remove("dark-mode");
        }
    });
    </script>
    
    <style>
        .mensaje { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
    </style>
     
</head>
<body>

    <h2>Añadir Nuevo Almacenista</h2>

    <?php if (!empty($mensaje)): ?>
        <div class="mensaje <?php echo $clase_mensaje; ?>"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <label for="nombres">Nombres:</label>
        <input type="text" name="nombres" required><br>

        <label for="apellidos">Apellidos:</label>
        <input type="text" name="apellidos" required><br>

        <label for="correo">Correo:</label>
        <input type="email" name="correo" required><br>

        <label for="telefono">Teléfono:</label>
        <input type="text" name="telefono"><br>


        <button type="submit">Añadir Almacenista</button>
    </form>

    <a href="index.php">🔙 Volver al menú</a>

</body>
</html>


