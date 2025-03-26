<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <form action="procesar_login.php" method="POST">
            <label for="correo">Correo:</label>
            <input type="email" name="correo" required placeholder="Ingresa tu correo">

            <label for="password">Contraseña:</label>
            <input type="password" name="password" required placeholder="Ingresa tu contraseña">

            <button type="submit">Ingresar</button>
        </form>
    </div>
</body>
</html>
