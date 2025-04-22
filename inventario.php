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
    // Usar punto y coma como separador
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
        ], ';'); // Usar punto y coma como separador
    }
    
    fclose($output);
    exit;
}

// Exportar a CSV para materiales
if (isset($_GET['exportar_materiales_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="materiales_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    // Usar punto y coma como separador
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
        ], ';'); // Usar punto y coma como separador
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f4f4;
            color: #333333;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #333333;
            margin-bottom: 20px;
            text-align: center;
        }
        h3 {
            color: #333333;
            margin-bottom: 15px;
        }
        .tabs {
            display: flex;
            border-bottom: 2px solid #d3d3d3;
            margin-bottom: 20px;
        }
        .tab {
            flex: 1;
            padding: 10px 0;
            text-align: center;
            background: #f4f4f4;
            color: #333333;
            cursor: pointer;
            transition: background 0.3s;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        .tab:hover {
            background: #e0e0e0;
        }
        .tab.active {
            background: #28a745;
            color: #ffffff;
            font-weight: 500;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .form-group {
            margin-bottom: 20px;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #d3d3d3;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        input, select {
            padding: 8px;
            width: 100%;
            max-width: 300px;
            border: 1px solid #d3d3d3;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        button {
            padding: 8px 16px;
            background-color: #28a745;
            color: #ffffff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background-color: #218838;
        }
        .export-btn {
            background-color: #007bff;
            margin-bottom: 10px;
        }
        .export-btn:hover {
            background-color: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #ffffff;
            border: 1px solid #d3d3d3;
        }
        th, td {
            padding: 10px;
            border: 1px solid #d3d3d3;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
            color: #333333;
            font-weight: 500;
            cursor: pointer;
            position: relative;
            transition: background-color 0.3s;
        }
        th:hover {
            background-color: #e0e0e0;
        }
        th .sort-icon {
            display: inline-block;
            margin-left: 5px;
            font-size: 12px;
        }
        th.asc .sort-icon::after {
            content: '↑';
        }
        th.desc .sort-icon::after {
            content: '↓';
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .modal-content h3 {
            margin-top: 0;
            color: #333333;
        }
        .close {
            float: right;
            cursor: pointer;
            font-size: 24px;
            color: #333333;
        }
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            border-radius: 4px;
            color: #ffffff;
            z-index: 1001;
            opacity: 1;
            transition: opacity 0.5s ease-out;
        }
        .notification.success {
            background-color: #28a745;
        }
        .notification.error {
            background-color: #dc3545;
        }
        .notification.fade-out {
            opacity: 0;
        }
        .action-buttons button {
            padding: 5px 10px;
            margin: 0 5px;
            border-radius: 4px;
        }
        .action-buttons .edit-btn {
            background-color: #28a745;
        }
        .action-buttons .edit-btn:hover {
            background-color: #218838;
        }
        .action-buttons .delete-btn {
            background-color: #dc3545;
        }
        .action-buttons .delete-btn:hover {
            background-color: #c82333;
        }
        #customMarca, #update_customMarca {
            display: none;
        }
        .search-container {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .search-container label {
            font-weight: 500;
            margin-bottom: 0;
        }
        .search-container input {
            padding: 8px;
            border: 1px solid #d3d3d3;
            border-radius: 4px;
            width: 250px;
            transition: border-color 0.3s;
        }
        .search-container input:focus {
            border-color: #28a745;
            outline: none;
        }
        .pagination {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .pagination a {
            padding: 8px 12px;
            border: 1px solid #d3d3d3;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
            transition: background-color 0.3s;
        }
        .pagination a:hover {
            background-color: #e0e0e0;
        }
        .pagination a.active {
            background-color: #28a745;
            color: #ffffff;
            border-color: #28a745;
        }
        .highlight {
            background-color: #e6ffe6;
            animation: fadeOutHighlight 2s forwards;
        }
        @keyframes fadeOutHighlight {
            from {
                background-color: #e6ffe6;
            }
            to {
                background-color: transparent;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Gestión de Inventario</h2>

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

    <script>
        // Manejo de pestañas
        function showTab(tabId) {
            const tabs = document.querySelectorAll('.tab');
            const contents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => tab.classList.remove('active'));
            contents.forEach(content => content.classList.remove('active'));

            document.querySelector(`.tab[onclick="showTab('${tabId}')"]`).classList.add('active');
            document.getElementById(tabId).classList.add('active');

            // Si se muestra la pestaña de historial, iniciar la actualización en tiempo real
            if (tabId === 'historial') {
                startHistorialUpdates();
            } else {
                stopHistorialUpdates();
            }
        }

        // Manejo de modales
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function openUpdateEquipoModal(id, marca, serial, estado) {
            document.getElementById('update_equipo_id').value = id;
            const marcaSelect = document.getElementById('update_marca');
            const customMarcaInput = document.getElementById('update_customMarca');
            if (['HP', 'Dell', 'Lenovo', 'Asus', 'Acer', 'Apple'].includes(marca)) {
                marcaSelect.value = marca;
                customMarcaInput.style.display = 'none';
            } else {
                marcaSelect.value = 'Otra';
                customMarcaInput.style.display = 'block';
                customMarcaInput.value = marca;
            }
            document.getElementById('update_serial').value = serial;
            document.getElementById('update_estado').value = estado;
            openModal('updateEquipoModal');
        }

        function openUpdateMaterialModal(id, nombre, tipo, stock) {
            document.getElementById('update_material_id').value = id;
            document.getElementById('update_nombre_material').value = nombre;
            document.getElementById('update_tipo').value = tipo;
            document.getElementById('update_stock').value = stock;
            openModal('updateMaterialModal');
        }

        window.onclick = function(event) {
            const modals = ['materialModal', 'updateEquipoModal', 'updateMaterialModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Manejo de notificaciones
        document.addEventListener('DOMContentLoaded', function() {
            const notification = document.getElementById('notification');
            if (notification) {
                setTimeout(() => {
                    notification.classList.add('fade-out');
                }, 3000);
            }
        });

        // Mostrar/esconder campo de marca personalizada
        function toggleCustomMarca() {
            const marcaSelect = document.getElementById('marca');
            const customMarcaInput = document.getElementById('customMarca');
            customMarcaInput.style.display = marcaSelect.value === 'Otra' ? 'block' : 'none';
            if (marcaSelect.value !== 'Otra') {
                customMarcaInput.value = '';
            }
        }

        function toggleUpdateCustomMarca() {
            const marcaSelect = document.getElementById('update_marca');
            const customMarcaInput = document.getElementById('update_customMarca');
            customMarcaInput.style.display = marcaSelect.value === 'Otra' ? 'block' : 'none';
            if (marcaSelect.value !== 'Otra') {
                customMarcaInput.value = '';
            }
        }

        // Agregar equipo mediante AJAX
        function addEquipo() {
            const form = document.getElementById('addEquipoForm');
            const formData = new FormData(form);
            formData.append('agregar_equipo_ajax', '1');
            let marca = formData.get('marca');
            if (marca === 'Otra') {
                marca = formData.get('custom_marca');
            }
            formData.set('marca', marca);

            fetch('inventario.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Mostrar notificación
                const notification = document.createElement('div');
                notification.className = `notification ${data.success ? 'success' : 'error'}`;
                notification.id = 'notification';
                notification.textContent = data.message;
                document.body.appendChild(notification);
                setTimeout(() => {
                    notification.classList.add('fade-out');
                }, 3000);

                // Si la adición fue exitosa, agregar la fila a la tabla
                if (data.success) {
                    const tbody = document.querySelector('#equiposTable tbody');
                    const noRecordsRow = tbody.querySelector('tr td[colspan="5"]');
                    if (noRecordsRow) {
                        noRecordsRow.parentElement.remove();
                    }

                    const newRow = document.createElement('tr');
                    newRow.setAttribute('data-id', data.id_equipo);
                    newRow.setAttribute('data-fecha-creacion', new Date().toISOString().slice(0, 19).replace('T', ' '));
                    newRow.classList.add('highlight');
                    newRow.innerHTML = `
                        <td class="marca">${marca}</td>
                        <td class="serial">${formData.get('serial')}</td>
                        <td class="estado">disponible</td>
                        <td class="fecha-creacion">${new Date().toLocaleString()}</td>
                        <td class="action-buttons">
                            <button class="edit-btn" onclick="openUpdateEquipoModal('${data.id_equipo}', '${marca}', '${formData.get('serial')}', 'disponible')">Editar</button>
                            <button class="delete-btn" onclick="deleteEquipo('${data.id_equipo}')">Eliminar</button>
                        </td>
                    `;
                    tbody.prepend(newRow); // Añadir al inicio para reflejar el orden

                    // Reordenar la tabla por id_equipo (descendente)
                    sortTable('equiposTable', 0, true);

                    // Limpiar el formulario
                    form.reset();
                    toggleCustomMarca();

                    // Actualizar el historial inmediatamente
                    updateHistorialTable();

                    // Actualizar la paginación (recargar la página para reflejar la nueva cantidad)
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            })
            .catch(error => {
                const notification = document.createElement('div');
                notification.className = 'notification error';
                notification.id = 'notification';
                notification.textContent = 'Error al agregar equipo: ' + error;
                document.body.appendChild(notification);
                setTimeout(() => {
                    notification.classList.add('fade-out');
                }, 3000);
            });
        }

        // Actualizar equipo mediante AJAX
        function updateEquipo() {
            const form = document.getElementById('updateEquipoForm');
            const formData = new FormData(form);
            let marca = formData.get('marca');
            if (marca === 'Otra') {
                marca = formData.get('custom_marca');
            }
            formData.set('marca', marca);

            fetch('inventario.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Mostrar notificación
                const notification = document.createElement('div');
                notification.className = `notification ${data.success ? 'success' : 'error'}`;
                notification.id = 'notification';
                notification.textContent = data.message;
                document.body.appendChild(notification);
                setTimeout(() => {
                    notification.classList.add('fade-out');
                }, 3000);

                // Si la actualización fue exitosa, actualizar la tabla dinámicamente
                if (data.success) {
                    const id = formData.get('id_equipo');
                    const serial = formData.get('serial');
                    const estado = formData.get('estado');
                    const row = document.querySelector(`#equiposTable tr[data-id='${id}']`);
                    if (row) {
                        row.querySelector('.marca').textContent = marca;
                        row.querySelector('.serial').textContent = serial;
                        row.querySelector('.estado').textContent = estado;
                        row.classList.add('highlight');
                    }
                    closeModal('updateEquipoModal');

                    // Reordenar la tabla por id_equipo (descendente)
                    sortTable('equiposTable', 0, true);

                    // Actualizar el historial inmediatamente
                    updateHistorialTable();
                }
            })
            .catch(error => {
                const notification = document.createElement('div');
                notification.className = 'notification error';
                notification.id = 'notification';
                notification.textContent = 'Error al actualizar equipo: ' + error;
                document.body.appendChild(notification);
                setTimeout(() => {
                    notification.classList.add('fade-out');
                }, 3000);
            });
        }

        // Eliminar equipo mediante AJAX
        function deleteEquipo(id) {
            if (!confirm('¿Estás seguro de que deseas eliminar este equipo?')) {
                return;
            }

            const formData = new FormData();
            formData.append('eliminar_equipo', '1');
            formData.append('id_equipo', id);

            fetch('inventario.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Mostrar notificación
                const notification = document.createElement('div');
                notification.className = `notification ${data.success ? 'success' : 'error'}`;
                notification.id = 'notification';
                notification.textContent = data.message;
                document.body.appendChild(notification);
                setTimeout(() => {
                    notification.classList.add('fade-out');
                }, 3000);

                // Si la eliminación fue exitosa, eliminar la fila de la tabla
                if (data.success) {
                    const row = document.querySelector(`#equiposTable tr[data-id='${id}']`);
                    if (row) {
                        row.remove();
                    }
                    // Verificar si la tabla está vacía después de eliminar
                    const tbody = document.querySelector('#equiposTable tbody');
                    if (tbody.children.length === 0) {
                        tbody.innerHTML = "<tr><td colspan='5'>No hay equipos registrados.</td></tr>";
                    } else {
                        // Reordenar la tabla por id_equipo (descendente)
                        sortTable('equiposTable', 0, true);
                    }

                    // Actualizar el historial inmediatamente
                    updateHistorialTable();

                    // Actualizar la paginación (recargar la página para reflejar la nueva cantidad)
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            })
            .catch(error => {
                const notification = document.createElement('div');
                notification.className = 'notification error';
                notification.id = 'notification';
                notification.textContent = 'Error al eliminar equipo: ' + error;
                document.body.appendChild(notification);
                setTimeout(() => {
                    notification.classList.add('fade-out');
                }, 3000);
            });
        }

        // Agregar material mediante AJAX
        function addMaterial() {
            const form = document.getElementById('addMaterialForm');
            const formData = new FormData(form);
            formData.append('agregar_material_ajax', '1');

            fetch('inventario.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Mostrar notificación
                const notification = document.createElement('div');
                notification.className = `notification ${data.success ? 'success' : 'error'}`;
                notification.id = 'notification';
                notification.textContent = data.message;
                document.body.appendChild(notification);
                setTimeout(() => {
                    notification.classList.add('fade-out');
                }, 3000);

                // Si la adición fue exitosa, agregar la fila a la tabla
                if (data.success) {
                    const tbody = document.querySelector('#materialesTable tbody');
                    const noRecordsRow = tbody.querySelector('tr td[colspan="5"]');
                    if (noRecordsRow) {
                        noRecordsRow.parentElement.remove();
                    }

                    const newRow = document.createElement('tr');
                    newRow.setAttribute('data-id', data.id_material);
                    newRow.setAttribute('data-fecha-creacion', new Date().toISOString().slice(0, 19).replace('T', ' '));
                    newRow.classList.add('highlight');
                    newRow.innerHTML = `
                        <td class="nombre">${formData.get('nombre_material')}</td>
                        <td class="tipo">${formData.get('tipo')}</td>
                        <td class="stock">${formData.get('stock')}</td>
                        <td class="fecha-creacion">${new Date().toLocaleString()}</td>
                        <td class="action-buttons">
                            <button class="edit-btn" onclick="openUpdateMaterialModal('${data.id_material}', '${formData.get('nombre_material')}', '${formData.get('tipo')}', '${formData.get('stock')}')">Editar</button>
                            <button class="delete-btn" onclick="deleteMaterial('${data.id_material}')">Eliminar</button>
                        </td>
                    `;
                    tbody.prepend(newRow);

                    // Reordenar la tabla por id_material (descendente)
                    sortTable('materialesTable', 0, true);

                    // Limpiar el formulario y cerrar el modal
                    form.reset();
                    closeModal('materialModal');

                    // Actualizar el historial inmediatamente
                    updateHistorialTable();

                    // Actualizar la paginación (recargar la página para reflejar la nueva cantidad)
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            })
            .catch(error => {
                const notification = document.createElement('div');
                notification.className = 'notification error';
                notification.id = 'notification';
                notification.textContent = 'Error al agregar material: ' + error;
                document.body.appendChild(notification);
                setTimeout(() => {
                    notification.classList.add('fade-out');
                }, 3000);
            });
        }

        // Actualizar material mediante AJAX
        function updateMaterial() {
            const form = document.getElementById('updateMaterialForm');
            const formData = new FormData(form);

            fetch('inventario.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Mostrar notificación
                const notification = document.createElement('div');
                notification.className = `notification ${data.success ? 'success' : 'error'}`;
                notification.id = 'notification';
                notification.textContent = data.message;
                document.body.appendChild(notification);
                setTimeout(() => {
                    notification.classList.add('fade-out');
                }, 3000);

                // Si la actualización fue exitosa, actualizar la tabla dinámicamente
                if (data.success) {
                    const id = formData.get('id_material');
                    const nombre = formData.get('nombre');
                    const tipo = formData.get('tipo');
                    const stock = formData.get('stock');
                    const row = document.querySelector(`#materialesTable tr[data-id='${id}']`);
                    if (row) {
                        row.querySelector('.nombre').textContent = nombre;
                        row.querySelector('.tipo').textContent = tipo;
                        row.querySelector('.stock').textContent = stock;
                        row.classList.add('highlight');
                    }
                    closeModal('updateMaterialModal');

                    // Reordenar la tabla por id_material (descendente)
                    sortTable('materialesTable', 0, true);

                    // Actualizar el historial inmediatamente
                    updateHistorialTable();
                }
            })
            .catch(error => {
                const notification = document.createElement('div');
                notification.className = 'notification error';
                notification.id = 'notification';
                notification.textContent = 'Error al actualizar material: ' + error;
                document.body.appendChild(notification);
                setTimeout(() => {
                    notification.classList.add('fade-out');
                }, 3000);
            });
        }

        // Eliminar material mediante AJAX
        function deleteMaterial(id) {
            if (!confirm('¿Estás seguro de que deseas eliminar este material?')) {
                return;
            }

            const formData = new FormData();
            formData.append('eliminar_material_ajax', '1');
            formData.append('id_material', id);

            fetch('inventario.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Mostrar notificación
                const notification = document.createElement('div');
                notification.className = `notification ${data.success ? 'success' : 'error'}`;
                notification.id = 'notification';
                notification.textContent = data.message;
                document.body.appendChild(notification);
                setTimeout(() => {
                    notification.classList.add('fade-out');
                }, 3000);

                // Si la eliminación fue exitosa, eliminar la fila de la tabla
                if (data.success) {
                    const row = document.querySelector(`#materialesTable tr[data-id='${id}']`);
                    if (row) {
                        row.remove();
                    }
                    // Verificar si la tabla está vacía después de eliminar
                    const tbody = document.querySelector('#materialesTable tbody');
                    if (tbody.children.length === 0) {
                        tbody.innerHTML = "<tr><td colspan='5'>No hay materiales registrados.</td></tr>";
                    } else {
                        // Reordenar la tabla por id_material (descendente)
                        sortTable('materialesTable', 0, true);
                    }

                    // Actualizar el historial inmediatamente
                    updateHistorialTable();

                    // Actualizar la paginación (recargar la página para reflejar la nueva cantidad)
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            })
            .catch(error => {
                const notification = document.createElement('div');
                notification.className = 'notification error';
                notification.id = 'notification';
                notification.textContent = 'Error al eliminar material: ' + error;
                document.body.appendChild(notification);
                setTimeout(() => {
                    notification.classList.add('fade-out');
                }, 3000);
            });
        }

        // Asegurarse de que la interfaz se muestre al recargar
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.container');
            if (container) {
                container.style.display = 'block';
            }

            // Configurar búsqueda para equipos
            const searchEquipos = document.getElementById('searchEquipos');
            searchEquipos.addEventListener('input', function() {
                filterTable('equiposTable', this.value);
            });

            // Configurar búsqueda para materiales
            const searchMateriales = document.getElementById('searchMateriales');
            searchMateriales.addEventListener('input', function() {
                filterTable('materialesTable', this.value);
            });

            // Configurar filtro por fechas para equipos
            const fechaInicioEquipos = document.getElementById('fechaInicioEquipos');
            const fechaFinEquipos = document.getElementById('fechaFinEquipos');
            fechaInicioEquipos.addEventListener('change', function() {
                filterTableByDate('equiposTable', fechaInicioEquipos.value, fechaFinEquipos.value);
            });
            fechaFinEquipos.addEventListener('change', function() {
                filterTableByDate('equiposTable', fechaInicioEquipos.value, fechaFinEquipos.value);
            });

            // Configurar filtro por fechas para materiales
            const fechaInicioMateriales = document.getElementById('fechaInicioMateriales');
            const fechaFinMateriales = document.getElementById('fechaFinMateriales');
            fechaInicioMateriales.addEventListener('change', function() {
                filterTableByDate('materialesTable', fechaInicioMateriales.value, fechaFinMateriales.value);
            });
            fechaFinMateriales.addEventListener('change', function() {
                filterTableByDate('materialesTable', fechaInicioMateriales.value, fechaFinMateriales.value);
            });

            // Ordenar las tablas al cargar la página
            sortTable('equiposTable', 0, true);
            sortTable('materialesTable', 0, true);
        });

        // Función para filtrar la tabla por texto
        function filterTable(tableId, searchText) {
            const table = document.getElementById(tableId);
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            searchText = searchText.toLowerCase();

            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let match = false;

                // Buscar en todas las columnas excepto la última (Acción) y la de fecha
                for (let j = 0; j < cells.length - 2; j++) {
                    const cellText = cells[j].textContent.toLowerCase();
                    if (cellText.includes(searchText)) {
                        match = true;
                        break;
                    }
                }

                rows[i].style.display = match ? '' : 'none';
            }

            // Reaplicar el filtro por fechas si está activo
            if (tableId === 'equiposTable') {
                const fechaInicio = document.getElementById('fechaInicioEquipos').value;
                const fechaFin = document.getElementById('fechaFinEquipos').value;
                if (fechaInicio || fechaFin) {
                    filterTableByDate(tableId, fechaInicio, fechaFin);
                }
            } else if (tableId === 'materialesTable') {
                const fechaInicio = document.getElementById('fechaInicioMateriales').value;
                const fechaFin = document.getElementById('fechaFinMateriales').value;
                if (fechaInicio || fechaFin) {
                    filterTableByDate(tableId, fechaInicio, fechaFin);
                }
            }
        }

        // Función para filtrar la tabla por rango de fechas
        function filterTableByDate(tableId, fechaInicio, fechaFin) {
            const table = document.getElementById(tableId);
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            const startDate = fechaInicio ? new Date(fechaInicio) : null;
            const endDate = fechaFin ? new Date(fechaFin) : null;
            if (endDate) {
                endDate.setHours(23, 59, 59, 999); // Incluir todo el día
            }

            for (let i = 0; i < rows.length; i++) {
                const fechaCreacionStr = rows[i].getAttribute('data-fecha-creacion');
                if (!fechaCreacionStr) continue;

                const fechaCreacion = new Date(fechaCreacionStr);
                let inDateRange = true;

                if (startDate && fechaCreacion < startDate) {
                    inDateRange = false;
                }
                if (endDate && fechaCreacion > endDate) {
                    inDateRange = false;
                }

                if (!inDateRange) {
                    rows[i].style.display = 'none';
                } else {
                    // Solo mostrar si también coincide con el filtro de texto
                    const searchText = tableId === 'equiposTable' 
                        ? document.getElementById('searchEquipos').value.toLowerCase()
                        : document.getElementById('searchMateriales').value.toLowerCase();
                    if (searchText) {
                        const cells = rows[i].getElementsByTagName('td');
                        let match = false;
                        for (let j = 0; j < cells.length - 2; j++) {
                            const cellText = cells[j].textContent.toLowerCase();
                            if (cellText.includes(searchText)) {
                                match = true;
                                break;
                            }
                        }
                        rows[i].style.display = match ? '' : 'none';
                    } else {
                        rows[i].style.display = '';
                    }
                }
            }
        }

        // Función para ordenar la tabla
        function sortTable(tableId, colIndex, forceDefaultSort = false) {
            const table = document.getElementById(tableId);
            const thead = table.querySelector('thead');
            const th = thead.getElementsByTagName('th')[colIndex];
            const rows = Array.from(table.getElementsByTagName('tbody')[0].getElementsByTagName('tr'));
            let isAsc;

            if (forceDefaultSort) {
                isAsc = false;
            } else {
                isAsc = !th.classList.contains('asc');
            }

            // Limpiar clases de ordenamiento previas
            const headers = thead.getElementsByTagName('th');
            for (let i = 0; i < headers.length; i++) {
                headers[i].classList.remove('asc', 'desc');
            }

            // Establecer la nueva dirección de ordenamiento
            th.classList.add(isAsc ? 'asc' : 'desc');

            rows.sort((a, b) => {
                let aValue, bValue;

                if (forceDefaultSort) {
                    aValue = parseInt(a.getAttribute('data-id'));
                    bValue = parseInt(b.getAttribute('data-id'));
                    return bValue - aValue; // Descendente por ID
                }

                if (colIndex === 3) { // Columna de Fecha Creación
                    aValue = new Date(a.getAttribute('data-fecha-creacion'));
                    bValue = new Date(b.getAttribute('data-fecha-creacion'));
                    return isAsc ? aValue - bValue : bValue - aValue;
                }

                aValue = a.getElementsByTagName('td')[colIndex].textContent.toLowerCase();
                bValue = b.getElementsByTagName('td')[colIndex].textContent.toLowerCase();

                if (colIndex === 2 && tableId === 'materialesTable') { // Columna de Stock (numérica)
                    return isAsc ? aValue - bValue : bValue - aValue;
                }

                return isAsc
                    ? aValue.localeCompare(bValue)
                    : bValue.localeCompare(aValue);
            });

            const tbody = table.getElementsByTagName('tbody')[0];
            rows.forEach(row => tbody.appendChild(row));
        }

        // Funciones para la actualización en tiempo real del historial
        let historialUpdateInterval = null;

        function startHistorialUpdates() {
            if (historialUpdateInterval) return; // Evitar múltiples intervalos
            updateHistorialTable(); // Actualización inicial
            historialUpdateInterval = setInterval(updateHistorialTable, 5000); // Actualizar cada 5 segundos
        }

        function stopHistorialUpdates() {
            if (historialUpdateInterval) {
                clearInterval(historialUpdateInterval);
                historialUpdateInterval = null;
            }
        }

        function updateHistorialTable() {
            fetch('inventario.php?obtener_historial=1')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.querySelector('#historialTable tbody');
                        tbody.innerHTML = ''; // Limpiar la tabla

                        if (data.data.length === 0) {
                            tbody.innerHTML = "<tr><td colspan='6'>No hay cambios registrados.</td></tr>";
                        } else {
                            data.data.forEach(row => {
                                const tr = document.createElement('tr');
                                const nombreCompleto = row.nombres && row.apellidos ? `${row.nombres} ${row.apellidos}` : 'N/A';
                                tr.innerHTML = `
                                    <td>${nombreCompleto}</td>
                                    <td>${row.tabla_afectada ?? 'N/A'}</td>
                                    <td>${row.accion ?? 'N/A'}</td>
                                    <td>${row.id_registro ?? 'N/A'}</td>
                                    <td>${row.fecha_accion ?? 'N/A'}</td>
                                    <td>${row.detalles ? row.detalles : 'N/A'}</td>
                                `;
                                tbody.appendChild(tr);
                            });
                        }
                    } else {
                        console.error('Error al actualizar el historial:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error al actualizar el historial:', error);
                });
        }
    </script>
</body>
</html>


