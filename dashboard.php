<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel</title>
</head>
<body>
    <h2>Bienvenido, <?php echo $_SESSION['nombre']; ?></h2>
    <a href="logout.php">Cerrar sesión</a>

    <?php if ($_SESSION['es_admin'] == 1) { ?>
        <h3>Opciones de administrador</h3>
        <a href="registro.php">Registrar nuevo usuario</a>
    <?php } ?>

    <a href="logout.php">Cerrar sesión</a>

</body>
</html>
