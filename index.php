<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

// Incluir la conexión a la base de datos
include("conexion.php");

if ($conn->connect_error) {
    error_log("Error de conexión: " . $conn->connect_error, 3, "error_log.txt");
    die("❌ Error de conexión, intente más tarde.");
}

// Obtener el estado de administrador del usuario actual
$id_usuario = $_SESSION['id_usuario'];
$sql_admin = "SELECT es_admin FROM almacenistas WHERE id_almacenista = ?";
$stmt_admin = $conn->prepare($sql_admin);
$stmt_admin->bind_param("i", $id_usuario);
$stmt_admin->execute();
$resultado_admin = $stmt_admin->get_result();
$usuario = $resultado_admin->fetch_assoc();
$es_admin = ($usuario['es_admin'] == 1);
$stmt_admin->close();

// Obtener los almacenistas activos (solo para admin)
$almacenistas_activos = [];
if ($es_admin) {
    $sql_activos = "SELECT nombres, apellidos, hora_ingreso 
                    FROM almacenistas 
                    WHERE estado = 'activo' AND es_admin = 0 
                    ORDER BY hora_ingreso DESC";
    $resultado_activos = $conn->query($sql_activos);
    if ($resultado_activos->num_rows > 0) {
        while ($fila = $resultado_activos->fetch_assoc()) {
            $almacenistas_activos[] = $fila;
        }
    }
}

echo "Bienvenido, " . $_SESSION['nombre']; 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú</title>
    <link rel="stylesheet" href="Css/style.css?v=<?php echo time(); ?>">


    <script>
    document.addEventListener("DOMContentLoaded", function () {
        // Modo oscuro
        if (localStorage.getItem("modoOscuro") === "enabled") {
            document.body.classList.add("dark-mode");
        } else {
            document.body.classList.remove("dark-mode");
        }

        // Manejar el menú desplegable de sesiones
        const sesionesBtn = document.querySelector('.sesiones-btn');
        const sesionesMenu = document.querySelector('.sesiones-menu');

        if (sesionesBtn && sesionesMenu) {
            sesionesBtn.addEventListener('click', function () {
                sesionesMenu.classList.toggle('active');
            });

            // Cerrar el menú si se hace clic fuera de él
            document.addEventListener('click', function (event) {
                if (!sesionesBtn.contains(event.target) && !sesionesMenu.contains(event.target)) {
                    sesionesMenu.classList.remove('active');
                }
            });
        }
    });
    </script>
</head>
<body>

    <!-- Sección de almacenistas activos (solo visible para admin) -->
    <?php if ($es_admin): ?>
        <div class="sesiones-container">
            <button class="sesiones-btn">👥 Almacenistas Activos</button>
            <div class="sesiones-menu">
                <h3>Almacenistas Activos</h3>
                <?php if (count($almacenistas_activos) > 0): ?>
                    <ul>
                        <?php foreach ($almacenistas_activos as $almacenista): ?>
                            <li>
                                <?php echo htmlspecialchars($almacenista['nombres'] . ' ' . $almacenista['apellidos']); ?> 
                                <span>(<?php echo date('d/m/Y H:i', strtotime($almacenista['hora_ingreso'])); ?>)</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-activos">No hay almacenistas activos.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="sidebar">
        <div class="user-info">
            🔑 Usuario logeado: <span id="username"><?php echo $_SESSION['nombre']; ?></span>
        </div>
        <h2>📌 Menú</h2>
        <ul>
            <li><a href="prestamos.php">📚 Préstamos y devoluciones</a></li>
            <li><a href="inventario.php">📦 Inventario</a></li>
            <li><a href="registro.html">👥 Registro de instructores</a></li>
            <li><a href="">📝 Novedades</a></li>
            <li><a href="mostrar_registros.php">🗒️ Listado de instructores</a></li>
            <li><a class="ajuste" href="ajustes.php">⚙️ Ajustes</a></li>
        </ul>
        <a href="logout.php" class="logout-btn">🚪 Cerrar sesión</a>
    </div>

    <div class="content">
        <h1>Información del software</h1>
        <p>Este software ayuda en la gestión de inventarios y préstamos.</p>

        <div class="carousel-container">
            <div class="carousel">
                <img src="Img/almacen-interior-logistica-entrega-carga_107791-1777.avif" alt="Imagen 1">
                <img src="Img/LOGOSENA-removebg-preview.png" alt="Imagen 2">
                <img src="Img/pngtree-black-warehouse-free-drawing-image_2292759.jpg" alt="Imagen 3">
                <img src="Img/Sena_logoverde.png" alt="Imagen 4">
                <img src="Img/logo-de-SENA-png-Negro-300x300-1.png" alt="Imagen 5">
            </div>
        </div>
    </div>

    <footer class="pie">
        © 2025 Almacén SENA. Todos los derechos reservados.
    </footer>

    <script>
        const carousel = document.querySelector('.carousel');
        const images = document.querySelectorAll('.carousel img');
        let index = 0;
        const totalImages = images.length;

        function changeImage() {
            index = (index + 1) % totalImages;
            carousel.style.transform = `translateX(-${index * 100}%)`;
        }

        setInterval(changeImage, 3000);
    </script>

</body>
</html>

<?php $conn->close(); ?>




