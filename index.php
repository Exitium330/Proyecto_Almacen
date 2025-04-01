<?php
session_start(); 

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
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
    if (localStorage.getItem("modoOscuro") === "enabled") {
        document.body.classList.add("dark-mode");
    } else {
        document.body.classList.remove("dark-mode");
    }
});
</script>




</head>
<div class="sidebar">
        <div class="user-info">
            🔑 Usuario logeado: <span id="username">Nombre</span>
        </div>
        <h2>📌 Menú</h2>
        <ul>
            <li><a href="prestamos.html">📚 Préstamos y devoluciones</a></li>
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
            </div>
        </div>
    </div>

    <footer class="pie">
        &copy; 2025 Almacén SENA. Todos los derechos reservados.
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

    
        document.getElementById('username').textContent = "<?php echo $_SESSION['nombre']; ?>";
    </script>
</body>
</html>



