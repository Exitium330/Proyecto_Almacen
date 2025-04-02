<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario</title>
    <link rel="stylesheet" href="Css/crear_nuevo_usuario.css?v=<?php echo time(); ?>">
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        if (localStorage.getItem("modoOscuro") === "enabled") {
            document.body.classList.add("dark-mode");
        } else {
            document.body.classList.remove("dark-mode");
        }
    });
    </script>
</head>
<body>
    <div class="container">
        <h2>Crear Nuevo Usuario</h2>
        <form action="crear_usuario.php" method="POST">
            <input type="text" name="nombres" placeholder="Nombres" required>
            <input type="text" name="apellidos" placeholder="Apellidos" required>
            <input type="email" name="correo" placeholder="Correo Electrónico" required>
            <input type="text" name="telefono" placeholder="Teléfono" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Crear Usuario</button>
        </form>
    </div>
</body>
</html>
