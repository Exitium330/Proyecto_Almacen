<?php
session_start();


if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}


if (!isset($_SESSION['es_admin'])) {
    echo "⚠️ Error: No se detectó el rol de administrador en la sesión.";
    exit();
}


if ($_SESSION['es_admin'] != 1) {
    die("🚫 Acceso denegado. No tienes permisos para acceder a esta sección. <a href='index.php'>Volver</a>");
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuraciones</title>
    <link rel="stylesheet" href="Css/ajustes.css">

</head>
<body>
    <div class="settings-container">
        <h1>Ajustes</h1>
        <div class="settings">
            <button>Cambiar Contraseña</button>
            <button onclick="window.location.href='crear_usuario.html'">➕ Añadir Almacenista</button>
            <button>Aumentar Tamaño de Letra</button>
            <button>Disminuir Tamaño de Letra</button>
        </div>
    </div>
</body>
</html>
