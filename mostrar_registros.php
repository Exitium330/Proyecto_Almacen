<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registros</title>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
    if (localStorage.getItem("modoOscuro") === "enabled") {
        document.body.classList.add("dark-mode");
        console.log("Modo oscuro activado");
    } else {
        document.body.classList.remove("dark-mode");
        console.log("Modo oscuro desactivado");
    }
});
        </script>
<link rel="stylesheet" href="Css/mostrar_registro.css?v=<?php echo time(); ?>">

</script>
</head>
<body>  
</body>
    
</html>

<?php
include("conexion.php");

if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

$sql = "SELECT id_instructor, cedula, nombre, apellido, correo, telefono FROM instructores";
$resultado = $conn->query($sql);

echo "<h2>📋 Lista de Instructores</h2>";

if ($resultado->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr>
            <th>ID</th>
            <th>Cédula</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Correo</th>
            <th>Teléfono</th>

            <th>Acciones</th>
          </tr>";

    while ($fila = $resultado->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($fila['id_instructor'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($fila['cedula']) . "</td>
                <td>" . htmlspecialchars($fila['nombre']) . "</td>
                <td>" . htmlspecialchars($fila['apellido']) . "</td>
                <td>" . htmlspecialchars($fila['correo']) . "</td>
                <td>" . (!empty($fila['telefono']) ? htmlspecialchars($fila['telefono']) : "No registrado") . "</td>
                
                <td>
                    <form action='eliminar.php' method='POST'>
                        <input type='hidden' name='id_instructor' value='" . htmlspecialchars($fila['id_instructor']) . "'>
                        <button type='submit' onclick='return confirm(\"¿Seguro que quieres eliminar este instructor?\")'>
                            🗑 Eliminar
                        </button>
                    </form>
                </td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "⚠ No hay instructores registrados.";
}

$conn->close();
?>

