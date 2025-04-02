<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

// Conexión a la base de datos (ajusta según tu configuración)
$host = 'localhost';
$dbname = 'proyecto_almacen';
$username = 'root';
$password = '1027802491';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Función para calcular el stock de un material
function calculateStock($pdo, $id_material) {
    // Sumar las cantidades de préstamos (salidas)
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(cantidad), 0) as total FROM prestamo_materiales WHERE id_material = ? AND estado = 'pendiente'");
    $stmt->execute([$id_material]);
    $total_prestamos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Sumar las cantidades de devoluciones (entradas)
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(pm.cantidad), 0) as total 
                           FROM devolucion_materiales dm 
                           JOIN prestamo_materiales pm ON dm.id_prestamo_material = pm.id_prestamo_material 
                           WHERE pm.id_material = ?");
    $stmt->execute([$id_material]);
    $total_devoluciones = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Stock = devoluciones - préstamos pendientes
    return $total_devoluciones - $total_prestamos;
}

// Manejar el formulario para agregar material
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_material'])) {
    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'];

    if (empty($nombre) || !in_array($tipo, ['consumible', 'no_consumible'])) {
        $error_material = "Por favor, completa todos los campos correctamente.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO materiales (nombre, tipo) VALUES (?, ?)");
            $stmt->execute([$nombre, $tipo]);
            $success_message = "Material agregado correctamente.";
        } catch (PDOException $e) {
            $error_material = "Error al agregar el material: " . $e->getMessage();
        }
    }
}

// Manejar el formulario para editar material
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_material'])) {
    $id_material = $_POST['id_material'];
    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'];

    if (empty($nombre) || !in_array($tipo, ['consumible', 'no_consumible'])) {
        $error_material = "Por favor, completa todos los campos correctamente.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE materiales SET nombre = ?, tipo = ? WHERE id_material = ?");
            $stmt->execute([$nombre, $tipo, $id_material]);
            $success_message = "Material actualizado correctamente.";
        } catch (PDOException $e) {
            $error_material = "Error al actualizar el material: " . $e->getMessage();
        }
    }
}

// Manejar la eliminación de material
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_material'])) {
    $id_material = $_POST['id_material'];
    try {
        $stmt = $pdo->prepare("DELETE FROM materiales WHERE id_material = ?");
        $stmt->execute([$id_material]);
        $success_message = "Material eliminado correctamente.";
    } catch (PDOException $e) {
        $error_material = "Error al eliminar el material: " . $e->getMessage();
    }
}

// Manejar el formulario para agregar equipo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_equipo'])) {
    $marca = $_POST['marca'];
    $serial = $_POST['serial'];
    $estado = $_POST['estado'];

    if (empty($marca) || empty($serial) || !in_array($estado, ['disponible', 'prestado', 'deteriorado'])) {
        $error_equipo = "Por favor, completa todos los campos correctamente.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO equipos (marca, serial, estado) VALUES (?, ?, ?)");
            $stmt->execute([$marca, $serial, $estado]);
            $success_message = "Equipo agregado correctamente.";
        } catch (PDOException $e) {
            $error_equipo = "Error al agregar el equipo: " . $e->getMessage();
        }
    }
}

// Manejar el formulario para editar equipo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_equipo'])) {
    $id_equipo = $_POST['id_equipo'];
    $marca = $_POST['marca'];
    $serial = $_POST['serial'];
    $estado = $_POST['estado'];

    if (empty($marca) || empty($serial) || !in_array($estado, ['disponible', 'prestado', 'deteriorado'])) {
        $error_equipo = "Por favor, completa todos los campos correctamente.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE equipos SET marca = ?, serial = ?, estado = ? WHERE id_equipo = ?");
            $stmt->execute([$marca, $serial, $estado, $id_equipo]);
            $success_message = "Equipo actualizado correctamente.";
        } catch (PDOException $e) {
            $error_equipo = "Error al actualizar el equipo: " . $e->getMessage();
        }
    }
}

// Manejar la eliminación de equipo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_equipo'])) {
    $id_equipo = $_POST['id_equipo'];
    try {
        $stmt = $pdo->prepare("DELETE FROM equipos WHERE id_equipo = ?");
        $stmt->execute([$id_equipo]);
        $success_message = "Equipo eliminado correctamente.";
    } catch (PDOException $e) {
        $error_equipo = "Error al eliminar el equipo: " . $e->getMessage();
    }
}

// Obtener lista de materiales
$materials = $pdo->query("SELECT * FROM materiales")->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de equipos
$equipos = $pdo->query("SELECT * FROM equipos")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario</title>
    <link rel="stylesheet" href="Css/inventario.css?v=<?php echo time(); ?>">
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
</head>
<body>
    <div class="inventory-container">
        <h1>Inventario</h1>

        <!-- Formulario para agregar material -->
        <h2>Agregar Material</h2>
        <?php if (isset($error_material)): ?>
            <p class="error-message"><?php echo $error_material; ?></p>
        <?php endif; ?>
        <form method="POST" class="inventory-form">
            <input type="hidden" name="add_material" value="1">
            <label for="nombre">Nombre:</label>
            <input type="text" name="nombre" required><br>

            <label for="tipo">Tipo:</label>
            <select name="tipo" required>
                <option value="consumible">Consumible</option>
                <option value="no_consumible">No consumible</option>
            </select><br>

            <button type="submit">Agregar Material</button>
        </form>

        <!-- Formulario para agregar equipo -->
        <h2>Agregar Equipo</h2>
        <?php if (isset($error_equipo)): ?>
            <p class="error-message"><?php echo $error_equipo; ?></p>
        <?php endif; ?>
        <form method="POST" class="inventory-form">
            <input type="hidden" name="add_equipo" value="1">
            <label for="marca">Marca:</label>
            <input type="text" name="marca" required><br>

            <label for="serial">Serial:</label>
            <input type="text" name="serial" required><br>

            <label for="estado">Estado:</label>
            <select name="estado" required>
                <option value="disponible">Disponible</option>
                <option value="prestado">Prestado</option>
                <option value="deteriorado">Deteriorado</option>
            </select><br>

            <button type="submit">Agregar Equipo</button>
        </form>

        <!-- Lista de materiales -->
        <h2>Materiales en inventario</h2>
        <table class="inventory-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Stock</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($materials as $material): ?>
                    <tr>
                        <td><?php echo $material['id_material']; ?></td>
                        <td><?php echo htmlspecialchars($material['nombre']); ?></td>
                        <td><?php echo $material['tipo'] === 'consumible' ? 'Consumible' : 'No consumible'; ?></td>
                        <td><?php echo calculateStock($pdo, $material['id_material']); ?></td>
                        <td>
                            <button class="edit-btn" onclick="showEditMaterialForm(<?php echo $material['id_material']; ?>, '<?php echo htmlspecialchars($material['nombre']); ?>', '<?php echo $material['tipo']; ?>')">Editar</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este material?');">
                                <input type="hidden" name="delete_material" value="1">
                                <input type="hidden" name="id_material" value="<?php echo $material['id_material']; ?>">
                                <button type="submit" class="delete-btn">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Formulario para editar material (oculto por defecto) -->
        <div id="edit-material-form" class="edit-form" style="display:none;">
            <h2>Editar Material</h2>
            <form method="POST" class="inventory-form">
                <input type="hidden" name="edit_material" value="1">
                <input type="hidden" name="id_material" id="edit-material-id">
                <label for="nombre">Nombre:</label>
                <input type="text" name="nombre" id="edit-material-nombre" required><br>

                <label for="tipo">Tipo:</label>
                <select name="tipo" id="edit-material-tipo" required>
                    <option value="consumible">Consumible</option>
                    <option value="no_consumible">No consumible</option>
                </select><br>

                <button type="submit">Guardar Cambios</button>
                <button type="button" onclick="hideEditMaterialForm()">Cancelar</button>
            </form>
        </div>

        <!-- Lista de equipos -->
        <h2>Equipos en inventario</h2>
        <table class="inventory-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Marca</th>
                    <th>Serial</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($equipos as $equipo): ?>
                    <tr>
                        <td><?php echo $equipo['id_equipo']; ?></td>
                        <td><?php echo htmlspecialchars($equipo['marca']); ?></td>
                        <td><?php echo htmlspecialchars($equipo['serial']); ?></td>
                        <td><?php echo $equipo['estado']; ?></td>
                        <td>
                            <button class="edit-btn" onclick="showEditEquipoForm(<?php echo $equipo['id_equipo']; ?>, '<?php echo htmlspecialchars($equipo['marca']); ?>', '<?php echo htmlspecialchars($equipo['serial']); ?>', '<?php echo $equipo['estado']; ?>')">Editar</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este equipo?');">
                                <input type="hidden" name="delete_equipo" value="1">
                                <input type="hidden" name="id_equipo" value="<?php echo $equipo['id_equipo']; ?>">
                                <button type="submit" class="delete-btn">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Formulario para editar equipo (oculto por defecto) -->
        <div id="edit-equipo-form" class="edit-form" style="display:none;">
            <h2>Editar Equipo</h2>
            <form method="POST" class="inventory-form">
                <input type="hidden" name="edit_equipo" value="1">
                <input type="hidden" name="id_equipo" id="edit-equipo-id">
                <label for="marca">Marca:</label>
                <input type="text" name="marca" id="edit-equipo-marca" required><br>

                <label for="serial">Serial:</label>
                <input type="text" name="serial" id="edit-equipo-serial" required><br>

                <label for="estado">Estado:</label>
                <select name="estado" id="edit-equipo-estado" required>
                    <option value="disponible">Disponible</option>
                    <option value="prestado">Prestado</option>
                    <option value="deteriorado">Deteriorado</option>
                </select><br>

                <button type="submit">Guardar Cambios</button>
                <button type="button" onclick="hideEditEquipoForm()">Cancelar</button>
            </form>
        </div>

        <!-- Contenedor para notificaciones -->
        <div id="notification" class="notification" style="display: none;">
            <span id="notification-message"></span>
        </div>
    </div>

    <script>
        // Mostrar notificación si hay un mensaje de éxito
        <?php if (isset($success_message)): ?>
            showNotification("<?php echo $success_message; ?>");
        <?php endif; ?>

        function showNotification(message) {
            const notification = document.getElementById("notification");
            const notificationMessage = document.getElementById("notification-message");
            notificationMessage.textContent = message;
            notification.style.display = "block";
            setTimeout(() => {
                notification.style.display = "none";
            }, 3000);
        }

        // Funciones para mostrar/ocultar el formulario de edición de material
        function showEditMaterialForm(id, nombre, tipo) {
            document.getElementById("edit-material-id").value = id;
            document.getElementById("edit-material-nombre").value = nombre;
            document.getElementById("edit-material-tipo").value = tipo;
            document.getElementById("edit-material-form").style.display = "block";
        }

        function hideEditMaterialForm() {
            document.getElementById("edit-material-form").style.display = "none";
        }

        // Funciones para mostrar/ocultar el formulario de edición de equipo
        function showEditEquipoForm(id, marca, serial, estado) {
            document.getElementById("edit-equipo-id").value = id;
            document.getElementById("edit-equipo-marca").value = marca;
            document.getElementById("edit-equipo-serial").value = serial;
            document.getElementById("edit-equipo-estado").value = estado;
            document.getElementById("edit-equipo-form").style.display = "block";
        }

        function hideEditEquipoForm() {
            document.getElementById("edit-equipo-form").style.display = "none";
        }
    </script>
</body>
</html>


