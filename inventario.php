<?php
session_start();

// Conexión a la base de datos
$conn = new mysqli("localhost", "root", "1027802491", "proyecto_almacen");
if ($conn->connect_error) {
    echo "<div class='notification error' id='notification'>Conexión fallida: " . $conn->connect_error . "</div>";
    exit;
}

// Verificar si las tablas existen
$required_tables = ['equipos', 'materiales', 'historial_cambios', 'almacenistas'];
foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        echo "<div class='notification error' id='notification'>Error: La tabla '$table' no existe en la base de datos 'proyecto_almacen'. Por favor, verifica las tablas.</div>";
        exit;
    }
}

// Simular un usuario logueado (reemplazar con tu sistema de autenticación)
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['id_usuario'] = 1; // Simulamos un usuario con ID 1
}
$id_usuario = (int)$_SESSION['id_usuario'];

// Configuración de paginación
$registros_por_pagina = 10;

// Paginación para equipos
$pagina_equipos = isset($_GET['pagina_equipos']) ? (int)$_GET['pagina_equipos'] : 1;
$inicio_equipos = ($pagina_equipos - 1) * $registros_por_pagina;
$total_equipos = $conn->query("SELECT COUNT(*) FROM equipos")->fetch_row()[0];
$total_paginas_equipos = ceil($total_equipos / $registros_por_pagina);

// Paginación para materiales
$pagina_materiales = isset($_GET['pagina_materiales']) ? (int)$_GET['pagina_materiales'] : 1;
$inicio_materiales = ($pagina_materiales - 1) * $registros_por_pagina;
$total_materiales = $conn->query("SELECT COUNT(*) FROM materiales")->fetch_row()[0];
$total_paginas_materiales = ceil($total_materiales / $registros_por_pagina);

// Manejar solicitud AJAX para actualizar equipo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_equipo'])) {
    $response = ['success' => false, 'message' => ''];

    $id_equipo = (int)$_POST['id_equipo'];
    $marca = trim($_POST['marca']);
    if ($marca === 'Otra') {
        $marca = trim($_POST['custom_marca']);
    }
    $serial = trim($_POST['serial']);
    $estado = $_POST['estado'];

    // Validar que el serial no exista en otro equipo
    $sql = "SELECT id_equipo FROM equipos WHERE serial = ? AND id_equipo != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $serial, $id_equipo);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $response['message'] = "Error: Ya existe otro equipo con el serial '$serial'.";
    } else {
        $sql = "UPDATE equipos SET marca = ?, serial = ?, estado = ? WHERE id_equipo = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $marca, $serial, $estado, $id_equipo);
        if ($stmt->execute()) {
            // Registrar en historial_cambios
            $detalles = json_encode([
                'marca' => $marca,
                'serial' => $serial,
                'estado' => $estado
            ]);
            $sql_historial = "INSERT INTO historial_cambios (id_usuario, tabla_afectada, accion, id_registro, detalles) VALUES (?, 'equipos', 'actualizar', ?, ?)";
            $stmt_historial = $conn->prepare($sql_historial);
            $stmt_historial->bind_param("iis", $id_usuario, $id_equipo, $detalles);
            $stmt_historial->execute();

            $response['success'] = true;
            $response['message'] = "Equipo actualizado exitosamente.";
        } else {
            $response['message'] = "Error al actualizar equipo: " . $stmt->error;
        }
    }

    // Enviar respuesta JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Manejar solicitud AJAX para eliminar equipo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_equipo'])) {
    $response = ['success' => false, 'message' => ''];

    $id_equipo = (int)$_POST['id_equipo'];

    $sql = "DELETE FROM equipos WHERE id_equipo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_equipo);
    if ($stmt->execute()) {
        // Registrar en historial_cambios
        $sql_historial = "INSERT INTO historial_cambios (id_usuario, tabla_afectada, accion, id_registro) VALUES (?, 'equipos', 'eliminar', ?)";
        $stmt_historial = $conn->prepare($sql_historial);
        $stmt_historial->bind_param("ii", $id_usuario, $id_equipo);
        $stmt_historial->execute();

        $response['success'] = true;
        $response['message'] = "Equipo eliminado exitosamente.";
    } else {
        $response['message'] = "Error al eliminar equipo: " . $stmt->error;
    }

    // Enviar respuesta JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Manejar solicitud AJAX para agregar equipo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_equipo_ajax'])) {
    $response = ['success' => false, 'message' => '', 'id_equipo' => null];

    $marca = trim($_POST['marca']);
    if ($marca === 'Otra') {
        $marca = trim($_POST['custom_marca']);
    }
    $serial = trim($_POST['serial']);
    $estado = 'disponible'; // Estado por defecto

    // Validar que el serial no exista
    $sql = "SELECT id_equipo FROM equipos WHERE serial = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $serial);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $response['message'] = "Error: Ya existe un equipo con el serial '$serial'.";
    } else {
        $sql = "INSERT INTO equipos (marca, serial, estado, fecha_creacion) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $marca, $serial, $estado);
        if ($stmt->execute()) {
            $id_equipo = $conn->insert_id;

            // Registrar en historial_cambios
            $detalles = json_encode([
                'marca' => $marca,
                'serial' => $serial,
                'estado' => $estado
            ]);
            $sql_historial = "INSERT INTO historial_cambios (id_usuario, tabla_afectada, accion, id_registro, detalles) VALUES (?, 'equipos', 'agregar', ?, ?)";
            $stmt_historial = $conn->prepare($sql_historial);
            $stmt_historial->bind_param("iis", $id_usuario, $id_equipo, $detalles);
            $stmt_historial->execute();

            $response['success'] = true;
            $response['message'] = "Equipo agregado exitosamente con estado 'disponible'.";
            $response['id_equipo'] = $id_equipo;
        } else {
            $response['message'] = "Error al agregar equipo: " . $stmt->error;
        }
    }

    // Enviar respuesta JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Manejar solicitud AJAX para agregar material
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_material_ajax'])) {
    $response = ['success' => false, 'message' => '', 'id_material' => null];

    $nombre = trim($_POST['nombre_material']);
    $tipo = $_POST['tipo'];
    $stock = (int)$_POST['stock'];

    // Validar que el stock no sea menor a 1
    if ($stock < 1) {
        $response['message'] = "Error: El stock debe ser mayor o igual a 1.";
    } else {
        $sql = "INSERT INTO materiales (nombre, tipo, stock, fecha_creacion) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $nombre, $tipo, $stock);
        if ($stmt->execute()) {
            $id_material = $conn->insert_id;

            // Registrar en historial_cambios
            $detalles = json_encode([
                'nombre' => $nombre,
                'tipo' => $tipo,
                'stock' => $stock
            ]);
            $sql_historial = "INSERT INTO historial_cambios (id_usuario, tabla_afectada, accion, id_registro, detalles) VALUES (?, 'materiales', 'agregar', ?, ?)";
            $stmt_historial = $conn->prepare($sql_historial);
            $stmt_historial->bind_param("iis", $id_usuario, $id_material, $detalles);
            $stmt_historial->execute();

            $response['success'] = true;
            $response['message'] = "Material agregado exitosamente.";
            $response['id_material'] = $id_material;
        } else {
            $response['message'] = "Error al agregar material: " . $stmt->error;
        }
    }

    // Enviar respuesta JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Manejar solicitud AJAX para actualizar material
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_material_ajax'])) {
    $response = ['success' => false, 'message' => ''];

    $id_material = (int)$_POST['id_material'];
    $nombre = trim($_POST['nombre']);
    $tipo = $_POST['tipo'];
    $stock = (int)$_POST['stock'];

    // Validar que el stock no sea negativo
    if ($stock < 0) {
        $response['message'] = "Error: El stock no puede ser negativo.";
    } else {
        $sql = "UPDATE materiales SET nombre = ?, tipo = ?, stock = ? WHERE id_material = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $nombre, $tipo, $stock, $id_material);
        if ($stmt->execute()) {
            // Registrar en historial_cambios
            $detalles = json_encode([
                'nombre' => $nombre,
                'tipo' => $tipo,
                'stock' => $stock
            ]);
            $sql_historial = "INSERT INTO historial_cambios (id_usuario, tabla_afectada, accion, id_registro, detalles) VALUES (?, 'materiales', 'actualizar', ?, ?)";
            $stmt_historial = $conn->prepare($sql_historial);
            $stmt_historial->bind_param("iis", $id_usuario, $id_material, $detalles);
            $stmt_historial->execute();

            $response['success'] = true;
            $response['message'] = "Material actualizado exitosamente.";
        } else {
            $response['message'] = "Error al actualizar material: " . $stmt->error;
        }
    }

    // Enviar respuesta JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Manejar solicitud AJAX para eliminar material
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_material_ajax'])) {
    $response = ['success' => false, 'message' => ''];

    $id_material = (int)$_POST['id_material'];

    $sql = "DELETE FROM materiales WHERE id_material = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_material);
    if ($stmt->execute()) {
        // Registrar en historial_cambios
        $sql_historial = "INSERT INTO historial_cambios (id_usuario, tabla_afectada, accion, id_registro) VALUES (?, 'materiales', 'eliminar', ?)";
        $stmt_historial = $conn->prepare($sql_historial);
        $stmt_historial->bind_param("ii", $id_usuario, $id_material);
        $stmt_historial->execute();

        $response['success'] = true;
        $response['message'] = "Material eliminado exitosamente.";
    } else {
        $response['message'] = "Error al eliminar material: " . $stmt->error;
    }

    // Enviar respuesta JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Manejar solicitud AJAX para obtener el historial actualizado
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['obtener_historial'])) {
    $response = ['success' => false, 'data' => [], 'message' => ''];

    $sql = "SELECT h.id_usuario, a.nombres, a.apellidos, h.tabla_afectada, h.accion, h.id_registro, h.fecha_accion, h.detalles 
            FROM historial_cambios h 
            LEFT JOIN almacenistas a ON h.id_usuario = a.id_almacenista 
            ORDER BY h.fecha_accion DESC";
    $result = $conn->query($sql);

    if ($result) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'id_usuario' => $row['id_usuario'],
                'nombres' => $row['nombres'],
                'apellidos' => $row['apellidos'],
                'tabla_afectada' => $row['tabla_afectada'],
                'accion' => $row['accion'],
                'id_registro' => $row['id_registro'],
                'fecha_accion' => $row['fecha_accion'],
                'detalles' => $row['detalles']
            ];
        }
        $response['success'] = true;
        $response['data'] = $data;
    } else {
        $response['message'] = "Error al cargar el historial: " . $conn->error;
    }

    // Enviar respuesta JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Exportar a CSV para equipos
if (isset($_GET['exportar_equipos_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="equipos_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Marca', 'Serial', 'Estado', 'Fecha Creacion'], ';');
    
    $sql = "SELECT id_equipo, marca, serial, estado, fecha_creacion FROM equipos ORDER BY id_equipo DESC";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id_equipo'],
            $row['marca'],
            $row['serial'],
            $row['estado'],
            $row['fecha_creacion']
        ], ';');
    }
    
    fclose($output);
    exit;
}

// Exportar a CSV para materiales
if (isset($_GET['exportar_materiales_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="materiales_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Nombre', 'Tipo', 'Stock', 'Fecha Creación'], ';');
    
    $sql = "SELECT id_material, nombre, tipo, stock, fecha_creacion FROM materiales ORDER BY id_material DESC";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id_material'],
            $row['nombre'],
            $row['tipo'],
            $row['stock'],
            $row['fecha_creacion']
        ], ';');
    }
    
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Inventario</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Css/inventario.css">
</head>
<body>
    <div class="container">
        <h2>Gestión de Inventario</h2>
        <button class="back-btn" onclick="window.location.href='index.php'">Volver al Inicio</button>

        <!-- Pestañas -->
        <div class="tabs">
            <div class="tab active" onclick="showTab('equipos')">Equipos</div>
            <div class="tab" onclick="showTab('materiales')">Materiales</div>
            <div class="tab" onclick="showTab('historial')">Historial de Cambios</div>
        </div>

        <!-- Contenido de la pestaña Equipos -->
        <div id="equipos" class="tab-content active">
            <!-- Formulario para agregar equipo -->
            <div class="form-group">
                <h3>Agregar Equipo</h3>
                <form id="addEquipoForm">
                    <label for="marca">Marca:</label>
                    <select name="marca" id="marca" required onchange="toggleCustomMarca()">
                        <option value="" disabled selected>Seleccione una marca</option>
                        <option value="HP">HP</option>
                        <option value="Dell">Dell</option>
                        <option value="Lenovo">Lenovo</option>
                        <option value="Asus">Asus</option>
                        <option value="Acer">Acer</option>
                        <option value="Apple">Apple</option>
                        <option value="Otra">Otra</option>
                    </select>
                    <input type="text" name="custom_marca" id="customMarca" placeholder="Ingrese la marca">

                    <label for="serial">Serial:</label>
                    <input type="text" name="serial" id="serial" required>

                    <button type="button" onclick="addEquipo()">Agregar Equipo</button>
                </form>
            </div>

            <!-- Filtro de búsqueda y fechas para equipos -->
            <div class="search-container">
                <label for="searchEquipos">Buscar Equipos:</label>
                <input type="text" id="searchEquipos" placeholder="Buscar por marca, serial o estado...">
                
                <label for="fechaInicioEquipos">Desde:</label>
                <input type="date" id="fechaInicioEquipos">
                
                <label for="fechaFinEquipos">Hasta:</label>
                <input type="date" id="fechaFinEquipos">
                
                <button class="export-btn" onclick="window.location.href='inventario.php?exportar_equipos_csv=1'">Exportar a CSV</button>
            </div>

            <!-- Mostrar equipos -->
            <h3>Equipos en Inventario</h3>
            <table id="equiposTable">
                <thead>
                    <tr>
                        <th onclick="sortTable('equiposTable', 0)">Marca <span class="sort-icon"></span></th>
                        <th onclick="sortTable('equiposTable', 1)">Serial <span class="sort-icon"></span></th>
                        <th onclick="sortTable('equiposTable', 2)">Estado <span class="sort-icon"></span></th>
                        <th onclick="sortTable('equiposTable', 3)">Fecha Creación <span class="sort-icon"></span></th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT id_equipo, marca, serial, estado, fecha_creacion 
                            FROM equipos 
                            ORDER BY id_equipo DESC 
                            LIMIT $inicio_equipos, $registros_por_pagina";
                    $result = $conn->query($sql);
                    if ($result) {
                        if ($result->num_rows == 0) {
                            echo "<tr><td colspan='5'>No hay equipos registrados.</td></tr>";
                        } else {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr data-id='{$row['id_equipo']}' data-fecha-creacion='{$row['fecha_creacion']}'>";
                                echo "<td class='marca'>" . ($row['marca'] ?? 'N/A') . "</td>";
                                echo "<td class='serial'>" . ($row['serial'] ?? 'N/A') . "</td>";
                                echo "<td class='estado'>" . ($row['estado'] ?? 'N/A') . "</td>";
                                echo "<td class='fecha-creacion'>" . ($row['fecha_creacion'] ?? 'N/A') . "</td>";
                                echo "<td class='action-buttons'>";
                                echo "<button class='edit-btn' onclick=\"openUpdateEquipoModal(
                                    '{$row['id_equipo']}', 
                                    '{$row['marca']}', 
                                    '{$row['serial']}', 
                                    '{$row['estado']}'
                                )\">Editar</button>";
                                echo "<button class='delete-btn' onclick=\"deleteEquipo('{$row['id_equipo']}')\">Eliminar</button>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        }
                    } else {
                        echo "<tr><td colspan='5' class='error'>Error al cargar los equipos: " . $conn->error . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <!-- Paginación para equipos -->
            <div class="pagination">
                <?php
                for ($i = 1; $i <= $total_paginas_equipos; $i++) {
                    $active = $i == $pagina_equipos ? 'active' : '';
                    echo "<a href='inventario.php?pagina_equipos=$i&pagina_materiales=$pagina_materiales' class='$active'>$i</a>";
                }
                ?>
            </div>
        </div>

        <!-- Contenido de la pestaña Materiales -->
        <div id="materiales" class="tab-content">
            <!-- Botón para agregar material -->
            <button onclick="openModal('materialModal')">Agregar Material</button>

            <!-- Filtro de búsqueda y fechas para materiales -->
            <div class="search-container">
                <label for="searchMateriales">Buscar Materiales:</label>
                <input type="text" id="searchMateriales" placeholder="Buscar por nombre, tipo o stock...">
                
                <label for="fechaInicioMateriales">Desde:</label>
                <input type="date" id="fechaInicioMateriales">
                
                <label for="fechaFinMateriales">Hasta:</label>
                <input type="date" id="fechaFinMateriales">
                
                <button class="export-btn" onclick="window.location.href='inventario.php?exportar_materiales_csv=1'">Exportar a CSV</button>
            </div>

            <!-- Mostrar materiales -->
            <h3>Materiales en Inventario</h3>
            <table id="materialesTable">
                <thead>
                    <tr>
                        <th onclick="sortTable('materialesTable', 0)">Nombre <span class="sort-icon"></span></th>
                        <th onclick="sortTable('materialesTable', 1)">Tipo <span class="sort-icon"></span></th>
                        <th onclick="sortTable('materialesTable', 2)">Stock <span class="sort-icon"></span></th>
                        <th onclick="sortTable('materialesTable', 3)">Fecha Creación <span class="sort-icon"></span></th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT id_material, nombre, tipo, stock, fecha_creacion 
                            FROM materiales 
                            ORDER BY id_material DESC 
                            LIMIT $inicio_materiales, $registros_por_pagina";
                    $result = $conn->query($sql);
                    if ($result) {
                        if ($result->num_rows == 0) {
                            echo "<tr><td colspan='5'>No hay materiales registrados.</td></tr>";
                        } else {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr data-id='{$row['id_material']}' data-fecha-creacion='{$row['fecha_creacion']}'>";
                                echo "<td class='nombre'>" . ($row['nombre'] ?? 'N/A') . "</td>";
                                echo "<td class='tipo'>" . ($row['tipo'] ?? 'N/A') . "</td>";
                                echo "<td class='stock'>" . ($row['stock'] ?? 'N/A') . "</td>";
                                echo "<td class='fecha-creacion'>" . ($row['fecha_creacion'] ?? 'N/A') . "</td>";
                                echo "<td class='action-buttons'>";
                                echo "<button class='edit-btn' onclick=\"openUpdateMaterialModal(
                                    '{$row['id_material']}', 
                                    '{$row['nombre']}', 
                                    '{$row['tipo']}', 
                                    '{$row['stock']}'
                                )\">Editar</button>";
                                echo "<button class='delete-btn' onclick=\"deleteMaterial('{$row['id_material']}')\">Eliminar</button>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        }
                    } else {
                        echo "<tr><td colspan='5' class='error'>Error al cargar los materiales: " . $conn->error . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <!-- Paginación para materiales -->
            <div class="pagination">
                <?php
                for ($i = 1; $i <= $total_paginas_materiales; $i++) {
                    $active = $i == $pagina_materiales ? 'active' : '';
                    echo "<a href='inventario.php?pagina_equipos=$pagina_equipos&pagina_materiales=$i' class='$active'>$i</a>";
                }
                ?>
            </div>
        </div>

        <!-- Contenido de la pestaña Historial -->
        <div id="historial" class="tab-content">
            <h3>Historial de Cambios</h3>
            <table id="historialTable">
                <thead>
                    <tr>
                        <th>Almacenista</th>
                        <th>Tabla</th>
                        <th>Acción</th>
                        <th>ID Registro</th>
                        <th>Fecha</th>
                        <th>Detalles</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT h.id_usuario, a.nombres, a.apellidos, h.tabla_afectada, h.accion, h.id_registro, h.fecha_accion, h.detalles 
                            FROM historial_cambios h 
                            LEFT JOIN almacenistas a ON h.id_usuario = a.id_almacenista 
                            ORDER BY h.fecha_accion DESC";
                    $result = $conn->query($sql);
                    if ($result) {
                        if ($result->num_rows == 0) {
                            echo "<tr><td colspan='6'>No hay cambios registrados.</td></tr>";
                        } else {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . ($row['nombres'] ? htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']) : 'N/A') . "</td>";
                                echo "<td>" . ($row['tabla_afectada'] ?? 'N/A') . "</td>";
                                echo "<td>" . ($row['accion'] ?? 'N/A') . "</td>";
                                echo "<td>" . ($row['id_registro'] ?? 'N/A') . "</td>";
                                echo "<td>" . ($row['fecha_accion'] ?? 'N/A') . "</td>";
                                echo "<td>" . ($row['detalles'] ? htmlspecialchars($row['detalles']) : 'N/A') . "</td>";
                                echo "</tr>";
                            }
                        }
                    } else {
                        echo "<tr><td colspan='6' class='error'>Error al cargar el historial: " . $conn->error . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Modal para agregar material -->
        <div id="materialModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('materialModal')">×</span>
                <h3>Agregar Material</h3>
                <form id="addMaterialForm">
                    <label for="nombre_material">Nombre:</label>
                    <input type="text" name="nombre_material" id="nombre_material" required>

                    <label for="tipo">Tipo:</label>
                    <select name="tipo" id="tipo" required>
                        <option value="consumible">Consumible</option>
                        <option value="no_consumible">No Consumible</option>
                    </select>

                    <label for="stock">Stock:</label>
                    <input type="number" name="stock" id="stock" min="1" required>

                    <button type="button" onclick="addMaterial()">Agregar Material</button>
                </form>
            </div>
        </div>

        <!-- Modal para actualizar equipo -->
        <div id="updateEquipoModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('updateEquipoModal')">×</span>
                <h3>Actualizar Equipo</h3>
                <form id="updateEquipoForm">
                    <input type="hidden" name="id_equipo" id="update_equipo_id">
                    <input type="hidden" name="actualizar_equipo" value="1">
                    <label for="update_marca">Marca:</label>
                    <select name="marca" id="update_marca" required onchange="toggleUpdateCustomMarca()">
                        <option value="" disabled>Seleccione una marca</option>
                        <option value="HP">HP</option>
                        <option value="Dell">Dell</option>
                        <option value="Lenovo">Lenovo</option>
                        <option value="Asus">Asus</option>
                        <option value="Acer">Acer</option>
                        <option value="Apple">Apple</option>
                        <option value="Otra">Otra</option>
                    </select>
                    <input type="text" name="custom_marca" id="update_customMarca" placeholder="Ingrese la marca">

                    <label for="update_serial">Serial:</label>
                    <input type="text" name="serial" id="update_serial" required>

                    <label for="update_estado">Estado:</label>
                    <select name="estado" id="update_estado" required>
                        <option value="disponible">Disponible</option>
                        <option value="prestado">Prestado</option>
                        <option value="deteriorado">Deteriorado</option>
                    </select>

                    <button type="button" onclick="updateEquipo()">Actualizar Equipo</button>
                </form>
            </div>
        </div>

        <!-- Modal para actualizar material -->
        <div id="updateMaterialModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('updateMaterialModal')">×</span>
                <h3>Actualizar Material</h3>
                <form id="updateMaterialForm">
                    <input type="hidden" name="id_material" id="update_material_id">
                    <input type="hidden" name="actualizar_material_ajax" value="1">
                    <label for="update_nombre_material">Nombre:</label>
                    <input type="text" name="nombre" id="update_nombre_material" required>

                    <label for="update_tipo">Tipo:</label>
                    <select name="tipo" id="update_tipo" required>
                        <option value="consumible">Consumible</option>
                        <option value="no_consumible">No Consumible</option>
                    </select>

                    <label for="update_stock">Stock:</label>
                    <input type="number" name="stock" id="update_stock" min="0" required>

                    <button type="button" onclick="updateMaterial()">Actualizar Material</button>
                </form>
            </div>
        </div>

        <?php
        // Cerrar la conexión al final
        $conn->close();
        ?>
    </div>

    <script src="scripts_inventario.js"></script>
    <script>
        // Verificar si scripts.js se cargó correctamente
        if (typeof showTab === 'undefined') {
            console.error('Error: scripts.js no se cargó correctamente. Asegúrate de que el archivo scripts.js esté en el directorio correcto (C:\\xampp\\htdocs\\proyecto_Almacen) y que el nombre del archivo sea correcto.');
            alert('Error: No se pudo cargar scripts.js. Por favor, verifica que el archivo esté en la carpeta correcta.');
        }
    </script>
</body>
</html>


