<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registros</title>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        // Activar/desactivar modo oscuro
        if (localStorage.getItem("modoOscuro") === "enabled") {
            document.body.classList.add("dark-mode");
            console.log("Modo oscuro activado");
        } else {
            document.body.classList.remove("dark-mode");
            console.log("Modo oscuro desactivado");
        }

        // Ocultar notificaciones después de 3 segundos
        const notifications = document.querySelectorAll('.notification');
        notifications.forEach(notification => {
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 500);
            }, 3000);
        });

        // Filtrar la tabla por nombre
        const searchInput = document.getElementById('searchInput');
        const table = document.getElementById('instructorsTable');
        const rows = table.getElementsByTagName('tr');

        searchInput.addEventListener('input', function () {
            const searchText = this.value.toLowerCase();

            // Empezamos desde i=1 para saltar la fila de encabezados
            for (let i = 1; i < rows.length; i++) {
                const nameCell = rows[i].getElementsByTagName('td')[2]; // Columna de "Nombre"
                if (nameCell) {
                    const name = nameCell.textContent.toLowerCase();
                    if (name.includes(searchText)) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        });
    });
    </script>
    <style>
        /* Estilo para las notificaciones */
        .notification {
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            transition: opacity 0.5s ease-in-out;
            position: fixed;
            top: 10px;
            right: 10px;
            max-width: 400px;
            text-align: center;
            z-index: 1000;
        }
        .notification.success {
            background-color: #4CAF50;
            color: white;
        }
        .notification.error {
            background-color: #f44336;
            color: white;
        }

        /* Estilo para la barra de búsqueda y el botón */
        .search-container {
            position: absolute;
            top: 10px;
            left: 10px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 10px;
            margin: 0;
            padding: 0;
        }
        .search-container input {
            padding: 8px;
            width: 200px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        .search-container input:focus {
            outline: none;
            border-color: #2a7a2a;
        }

        /* Estilo para el botón de retroceder */
        .back-btn {
            padding: 8px 16px;
            background-color: #666;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .back-btn:hover {
            background-color: #888;
        }

        /* Ajuste para el contenido principal */
        h2 {
            margin-top: 60px; /* Espacio para la notificación y la barra de búsqueda */
            text-align: center;
        }

        /* Ajuste para la tabla */
        table {
            margin-top: 20px;
        }
    </style>
    <link rel="stylesheet" href="Css/mostrar_registro.css?v=<?php echo time(); ?>">
</head>
<body>
<?php
include("conexion.php");

if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

// Procesar la actualización si se envía el formulario de edición
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar'])) {
    $id_instructor = $_POST['id_instructor'];
    $cedula = $_POST['cedula'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo = $_POST['correo'];
    $telefono = $_POST['telefono'];

    // Validar y sanitizar los datos
    $cedula = filter_var($cedula, FILTER_SANITIZE_STRING);
    $nombre = filter_var($nombre, FILTER_SANITIZE_STRING);
    $apellido = filter_var($apellido, FILTER_SANITIZE_STRING);
    $correo = filter_var($correo, FILTER_SANITIZE_EMAIL);
    $telefono = filter_var($telefono, FILTER_SANITIZE_STRING);

    // Preparar la consulta de actualización
    $sql_update = "UPDATE instructores SET cedula = ?, nombre = ?, apellido = ?, correo = ?, telefono = ? WHERE id_instructor = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("sssssi", $cedula, $nombre, $apellido, $correo, $telefono, $id_instructor);

    if ($stmt->execute()) {
        echo "<div class='notification success'>✅ Instructor actualizado correctamente.</div>";
    } else {
        echo "<div class='notification error'>❌ Error al actualizar el instructor: " . $conn->error . "</div>";
    }

    $stmt->close();
}

// Mostrar el formulario de edición si se selecciona un instructor
if (isset($_GET['editar'])) {
    $id_instructor = $_GET['editar'];
    $sql_edit = "SELECT id_instructor, cedula, nombre, apellido, correo, telefono FROM instructores WHERE id_instructor = ?";
    $stmt = $conn->prepare($sql_edit);
    $stmt->bind_param("i", $id_instructor);
    $stmt->execute();
    $resultado_edit = $stmt->get_result();

    if ($resultado_edit->num_rows > 0) {
        $instructor = $resultado_edit->fetch_assoc();
        ?>
        <h2>✏️ Editar Instructor</h2>
        <form action="mostrar_registros.php" method="POST">
            <input type="hidden" name="id_instructor" value="<?php echo htmlspecialchars($instructor['id_instructor']); ?>">
            <label for="cedula">Cédula:</label>
            <input type="text" name="cedula" value="<?php echo htmlspecialchars($instructor['cedula']); ?>" required><br>

            <label for="nombre">Nombre:</label>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($instructor['nombre']); ?>" required><br>

            <label for="apellido">Apellido:</label>
            <input type="text" name="apellido" value="<?php echo htmlspecialchars($instructor['apellido']); ?>" required><br>

            <label for="correo">Correo:</label>
            <input type="email" name="correo" value="<?php echo htmlspecialchars($instructor['correo']); ?>" required><br>

            <label for="telefono">Teléfono:</label>
            <input type="text" name="telefono" value="<?php echo htmlspecialchars($instructor['telefono'] ?? ''); ?>"><br>

            <button type="submit" name="actualizar">💾 Guardar Cambios</button>
            <a href="mostrar_registros.php">❌ Cancelar</a>
        </form>
        <?php
    } else {
        echo "<div class='notification error'>❌ Instructor no encontrado.</div>";
    }
    $stmt->close();
}

// Mostrar la lista de instructores
$sql = "SELECT id_instructor, cedula, nombre, apellido, correo, telefono FROM instructores";
$resultado = $conn->query($sql);

echo "<h2>📋 Lista de Instructores</h2>";

// Agregar la barra de búsqueda y el botón de retroceder
echo "<div class='search-container'>";
echo "<input type='text' id='searchInput' placeholder='Buscar por nombre...'>";
echo "<a href='index.php' class='back-btn'>⬅️ Volver al Menú</a>";
echo "</div>";

if ($resultado->num_rows > 0) {
    echo "<table border='1' id='instructorsTable'>";
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
                    <a href='mostrar_registros.php?editar=" . htmlspecialchars($fila['id_instructor']) . "'><button>✏️ Editar</button></a>
                    <form action='eliminar.php' method='POST' style='display:inline;'>
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
</body>
</html>

